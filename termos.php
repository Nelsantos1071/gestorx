<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Termos - NSVM</title>
    <style>
        /* Repeti o estilo do seu index.php para manter padrão */
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
        .logo { font-size: 1.5em; font-weight: bold; }
        .nav { display: flex; gap: 20px; }
        .nav a {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        .nav a:hover { color: #f0f0f0; }
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
            .nav.show { display: flex; }
            .menu-toggle { display: block; }
        }

        /* Conteúdo */
        h1 {
            text-align: center;
            margin: 30px auto 20px;
            font-size: 2em;
        }
        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            line-height: 1.6em;
            font-size: 1em;
        }
        p {
            margin-bottom: 1em;
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

<h1>Termos e Condições</h1>

<div class="container">
    <p><strong>Bem-vindo à NSVM.</strong> Ao acessar e utilizar nossos serviços, você concorda com os seguintes termos e condições:</p>

    <p><strong>Uso do serviço:</strong> Você concorda em utilizar nossos produtos e serviços apenas para fins legais e conforme as leis vigentes.</p>

    <p><strong>Propriedade intelectual:</strong> Todo o conteúdo, design e marcas pertencem à NSVM e seus licenciadores. É proibida a reprodução sem autorização.</p>

    <p><strong>Limitação de responsabilidade:</strong> NSVM não se responsabiliza por danos diretos ou indiretos decorrentes do uso do serviço.</p>

    <p><strong>Modificações:</strong> Reservamo-nos o direito de modificar estes termos a qualquer momento, sendo sua responsabilidade verificar atualizações.</p>

    <p><strong>Contato:</strong> Para dúvidas, entre em contato conosco através do nosso e-mail ou canais oficiais.</p>
</div>

<!-- Script do menu mobile -->
<script>
    document.getElementById('menu-toggle').addEventListener('click', function () {
        document.getElementById('nav').classList.toggle('show');
    });
</script>

</body>
</html>
