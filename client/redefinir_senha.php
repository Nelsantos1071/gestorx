<?php
// arquivo: redefinir_senha.php
session_start();
require_once '../includes/db.php';

$msg = "";
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$site_domain = $protocol . '://' . $_SERVER['HTTP_HOST']; // Detecção automática do domínio

if (!$token) {
    $msg = "Token inválido ou ausente.";
} else {
    // Verifica se o token existe na tabela clientes
    $stmt = $pdo->prepare("SELECT id, email FROM clientes WHERE token = ? AND ativo = 1 AND (token_expiracao IS NULL OR token_expiracao > NOW())");
    $stmt->execute([$token]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        $msg = "Token inválido ou expirado.";
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redefinir'])) {
        $nova_senha = $_POST['nova_senha'] ?? '';
        if ($nova_senha) {
            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE clientes SET senha = ?, token = NULL, token_expiracao = NULL WHERE id = ?");
            if ($stmt->execute([$hash, $cliente['id']])) {
                $msg = "Senha redefinida com sucesso! Você será redirecionado para a tela de login.";
                // Adiciona JavaScript para redirecionamento automático
                echo "<script>
                        setTimeout(function() {
                            window.location.href = 'login.php';
                        }, 3000); // Redireciona após 3 segundos
                      </script>";
            } else {
                $msg = "Erro ao redefinir a senha. Tente novamente.";
            }
        } else {
            $msg = "Por favor, insira uma nova senha.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redefinir Senha</title>
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
        h2 a { color: #293a5e; text-decoration: none; }
        form { text-align: left; }
        label { display: block; font-weight: bold; margin-top: 15px; margin-bottom: 6px; color: #555; }
        input[type="password"] {
            width: 100%; padding: 10px 12px; border: 1.5px solid #ccc;
            border-radius: 4px; font-size: 15px;
        }
        .senha-wrapper { position: relative; }
        .senha-wrapper input { padding-right: 40px; }
        .senha-wrapper button {
            position: absolute; right: 5px; top: 50%; transform: translateY(-50%);
            background: transparent; border: none; cursor: pointer; font-size: 13px; color: #293a5e;
        }
        button[type="submit"] {
            margin-top: 25px; padding: 12px; width: 100%;
            background-color: #293a5e; border: none; border-radius: 5px;
            color: white; font-size: 16px; font-weight: bold; cursor: pointer;
        }
        .msg { margin-top: 15px; color: #d93025; font-weight: 600; min-height: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <h2><a href="login.php">Redefinir Senha</a></h2>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>

        <?php if ($cliente && empty($msg) || strpos($msg, "Por favor") !== false): ?>
            <form method="post" novalidate>
                <label for="nova-senha">Nova Senha:</label>
                <div class="senha-wrapper">
                    <input type="password" id="nova-senha" name="nova_senha" required placeholder="Digite sua nova senha" autocomplete="new-password" />
                    <button type="button" onclick="toggleSenha('nova-senha', this)">Mostrar</button>
                </div>
                <button type="submit" name="redefinir">Redefinir Senha</button>
            </form>
        <?php endif; ?>
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