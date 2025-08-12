<?php
session_start();
require_once '../includes/db.php';

// Verifica sessão de admin
if (!isset($_SESSION['admin_id']) || !is_numeric($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Verifica conexão com o banco
if (!isset($pdo)) {
    error_log("Erro: Conexão com o banco de dados não foi estabelecida. Verifique o arquivo db.php.");
    http_response_code(500);
    echo "<div class='alert alert-danger'>Erro interno do servidor. Tente novamente mais tarde.</div>";
    exit;
}

// Valida client_id
if (!isset($_GET['client_id']) || !is_numeric($_GET['client_id'])) {
    http_response_code(400);
    echo "<div class='alert alert-danger'>ID do cliente inválido.</div>";
    exit;
}

$client_id = intval($_GET['client_id']);

// CSRF para POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo "<div class='alert alert-danger'>Erro: Token CSRF inválido.</div>";
        exit;
    }
}

// Função para gerar código pix fictício
function generatePixString($valor, $client_id, $invoice_id) {
    return "pix_example_{$client_id}_{$invoice_id}_" . number_format($valor, 2, '.', '');
}

// Buscar dados do cliente e plano
try {
    $stmt = $pdo->prepare("
        SELECT c.id, c.nome, c.vencimento, c.plano_id, p.nome AS plano_nome, p.valor_1 
        FROM clientes c 
        LEFT JOIN planos p ON c.plano_id = p.id 
        WHERE c.id = ? AND c.ativo = 1
    ");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        http_response_code(404);
        echo "<div class='alert alert-danger'>Cliente não encontrado.</div>";
        exit;
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar cliente: " . $e->getMessage());
    http_response_code(500);
    echo "<div class='alert alert-danger'>Erro ao buscar cliente.</div>";
    exit;
}

// Processa renovação
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $duracao_dias = 30;

        $vencimento_atual = $client['vencimento'] ? new DateTime($client['vencimento']) : new DateTime();
        $vencimento_atual->modify("+{$duracao_dias} days");
        $novo_vencimento = $vencimento_atual->format('Y-m-d');

        // Atualiza vencimento
        $stmt = $pdo->prepare("UPDATE clientes SET vencimento = ? WHERE id = ?");
        $stmt->execute([$novo_vencimento, $client_id]);

        $plano_id = $client['plano_id'];
        $valor = $client['valor_1'] ?? 30.00;
        $data_emissao = date('Y-m-d');
        $pix_string = generatePixString($valor, $client_id, null);

        // Verifica se já existe fatura pendente para essa data
        $stmt = $pdo->prepare("
            SELECT id FROM faturas
            WHERE user_id = ? AND data_vencimento = ? AND status = 'pendente'
        ");
        $stmt->execute([$client_id, $novo_vencimento]);
        if ($stmt->fetch()) {
            throw new Exception("Já existe uma fatura pendente para este cliente e vencimento.");
        }

        // Cria nova fatura com status 'ativa'
        $stmt = $pdo->prepare("
            INSERT INTO faturas (user_id, plano_id, valor, status, data_emissao, data_vencimento, criado_em, pix_string)
            VALUES (?, ?, ?, 'ativa', ?, ?, NOW(), ?)
        ");
        $stmt->execute([$client_id, $plano_id, $valor, $data_emissao, $novo_vencimento, $pix_string]);

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header("Location: dashboard.php?mensagem=" . urlencode("Plano renovado com sucesso para {$client['nome']} até " . date('d/m/Y', strtotime($novo_vencimento)) . "."));
        exit;
    } catch (PDOException $e) {
        error_log("Erro ao renovar plano: " . $e->getMessage());
        $mensagem = "Erro ao renovar plano: " . htmlspecialchars($e->getMessage());
    } catch (Exception $e) {
        error_log("Erro geral ao renovar plano: " . $e->getMessage());
        $mensagem = "Erro geral ao renovar plano: " . htmlspecialchars($e->getMessage());
    }
}

// Gera CSRF token se não existir
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renovar Plano</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f9f9f9; font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .btn-confirm { background: #198754; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; width: 100%; }
        .btn-back { background: #6c757d; color: #fff; text-decoration: none; padding: 10px 20px; border-radius: 5px; display: block; margin-top: 10px; text-align: center; }
        .btn-confirm:hover { background: #157347; }
        .btn-back:hover { background: #5a6268; }
        .card-title { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="mb-4 text-center">Renovar Plano</h2>

        <?php if (isset($mensagem)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Detalhes do Cliente</h5>
                <p><strong>Nome:</strong> <?= htmlspecialchars($client['nome']) ?></p>
                <p><strong>Plano:</strong> <?= htmlspecialchars($client['plano_nome'] ?? 'Sem plano') ?></p>
                <p><strong>Valor:</strong> R$ <?= number_format($client['valor_1'] ?? 30.00, 2, ',', '.') ?></p>
                <p><strong>Vencimento Atual:</strong> <?= $client['vencimento'] ? date('d/m/Y', strtotime($client['vencimento'])) : 'Não definido' ?></p>
                <p><strong>Novo Vencimento:</strong> <?= date('d/m/Y', strtotime(($client['vencimento'] ?: date('Y-m-d')) . ' +30 days')) ?></p>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit" class="btn-confirm">Confirmar Renovação</button>
        </form>

        <a href="dashboard.php" class="btn-back">Voltar</a>
    </div>
</body>
</html>
