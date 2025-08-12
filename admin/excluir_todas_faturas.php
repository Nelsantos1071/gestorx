<?php
include_once '../includes/db.php'; // sua conexão PDO

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    try {
        // Executa a exclusão de todas as faturas
        $stmt = $pdo->prepare("DELETE FROM faturas");
        $stmt->execute();

        // Redireciona para faturas.php após exclusão
        header("Location: faturas.php");
        exit;
    } catch (PDOException $e) {
        $message = "Erro ao excluir as faturas: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Excluir Todas as Faturas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0; padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .container {
            background: white;
            padding: 25px 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        h2 {
            margin-bottom: 20px;
            color: #dc3545;
        }
        button {
            background: #dc3545;
            border: none;
            color: white;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            margin-top: 20px;
        }
        button:hover {
            background: #b52a37;
        }
        .message {
            margin-top: 15px;
            font-weight: bold;
            color: green;
        }
        .warning {
            color: #b52a37;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Excluir Todas as Faturas</h2>
    <p class="warning">Esta ação <strong>não pode ser desfeita</strong>. Você realmente deseja excluir todas as faturas do banco de dados?</p>

    <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
    <?php else: ?>
        <form method="post" onsubmit="return confirm('Confirma exclusão de todas as faturas? Esta ação é irreversível!');">
            <input type="hidden" name="confirm" value="yes">
            <button type="submit">Excluir Todas as Faturas</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
