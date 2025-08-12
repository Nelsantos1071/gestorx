<?php
session_start();
require_once '../includes/db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['cliente_id']) || !is_numeric($_SESSION['cliente_id'])) {
    header("Location: login.php?msg=" . urlencode("Por favor, faça login para acessar os servidores."));
    exit;
}

$clientId = (int)$_SESSION['cliente_id'];
$isAdmin = false;

// Check if user is an admin (match by email)
try {
    $stmt = $pdo->prepare("SELECT email FROM clientes WHERE id = ?");
    $stmt->execute([$clientId]);
    $clientEmail = $stmt->fetchColumn();

    if ($clientEmail) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND nivel = 'superadmin'");
        $stmt->execute([$clientEmail]);
        $isAdmin = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
} catch (PDOException $e) {
    $_SESSION['errorMsg'] = "Erro ao verificar admin: " . $e->getMessage();
}

// Fetch client data and check active status
try {
    $stmt = $pdo->prepare("SELECT nome, email, ativo FROM clientes WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        session_destroy();
        header("Location: login.php?msg=" . urlencode("Conta não encontrada. Contate o administrador."));
        exit;
    }
    $client['ativo'] = $client['ativo'] ?? 0;
} catch (PDOException $e) {
    $_SESSION['errorMsg'] = "Erro ao buscar dados do cliente: " . $e->getMessage();
}

// Handle form submission to add a new server
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_server'])) {
    $brand = filter_input(INPUT_POST, 'marca', FILTER_SANITIZE_STRING);

    if ($brand) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO servidores (cliente_id, marca, criado_em)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$clientId, $brand]);
            $_SESSION['success'] = "Servidor foi adicionado!";
        } catch (PDOException $e) {
            $_SESSION['errorMsg'] = "Erro ao adicionar servidor: " . $e->getMessage();
        }
    } else {
        $_SESSION['errorMsg'] = "Por favor, preencha o campo 'Marca'.";
    }
    header("Location: servidores.php");
    exit;
}

// Handle server edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_server'])) {
    $server_id = filter_input(INPUT_POST, 'server_id', FILTER_VALIDATE_INT);
    $brand = filter_input(INPUT_POST, 'marca', FILTER_SANITIZE_STRING);

    if ($server_id && $brand) {
        try {
            $stmt = $pdo->prepare("
                UPDATE servidores
                SET marca = ?
                WHERE id = ? AND cliente_id = ?
            ");
            $stmt->execute([$brand, $server_id, $clientId]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "Servidor foi atualizado!";
            } else {
                $_SESSION['errorMsg'] = "Servidor não foi encontrado ou não pertence a você.";
            }
        } catch (PDOException $e) {
            $_SESSION['errorMsg'] = "Erro ao atualizar servidor: " . $e->getMessage();
        }
    } else {
        $_SESSION['errorMsg'] = "Por favor, preencha o campo 'Marca'.";
    }
    header("Location: servidores.php");
    exit;
}

// Handle server deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_server'])) {
    $server_id = filter_input(INPUT_POST, 'server_id', FILTER_VALIDATE_INT);
    if ($server_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM servidores WHERE id = ? AND cliente_id = ?");
            $stmt->execute([$server_id, $clientId]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['success'] = "Servidor foi removido!";
            } else {
                $_SESSION['errorMsg'] = "Servidor não foi encontrado ou não pertence a você.";
            }
        } catch (PDOException $e) {
            $_SESSION['errorMsg'] = "Erro ao remover servidor: " . $e->getMessage();
        }
    } else {
        $_SESSION['errorMsg'] = "ID de servidor inválido.";
    }
    header("Location: servidores.php");
    exit;
}

