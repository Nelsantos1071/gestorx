<?php
session_start();
require_once '../includes/db.php';

// Verifica se o cliente está logado e se cliente_id é válido
if (!isset($_SESSION['cliente_id']) || !is_numeric($_SESSION['cliente_id'])) {
    header("Location: login.php?msg=" . urlencode("Por favor, faça login para acessar o dashboard."));
    exit;
}

$clienteId = (int)$_SESSION['cliente_id'];
$error = '';

// Verificar status ativo do cliente
try {
    $stmt = $pdo->prepare("SELECT ativo, nome FROM clientes WHERE id = ?");
    $stmt->execute([$clienteId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cliente || $cliente['ativo'] == 0) {
        $error = "Sua conta está inativa. Entre em contato com o suporte.";
    }
} catch (PDOException $e) {
    $error = "Erro ao verificar status da conta: " . htmlspecialchars($e->getMessage());
}

if (empty($error)) {
    // Buscar estatísticas de usuários
    try {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN ativo = 1 AND vencimento >= CURDATE() THEN 1 ELSE 0 END) as ativos,
                SUM(CASE WHEN ativo = 0 OR vencimento < CURDATE() THEN 1 ELSE 0 END) as vencidos,
                COUNT(*) as total
            FROM users
            WHERE created_by = ?
        ");
        $stmt->execute([$clienteId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['ativos' => 0, 'vencidos' => 0, 'total' => 0];

        // Adicionar log para depuração (pode ser removido em produção)
        error_log("Estatísticas: " . print_r($stats, true));

        // Usuários com faturas nos últimos 3 dias ou vencimento nos próximos 3 dias
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.nome_completo, u.vencimento, u.plano_id, p.nome as plano_nome
            FROM users u
            LEFT JOIN faturas f ON u.user_id = f.user_id
            LEFT JOIN planos p ON u.plano_id = p.id
            WHERE u.ativo = 1 AND u.created_by = ?
            AND (
                (f.data_emissao >= DATE_SUB(CURDATE(), INTERVAL 3 DAY) AND f.data_emissao < CURDATE())
                OR (u.vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY))
            )
        ");
        $stmt->execute([$clienteId]);
        $proximos_vencer = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stats['proximos_vencer_count'] = count($proximos_vencer);

        // Produtos ativos (soma de quantidade em alugueis)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(quantidade), 0) as produtos_ativos
            FROM alugueis
            WHERE cliente_id = ? AND status = 'ativo' AND (data_fim IS NULL OR data_fim > CURDATE())
        ");
        $stmt->execute([$clienteId]);
        $stats['produtos_ativos'] = $stmt->fetch(PDO::FETCH_ASSOC)['produtos_ativos'];

        // Lista de alugueis ativos
        $stmt = $pdo->prepare("
            SELECT id, produto_id, quantidade, data_aluguel
            FROM alugueis
            WHERE cliente_id = ? AND status = 'ativo' AND (data_fim IS NULL OR data_fim > CURDATE())
        ");
        $stmt->execute([$clienteId]);
        $alugueis_ativos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Erro ao buscar estatísticas: " . htmlspecialchars($e->getMessage());
    }

    // Buscar dados para o gráfico
    try {
        $labels = [];
        $chartData = [];
        $stmt = $pdo->prepare("
            SELECT 
                DATE(f.data_emissao) as data,
                COALESCE(SUM(f.valor), 0) as total_valor
            FROM users u
            LEFT JOIN faturas f ON u.user_id = f.user_id
            WHERE u.created_by = ?
            AND f.data_emissao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY DATE(f.data_emissao)
            ORDER BY data
        ");
        $stmt->execute([$clienteId]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $dias = [];
        for ($i = 29; $i >= 0; $i--) {
            $data = date('Y-m-d', strtotime("-{$i} days"));
            $dias[$data] = 0;
            $labels[] = date('d/m', strtotime("-{$i} days"));
        }
        foreach ($resultados as $row) {
            $dias[$row['data']] = (float)$row['total_valor'];
        }
        $chartData = array_values($dias);
    } catch (PDOException $e) {
        $error = "Erro ao buscar dados do gráfico: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard - <?php echo htmlspecialchars($cliente['nome'] ?? 'Cliente'); ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body, html {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f5f7fa;
      color: #333;
      height: 100vh;
      overflow-x: hidden;
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
      width: 35px;
      height: 30px;
      user-select: none;
    }
    .menu-icon div {
      width: 35px;
      height: 4px;
      background-color: #000;
      margin: 6px 0;
      border-radius: 3px;
      transition: 0.4s;
    }
    .menu-icon.open div {
      background-color: #f0a500;
    }
    .menu-icon.open div:nth-child(1) {
      transform: rotate(45deg) translate(6px, 6px);
    }
    .menu-icon.open div:nth-child(2) {
      opacity: 0;
    }
    .menu-icon.open div:nth-child(3) {
      transform: rotate(-45deg) translate(7px, -7px);
    }
    main {
      margin-left: 220px;
      padding: 20px 30px 40px;
      min-height: 100vh;
      background: #fff;
      box-shadow: 0 0 12px rgb(0 0 0 / 0.1);
      display: flex;
      flex-direction: column;
      gap: 25px;
      transition: margin-left 0.3s ease-in-out;
    }
    main .welcome {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      color: #293a5e;
      font-weight: 700;
      font-size: 1.9rem;
      user-select: none;
      margin-top: -20px;
    }
    main .welcome p {
      font-weight: 400;
      font-size: 1.1rem;
      margin-top: 8px;
      color: #666;
    }
    .stats-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      max-width: 1000px;
      margin: 0 auto;
      animation: fadeIn 0.5s ease-out;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .stats-card {
      background: linear-gradient(135deg, #ffffff, #f9f9f9);
      border-radius: 12px;
      padding: 25px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      position: relative;
      overflow: hidden;
    }
    .stats-card:hover {
      transform: scale(1.05);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }
    .stats-card i {
      font-size: 2.5rem;
      margin-bottom: 15px;
      opacity: 0.9;
    }
    .stats-card h3 {
      font-size: 1.2rem;
      margin-bottom: 10px;
      font-weight: 700;
      color: #293a5e;
    }
    .stats-card p {
      font-size: 2rem;
      font-weight: 800;
      color: #333;
    }
    .stats-card .details-btn {
      margin-top: 10px;
      padding: 8px 16px;
      background-color: #293a5e;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 0.9rem;
      transition: background-color 0.3s ease;
    }
    .stats-card .details-btn:hover {
      background-color: #1f2a44;
    }
    .stats-card.active {
      border-left: 6px solid #28a745;
      background: linear-gradient(135deg, #e8f5e9, #ffffff);
    }
    .stats-card.active i { color: #28a745; }
    .stats-card.expired {
      border-left: 6px solid #dc3545;
      background: linear-gradient(135deg, #f8e1e4, #ffffff);
    }
    .stats-card.expired i { color: #dc3545; }
    .stats-card.next-to-expire {
      border-left: 6px solid #ffc107;
      background: linear-gradient(135deg, #fff3cd, #ffffff);
    }
    .stats-card.next-to-expire i { color: #ffc107; }
    .stats-card.total {
      border-left: 6px solid #007bff;
      background: linear-gradient(135deg, #e3f2fd, #ffffff);
    }
    .stats-card.total i { color: #007bff; }
    .stats-card.products {
      border-left: 6px solid #17a2b8;
      background: linear-gradient(135deg, #e6f7fa, #ffffff);
    }
    .stats-card.products i { color: #17a2b8; }
    .stats-card[data-tooltip]:hover:after {
      content: attr(data-tooltip);
      position: absolute;
      bottom: 100%;
      left: 50%;
      transform: translateX(-50%);
      background: #293a5e;
      color: white;
      padding: 8px 12px;
      border-radius: 5px;
      font-size: 0.85rem;
      white-space: nowrap;
      z-index: 10;
      opacity: 0.95;
    }
    .proximos-vencer-list, .alugueis-list {
      max-width: 1000px;
      margin: 0 auto;
    }
    .proximos-vencer-list h3, .alugueis-list h3 {
      margin-bottom: 15px;
      color: #293a5e;
      text-align: center;
      font-weight: 700;
      font-size: 1.4rem;
    }
    .proximos-vencer-item, .alugueis-item {
      background: linear-gradient(135deg, #ffffff, #f9f9f9);
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: transform 0.3s ease;
    }
    .proximos-vencer-item:hover, .alugueis-item:hover {
      transform: translateY(-3px);
    }
    .proximos-vencer-item p, .alugueis-item p {
      margin: 0;
      font-size: 0.95rem;
      color: #333;
    }
    .proximos-vencer-item button {
      padding: 8px 12px;
      margin-left: 10px;
      cursor: pointer;
      background-color: #28a745;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 0.9rem;
      transition: background-color 0.3s ease;
    }
    .proximos-vencer-item button:hover {
      background-color: #218838;
    }
    .charts-section {
      max-width: 1000px;
      margin: 0 auto;
    }
    .charts-section h2 {
      margin-bottom: 10px;
      color: #293a5e;
      text-align: center;
      font-weight: 700;
      font-size: 1.5rem;
    }
    .chart-container {
      background: linear-gradient(135deg, #ffffff, #f9f9f9);
      border-radius: 12px;
      padding: 15px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
      transition: box-shadow 0.3s ease;
      max-width: 100%;
      height: 300px;
    }
    .chart-container:hover {
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
    }
    .chart-container h3 {
      font-size: 1.2rem;
      margin-bottom: 10px;
      color: #293a5e;
      text-align: center;
      font-weight: 600;
    }
    .error-message {
      color: #d93025;
      text-align: center;
      font-weight: 600;
      margin-bottom: 20px;
      padding: 10px;
      background: #f8d7da;
      border-radius: 8px;
    }
    @media (max-width: 768px) {
      main {
        margin-left: 0 !important;
        padding: 80px 15px 40px;
      }
      .menu-icon {
        display: block;
      }
      .stats-cards {
        grid-template-columns: 1fr;
        gap: 15px;
        max-width: 100%;
        margin: 0;
      }
      .stats-card {
        padding: 20px;
        width: 100%;
      }
      .stats-card i {
        font-size: 2rem;
      }
      .stats-card h3 {
        font-size: 1.1rem;
      }
      .stats-card p {
        font-size: 1.8rem;
      }
      .stats-card .details-btn {
        font-size: 0.85rem;
        padding: 6px 12px;
      }
      .proximos-vencer-item, .alugueis-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      .proximos-vencer-item button {
        width: auto;
        margin-left: 10px;
        padding: 8px 12px;
      }
      .charts-section h2 {
        font-size: 1.3rem;
      }
      .chart-container {
        padding: 10px;
        height: 250px;
      }
      .chart-container h3 {
        font-size: 1rem;
      }
    }
    @media (max-width: 480px) {
      main {
        padding: 80px 10px 40px;
      }
      .stats-cards {
        grid-template-columns: 1fr;
        gap: 15px;
        max-width: 100%;
        margin: 0;
      }
      .stats-card {
        padding: 15px;
        width: 100%;
      }
      .stats-card i {
        font-size: 1.8rem;
      }
      .stats-card h3 {
        font-size: 1rem;
      }
      .stats-card p {
        font-size: 1.6rem;
      }
      .stats-card .details-btn {
        font-size: 0.8rem;
        padding: 5px 10px;
      }
      .proximos-vencer-item, .alugueis-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      .proximos-vencer-item button {
        width: auto;
        margin-left: 5px;
        padding: 6px 10px;
        font-size: 0.85rem;
      }
      .proximos-vencer-item p, .alugueis-item p {
        font-size: 0.85rem;
      }
      .charts-section h2 {
        font-size: 1.2rem;
      }
      .chart-container {
        height: 200px;
      }
      .chart-container h3 {
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/assets/sidebar.php'; ?>

  <main role="main" tabindex="-1">
    <section class="welcome" aria-label="Mensagem de boas vindas">
      <h1>Bem-vindo ao Dashboard</h1>
      <p>Este é o painel principal do cliente.</p>
    </section>

    <?php if (!empty($error)): ?>
      <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php else: ?>
      <section class="stats-cards" aria-label="Estatísticas de clientes">
        <div class="stats-card active" data-tooltip="Total de clientes ativos no sistema">
          <i class="fa fa-check-circle"></i>
          <h3>Clientes Ativos</h3>
          <p><?php echo htmlspecialchars($stats['ativos']); ?></p>
          <button class="details-btn" onclick="window.location.href='users.php'">Ver Detalhes</button>
        </div>
        <div class="stats-card expired" data-tooltip="Clientes com vencimento vencido">
          <i class="fa fa-times-circle"></i>
          <h3>Clientes Vencidos</h3>
          <p><?php echo htmlspecialchars($stats['vencidos']); ?></p>
          <button class="details-btn" onclick="window.location.href='users.php'">Ver Detalhes</button>
        </div>
        <div class="stats-card next-to-expire" data-tooltip="Clientes com vencimento nos últimos 3 dias ou nos próximos 3 dias">
          <i class="fa fa-clock"></i>
          <h3>Próximos a Vencer</h3>
          <p><?php echo htmlspecialchars($stats['proximos_vencer_count']); ?></p>
          <button class="details-btn" onclick="window.location.href='users.php'">Ver Detalhes</button>
        </div>
        <div class="stats-card total" data-tooltip="Total de clientes associados à sua conta">
          <i class="fa fa-users"></i>
          <h3>Total de Clientes</h3>
          <p><?php echo htmlspecialchars($stats['total']); ?></p>
          <button class="details-btn" onclick="window.location.href='users.php'">Ver Detalhes</button>
        </div>
        <div class="stats-card products" data-tooltip="Total de produtos atualmente locados">
          <i class="fa fa-box"></i>
          <h3>Produtos Ativos</h3>
          <p><?php echo htmlspecialchars($stats['produtos_ativos']); ?></p>
          <button class="details-btn" onclick="window.location.href='alugueis.php'">Ver Detalhes</button>
        </div>
      </section>

      <?php if (!empty($proximos_vencer)): ?>
        <section class="proximos-vencer-list" aria-label="Lista de próximos a vencer">
          <h3>Próximos a Vencer (Últ. 3 Dias a +3 Dias)</h3>
          <?php foreach ($proximos_vencer as $item): ?>
            <div class="proximos-vencer-item" data-user-id="<?php echo htmlspecialchars($item['user_id']); ?>" data-plano-id="<?php echo htmlspecialchars($item['plano_id'] ?? 0); ?>">
              <p><?php echo htmlspecialchars($item['nome_completo']) . ' - Vencimento: ' . date('d/m/Y', strtotime($item['vencimento'])); ?></p>
              <div>
                <button class="renew-btn"
                        data-id="<?php echo htmlspecialchars($item['user_id']); ?>"
                        data-plano-id="<?php echo htmlspecialchars($item['plano_id'] ?? 0); ?>"
                        data-plano-nome="<?php echo htmlspecialchars($item['plano_nome'] ?? 'Sem Plano'); ?>"
                        data-bs-toggle="modal"
                        data-bs-target="#modalRenovar">Renovar</button>
              </div>
            </div>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <?php if (!empty($alugueis_ativos)): ?>
        <section class="alugueis-list" aria-label="Lista de alugueis ativos">
          <h3>Alugueis Ativos</h3>
          <?php foreach ($alugueis_ativos as $aluguel): ?>
            <div class="alugueis-item" data-aluguel-id="<?php echo htmlspecialchars($aluguel['id']); ?>">
              <p>Produto ID: <?php echo htmlspecialchars($aluguel['produto_id']); ?> - Quantidade: <?php echo htmlspecialchars($aluguel['quantidade']); ?> - Data Aluguel: <?php echo date('d/m/Y H:i', strtotime($aluguel['data_aluguel'])); ?></p>
            </div>
          <?php endforeach; ?>
        </section>
      <?php endif; ?>

      <section class="charts-section" aria-label="Gráfico de valor diário">
        <h2>Valor Diário (Últimos 30 Dias, R$)</h2>
        <div class="chart-container">
          <h3>Valor Diário</h3>
          <canvas id="valorMensalChart"></canvas>
        </div>
      </section>

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
                <input type="hidden" id="renewPlanoId">
                <div class="form-group mb-3">
                  <label class="form-label">Plano Atual</label>
                  <p id="planoNome" class="form-control-static"></p>
                </div>
                <div class="form-group mb-3">
                  <p>Deseja renovar o plano por 1 mês?</p>
                </div>
                <div class="d-flex justify-content-between">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="submit" class="btn btn-success">Confirmar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <script>
    <?php if (empty($error)): ?>
      const ctx = document.getElementById('valorMensalChart').getContext('2d');
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: <?php echo json_encode($labels); ?>,
          datasets: [{
            label: 'Valor Diário (R$)',
            data: <?php echo json_encode($chartData); ?>,
            backgroundColor: 'rgba(40, 167, 69, 0.7)',
            borderColor: '#28a745',
            borderWidth: 2,
            borderRadius: 5,
            hoverBackgroundColor: 'rgba(40, 167, 69, 0.9)'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              grid: { color: 'rgba(0, 0, 0, 0.1)', drawBorder: false },
              title: {
                display: true,
                text: 'Valor (R$)',
                font: { size: 14, weight: '600' },
                color: '#293a5e'
              },
              ticks: { color: '#333', font: { size: 12 } }
            },
            x: {
              grid: { display: false },
              title: {
                display: true,
                text: 'Dia',
                font: { size: 14, weight: '600' },
                color: '#293a5e'
              },
              ticks: {
                color: '#333',
                font: { size: 12 },
                maxRotation: 45,
                minRotation: 45,
                callback: function(value, index) {
                  return index % 3 === 0 ? this.getLabelForValue(value) : '';
                }
              }
            }
          },
          plugins: {
            legend: {
              display: true,
              position: 'top',
              labels: { font: { size: 12, weight: '600' }, color: '#293a5e' }
            },
            tooltip: {
              backgroundColor: '#293a5e',
              titleFont: { size: 14, weight: '600' },
              bodyFont: { size: 12 },
              padding: 10,
              cornerRadius: 4
            }
          },
          animation: { duration: 1000, easing: 'easeOutQuart' }
        }
      });
    <?php endif; ?>

    function gerarFatura(userId, planoId) {
      if (confirm('Deseja gerar uma fatura para este cliente?')) {
        $.ajax({
          url: 'gerar_fatura.php',
          type: 'POST',
          data: { user_id: userId, plano_id: planoId },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              alert('Fatura gerada com sucesso!');
              location.reload();
            } else {
              alert('Erro: ' + (response.message || 'Falha ao gerar fatura.'));
            }
          },
          error: function() {
            alert('Erro ao conectar com o servidor.');
          }
        });
      }
    }

    function renovar(userId, planoId) {
      if (confirm('Deseja renovar o plano para este cliente?')) {
        $.ajax({
          url: 'renovar_plano.php',
          type: 'POST',
          data: { user_id: userId, plano_id: planoId },
          dataType: 'json',
          success: function(response) {
            if (response.success) {
              alert('Plano renovado com sucesso!');
              location.reload();
            } else {
              alert('Erro: ' + (response.message || 'Falha ao renovar plano.'));
            }
          },
          error: function() {
            alert('Erro ao conectar com o servidor.');
          }
        });
      }
    }

    $(document).ready(function() {
      $('#modalRenovar').on('show.bs.modal', function(e) {
        const button = $(e.relatedTarget);
        const userId = button.data('id');
        const planoId = button.data('plano-id');
        const planoNome = button.data('plano-nome');

        $('#renewUserId').val(userId);
        $('#renewPlanoId').val(planoId);
        $('#planoNome').text(planoNome);

        if (!planoId || planoId == 0) {
          $('#planoNome').text('Sem Plano');
          $('#renewForm .btn-success').prop('disabled', true);
        } else {
          $('#renewForm .btn-success').prop('disabled', false);
        }
      });

      $('#renewForm').on('submit', function(e) {
        e.preventDefault();
        const userId = $('#renewUserId').val();
        const planoId = $('#renewPlanoId').val();
        const periodo = 1; // Fixed to 1 month
        if (planoId && userId && planoId != 0) {
          const href = `renovar.php?user_id=${encodeURIComponent(userId)}&plano_id=${encodeURIComponent(planoId)}&periodo=${encodeURIComponent(periodo)}`;
          window.location.href = href;
        } else {
          alert('Não é possível renovar: nenhum plano válido selecionado.');
        }
      });
    });
  </script>
</body>
</html>