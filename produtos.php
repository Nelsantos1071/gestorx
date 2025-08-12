<?php
require_once 'includes/db.php';

try {
    $stmt = $pdo->query("SELECT * FROM produtos_servicos ORDER BY criado_em DESC");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na consulta: " . $e->getMessage());
}

function formatarPreco($valor) {
    return number_format($valor, 2, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produtos - NSVM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f9f9f9; color: #333; }

        /* Menu responsivo */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #293a5e;
            padding: 10px 20px;
            color: white;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .logo {
            font-size: 1.5em;
            font-weight: bold;
        }
        .nav {
            display: flex;
            gap: 20px;
        }
        .nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        .nav a:hover {
            color: #f0f0f0;
        }
        .menu-toggle {
            display: none;
            font-size: 1.8em;
            background: none;
            border: none;
            color: white;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .nav {
                display: none;
                flex-direction: column;
                background: #293a5e;
                position: absolute;
                top: 60px;
                left: 0;
                right: 0;
                padding: 20px;
            }
            .nav.show {
                display: flex;
            }
            .menu-toggle {
                display: block;
            }
        }

        h1 {
            text-align: center;
            margin: 30px auto 20px;
            font-size: 2em;
            color: #293a5e;
        }

        .grid {
            max-width: 1200px;
            margin: 0 auto 50px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        @media (max-width: 991px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 576px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: #e9ecef;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
            padding: 15px 20px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s ease;
            height: 100%;
        }
        .card:hover { transform: translateY(-4px); }

        .card img {
            max-width: 100%;
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .no-image {
            width: 100%;
            height: 200px;
            background: #d3d3d3;
            border-radius: 8px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-style: italic;
            font-size: 1rem;
        }

        .titulo {
            font-weight: bold;
            font-size: 1.2em;
            color: #293a5e;
            margin-bottom: 6px;
        }
        .descricao {
            flex-grow: 1;
            font-size: 0.9em;
            margin-bottom: 15px;
            color: #555;
            min-height: 60px;
            overflow: hidden;
        }
        .preco {
            font-weight: 700;
            font-size: 1.1em;
            color: #d9534f;
            margin-bottom: 12px;
        }

        .botoes {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .btn-info, .btn-comprar {
            width: 100px;
            padding: 8px 0;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            user-select: none;
            border: none;
            font-size: 0.9em;
            color: white;
            text-decoration: none;
        }
        .btn-info { background-color: #293a5e; }
        .btn-info:hover { background-color: #1e2a44; }
        .btn-comprar { background-color: #28a745; }
        .btn-comprar:hover { background-color: #1e7e34; }

        /* Modal de informação */
        #modalInfo {
            display: none;
            position: fixed;
            z-index: 1100;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        #modalInfo.active {
            display: flex;
        }
        #modalInfo .modal-content {
            background: white;
            border-radius: 10px;
            max-width: 480px;
            width: 100%;
            padding: 25px;
            position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.25);
            max-height: 80vh;
            overflow-y: auto;
        }
        #modalInfo .modal-close {
            position: absolute;
            top: 12px;
            right: 16px;
            font-size: 1.8em;
            font-weight: bold;
            color: #555;
            cursor: pointer;
        }
        #modalInfo img {
            max-width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        #modalInfo h2 {
            color: #293a5e;
            margin-bottom: 10px;
        }
        #modalInfo p {
            margin-bottom: 15px;
            color: #444;
            white-space: pre-wrap;
        }
        .debug-info {
            color: #d9534f;
            font-size: 0.8em;
            padding: 0.5em;
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

<h1>Produtos</h1>

<div class="grid" role="list">
    <?php if (count($produtos) === 0): ?>
        <p style="grid-column: 1/-1; text-align: center;">Nenhum produto encontrado.</p>
    <?php else: ?>
        <?php foreach ($produtos as $produto): ?>
            <div class="card" role="listitem">
                <?php
                // Corrigir o caminho da imagem
                $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $produto['imagem'];
                $imageUrl = '/' . $produto['imagem'];
                ?>
                <?php if ($produto['imagem'] && file_exists($imagePath)): ?>
                    <img src="<?= htmlspecialchars($imageUrl) ?>" alt="<?= htmlspecialchars($produto['titulo']) ?>">
                <?php else: ?>
                    <div class="no-image">Sem imagem</div>
                <?php endif; ?>
                <div class="titulo"><?= htmlspecialchars($produto['titulo']) ?></div>
                <div class="descricao"><?= nl2br(htmlspecialchars($produto['descricao'])) ?></div>
                <div class="preco">R$ <?= formatarPreco($produto['preco']) ?></div>
                <div class="botoes">
                    <a href="#" class="btn-info" onclick="mostrarInfo('<?= htmlspecialchars(addslashes($produto['titulo'])) ?>', '<?= htmlspecialchars(addslashes($produto['descricao'])) ?>', '<?= htmlspecialchars($imageUrl) ?>')">Info</a>
                    <a href="client/login.php" class="btn-comprar">Comprar</a>
                </div>
                <?php if ($produto['imagem'] && !file_exists($imagePath)): ?>
                    <div class="debug-info">
                        Erro: Imagem <?= htmlspecialchars($produto['imagem']) ?> não encontrada em <?= $imagePath ?><br>
                        DOCUMENT_ROOT: <?= $_SERVER['DOCUMENT_ROOT'] ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal de Informação -->
<div id="modalInfo" role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
    <div class="modal-content">
        <span class="modal-close" id="modalClose" role="button" tabindex="0" aria-label="Fechar modal">×</span>
        <img src="" alt="" id="modalImg" />
        <h2 id="modalTitulo"></h2>
        <p id="modalDescricao"></p>
    </div>
</div>
</div>

<script>
    // Toggle menu mobile
    document.getElementById('menu-toggle').addEventListener('click', function () {
        document.getElementById('nav').classList.toggle('show');
    });

    // Modal info
    const modal = document.getElementById('modalInfo');
    const modalImg = document.getElementById('modalImg');
    const modalTitulo = document.getElementById('modalTitulo');
    const modalDescricao = document.getElementById('modalDescricao');
    const modalClose = document.getElementById('modalClose');

    function mostrarInfo(titulo, descricao, img) {
        modalTitulo.textContent = titulo;
        modalDescricao.textContent = descricao;
        modalImg.src = img;
        modalImg.alt = titulo;
        modal.classList.add('active');
    }
    modalClose.onclick = function () {
        modal.classList.remove('active');
    };
    // Fechar modal com tecla ESC
    window.addEventListener('keydown', function(e) {
        if (e.key === "Escape") {
            modal.classList.remove('active');
        }
    });
</script>

</body>
</html>