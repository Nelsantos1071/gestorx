<?php
session_start();
require_once '../includes/db.php';

// Verifica se o administrador está logado
if (!isset($_SESSION['admin_id']) || !is_numeric($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Inicializa token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verifica se o ID do cliente foi fornecido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ver_usuarios.php");
    exit;
}

$clienteId = (int)$_GET['id'];

// Busca os dados do cliente
try {
    $stmt = $pdo->prepare("SELECT id, nome, email FROM clientes WHERE id = :id");
    $stmt->execute(['id' => $clienteId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        header("Location: ver_usuarios.php");
        exit;
    }
} catch (PDOException $e) {
    $error = "Erro ao carregar os dados do cliente: " . htmlspecialchars($e->getMessage());
}

// Processa o formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['email'], $_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);

        // Validação básica
        if (empty($nome) || empty($email)) {
            $error = "Nome e email são obrigatórios.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Email inválido.";
        } else {
            try {
                $stmtUpdate = $pdo->prepare("UPDATE clientes SET nome = :nome, email = :email WHERE id = :id");
                $stmtUpdate->execute([
                    'nome' => $nome,
                    'email' => $email,
                    'id' => $clienteId
                ]);
                header("Location: ver_usuarios.php");
                exit;
            } catch (PDOException $e) {
                $error = "Erro ao atualizar o cliente: " . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        $error = "Token CSRF inválido.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Editar Cliente</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        input[type="text"]:focus,
        input[type="email"]:focus {
            border-color: #007BFF;
            outline: none;
        }
        .btn {
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            color: #fff;
            transition: background-color 0.3s ease;
            font-size: 0.9rem;
            width: 100%;
            text-align: center;
        }
        .btn-primary {
            background-color: #007BFF;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
            margin-top: 10px;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            .container {
                padding: 15px 20px;
            }
            h2 {
                font-size: 1.5rem;
            }
            input[type="text"],
            input[type="email"] {
                font-size: 0.85rem;
            }
            .btn {
                font-size: 0.85rem;
                padding: 8px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 10px 15px;
            }
            h2 {
                font-size: 1.3rem;
            }
            label {
                font-size: 0.9rem;
            }
            input[type="text"],
            input[type="email"] {
                font-size: 0.8rem;
            }
            .btn {
                font-size: 0.8rem;
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/assets/sidebar.php'; ?>

    <div class="container">
        <h2>Editar Cliente</h2>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php elseif (isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($cliente['nome']) ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </form>
    </div>
</body>
</html>