<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['cliente_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit;
}

$clienteId = $_SESSION['cliente_id'];
$aluguelId = filter_input(INPUT_POST, 'aluguel_id', FILTER_VALIDATE_INT);

if (!$aluguelId) {
    die("ID do aluguel inválido.");
}

// Verificar se o aluguel pertence ao cliente
$stmt = $pdo->prepare("SELECT id FROM alugueis WHERE id = ? AND cliente_id = ?");
$stmt->execute([$aluguelId, $clienteId]);
if (!$stmt->fetch()) {
    die("Você não tem permissão para enviar comprovante para este aluguel.");
}

// Validar arquivo
if (!isset($_FILES['comprovante']) || $_FILES['comprovante']['error'] !== UPLOAD_ERR_OK) {
    die("Erro no upload do arquivo.");
}

$arquivo = $_FILES['comprovante'];
$tiposPermitidos = ['image/jpeg', 'image/png', 'application/pdf'];

if (!in_array($arquivo['type'], $tiposPermitidos)) {
    die("Tipo de arquivo não permitido. Envie JPG, PNG ou PDF.");
}

$ext = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
$novoNome = uniqid('comp_') . '.' . $ext;
$pastaUploads = __DIR__ . '/../uploads/comprovantes/';

if (!is_dir($pastaUploads)) {
    mkdir($pastaUploads, 0755, true);
}

$destino = $pastaUploads . $novoNome;

if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
    die("Falha ao salvar o arquivo.");
}

// Inserir no banco (permite mais de um comprovante por aluguel)
$stmt = $pdo->prepare("INSERT INTO comprovantes_pagamento (aluguel_id, arquivo) VALUES (?, ?)");
$stmt->execute([$aluguelId, $novoNome]);

header("Location: dashboard.php?upload=success");
exit;
