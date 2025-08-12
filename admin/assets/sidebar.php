<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sidebar Responsiva</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
    }

    .sidebar {
      width: 220px;
      background: #293a5e;
      color: white;
      padding: 15px;
      height: 100vh;
      position: fixed;
      left: 0;
      top: 0;
      overflow-y: auto;
      transition: transform 0.3s ease-in-out;
      display: flex;
      flex-direction: column;
      z-index: 1000;
    }

    .sidebar h3 {
      text-align: left;
      margin-bottom: 20px;
      color: #f0a500;
      font-size: 20px;
      padding-left: 5px;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .sidebar ul li {
      margin-bottom: 8px;
    }

    .sidebar ul li a {
      color: white;
      text-decoration: none;
      padding: 10px 12px;
      display: flex;
      align-items: center;
      border-radius: 5px;
      transition: background 0.3s ease;
      font-size: 15px;
    }

    .sidebar ul li a i {
      margin-right: 10px;
      min-width: 20px;
    }

    .sidebar ul li a:hover {
      background: #f0a500;
      color: #222;
    }

    .sidebar ul li a .arrow {
      margin-left: auto;
      transition: transform 0.3s ease;
    }

    .sidebar ul li a.active .arrow {
      transform: rotate(90deg);
    }

    .submenu {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
      background: #3a4a7a;
      border-radius: 5px;
      margin-top: 5px;
    }

    .submenu.open {
      max-height: 200px;
    }

    .submenu li a {
      padding: 10px 30px;
      font-size: 14px;
      color: #ddd;
      display: block;
      text-align: left;
    }

    .submenu li a:hover {
      background: #f0a500;
      color: #222;
    }

    .divider {
      border-top: 1px solid #44567c;
      margin: 10px 0;
    }

    .menu-icon {
      display: none;
      cursor: pointer;
      position: absolute;
      top: 20px;
      left: 20px;
      z-index: 1100;
      width: 35px;
      height: 30px;
    }

    .menu-icon div {
      width: 35px;
      height: 5px;
      background-color: #000;
      margin: 6px 0;
      border-radius: 5px;
      transition: 0.4s;
    }

    .menu-icon.open div {
      background-color: #fff;
    }

    .menu-icon.open div:nth-child(1) {
      transform: rotate(45deg) translate(5px, 5px);
    }

    .menu-icon.open div:nth-child(2) {
      opacity: 0;
    }

    .menu-icon.open div:nth-child(3) {
      transform: rotate(-45deg) translate(6px, -6px);
    }

    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
      }

      .sidebar.active {
        transform: translateX(0);
      }

      .menu-icon {
        display: block;
      }
    }
  </style>
</head>
<body>

  <div class="menu-icon" onclick="toggleSidebar()" id="menuBtn">
    <div></div>
    <div></div>
    <div></div>
  </div>

  <div class="sidebar" id="sidebar">
    <h3>Admin Panel</h3>
    <ul>
      <li><a href="dashboard.php"><i class="fa fa-tachometer-alt"></i> Dashboard</a></li>
      <div class="divider"></div>

      <li><a href="ver_usuarios.php"><i class="fa fa-user-shield"></i> Usuários / Gestor</a></li>
      <li><a href="users.php"><i class="fa fa-users"></i> Clientes</a></li>
      <div class="divider"></div>

      <!-- Catálogo Digital -->
      <li>
        <a href="#" id="catalogoBtn"><i class="fa fa-box"></i> Catálogo Digital <i class="fa fa-chevron-right arrow"></i></a>
        <ul class="submenu" id="catalogoSubmenu">
          <li><a href="produtos.php">Produtos Index</a></li>
          <li><a href="servicos.php">Produtos / Serviços</a></li>
          <li><a href="https://go.aftvnews.com/">Downloader</a></li>
        </ul>
      </li>
      <div class="divider"></div>

      <!-- Cloud Store -->
      <li>
        <a href="#" id="cloudBtn"><i class="fa fa-cloud"></i> Cloud Store <i class="fa fa-chevron-right arrow"></i></a>
        <ul class="submenu" id="cloudSubmenu">
          <li><a href="admin_listar_alugueis.php">Aluguéis Ativos</a></li>
          <li><a href="servidores.php">Servidores</a></li>
          <li><a href="listar_planos.php">Listar Planos</a></li>
        </ul>
      </li>

      <li><a href="admin_whatsapp_template.php"><i class="fa fa-comment-dots"></i> Mensagens</a></li>
      <li><a href="faturas.php"><i class="fa fa-credit-card"></i> Faturas</a></li>
      <li><a href="listar_admins.php"><i class="fa fa-user-cog"></i> Admin</a></li>
      <div class="divider"></div>

      <li><a href="logout.php"><i class="fa fa-sign-out-alt"></i> Sair</a></li>
    </ul>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      const menuIcon = document.getElementById("menuBtn");
      sidebar.classList.toggle("active");
      menuIcon.classList.toggle("open");
    }

    // Submenu 1
    const catalogoBtn = document.getElementById("catalogoBtn");
    const catalogoSubmenu = document.getElementById("catalogoSubmenu");

    catalogoBtn.addEventListener("click", function(e) {
      e.preventDefault();
      catalogoSubmenu.classList.toggle("open");
      catalogoBtn.classList.toggle("active");
    });

    // Submenu 2
    const cloudBtn = document.getElementById("cloudBtn");
    const cloudSubmenu = document.getElementById("cloudSubmenu");

    cloudBtn.addEventListener("click", function(e) {
      e.preventDefault();
      cloudSubmenu.classList.toggle("open");
      cloudBtn.classList.toggle("active");
    });
  </script>
</body>
</html>
