<?php
session_start();
require_once '../includes/db.php';

// Verifica se admin está logado (ajuste conforme seu sistema)
if (!isset($_SESSION['admin_id']) || !is_numeric($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Processa exclusão em massa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'], $_POST['selected_plans'], $_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $selectedPlans = $_POST['selected_plans'];
        if (is_array($selectedPlans) && count($selectedPlans) > 0) {
            $placeholders = implode(',', array_fill(0, count($selectedPlans), '?'));
            $stmtDelBulk = $pdo->prepare("DELETE FROM planos WHERE id IN ($placeholders)");
            $stmtDelBulk->execute($selectedPlans);
            $_SESSION['message'] = "Planos selecionados foram deletados com sucesso.";
        }
    } else {
        $_SESSION['error'] = "Token CSRF inválido.";
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Processa exclusão individual
if (isset($_GET['delete_id'], $_GET['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_GET['csrf_token'])) {
        $deleteId = (int)$_GET['delete_id'];
        $stmtDel = $pdo->prepare("DELETE FROM planos WHERE id = ?");
        $stmtDel->execute([$deleteId]);
        $_SESSION['message'] = "Plano ID $deleteId deletado com sucesso.";
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    } else {
        $_SESSION['error'] = "Token CSRF inválido.";
    }
}

// Paginação
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Contar total de registros
$totalPlans = $pdo->query("SELECT COUNT(*) FROM planos")->fetchColumn();
$totalPages = ceil($totalPlans / $perPage);

// Buscar registros da página
$stmt = $pdo->prepare("SELECT * FROM planos ORDER BY criado_em DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$planos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Listar Planos</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f5f6fa;
        margin: 0; padding: 0;
    }
    .container {
        max-width: 1100px;
        margin: 30px auto;
        background: #fff;
        padding: 20px 30px;
        box-shadow: 0 0 8px rgba(0,0,0,0.1);
        border-radius: 8px;
    }
    h1 {
        color: #222;
        margin-bottom: 20px;
        font-size: 1.8rem;
        text-align: center;
    }
    /* Wrapper para tabela com overflow horizontal */
    .table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 15px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px; /* Garante largura mínima para manter o layout */
    }
    table thead tr {
        background-color: #34495e;
        color: #ecf0f1;
    }
    table th, table td {
        padding: 12px 10px;
        border: 1px solid #ddd;
        text-align: center;
        font-size: 14px;
        white-space: nowrap;
    }
    table tbody tr:hover {
        background-color: #f1f1f1;
    }
    .btn {
        padding: 6px 12px;
        border: none;
        border-radius: 4px;
        color: #fff;
        cursor: pointer;
        font-size: 13px;
        transition: background-color 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    .btn-editar {
        background-color: #27ae60;
    }
    .btn-editar:hover {
        background-color: #219150;
    }
    .btn-deletar {
        background-color: #e74c3c;
    }
    .btn-deletar:hover {
        background-color: #c0392b;
    }
    .btn-delete-massa {
        background-color: #c0392b;
        margin-bottom: 20px;
    }
    .btn-delete-massa:hover {
        background-color: #962d22;
    }
    .pagination {
        text-align: center;
        margin-top: 15px;
        flex-wrap: wrap;
        display: flex;
        justify-content: center;
        gap: 5px;
    }
    .pagination a {
        color: #34495e;
        padding: 8px 12px;
        margin: 0 3px;
        text-decoration: none;
        border-radius: 4px;
        border: 1px solid #ccc;
        font-size: 14px;
        transition: background-color 0.3s ease;
        min-width: 36px;
        text-align: center;
    }
    .pagination a:hover {
        background-color: #34495e;
        color: #fff;
    }
    .pagination a.current-page {
        background-color: #34495e;
        color: #fff;
        pointer-events: none;
    }
    .message {
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 5px;
        color: #2ecc71;
        background-color: #dff0d8;
        border: 1px solid #27ae60;
        text-align: center;
    }
    .error {
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 5px;
        color: #e74c3c;
        background-color: #f9d6d5;
        border: 1px solid #c0392b;
        text-align: center;
    }
    label.select-all-label {
        font-size: 14px;
        cursor: pointer;
        user-select: none;
    }

    /* Responsividade */
    @media (max-width: 768px) {
        .container {
            padding: 15px 15px;
            margin: 15px;
        }
        h1 {
            font-size: 1.5rem;
        }
        .btn, .btn-delete-massa {
            font-size: 12px;
            padding: 6px 10px;
        }
        table th, table td {
            font-size: 12px;
            padding: 8px 6px;
        }
        .pagination a {
            font-size: 12px;
            padding: 6px 8px;
            min-width: 28px;
        }
    }
    @media (max-width: 480px) {
        .btn, .btn-delete-massa {
            font-size: 11px;
            padding: 5px 8px;
        }
        label.select-all-label {
            font-size: 13px;
        }
    }
</style>
</head>
<body>
    
    <?php include __DIR__ . '/assets/sidebar.php'; ?>
    
<div class="container">
    <h1>Listar Planos</h1>

    <?php if (!empty($_SESSION['message'])): ?>
        <div class="message"><?= htmlspecialchars($_SESSION['message']) ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="error"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <form method="post" onsubmit="return confirm('Tem certeza que deseja deletar os planos selecionados?');">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <button type="submit" name="bulk_delete" class="btn btn-delete-massa">Deletar Selecionados</button>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th><label class="select-all-label"><input type="checkbox" id="selectAll"> Todos</label></th>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Valor 1</th>
                        <th>Valor 2</th>
                        <th>Valor 3</th>
                        <th>Valor 4</th>
                        <th>Duração</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($planos)): ?>
                        <tr><td colspan="9">Nenhum plano encontrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($planos as $plano): ?>
                        <tr>
                            <td><input type="checkbox" name="selected_plans[]" value="<?= $plano['id'] ?>"></td>
                            <td><?= $plano['id'] ?></td>
                            <td><?= htmlspecialchars($plano['nome']) ?></td>
                            <td><?= number_format($plano['valor_1'], 2, ',', '.') ?></td>
                            <td><?= number_format($plano['valor_2'], 2, ',', '.') ?></td>
                            <td><?= number_format($plano['valor_3'], 2, ',', '.') ?></td>
                            <td><?= number_format($plano['valor_4'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($plano['duracao']) ?> dias</td>
                            <td>
                                <a href="editar_plano.php?id=<?= $plano['id'] ?>" class="btn btn-editar" title="Editar">Editar</a>
                                <a href="?delete_id=<?= $plano['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-deletar" onclick="return confirm('Tem certeza que deseja deletar este plano?');" title="Deletar">Deletar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=1">&laquo; Primeiro</a>
            <a href="?page=<?= $page - 1 ?>">&lt; Anterior</a>
        <?php endif; ?>

        <?php
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'current-page' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>">Próximo &gt;</a>
            <a href="?page=<?= $totalPages ?>">Último &raquo;</a>
        <?php endif; ?>
    </div>
</div>

<script>
    // Selecionar/desselecionar todos os checkboxes
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="selected_plans[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>
</body>
</html>
