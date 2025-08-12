<?php
session_start();
require_once '../includes/db.php';

// Verifica se está logado como admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $nivel = trim($_POST['nivel']);

    if ($nome && $email && $senha && $nivel) {
        $stmt = $pdo->prepare("INSERT INTO admins (nome, email, senha, nivel, criado_em) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$nome, $email, $senha, $nivel])) {
            header("Location: listar_admins.php");
            exit;
        } else {
            $mensagem = "Erro ao adicionar administrador.";
        }
    } else {
        $mensagem = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Administrador</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 500px;
            margin: 40px auto;
            background: #fff;
            padding: 30px 40px;
            border-left: 8px solid #293a5e;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #293a5e;
            margin-bottom: 25px;
        }

        label {
            font-weight: bold;
            color: #293a5e;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #293a5e;
            border: none;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #1e2e4b;
        }

        .alert {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
                margin: 20px;
            }

            h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
<?php include 'assets/sidebar.php'; ?>
<div class="container">
    <h2>Adicionar Administrador</h2>
    <?php if ($mensagem): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Nome:</label>
        <input type="text" name="nome" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Senha:</label>
        <input type="password" name="senha" required>

        <label>Nível (ex: superadmin, suporte):</label>
        <input type="text" name="nivel" required>

        <button type="submit">Salvar</button>
    </form>
</div>
</body>
</html>
