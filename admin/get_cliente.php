<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) || !is_int((int)$_SESSION['admin_id']) || (int)$_SESSION['admin_id'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

if (!isset($_GET['cliente_id']) || !is_numeric($_GET['cliente_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID do cliente inválido.']);
    exit;
}

$cliente_id = (int)$_GET['cliente_id'];

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("
        SELECT id, nome, email,
               COALESCE(telefone, '') as telefone
        FROM clientes
        WHERE id = ?
    ");
    $stmt->execute([$cliente_id]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        echo json_encode(['success' => true, 'cliente' => $cliente]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cliente não encontrado.']);
    }
} catch (PDOException $e) {
    error_log("Erro ao buscar cliente: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar cliente: ' . $e->getMessage()]);
}
?>