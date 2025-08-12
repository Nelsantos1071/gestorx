<?php
session_start();
require_once '../includes/db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['cliente_id']) || !is_numeric($_SESSION['cliente_id'])) {
    header("Location: login.php?msg=" . urlencode("Por favor, faça login para acessar a loja."));
    exit;
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Function to add product to cart
function adicionarAoCarrinho($pdo, $produto_id) {
    $produto_id = filter_var($produto_id, FILTER_VALIDATE_INT);
    if (!$produto_id) {
        $_SESSION['error'] = "ID do produto inválido.";
        return false;
    }

    try {
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $produto_id) {
                $item['qty']++;
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $stmt = $pdo->prepare("SELECT id, titulo, preco, url_img FROM produtos WHERE id = ?");
            $stmt->execute([$produto_id]);
            $produto = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($produto) {
                $_SESSION['cart'][] = [
                    'id' => (int)$produto['id'],
                    'titulo' => $produto['titulo'],
                    'preco' => (float)$produto['preco'],
                    'url_img' => $produto['url_img'] ?? 'https://via.placeholder.com/150',
                    'qty' => 1
                ];
                $_SESSION['success'] = "Produto adicionado ao carrinho!";
                return true;
            } else {
                $_SESSION['error'] = "Produto não encontrado.";
                return false;
            }
        }
        $_SESSION['success'] = "Produto adicionado ao carrinho!";
        return true;
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erro ao adicionar produto: " . $e->getMessage();
        return false;
    }
}

// Function to remove product from cart
function removerDoCarrinho($remover_id) {
    $remover_id = filter_var($remover_id, FILTER_VALIDATE_INT);
    if (!$remover_id) {
        $_SESSION['error'] = "ID do produto inválido.";
        return false;
    }

    $found = false;
    foreach ($_SESSION['cart'] as $index => $item) {
        if ((int)$item['id'] === $remover_id) {
            if ($item['qty'] > 1) {
                $_SESSION['cart'][$index]['qty']--;
            } else {
                unset($_SESSION['cart'][$index]);
            }
            $found = true;
            break;
        }
    }

    if ($found) {
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        $_SESSION['success'] = "Produto removido do carrinho!";
        return true;
    } else {
        $_SESSION['error'] = "Produto não encontrado no carrinho.";
        return false;
    }
}

// Calculate cart total
function calcularTotalCarrinho() {
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += (float)$item['preco'] * $item['qty'];
    }
    return $total;
}

// Process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['produto_id'])) {
        adicionarAoCarrinho($pdo, $_POST['produto_id']);
    } elseif (isset($_POST['remover_id'])) {
        removerDoCarrinho($_POST['remover_id']);
    } else {
        $_SESSION['error'] = "Ação inválida.";
    }
    header("Location: produtos.php");
    exit;
}

// Fetch all products
try {
    $stmt = $pdo->query("SELECT id, titulo, descricao, preco, url_img FROM produtos ORDER BY criado_em DESC");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erro ao buscar produtos: " . $e->getMessage();
    $produtos = [];
}

// Calculate cart item count
$cartItemCount = array_sum(array_column($_SESSION['cart'], 'qty'));

