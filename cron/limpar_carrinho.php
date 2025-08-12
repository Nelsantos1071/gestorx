<?php
include_once '../includes/config.php';

$stmt = $conn->prepare("DELETE FROM carrinho_temp WHERE criado_em < (NOW() - INTERVAL 3 HOUR)");
$stmt->execute();
