<?php
require_once 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$mp_id = $input['data']['id'] ?? null;

if ($mp_id) {
    // Aqui você usaria a API do Mercado Pago para consultar o pagamento
    // Simulando que foi aprovado:
    $pdo->prepare("UPDATE pagamentos SET status = 'aprovado' WHERE mp_id = ?")->execute([$mp_id]);
}
