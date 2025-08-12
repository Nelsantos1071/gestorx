<?php
session_start();
require_once '../includes/db.php';

// Verifica se o cliente está logado
if (empty($_SESSION['cliente_id']) || !filter_var($_SESSION['cliente_id'], FILTER_VALIDATE_INT)) {
    header('Location: login.php');
    exit;
}

$clienteId = (int)$_SESSION['cliente_id'];

// Busca os templates do cliente
$stmt = $pdo->prepare("SELECT id, titulo, template_text, updated_at FROM template_users WHERE cliente_id = ? ORDER BY updated_at DESC");
$stmt->execute([$clienteId]);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Seus Templates</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 2rem; background-color: #f4f4f4; }
    h2 { text-align: center; color: #2c3e50; }
    table { width: 100%; border-collapse: collapse; background: white; margin-top: 2rem; }
    th, td { padding: 0.75rem; border: 1px solid #ccc; text-align: left; }
    th { background-color: #2c3e50; color: white; }
    tr:nth-child(even) { background-color: #f9f9f9; }
    .actions a { color: #2980b9; text-decoration: none; font-weight: bold; }
    .empty { text-align: center; margin-top: 2rem; color: #888; }
    .btn-voltar {
        display: inline-block;
        margin-top: 1rem;
        padding: 0.5rem 1rem;
        background-color: #2c3e50;
        color: white;
        text-decoration: none;
        border-radius: 5px;
    }
  </style>
</head>
<body>

  <h2>Modelos Salvos</h2>

  <?php if (count($templates) === 0): ?>
    <p class="empty">Nenhum modelo encontrado. <a href="editar_template.php">Clique aqui para criar</a>.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Título</th>
          <th>Texto (preview)</th>
          <th>Atualizado em</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($templates as $template): ?>
          <tr>
            <td><?= htmlspecialchars($template['titulo']) ?></td>
            <td><?= htmlspecialchars(mb_strimwidth($template['template_text'], 0, 50, "...")) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($template['updated_at'])) ?></td>
            <td class="actions">
              <a href="editar_template.php?id=<?= $template['id'] ?>">Editar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <a class="btn-voltar" href="dashboard.php">← Voltar ao painel</a>

</body>
</html>
