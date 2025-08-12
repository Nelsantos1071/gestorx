<?php
// Configurações de segurança da sessão devem vir antes de session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

session_start();
require_once '../includes/db.php';

session_regenerate_id(true);

// Geração do token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verificar se o cliente está logado
if (!isset($_SESSION['cliente_id']) || !is_numeric($_SESSION['cliente_id'])) {
    header("Location: login.php?msg=" . urlencode("Por favor, faça login para ver seus planos."));
    exit;
}

$clienteId = (int)$_SESSION['cliente_id'];
$planos_criados = [];
$error = null;
$mensagem = null;

// Buscar dados do cliente para a sidebar
try {
    $stmt_cliente = $pdo->prepare("SELECT nome, email FROM clientes WHERE id = ? AND ativo = 1");
    $stmt_cliente->execute([$clienteId]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
    if (!$cliente) {
        session_destroy();
        header("Location: login.php?msg=" . urlencode("Conta não encontrada ou inativa."));
        exit;
    }
} catch (PDOException $e) {
    $error = "Erro ao buscar dados do cliente: " . $e->getMessage();
    error_log("Erro ao buscar dados do cliente (planos.php): " . $e->getMessage());
    $cliente = ['nome' => 'Cliente', 'email' => '']; // Fallback
}

// Processar adição de novo plano
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_plano'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "CSRF token inválido.";
    } else {
        $nome = trim($_POST['nome']);
        $valor_1 = str_replace(['.', ','], ['', '.'], trim($_POST['valor_1']));
        $valor_1 = floatval($valor_1);

        if (empty($nome)) {
            $error = "O nome do plano é obrigatório.";
        } elseif ($valor_1 <= 0) {
            $error = "O valor deve ser maior que zero.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO planos (nome, valor_1, cliente_id, criado_em) 
                                       VALUES (?, ?, ?, NOW())");
                $stmt->execute([$nome, $valor_1, $clienteId]);
                $mensagem = "Plano adicionado com sucesso!";
            } catch (PDOException $e) {
                $error = "Erro ao adicionar o plano: " . $e->getMessage();
                error_log("Erro ao adicionar plano (planos.php): " . $e->getMessage());
            }
        }
    }
}

// Processar exclusão de plano
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_plano'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "CSRF token inválido.";
    } else {
        $plano_id = (int)($_POST['plano_id'] ?? 0);
        try {
            // Verificar se o plano pertence ao cliente
            $stmt = $pdo->prepare("SELECT id FROM planos WHERE id = ? AND cliente_id = ?");
            $stmt->execute([$plano_id, $clienteId]);
            if (!$stmt->fetch()) {
                $error = "Plano inválido ou não pertence a este cliente.";
            } else {
                // Verificar se há faturas associadas
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM faturas WHERE plano_id = ?");
                $stmt->execute([$plano_id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Não é possível excluir o plano porque ele está associado a faturas.";
                } else {
                    // Excluir o plano
                    $stmt = $pdo->prepare("DELETE FROM planos WHERE id = ? AND cliente_id = ?");
                    $stmt->execute([$plano_id, $clienteId]);
                    $mensagem = "Plano excluído com sucesso!";
                }
            }
        } catch (PDOException $e) {
            $error = "Erro ao excluir o plano: " . $e->getMessage();
            error_log("Erro ao excluir plano (planos.php): " . $e->getMessage());
        }
    }
}

