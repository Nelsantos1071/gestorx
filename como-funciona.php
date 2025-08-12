<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Como Funciona - NSVM</title>
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
        ul {
            margin-left: 20px;
            margin-bottom: 1em;
        }
        li {
            margin-bottom: 0.6em;
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

<h1>Como Funciona</h1>

<div class="container">
    <p>A NSVM oferece produtos e serviços de alta qualidade para atender suas necessidades. Veja como funciona nosso sistema:</p>

    <ul>
        <li><strong>Cadastro:</strong> Você se cadastra em nosso sistema para acessar os produtos e benefícios.</li>
        <li><strong>Escolha de produtos:</strong> Navegue pela nossa lista de produtos e escolha os que desejar.</li>
        <li><strong>Pagamento:</strong> Realize o pagamento pelos meios disponíveis, como Pix, cartão ou boleto.</li>
        <li><strong>Acesso ao produto:</strong> Após a confirmação do pagamento, você terá acesso imediato ao produto adquirido.</li>
        <li><strong>Suporte:</strong> Nossa equipe está à disposição para ajudar com qualquer dúvida ou problema.</li>
    </ul>

    <p>Nosso objetivo é facilitar a sua experiência, garantindo segurança, rapidez e facilidade em todas as etapas.</p>
</div>

<!-- Script do menu mobile -->
<script>
    document.getElementById('menu-toggle').addEventListener('click', function () {
        document.getElementById('nav').classList.toggle('show');
    });
</script>

</body>
</html>
