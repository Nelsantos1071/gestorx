<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$id = (int) $_GET['id'];
$mensagem = '';

$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$id]);
$admin = $stmt->fetch();

if (!$admin) {
    die("Administrador não encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $nivel = trim($_POST['nivel']);

    $senhaSql = '';
    $params = [$nome, $email, $nivel];

    if (!empty($_POST['senha'])) {
        $senhaSql = ", senha = ?";
        $params[] = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    }

    $params[] = $id;

    $stmt = $pdo->prepare("UPDATE admins SET nome = ?, email = ?, nivel = ? $senhaSql WHERE id = ?");
    if ($stmt->execute($params)) {
        header("Location: listar_admins.php");
        exit;
    } else {
        $mensagem = "Erro ao atualizar.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Administrador</title>
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
    <h2>Editar Administrador</h2>
    <?php if ($mensagem): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($mensagem) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Nome:</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($admin['nome']) ?>" required>

        <label>Email:</label>
        <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>

        <label>Nova Senha (opcional):</label>
        <input type="password" name="senha">

        <label>Nível:</label>
        <input type="text" name="nivel" value="<?= htmlspecialchars($admin['nivel']) ?>" required>

        <button type="submit">Atualizar</button>
    </form>
</div>
</body>
</html>