// Fetch servers (client-specific or all for admins)
try {
    if ($isAdmin) {
        $stmt = $pdo->query("
            SELECT s.id, s.marca, s.cliente_id, c.nome AS cliente_nome
            FROM servidores s
            JOIN clientes c ON s.cliente_id = c.id
            ORDER BY s.criado_em DESC
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT id, marca, cliente_id
            FROM servidores
            WHERE cliente_id = ?
            ORDER BY criado_em DESC
        ");
        $stmt->execute([$clientId]);
    }
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['errorMsg'] = "Erro ao buscar servidores: " . $e->getMessage();
    $servers = [];
}

// Fetch server data for edit modal
$editServer = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $server_id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("
            SELECT id, marca
            FROM servidores
            WHERE id = ? AND cliente_id = ?
        ");
        $stmt->execute([$server_id, $clientId]);
        $editServer = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['errorMsg'] = "Erro ao buscar servidor para edição: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servidores Dedicados</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body, html {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a2a44 0%, #2e4a7b 100%);
            color: #333;
            height: 100vh;
            overflow-x: hidden;
            /* Optional: Uncomment for server-themed background image */
            /* background: url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1920&q=80') no-repeat center center fixed; */
            /* background-size: cover; */
            /* background-blend-mode: overlay; */
        }
        a {
            text-decoration: none;
            color: inherit;
        }
        .menu-icon {
            display: none;
            cursor: pointer;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            width: 40px;
            height: 35px;
            user-select: none;
        }
        .menu-icon div {
            width: 40px;
            height: 5px;
            background-color: #fff;
            margin: 7px 0;
            border-radius: 3px;
            transition: 0.4s;
        }
        .menu-icon.open div {
            background-color: #f0a500;
        }
        .menu-icon.open div:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }
        .menu-icon.open div:nth-child(2) {
            opacity: 0;
        }
        .menu-icon.open div:nth-child(3) {
            transform: rotate(-45deg) translate(9px, -9px);
        }
        main {
            margin-left: 240px;
            padding: 30px 60px 50px;
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            gap: 30px;
            transition: margin-left 0.3s ease-in-out;
        }
        .container {
            max-width: 1300px;
            margin: 0 30px;
        }
        h2 {
            color: #293a5e;
            text-align: center;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
            font-size: 1.1rem;
            position: relative;
        }
        th {
            background-color: #293a5e;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        /* Right-align Ações header */
        th:last-child {
            text-align: right;
        }
        tr:hover {
            background-color: #f8fafc;
        }
        /* Vertical line separator between Servidor and Ações */
        td:first-child::after {
            content: '';
            position: absolute;
            top: 10%;
            bottom: 10%;
            right: 0;
            width: 2px;
            background-color: #bbb;
        }
        th:first-child::after {
            content: '';
            position: absolute;
            top: 10%;
            bottom: 10%;
            right: 0;
            width: 2px;
            background-color: #fff;
        }
        /* Right-align actions column */
        td.actions {
            text-align: right;
        }
        .actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 12px;
        }
        .action-separator {
            color: #bbb;
            font-size: 1.2rem;
            font-weight: bold;
        }
        button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
            font-size: 1rem;
            font-weight: 600;
            width: 100px;
            height: 40px;
            line-height: 20px;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .edit-btn {
            background-color: #007bff;
            color: white;
        }
        .edit-btn:hover {
            background-color: #0056b3;
        }
        .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .delete-btn:hover {
            background-color: #c82333;
        }
        .add-btn {
            background-color: #28a745;
            color: white;
            width: auto;
            padding: 12px 24px;
            margin: 25px auto;
            display: block;
            height: auto;
            font-size: 1.1rem;
            border-radius: 6px;
        }
        .add-btn:hover {
            background-color: #218838;
        }
        .message {
            padding: 12px;
            margin: 25px auto;
            max-width: 700px;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .message.info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
        }
        .modal-content h3 {
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #293a5e;
        }
        .modal-content .close {
            float: right;
            font-size: 1.8rem;
            cursor: pointer;
            color: #333;
            transition: color 0.2s;
        }
        .modal-content .close:hover {
            color: #dc3545;
        }
        .modal-content label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #293a5e;
            font-size: 1.1rem;
        }
        .modal-content input {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }
        .modal-content button {
            width: auto;
            padding: 12px 24px;
            background-color: #28a745;
            height: auto;
            font-size: 1.1rem;
        }
        .modal-content button:hover {
            background-color: #218838;
        }
        @media (max-width: 768px) {
            main {
                margin-left: 0 !important;
                padding: 80px 30px 30px;
            }
            .menu-icon {
                display: block;
            }
            .container {
                max-width: 100%;
                margin: 0 15px;
            }
            h2 {
                font-size: 1.5rem;
            }
            table, th, td {
                font-size: 0.95rem;
            }
            th {
                font-size: 1rem;
            }
            th:last-child {
                text-align: right;
            }
            td:first-child::after, th:first-child::after {
                width: 1px;
            }
            td.actions {
                text-align: right;
            }
            button {
                width: 90px;
                height: 36px;
                font-size: 0.9rem;
            }
            .add-btn {
                padding: 10px 20px;
                font-size: 1rem;
            }
            .message {
                font-size: 1rem;
                margin: 20px 10px;
            }
            .modal-content {
                padding: 20px;
                max-width: 95%;
            }
            .modal-content h3 {
                font-size: 1.3rem;
            }
            .modal-content label {
                font-size: 1rem;
            }
            .modal-content input {
                padding: 8px;
                font-size: 0.95rem;
            }
            .modal-content button {
                padding: 10px 20px;
                font-size: 1rem;
            }
            .action-separator {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/assets/sidebar.php'; ?>

    <main role="main" tabindex="-1">
        <div class="container">
            <h2>Servidores Dedicados</h2>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="message success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['errorMsg'])): ?>
                <div class="message error"><?php echo htmlspecialchars($_SESSION['errorMsg']); ?></div>
                <?php unset($_SESSION['errorMsg']); ?>
            <?php endif; ?>

            <!-- Button to open add server modal -->
            <button class="add-btn" onclick="openAddModal()" aria-label="Adicionar novo servidor">Adicionar Servidor</button>

            <?php if (!$client['ativo']): ?>
                <div class="message info">Sua conta está inativa. Contate o administrador para ativá-la.</div>
            <?php elseif (empty($servers)): ?>
                <div class="message error">Nenhum servidor disponível no momento. Adicione um servidor acima.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Servidor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servers as $server): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($server['marca']); ?></td>
                                <td class="actions">
                                    <?php if (!$isAdmin): ?>
                                        <button type="button" class="edit-btn" onclick="openEditModal(<?php echo htmlspecialchars($server['id']); ?>)" aria-label="Editar servidor <?php echo htmlspecialchars($server['marca']); ?>">Editar</button>
                                        <span class="action-separator">|</span>
                                        <form method="post" onsubmit="return confirm('Tem certeza que deseja excluir o servidor <?php echo htmlspecialchars($server['marca']); ?>?');" style="display:inline;">
                                            <input type="hidden" name="server_id" value="<?php echo htmlspecialchars($server['id']); ?>">
                                            <button type="submit" name="delete_server" class="delete-btn" aria-label="Excluir servidor <?php echo htmlspecialchars($server['marca']); ?>">Excluir</button>
                                        </form>
                                    <?php else: ?>
                                        <span>Cliente: <?php echo htmlspecialchars($server['cliente_nome']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Add Server Modal -->
        <div class="modal" id="addModal">
            <div class="modal-content">
                <span class="close" onclick="closeAddModal()">×</span>
                <h3>Adicionar Novo Servidor</h3>
                <form method="post" id="addForm">
                    <label for="add_marca">Marca do Servidor</label>
                    <input type="text" name="marca" id="add_marca" required>
                    <button type="submit" name="add_server">Adicionar Servidor</button>
                </form>
            </div>
        </div>

        <!-- Edit Server Modal -->
        <?php if ($editServer): ?>
            <div class="modal" id="editModal" style="display: block;">
                <div class="modal-content">
                    <span class="close" onclick="closeEditModal()">×</span>
                    <h3>Editar Servidor: <?php echo htmlspecialchars($editServer['marca']); ?></h3>
                    <form method="post" id="editForm">
                        <input type="hidden" name="server_id" value="<?php echo htmlspecialchars($editServer['id']); ?>">
                        <label for="edit_marca">Marca do Servidor</label>
                        <input type="text" name="marca" id="edit_marca" value="<?php echo htmlspecialchars($editServer['marca']); ?>" required>
                        <button type="submit" name="edit_server">Salvar Alterações</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openEditModal(serverId) {
            window.location.href = 'servidores.php?edit=' + serverId;
        }

        function closeEditModal() {
            window.location.href = 'servidores.php';
        }
    </script>
</body>
</html>