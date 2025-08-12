<?php
session_start();
require_once '../includes/db.php';

// Verifica se o administrador está logado
if (!isset($_SESSION['admin_id']) || !is_numeric($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, nome, email, nivel, criado_em FROM admins ORDER BY id DESC");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao buscar os administradores: " . htmlspecialchars($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Administradores</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            background: #fff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-top {
            text-align: right;
            margin-bottom: 15px;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            margin-left: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
            color: white;
        }
        .btn-add {
            background-color: #293a5e; /* azul personalizado */
        }
        .btn-add:hover {
            background-color: #1f2a4a; /* azul mais escuro */
        }
        .btn-edit {
            background-color: #293a5e; /* mesmo azul */
        }
        .btn-edit:hover {
            background-color: #1f2a4a;
        }
        .btn-delete {
            background-color: #dc3545;
        }
        .btn-delete:hover {
            background-color: #b02a37;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ccc;
        }
        th {
            background-color: #007BFF;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                display: none;
            }
            tr {
                margin-bottom: 15px;
            }
            td {
                position: relative;
                padding-left: 50%;
                text-align: right;
                border: none;
                border-bottom: 1px solid #ccc;
            }
            td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: 45%;
                padding-right: 10px;
                font-weight: bold;
                text-align: left;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/assets/sidebar.php'; ?>

<div class="container">
    <h2>Lista de Administradores</h2>

    <div class="btn-top">
        <a href="adicionar_admin.php" class="btn btn-add">+ Adicionar Administrador</a>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php elseif (empty($admins)): ?>
        <div class="alert alert-danger">Nenhum administrador encontrado.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Nível</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td data-label="ID"><?= htmlspecialchars($admin['id']) ?></td>
                        <td data-label="Nome"><?= htmlspecialchars($admin['nome']) ?></td>
                        <td data-label="Email"><?= htmlspecialchars($admin['email']) ?></td>
                        <td data-label="Nível"><?= htmlspecialchars($admin['nivel']) ?></td>
                        <td data-label="Criado em"><?= htmlspecialchars($admin['criado_em']) ?></td>
                        <td data-label="Ações">
                            <a href="editar_admin.php?id=<?= $admin['id'] ?>" class="btn btn-edit">Editar</a>
                            <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                <a href="excluir_admin.php?id=<?= $admin['id'] ?>" class="btn btn-delete" onclick="return confirm('Tem certeza que deseja excluir este administrador?');">Excluir</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
