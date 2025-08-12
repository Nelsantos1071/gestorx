<?php
session_start();
require_once '../includes/db.php';

// Verifica se está autenticado
if (empty($_SESSION['cliente_id'])) {
    header('Location: login.php');
    exit;
}

$clienteId = (int)$_SESSION['cliente_id'];
$csrf_token = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

$id = (int)($_GET['id'] ?? 0);
$error = '';
$success = false;

// Se enviou formulário para salvar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Token CSRF inválido.';
    } else {
        $id = (int)($_POST['id'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $template_text = trim($_POST['template_text'] ?? '');

        if ($id <= 0 || $titulo === '' || $template_text === '') {
            $error = 'Preencha todos os campos.';
        } else {
            // Verifica se o template pertence ao cliente
            $stmt = $pdo->prepare("SELECT id FROM template_users WHERE id = ? AND cliente_id = ?");
            $stmt->execute([$id, $clienteId]);
            if (!$stmt->fetch()) {
                $error = 'Template não encontrado.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE template_users SET titulo = ?, template_text = ?, updated_at = NOW() WHERE id = ? AND cliente_id = ?");
                    $stmt->execute([$titulo, $template_text, $id, $clienteId]);
                    $success = true;
                    // Redireciona após salvar
                    header('Location: template.php');
                    exit;
                } catch (PDOException $e) {
                    $error = 'Erro ao atualizar no banco.';
                }
            }
        }
    }
}

// Se não enviou POST, carrega dados do template para preencher o formulário
if (!$success && $id > 0) {
    $stmt = $pdo->prepare("SELECT titulo, template_text FROM template_users WHERE id = ? AND cliente_id = ?");
    $stmt->execute([$id, $clienteId]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$template) {
        die('Template não encontrado ou acesso negado.');
    }
} else {
    $template = ['titulo' => '', 'template_text' => ''];
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<title>Editar Template</title>
</head>
<body>

<h1>Editar Template</h1>

<?php if ($error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

    <label for="titulo">Título:</label><br>
    <input type="text" id="titulo" name="titulo" required value="<?= htmlspecialchars($template['titulo']) ?>"><br><br>

    <label for="template_text">Texto do Template:</label><br>
    <textarea id="template_text" name="template_text" rows="10" cols="50" required><?= htmlspecialchars($template['template_text']) ?></textarea><br><br>

    <button type="submit">Salvar</button>
</form>

<p><a href="template.php">Voltar para Templates</a></p>

</body>
</html>
