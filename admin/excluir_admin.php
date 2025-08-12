<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$id = (int) $_GET['id'];

if ($_SESSION['admin_id'] == $id) {
    die("Você não pode excluir a si mesmo.");
}

$stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
if ($stmt->execute([$id])) {
    header("Location: listar_admins.php");
} else {
    echo "Erro ao excluir administrador.";
}
?>
