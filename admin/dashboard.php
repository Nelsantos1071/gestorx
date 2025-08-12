<?php
session_start();
require_once '../includes/db.php';

// Verifica login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Função reutilizável para executar consultas
function executeQuery($pdo, $query, $params = []) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erro na consulta: " . $e->getMessage());
        return 0;
    }
}

// Estatísticas gerais
$totalUsuarios = executeQuery($pdo, "SELECT COUNT(*) FROM users");
$totalClientes = executeQuery($pdo, "SELECT COUNT(*) FROM clientes");
$totalVencidos = executeQuery($pdo, "SELECT COUNT(*) FROM clientes WHERE vencimento < CURDATE()");
$usuariosAVencer3Dias = executeQuery($pdo, "SELECT COUNT(*) FROM clientes WHERE DATE(vencimento) BETWEEN CURDATE() AND CURDATE() + INTERVAL 3 DAY");
$usuariosVencendoHoje = executeQuery($pdo, "SELECT COUNT(*) FROM clientes WHERE DATE(vencimento) = CURDATE()");
$pagamentosPendentes = executeQuery($pdo, "SELECT COUNT(*) FROM faturas WHERE status = ?", ['pendente']);

// Fluxo Mensal de Pagamentos (apenas faturas pagas)
$fluxoMensalPagamentos = executeQuery($pdo, "SELECT SUM(valor) FROM faturas WHERE status = ? AND MONTH(data_vencimento) = MONTH(CURDATE()) AND YEAR(data_vencimento) = YEAR(CURDATE())", ['pago']);
$debugFaturasCount = executeQuery($pdo, "SELECT COUNT(*) FROM faturas");
if ($fluxoMensalPagamentos === 0) {
    error_log("Fluxo Mensal zero. Total faturas: $debugFaturasCount");
}
if ($debugFaturasCount == 0) {
    $_SESSION['warning'] = "Nenhuma fatura encontrada. Gere faturas em 'Clientes'.";
}

// Log valores monetários
try {
    $stmt = $pdo->query("SELECT id, valor_1, valor_2, valor_3, valor_4 FROM planos");
    $planos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Planos monetários: " . json_encode($planos));
    $stmt = $pdo->query("SELECT id, preco FROM produtos_servicos");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Produtos/Serviços monetários: " . json_encode($produtos));
} catch (PDOException $e) {
    error_log("Erro ao logar valores monetários: " . $e->getMessage());
}

