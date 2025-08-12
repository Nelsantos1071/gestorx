<?php
require_once '../includes/db.php';

// Função para gerar pix_string (placeholder, substitua com sua lógica)
function generatePixString($valor, $client_id, $invoice_id) {
    return "pix_example_{$client_id}_{$invoice_id}_" . number_format($valor, 2, '.', '');
}

try {
    // Busca clientes com vencimento daqui a 3 dias
    $stmt = $pdo->prepare("
        SELECT c.id, c.nome, c.plano_id, c.vencimento, c.telefone, p.valor_1 AS valor
        FROM clientes c
        JOIN planos p ON c.plano_id = p.id
        WHERE DATE(c.vencimento) = CURDATE() + INTERVAL 3 DAY
        AND c.ativo = 1
        AND c.plano_id IS NOT NULL
        AND c.telefone IS NOT NULL
    ");
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($clientes as $cliente) {
        $client_id = $cliente['id'];
        $plano_id = $cliente['plano_id'];
        $valor = $cliente['valor'] ?? 30.00; // Default from existing fatura
        $data_emissao = date('Y-m-d');
        $data_vencimento = $cliente['vencimento'];
        $pix_string = generatePixString($valor, $client_id, null);

        // Verifica se já existe fatura pendente
        $stmt = $pdo->prepare("
            SELECT id FROM faturas
            WHERE user_id = ? AND data_vencimento = ? AND status = 'pendente'
        ");
        $stmt->execute([$client_id, $data_vencimento]);
        if ($stmt->fetch()) {
            error_log("Fatura pendente já existe para cliente ID {$client_id}, vencimento {$data_vencimento}");
            continue;
        }

        // Insere nova fatura
        $stmt = $pdo->prepare("
            INSERT INTO faturas (user_id, plano_id, valor, status, data_emissao, data_vencimento, criado_em, pix_string)
            VALUES (?, ?, ?, 'pendente', ?, ?, NOW(), ?)
        ");
        $stmt->execute([$client_id, $plano_id, $valor, $data_emissao, $data_vencimento, $pix_string]);

        error_log("Fatura gerada para cliente ID {$client_id}, vencimento {$data_vencimento}");
    }

} catch (PDOException $e) {
    error_log("Erro ao gerar faturas em massa: " . $e->getMessage());
}

echo "Processo de geração de faturas em massa concluído.";
?>