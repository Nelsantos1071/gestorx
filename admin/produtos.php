<?php
require_once '../includes/auth_admin.php';
require_once '../includes/db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Definir o diretório de upload
$uploadDir = __DIR__ . '/../Uploads/produto/';
$publicUploadPath = '../Uploads/produto/';

// Verificar se a pasta de upload existe e é gravável
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        die("Erro ao criar a pasta de upload: $uploadDir");
    }
}
if (!is_writable($uploadDir)) {
    die("A pasta de upload não é gravável: $uploadDir");
}

// Função para validar e mover a imagem
function uploadImage($file, $uploadDir, $existingFile = null) {
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return $existingFile;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB
    $fileType = mime_content_type($file['tmp_name']);
    $fileSize = $file['size'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = uniqid() . '.' . $extension;
    $destPath = $uploadDir . $fileName;

    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception("Tipo de arquivo não permitido. Use JPG, PNG ou GIF.");
    }
    if ($fileSize > $maxFileSize) {
        throw new Exception("Arquivo muito grande. Máximo 5MB.");
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erro no upload do arquivo: Código " . $file['error']);
    }

    if ($existingFile && file_exists($uploadDir . $existingFile)) {
        if (!unlink($uploadDir . $existingFile)) {
            throw new Exception("Erro ao excluir a imagem antiga: $existingFile");
        }
    }

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new Exception("Erro ao mover o arquivo para $destPath");
    }

    return $fileName;
}

// Criar produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'criar') {
    try {
        $titulo = $_POST['titulo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $preco = str_replace(',', '.', str_replace('.', '', $_POST['preco'] ?? 0));
        $preco = floatval($preco);
        $url_img = null;

        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
            $url_img = uploadImage($_FILES['imagem'], $uploadDir);
        }

        $stmt = $pdo->prepare("INSERT INTO produtos (titulo, descricao, preco, url_img) VALUES (?, ?, ?, ?)");
        $stmt->execute([$titulo, $descricao, $preco, $url_img]);

        header('Location: produtos.php');
        exit;
    } catch (Exception $e) {
        die("Erro ao criar produto: " . $e->getMessage());
    }
}

