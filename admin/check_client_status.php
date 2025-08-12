<?php
session_start();
require_once '../includes/db.php';
header('Content-Type: application/json');

$clienteId = isset($_GET['cliente_id']) && is_numeric($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 
             (isset($_SESSION['cliente_id']) && is_numeric($_SESSION['cliente_id']) ? (int)$_SESSION['cliente_id'] : 0);

if (!$clienteId) {
    echo json_encode(['ativo' => 0, 'error' => 'Nenhum cliente especificado.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT ativo FROM clientes WHERE id = ?");
    $stmt->execute([$clienteId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['ativo' => $cliente['ativo'] ?? 0]);
} catch (PDOException $e) {
    echo json_encode(['ativo' => 0, 'error' => 'Erro ao verificar status: ' . htmlspecialchars($e->getMessage())]);
}
?>