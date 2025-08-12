<?php
session_start();
require_once '../includes/db.php';

// Verificar se o usuário é administrador
if (!isset($_SESSION['admin_id']) || !is_int((int)$_SESSION['admin_id']) || (int)$_SESSION['admin_id'] <= 0) {
    header("Location: admin_login.php?msg=" . urlencode("Por favor, faça login como administrador."));
    exit;
}

$adminId = (int)$_SESSION['admin_id'];
$error = '';
$success = '';

// Buscar clientes e produtos para os selects
$clientes = [];
$produtos = [];
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT id, nome FROM clientes WHERE ativo = 1 ORDER BY nome");
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Clientes encontrados: " . count($clientes));

    $stmt = $pdo->query("SELECT id, titulo FROM produtos ORDER BY titulo");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Produtos encontrados: " . count($produtos));
} catch (PDOException $e) {
    $error = "Erro ao buscar dados: " . htmlspecialchars($e->getMessage());
    error_log($error);
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar_aluguel'])) {
    try {
        $cliente_id = (int)$_POST['cliente_id'];
        $produto_id = (int)$_POST['produto_id'];
        $quantidade = (int)$_POST['quantidade'];
        $url_download = trim($_POST['url_download']);
        $site = trim($_POST['site']);
        $usuario = trim($_POST['usuario']);
        $senha = trim($_POST['senha']);
        $chave_pix = trim($_POST['chave_pix']);

        // Validações
        if ($cliente_id <= 0 || $produto_id <= 0) {
            $error = "Selecione um cliente e um produto válidos.";
        } elseif ($quantidade <= 0) {
            $error = "A quantidade deve ser maior que zero.";
        } elseif ($url_download && !filter_var($url_download, FILTER_VALIDATE_URL)) {
            $error = "O URL de download fornecido não é válido.";
        } elseif ($site && !filter_var($site, FILTER_VALIDATE_URL)) {
            $error = "O URL do site fornecido não é válido.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO alugueis (cliente_id, produto_id, quantidade, data_aluguel, status, url_download, site, usuario, senha, chave_pix)
                VALUES (?, ?, ?, NOW(), 'ativo', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$cliente_id, $produto_id, $quantidade, $url_download ?: null, $site ?: null, $usuario ?: null, $senha ?: null, $chave_pix ?: null]);
            $success = "Aluguel registrado com sucesso!";
            error_log("Aluguel inserido: cliente_id=$cliente_id, produto_id=$produto_id, quantidade=$quantidade, url_download=" . ($url_download ?: 'null') . ", site=" . ($site ?: 'null') . ", usuario=" . ($usuario ?: 'null') . ", senha=" . ($senha ?: 'null') . ", chave_pix=" . ($chave_pix ?: 'null'));
            header("Location: admin_alugueis.php?success=" . urlencode($success));
            exit;
        }
    } catch (PDOException $e) {
        $error = "Erro ao registrar aluguel: " . htmlspecialchars($e->getMessage());
        error_log($error);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Adicionar Aluguel - Painel Administrativo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body, html {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
            height: 100vh;
            overflow-x: hidden;
        }
        a {
            text-decoration: none;
            color: inherit;
        }
        main {
            margin-left: 220px;
            padding: 20px 30px 40px;
            min-height: 100vh;
            background: #fff;
            box-shadow: 0 0 12px rgb(0 0 0 / 0.1);
            display: flex;
            flex-direction: column;
            gap: 25px;
            transition: margin-left 0.3s ease-in-out;
        }
        .welcome {
            text-align: center;
            color: #293a5e;
            font-weight: 700;
            font-size: 1.9rem;
        }
        .welcome p {
            font-weight: 400;
            font-size: 1.1rem;
            margin-top: 8px;
            color: #666;
        }
        .form-section {
            max-width: 600px;
            margin: 0 auto;
        }
        .form-section h2 {
            color: #293a5e;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 15px;
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        form label {
            font-weight: bold;
            color: #293a5e;
        }
        form select, form input[type="number"], form input[type="text"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        form button {
            padding: 10px;
            background: #293a5e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        form button:hover {
            background: #1a263e;
        }
        .error-message {
            color: #d93025;
            text-align: center;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8d7da;
            border-radius: 8px;
        }
        .success-message {
            color: #2e7d32;
            text-align: center;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px;
            background: #c8e6c9;
            border-radius: 8px;
        }
        @media (max-width: 768px) {
            main {
                margin-left: 0;
                padding-top: 20px;
            }
            .welcome {
                font-size: 1.5rem;
            }
            .form-section h2 {
                font-size: 1.3rem;
            }
            form select, form input[type="number"], form input[type="text"] {
                font-size: 0.9rem;
            }
            form button {
                font-size: 0.9rem;
            }
        }
        @media (max-width: 480px) {
            form select, form input[type="number"], form input[type="text"] {
                font-size: 0.85rem;
            }
            form button {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/assets/sidebar.php'; ?>

    <main role="main" tabindex="-1">
        <section class="welcome" aria-label="Mensagem de boas vindas">
            <h1>Adicionar Aluguel</h1>
            <p>Registre um novo aluguel para um cliente.</p>
        </section>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <section class="form-section" aria-label="Formulário de Aluguel">
                <h2>Novo Aluguel</h2>
                <form action="admin_alugueis.php" method="POST">
                    <label for="cliente_id">Cliente:</label>
                    <select name="cliente_id" id="cliente_id" required>
                        <option value="">Selecione um cliente</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente['id']; ?>">
                                <?php echo htmlspecialchars($cliente['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="produto_id">Produto:</label>
                    <select name="produto_id" id="produto_id" required>
                        <option value="">Selecione um produto</option>
                        <?php foreach ($produtos as $produto): ?>
                            <option value="<?php echo $produto['id']; ?>">
                                <?php echo htmlspecialchars($produto['titulo']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="quantidade">Quantidade:</label>
                    <input type="number" name="quantidade" id="quantidade" min="1" required>

                    <label for="url_download">URL de Download (opcional):</label>
                    <input type="text" name="url_download" id="url_download" placeholder="https://example.com/arquivo.pdf">

                    <label for="site">Site (opcional):</label>
                    <input type="text" name="site" id="site" placeholder="https://example.com">

                    <label for="usuario">Usuário (opcional):</label>
                    <input type="text" name="usuario" id="usuario" placeholder="Digite o usuário">

                    <label for="senha">Senha (opcional):</label>
                    <input type="text" name="senha" id="senha" placeholder="Digite a senha">

                    <label for="chave_pix">Chave Pixura (opcional):</label>
                    <input type="text" name="chave_pix" id="chave_pix" placeholder="Ex.: 12345678900 ou email@example.com">

                    <button type="submit" name="adicionar_aluguel">Adicionar Aluguel</button>
                </form>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>