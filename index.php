<?php
require_once 'includes/db.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Buscar todos os produtos
$produtos = $pdo->query("SELECT * FROM produtos ORDER BY criado_em DESC")->fetchAll();

// Definir o caminho público para as imagens
$publicUploadPath = '/Uploads/produto/'; // Caminho absoluto relativo à raiz do site
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Página Principal - Produtos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
        }
        /* Estilização do Header */
        .header {
            background: #293a5e;
            color: #fff;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .nav {
            display: flex;
            gap: 1.5rem;
        }
        .nav a {
            color: #fff;
            text-decoration: none;
            font-size: 1rem;
            transition: color 0.3s ease;
        }
        .nav a:hover {
            color: #ffb703;
        }
        .menu-toggle {
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            display: none;
            cursor: pointer;
        }
        .container {
            padding-top: 80px; /* Espaço para o header fixo */
        }
        h1 {
            color: #293a5e;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 2rem;
        }
        .product-card {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .product-card .card-body {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .product-card .card-title {
            font-size: 1.25rem;
            color: #293a5e;
        }
        .product-card .card-text {
            color: #495057;
            font-size: 1rem;
        }
        .product-card .price {
            font-weight: bold;
            color: #d90429;
            font-size: 1.1rem;
        }
        .product-card .btn-container {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }
        .no-image {
            width: 100%;
            height: 200px;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-style: italic;
            font-size: 1rem;
        }
        .debug-info {
            color: #d90429;
            font-size: 0.8rem;
            padding: 0.5rem;
        }
        /* Estilização do Modal */
        .modal-content {
            border-radius: 0.5rem;
            border: none;
        }
        .modal-header {
            background-color: #293a5e;
            color: #fff;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }
        .modal-body {
            background-color: #f8f9fa;
        }
        .modal-footer {
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
        }
        /* Responsividade */
        @media (max-width: 991.98px) {
            .nav {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 60px;
                left: 0;
                width: 100%;
                background: #293a5e;
                padding: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .nav.active {
                display: flex;
            }
            .menu-toggle {
                display: block;
            }
        }
        @media (max-width: 767.98px) {
            .container {
                padding-top: 70px;
                /* Garante que a seção de produtos seja visível e destacada */
                margin-bottom: 2rem;
            }
            h1 {
                font-size: 1.5rem;
                /* Adiciona um fundo leve para destacar o título */
                background: #ffffff;
                padding: 0.5rem;
                border-radius: 0.3rem;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .row {
                /* Garante que a grade de produtos seja visível */
                display: flex;
                flex-wrap: wrap;
                margin-bottom: 1rem;
            }
            .product-card img, .no-image {
                height: 150px;
            }
            .product-card .card-title {
                font-size: 1.1rem;
            }
            .product-card .card-text {
                font-size: 0.9rem;
            }
            .product-card .price {
                font-size: 1rem;
            }
            .product-card .btn-container {
                flex-direction: column;
                gap: 0.25rem;
            }
        }
        @media (max-width: 575.98px) {
            .container {
                padding: 1rem;
                padding-top: 60px;
                /* Adiciona um fundo leve para destacar a seção de produtos */
                background: #ffffff;
                border-radius: 0.5rem;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            h1 {
                font-size: 1.25rem;
                /* Mantém o destaque do título */
                background: #ffffff;
                padding: 0.5rem;
                border-radius: 0.3rem;
            }
            .row {
                /* Garante visibilidade e espaçamento */
                display: flex;
                flex-wrap: wrap;
                margin-bottom: 1rem;
            }
            .product-card {
                margin-bottom: 1rem;
            }
            .product-card img, .no-image {
                height: 120px;
            }
            .product-card .card-title {
                font-size: 1rem;
            }
            .product-card .card-text {
                font-size: 0.85rem;
            }
            .product-card .price {
                font-size: 0.9rem;
            }
            .product-card .btn-container {
                flex-direction: column;
                gap: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Menu -->
    <header class="header">
        <div class="logo">NSVM</div>
        <nav class="nav" id="nav">
            <a href="index.php">Home</a>
            <a href="produtos.php">Produtos</a>
            <a href="como-funciona.php">Como Funciona</a>
            <a href="termos.php">Termos</a>
            <a href="client/login.php">Login</a>
        </nav>
        <button class="menu-toggle" id="menu-toggle">☰</button>
    </header>

    <div class="container">
        <h1 class="text-center mb-4">Produtos</h1>
        <div class="row">
            <?php foreach ($produtos as $produto): ?>
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="product-card">
                        <?php
                        // Caminho completo para verificar a existência da imagem
                        $imagePath = $_SERVER['DOCUMENT_ROOT'] . $publicUploadPath . $produto['url_img'];
                        $imageUrl = $publicUploadPath . htmlspecialchars($produto['url_img']);
                        ?>
                        <?php if ($produto['url_img'] && file_exists($imagePath)): ?>
                            <img src="<?= $imageUrl ?>" alt="<?= htmlspecialchars($produto['titulo']) ?>">
                        <?php else: ?>
                            <div class="no-image">Sem imagem</div>
                            <?php if ($produto['url_img']): ?>
                                <div class="debug-info">
                                    Erro: Imagem <?= htmlspecialchars($produto['url_img']) ?> não encontrada em <?= $imagePath ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($produto['titulo']) ?></h5>
                            <p class="card-text"><?= nl2br(htmlspecialchars($produto['descricao'])) ?></p>
                            <p class="price">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                            <div class="btn-container">
                                <a href="client/login.php" class="btn btn-primary">Comprar</a>
                                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#saibaMaisModal<?= $produto['id'] ?>">Saiba Mais</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Saiba Mais para este produto -->
                <div class="modal fade" id="saibaMaisModal<?= $produto['id'] ?>" tabindex="-1" aria-labelledby="saibaMaisModalLabel<?= $produto['id'] ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="saibaMaisModalLabel<?= $produto['id'] ?>">Saiba Mais - <?= htmlspecialchars($produto['titulo']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php if (!empty($produto['saiba_mais'])): ?>
                                    <?php echo $produto['saiba_mais']; ?>
                                <?php else: ?>
                                    <p>Informações adicionais não disponíveis para este produto.</p>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle do menu hamburguer
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('nav').classList.toggle('active');
        });
    </script>
</body>
</html>