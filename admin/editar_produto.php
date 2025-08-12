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

// Buscar produto para edição
$produto = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch();
}

if (!$produto) {
    die("Produto não encontrado.");
}

// Atualizar produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar') {
    try {
        $id = intval($_POST['id']);
        $titulo = $_POST['titulo'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $preco = str_replace(',', '.', str_replace('.', '', $_POST['preco'] ?? 0));
        $preco = floatval($preco);
        $saiba_mais = $_POST['saiba_mais'] ?? ''; // Novo campo
        $existingFile = $_POST['existing_image'] ?? null;
        $url_img = $existingFile;

        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
            $url_img = uploadImage($_FILES['imagem'], $uploadDir, $existingFile);
        }

        $stmt = $pdo->prepare("UPDATE produtos SET titulo = ?, descricao = ?, preco = ?, url_img = ?, saiba_mais = ? WHERE id = ?");
        $success = $stmt->execute([$titulo, $descricao, $preco, $url_img, $saiba_mais, $id]);

        if (!$success) {
            throw new Exception("Falha ao executar a query de atualização.");
        }

        session_start();
        $_SESSION['success'] = "Produto atualizado com sucesso!";
        header('Location: produtos.php');
        exit;
    } catch (Exception $e) {
        session_start();
        $_SESSION['error'] = "Erro ao atualizar produto: " . $e->getMessage();
        header('Location: produtos.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>Editar Produto - Gestor Token</title>
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
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        .card {
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            border-radius: 0;
        }
        .form-group {
            margin-bottom: 1rem;
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
        .alert {
            margin-bottom: 1rem;
            border-radius: 0.3rem;
            font-size: clamp(0.9rem, 2vw, 1rem);
        }
        /* Ajustes responsivos */
        @media (min-width: 1200px) {
            main.content {
                margin-left: 220px;
                padding: 2rem;
            }
            .card {
                max-width: 600px;
                margin-left: auto;
                margin-right: auto;
            }
        }
        @media (max-width: 1199.98px) {
            .card {
                padding: 1rem;
            }
        }
        @media (max-width: 991.98px) {
            main.content {
                margin-left: 0;
                padding: 0.75rem;
            }
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
        @media (max-width: 767.98px) {
            .card {
                padding: 0.75rem;
            }
            .form-group {
                margin-bottom: 0.75rem;
            }
            .form-control, .form-control-file {
                font-size: clamp(0.8rem, 2vw, 0.9rem);
                padding: 0.4rem;
            }
        }
        @media (max-width: 575.98px) {
            h1 {
                font-size: clamp(1.2rem, 4vw, 1.5rem);
            }
            .card {
                padding: 0.5rem;
            }
            .form-group {
                margin-bottom: 0.5rem;
            }
            .form-control, .form-control-file {
                font-size: clamp(0.7rem, 2vw, 0.8rem);
                padding: 0.3rem;
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
        <h1>Editar Produto</h1>

        <!-- Exibir mensagens de sucesso ou erro (se redirecionado de outro lugar) -->
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

        <!-- Formulário para editar produto -->
        <div class="card">
            <form method="POST" enctype="multipart/form-data" class="p-3">
                <input type="hidden" name="acao" value="atualizar" />
                <input type="hidden" name="id" value="<?= $produto['id'] ?>" />
                <input type="hidden" name="existing_image" value="<?= htmlspecialchars($produto['url_img'] ?? '') ?>" />

                <div class="form-group mb-3">
                    <label for="titulo" class="form-label">Título</label>
                    <input type="text" name="titulo" id="titulo" class="form-control" value="<?= htmlspecialchars($produto['titulo'] ?? '') ?>" required />
                </div>
                <div class="form-group mb-3">
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea name="descricao" id="descricao" class="form-control" rows="3" required><?= htmlspecialchars($produto['descricao'] ?? '') ?></textarea>
                </div>
                <div class="form-group mb-3 price-input">
                    <label for="preco" class="form-label">Preço (R$)</label>
                    <input type="text" name="preco" id="preco" class="form-control" value="<?= number_format($produto['preco'] ?? 0, 2, ',', '.') ?>" required />
                </div>
                <div class="form-group mb-3">
                    <label for="imagem" class="form-label">Imagem</label>
                    <?php if (!empty($produto['url_img'])): ?>
                        <div class="mb-2">
                            <img src="<?= $publicUploadPath . htmlspecialchars($produto['url_img']) ?>" alt="<?= htmlspecialchars($produto['titulo'] ?? 'Imagem do produto') ?>" class="img-fluid rounded" style="max-height: 120px;" />
                        </div>
                    <?php endif; ?>
                    <input type="file" name="imagem" id="imagem" accept="image/*" class="form-control form-control-file" />
                </div>
                <div class="form-group mb-3">
                    <label for="saiba_mais" class="form-label">Saiba Mais (HTML permitido)</label>
                    <textarea name="saiba_mais" id="saiba_mais" class="form-control" rows="10"><?= htmlspecialchars($produto['saiba_mais'] ?? '') ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <a href="produtos.php" class="btn btn-secondary" style="flex: 1;">Cancelar</a>
                    <button type="submit" class="btn btn-primary" style="flex: 1;">Salvar Alterações</button>
                </div>
            </form>
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

        // Focar no primeiro campo ao carregar a página
        window.addEventListener('load', function() {
            document.getElementById('titulo').focus();
        });
    </script>
</body>
</html>