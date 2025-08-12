<?php
session_start();
require_once '../includes/db.php';

$erro = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = $_POST['email'] ?? '';
  $senha = $_POST['senha'] ?? '';

  try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    if (!$stmt) {
      error_log("Erro na preparação da query SQL no login.");
      header("Location: error.php");
      exit;
    }

    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($senha, $admin['senha'])) {
      $_SESSION['admin_id'] = $admin['id'];
      header("Location: dashboard.php");
      exit;
    } else {
      $erro = "E-mail ou senha incorretos.";
    }

  } catch (PDOException $e) {
    error_log("Erro PDO no login: " . $e->getMessage());
    header("Location: error.php");
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <title>Login Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    .btn-primary {
      background-color: #293a5e;
      border-color: #293a5e;
    }
    .btn-primary:hover, 
    .btn-primary:focus, 
    .btn-primary:active, 
    .btn-primary.active, 
    .show > .btn-primary.dropdown-toggle {
      background-color: #1f2d4b;
      border-color: #1f2d4b;
    }
    .login-card {
      width: 100%;
      max-width: 400px;
    }
  </style>
</head>
<body class="bg-light d-flex justify-content-center align-items-center vh-100 p-3">
  <div class="login-card card p-4 shadow-sm">
    <h4 class="mb-4">Login Admin</h4>
    <?php if ($erro): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST" novalidate>
      <input type="email" name="email" placeholder="E-mail" class="form-control mb-3" required autofocus />

      <div class="input-group mb-4">
        <input type="password" name="senha" id="senha" class="form-control" placeholder="Senha" required />
        <button type="button" class="btn btn-outline-secondary" id="toggleSenha" tabindex="-1">
          Mostrar
        </button>
      </div>

      <button class="btn btn-primary w-100">Entrar</button>
    </form>
  </div>

  <script>
    document.getElementById('toggleSenha').addEventListener('click', function () {
      const senhaInput = document.getElementById('senha');
      if (senhaInput.type === 'password') {
        senhaInput.type = 'text';
        this.textContent = 'Ocultar';
      } else {
        senhaInput.type = 'password';
        this.textContent = 'Mostrar';
      }
    });
  </script>
</body>
</html>
