<?php
session_start();
require_once '../includes/db.php';

// Validate session
if (!isset($_SESSION['admin_id']) || !is_numeric($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem = "";

// Verify database connection
if (!isset($pdo)) {
    error_log("Erro: Conexão com o banco de dados não foi estabelecida. Verifique o arquivo db.php.");
    http_response_code(500);
    echo "<div class='alert alert-danger'>Erro interno do servidor. Tente novamente mais tarde.</div>";
    exit;
}

// Fetch servers
try {
    $stmt = $pdo->query("SELECT id, marca FROM servidores ORDER BY marca");
    $servidores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao buscar servidores: " . htmlspecialchars($e->getMessage());
    error_log("Erro ao buscar servidores: " . $e->getMessage());
    $servidores = [];
}

// Fetch users with pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);

    $stmt = $pdo->query("SELECT u.user_id, u.nome_completo, u.usuario, u.senha, u.celular, u.vencimento, u.created_at, u.servidor_id, s.marca 
                         FROM users u 
                         LEFT JOIN servidores s ON u.servidor_id = s.id 
                         ORDER BY u.nome_completo 
                         LIMIT $perPage OFFSET $offset");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao buscar usuários: " . htmlspecialchars($e->getMessage());
    error_log("Erro ao buscar usuários: " . $e->getMessage());
    $users = [];
}

// Add user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensagem = "Erro: Token CSRF inválido.";
    } else {
        $nome = trim($_POST["nome"]);
        $usuario = trim($_POST["usuario"]);
        $senha = trim($_POST["senha"]);
        $celular = preg_replace('/\D/', '', trim($_POST["celular"]));
        $vencimento = !empty($_POST["vencimento"]) ? $_POST["vencimento"] : null;
        $servidor_id = intval($_POST["servidor_id"]);

        // Input validation
        if (strlen($nome) > 255) {
            $mensagem = "Nome completo excede 255 caracteres.";
        } elseif (strlen($usuario) > 50) {
            $mensagem = "Usuário excede 50 caracteres.";
        } elseif (strlen($senha) > 50) {
            $mensagem = "Senha excede 50 caracteres.";
        } elseif (strlen($celular) !== 11) {
            $mensagem = "Celular deve ter 11 dígitos.";
        } elseif ($vencimento && !DateTime::createFromFormat('Y-m-d', $vencimento)) {
            $mensagem = "Data de vencimento inválida.";
        } elseif ($nome && $usuario && $senha && $celular && $servidor_id) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE usuario = ?");
                $stmt->execute([$usuario]);
                if ($stmt->fetchColumn() > 0) {
                    $mensagem = "Erro: Usuário já existe.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (nome_completo, usuario, senha, celular, vencimento, servidor_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nome, $usuario, $senha, $celular, $vencimento, $servidor_id]);
                    $mensagem = "Usuário cadastrado com sucesso!";
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header("Location: users.php");
                    exit;
                }
            } catch (PDOException $e) {
                $mensagem = "Erro ao cadastrar usuário: " . htmlspecialchars($e->getMessage());
                error_log("Erro ao cadastrar usuário: " . $e->getMessage());
            }
        } else {
            $mensagem = "Por favor, preencha todos os campos obrigatórios.";
        }
    }
}

// Edit user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensagem = "Erro: Token CSRF inválido.";
    } else {
        $id = intval($_POST["id"]);
        $nome = trim($_POST["nome"]);
        $usuario = trim($_POST["usuario"]);
        $senha = trim($_POST["senha"]);
        $celular = preg_replace('/\D/', '', trim($_POST["celular"]));
        $vencimento = !empty($_POST["vencimento"]) ? $_POST["vencimento"] : null;
        $servidor_id = intval($_POST["servidor_id"]);

        // Input validation
        if (strlen($nome) > 255) {
            $mensagem = "Nome completo excede 255 caracteres.";
        } elseif (strlen($usuario) > 50) {
            $mensagem = "Usuário excede 50 caracteres.";
        } elseif (strlen($senha) > 50) {
            $mensagem = "Senha excede 50 caracteres.";
        } elseif (strlen($celular) !== 11) {
            $mensagem = "Celular deve ter 11 dígitos.";
        } elseif ($vencimento && !DateTime::createFromFormat('Y-m-d', $vencimento)) {
            $mensagem = "Data de vencimento inválida.";
        } elseif ($nome && $usuario && $senha && $celular && $servidor_id) {
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE usuario = ? AND user_id != ?");
                $stmt->execute([$usuario, $id]);
                if ($stmt->fetchColumn() > 0) {
                    $mensagem = "Erro: Usuário já existe.";
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET nome_completo=?, usuario=?, senha=?, celular=?, vencimento=?, servidor_id=? WHERE user_id=?");
                    $stmt->execute([$nome, $usuario, $senha, $celular, $vencimento, $servidor_id, $id]);
                    $mensagem = "Usuário atualizado com sucesso!";
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header("Location: users.php");
                    exit;
                }
            } catch (PDOException $e) {
                $mensagem = "Erro ao atualizar usuário: " . htmlspecialchars($e->getMessage());
                error_log("Erro ao atualizar usuário: " . $e->getMessage());
            }
        } else {
            $mensagem = "Por favor, preencha todos os campos obrigatórios.";
        }
    }
}

