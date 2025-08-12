<?php
session_start();

if (!isset($_SESSION['cliente_id']) || !is_numeric($_SESSION['cliente_id'])) {
    header("Location: login.php?msg=" . urlencode("Por favor, faça login para acessar o perfil."));
    exit;
}

require_once '../includes/db.php';
$clienteId = (int)$_SESSION['cliente_id'];
$success = '';
$error = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$clienteId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    $ativo = $cliente && $cliente['ativo'] == 1;
    $primeiroNome = $cliente && !empty($cliente['nome']) ? explode(' ', trim($cliente['nome']))[0] : '';

    if (!$cliente) {
        $error = "Conta não encontrada.";
    }
} catch (PDOException $e) {
    $error = "Erro ao carregar dados: " . htmlspecialchars($e->getMessage());
    $cliente = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Erro de validação CSRF.";
    } else {
        $nome = trim($_POST['nome']);
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $telefone = preg_replace('/\D/', '', $_POST['telefone']);
        $chave_pix = trim($_POST['chave_pix']);
        $senha = $_POST['senha'] ?? '';

        if (empty($nome)) {
            $error = "Nome é obrigatório.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "E-mail inválido.";
        }

        if (empty($error)) {
            try {
                $sql = "UPDATE clientes SET nome = ?, email = ?, telefone = ?, chave_pix = ?";
                $params = [$nome, $email, $telefone ?: null, $chave_pix ?: null];

                if (!empty($senha)) {
                    $sql .= ", senha = ?";
                    $params[] = password_hash($senha, PASSWORD_BCRYPT);
                }

                $sql .= " WHERE id = ?";
                $params[] = $clienteId;

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);

                $success = "Perfil atualizado com sucesso!";
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $cliente['nome'] = $nome;
                $cliente['email'] = $email;
                $cliente['telefone'] = $telefone;
                $cliente['chave_pix'] = $chave_pix;
                $primeiroNome = explode(' ', trim($nome))[0];
            } catch (PDOException $e) {
                $error = "Erro ao atualizar: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Editar Perfil</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f5f7fa;
    }

    .container {
      display: flex;
      min-height: 100vh;
    }

    .content {
      flex: 1;
      padding: 40px;
      margin-left: 240px;
    }

    main {
      max-width: 700px;
      margin: auto;
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #293a5e;
    }

    .form-group {
      margin-bottom: 15px;
    }

    label {
      font-weight: bold;
      margin-bottom: 5px;
      display: block;
    }

    input[type="text"], input[type="email"], input[type="password"] {
      width: 100%;
      padding: 10px;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    button {
      width: 100%;
      padding: 12px;
      background-color: #293a5e;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
    }

    .success-message, .error-message {
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
    }

    .success-message {
      background: #d4edda;
      color: #155724;
    }

    .error-message {
      background: #f8d7da;
      color: #721c24;
    }

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

      .content {
        margin-left: 0;
        padding: 20px;
      }
    }
  </style>
</head>
<body>

<div class="container">
  <!-- Sidebar -->
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
        <li><a href="dashboard.php"><i class="fa fa-gauge"></i> Dashboard</a></li>
        <li><a href="users.php" class="<?php echo !$ativo ? 'disabled' : ''; ?>"><i class="fa fa-user-group"></i> Clientes</a></li>
        <li><a href="servidores.php" class="<?php echo !$ativo ? 'disabled' : ''; ?>"><i class="fa fa-server"></i> Servidores</a></li>
        <li><a href="planos.php" class="<?php echo !$ativo ? 'disabled' : ''; ?>"><i class="fa fa-tags"></i> Planos</a></li>
        <li><a href="template.php" class="<?php echo !$ativo ? 'disabled' : ''; ?>"><i class="fa fa-palette"></i> Templates</a></li>
        <li><a href="produtos.php" class="<?php echo !$ativo ? 'disabled' : ''; ?>"><i class="fa fa-box"></i> Produtos</a></li>
        <li><a href="logout.php"><i class="fa fa-right-from-bracket"></i> Sair</a></li>
      </ul>
    </nav>
  </aside>

  <!-- Content -->
  <div class="content">
    <main>
      <h2>Editar Perfil</h2>

      <?php if ($success): ?>
        <div class="success-message"><?php echo $success; ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="error-message"><?php echo $error; ?></div>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <div class="form-group">
          <label for="nome">Nome Completo:</label>
          <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($cliente['nome'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
          <label for="email">E-mail:</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($cliente['email'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
          <label for="telefone">Telefone:</label>
          <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?>" placeholder="(11) 98765-4321">
        </div>

        <div class="form-group">
          <label for="chave_pix">Chave Pix:</label>
          <input type="text" id="chave_pix" name="chave_pix" value="<?php echo htmlspecialchars($cliente['chave_pix'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label for="senha">Nova Senha (opcional):</label>
          <input type="password" id="senha" name="senha" placeholder="Deixe em branco para manter a senha atual">
        </div>

        <button type="submit">Salvar Alterações</button>
      </form>
    </main>
  </div>
</div>

<script>
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const menuToggle = document.getElementById('menuToggle');
  const menuIcon = document.getElementById('menuIcon');

  sidebar.classList.toggle('active');
  menuToggle.classList.toggle('open');
  menuIcon.textContent = sidebar.classList.contains('active') ? '✖' : '☰';
}

$(document).ready(function () {
  $('#telefone').mask('(00) 00000-0000');

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

</body>
</html>