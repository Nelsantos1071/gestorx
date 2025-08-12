<?php
// arquivo: client/editar_plano.php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['cliente_id'])) {
    header("Location: login.php");
    exit();
}

$cliente_id = $_SESSION['cliente_id'];
$mensagem = "";
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$site_domain = $protocol . '://' . $_SERVER['HTTP_HOST']; // Detecção automática do domínio

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: planos.php");
    exit();
}

$plano_id = intval($_GET['id']);

// Buscar dados do plano
try {
    $stmt = $pdo->prepare("SELECT id, nome, valor_1 FROM planos WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$plano_id, $cliente_id]);
    $plano = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plano) {
        $mensagem = "Plano não encontrado.";
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar plano: " . $e->getMessage());
    $mensagem = "Erro ao buscar plano.";
}

// Atualizar dados do plano
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    // Remover pontos e substituir vírgula por ponto para converter "1.234,56" em "1234.56"
    $valor_1 = str_replace(['.', ','], ['', '.'], $_POST['valor_1']);
    $valor_1 = floatval($valor_1);

    try {
        $stmt = $pdo->prepare("UPDATE planos SET nome = ?, valor_1 = ? WHERE id = ? AND cliente_id = ?");
        $stmt->execute([$nome, $valor_1, $plano_id, $cliente_id]);
        header("Location: planos.php?editado=1");
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar plano: " . $e->getMessage());
        $mensagem = "Erro ao atualizar o plano.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Editar Plano</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f0f0f0;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-top: 10px;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
        }
        button {
            margin-top: 20px;
            padding: 12px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
        }
        button:hover {
            background-color: #45a049;
        }
        .mensagem {
            margin-top: 15px;
            color: red;
            font-weight: bold;
            text-align: center;
        }
        a {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Editar Plano</h2>

        <?php if (!empty($mensagem)): ?>
            <p class="mensagem"><?php echo htmlspecialchars($mensagem); ?></p>
        <?php elseif ($plano): ?>
            <form method="POST">
                <label for="nome">Nome do Plano:</label>
                <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($plano['nome']) ?>" required>

                <label for="valor_1">Valor 1:</label>
                <input type="text" name="valor_1" id="valor_1" value="<?= number_format($plano['valor_1'], 2, ',', '.') ?>" required>

                <button type="submit">Salvar Alterações</button>
            </form>

            <a href="planos.php">← Voltar</a>
        <?php endif; ?>
    </div>

    <script>
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