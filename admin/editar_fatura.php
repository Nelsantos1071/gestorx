<?php
include_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fatura_id'])) {
    $fatura_id = (int) $_POST['fatura_id'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM faturas WHERE id = ?");
        $stmt->execute([$fatura_id]);
        $fatura = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fatura) {
            die("Fatura não encontrada.");
        }
    } catch (PDOException $e) {
        die("Erro ao buscar fatura: " . $e->getMessage());
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && isset($_POST['vencimento'])) {
    $fatura_id = (int) $_POST['fatura_id'];
    $novo_status = $_POST['status'];
    $nova_data = $_POST['vencimento'];

    try {
        $stmt = $pdo->prepare("UPDATE faturas SET status = ?, data_vencimento = ? WHERE id = ?");
        $stmt->execute([$novo_status, $nova_data, $fatura_id]);
        header("Location: faturas.php");
        exit;
    } catch (PDOException $e) {
        die("Erro ao atualizar fatura: " . $e->getMessage());
    }
} else {
    die("Requisição inválida.");
}
?>

<?php if (isset($fatura)): ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Editar Fatura</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f4f4f4; }
        form { background: #fff; padding: 20px; border-radius: 8px; max-width: 400px; margin: auto; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; }
        button { background: #007bff; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h2>Editar Fatura #<?= htmlspecialchars($fatura['id']) ?></h2>
    <form method="POST">
        <input type="hidden" name="fatura_id" value="<?= $fatura['id'] ?>">
        <label>Status:</label>
        <select name="status" required>
            <option value="pendente" <?= $fatura['status'] == 'pendente' ? 'selected' : '' ?>>Pendente</option>
            <option value="pago" <?= $fatura['status'] == 'pago' ? 'selected' : '' ?>>Pago</option>
            <option value="cancelado" <?= $fatura['status'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
        </select>

        <label>Data de Vencimento:</label>
        <input type="date" name="vencimento" value="<?= $fatura['data_vencimento'] ?>" required>

        <button type="submit">Salvar Alterações</button>
    </form>
</body>
</html>
<?php endif; ?>
