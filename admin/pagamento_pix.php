<?php
session_start();
require_once '../includes/db.php';

// Verifica login
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Verifica fatura_id
if (!isset($_GET['fatura_id'])) {
    $_SESSION['error'] = "ID da fatura não fornecido.";
    header("Location: dashboard.php");
    exit;
}

$fatura_id = filter_var($_GET['fatura_id'], FILTER_VALIDATE_INT);
if ($fatura_id === false) {
    $_SESSION['error'] = "ID da fatura inválido.";
    header("Location: dashboard.php");
    exit;
}

// Fetch invoice details
try {
    $stmt = $pdo->prepare("
        SELECT f.valor, f.pix_string, f.data_vencimento, u.nome_completo, p.nome AS plano_nome
        FROM faturas f
        JOIN users u ON f.user_id = u.user_id
        JOIN planos p ON f.plano_id = p.id
        WHERE f.id = ? AND f.status = 'pendente'
    ");
    $stmt->execute([$fatura_id]);
    $fatura = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fatura) {
        $_SESSION['error'] = "Fatura não encontrada ou já paga.";
        header("Location: dashboard.php");
        exit;
    }

    $amount = number_format($fatura['valor'], 2, '.', '');
    $pix_string = $fatura['pix_string'];
    $nome_completo = $fatura['nome_completo'];
    $plano_nome = $fatura['plano_nome'];
    $data_vencimento = date('d/m/Y', strtotime($fatura['data_vencimento']));
} catch (PDOException $e) {
    error_log("Erro ao buscar fatura: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao carregar fatura.";
    header("Location: dashboard.php");
    exit;
}

// Configuração da chave Pix
$pix_keys = [
    'cpf' => '30967526809', // CPF sem pontuação
    'email' => 'seu-email@exemplo.com', // Substitua pelo seu email
    'random' => '12345678-1234-1234-1234-1234567890ab', // Substitua pela sua chave aleatória
    'cnpj' => '12345678000195', // Substitua pelo seu CNPJ sem pontuação
];
$selected_key_type = 'cpf'; // Mude para 'email', 'random' ou 'cnpj' para usar outra chave
$pix_key = $pix_keys[$selected_key_type];

// Regenerar pix_string se necessário
$txid = uniqid();
$amount_str = str_pad(number_format($amount, 2, '', ''), 10, '0', STR_PAD_LEFT);
$pix_string = "00020126580014BR.GOV.BCB.PIX0114{$pix_key}0208BR.COM.OPUS" .
              $amount_str .
              "5909Loja Exemplo6009SAO PAULO62070503***6304";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Pagamento via Pix</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            background: #f8f9fa;
        }
        h1 { font-size: 1.8rem; margin-bottom: 1rem; }
        .info { margin-bottom: 1.5rem; font-size: 1rem; }
        .info p { margin: 0.5rem 0; }
        #qrcode {
            margin: 20px auto;
            width: 256px;
            height: 256px;
        }
        label {
            display: block;
            font-weight: bold;
            margin: 1rem 0 0.5rem;
        }
        input#pixCopy {
            width: 100%;
            padding: 10px;
            font-size: 1.1rem;
            margin-top: 10px;
            border: 1px solid #aaa;
            border-radius: 4px;
            user-select: all;
            background: #fff;
        }
        button {
            margin-top: 15px;
            padding: 10px 20px;
            font-size: 1rem;
            cursor: pointer;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 1rem;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @media (max-width: 575.98px) {
            body { padding: 15px; max-width: 90%; }
            h1 { font-size: 1.5rem; }
            #qrcode { width: 200px; height: 200px; }
            input#pixCopy { font-size: 1rem; }
            button { width: 100%; }
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <h1>Pagamento via Pix</h1>

    <div class="info">
        <p><strong>Usuário:</strong> <?php echo htmlspecialchars($nome_completo); ?></p>
        <p><strong>Plano:</strong> <?php echo htmlspecialchars($plano_nome); ?></p>
        <p><strong>Valor:</strong> R$ <?php echo number_format($fatura['valor'], 2, ',', '.'); ?></p>
        <p><strong>Vencimento:</strong> <?php echo htmlspecialchars($data_vencimento); ?></p>
    </div>

    <div id="qrcode"></div>

    <label for="pixCopy">Copia e cola o código Pix abaixo:</label>
    <input type="text" id="pixCopy" readonly value="<?php echo htmlspecialchars($pix_string); ?>" />

    <button onclick="copiarPix()">Copiar código Pix</button>

    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.4.4/build/qrcode.min.js"></script>
    <script>
        const pixCode = document.getElementById('pixCopy').value;
        const qrCodeDiv = document.getElementById('qrcode');

        // Gerar QR Code
        QRCode.toCanvas(qrCodeDiv, pixCode, { width: 256 }, function (error) {
            if (error) console.error(error);
        });

        // Função copiar para clipboard
        function copiarPix() {
            const pixInput = document.getElementById('pixCopy');
            pixInput.select();
            pixInput.setSelectionRange(0, 99999); // Para mobile

            try {
                document.execCommand('copy');
                alert('Código Pix copiado!');
            } catch (err) {
                alert('Não foi possível copiar o código.');
            }
        }
    </script>
</body>
</html>