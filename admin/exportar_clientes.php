<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db.php'; // Já contém o $pdo configurado

// Cabeçalhos para forçar download do arquivo CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=clientes_export.csv');
header('Pragma: no-cache');
header('Expires: 0');

// Abre o "arquivo" de saída (php://output)
$output = fopen('php://output', 'w');

// Escreve o cabeçalho do CSV
fputcsv($output, ['ID', 'Nome', 'Email', 'Ativo', 'Criado Em']);

// Consulta os clientes
$stmt = $pdo->query("SELECT id, nome, email, ativo, criado_em FROM clientes ORDER BY id ASC");

// Escreve os dados linha por linha
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ativo = $row['ativo'] ? 'Sim' : 'Não';
    fputcsv($output, [
        $row['id'],
        $row['nome'],
        $row['email'],
        $ativo,
        $row['criado_em']
    ]);
}

fclose($output);
exit;
