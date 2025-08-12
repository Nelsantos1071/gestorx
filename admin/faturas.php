<?php
include_once '../includes/db.php';

$busca = $_GET['busca'] ?? '';
$statusFiltro = $_GET['status'] ?? '';
$pagina = $_GET['pagina'] ?? 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

$params = [];
$where = [];

if (!empty($busca)) {
    $where[] = "(f.id LIKE :busca OR p.nome LIKE :busca)";
    $params[':busca'] = "%$busca%";
}

if (!empty($statusFiltro)) {
    $where[] = "f.status = :status";
    $params[':status'] = $statusFiltro;
}

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $sql = "SELECT f.*, p.nome AS plano_nome 
            FROM faturas f 
            LEFT JOIN planos p ON f.plano_id = p.id 
            $whereSQL 
            ORDER BY f.id DESC 
            LIMIT :limite OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $faturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contagem total para paginação
    $conta = $pdo->prepare("SELECT COUNT(*) FROM faturas f 
                            LEFT JOIN planos p ON f.plano_id = p.id 
                            $whereSQL");
    foreach ($params as $key => $val) {
        $conta->bindValue($key, $val);
    }
    $conta->execute();
    $total = $conta->fetchColumn();
    $totalPaginas = ceil($total / $limite);

} catch (PDOException $e) {
    error_log("Erro: " . $e->getMessage());
    $faturas = [];
    $totalPaginas = 1;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Faturas</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f5f7fa; /* Cor de fundo consistente com o sistema */
        color: #333;
        margin: 0;
        min-height: 100vh;
    }

    a {
        text-decoration: none;
        color: inherit;
    }

    .content {
        margin-left: 220px;
        padding: 30px;
        min-height: 100vh;
        background: #fff;
        box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
        transition: margin-left 0.3s ease-in-out;
    }

    h2 {
        color: #293a5e;
        font-weight: 700;
        font-size: 1.9rem;
        margin-bottom: 20px;
        text-align: center;
    }

    form.filtro {
        margin-bottom: 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: center;
    }

    form.filtro input, form.filtro select, form.filtro button {
        padding: 10px;
        font-size: 1rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        transition: border-color 0.3s ease;
    }

    form.filtro input:focus, form.filtro select:focus {
        outline: none;
        border-color: #293a5e;
    }

    form.filtro button {
        background: #293a5e;
        color: white;
        border: none;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    form.filtro button:hover {
        background: #1a263e;
    }

    table {
        width: 100%;
        background: white;
        border-collapse: collapse;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }

    th, td {
        padding: 12px;
        text-align: left;
    }

    th {
        background: #293a5e;
        color: white;
        border-right: 2px solid #fff;
        font-weight: 600;
        font-size: 1rem;
    }

    th:last-child {
        border-right: none;
    }

    tbody tr {
        border-bottom: 2px solid #e0e0e0;
        transition: background 0.3s ease;
    }

    tbody tr:hover {
        background: #f8f9fa;
    }

    td {
        font-size: 0.95rem;
    }

    .btn-acoes {
        background: #293a5e;
        border: none;
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: background 0.3s ease;
    }

    .btn-acoes:hover {
        background: #1a263e;
    }

    .btn-excluir-todas {
        background-color: #dc3545;
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: background 0.3s ease;
    }

    .btn-excluir-todas:hover {
        background-color: #c82333;
    }

    .no-results {
        text-align: center;
        color: #666;
        font-size: 1.1rem;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal {
        background: white;
        padding: 25px;
        border-radius: 8px;
        width: 90%;
        max-width: 350px;
        text-align: center;
        position: relative;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
    }

    .modal h3 {
        margin-top: 0;
        color: #293a5e;
        font-size: 1.3rem;
    }

    .btn-edit, .btn-delete {
        width: 80%;
        margin: 10px auto;
        display: block;
        padding: 12px;
        border: none;
        border-radius: 4px;
        font-size: 1rem;
        cursor: pointer;
        transition: background 0.3s ease;
    }

    .btn-edit {
        background-color: #28a745;
        color: white;
    }

    .btn-edit:hover {
        background-color: #218838;
    }

    .btn-delete {
        background-color: #dc3545;
        color: white;
    }

    .btn-delete:hover {
        background-color: #c82333;
    }

    .close-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 1.2rem;
        background: transparent;
        border: none;
        cursor: pointer;
        color: #666;
        transition: color 0.3s ease;
    }

    .close-btn:hover {
        color: #293a5e;
    }

    .paginacao {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .paginacao a {
        padding: 8px 14px;
        text-decoration: none;
        color: #293a5e;
        font-weight: bold;
        border: 1px solid #ddd;
        border-radius: 4px;
        transition: background 0.3s ease, color 0.3s ease;
    }

    .paginacao a:hover {
        background: #293a5e;
        color: white;
    }

    .paginacao a.active {
        background: #293a5e;
        color: white;
        border-color: #293a5e;
    }

    /* Responsividade */
    @media (max-width: 1024px) {
        .content {
            margin-left: 0;
            padding: 20px;
        }

        form.filtro {
            flex-direction: column;
            align-items: stretch;
        }

        form.filtro input, form.filtro select, form.filtro button {
            width: 100%;
        }

        table {
            font-size: 0.9rem;
        }

        th, td {
            padding: 10px;
        }

        .btn-acoes {
            padding: 6px 10px;
            font-size: 0.85rem;
        }
    }

    @media (max-width: 768px) {
        h2 {
            font-size: 1.5rem;
        }

        table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }

        th, td {
            min-width: 120px;
        }

        .btn-excluir-todas {
            font-size: 0.9rem;
            padding: 8px 12px;
        }

        .modal h3 {
            font-size: 1.1rem;
        }

        .btn-edit, .btn-delete {
            font-size: 0.9rem;
            padding: 10px;
        }
    }

    @media (max-width: 480px) {
        h2 {
            font-size: 1.3rem;
        }

        form.filtro input, form.filtro select, form.filtro button {
            font-size: 0.85rem;
        }

        th, td {
            padding: 8px;
            font-size: 0.8rem;
        }

        .btn-acoes {
            font-size: 0.8rem;
            padding: 5px 8px;
        }

        .btn-excluir-todas {
            font-size: 0.85rem;
            padding: 6px 10px;
        }

        .paginacao a {
            padding: 6px 10px;
            font-size: 0.85rem;
        }
    }
    </style>
</head>
<body>

<?php include __DIR__ . '/assets/sidebar.php'; ?>

<div class="content">
    <h2>Lista de Faturas</h2>

    <form method="get" class="filtro">
        <input type="text" name="busca" placeholder="Buscar por ID ou Plano" value="<?= htmlspecialchars($busca) ?>">
        <select name="status">
            <option value="">Todos os Status</option>
            <option value="pago" <?= $statusFiltro == 'pago' ? 'selected' : '' ?>>Pago</option>
            <option value="pendente" <?= $statusFiltro == 'pendente' ? 'selected' : '' ?>>Pendente</option>
            <option value="vencido" <?= $statusFiltro == 'vencido' ? 'selected' : '' ?>>Vencido</option>
        </select>
        <button type="submit">Filtrar</button>
    </form>

    <form method="POST" action="excluir_todas_faturas.php" onsubmit="return confirm('Tem certeza que deseja excluir todas as faturas? Esta ação não poderá ser desfeita.')">
        <button type="submit" class="btn-excluir-todas">
            <i class="fas fa-trash-alt"></i> Excluir Todas as Faturas
        </button>
    </form>

    <?php if (count($faturas) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Plano</th>
                <th>Status</th>
                <th>Vencimento</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($faturas as $f): ?>
            <tr>
                <td><?= $f['id'] ?></td>
                <td><?= htmlspecialchars($f['plano_nome']) ?></td>
                <td><?= htmlspecialchars($f['status']) ?></td>
                <td><?= date('d/m/Y', strtotime($f['data_vencimento'])) ?></td>
                <td>
                    <button class="btn-acoes" data-id="<?= $f['id'] ?>" onclick="abrirModal(this)">
                        <i class="fas fa-ellipsis-h"></i> Ações
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="paginacao">
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <a href="?pagina=<?= $i ?>&busca=<?= urlencode($busca) ?>&status=<?= urlencode($statusFiltro) ?>" class="<?= $pagina == $i ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php else: ?>
        <p class="no-results">Nenhuma fatura encontrada.</p>
    <?php endif; ?>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <button class="close-btn" onclick="fecharModal()"><i class="fas fa-times"></i></button>
        <h3>Ações para fatura <span id="modalFaturaID"></span></h3>
        <form method="POST" action="editar_fatura.php">
            <input type="hidden" name="fatura_id" id="editFaturaId">
            <button type="submit" class="btn-edit"><i class="fas fa-edit"></i> Editar</button>
        </form>
        <form method="POST" action="excluir_fatura.php" onsubmit="return confirm('Tem certeza que deseja excluir esta fatura?')">
            <input type="hidden" name="fatura_id" id="deleteFaturaId">
            <button type="submit" class="btn-delete"><i class="fas fa-trash-alt"></i> Excluir</button>
        </form>
    </div>
</div>

<script>
function abrirModal(botao) {
    const id = botao.getAttribute('data-id');
    document.getElementById('modalFaturaID').textContent = id;
    document.getElementById('editFaturaId').value = id;
    document.getElementById('deleteFaturaId').value = id;
    document.getElementById('modalOverlay').classList.add('active');
}

function fecharModal() {
    document.getElementById('modalOverlay').classList.remove('active');
}
</script>

</body>
</html>