// Deletar produto
if (isset($_GET['delete'])) {
    try {
        $id = intval($_GET['delete']);
        $stmt = $pdo->prepare("SELECT url_img FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $produto = $stmt->fetch();

        if ($produto && $produto['url_img'] && file_exists($uploadDir . $produto['url_img'])) {
            if (!unlink($uploadDir . $produto['url_img'])) {
                throw new Exception("Erro ao excluir a imagem: " . $produto['url_img']);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->execute([$id]);

        header('Location: produtos.php');
        exit;
    } catch (Exception $e) {
        die("Erro ao excluir produto: " . $e->getMessage());
    }
}

// Buscar todos produtos
$produtos = $pdo->query("SELECT * FROM produtos ORDER BY criado_em DESC")->fetchAll();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Produtos - Gestor Token</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            overflow-x: hidden;
        }
        main.content {
            margin-left: 0;
            padding: 1rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }
        h1 {
            color: #293a5e;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: clamp(1.5rem, 4vw, 2.5rem);
        }
        .btn-primary {
            background-color: #293a5e;
            border-color: #293a5e;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #1f2b49;
            border-color: #1f2b49;
        }
        .btn-warning {
            background-color: #ffb703;
            border-color: #ffb703;
            color: #000;
        }
        .btn-danger {
            background-color: #d90429;
            border-color: #d90429;
        }
        .card {
            padding: 1.5rem;
            border-radius: 0.5rem;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .table-responsive {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        table {
            word-break: break-word;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 100%;
        }
        th, td {
            vertical-align: middle;
            padding: 0.75rem;
            font-size: clamp(0.8rem, 2vw, 1rem);
        }
        td img {
            max-width: 100px;
            max-height: 50px;
            object-fit: cover;
            border-radius: 0.25rem;
        }
        .form-control, .form-control-file {
            border-radius: 0.25rem;
            transition: border-color 0.3s ease;
            font-size: clamp(0.9rem, 2vw, 1rem);
            padding: 0.5rem;
        }
        .form-control:focus {
            border-color: #293a5e;
            box-shadow: 0 0 0 0.2rem rgba(41, 58, 94, 0.25);
        }
        .alert {
            margin-bottom: 1rem;
            border-radius: 0.3rem;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        /* Formatação de preço */
        .price-input {
            position: relative;
        }
        .price-input input {
            padding-left: 2.5rem;
            text-align: right;
            width: 100%;
        }
        .price-input::before {
            content: "R$";
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #495057;
            font-weight: 600;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        /* Ajustes responsivos */
        @media (min-width: 1200px) {
            main.content {
                margin-left: 220px;
                padding: 2rem;
            }
        }
        @media (max-width: 1199.98px) {
            .card {
                padding: 1rem;
            }
            .table-responsive {
                border-radius: 0;
            }
        }
        @media (max-width: 991.98px) {
            main.content {
                margin-left: 0;
                padding: 0.75rem;
            }
            .form-group {
                margin-bottom: 0.75rem;
            }
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
        @media (max-width: 767.98px) {
            table thead {
                display: none;
            }
            table, tbody, tr, td {
                display: block;
                width: 100%;
            }
            tr {
                margin-bottom: 0.5rem;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                padding: 0.5rem;
                background-color: #fff;
                box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            }
            td {
                padding-left: 45%;
                position: relative;
                text-align: left;
                border: none;
                border-bottom: 1px solid #dee2e6;
                font-size: clamp(0.7rem, 2vw, 0.9rem);
            }
            td:last-child {
                border-bottom: 0;
            }
            td::before {
                position: absolute;
                top: 0.5rem;
                left: 0.5rem;
                width: 40%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: 600;
                color: #293a5e;
                font-size: clamp(0.7rem, 2vw, 0.9rem);
            }
            td:nth-of-type(1)::before { content: "ID"; }
            td:nth-of-type(2)::before { content: "Título"; }
            td:nth-of-type(3)::before { content: "Descrição"; }
            td:nth-of-type(4)::before { content: "Preço"; }
            td:nth-of-type(5)::before { content: "Imagem"; }
            td:nth-of-type(6)::before { content: "Criado em"; }
            td:nth-of-type(7)::before { content: "Ações"; }
            td img {
                max-width: 60px;
                max-height: 30px;
            }
            .d-flex {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 0.5rem;
                justify-content: center;
            }
            .btn-sm {
                flex: 1 1 45%;
                min-width: 80px;
                text-align: center;
                font-size: clamp(0.7rem, 2vw, 0.9rem);
            }
        }
        @media (max-width: 575.98px) {
            .form-control, .form-control-file {
                font-size: clamp(0.8rem, 2vw, 0.9rem);
                padding: 0.4rem;
            }
            h1 {
                font-size: clamp(1.2rem, 4vw, 1.5rem);
            }
            .card {
                padding: 0.75rem;
            }
            td {
                padding-left: 35%;
                font-size: clamp(0.6rem, 2vw, 0.8rem);
            }
            td::before {
                font-size: clamp(0.6rem, 2vw, 0.8rem);
            }
            td img {
                max-width: 50px;
                max-height: 25px;
            }
            .price-input::before {
                left: 0.5rem;
                font-size: clamp(0.6rem, 2vw, 0.8rem);
            }
            .price-input input {
                padding-left: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/assets/sidebar.php'; ?>
    <main class="content">
        <h1>Produtos</h1>

        <!-- Exibir mensagens de sucesso ou erro -->
        <?php
        session_start();
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($_SESSION['success']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($_SESSION['error']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            unset($_SESSION['error']);
        }
        ?>

        <!-- Formulário para criar novo produto -->
        <div class="card mb-4">
            <form method="POST" enctype="multipart/form-data" class="p-3">
                <input type="hidden" name="acao" value="criar" />
                <div class="form-group mb-3">
                    <input type="text" name="titulo" placeholder="Título" class="form-control" required />
                </div>
                <div class="form-group mb-3">
                    <textarea name="descricao" placeholder="Descrição" class="form-control" rows="3" required></textarea>
                </div>
                <div class="form-group mb-3 price-input">
                    <input type="text" name="preco" placeholder="0,00" class="form-control" required id="preco-create" />
                </div>
                <div class="form-group mb-3">
                    <input type="file" name="imagem" accept="image/*" class="form-control form-control-file" />
                </div>
                <button type="submit" class="btn btn-primary">Adicionar Produto</button>
            </form>
        </div>

        <!-- Lista de produtos -->
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Descrição</th>
                        <th>Preço</th>
                        <th>Imagem</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produtos as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?? '-' ?></td>
                            <td><?= htmlspecialchars($p['titulo'] ?? '') ?></td>
                            <td><?= nl2br(htmlspecialchars($p['descricao'] ?? '')) ?></td>
                            <td>R$ <?= number_format($p['preco'] ?? 0, 2, ',', '.') ?></td>
                            <td>
                                <?php if (!empty($p['url_img'])): ?>
                                    <img src="<?= $publicUploadPath . htmlspecialchars($p['url_img']) ?>" alt="<?= htmlspecialchars($p['titulo'] ?? 'Imagem do produto') ?>" />
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= $p['criado_em'] ?? '-' ?></td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="editar_produto.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <a href="produtos.php?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger btn-excluir" data-id="<?= $p['id'] ?>" onclick="return confirm('Deseja excluir este produto?')">Excluir</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Função para formatar o campo de preço
        function formatPrice(input) {
            let value = input.value.replace(/[^\d,]/g, '');
            if (value === '') {
                input.value = '0,00';
                return;
            }
            value = value.replace(',', '.');
            let num = parseFloat(value) || 0;
            if (num < 0) num = 0;
            input.value = num.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Aplicar formatação nos campos de preço
        document.querySelectorAll('input[name="preco"]').forEach(input => {
            input.addEventListener('input', function() {
                formatPrice(this);
            });
            formatPrice(input);
        });

        // Adicionar spinner ao enviar formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Salvando...';
                submitButton.disabled = true;
                setTimeout(() => {
                    submitButton.disabled = false;
                }, 100);
            }
        });
    </script>
</body>
</html>