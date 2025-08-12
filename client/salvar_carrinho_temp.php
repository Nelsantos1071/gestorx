<?php
include_once '../includes/config.php';

if (isset($_GET['produto_id'])) {
    $produto_id = intval($_GET['produto_id']);

    $stmt = $conn->prepare("INSERT INTO carrinho_temp (produto_id) VALUES (:produto_id)");
    $stmt->bindValue(':produto_id', $produto_id);
    $stmt->execute();
}
