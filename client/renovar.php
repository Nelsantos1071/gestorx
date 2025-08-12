<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['cliente_id']) || !isset($_GET['user_id']) || !isset($_GET['plano_id']) || !isset($_GET['periodo'])) {
    $error = 'Dados inválidos.';
    error_log("Renovar.php: Missing required GET parameters - user_id: " . ($_GET['user_id'] ?? 'unset') . ", plano_id: " . ($_GET['plano_id'] ?? 'unset') . ", periodo: " . ($_GET['periodo'] ?? 'unset'));
}

$clienteId = (int)$_SESSION['cliente_id'];
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$planoId = isset($_GET['plano_id']) ? (int)$_GET['plano_id'] : 0;
$periodo = isset($_GET['periodo']) ? (int)$_GET['periodo'] : 0;
$duracaoGet = isset($_GET['duracao']) ? (int)$_GET['duracao'] : null;

if (!isset($error) && ($periodo < 1 || $periodo > 4)) {
    $error = 'Período inválido.';
    error_log("Renovar.php: Invalid periodo - $periodo");
}

$success = false;
$message = '';
$novoVencimentoDisplay = '';

if (!isset($error)) {
    try {
        // Verificar se o usuário pertence ao cliente e obter vencimento atual
        $stmt = $pdo->prepare("SELECT created_by, vencimento FROM users WHERE user_id = ? AND created_by = ? AND ativo = 1");
        $stmt->execute([$userId, $clienteId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            $error = 'Usuário não autorizado.';
            error_log("Renovar.php: Unauthorized user - user_id: $userId, cliente_id: $clienteId");
        }

        if (!isset($error)) {
            // Obter valor do plano
            $stmt = $pdo->prepare("SELECT valor_$periodo AS valor FROM planos WHERE id = ? AND (cliente_id = ? OR cliente_id IS NULL)");
            $stmt->execute([$planoId, $clienteId]);
            $plano = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plano || !$plano['valor']) {
                $error = 'Plano ou período não encontrado.';
                error_log("Renovar.php: Plan not found or invalid value - plano_id: $planoId, periodo: $periodo");
            }
        }

        if (!isset($error)) {
            $valor = (float)$plano['valor'];

            // Determinar duração
            $defaultDurations = [
                1 => 30,  // 1 month
                2 => 60,  // 2 months
                3 => 90,  // 3 months
                4 => 120  // 4 months
            ];
            $duracao = $duracaoGet ?? $defaultDurations[$periodo];
            if (!$duracao) {
                $error = 'Duração inválida.';
                error_log("Renovar.php: Invalid duration - duracaoGet: " . ($duracaoGet ?? 'null') . ", periodo: $periodo");
            }
        }

        if (!isset($error)) {
            // Calcular novo vencimento
            $currentVencimento = $user['vencimento'];
            $baseDate = date('Y-m-d');
            if ($currentVencimento && strtotime($currentVencimento) > strtotime($baseDate)) {
                // Se vencimento atual é futuro, adicionar duração a ele
                $novoVencimento = date('Y-m-d', strtotime("+{$duracao} days", strtotime($currentVencimento)));
            } else {
                // Se vencimento é nulo ou passado, usar data atual + duração
                $novoVencimento = date('Y-m-d', strtotime("+{$duracao} days"));
            }
            $novoVencimentoDisplay = date('d/m/Y', strtotime($novoVencimento));

            // Atualizar vencimento e plano_id
            $stmt = $pdo->prepare("UPDATE users SET vencimento = ?, plano_id = ? WHERE user_id = ? AND created_by = ?");
            $stmt->execute([$novoVencimento, $planoId, $userId, $clienteId]);

            // Opcional: Registrar a renovação em uma tabela de histórico
            /*
            $stmt = $pdo->prepare("INSERT INTO renovacoes (user_id, plano_id, periodo, valor, data_renovacao, cliente_id) 
                                   VALUES (?, ?, ?, ?, NOW(), ?)");
            $stmt->execute([$userId, $planoId, $periodo, $valor, $clienteId]);
            */

            $success = true;
            $message = "Renovação com sucesso. Nova data de vencimento: $novoVencimentoDisplay";
        }
    } catch (PDOException $e) {
        $error = 'Erro: ' . $e->getMessage();
        error_log("Renovar.php: PDO error - " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Renovação de Plano</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f1f1f1;
            font-family: Arial, sans-serif;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1055;
        }
        .toast {
            min-width: 300px;
        }
    </style>
</head>
<body>
    <div class="toast-container">
        <div class="toast <?php echo $success ? 'bg-success' : 'bg-danger'; ?> text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto"><?php echo $success ? 'Sucesso' : 'Erro'; ?></strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                <?php echo htmlspecialchars($success ? $message : ($error ?? 'Erro desconhecido')); ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toastEl = document.querySelector('.toast');
            const toast = new bootstrap.Toast(toastEl, {
                autohide: <?php echo $success ? 'true' : 'false'; ?>,
                delay: 3000
            });
            toast.show();

            <?php if ($success): ?>
                setTimeout(() => {
                    window.location.href = 'users.php';
                }, 3000);
            <?php endif; ?>
        });
    </script>
</body>
</html>