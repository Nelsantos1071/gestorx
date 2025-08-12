<?php
session_start();
if (!isset($_SESSION['admin_logado'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/config.php';

if (!isset($_GET['id'])) {
    echo "ID do plano não especificado.";
    exit();
}

$plano_id = intval($_GET['id']);

// Consulta para buscar o plano
$stmt = $conn->prepare("SELECT * FROM planos WHERE id = ?");
$stmt->bind_param("i", $plano_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Plano não encontrado.";
    exit();
}

$plano = $result->fetch_assoc();

// Atualizar plano
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];

    $stmt = $conn->prepare("UPDATE planos SET titulo = ?, descricao = ?, preco = ? WHERE id = ?");
    $stmt->bind_param("ssdi", $titulo, $descricao, $preco, $plano_id);

    if ($stmt->execute()) {
        header("Location: planos.php?msg=Plano+atualizado+com+sucesso");
        exit();
    } else {
        $erro = "Erro ao atualizar plano.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Plano - Painel Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            padding: 2rem;
            margin-left: 260px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.3rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            padding: 0.6rem 1.5rem;
            background-color: #2e86de;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #1b4f72;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="container">
        <h2>Editar Plano</h2>
        <?php if (isset($erro)): ?>
            <p style="color:red;"><?= $erro ?></p>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="titulo">Título do Plano:</label>
                <input type="text" id="titulo" name="titulo" value="<?= htmlspecialchars($plano['titulo']) ?>" required>
            </div>

            <div class="form-group">
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="5" required><?= htmlspecialchars($plano['descricao']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="preco">Preço (R$):</label>
                <input type="number" step="0.01" id="preco" name="preco" value="<?= htmlspecialchars($plano['preco']) ?>" required>
            </div>

            <button type="submit">Salvar Alterações</button>
        </form>
    </div>
</body>
</html>
