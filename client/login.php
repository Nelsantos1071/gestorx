<?php
// arquivo: login.php
session_start();
require_once '../includes/db.php';
require_once '../includes/enviar_email.php';

$aba = $_GET['aba'] ?? 'login';
$msg = "";
$debug_mode = true; // Desative em produção (false)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$site_domain = $protocol . '://' . $_SERVER['HTTP_HOST']; // Detecção automática do domínio

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // LOGIN
    if (isset($_POST['login'])) {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        $stmt = $pdo->prepare("SELECT id, nome, senha FROM clientes WHERE email = ?");
        $stmt->execute([$email]);
        $cliente = $stmt->fetch();

        if ($cliente && password_verify($senha, $cliente['senha'])) {
            $_SESSION['cliente_id'] = $cliente['id'];
            $_SESSION['cliente_nome'] = $cliente['nome'];
            header("Location: dashboard.php");
            exit;
        } else {
            $msg = "E-mail ou senha inválidos.";
        }
    }

    // CADASTRO
    if (isset($_POST['cadastrar'])) {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        if ($nome && $email && $senha) {
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $msg = "E-mail já cadastrado.";
                $aba = 'cadastrar';
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $token = bin2hex(random_bytes(16));
                $stmt = $pdo->prepare("INSERT INTO clientes (nome, email, senha, token, ativo, criado_em) VALUES (?, ?, ?, ?, 1, NOW())");
                $stmt->execute([$nome, $email, $hash, $token]);
                $msg = "Cadastro realizado! Faça login.";
                $aba = 'login';
            }
        } else {
            $msg = "Preencha todos os campos.";
            $aba = 'cadastrar';
        }
    }

    // RECUPERAR SENHA
    if (isset($_POST['recuperar'])) {
        $email = trim($_POST['email'] ?? '');
        $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
        $stmt->execute([$email]);
        $cliente = $stmt->fetch();

        if ($cliente) {
            $token = bin2hex(random_bytes(32));
            $stmt = $pdo->prepare("UPDATE clientes SET token = ?, token_expiracao = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ?");
            $stmt->execute([$token, $cliente['id']]);

            $link = "$site_domain/client/redefinir_senha.php?token=$token";
            $assunto = "Recuperação de Senha - GestorX";
            $mensagem = "
                <h2>Recuperação de Senha</h2>
                <p>Olá,</p>
                <p>Recebemos uma solicitação para redefinir sua senha no GestorX. Clique no botão abaixo para criar uma nova senha:</p>
                <p><a href='$link' style='display: inline-block; padding: 10px 20px; background-color: #293a5e; color: white; text-decoration: none; border-radius: 5px;'>Redefinir Senha</a></p>
                <p>Se você não solicitou esta recuperação, ignore este e-mail.</p>
                <p>Atenciosamente,<br>Equipe GestorX</p>
            ";

            $enviado = enviarEmailSMTP($email, $assunto, $mensagem);
            if ($enviado === true) {
                $msg = "Link de recuperação enviado para seu e-mail.";
                $aba = 'login';
            } else {
                $msg = $debug_mode ? "Erro ao enviar e-mail: $enviado" : "Erro ao enviar e-mail de recuperação. Tente novamente ou contate o suporte.";
                $aba = 'recuperar';
            }
        } else {
            $msg = "E-mail não encontrado.";
            $aba = 'recuperar';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(ucfirst($aba)) ?></title>
    <style>
        * { box-sizing: border-box; }
        body, html {
            height: 100%; margin: 0;
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 15px;
        }
        .container {
            background: white;
            padding: 30px 25px 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 400px; width: 100%;
            text-align: center;
        }
        h2 { margin: 0 0 20px; }
        h2 a {
            color: #293a5e;
            text-decoration: none;
        }
        form { text-align: left; }
        label {
            display: block;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 6px;
            color: #555;
        }
        input[type="email"], input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #ccc;
            border-radius: 4px;
            font-size: 15px;
        }
        .senha-wrapper {
            position: relative;
        }
        .senha-wrapper input {
            padding-right: 40px;
        }
        .senha-wrapper button {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 13px;
            color: #293a5e;
        }
        button[type="submit"] {
            margin-top: 25px;
            padding: 12px;
            width: 100%;
            background-color: #293a5e;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }
        .msg {
            margin-top: 15px;
            color: #d93025;
            font-weight: 600;
            min-height: 24px;
        }
        nav {
            margin-top: 25px;
            font-size: 14px;
        }
        nav a {
            color: #293a5e;
            text-decoration: none;
            margin: 0 7px;
        }
        nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2><a href="/index.php"><?= htmlspecialchars(ucfirst($aba)) ?></a></h2>
        <div class="msg"><?= $msg ? htmlspecialchars($msg) : " " ?></div>

        <?php if ($aba === 'login'): ?>
            <form method="post" novalidate>
                <label for="email-login">E-mail:</label>
                <input type="email" id="email-login" name="email" required placeholder="Ex: nelsantos.soft@gmail.com" autocomplete="username" />

                <label for="senha-login">Senha:</label>
                <div class="senha-wrapper">
                    <input type="password" id="senha-login" name="senha" required placeholder="Ex: senha123" autocomplete="current-password" />
                    <button type="button" onclick="toggleSenha('senha-login', this)">Mostrar</button>
                </div>

                <button type="submit" name="login">Entrar</button>
            </form>

        <?php elseif ($aba === 'cadastrar'): ?>
            <form method="post" novalidate>
                <label for="nome-cadastrar">Nome:</label>
                <input type="text" id="nome-cadastrar" name="nome" required placeholder="Ex: Nelsantos" autocomplete="name" />

                <label for="email-cadastrar">E-mail:</label>
                <input type="email" id="email-cadastrar" name="email" required placeholder="Ex: nelsantos.soft@gmail.com" autocomplete="email" />

                <label for="senha-cadastrar">Senha:</label>
                <div class="senha-wrapper">
                    <input type="password" id="senha-cadastrar" name="senha" required placeholder="Ex: senha123" autocomplete="new-password" />
                    <button type="button" onclick="toggleSenha('senha-cadastrar', this)">Mostrar</button>
                </div>

                <button type="submit" name="cadastrar">Cadastrar</button>
            </form>

        <?php elseif ($aba === 'recuperar'): ?>
            <form method="post" novalidate>
                <label for="email-recuperar">Digite seu e-mail para recuperar a senha:</label>
                <input type="email" id="email-recuperar" name="email" required placeholder="Ex: nelsantos.soft@gmail.com" autocomplete="email" />
                <button type="submit" name="recuperar">Enviar link</button>
            </form>
        <?php endif; ?>

        <nav>
            <?php
                $links = [];
                if ($aba !== 'login') $links[] = '<a href="?aba=login">Login</a>';
                if ($aba !== 'cadastrar') $links[] = '<a href="?aba=cadastrar">Cadastrar</a>';
                if ($aba !== 'recuperar') $links[] = '<a href="?aba=recuperar">Esqueci a senha</a>';
                echo implode(' | ', $links);
            ?>
        </nav>
    </div>

    <script>
        function toggleSenha(id, btn) {
            const campo = document.getElementById(id);
            if (campo.type === 'password') {
                campo.type = 'text';
                btn.textContent = 'Ocultar';
            } else {
                campo.type = 'password';
                btn.textContent = 'Mostrar';
            }
        }
    </script>
</body>
</html>