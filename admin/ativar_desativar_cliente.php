<?php
session_start();
require_once '../includes/db.php';

// Verifica se o administrador está logado
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Verifica se os dados foram enviados corretamente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cliente_id'], $_POST['csrf_token'])) {
    $cliente_id = intval($_POST['cliente_id']);
    $csrf_token = $_POST['csrf_token'];

    // Verificação CSRF
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        die("Token CSRF inválido.");
    }

    // Buscar status atual
    $stmt = $pdo->prepare("SELECT ativo FROM clientes WHERE id = :id");
    $stmt->execute(['id' => $cliente_id]);
    $cliente = $stmt->fetch();

    if ($cliente) {
        $novo_status = $cliente['ativo'] ? 0 : 1;

        $update = $pdo->prepare("UPDATE clientes SET ativo = :novo_status WHERE id = :id");
        $update->execute([
            'novo_status' => $novo_status,
            'id' => $cliente_id
        ]);
    }
}

// Redireciona de volta
header("Location: ver_usuarios.php");
exit;
