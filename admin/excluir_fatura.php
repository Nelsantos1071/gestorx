<?php
include_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fatura_id'])) {
    $fatura_id = (int) $_POST['fatura_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM faturas WHERE id = ?");
        $stmt->execute([$fatura_id]);

        header("Location: faturas.php");
        exit;
    } catch (PDOException $e) {
        die("Erro ao excluir fatura: " . $e->getMessage());
    }
} else {
    die("Requisição inválida.");
}
