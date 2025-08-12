<?php
session_start();
header('Content-Type: application/json');

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF inválido.']);
    exit;
}

if (
    empty($_POST['cliente_id']) ||
    empty($_POST['titulo']) ||
    empty($_POST['template_text'])
) {
    echo json_encode(['success' => false, 'message' => 'Preencha todos os campos.']);
    exit;
}

$cliente_id = intval($_POST['cliente_id']);
$titulo = trim($_POST['titulo']);
$template = trim($_POST['template_text']);

require_once __DIR__ . '/../includes/db.php';

try {
    $stmt = $pdo->prepare("INSERT INTO template_users (cliente_id, titulo, template_text) VALUES (?, ?, ?)");
    $stmt->execute([$cliente_id, $titulo, $template]);

    echo json_encode(['success' => true, 'message' => 'Template salvo com sucesso.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
