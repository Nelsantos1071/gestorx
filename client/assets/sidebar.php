<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';

$clienteId = isset($_SESSION['cliente_id']) ? (int)$_SESSION['cliente_id'] : 0;
$ativo = false;
$primeiroNome = '';

if ($clienteId) {
    try {
        $stmt = $pdo->prepare("SELECT nome, ativo FROM clientes WHERE id = ?");
        $stmt->execute([$clienteId]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        $ativo = $cliente && $cliente['ativo'] == 1;

        if ($cliente && !empty($cliente['nome'])) {
            $primeiroNome = explode(' ', trim($cliente['nome']))[0];
        }
    } catch (PDOException $e) {
        error_log("Erro ao verificar status do cliente: " . $e->getMessage());
    }
}
?>

<!-- Botão Hamburguer -->
<div class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">
    <span id="menuIcon">☰</span>
</div>

<aside class="sidebar" id="sidebar">
    <nav>
        <?php if (!empty($primeiroNome)): ?>
            <div class="sidebar-user">
                Olá, <strong><?php echo htmlspecialchars($primeiroNome); ?></strong>
            </div>
        <?php endif; ?>
        <ul>
            <li><a href="dashboard.php"><i class="fla fa-gauge"></i> Dashboard</a></li>
            <li><a href="users.php" class="<?php echo !$ativo ? 'disabled' : ''; ?>"><i class="fla fa-user-group"></i> Clientes</a></li>
            <li><a href="servidores.php" class="<?php echo !$ativo ? 'disabled' : ''; ?>"><i class="fla fa-server"></i> Servidores</a></li>
            <li><a href="planos.php" class="<?php echo !$ativo ? 'disabled' : ''; ?>"><i class="fla fa-tags"></i> Planos</a></li>
            <!---<li><a href="template.php" class="<?php echo !$ativo ? 'disabled' : ''; ?>"><i class="fla fa-palette"></i> Templates</a></li> --->
            <li><a href="produtos.php" class="<?php echo !$ativo ? 'disabled' : ''; ?>"><i class="fla fa-box"></i> Produtos</a></li>
            <li><a href="logout.php"><i class="fla fa-right-from-bracket"></i> Sair</a></li>
        </ul>

    </nav>
</aside>

<!-- Estilos -->
<style>
/* Botão hamburguer */
.menu-toggle {
    position: fixed;
    top: 15px;
    left: 15px;
    font-size: 28px;
    color: black;
    background-color: transparent;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    z-index: 1100;
    display: none;
    transition: color 0.3s ease;
}
.menu-toggle.open {
    color: white;
}

/* Sidebar */
.sidebar {
    width: 240px;
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    background: #293a5e;
    color: white;
    padding-top: 60px;
    transition: transform 0.3s ease;
}

/* Responsivo */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    .sidebar.active {
        transform: translateX(0);
    }
    .menu-toggle {
        display: block;
    }
}

/* Nome do cliente */
.sidebar-user {
    padding: 15px 20px;
    font-size: 1.1rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    background-color: #2e4a7b;
}

/* Lista de navegação */
.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sidebar ul li {
    padding: 15px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}
.sidebar ul li a {
    color: white;
    text-decoration: none;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.sidebar ul li a.disabled {
    color: #888;
    pointer-events: none;
    cursor: not-allowed;
}
.sidebar ul li a:hover:not(.disabled) {
    background: #1f2a44;
}
</style>

<!-- FontAwesome (certifique-se de incluir no <head> ou layout geral do sistema) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />

<!-- Script de toggle -->
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    const menuIcon = document.getElementById('menuIcon');

    sidebar.classList.toggle('active');
    menuToggle.classList.toggle('open');

    menuIcon.textContent = sidebar.classList.contains('active') ? '✖' : '☰';
}

// Revalida menus via JS (opcional)
document.addEventListener('DOMContentLoaded', () => {
    const clienteId = <?php echo json_encode($clienteId); ?>;
    if (clienteId) {
        fetch('../admin/check_client_status.php?cliente_id=' + clienteId)
            .then(response => response.json())
            .then(data => {
                const links = document.querySelectorAll('.sidebar a:not([href="dashboard.php"]):not([href="logout.php"])');
                links.forEach(link => {
                    if (data.ativo === 0) {
                        link.classList.add('disabled');
                    } else {
                        link.classList.remove('disabled');
                    }
                });
            })
            .catch(error => console.error('Erro ao verificar status:', error));
    }
});
</script>
