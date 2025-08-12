<?php
session_start();
require_once '../includes/db.php';

// Verifica se está logado como admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Função para criar pasta caso não exista
function criarPastaSeNaoExistir($pasta) {
    if (!is_dir($pasta)) {
        mkdir($pasta, 0755, true);
    }
}

// Adicionar novo item
if (isset($_POST['add'])) {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $tipo = $_POST['tipo'];

    $img_path = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $pasta_upload = '../Uploads/' . $tipo . '/';
            criarPastaSeNaoExistir($pasta_upload);
            $newName = uniqid() . '.' . $ext;
            $dest = $pasta_upload . $newName;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $dest)) {
                $img_path = 'Uploads/' . $tipo . '/' . $newName;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO produtos_servicos (titulo, descricao, preco, tipo, imagem) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$titulo, $descricao, $preco, $tipo, $img_path]);
    header("Location: servicos.php");
    exit;
}

// Editar item
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $tipo = $_POST['tipo'];

    $img_path = null;
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $pasta_upload = '../Uploads/' . $tipo . '/';
            criarPastaSeNaoExistir($pasta_upload);
            $newName = uniqid() . '.' . $ext;
            $dest = $pasta_upload . $newName;
            if (move_uploaded_file($_FILES['imagem']['tmp_name'], $dest)) {
                $img_path = 'Uploads/' . $tipo . '/' . $newName;

                // Deletar imagem antiga para liberar espaço
                $stmtOldImg = $pdo->prepare("SELECT imagem FROM produtos_servicos WHERE id=?");
                $stmtOldImg->execute([$id]);
                $oldImg = $stmtOldImg->fetchColumn();
                if ($oldImg && file_exists('../' . $oldImg)) {
                    unlink('../' . $oldImg);
                }
            }
        }
    }

    if ($img_path) {
        $stmt = $pdo->prepare("UPDATE produtos_servicos SET titulo=?, descricao=?, preco=?, tipo=?, imagem=? WHERE id=?");
        $stmt->execute([$titulo, $descricao, $preco, $tipo, $img_path, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE produtos_servicos SET titulo=?, descricao=?, preco=?, tipo=? WHERE id=?");
        $stmt->execute([$titulo, $descricao, $preco, $tipo, $id]);
    }

    header("Location: servicos.php");
    exit;
}

// Excluir item
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Deletar imagem associada
    $stmtImg = $pdo->prepare("SELECT imagem FROM produtos_servicos WHERE id=?");
    $stmtImg->execute([$id]);
    $img = $stmtImg->fetchColumn();
    if ($img && file_exists('../' . $img)) {
        unlink('../' . $img);
    }
    $stmt = $pdo->prepare("DELETE FROM produtos_servicos WHERE id=?");
    $stmt->execute([$id]);
    header("Location: servicos.php");
    exit;
}

