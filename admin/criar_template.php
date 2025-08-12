<?php
session_start();
include '../includes/db.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = trim($_POST['cliente_id'] ?? '');
    $template_text = trim($_POST['template_text'] ?? '');

    if (empty($cliente_id) || empty($template_text)) {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO whatsapp_template (cliente_id, template_text, updated_at) VALUES (:cliente_id, :template_text, NOW())");
            $stmt->execute([
                ':cliente_id' => $cliente_id,
                ':template_text' => $template_text
            ]);
            $sucesso = "Modelo criado com sucesso!";
            // Limpa campos
            $cliente_id = '';
            $template_text = '';
        } catch (PDOException $e) {
            $erro = "Erro ao salvar no banco: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <title>Criar Modelo de Mensagem - WhatsApp</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f8;
            margin: 0; padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #007BFF;
        }

        label {
            display: block;
            margin: 15px 0 5px;
            font-weight: 600;
            color: #333;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
            resize: vertical;
        }

        textarea {
            min-height: 120px;
        }

        button {
            margin-top: 20px;
            background-color: #28a745;
            border: none;
            padding: 12px 20px;
            color: white;
            font-size: 1.1rem;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #218838;
        }

        .message {
            margin: 20px 0;
            padding: 10px 15px;
            border-radius: 6px;
            font-weight: 600;
        }

        .error {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
        }

        .success {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }

        .back-link {
            display: block;
            margin-top: 25px;
            text-align: center;
            color: #007BFF;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Responsivo */
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Criar Novo Modelo de Mensagem</h2>

        <?php if ($erro): ?>
            <div class="message error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if ($sucesso): ?>
            <div class="message success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="cliente_id">ID do Cliente</label>
            <input type="text" id="cliente_id" name="cliente_id" value="<?= htmlspecialchars($cliente_id ?? '') ?>" required>

            <label for="template_text">Mensagem do Template</label>
            <textarea id="template_text" name="template_text" required><?= htmlspecialchars($template_text ?? '') ?></textarea>

            <button type="submit">Salvar Modelo</button>
        </form>

        <a href="admin_whatsapp_template.php" class="back-link">← Voltar para lista de modelos</a>
    </div>

</body>
</html>
