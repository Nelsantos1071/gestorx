<?php
session_start();
require_once '../includes/db.php';

// Verifica se admin está logado
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Verifica CSRF token para o POST (para segurança)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Erro: token CSRF inválido');
    }

    if (!isset($_POST['cliente_id']) || !is_numeric($_POST['cliente_id'])) {
        die('Erro: cliente inválido');
    }

    $clienteId = (int)$_POST['cliente_id'];

    // Aqui você processa a geração da fatura, por exemplo, inserindo no banco
    // ou gerando um boleto/Pix. Aqui vamos só simular inserção no banco.

    $valor_fatura = 100.00; // exemplo fixo, pode ser dinâmico

    $stmt = $pdo->prepare("INSERT INTO faturas (cliente_id, valor, status, data_criacao) VALUES (:cliente_id, :valor, 'pendente', NOW())");
    $stmt->execute([
        ':cliente_id' => $clienteId,
        ':valor' => $valor_fatura,
    ]);

    $_SESSION['mensagem'] = "Fatura gerada com sucesso para o cliente ID $clienteId.";

    header('Location: clientes.php'); // redireciona de volta para a lista de clientes
    exit;
}

// Geração do token CSRF para o formulário
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Validação do parâmetro GET
if (!isset($_GET['cliente_id']) || !is_numeric($_GET['cliente_id'])) {
    die("Cliente inválido.");
}

$clienteId = (int)$_GET['cliente_id'];

// Buscar dados do cliente
$stmt = $pdo->prepare("SELECT nome, email FROM clientes WHERE id = :id");
$stmt->execute([':id' => $clienteId]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("Cliente não encontrado.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Gerar Fatura - <?= htmlspecialchars($cliente['nome']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #f9f9f9; }
        .container { background: #fff; padding: 20px; border-radius: 6px; max-width: 500px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #333; }
        p { font-size: 1.1em; }
        button { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        button:hover { background-color: #218838; }
        a { display: inline-block; margin-top: 15px; text-decoration: none; color: #007bff; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Gerar Fatura para <?= htmlspecialchars($cliente['nome']) ?></h2>
        <p><strong>Email:</strong> <?= htmlspecialchars($cliente['email']) ?></p>
        <p><strong>Valor da fatura:</strong> R$ 100,00</p> <!-- Substitua pelo valor real, se precisar -->

        <form action="gerar_fatura.php" method="post">
            <input type="hidden" name="cliente_id" value="<?= $clienteId ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit">Confirmar e Gerar Fatura</button>
        </form>

        <a href="clientes.php">&larr; Voltar para a lista de clientes</a>
    </div>
</body>
</html>