// Busca clientes com vencimento em até 3 dias
try {
    $stmt = $pdo->prepare("
        SELECT id, nome AS nome_completo, vencimento
        FROM clientes
        WHERE ativo = 1
        AND vencimento IS NOT NULL
        AND DATE(vencimento) BETWEEN CURDATE() AND CURDATE() + INTERVAL 3 DAY
        ORDER BY vencimento, criado_em
    ");
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar clientes: " . $e->getMessage());
    $clientes = [];
}

// Busca faturas pendentes
try {
    $stmt = $pdo->prepare("
        SELECT f.id, f.user_id, f.valor, f.data_vencimento, f.pix_string, c.nome AS nome_completo, p.nome AS plano_nome, c.telefone
        FROM faturas f
        JOIN clientes c ON f.user_id = c.id
        JOIN planos p ON f.plano_id = p.id
        WHERE f.status = 'pendente'
        ORDER BY f.data_vencimento
    ");
    $stmt->execute();
    $faturasPendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar faturas pendentes: " . $e->getMessage());
    $faturasPendentes = [];
}

// Função para formatar em BRL
function formatBRL($value) {
    return 'R$ ' . number_format((float)$value, 2, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f8f9fa; }
        main.content { margin-left: 220px; padding: 2rem; min-height: 100vh; transition: margin-left 0.3s ease; }
        .row { display: flex; flex-wrap: wrap; gap: 1rem; }
        .card {
            flex: 1 1 250px;
            padding: 1.5rem;
            border-radius: 0.5rem;
            color: #fff;
            font-weight: bold;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }
        .bg-primary { background-color: #007bff; }
        .bg-success { background-color: #28a745; }
        .bg-warning { background-color: #ffc107; color: #212529; }
        .bg-danger { background-color: #dc3545; }
        .bg-secondary { background-color: #6c757d; }
        .bg-dark { background-color: #343a40; }
        .card h2 { font-size: 1.25rem; margin-bottom: 0.5rem; }
        .card p { font-size: 1.75rem; }
        .chart-grid { display: flex; flex-wrap: wrap; gap: 2rem; margin-top: 1rem; }
        .chart-container {
            flex: 1 1 400px;
            max-width: 100%;
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        .table-container {
            margin-top: 2rem;
            background: #fff;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }
        .table-container h3 { margin-top: 1.5rem; font-size: 1.2rem; }
        .table-container table { width: 100%; border-collapse: collapse; }
        .table-container th, .table-container td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #dee2e6; }
        .table-container th { background-color: #f1f1f1; font-weight: bold; }
        .table-container .no-data { text-align: center; padding: 1rem; color: #6c757d; }
        .action-btn {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background-color 0.3s ease;
            text-decoration: none;
            margin-right: 0.5rem;
        }
        .action-btn:hover { background-color: #218838; }
        .invoice-btn { background-color: #ffc107; color: #212529; }
        .invoice-btn:hover { background-color: #e0a800; }
        .pix-btn { background-color: #007bff; }
        .pix-btn:hover { background-color: #0056b3; }
        .whatsapp-btn { background-color: #25D366; color: #fff; }
        .whatsapp-btn:hover { background-color: #1DA851; }
        .alert {
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
            position: relative;
        }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background-color: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .alert-dismissible .close { position: absolute; top: 0.5rem; right: 1rem; cursor: pointer; font-size: 1.2rem; }
        @media (max-width: 767.98px) {
            main.content { margin-left: 0; padding: 1rem; }
            .row { flex-direction: column; }
            .card { flex: 1 1 100%; }
            .table-container table, .table-container thead, .table-container tbody, .table-container th, .table-container td, .table-container tr { display: block; }
            .table-container thead tr { display: none; }
            .table-container tr { margin-bottom: 1rem; border-bottom: 1px solid #dee2e6; }
            .table-container td { position: relative; padding-left: 50%; text-align: right; }
            .table-container td::before { content: attr(data-label); position: absolute; left: 0.75rem; width: 45%; font-weight: bold; }
            .table-container td[data-label="Ações"] { text-align: center; padding-left: 0; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/assets/sidebar.php'; ?>

<main class="content">
    <?php if (isset($_GET['mensagem'])): ?>
        <div class="alert alert-success alert-dismissible">
            <?php echo htmlspecialchars($_GET['mensagem']); ?>
            <span class="close" onclick="this.parentElement.style.display='none'">×</span>
        </div>
    <?php elseif (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <span class="close" onclick="this.parentElement.style.display='none'">×</span>
        </div>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <span class="close" onclick="this.parentElement.style.display='none'">×</span>
        </div>
    <?php elseif (isset($_SESSION['warning'])): ?>
        <div class="alert alert-warning alert-dismissible">
            <?php echo htmlspecialchars($_SESSION['warning']); unset($_SESSION['warning']); ?>
            <span class="close" onclick="this.parentElement.style.display='none'">×</span>
        </div>
    <?php endif; ?>
    <h1 style="text-align: center;">Dashboard</h1>

    <div class="row">
        <div class="card bg-success"><h2>Total Clientes</h2><p><?php echo htmlspecialchars($totalClientes); ?></p></div>
        <div class="card bg-success"><h2>Total Usuários</h2><p><?php echo htmlspecialchars($totalUsuarios); ?></p></div>
        <div class="card bg-secondary"><h2>Total Vencidos</h2><p><?php echo htmlspecialchars($totalVencidos); ?></p></div>
        <div class="card bg-danger"><h2>Clientes a Vencer (3 dias)</h2><p><?php echo htmlspecialchars($usuariosAVencer3Dias); ?></p></div>
        <div class="card bg-warning"><h2>Pagamentos Pendentes</h2><p><?php echo htmlspecialchars($pagamentosPendentes); ?></p></div>
        <div class="card bg-dark"><h2>Clientes Vencendo Hoje</h2><p><?php echo htmlspecialchars($usuariosVencendoHoje); ?></p></div>
        <div class="card bg-primary"><h2>Fluxo Mensal de Pagamentos</h2><p><?php echo formatBRL($fluxoMensalPagamentos ?: 0); ?></p></div>
    </div>

    <section class="table-container">
        <h2>Clientes com Vencimento em até 3 Dias</h2>
        <?php if (empty($clientes)): ?>
            <p class="no-data">Nenhum cliente com vencimento em até 3 dias.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>ID</th><th>Nome Completo</th><th>Vencimento</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td data-label="ID"><?php echo htmlspecialchars($cliente['id']); ?></td>
                            <td data-label="Nome Completo"><?php echo htmlspecialchars($cliente['nome_completo']); ?></td>
                            <td data-label="Vencimento"><?php echo htmlspecialchars($cliente['vencimento'] ? date('d/m/Y', strtotime($cliente['vencimento'])) : 'Não definido'); ?></td>
                            <td data-label="Ações">
                                <a href="renovar.php?client_id=<?php echo htmlspecialchars($cliente['id']); ?>" class="action-btn">Renovar</a>
                                <a href="generate_invoices.php?client_id=<?php echo htmlspecialchars($cliente['id']); ?>" class="action-btn invoice-btn">Gerar Fatura</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="table-container">
        <h2>Faturas Pendentes</h2>
        <?php if (empty($faturasPendentes)): ?>
            <p class="no-data">Nenhuma fatura pendente.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>ID Fatura</th><th>Cliente</th><th>Plano</th><th>Valor</th><th>Vencimento</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($faturasPendentes as $fatura): ?>
                        <tr>
                            <td data-label="ID Fatura"><?php echo htmlspecialchars($fatura['id']); ?></td>
                            <td data-label="Cliente"><?php echo htmlspecialchars($fatura['nome_completo']); ?></td>
                            <td data-label="Plano"><?php echo htmlspecialchars($fatura['plano_nome']); ?></td>
                            <td data-label="Valor"><?php echo formatBRL($fatura['valor']); ?></td>
                            <td data-label="Vencimento"><?php echo htmlspecialchars(date('d/m/Y', strtotime($fatura['data_vencimento']))); ?></td>
                            <td data-label="Ações">
                                <a href="pagamento_pix.php?fatura_id=<?php echo htmlspecialchars($fatura['id']); ?>" class="action-btn pix-btn">Ver Pix</a>
                                <?php if (!empty($fatura['telefone'])): ?>
                                    <a href="https://api.whatsapp.com/send?phone=<?php echo urlencode($fatura['telefone']); ?>&text=<?php echo urlencode("Olá {$fatura['nome_completo']}, sua fatura ID {$fatura['id']} no valor de " . formatBRL($fatura['valor']) . " vence em " . date('d/m/Y', strtotime($fatura['data_vencimento'])) . ". Pague com Pix: {$fatura['pix_string']}"); ?>" class="action-btn whatsapp-btn" target="_blank">Enviar WhatsApp</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section style="margin-top: 2rem;">
        <h2>Gráficos</h2>
        <div class="chart-grid">
            <div class="chart-container"><canvas id="graficoPizzaUsuarios"></canvas></div>
            <div class="chart-container" style="height: 300px;"><canvas id="graficoLinha"></canvas></div>
        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gráfico Pizza
    const cardData = [
        <?php echo $totalClientes; ?>,
        <?php echo $totalUsuarios; ?>,
        <?php echo $totalVencidos; ?>,
        <?php echo $usuariosAVencer3Dias; ?>,
        <?php echo $pagamentosPendentes; ?>,
        <?php echo $usuariosVencendoHoje; ?>
    ];
    const totalCardData = cardData.reduce((a, b) => a + b, 0);
    const cardLabels = ['Total Clientes', 'Total Usuários', 'Total Vencidos', 'Clientes a Vencer (3 Dias)', 'Pagamentos Pendentes', 'Clientes Vencendo Hoje'];
    const cardColors = totalCardData === 0 ? ['#ccc'] : ['#28a745', '#28a745', '#6c757d', '#dc3545', '#ffc107', '#343a40'];

    new Chart(document.getElementById('graficoPizzaUsuarios').getContext('2d'), {
        type: 'pie',
        data: {
            labels: totalCardData === 0 ? ['Sem dados'] : cardLabels,
            datasets: [{ data: totalCardData === 0 ? [1] : cardData, backgroundColor: cardColors, borderColor: '#fff', borderWidth: 1 }]
        },
        options: {
            responsive: true,
            plugins: { title: { display: true, text: 'Distribuição de Estatísticas (Fluxo Mensal: <?php echo formatBRL($fluxoMensalPagamentos ?: 0); ?>)' }, legend: { position: 'bottom' } }
        }
    });

    // Gráfico Linha
    new Chart(document.getElementById('graficoLinha').getContext('2d'), {
        type: 'line',
        data: {
            labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            datasets: [{
                label: 'Valor Faturas Pendentes (R$)',
                data: [<?php
                    $monthlyData = [];
                    for ($i = 1; $i <= 12; $i++) {
                        $value = executeQuery($pdo, "SELECT SUM(valor) FROM faturas WHERE status = ? AND MONTH(data_vencimento) = ? AND YEAR(data_vencimento) = YEAR(CURDATE())", ['pendente', $i]);
                        $monthlyData[] = $value ?: 0;
                    }
                    echo implode(',', $monthlyData);
                ?>],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { title: { display: true, text: 'Faturas Pendentes por Mês' }, legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, ticks: { callback: value => 'R$ ' + value.toFixed(2).replace('.', ',') } } }
        }
    });
});
</script>

</body>
</html>