// Delete user
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensagem = "Erro: Token CSRF inválido.";
    } else {
        $id = intval($_POST["id"]);
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id=?");
            $stmt->execute([$id]);
            $mensagem = "Usuário excluído com sucesso!";
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: users.php");
            exit;
        } catch (PDOException $e) {
            $mensagem = "Erro ao excluir usuário: " . htmlspecialchars($e->getMessage());
            error_log("Erro ao excluir usuário: " . $e->getMessage());
        }
    }
}

// Delete all users
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_all'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensagem = "Erro: Token CSRF inválido.";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users");
            $stmt->execute();
            $mensagem = "Todos os usuários foram excluídos com sucesso!";
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: users.php");
            exit;
        } catch (PDOException $e) {
            $mensagem = "Erro ao excluir todos os usuários: " . htmlspecialchars($e->getMessage());
            error_log("Erro ao excluir todos os usuários: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Gerenciar Usuários</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body {
            background-color: #f1f1f1;
            font-family: Arial, sans-serif;
            font-size: 16px;
        }
        main.content {
            margin-left: 220px;
            padding: 0.5rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        .container {
            width: 100%;
            max-width: 90vw;
            margin: 0.5rem auto;
            padding: 0 0.5rem;
        }
        .add-btn {
            display: block;
            background: #28a745;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: clamp(0.75rem, 2.5vw, 0.9rem);
            margin: 0 auto;
            width: 100%;
            max-width: 200px;
            text-align: center;
            transition: all 0.3 ease;
        }
        .add-btn:hover {
            background: #1e7e34;
        }
        .table-container {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin: 0.5rem auto;
            overflow-x: auto;
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
            font-size: 1rem;
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
        .invoice-btn {
            background-color: #ffc107;
            color: #212529;
        }
        .invoice-btn:hover {
            background-color: #e0a800;
        }
        .renew-btn {
            background-color: #17a2b8;
        }
        .renew-btn:hover {
            background-color: #138496;
        }
        @media (max-width: 991.98px) {
            main.content {
                margin-left: 200px;
            }
        }
        @media (max-width: 768px) {
            main.content {
                margin-left: 0;
                padding: 0.25rem;
            }
            .container {
                max-width: 95vw;
                padding: 0 0.25rem;
            }
            .table-container {
                padding: 0.5rem;
                margin: 0.5rem 0;
            }
            .add-btn {
                max-width: 100%;
                padding: 0.6rem 1rem;
            }
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                display: none;
            }
            tbody tr {
                margin-bottom: 0.75rem;
                background: #f9f9f9;
                border-radius: 5px;
                padding: 0.5rem;
                box-shadow: 0 0 5px rgba(0,0,0,0.1);
            }
            tbody td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: right;
                font-size: clamp(0.85rem, 2.2vw, 0.95rem);
                padding: 0.5rem;
            }
            tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 0.5rem;
                top: 0.5rem;
                width: calc(50% - 1rem);
                font-weight: bold;
                text-transform: uppercase;
                font-size: clamp(0.7rem, 2vw, 0.8rem);
                color: #555;
            }
            .actions {
                justify-content: flex-end;
            }
            .modal-dialog {
                margin: 0.5rem;
                max-width: 95vw;
            }
        }
        @media (max-width: 576px) {
            .container {
                max-width: 98vw;
            }
            .add-btn {
                font-size: 0.9rem;
            }
            .actions button, .action-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/assets/sidebar.php'; ?>

    <main class="content">
        <div class="container">
            <h2 class="mb-4 text-center">Gerenciar Usuários</h2>

            <!-- Display messages -->
            <?php if ($mensagem): ?>
                <div class="alert alert-info"><?= htmlspecialchars($mensagem) ?></div>
            <?php endif; ?>

            <!-- Botões de ação -->
<div style="display: flex; gap: 10px;">
  <button class="add-btn" data-bs-toggle="modal" data-bs-target="#addUserModal">Adicionar Novo Usuário</button>

  <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir TODOS os usuários? Esta ação é irreversível.');">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="hidden" name="delete_all" value="1">
    <button type="submit" class="delete-btn" style="background-color: crimson; color: white; border: none; padding: 8px 12px; border-radius: 4px;">Deletar Todos</button>
  </form>
</div>

            <!-- Users table -->
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome Completo</th>
                            <th>Usuário</th>
                            <th>Senha</th>
                            <th>Celular</th>
                            <th>Criado Em</th>
                            <th>Vencimento</th>
                            <th>Servidor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users): ?>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $celular_display = preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $user['celular']);
                                $vencimento_display = $user['vencimento'] ? date('d/m/Y', strtotime($user['vencimento'])) : 'Não definido';
                                $created_at_display = $user['created_at'] ? date('d/m/Y H:i:s', strtotime($user['created_at'])) : 'N/A';
                                ?>
                                <tr>
                                    <td data-label="ID"><?= htmlspecialchars($user['user_id']) ?></td>
                                    <td data-label="Nome Completo"><?= htmlspecialchars($user['nome_completo']) ?></td>
                                    <td data-label="Usuário"><?= htmlspecialchars($user['usuario']) ?></td>
                                    <td data-label="Senha"><?= htmlspecialchars($user['senha']) ?></td>
                                    <td data-label="Celular"><?= htmlspecialchars($celular_display) ?></td>
                                    <td data-label="Criado Em"><?= htmlspecialchars($created_at_display) ?></td>
                                    <td data-label="Vencimento"><?= htmlspecialchars($vencimento_display) ?></td>
                                    <td data-label="Servidor"><?= htmlspecialchars($user['marca'] ?? 'Sem servidor') ?></td>
                                    <td data-label="Ações" class="actions">
                                        <button class="btn btn-sm btn-primary open-action-modal" 
                                                data-id="<?= $user['user_id'] ?>"
                                                data-nome="<?= htmlspecialchars($user['nome_completo'], ENT_QUOTES) ?>"
                                                data-usuario="<?= htmlspecialchars($user['usuario'], ENT_QUOTES) ?>"
                                                data-senha=""
                                                data-celular="<?= htmlspecialchars($celular_display, ENT_QUOTES) ?>"
                                                data-celular-raw="<?= htmlspecialchars($user['celular'], ENT_QUOTES) ?>"
                                                data-vencimento="<?= htmlspecialchars($user['vencimento'] ?? '', ENT_QUOTES) ?>"
                                                data-servidor="<?= $user['servidor_id'] ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#actionModal">Ações</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center text-muted">Nenhum usuário cadastrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">Anterior</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Próximo</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

            <!-- Add user modal -->
            <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addUserModalLabel">Cadastrar Novo Usuário</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="add" value="1">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome completo</label>
                                    <input type="text" name="nome" id="nome" class="form-control" maxlength="255" required>
                                </div>
                                <div class="mb-3">
                                    <label for="usuario" class="form-label">Usuário (login)</label>
                                    <input type="text" name="usuario" id="usuario" class="form-control" maxlength="50" required>
                                </div>
                                <div class="mb-3 position-relative">
                                    <label for="senha" class="form-label">Senha</label>
                                    <input type="password" name="senha" id="senha" class="form-control" maxlength="255" required>
                                    <span class="password-toggle" onclick="togglePassword('senha')">👁️</span>
                                </div>
                                <div class="mb-3">
                                    <label for="celular" class="form-label">Celular</label>
                                    <input type="text" name="celular" id="celular" class="form-control" pattern="\(\d{2}\) \d{5}-\d{4}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="vencimento" class="form-label">Vencimento (opcional)</label>
                                    <input type="date" name="vencimento" id="vencimento" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="servidor_id" class="form-label">Servidor</label>
                                    <select name="servidor_id" id="servidor_id" class="form-select" required>
                                        <option value="">Selecione um servidor</option>
                                        <?php foreach ($servidores as $row): ?>
                                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['marca']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100">Cadastrar Usuário</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action modal -->
            <div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="actionModalLabel">Gerenciar Usuário</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Edit form -->
                            <form method="POST" id="editForm">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="edit" value="1">
                                <input type="hidden" name="id" id="actionId">
                                <div class="mb-3">
                                    <label for="actionNome" class="form-label">Nome completo</label>
                                    <input type="text" name="nome" id="actionNome" class="form-control" maxlength="255" required>
                                </div>
                                <div class="mb-3">
                                    <label for="actionUsuario" class="form-label">Usuário (login)</label>
                                    <input type="text" name="usuario" id="actionUsuario" class="form-control" maxlength="50" required>
                                </div>
                                <div class="mb-3 position-relative">
                                    <label for="actionSenha" class="form-label">Senha (deixe em branco para manter)</label>
                                    <input type="password" name="senha" id="actionSenha" class="form-control" maxlength="255">
                                    <span class="password-toggle" onclick="togglePassword('actionSenha')">👁️</span>
                                </div>
                                <div class="mb-3">
                                    <label for="actionCelular" class="form-label">Celular</label>
                                    <input type="text" name="celular" id="actionCelular" class="form-control" pattern="\(\d{2}\) \d{5}-\d{4}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="actionVencimento" class="form-label">Vencimento (opcional)</label>
                                    <input type="date" name="vencimento" id="actionVencimento" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label for="actionServidor" class="form-label">Servidor</label>
                                    <select name="servidor_id" id="actionServidor" class="form-select" required>
                                        <option value="">Selecione um servidor</option>
                                        <?php foreach ($servidores as $row): ?>
                                            <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['marca']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mb-2">Salvar Alterações</button>
                            </form>
                            <!-- Delete form -->
                            <form method="POST" id="deleteForm">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="delete" value="1">
                                <input type="hidden" name="id" id="deleteId">
                                <button type="submit" class="btn btn-danger w-100 mb-2" onclick="return confirm('Tem certeza que deseja excluir este usuário?')">Excluir Usuário</button>
                            </form>
                            <!-- Renew plan -->
                            <a id="renewLink" href="#" class="btn btn-info w-100 mb-2 action-btn renew-btn">Renovar Plano</a>
                            <!-- Generate invoice -->
                            <a id="invoiceLink" href="#" class="btn btn-warning w-100 mb-2 action-btn invoice-btn">Gerar Fatura</a>
                            <!-- WhatsApp link -->
                            <a id="whatsappLink" href="#" class="btn btn-success w-100 action-btn" target="_blank">Enviar Mensagem WhatsApp</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Format phone number
        function formatarCelular(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            if (value.length > 6) {
                value = `(${value.slice(0,2)}) ${value.slice(2,7)}-${value.slice(7)}`;
            } else if (value.length > 2) {
                value = `(${value.slice(0,2)}) ${value.slice(2)}`;
            } else if (value.length > 0) {
                value = `(${value}`;
            }
            input.value = value;
        }

        // Toggle password visibility
        function togglePassword(id) {
            const input = document.getElementById(id);
            const icon = input.nextElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = '🙈';
            } else {
                input.type = 'password';
                icon.textContent = '👁️';
            }
        }

        // Apply phone formatting
        document.getElementById('celular').addEventListener('input', function(e) {
            formatarCelular(e.target);
        });
        document.getElementById('actionCelular').addEventListener('input', function(e) {
            formatarCelular(e.target);
        });

        // Clear add user modal
        document.getElementById('addUserModal').addEventListener('show.bs.modal', () => {
            document.getElementById('nome').value = '';
            document.getElementById('usuario').value = '';
            document.getElementById('senha').value = '';
            document.getElementById('celular').value = '';
            document.getElementById('vencimento').value = '';
            document.getElementById('servidor_id').value = '';
            document.getElementById('nome').focus();
        });

        // Populate action modal
        document.querySelectorAll('.open-action-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const nome = btn.getAttribute('data-nome');
                const usuario = btn.getAttribute('data-usuario');
                const celular = btn.getAttribute('data-celular');
                const celularRaw = btn.getAttribute('data-celular-raw');
                const vencimento = btn.getAttribute('data-vencimento');
                const servidor = btn.getAttribute('data-servidor');

                document.getElementById('actionId').value = id;
                document.getElementById('actionNome').value = nome;
                document.getElementById('actionUsuario').value = usuario;
                document.getElementById('actionSenha').value = '';
                document.getElementById('actionCelular').value = celular;
                document.getElementById('actionVencimento').value = vencimento;
                document.getElementById('actionServidor').value = servidor;
                document.getElementById('deleteId').value = id;

                // Configure renew link
                const renewUrl = `renovar.php?user_id=${id}`;
                document.getElementById('renewLink').setAttribute('href', renewUrl);

                // Configure invoice link
                const invoiceUrl = `generate_single_invoice.php?user_id=${id}`;
                document.getElementById('invoiceLink').setAttribute('href', invoiceUrl);

                // Configure WhatsApp link
                const whatsappUrl = `https://wa.me/55${celularRaw}?text=Olá, ${encodeURIComponent(nome)}, como posso ajudar?`;
                document.getElementById('whatsappLink').setAttribute('href', whatsappUrl);
            });
        });
    </script>
</body>
</html>