// Buscar planos associados ao cliente
try {
    $sql = "SELECT id, nome, valor_1
            FROM planos
            WHERE cliente_id = ?
            ORDER BY nome ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clienteId]);
    $planos_criados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao buscar seus planos: " . $e->getMessage();
    error_log("Erro ao buscar planos (planos.php): " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meus Planos Criados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Reset e básico */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, html { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; color: #333; min-height: 100vh; }
        a { text-decoration: none; color: inherit; }

        /* Menu icon para mobile */
        .menu-icon {
            display: none;
            cursor: pointer;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1200; /* Above sidebar */
            width: 35px;
            height: 30px;
            user-select: none;
        }
        .menu-icon div {
            width: 35px;
            height: 4px;
            background-color: #000;
            margin: 6px 0;
            border-radius: 3px;
            transition: 0.4s;
        }
        .menu-icon.open div {
            background-color: #f0a500;
        }
        .menu-icon.open div:nth-child(1) {
            transform: rotate(-45deg) translate(-8px, 7px);
        }
        .menu-icon.open div:nth-child(2) {
            opacity: 0;
        }
        .menu-icon.open div:nth-child(3) {
            transform: rotate(45deg) translate(-8px, -7px);
        }

        /* Conteúdo principal */
        .main-content {
            margin-left: 220px;
            padding: 25px 30px;
            transition: margin-left 0.3s ease-in-out;
            min-height: 100vh;
            z-index: 1; /* Below sidebar */
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2 {
            font-size: 1.9rem;
            margin-bottom: 15px;
            color: #293a5e;
        }
        h2 {
            font-size: 1.5rem;
            margin-top: 20px;
        }
        .msg-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .msg-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        /* Tabela */
        .table-container {
            overflow-x: auto;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 300px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
            word-wrap: break-word;
        }
        th {
            background-color: #293a5e;
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        td.actions {
            display: flex;
            gap: 5px;
            align-items: center;
            flex-wrap: nowrap;
        }
        td.actions .btn {
            flex: 1;
            max-width: 100px;
            margin: 0;
        }

        /* Botões */
        .btn {
            padding: 7px 14px;
            font-weight: 600;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-align: center;
            min-width: 80px;
            display: inline-block;
            user-select: none;
            box-sizing: border-box;
            line-height: 1.5;
        }
        .btn-edit {
            background-color: #f0a500;
            color: white;
        }
        .btn-edit:hover {
            background-color: #d48f00;
        }
        .btn-delete {
            background-color: #dc3545;
            color: white;
        }
        .btn-delete:hover {
            background-color: #b02a37;
        }
        .btn-add {
            background-color: #28a745;
            color: white;
            padding: 12px 24px;
            font-size: 1rem;
            width: auto;
            margin: 20px auto;
            display: block;
            height: 44px;
        }
        .btn-add:hover {
            background-color: #1e7e34;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        .modal-content h3 {
            margin-bottom: 15px;
            color: #293a5e;
        }
        .modal-content .close {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
            color: #333;
        }
        .modal-content label {
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
            color: #293a5e;
        }
        .modal-content input[type="text"] {
            width: 100%;
            padding: 8px 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        .modal-content input[type="text"]:focus {
            border-color: #f0a500;
            outline: none;
        }
        .modal-content .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .modal-content .form-group {
            flex: 1;
            min-width: 150px;
        }
        .modal-content button {
            width: auto;
            padding: 12px 20px;
            background-color: #28a745;
            height: auto;
        }
        .modal-content button:hover {
            background-color: #1e7e34;
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            .menu-icon {
                display: block;
            }
            .main-content {
                margin-left: 0;
                padding: 80px 10px 20px;
            }
            .container {
                max-width: 95vw;
                padding: 0 10px;
            }
            th, td {
                padding: 8px;
                font-size: 0.8rem;
            }
            td.actions .btn {
                font-size: 0.85rem;
                padding: 5px 10px;
                min-width: 70px;
                max-width: 90px;
            }
            .modal-content {
                max-width: 400px;
                margin: 10px;
            }
            .btn-add {
                padding: 12px 20px;
                font-size: 0.95rem;
            }
            .msg-success, .msg-error {
                font-size: 0.9rem;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
   <?php include __DIR__ . '/assets/sidebar.php'; ?>

    <main class="main-content" role="main">
        <div class="container">
            <h1>Meus Planos Criados</h1>

            <?php if ($mensagem): ?>
                <div class="msg-success" role="alert"><?=htmlspecialchars($mensagem);?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="msg-error" role="alert"><?=htmlspecialchars($error);?></div>
            <?php endif; ?>

            <?php if ($planos_criados): ?>
                <div class="table-container">
                    <table aria-describedby="planos_descr">
                        <caption id="planos_descr" style="text-align:left; margin-bottom: 10px; font-weight:600;">
                            Planos vinculados à sua conta:
                        </caption>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Valor</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($planos_criados as $plano): ?>
                                <tr>
                                    <td><?=htmlspecialchars($plano['nome']);?></td>
                                    <td>R$ <?=number_format($plano['valor_1'], 2, ',', '.');?></td>
                                    <td class="actions">
                                        <a href="editar_plano.php?id=<?= (int)$plano['id'] ?>" class="btn btn-edit" aria-label="Editar plano <?=htmlspecialchars($plano['nome']);?>">
                                            <i class="fa fa-edit"></i> Editar
                                        </a>
                                        <form method="post" onsubmit="return confirm('Deseja realmente excluir este plano?');" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token']);?>">
                                            <input type="hidden" name="plano_id" value="<?= (int)$plano['id'] ?>">
                                            <button type="submit" name="delete_plano" class="btn btn-delete" aria-label="Excluir plano <?=htmlspecialchars($plano['nome']);?>">
                                                <i class="fa fa-trash"></i> Excluir
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>Você ainda não criou planos.</p>
            <?php endif; ?>

            <h2>Adicionar Novo Plano</h2>
            <button class="btn btn-add" onclick="openAddModal()" aria-label="Adicionar novo plano">Adicionar Novo Plano</button>
        </div>

        <!-- Add Plan Modal -->
        <div class="modal" id="addModal">
            <div class="modal-content">
                <span class="close" onclick="closeAddModal()">×</span>
                <h3>Adicionar Novo Plano</h3>
                <form method="post" novalidate autocomplete="off" aria-label="Formulário para adicionar novo plano">
                    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token']);?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nome">Nome do Plano *</label>
                            <input type="text" id="nome" name="nome" required placeholder="Nome do plano">
                        </div>
                        <div class="form-group">
                            <label for="valor_1">Valor *</label>
                            <input type="text" id="valor_1" name="valor_1" required placeholder="0,00">
                        </div>
                    </div>
                    <button type="submit" name="add_plano" class="btn btn-add" aria-label="Adicionar plano">Adicionar Plano</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Acessibilidade: toggle via teclado
        const menuIcon = document.querySelector('.menu-icon');
        if (menuIcon) {
            menuIcon.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleMenu();
                }
            });
        }

        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
            document.getElementById('nome').focus();
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        // Função para formatar o campo de valor enquanto o usuário digita
        function formatarValor(input) {
            let valor = input.value.replace(/\D/g, ''); // Remove tudo que não é dígito
            if (valor.length === 0) {
                input.value = '';
                return;
            }
            // Adiciona dois dígitos decimais
            valor = (parseInt(valor) / 100).toFixed(2); // Divide por 100 para 2 casas decimais
            // Formata com ponto como separador de milhares e vírgula como decimal
            valor = valor.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            input.value = valor;
        }

        // Aplica a formatação ao campo valor_1
        document.getElementById('valor_1').addEventListener('input', function() {
            formatarValor(this);
        });
    </script>
</body>
</html>