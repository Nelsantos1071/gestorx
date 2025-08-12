<?php
// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Enable if HTTPS

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';

session_regenerate_id(true);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['cliente_id']) || !is_numeric($_SESSION['cliente_id'])) {
    header("Location: login.php?msg=" . urlencode("Por favor, faça login."));
    exit;
}

$clienteId = (int)$_SESSION['cliente_id'];
$primeiroNome = ''; // For sidebar greeting
$templateUsers = []; // Array to store template_users data

try {
    $stmt = $pdo->prepare("SELECT nome, email, chave_pix FROM clientes WHERE id = ? AND ativo = 1");
    $stmt->execute([$clienteId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        session_destroy();
        header("Location: login.php?msg=" . urlencode("Conta não encontrada ou inativa."));
        exit;
    }
    $primeiroNome = explode(' ', $cliente['nome'])[0]; // Extract first name for sidebar
} catch (PDOException $e) {
    $error = "Erro ao buscar cliente: " . $e->getMessage();
    error_log("Client fetch error: " . $e->getMessage());
}

// Fetch template_users data
try {
    $stmt = $pdo->prepare("SELECT id, cliente_id, titulo, template_text, updated_at FROM template_users WHERE cliente_id = ?");
    $stmt->execute([$clienteId]);
    $templateUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $error ?? "Erro ao buscar templates: " . $e->getMessage();
    error_log("Template fetch error: " . $e->getMessage());
}

$mensagem = '';
$users = [];
$servidores = [];
$planos = [];
$totalPages = 1;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "CSRF token inválido.";
    } else {
        try {
            if (isset($_POST['action']) && $_POST['action'] === 'add') {
                $nome = trim($_POST['nome']);
                $usuario = trim($_POST['usuario']);
                $senha = trim($_POST['senha']); // Store plaintext
                $celular = preg_replace('/\D/', '', $_POST['celular']);
                $vencimento = $_POST['vencimento'] ?: null;
                $servidor_id = (int)$_POST['servidor_id'];
                $plano_id = (int)$_POST['plano_id'];

                if (!preg_match('/^\d{11}$/', $celular)) {
                    $error = "Celular inválido. Use (XX) XXXXX-XXXX.";
                } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $usuario)) {
                    $error = "Usuário deve conter apenas letras, números e sublinhados.";
                } elseif (empty($nome)) {
                    $error = "O nome completo é obrigatório.";
                } elseif (!$plano_id) {
                    $error = "Selecione um plano.";
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE usuario = ?");
                    $stmt->execute([$usuario]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "O nome de usuário '$usuario' já está em uso.";
                    } else {
                        $stmt = $pdo->prepare("SELECT id FROM servidores WHERE id = ? AND cliente_id = ? AND ativo = 1");
                        $stmt->execute([$servidor_id, $clienteId]);
                        if (!$stmt->fetch()) {
                            $error = "Servidor inválido ou não associado a este cliente.";
                        } else {
                            $stmt = $pdo->prepare("SELECT id FROM planos WHERE id = ? AND cliente_id = ?");
                            $stmt->execute([$plano_id, $clienteId]);
                            if (!$stmt->fetch()) {
                                $error = "Plano inválido ou não associado ao cliente.";
                            } else {
                                $stmt = $pdo->prepare("INSERT INTO users (nome_completo, usuario, senha, celular, vencimento, servidor_id, plano_id, created_by, created_at, ativo) 
                                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)");
                                $stmt->execute([$nome, $usuario, $senha, $celular, $vencimento, $servidor_id, $plano_id, $clienteId]);
                                $mensagem = "Usuário cadastrado com sucesso!";
                            }
                        }
                    }
                }
            } elseif (isset($_POST['action']) && $_POST['action'] === 'edit') {
                $id = (int)$_POST['id'];
                $nome = trim($_POST['nome']);
                $usuario = trim($_POST['usuario']);
                $senha = !empty(trim($_POST['senha'])) ? trim($_POST['senha']) : null; // Store plaintext
                $celular = preg_replace('/\D/', '', $_POST['celular']);
                $vencimento = $_POST['vencimento'] ?: null;
                $servidor_id = (int)$_POST['servidor_id'];
                $plano_id = (int)$_POST['plano_id'];

                if (!preg_match('/^\d{11}$/', $celular)) {
                    $error = "Celular inválido. Use (XX) XXXXX-XXXX.";
                } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $usuario)) {
                    $error = "Usuário deve conter apenas letras, números e sublinhados.";
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE usuario = ? AND user_id != ?");
                    $stmt->execute([$usuario, $id]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "O nome de usuário '$usuario' já está em uso por outro usuário.";
                    } else {
                        $stmt = $pdo->prepare("SELECT id FROM servidores WHERE id = ? AND cliente_id = ? AND ativo = 1");
                        $stmt->execute([$servidor_id, $clienteId]);
                        if (!$stmt->fetch()) {
                            $error = "Servidor inválido ou não associado a este cliente.";
                        } else {
                            if ($plano_id) {
                                $stmt = $pdo->prepare("SELECT id FROM planos WHERE id = ? AND cliente_id = ?");
                                $stmt->execute([$plano_id, $clienteId]);
                                if (!$stmt->fetch()) {
                                    $error = "Plano inválido ou não associado a este cliente.";
                                }
                            }
                            if (!isset($error)) {
                                if ($senha) {
                                    $stmt = $pdo->prepare("UPDATE users SET nome_completo = ?, usuario = ?, senha = ?, celular = ?, vencimento = ?, servidor_id = ?, plano_id = ? 
                                                           WHERE user_id = ? AND created_by = ? AND ativo = 1");
                                    $stmt->execute([$nome, $usuario, $senha, $celular, $vencimento, $servidor_id, $plano_id, $id, $clienteId]);
                                } else {
                                    $stmt = $pdo->prepare("UPDATE users SET nome_completo = ?, usuario = ?, celular = ?, vencimento = ?, servidor_id = ?, plano_id = ? 
                                                           WHERE user_id = ? AND created_by = ? AND ativo = 1");
                                    $stmt->execute([$nome, $usuario, $celular, $vencimento, $servidor_id, $plano_id, $id, $clienteId]);
                                }
                                $mensagem = "Usuário atualizado com sucesso!";
                            }
                        }
                    }
                }
            } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND created_by = ? AND ativo = 1");
                $stmt->execute([$id, $clienteId]);
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND created_by = ?");
                    $stmt->execute([$id, $clienteId]);
                    $mensagem = "Usuário excluído com sucesso!";
                } else {
                    $error = "Usuário não encontrado ou não autorizado.";
                }
            }
        } catch (PDOException $e) {
            $error = "Erro ao processar operação: " . $e->getMessage();
            error_log("Form error: " . $e->getMessage());
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT id, marca FROM servidores WHERE cliente_id = ? AND ativo = 1");
    $stmt->execute([$clienteId]);
    $servidores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $error ?? "Erro ao buscar servidores: " . $e->getMessage();
    error_log("Server fetch error: " . $e->getMessage());
}

