<?php
session_start();
require_once '../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

if (empty($_SESSION['cliente_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
    exit;
}

$clienteId = (int)$_SESSION['cliente_id'];
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit;
}

// Verifica se o template pertence ao cliente
$stmt = $pdo->prepare("SELECT id FROM template_users WHERE id = ? AND cliente_id = ?");
$stmt->execute([$id, $clienteId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Template não encontrado.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM template_users WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$id, $clienteId]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao excluir no banco.']);
}
