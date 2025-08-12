<?php
require_once '../includes/db.php';
require_once '../includes/email.php';

$hoje = date('Y-m-d');
$proximos_dias = date('Y-m-d', strtotime('+3 days'));

$stmt = $pdo->prepare("SELECT d.*, c.nome, c.email FROM dominios d 
    JOIN clientes c ON d.cliente_id = c.id 
    WHERE d.vencimento BETWEEN ? AND ?");
$stmt->execute([$hoje, $proximos_dias]);

while ($row = $stmt->fetch()) {
    enviarNotificacaoRenovacao(
        $row['email'],
        $row['nome'],
        $row['url'],
        date('d/m/Y', strtotime($row['vencimento']))
    );
}