try {
    $stmt = $pdo->prepare("SELECT id, nome, valor_1, valor_2, valor_3, valor_4 FROM planos WHERE cliente_id = ?");
    $stmt->execute([$clienteId]);
    $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $error ?? "Erro ao buscar planos: " . $e->getMessage();
    error_log("Planos fetch error: " . $e->getMessage());
}

$limit = 10;
$page = max(1, $page);
$offset = ($page - 1) * $limit;

try {
    $stmt = $pdo->prepare("SELECT u.user_id, u.nome_completo, u.usuario, u.senha, u.celular, u.created_at, u.vencimento, s.marca, u.servidor_id, u.plano_id 
                           FROM users u 
                           LEFT JOIN servidores s ON u.servidor_id = s.id AND s.cliente_id = ? 
                           WHERE u.ativo = 1 AND u.created_by = ? 
                           ORDER BY u.created_at DESC 
                           LIMIT ? OFFSET ?");
    $stmt->bindParam(1, $clienteId, PDO::PARAM_INT);
    $stmt->bindParam(2, $clienteId, PDO::PARAM_INT);
    $stmt->bindParam(3, $limit, PDO::PARAM_INT);
    $stmt->bindParam(4, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users u WHERE u.ativo = 1 AND u.created_by = ?");
    $stmt->execute([$clienteId]);
    $totalUsers = $stmt->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);
    $page = min($page, $totalPages);
} catch (PDOException $e) {
    $error = $error ?? "Erro ao buscar usuários: " . $e->getMessage();
    error_log("User fetch error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gerenciar Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #f1f1f1;
            font-family: Arial, sans-serif;
            font-size: 16px;
        }

        .menu-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            font-size: 28px;
            color: black;
            background-color: transparent;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            z-index: 1200;
            display: none;
            transition: color 0.3s ease;
        }

        .menu-toggle.open {
            color: white;
        }

        .sidebar {
            width: 240px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            background: #293a5e;
            color: white;
            padding-top: 60px;
            transition: transform 0.3s ease;
            z-index: 1100;
        }

        .sidebar-user {
            padding: 15px 20px;
            font-size: 1.1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            background-color: #2e4a7b;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar ul li {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar ul li a.disabled {
            color: #888;
            pointer-events: none;
            cursor: not-allowed;
        }

        .sidebar ul li a:hover:not(.disabled) {
            background: #1f2a44;
        }

        main.content {
            margin-left: 240px;
            padding: 0.5rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
            z-index: 1;
        }

        .container {
            width: 100%;
            max-width: 90vw;
            margin: 0.5rem auto;
            padding: 0 0.5rem;
        }

        .add-btn {
            display: block;
            background: #293a5e;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: clamp(0.9rem, 2.5vw, 1rem);
            margin: 0 auto 1rem;
            width: 100%;
            max-width: 12rem;
            text-align: center;
            transition: background 0.2s;
        }

        .add-btn:hover {
            background: #1f2a44;
        }

        .table-container {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 0.5rem 0;
            overflow-x: auto;
        }

        .table {
            min-width: 600px;
        }

        .table td, .table th {
            word-wrap: break-word;
            max-width: 200px;
            padding: 0.75rem;
            vertical-align: middle;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .actions button {
            padding: 0.4rem 0.8rem;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
        }

        .modal-content {
            border-radius: 10px;
            padding: 1rem;
        }

        .modal-header {
            border-bottom: none;
        }

        .modal-title {
            font-size: clamp(1.2rem, 3vw, 1.5rem);
        }

        .btn-close {
            font-size: clamp(1rem, 2.5vw, 1.2rem);
        }

        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 0.625rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .pagination {
            justify-content: center;
            margin-top: 1rem;
        }

        .action-btn {
            padding: 0.4rem 0.8rem;
            font-size: clamp(0.8rem, 2vw, 0.9rem);
            border-radius: 4px;
            text-decoration: none;
            color: white;
        }

        .renew-btn {
            background-color: #17a2b8;
        }

        .renew-btn:hover {
            background-color: #138496;
        }

        .delete-btn {
            background-color: #dc3545;
        }

        .delete-btn:hover {
            background-color: #c82333;
        }

        .btn-success {
            background-color: #293a5e;
            border-color: #293a5e;
        }

        .btn-success:hover {
            background-color: #1f2a44;
            border-color: #1f2a44;
        }

        .disabled {
            pointer-events: none;
            opacity: 0.65;
        }

        .modal-body .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .modal-body .form-row .form-group {
            flex: 1;
            min-width: 0;
        }

        @media (max-width: 991.98px) {
            main.content {
                margin-left: 200px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 90%;
                max-width: 240px;
                z-index: 1100;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .menu-toggle {
                display: block;
                z-index: 1200;
            }

            main.content {
                margin-left: 0;
                padding: 80px 0.25rem 0.25rem;
            }

            .container {
                max-width: 95vw;
                padding: 0 0.25rem;
            }

            .add-btn {
                max-width: 100%;
                padding: 0.6rem 1rem;
            }

            .actions {
                justify-content: center;
            }

            .modal-dialog {
                margin: 0.5rem auto;
                max-width: 95vw;
            }

            th[data-column="nome_completo"],
            td[data-column="nome_completo"],
            th[data-column="senha"],
            td[data-column="senha"],
            th[data-column="celular"],
            td[data-column="celular"],
            th[data-column="created_at"],
            td[data-column="created_at"] {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .container {
                max-width: 100%;
            }

            .add-btn {
                font-size: 0.9rem;
            }

            .actions button, .action-btn {
                width: 100%;
                text-align: center;
            }

            .modal-body .form-row .form-group {
                min-width: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- Botão Hamburguer -->
    <div class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">
        <span id="menuIcon">☰</span>
    </div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <nav>
            <?php if (!empty($primeiroNome)): ?>
                <div class="sidebar-user">
                    Olá, <strong><?php echo htmlspecialchars($primeiroNome); ?></strong>
                </div>
            <?php endif; ?>
            <ul>
                <li><a href="dashboard.php"><i class="fa fa-gauge"></i> Dashboard</a></li>
                <li><a href="users.php"><i class="fa fa-user-group"></i> Clientes</a></li>
                <li><a href="servidores.php"><i class="fa fa-server"></i> Servidores</a></li>
                <li><a href="planos.php"><i class="fa fa-tags"></i> Planos</a></li>
                <li><a href="template.php"><i class="fa fa-palette"></i> Templates</a></li>
                <li><a href="alugueis.php"><i class="fa fa-house-user"></i> Aluguéis</a></li>
                <li><a href="produtos.php"><i class="fa fa-box"></i> Produtos</a></li>
                <li><a href="logout.php"><i class="fa fa-right-from-bracket"></i> Sair</a></li>
            </ul>
        </nav>
    </aside>

    <main class="content">
        <div class="container">
            <h2 class="mb-4 text-center">Gerenciar Usuários</h2>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (!empty($mensagem)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($mensagem); ?></div>
            <?php endif; ?>

            <button class="add-btn" data-bs-toggle="modal" data-bs-target="#modalUsuario">Inserir Usuário</button>

            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th data-column="id">ID</th>
                            <th data-column="nome_completo">Nome Completo</th>
                            <th data-column="usuario">Usuário</th>
                            <th data-column="senha">Senha</th>
                            <th data-column="celular">Celular</th>
                            <th data-column="created_at">Criado Em</th>
                            <th data-column="vencimento">Vencimento</th>
                            <th data-column="servidor">Servidor</th>
                            <th data-column="plano">Plano</th>
                            <th data-column="acoes">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $celular_display = preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $user['celular']);
                                $vencimento_display = $user['vencimento'] ? date('d/m/Y', strtotime($user['vencimento'])) : 'Não definido';
                                $created_at_display = $user['created_at'] ? date('d/m/Y H:i:s', strtotime($user['created_at'])) : 'N/A';
                                $plano_nome = 'Sem plano';
                                foreach ($planos as $plano) {
                                    if ($plano['id'] == $user['plano_id']) {
                                        $plano_nome = $plano['nome'];
                                        break;
                                    }
                                }
                                ?>
                                <tr>
                                    <td data-column="id"><?php echo htmlspecialchars($user['user_id']); ?></td>
                                    <td data-column="nome_completo"><?php echo htmlspecialchars($user['nome_completo']); ?></td>
                                    <td data-column="usuario"><?php echo htmlspecialchars($user['usuario']); ?></td>
                                    <td data-column="senha"><?php echo htmlspecialchars($user['senha']); ?></td>
                                    <td data-column="celular"><?php echo htmlspecialchars($celular_display); ?></td>
                                    <td data-column="created_at"><?php echo htmlspecialchars($created_at_display); ?></td>
                                    <td data-column="vencimento"><?php echo htmlspecialchars($vencimento_display); ?></td>
                                    <td data-column="servidor"><?php echo htmlspecialchars($user['marca'] ?? 'Sem servidor'); ?></td>
                                    <td data-column="plano"><?php echo htmlspecialchars($plano_nome); ?></td>
                                    <td data-column="acoes" class="actions">
                                        <button class="btn btn-sm btn-primary open-action-modal" 
                                                data-id="<?php echo $user['user_id']; ?>"
                                                data-nome="<?php echo htmlspecialchars($user['nome_completo'], ENT_QUOTES); ?>"
                                                data-usuario="<?php echo htmlspecialchars($user['usuario'], ENT_QUOTES); ?>"
                                                data-celular="<?php echo htmlspecialchars($celular_display, ENT_QUOTES); ?>"
                                                data-celular-raw="<?php echo htmlspecialchars($user['celular'], ENT_QUOTES); ?>"
                                                data-vencimento="<?php echo htmlspecialchars($user['vencimento'] ?? '', ENT_QUOTES); ?>"
                                                data-servidor="<?php echo $user['servidor_id']; ?>"
                                                data-plano="<?php echo htmlspecialchars($user['plano_id'] ?? NULL, ENT_QUOTES); ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalUsuario">Ações</button>
                                        <a class="action-btn renew-btn btn btn-sm btn-info renew-action"
                                           href="#"
                                           data-id="<?php echo $user['user_id']; ?>"
                                           data-plano="<?php echo htmlspecialchars($user['plano_id'] ?? NULL, ENT_QUOTES); ?>"
                                           data-bs-toggle="modal"
                                           data-bs-target="#modalRenovar">Renovar</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center text-muted">Nenhum usuário cadastrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Anterior</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Próximo</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <!-- Modal para Cadastrar/Editar Usuário -->
            <div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalUsuarioLabel">Cadastrar Novo Usuário</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php if (empty($servidores)): ?>
                                <div class="alert alert-warning text-center">Nenhum servidor ativo associado. Contate o administrador.</div>
                            <?php endif; ?>
                            <?php if (empty($planos)): ?>
                                <div class="alert alert-warning text-center">Nenhum plano disponível.</div>
                            <?php endif; ?>
                            <form method="POST" id="userForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="action" id="formAction" value="add">
                                <input type="hidden" name="id" id="formId">
                                <div class="form-row">
                                    <div class="form-group mb-3">
                                        <label for="nome" class="form-label">Nome Completo</label>
                                        <input type="text" name="nome" id="nome" class="form-control" maxlength="255" required>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="usuario" class="form-label">Usuário (login)</label>
                                        <input type="text" name="usuario" id="usuario" class="form-control" maxlength="50" pattern="[a-zA-Z0-9_]+" required title="Apenas letras, números e sublinhados.">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group mb-3 position-relative">
                                        <label for="senha" class="form-label">Senha</label>
                                        <input type="password" name="senha" id="senha" class="form-control" maxlength="50">
                                        <span class="password-toggle" onclick="togglePassword('senha')"><i class="bi bi-eye"></i></span>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="celular" class="form-label">Celular</label>
                                        <input type="text" name="celular" id="celular" class="form-control" maxlength="15" pattern="\(\d{2}\)\s\d{5}-\d{4}" required>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group mb-3">
                                        <label for="vencimento" class="form-label">Vencimento (opcional)</label>
                                        <input type="date" name="vencimento" id="vencimento" class="form-control">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="servidor_id" class="form-label">Servidor</label>
                                        <select name="servidor_id" id="servidor_id" class="form-select" required>
                                            <option value="">Selecione um servidor</option>
                                            <?php foreach ($servidores as $row): ?>
                                                <option value="<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['marca']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group mb-3">
                                        <label for="plano_id" class="form-label">Plano</label>
                                        <select name="plano_id" id="plano_id" class="form-select" required>
                                            <option value="">Selecione um plano</option>
                                            <?php foreach ($planos as $plano): ?>
                                                <option value="<?php echo $plano['id']; ?>"><?php echo htmlspecialchars($plano['nome']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group mb-3" id="planoSection" style="display: none;">
                                        <label for="plano" class="form-label">Plano para Renovação</label>
                                        <select name="plano" id="plano" class="form-select">
                                            <option value="">Selecione um plano</option>
                                            <?php foreach ($planos as $plano): ?>
                                                <optgroup label="<?php echo htmlspecialchars($plano['nome']); ?>">
                                                    <?php if (!empty($plano['valor_1']) && $plano['valor_1'] > 0): ?>
                                                        <option value="<?php echo $plano['id']; ?>-1">R$ <?php echo number_format($plano['valor_1'], 2, ',', '.'); ?> - 1 mês</option>
                                                    <?php endif; ?>
                                                    <?php if (!empty($plano['valor_2']) && $plano['valor_2'] > 0): ?>
                                                        <option value="<?php echo $plano['id']; ?>-2">R$ <?php echo number_format($plano['valor_2'], 2, ',', '.'); ?> - 2 meses</option>
                                                    <?php endif; ?>
                                                    <?php if (!empty($plano['valor_3']) && $plano['valor_3'] > 0): ?>
                                                        <option value="<?php echo $plano['id']; ?>-3">R$ <?php echo number_format($plano['valor_3'], 2, ',', '.'); ?> - 3 meses</option>
                                                    <?php endif; ?>
                                                    <?php if (!empty($plano['valor_4']) && $plano['valor_4'] > 0): ?>
                                                        <option value="<?php echo $plano['id']; ?>-4">R$ <?php echo number_format($plano['valor_4'], 2, ',', '.'); ?> - 4 meses</option>
                                                    <?php endif; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success w-100" <?php echo empty($servidores) || empty($planos) ? ' disabled' : ''; ?>>Salvar</button>
                            </form>
                            <a id="whatsappLink" href="#" class="btn btn-success w-100 mt-2 action-btn" target="_blank">Enviar Mensagem WhatsApp</a>
                            <form id="deleteForm" method="POST" style="display: none;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" id="deleteId">
                            </form>
                            <button id="deleteButton" class="btn btn-danger w-100 mt-2 action-btn delete-btn" style="display: none;">Excluir Usuário</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal para Renovar Plano -->
            <div class="modal fade" id="modalRenovar" tabindex="-1" aria-labelledby="modalRenovarLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalRenovarLabel">Renovar Plano</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="renewForm">
                                <input type="hidden" id="renewUserId">
                                <div class="form-group mb-3">
                                    <label for="renewPlano" class="form-label">Plano para Renovação</label>
                                    <select id="renewPlano" class="form-select" required>
                                        <option value="">Selecione um plano</option>
                                        <?php foreach ($planos as $plano): ?>
                                            <optgroup label="<?php echo htmlspecialchars($plano['nome']); ?>">
                                                <?php if (!empty($plano['valor_1']) && $plano['valor_1'] > 0): ?>
                                                    <option value="<?php echo $plano['id']; ?>-1">R$ <?php echo number_format($plano['valor_1'], 2, ',', '.'); ?> - 1 mês</option>
                                                <?php endif; ?>
                                                <?php if (!empty($plano['valor_2']) && $plano['valor_2'] > 0): ?>
                                                    <option value="<?php echo $plano['id']; ?>-2">R$ <?php echo number_format($plano['valor_2'], 2, ',', '.'); ?> - 2 meses</option>
                                                <?php endif; ?>
                                                <?php if (!empty($plano['valor_3']) && $plano['valor_3'] > 0): ?>
                                                    <option value="<?php echo $plano['id']; ?>-3">R$ <?php echo number_format($plano['valor_3'], 2, ',', '.'); ?> - 3 meses</option>
                                                <?php endif; ?>
                                                <?php if (!empty($plano['valor_4']) && $plano['valor_4'] > 0): ?>
                                                    <option value="<?php echo $plano['id']; ?>-4">R$ <?php echo number_format($plano['valor_4'], 2, ',', '.'); ?> - 4 meses</option>
                                                <?php endif; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100">Confirmar Renovação</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Embed template_users data as a JavaScript variable
        const templateUsers = <?php echo json_encode($templateUsers); ?>;
        const chavePix = '<?php echo htmlspecialchars($cliente["chave_pix"] ?? "", ENT_QUOTES, "UTF-8"); ?>';

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            sidebar.classList.toggle('active');
            menuToggle.classList.toggle('open');
        }

        function formatarCelular(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            let formatted = '';
            if (value.length > 0) {
                formatted = '(' + value.slice(0, 2);
                if (value.length > 2) {
                    formatted += ') ' + value.slice(2, 7);
                    if (value.length > 7) {
                        formatted += '-' + value.slice(7, 11);
                    }
                }
            }
            input.value = formatted;
        }

        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        document.getElementById('celular').addEventListener('input', (e) => formatarCelular(e.target));

        function updateRenewLink(planoSelect, id) {
            const selectedOption = planoSelect.options[planoSelect.selectedIndex];
            console.log('updateRenewLink called:', { id, selectedValue: selectedOption ? selectedOption.value : 'none', selectedText: selectedOption ? selectedOption.text : 'none' });
            if (selectedOption && selectedOption.value) {
                const [planoId, periodo] = selectedOption.value.split('-');
                if (planoId && periodo && id) {
                    const href = `renovar.php?user_id=${encodeURIComponent(id)}&plano_id=${encodeURIComponent(planoId)}&periodo=${encodeURIComponent(periodo)}`;
                    console.log('Renew link updated:', { href, planoId, periodo });
                    return href;
                } else {
                    console.log('Invalid plan selection:', { planoId, periodo });
                    return '#';
                }
            } else {
                console.log('No plan selected');
                return '#';
            }
        }

        // Function to replace placeholders in the template
        function replaceTemplatePlaceholders(template, values) {
            let result = template;
            // Handle both ${key} and {key} formats
            result = result.replace(/(\${nome}|\{nome\})/g, values.nome || 'N/A');
            result = result.replace(/(\${vencimento}|\{vencimento\})/g, values.vencimento || 'N/A');
            result = result.replace(/(\${pix}|\{pix\})/g, values.pix || 'N/A');
            result = result.replace(/(\${cliente_id}|\{cliente_id\})/g, values.cliente_id || 'N/A');
            return result;
        }

        // Modal para Cadastrar/Editar Usuário
        document.getElementById('modalUsuario').addEventListener('show.bs.modal', (e) => {
            const modal = e.target;
            const form = modal.querySelector('#userForm');
            const title = modal.querySelector('.modal-title');
            const actionButtons = modal.querySelectorAll('.action-btn');
            const senhaInput = document.getElementById('senha');
            const planoSection = document.getElementById('planoSection');
            const planoSelect = document.getElementById('plano');
            const planoIdSelect = document.getElementById('plano_id');
            const deleteButton = document.getElementById('deleteButton');
            const deleteForm = document.getElementById('deleteForm');

            if (e.relatedTarget.classList.contains('open-action-modal')) {
                title.textContent = 'Editar Usuário';
                form.querySelector('#formAction').value = 'edit';
                const id = e.relatedTarget.getAttribute('data-id');
                form.querySelector('#formId').value = id;
                document.getElementById('nome').value = e.relatedTarget.getAttribute('data-nome');
                document.getElementById('usuario').value = e.relatedTarget.getAttribute('data-usuario');
                senhaInput.value = '';
                senhaInput.removeAttribute('required');
                document.getElementById('celular').value = e.relatedTarget.getAttribute('data-celular');
                document.getElementById('vencimento').value = e.relatedTarget.getAttribute('data-vencimento');
                document.getElementById('servidor_id').value = e.relatedTarget.getAttribute('data-servidor');
                document.getElementById('plano_id').value = e.relatedTarget.getAttribute('data-plano') || '';
                actionButtons.forEach(btn => btn.style.display = 'block');
                planoSection.style.display = 'none';
                const planoId = e.relatedTarget.getAttribute('data-plano');
                console.log('Modal opened for user:', { id, planoId });
                const celularRaw = e.relatedTarget.getAttribute('data-celular-raw');
                const usuario = e.relatedTarget.getAttribute('data-usuario');
                const vencimento = e.relatedTarget.getAttribute('data-vencimento');
                const nomeCompleto = e.relatedTarget.getAttribute('data-nome');

                // Find the "Vencimento" template (titulo='Vencimento') first
                let selectedTemplate = templateUsers.find(template => template.titulo === 'Vencimento');

                // If not found, select the first available template for cliente_id
                if (!selectedTemplate) {
                    selectedTemplate = templateUsers.find(template => template.cliente_id === '<?php echo $clienteId; ?>');
                }

                let mensagem = '';

                if (selectedTemplate) {
                    // Replace placeholders in the selected template
                    const vencimentoFormatted = vencimento ? new Date(vencimento).toLocaleDateString('pt-BR') : 'N/A';
                    mensagem = replaceTemplatePlaceholders(selectedTemplate.template_text, {
                        nome: nomeCompleto,
                        vencimento: vencimentoFormatted,
                        pix: chavePix,
                        cliente_id: '<?php echo $clienteId; ?>'
                    });
                    console.log('Template selected:', { id: selectedTemplate.id, titulo: selectedTemplate.titulo, mensagem });
                    <?php error_log("Template selected for user_id ${id}: ID=${selectedTemplate.id}, Titulo=${selectedTemplate.tulo}"); ?>
                } else {
                    // Minimal fallback message if no templates are found
                    mensagem = `Olá ${nomeCompleto}, tudo bem ?\nPassando para lembrar que A data vencimento do seu plano é ${vencimento ? new Date(vencimento).toLocaleDateString('pt-BR') : 'N/A'}. \nEvite o bloqueio automático do seu sinal.\nPara renovar o seu plano agora mesmo, utilize a nossa chave pix abaixo: ${chavePix}.\nObservações:\n Em descrição deixe em branco ou se precisar coloque SUPORTE TÉCNICO\nPor favor, nos envie o comprovante de pagamento assim que possível para conferencia.\n\nÉ sempre um prazer te atender.`;
                    console.log('No template found, using minimal fallback message');
                    <?php error_log("No template found for cliente_id ${clienteId}, using fallback for user_id ${id}"); ?>
                }

                // Update WhatsApp link
                document.getElementById('whatsappLink').setAttribute('href',
                    `https://wa.me/55${encodeURIComponent(celularRaw)}?text=${encodeURIComponent(mensagem)}`
                );

                deleteForm.querySelector('#deleteId').value = id;
                deleteButton.style.display = 'block';
            } else {
                title.textContent = 'Cadastrar Novo Usuário';
                form.querySelector('#formAction').value = 'add';
                form.reset();
                senhaInput.setAttribute('required', 'required');
                actionButtons.forEach(btn => btn.style.display = 'none');
                planoSection.style.display = 'none';
                deleteButton.style.display = 'none';
                document.getElementById('nome').focus();
                console.log('Modal opened for new user');
            }
        });

        // Modal para Renovar Plano
        document.getElementById('modalRenovar').addEventListener('show.bs.modal', (e) => {
            const userId = e.relatedTarget.getAttribute('data-id');
            const planoId = e.relatedTarget.getAttribute('data-plano');
            document.getElementById('renewUserId').value = userId;
            const renewPlanoSelect = document.getElementById('renewPlano');
            renewPlanoSelect.value = planoId ? `${planoId}-1` : '';
            console.log('Renew modal opened for user:', { userId, planoId });
        });

        document.getElementById('renewForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const userId = document.getElementById('renewUserId').value;
            const renewPlanoSelect = document.getElementById('renewPlano');
            const href = updateRenewLink(renewPlanoSelect, userId);
            if (href !== '#') {
                window.location.href = href;
            } else {
                alert('Por favor, selecione um plano válido para renovação.');
                console.log('Renew submission prevented: invalid plan selection');
            }
        });

        document.getElementById('deleteButton').addEventListener('click', (e) => {
            if (confirm('Tem certeza que deseja excluir este usuário? Esta ação não pode ser desfeita.')) {
                document.getElementById('deleteForm').submit();
            }
        });
    </script>
</body>
</html>
```