// Buscar todos os itens
$stmt = $pdo->query("SELECT * FROM produtos_servicos ORDER BY criado_em DESC");
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Gerenciar Produtos e Serviços</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f9f9f9;
            color: #333;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }
        .add-btn {
            display: block;
            background: #293a5e;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
            max-width: 200px;
            margin: 0 auto 30px;
            text-align: center;
        }
        .add-btn:hover {
            background: #121928;
        }
        form {
            background: white;
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        form h3 {
            margin-top: 0;
        }
        form input[type="text"],
        form input[type="number"],
        form textarea,
        form select {
            width: 100%;
            padding: 8px 10px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
        }
        form textarea {
            resize: vertical;
            min-height: 60px;
        }
        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        form input[type="file"] {
            margin-bottom: 15px;
        }
        form button {
            background: #293a5e;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
        }
        form button:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            max-width: 960px;
            margin-left: auto;
            margin-right: auto;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        thead {
            background: #293a5e;
            color: white;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        tbody tr:hover {
            background: #f1f1f1;
        }
        img {
            max-width: 80px;
            max-height: 60px;
            border-radius: 4px;
            object-fit: cover;
        }
        a {
            color: #007BFF;
            text-decoration: none;
            margin-right: 10px;
            font-weight: bold;
        }
        a:hover {
            text-decoration: underline;
        }
        button.open-modal-btn {
            background: #293a5e;
            border: none;
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        button.open-modal-btn:hover {
            background: #121928;
        }
        /* Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            padding: 15px;
            z-index: 1000;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
        }
        .modal-content h3 {
            margin-top: 0;
        }
        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            background: transparent;
            border: none;
            font-size: 1.8rem;
            cursor: pointer;
            color: #666;
            font-weight: bold;
        }
        .modal-close:hover {
            color: #000;
        }
        /* Responsivo */
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                display: none;
            }
            tbody tr {
                margin-bottom: 20px;
                background: white;
                padding: 15px;
                border-radius: 8px;
                box-shadow: 0 0 5px rgba(0,0,0,0.1);
            }
            tbody td {
                padding-left: 50%;
                position: relative;
                text-align: right;
            }
            tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: 45%;
                padding-left: 10px;
                font-weight: bold;
                text-align: left;
            }
            img {
                max-width: 100%;
                height: auto;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    
    <?php include __DIR__ . '/assets/sidebar.php'; ?>

<h1>Gerenciar Produtos e Serviços</h1>

<button class="add-btn" id="btnAddOpen">+ Adicionar Produto/Serviço</button>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Imagem</th>
            <th>Título</th>
            <th>Descrição</th>
            <th>Preço (R$)</th>
            <th>Tipo</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($itens as $item): ?>
        <tr>
            <td data-label="ID"><?= htmlspecialchars($item['id']) ?></td>
            <td data-label="Imagem">
                <?php if ($item['imagem'] && file_exists('../' . $item['imagem'])): ?>
                    <img src="../<?= htmlspecialchars($item['imagem']) ?>" alt="Imagem do item" />
                <?php else: ?>
                    Sem imagem
                <?php endif; ?>
            </td>
            <td data-label="Título"><?= htmlspecialchars($item['titulo']) ?></td>
            <td data-label="Descrição"><?= nl2br(htmlspecialchars($item['descricao'])) ?></td>
            <td data-label="Preço"><?= number_format($item['preco'], 2, ',', '.') ?></td>
            <td data-label="Tipo"><?= htmlspecialchars($item['tipo']) ?></td>
            <td data-label="Ações">
                <button class="open-modal-btn btnEdit" 
                    data-id="<?= $item['id'] ?>"
                    data-titulo="<?= htmlspecialchars($item['titulo'], ENT_QUOTES) ?>"
                    data-descricao="<?= htmlspecialchars($item['descricao'], ENT_QUOTES) ?>"
                    data-preco="<?= $item['preco'] ?>"
                    data-tipo="<?= $item['tipo'] ?>"
                >Editar</button>
                <a href="?delete=<?= $item['id'] ?>" onclick="return confirm('Confirma exclusão?');" style="color:red;">Excluir</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Modal Adicionar -->
<div class="modal" id="modalAdd">
    <div class="modal-content">
        <button class="modal-close" id="closeAdd">&times;</button>
        <h3>Adicionar Produto/Serviço</h3>
        <form method="post" enctype="multipart/form-data">
            <label for="titulo_add">Título:</label>
            <input type="text" id="titulo_add" name="titulo" required />

            <label for="descricao_add">Descrição:</label>
            <textarea id="descricao_add" name="descricao" required></textarea>

            <label for="preco_add">Preço (ex: 99.90):</label>
            <input type="number" id="preco_add" name="preco" step="0.01" min="0" required />

            <label for="tipo_add">Tipo:</label>
            <select id="tipo_add" name="tipo" required>
                <option value="">-- Selecione --</option>
                <option value="produto">Produto</option>
                <option value="servico">Serviço</option>
            </select>

            <label for="imagem_add">Imagem (jpg, png, gif):</label>
            <input type="file" id="imagem_add" name="imagem" accept="image/*" />

            <button type="submit" name="add">Adicionar</button>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal" id="modalEdit">
    <div class="modal-content">
        <button class="modal-close" id="closeEdit">&times;</button>
        <h3>Editar Produto/Serviço</h3>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" id="edit_id" />

            <label for="titulo_edit">Título:</label>
            <input type="text" id="titulo_edit" name="titulo" required />

            <label for="descricao_edit">Descrição:</label>
            <textarea id="descricao_edit" name="descricao" required></textarea>

            <label for="preco_edit">Preço (ex: 99.90):</label>
            <input type="number" id="preco_edit" name="preco" step="0.01" min="0" required />

            <label for="tipo_edit">Tipo:</label>
            <select id="tipo_edit" name="tipo" required>
                <option value="">-- Selecione --</option>
                <option value="produto">Produto</option>
                <option value="servico">Serviço</option>
            </select>

            <label for="imagem_edit">Substituir imagem (opcional):</label>
            <input type="file" id="imagem_edit" name="imagem" accept="image/*" />

            <button type="submit" name="edit">Salvar Alterações</button>
        </form>
    </div>
</div>

<script>
    // Modal Add
    const modalAdd = document.getElementById('modalAdd');
    const btnAddOpen = document.getElementById('btnAddOpen');
    const closeAdd = document.getElementById('closeAdd');

    btnAddOpen.addEventListener('click', () => {
        modalAdd.classList.add('active');
    });
    closeAdd.addEventListener('click', () => {
        modalAdd.classList.remove('active');
    });
    window.addEventListener('click', (e) => {
        if (e.target === modalAdd) {
            modalAdd.classList.remove('active');
        }
    });

    // Modal Edit
    const modalEdit = document.getElementById('modalEdit');
    const closeEdit = document.getElementById('closeEdit');
    const btnEdits = document.querySelectorAll('.btnEdit');

    btnEdits.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-id');
            const titulo = btn.getAttribute('data-titulo');
            const descricao = btn.getAttribute('data-descricao');
            const preco = btn.getAttribute('data-preco');
            const tipo = btn.getAttribute('data-tipo');

            document.getElementById('edit_id').value = id;
            document.getElementById('titulo_edit').value = titulo;
            document.getElementById('descricao_edit').value = descricao;
            document.getElementById('preco_edit').value = preco;
            document.getElementById('tipo_edit').value = tipo;

            modalEdit.classList.add('active');
        });
    });

    closeEdit.addEventListener('click', () => {
        modalEdit.classList.remove('active');
    });
    window.addEventListener('click', (e) => {
        if (e.target === modalEdit) {
            modalEdit.classList.remove('active');
        }
    });
</script>

</body>
</html>
