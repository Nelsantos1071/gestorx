<?php
// Verificar se a sessão já está ativa antes de iniciar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

if (!isset($_SESSION['admin_id']) || !is_numeric($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Função para gerar uma senha aleatória
function generateRandomPassword($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*';
    $charactersLength = strlen($characters);
    $randomPassword = '';
    for ($i = 0; $i < $length; $i++) {
        $randomPassword .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomPassword;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $action = htmlspecialchars($_POST['action'], ENT_QUOTES, 'UTF-8');

        try {
            $pdo->beginTransaction();

            if ($action === 'ativar' && isset($_POST['cliente_id'])) {
                $clienteId = (int)$_POST['cliente_id'];
                $stmtUpdate = $pdo->prepare("UPDATE clientes SET ativo = 1 WHERE id = :id");
                $stmtUpdate->execute(['id' => $clienteId]);
                $pdo->commit();
                $response = ['success' => true, 'ativo' => 1, 'cliente_id' => $clienteId];
            } elseif ($action === 'desativar' && isset($_POST['cliente_id'])) {
                $clienteId = (int)$_POST['cliente_id'];
                $stmtUpdate = $pdo->prepare("UPDATE clientes SET ativo = 0 WHERE id = :id");
                $stmtUpdate->execute(['id' => $clienteId]);
                $pdo->commit();
                $response = ['success' => true, 'ativo' => 0, 'cliente_id' => $clienteId];
            } elseif ($action === 'excluir' && isset($_POST['cliente_id'])) {
                $clienteId = (int)$_POST['cliente_id'];
                $stmtProdutos = $pdo->prepare("SELECT DISTINCT produto_id FROM alugueis WHERE cliente_id = :id");
                $stmtProdutos->execute(['id' => $clienteId]);
                $produtoIds = $stmtProdutos->fetchAll(PDO::FETCH_COLUMN);
                error_log("Produtos associados ao cliente_id=$clienteId: " . json_encode($produtoIds));

                $stmtDeleteAlugueis = $pdo->prepare("DELETE FROM alugueis WHERE cliente_id = :id");
                $stmtDeleteAlugueis->execute(['id' => $clienteId]);
                $deletedAlugueis = $stmtDeleteAlugueis->rowCount();
                error_log("Aluguéis deletados para cliente_id=$clienteId: $deletedAlugueis");

                $stmtDeleteCompras = $pdo->prepare("DELETE FROM compras WHERE cliente_id = :id");
                $stmtDeleteCompras->execute(['id' => $clienteId]);
                $deletedCompras = $stmtDeleteCompras->rowCount();
                error_log("Compras deletadas para cliente_id=$clienteId: $deletedCompras");

                $stmtCheckTable = $pdo->query("SHOW TABLES LIKE 'whatsapp_template'");
                $tableExists = $stmtCheckTable->rowCount() > 0;
                $deletedWhatsApp = 0;
                if ($tableExists) {
                    $stmtDeleteWhatsApp = $pdo->prepare("DELETE FROM whatsapp_template WHERE cliente_id = :id");
                    $stmtDeleteWhatsApp->execute(['id' => $clienteId]);
                    $deletedWhatsApp = $stmtDeleteWhatsApp->rowCount();
                    error_log("Registros do whatsapp_template deletados para cliente_id=$clienteId: $deletedWhatsApp");
                } else {
                    error_log("Tabela whatsapp_template não existe, pulando exclusão para cliente_id=$clienteId");
                }

                $stmtDeleteCliente = $pdo->prepare("DELETE FROM clientes WHERE id = :id");
                $stmtDeleteCliente->execute(['id' => $clienteId]);
                $clientDeleted = $stmtDeleteCliente->rowCount();
                error_log("Cliente deletado: id=$clienteId, linhas=$clientDeleted");

                foreach ($produtoIds as $produtoId) {
                    $stmtCheckAlugueis = $pdo->prepare("SELECT COUNT(*) FROM alugueis WHERE produto_id = :produto_id");
                    $stmtCheckAlugueis->execute(['produto_id' => $produtoId]);
                    $countAlugueis = $stmtCheckAlugueis->fetchColumn();

                    $stmtCheckCompras = $pdo->prepare("SELECT COUNT(*) FROM compras WHERE produto_id = :produto_id");
                    $stmtCheckCompras->execute(['produto_id' => $produtoId]);
                    $countCompras = $stmtCheckCompras->fetchColumn();

                    error_log("Produto_id=$produtoId: aluguéis=$countAlugueis, compras=$countCompras");

                    if ($countAlugueis == 0 && $countCompras == 0) {
                        $stmtDeleteProduto = $pdo->prepare("DELETE FROM produtos WHERE id = :produto_id");
                        $stmtDeleteProduto->execute(['produto_id' => $produtoId]);
                        $productDeleted = $stmtDeleteProduto->rowCount();
                        error_log("Produto deletado: id=$produtoId, linhas=$productDeleted");
                    }
                }

                $pdo->commit();
                $response = ['success' => true, 'action' => 'excluir', 'cliente_id' => $clienteId];
            } elseif ($action === 'editar' && isset($_POST['cliente_id'])) {
                $clienteId = (int)$_POST['cliente_id'];
                $nome = trim($_POST['nome']);
                $email = trim($_POST['email']);
                $telefone = isset($_POST['telefone']) ? preg_replace('/[^0-9]/', '', trim($_POST['telefone'])) : null;
                $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';

                if (empty($nome) || empty($email)) {
                    $response = ['success' => false, 'message' => 'Nome e e-mail são obrigatórios.'];
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $response = ['success' => false, 'message' => 'E-mail inválido.'];
                } elseif ($telefone && strlen($telefone) < 10) {
                    $response = ['success' => false, 'message' => 'Telefone inválido.'];
                } elseif ($senha && strlen($senha) < 8) {
                    $response = ['success' => false, 'message' => 'A senha deve ter pelo menos 8 caracteres.'];
                } else {
                    $sql = "UPDATE clientes SET nome = :nome, email = :email";
                    $params = [
                        'nome' => $nome,
                        'email' => $email,
                        'id' => $clienteId
                    ];
                    if ($telefone !== null) {
                        $sql .= ", telefone = :telefone";
                        $params['telefone'] = $telefone;
                    }
                    if (!empty($senha)) {
                        $sql .= ", senha = :senha";
                        $params['senha'] = password_hash($senha, PASSWORD_DEFAULT);
                    }
                    $sql .= " WHERE id = :id";

                    $stmtUpdate = $pdo->prepare($sql);
                    $stmtUpdate->execute($params);
                    $pdo->commit();
                    $response = [
                        'success' => true,
                        'action' => 'editar',
                        'cliente_id' => $clienteId,
                        'nome' => $nome,
                        'email' => $email,
                        'telefone' => $telefone
                    ];
                    error_log("Cliente editado: id=$clienteId, nome=$nome, email=$email, senha_atualizada=" . (!empty($senha) ? 'sim' : 'não'));
                }
            } elseif ($action === 'adicionar') {
                $nome = trim($_POST['nome']);
                $email = trim($_POST['email']);
                $telefone = isset($_POST['telefone']) ? preg_replace('/[^0-9]/', '', trim($_POST['telefone'])) : null;
                $senha = trim($_POST['senha']);

                if (empty($nome) || empty($email) || empty($senha)) {
                    $response = ['success' => false, 'message' => 'Nome, e-mail e senha são obrigatórios.'];
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $response = ['success' => false, 'message' => 'E-mail inválido.'];
                } elseif ($telefone && strlen($telefone) < 10) {
                    $response = ['success' => false, 'message' => 'Telefone inválido.'];
                } elseif (strlen($senha) < 8) {
                    $response = ['success' => false, 'message' => 'A senha deve ter pelo menos 8 caracteres.'];
                } else {
                    $sql = "INSERT INTO clientes (nome, email, telefone, senha, ativo) VALUES (:nome, :email, :telefone, :senha, 1)";
                    $params = [
                        'nome' => $nome,
                        'email' => $email,
                        'telefone' => $telefone,
                        'senha' => password_hash($senha, PASSWORD_DEFAULT)
                    ];
                    $stmtInsert = $pdo->prepare($sql);
                    $stmtInsert->execute($params);
                    $newClienteId = $pdo->lastInsertId();
                    $pdo->commit();
                    $response = [
                        'success' => true,
                        'action' => 'adicionar',
                        'cliente_id' => $newClienteId,
                        'nome' => $nome,
                        'email' => $email,
                        'telefone' => $telefone
                    ];
                    error_log("Novo cliente adicionado: id=$newClienteId, nome=$nome, email=$email");
                }
            } elseif ($action === 'resetar_senha' && isset($_POST['cliente_id'])) {
                $clienteId = (int)$_POST['cliente_id'];
                $novaSenha = generateRandomPassword();
                $stmtUpdate = $pdo->prepare("UPDATE clientes SET senha = :senha WHERE id = :id");
                $stmtUpdate->execute([
                    'senha' => password_hash($novaSenha, PASSWORD_DEFAULT),
                    'id' => $clienteId
                ]);
                $pdo->commit();
                $response = [
                    'success' => true,
                    'action' => 'resetar_senha',
                    'cliente_id' => $clienteId,
                    'new_password' => $novaSenha
                ];
                error_log("Senha resetada para cliente_id=$clienteId");
            } else {
                $response = ['success' => false, 'message' => 'Ação inválida.'];
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $response = ['success' => false, 'message' => 'Erro ao processar a ação: ' . htmlspecialchars($e->getMessage())];
            error_log("Erro PDO ao processar ação=$action: " . $e->getMessage());
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
        exit;
    }
}

$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

try {
    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM clientes");
    $totalClientes = $stmtTotal->fetchColumn();
    $totalPages = ceil($totalClientes / $perPage);

    $stmt = $pdo->prepare("
        SELECT id, nome, email, ativo 
        FROM clientes 
        ORDER BY id DESC 
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erro ao carregar os dados: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Clientes Cadastrados</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f8f9fa; }
        main.content { 
            margin-left: 220px; 
            padding: 2rem; 
            min-height: 100vh; 
            transition: margin-left 0.3s ease; 
        }
        .container {
            margin: 20px auto;
            max-width: 1100px;
            padding: 20px;
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            overflow-x: auto;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .btn-add {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            margin-bottom: 15px;
        }
        .btn-add:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
            white-space: nowrap;
        }
        table th {
            background-color: #f5f5f5;
        }
        .actions {
            display: flex;
            gap: 5px;
            flex-wrap: nowrap;
            align-items: center;
        }
        .btn {
            padding: 6px 10px;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 0.9rem;
            white-space: nowrap;
            min-width: 70px;
            text-align: center;
        }
        .btn-ativar {
            background-color: #4CAF50;
            color: white;
        }
        .btn-desativar {
            background-color: #f39c12;
            color: white;
        }
        .btn-editar {
            background-color: #007bff;
            color: white;
        }
        .btn-excluir {
            background-color: #e74c3c;
            color: white;
        }
        .btn-resetar {
            background-color: #6c757d;
            color: white;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #d93025;
        }
        .pagination {
            margin-top: 15px;
        }
        .pagination a {
            padding: 6px 10px;
            margin: 2px;
            border: 1px solid #ccc;
            text-decoration: none;
            color: #333;
        }
        .pagination a:hover {
            background-color: #eee;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            position: relative;
        }
        .modal-content h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .modal-content label {
            font-weight: 500;
            color: #333;
        }
        .modal-content input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .modal-content button {
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95rem;
        }
        .modal-content .edit-button {
            background: #28a745;
            color: #fff;
        }
        .modal-content .edit-button:hover {
            background: #218838;
        }
        .modal-content .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        @media (max-width: 767px) {
            main.content {
                margin-left: 0;
                padding: 1rem;
            }
            .container {
                padding: 10px;
                overflow-x: auto;
            }
            table {
                min-width: 700px;
            }
            table th, table td {
                padding: 8px;
                font-size: 0.85rem;
            }
            .actions {
                gap: 3px;
                flex-wrap: nowrap;
            }
            .btn {
                padding: 5px 8px;
                font-size: 0.8rem;
                min-width: 60px;
            }
            .btn-add {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
            .pagination {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 5px;
            }
            .pagination a {
                padding: 8px 12px;
                font-size: 1rem;
                min-width: 44px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/assets/sidebar.php'; ?>

    <main class="content">
        <div class="container">
            <h2>Clientes Cadastrados</h2>
            <button class="btn-add" onclick="openAddModal()">Adicionar Cliente</button>
            <div id="alert-container"></div>

            <?php if (empty($clientes)): ?>
                <p>Nenhum cliente encontrado.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr data-cliente-id="<?= $cliente['id']; ?>">
                                <td data-label="ID"><?= $cliente['id']; ?></td>
                                <td data-label="Nome"><?= htmlspecialchars($cliente['nome']); ?></td>
                                <td data-label="Email"><?= htmlspecialchars($cliente['email']); ?></td>
                                <td data-label="Status" class="status-cell">
                                    <?= $cliente['ativo'] ? '<span style="color:green;">Ativo</span>' : '<span style="color:red;">Inativo</span>'; ?>
                                </td>
                                <td data-label="Ações">
                                    <div class="actions">
                                        <button class="btn <?= $cliente['ativo'] ? 'btn-desativar' : 'btn-ativar'; ?> toggle-status"
                                                data-cliente-id="<?= $cliente['id']; ?>"
                                                data-action="<?= $cliente['ativo'] ? 'desativar' : 'ativar'; ?>">
                                            <?= $cliente['ativo'] ? 'Desativar' : 'Ativar'; ?>
                                        </button>
                                        <button class="btn btn-editar" onclick="openEditModal(<?= $cliente['id']; ?>)">Editar</button>
                                        <button class="btn btn-resetar reset-senha"
                                                data-cliente-id="<?= $cliente['id']; ?>">Resetar Senha</button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir todos os aluguéis, compras, templates de WhatsApp e dados do cliente permanentemente?');">
                                            <input type="hidden" name="cliente_id" value="<?= $cliente['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="action" value="excluir">
                                            <button class="btn btn-excluir" type="submit">Excluir</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?= $i; ?>" <?= ($i === $page) ? 'style="font-weight:bold;"' : ''; ?>>
                                <?= $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeModal()">×</span>
                <h3 id="modal-title">Editar Cliente</h3>
                <form id="cliente-form">
                    <input type="hidden" name="cliente_id" id="modal-cliente-id">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" id="modal-action" value="editar">
                    <label for="modal-nome">Nome:</label>
                    <input type="text" name="nome" id="modal-nome" required>
                    <label for="modal-email">E-mail:</label>
                    <input type="email" name="email" id="modal-email" required>
                    <label for="modal-telefone">Telefone (opcional):</label>
                    <input type="text" name="telefone" id="modal-telefone" maxlength="15">
                    <label for="modal-senha">Senha (mínimo 8 caracteres, deixe em branco para não alterar):</label>
                    <input type="password" name="senha" id="modal-senha">
                    <button type="submit" class="edit-button">Salvar</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        $(document).ready(function() {
            $('.toggle-status').on('click', function() {
                const button = $(this);
                const clienteId = button.data('cliente-id');
                const action = button.data('action');
                const csrfToken = '<?= $_SESSION['csrf_token']; ?>';
                const row = button.closest('tr');
                const statusCell = row.find('.status-cell');

                $.ajax({
                    url: 'ver_usuarios.php',
                    type: 'POST',
                    data: {
                        cliente_id: clienteId,
                        action: action,
                        csrf_token: csrfToken
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            if (response.ativo === 1) {
                                statusCell.html('<span style="color:green;">Ativo</span>');
                                button.removeClass('btn-ativar').addClass('btn-desativar')
                                      .text('Desativar')
                                      .data('action', 'desativar');
                                sessionStorage.setItem('activeClienteId', response.cliente_id);
                                sessionStorage.setItem('clienteStatus', '1');
                            } else if (response.ativo === 0) {
                                statusCell.html('<span style="color:red;">Inativo</span>');
                                button.removeClass('btn-desativar').addClass('btn-ativar')
                                      .text('Ativar')
                                      .data('action', 'ativar');
                                sessionStorage.setItem('activeClienteId', response.cliente_id);
                                sessionStorage.setItem('clienteStatus', '0');
                            }
                            $('#alert-container').html(
                                '<div class="alert alert-success">Status atualizado com sucesso!</div>'
                            );
                            setTimeout(() => $('#alert-container').empty(), 3000);
                        } else {
                            $('#alert-container').html(
                                `<div class="alert alert-danger">${response.message || 'Erro ao processar a ação.'}</div>`
                            );
                            setTimeout(() => $('#alert-container').empty(), 3000);
                        }
                    },
                    error: function() {
                        $('#alert-container').html(
                            '<div class="alert alert-danger">Erro ao conectar com o servidor.</div>'
                        );
                        setTimeout(() => $('#alert-container').empty(), 3000);
                    }
                });
            });

            $('.reset-senha').on('click', function() {
                const button = $(this);
                const clienteId = button.data('cliente-id');
                const csrfToken = '<?= $_SESSION['csrf_token']; ?>';

                if (confirm('Tem certeza que deseja resetar a senha deste cliente? A nova senha será exibida para você compartilhar com o cliente.')) {
                    $.ajax({
                        url: 'ver_usuarios.php',
                        type: 'POST',
                        data: {
                            cliente_id: clienteId,
                            action: 'resetar_senha',
                            csrf_token: csrfToken
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $('#alert-container').html(
                                    `<div class="alert alert-success">Senha resetada com sucesso! Nova senha: <strong>${response.new_password}</strong></div>`
                                );
                                setTimeout(() => $('#alert-container').empty(), 10000); // Mantém o alerta por 10 segundos
                            } else {
                                $('#alert-container').html(
                                    `<div class="alert alert-danger">${response.message || 'Erro ao resetar a senha.'}</div>`
                                );
                                setTimeout(() => $('#alert-container').empty(), 3000);
                            }
                        },
                        error: function() {
                            $('#alert-container').html(
                                '<div class="alert alert-danger">Erro ao conectar com o servidor.</div>'
                            );
                            setTimeout(() => $('#alert-container').empty(), 3000);
                        }
                    });
                }
            });

            $('form').on('submit', function(e) {
                if ($(this).find('input[name="action"]').val() === 'excluir') {
                    e.preventDefault();
                    const form = $(this);
                    const clienteId = form.find('input[name="cliente_id"]').val();
                    const csrfToken = form.find('input[name="csrf_token"]').val();
                    const row = form.closest('tr');

                    if (confirm('Tem certeza que deseja excluir todos os aluguéis, compras, templates de WhatsApp e dados do cliente permanentemente?')) {
                        $.ajax({
                            url: 'ver_usuarios.php',
                            type: 'POST',
                            data: {
                                cliente_id: clienteId,
                                action: 'excluir',
                                csrf_token: csrfToken
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    row.remove();
                                    $('#alert-container').html(
                                        '<div class="alert alert-success">Cliente excluído com sucesso!</div>'
                                    );
                                    setTimeout(() => $('#alert-container').empty(), 3000);
                                } else {
                                    $('#alert-container').html(
                                        `<div class="alert alert-danger">${response.message || 'Erro ao excluir o cliente.'}</div>`
                                    );
                                    setTimeout(() => $('#alert-container').empty(), 3000);
                                }
                            },
                            error: function() {
                                $('#alert-container').html(
                                    '<div class="alert alert-danger">Erro ao conectar com o servidor.</div>'
                                );
                                setTimeout(() => $('#alert-container').empty(), 3000);
                            }
                        });
                    }
                }
            });

            $('#cliente-form').on('submit', function(e) {
                e.preventDefault();
                const form = $(this);
                const action = $('#modal-action').val();
                const data = form.serialize();

                $.ajax({
                    url: 'ver_usuarios.php',
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            if (action === 'editar') {
                                const row = $(`tr[data-cliente-id="${response.cliente_id}"]`);
                                row.find('td[data-label="Nome"]').text(response.nome);
                                row.find('td[data-label="Email"]').text(response.email);
                                $('#alert-container').html(
                                    '<div class="alert alert-success">Cliente atualizado com sucesso!</div>'
                                );
                            } else if (action === 'adicionar') {
                                const newRow = `
                                    <tr data-cliente-id="${response.cliente_id}">
                                        <td data-label="ID">${response.cliente_id}</td>
                                        <td data-label="Nome">${response.nome}</td>
                                        <td data-label="Email">${response.email}</td>
                                        <td data-label="Status" class="status-cell">
                                            <span style="color:green;">Ativo</span>
                                        </td>
                                        <td data-label="Ações">
                                            <div class="actions">
                                                <button class="btn btn-desativar toggle-status"
                                                        data-cliente-id="${response.cliente_id}"
                                                        data-action="desativar">
                                                    Desativar
                                                </button>
                                                <button class="btn btn-editar" onclick="openEditModal(${response.cliente_id})">
                                                    Editar
                                                </button>
                                                <button class="btn btn-resetar reset-senha"
                                                        data-cliente-id="${response.cliente_id}">
                                                    Resetar Senha
                                                </button>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir todos os aluguéis, compras, templates de WhatsApp e dados do cliente permanentemente?');">
                                                    <input type="hidden" name="cliente_id" value="${response.cliente_id}">
                                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                                    <input type="hidden" name="action" value="excluir">
                                                    <button class="btn btn-excluir" type="submit">Excluir</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>`;
                                $('table tbody').prepend(newRow);
                                $('#alert-container').html(
                                    '<div class="alert alert-success">Cliente adicionado com sucesso!</div>'
                                );
                            }
                            closeModal();
                            setTimeout(() => $('#alert-container').empty(), 3000);
                        } else {
                            $('#alert-container').html(
                                `<div class="alert alert-danger">${response.message || 'Erro ao processar a ação.'}</div>`
                            );
                            setTimeout(() => $('#alert-container').empty(), 3000);
                        }
                    },
                    error: function() {
                        $('#alert-container').html(
                            '<div class="alert alert-danger">Erro ao conectar com o servidor.</div>'
                        );
                        setTimeout(() => $('#alert-container').empty(), 3000);
                    }
                });
            });
        });

        function openAddModal() {
            const modal = document.getElementById('editModal');
            const modalTitle = document.getElementById('modal-title');
            const form = document.getElementById('cliente-form');
            if (modal && modalTitle && form) {
                modalTitle.textContent = 'Adicionar Cliente';
                document.getElementById('modal-cliente-id').value = '';
                document.getElementById('modal-nome').value = '';
                document.getElementById('modal-email').value = '';
                document.getElementById('modal-telefone').value = '';
                document.getElementById('modal-senha').value = '';
                document.getElementById('modal-action').value = 'adicionar';
                document.getElementById('modal-senha').setAttribute('required', 'required');
                modal.style.display = 'flex';
            } else {
                $('#alert-container').html(
                    '<div class="alert alert-danger">Erro: Elementos do modal não encontrados.</div>'
                );
                setTimeout(() => $('#alert-container').empty(), 3000);
            }
        }

        function openEditModal(clienteId) {
            const modal = document.getElementById('editModal');
            const modalTitle = document.getElementById('modal-title');
            const clienteIdInput = document.getElementById('modal-cliente-id');
            if (!modal || !modalTitle || !clienteIdInput) {
                $('#alert-container').html(
                    '<div class="alert alert-danger">Erro: Elementos do modal não encontrados.</div>'
                );
                setTimeout(() => $('#alert-container').empty(), 3000);
                return;
            }

            modalTitle.textContent = 'Editar Cliente';
            clienteIdInput.value = clienteId;
            document.getElementById('modal-action').value = 'editar';
            document.getElementById('modal-senha').removeAttribute('required');
            document.getElementById('modal-senha').value = '';

            fetch(`get_cliente.php?cliente_id=${clienteId}`)
                .then(response => {
                    if (!response.ok) throw new Error('Erro na resposta da rede');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('modal-nome').value = data.cliente.nome || '';
                        document.getElementById('modal-email').value = data.cliente.email || '';
                        document.getElementById('modal-telefone').value = data.cliente.telefone ? formatTelefone(data.cliente.telefone) : '';
                        modal.style.display = 'flex';
                    } else {
                        $('#alert-container').html(
                            `<div class="alert alert-danger">Erro ao carregar dados do cliente: ${data.message || 'Dados inválidos'}</div>`
                        );
                        setTimeout(() => $('#alert-container').empty(), 3000);
                    }
                })
                .catch(error => {
                    $('#alert-container').html(
                        '<div class="alert alert-danger">Erro ao carregar dados do cliente: falha na conexão com o servidor.</div>'
                    );
                    setTimeout(() => $('#alert-container').empty(), 3000);
                });
        }

        function closeModal() {
            const modal = document.getElementById('editModal');
            if (modal) modal.style.display = 'none';
        }

        function formatTelefone(telefone) {
            if (!telefone) return '';
            if (telefone.length === 11) {
                return telefone.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (telefone.length === 10) {
                return telefone.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
            }
            return telefone;
        }

        $(document).on('input', '#modal-telefone', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            if (value.length > 10) {
                e.target.value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
            } else if (value.length > 6) {
                e.target.value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                e.target.value = value.replace(/(\d{2})(\d{0,5})/, '($1) $2');
            } else {
                e.target.value = value;
            }
        });

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeModal();
            }
        };
    </script>
</body>
</html>