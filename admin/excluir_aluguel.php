<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_id']) || !is_numeric($_SESSION['admin_id']) || (int)$_SESSION['admin_id'] <= 0) {
    header("Location: admin_login.php?msg=" . urlencode("Por favor, faça login como administrador."));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_listar_alugueis.php?error=" . urlencode("Método inválido."));
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    error_log("Erro de validação CSRF para excluir aluguel_id={$_POST['aluguel_id']}");
    header("Location: admin_listar_alugueis.php?error=" . urlencode("Erro de validação do token de segurança."));
    exit;
}

$aluguel_id = isset($_POST['aluguel_id']) ? (int)$_POST['aluguel_id'] : 0;
if ($aluguel_id <= 0) {
    error_log("Aluguel_id inválido: $aluguel_id");
    header("Location: admin_listar_alugueis.php?error=" . urlencode("ID do aluguel inválido."));
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT cliente_id, produto_id FROM alugueis WHERE id = ?");
    $stmt->execute([$aluguel_id]);
    $aluguel = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$aluguel) {
        $pdo->rollBack();
        error_log("Aluguel não encontrado: id=$aluguel_id");
        header("Location: admin_listar_alugueis.php?error=" . urlencode("Aluguel não encontrado (ID: $aluguel_id)."));
        exit;
    }

    $cliente_id = $aluguel['cliente_id'];
    $produto_id = $aluguel['produto_id'];
    error_log("Exclusão iniciada: aluguel_id=$aluguel_id, cliente_id=$cliente_id, produto_id=$produto_id");

    $stmt = $pdo->prepare("DELETE FROM alugueis WHERE cliente_id = ?");
    $stmt->execute([$cliente_id]);
    $deleted_alugueis = $stmt->rowCount();
    error_log("Aluguéis deletados: cliente_id=$cliente_id, linhas=$deleted_alugueis");

    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
    $stmt->execute([$cliente_id]);
    $cliente_deleted = $stmt->rowCount();
    error_log("Cliente deletado: id=$cliente_id, linhas=$cliente_deleted");

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM alugueis WHERE produto_id = ?");
    $stmt->execute([$produto_id]);
    $count_alugueis_prod = $stmt->fetchColumn();
    error_log("Aluguéis para produto_id=$produto_id: $count_alugueis_prod");

    if ($count_alugueis_prod == 0) {
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->execute([$produto_id]);
        $produto_deleted = $stmt->rowCount();
        error_log("Produto deletado: id=$produto_id, linhas=$produto_deleted");
    }

    $pdo->commit();
    error_log("Exclusão concluída: aluguel_id=$aluguel_id");
    header("Location: admin_listar_alugueis.php?success=" . urlencode("Aluguéis, compras, cliente e produto (se não usado) excluídos com sucesso!"));
    exit;
} catch (PDOException $e) {
    $pdo->rollBack();
    $error = "Erro ao excluir: " . htmlspecialchars($e->getMessage());
    error_log("Erro PDO ao excluir aluguel_id=$aluguel_id: " . $e->getMessage());
    header("Location: admin_listar_alugueis.php?error=" . urlencode($error));
    exit;
}
?>