// Fetch user data for sidebar
try {
    $stmt = $pdo->prepare("SELECT nome, email FROM clientes WHERE id = ? AND ativo = 1");
    $stmt->execute([$_SESSION['cliente_id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cliente) {
        session_destroy();
        header("Location: login.php?msg=" . urlencode("Conta não encontrada ou inativa."));
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erro ao buscar dados do cliente: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loja - Produtos para Aluguel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body, html {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
            height: 100vh;
            overflow-x: hidden;
        }
        a {
            text-decoration: none;
            color: inherit;
        }
        .sidebar {
            width: 220px;
            background: #293a5e;
            color: white;
            padding: 20px 15px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease-in-out;
            z-index: 1000;
        }
        .sidebar h3 {
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
            color: #f0a500;
            user-select: none;
        }
        .sidebar .user-info {
            font-size: 0.95rem;
            padding: 10px 0;
            border-bottom: 1px solid #ffffff33;
            margin-bottom: 20px;
            text-align: center;
            user-select: none;
        }
        .sidebar ul {
            list-style: none;
            flex-grow: 1;
            padding-left: 0;
            margin: 0;
        }
        .sidebar ul li {
            margin-bottom: 0;
            border-bottom: 1px solid #ffffff33;
        }
        .sidebar ul li:last-child {
            border-bottom: none;
        }
        .sidebar ul li a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            padding: 12px 15px;
            border-radius: 0;
            transition: background-color 0.3s ease;
            font-weight: 600;
        }
        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #f0a500;
            color: #222;
        }
        .sidebar ul li a i {
            min-width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        .menu-icon {
            display: none;
            cursor: pointer;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            width: 35px;
            height: 30px;
            user-select: none;
        }
        .menu-icon div {
            width: 35px;
            height: 4px;
            background-color: #000;
            margin: 6px 0;
            border-radius: 3px;
            transition: 0.4s;
        }
        .menu-icon.open div {
            background-color: #f0a500;
        }
        .menu-icon.open div:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }
        .menu-icon.open div:nth-child(2) {
            opacity: 0;
        }
        .menu-icon.open div:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }
        main {
            margin-left: 220px;
            padding: 20px 30px 40px;
            min-height: 100vh;
            background: #fff;
            box-shadow: 0 0 12px rgb(0 0 0 / 0.1);
            display: flex;
            flex-direction: column;
            gap: 25px;
            transition: margin-left 0.3s ease-in-out;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h2 {
            color: #293a5e;
            text-align: center;
            font-weight: 700;
        }
        .produtos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .produto {
            background: #f0f0f0;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 0 6px rgb(0 0 0 / 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .produto img {
            max-width: 150px;
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 12px;
            box-shadow: 0 0 8px rgb(0 0 0 / 0.1);
        }
        .produto h3 {
            font-size: 1.2rem;
            margin-bottom: 6px;
            color: #293a5e;
        }
        .produto p {
            font-size: 0.95rem;
            margin-bottom: 10px;
            color: #555;
        }
        .produto .preco {
            font-weight: bold;
            font-size: 1.1rem;
            color: #293a5e;
        }
        button {
            padding: 8px 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            font-size: 0.9rem;
            font-weight: 600;
        }
        button:hover {
            background-color: #218838;
        }
        .remover-btn {
            background-color: #dc3545;
            width: auto;
            padding: 6px 10px;
        }
        .remover-btn:hover {
            background-color: #c82333;
        }
        .finalizar {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background-color 0.3s;
            font-weight: 600;
        }
        .finalizar:hover {
            background-color: #0056b3;
        }
        .mensagem {
            padding: 10px;
            margin: 20px auto;
            max-width: 600px;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
        }
        .mensagem.success {
            background-color: #d4edda;
            color: #155724;
        }
        .mensagem.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .cart-icon {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #007bff;
            color: white;
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .cart-icon:hover {
            background-color: #0056b3;
        }
        .cart-count {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1001;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        .modal-content h2 {
            margin-top: 0;
            color: #293a5e;
        }
        .modal-content ul {
            list-style-type: none;
            padding: 0;
        }
        .modal-content li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-content li img {
            max-width: 50px;
            margin-right: 10px;
        }
        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 20px;
            cursor: pointer;
            color: #333;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            main {
                margin-left: 0 !important;
                padding-top: 80px;
            }
            .menu-icon {
                display: block;
            }
            .produtos-grid {
                grid-template-columns: 1fr;
            }
            .produto img {
                max-width: 120px;
            }
            .modal-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="menu-icon" id="menuBtn" onclick="toggleSidebar()" aria-label="Toggle menu" role="button" tabindex="0">
        <div></div>
        <div></div>
        <div></div>
    </div>

    <nav class="sidebar" id="sidebar" aria-label="Menu lateral principal">
        <h3>Olá, <?php echo htmlspecialchars(explode(" ", $cliente['nome'])[0]); ?>!</h3>
        <div class="user-info"><?php echo htmlspecialchars($cliente['email']); ?></div>
        <ul>
            <li><a href="dashboard.php"><i class="fa fa-tachometer"></i> Dashboard</a></li>
            <li><a href="produtos.php" class="active"><i class="fa fa-store"></i> Loja</a></li>
            <li><a href="dominios.php"><i class="fa fa-list"></i> Domínios</a></li>
            <li><a href="pagamentos.php"><i class="fa fa-credit-card"></i> Pagamentos</a></li>
            <li><a href="logout.php"><i class="fa fa-sign-out"></i> Sair</a></li>
        </ul>
    </nav>

    <main role="main" tabindex="-1">
        <div class="container">
            <h2>Produtos Disponíveis</h2>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="mensagem success"><?php echo htmlspecialchars($_SESSION['success']); ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="mensagem error"><?php echo htmlspecialchars($_SESSION['error']); ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (empty($produtos)): ?>
                <div class="mensagem error">Nenhum produto disponível no momento. Por favor, volte mais tarde.</div>
            <?php else: ?>
                <div class="produtos-grid">
                    <?php foreach ($produtos as $produto): ?>
                        <article class="produto" aria-label="Produto <?php echo htmlspecialchars($produto['titulo']); ?>">
                            <img src="<?php echo htmlspecialchars($produto['url_img'] ?? 'https://via.placeholder.com/150'); ?>" 
                                 alt="<?php echo htmlspecialchars($produto['titulo']); ?>" 
                                 onerror="this.src='https://via.placeholder.com/150'" />
                            <h3><?php echo htmlspecialchars($produto['titulo']); ?></h3>
                            <p><?php echo htmlspecialchars($produto['descricao']); ?></p>
                            <p class="preco">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>
                            <form method="post">
                                <input type="hidden" name="produto_id" value="<?php echo htmlspecialchars($produto['id']); ?>">
                                <button type="submit" aria-label="Adicionar <?php echo htmlspecialchars($produto['titulo']); ?> ao carrinho">Adicionar ao Carrinho</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="cart-icon" onclick="openModal()" aria-label="Abrir carrinho">
            <i class="fas fa-shopping-cart"></i>
            <?php if ($cartItemCount > 0): ?>
                <span class="cart-count"><?php echo $cartItemCount; ?></span>
            <?php endif; ?>
        </div>

        <div id="cartModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeModal()" aria-label="Fechar carrinho">×</span>
                <h2>Carrinho</h2>
                <?php if (!empty($_SESSION['cart'])): ?>
                    <ul>
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <li>
                                <img src="<?php echo htmlspecialchars($item['url_img']); ?>" alt="<?php echo htmlspecialchars($item['titulo']); ?>">
                                <span><?php echo htmlspecialchars($item['titulo']); ?> (<?php echo $item['qty']; ?>x) - R$ <?php echo number_format($item['preco'] * $item['qty'], 2, ',', '.'); ?></span>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="remover_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                    <button type="submit" class="remover-btn" aria-label="Remover <?php echo htmlspecialchars($item['titulo']); ?> do carrinho">Remover</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="total">Total: R$ <?php echo number_format(calcularTotalCarrinho(), 2, ',', '.'); ?></p>
                    <a class="finalizar" href="finalizar.php">Finalizar Aluguel</a>
                <?php else: ?>
                    <p>Seu carrinho está vazio.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.getElementById('menuBtn');
            sidebar.classList.toggle('active');
            menuBtn.classList.toggle('open');
        }
        document.getElementById('menuBtn').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleSidebar();
            }
        });
        function openModal() {
            document.getElementById('cartModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('cartModal').style.display = 'none';
        }
        window.onclick = function(event) {
            const modal = document.getElementById('cartModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>