<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db.php';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Erro na conexão com o banco']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $executou = $stmt->execute([$id]);
        if ($executou) {
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

http_response_code(400);
echo json_encode(['error' => 'ID inválido ou método não permitido']);
exit;
