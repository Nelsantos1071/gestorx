<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['cliente_id']) || !is_int((int)$_SESSION['cliente_id']) || (int)$_SESSION['cliente_id'] <= 0) {
    header("Location: login.php?msg=" . urlencode("Por favor, faça login para acessar os produtos."));
    exit;
}

$clienteId = (int)$_SESSION['cliente_id'];
$error = '';
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

// Log para depuração
error_log("Cliente ID na sessão: $clienteId");

// Processar cancelamento de aluguel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_aluguel'])) {
    try {
        $aluguel_id = (int)$_POST['aluguel_id'];
        $stmt = $pdo->prepare("
            UPDATE alugueis 
            SET status = 'cancelado', data_cancelamento = NOW()
            WHERE id = ? AND cliente_id = ? AND status = 'ativo'
        ");
        $stmt->execute([$aluguel_id, $clienteId]);
        if ($stmt->rowCount() > 0) {
            $success = "Aluguel cancelado com sucesso!";
            error_log("Aluguel cancelado: id=$aluguel_id, cliente_id=$clienteId");
        } else {
            $error = "Não foi possível cancelar o aluguel. Verifique se ele ainda está ativo.";
            error_log("Falha ao cancelar aluguel: id=$aluguel_id, cliente_id=$clienteId");
        }
        header("Location: produtos.php?success=" . urlencode($success));
        exit;
    } catch (PDOException $e) {
        $error = "Erro ao cancelar aluguel: " . htmlspecialchars($e->getMessage());
        error_log($error);
    }
}

// Processar renovação de aluguel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_renovacao'])) {
    try {
        $aluguel_id = (int)$_POST['aluguel_id'];
        $stmt = $pdo->prepare("
            UPDATE alugueis 
            SET data_aluguel = NOW()
            WHERE id = ? AND cliente_id = ? AND status = 'ativo'
        ");
        $stmt->execute([$aluguel_id, $clienteId]);
        if ($stmt->rowCount() > 0) {
            $success = "Aluguel renovado com sucesso! Aguarde a confirmação do Administrador.";
            error_log("Aluguel renovado: id=$aluguel_id, cliente_id=$clienteId");
        } else {
            $error = "Não foi possível renovar o aluguel. Verifique se ele ainda está ativo.";
            error_log("Falha ao renovar aluguel: id=$aluguel_id, cliente_id=$clienteId");
        }
        header("Location: produtos.php?success=" . urlencode($success));
        exit;
    } catch (PDOException $e) {
        $error = "Erro ao renovar aluguel: " . htmlspecialchars($e->getMessage());
        error_log($error);
    }
}

// Verificar status ativo do cliente
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare("SELECT ativo, nome FROM clientes WHERE id = ?");
    $stmt->execute([$clienteId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cliente || $cliente['ativo'] == 0) {
        $error = "Sua conta está inativa. Entre em contato com o suporte.";
    }
} catch (PDOException $e) {
    $error = "Erro ao verificar status da conta: " . htmlspecialchars($e->getMessage());
    error_log($error);
}

$alugueis = [];
$compras = [];
if (empty($error)) {
    // Buscar alugueis ativos
    try {
        $stmt = $pdo->prepare("
            SELECT a.id, p.titulo as produto, a.quantidade, a.data_aluguel, a.status, a.url_download, a.site, a.usuario, a.senha, a.chave_pix
            FROM alugueis a
            LEFT JOIN produtos p ON a.produto_id = p.id
            WHERE a.cliente_id = ? AND a.status = 'ativo'
            ORDER BY a.data_aluguel DESC
        ");
        $stmt->execute([$clienteId]);
        $alugueis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Aluguéis encontrados: " . count($alugueis) . " para cliente_id = $clienteId");
        error_log("Aluguéis: " . json_encode($alugueis));
    } catch (PDOException $e) {
        $error = "Erro ao buscar alugueis: " . htmlspecialchars($e->getMessage());
        error_log($error);
    }

    // Buscar compras
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, p.titulo as produto, c.quantidade, c.data_compra, c.valor_total
            FROM compras c
            LEFT JOIN produtos p ON c.produto_id = p.id
            WHERE c.cliente_id = ?
            ORDER BY c.data_compra DESC
        ");
        $stmt->execute([$clienteId]);
        $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Compras encontradas: " . count($compras) . " para cliente_id = $clienteId");
        error_log("Compras: " . json_encode($compras));
    } catch (PDOException $e) {
        $error = "Erro ao buscar compras: " . htmlspecialchars($e->getMessage());
        error_log($error);
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meus Produtos - <?php echo htmlspecialchars($cliente['nome'] ?? 'Cliente'); ?></title>
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
        .welcome {
            text-align: center;
            color: #293a5e;
            font-weight: 700;
            font-size: 1.9rem;
        }
        .welcome p {
            font-weight: 400;
            font-size: 1.1rem;
            margin-top: 8px;
            color: #666;
        }
        .products-section {
            max-width: 1000px;
            margin: 0 auto;
        }
        .products-section h2 {
            color: #293a5e;
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 15px;
            text-align: center;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            background: linear-gradient(135deg, #ffffff, #f9f9f9);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .products-table th, .products-table td {
            padding: 15px;
            text-align: left;
            font-size: 0.95rem;
            color: #333;
        }
        .products-table th {
            background: #293a5e;
            color: white;
            font-weight: 600;
        }
        .products-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .products-table tr:hover {
            background: #e6f7fa;
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        .action-button {
            padding: 8px 12px;
            background: #293a5e;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }
        .action-button:hover {
            background: #1a263e;
        }
        .cancel-button {
            background: #d93025;
        }
        .cancel-button:hover {
            background: #b71c1c;
        }
        .renew-button {
            background: #2e7d32;
        }
        .renew-button:hover {
            background: #1b5e20;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            position: relative;
        }
        .modal-content h3 {
            margin-bottom: 15px;
            color: #293a5e;
        }
        .modal-content p {
            margin-bottom: 10px;
            color: #666;
        }
        .modal-content input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .modal-content button {
            padding: 8px 12px;
            margin-right: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .modal-content .copy-button {
            background: #293a5e;
            color: white;
        }
        .modal-content .copy-button:hover {
            background: #1a263e;
        }
        .modal-content .download-button {
            background: #2e7d32;
            color: white;
        }
        .modal-content .download-button:hover {
            background: #1b5e20;
        }
        .modal-content .site-button {
            background: #0288d1;
            color: white;
        }
        .modal-content .site-button:hover {
            background: #01579b;
        }
        .modal-content .pix-button {
            background: #26a69a;
            color: white;
        }
        .modal-content .pix-button:hover {
            background: #1e7e73;
        }
        .modal-content .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            cursor: pointer;
            color: #666;
        }
        .modal-content .credentials {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #333;
        }
        .modal-content .credentials span {
            font-weight: bold;
        }
        .error-message {
            color: #d93025;
            text-align: center;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px;
            background: #f8d7da;
            border-radius: 8px;
        }
        .success-message {
            color: #2e7d32;
            text-align: center;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px;
            background: #c8e6c9;
            border-radius: 8px;
        }
        .no-data {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 20px;
        }
        @media (max-width: 768px) {
            main {
                margin-left: 0;
                padding-top: 20px;
            }
            .welcome {
                font-size: 1.5rem;
            }
            .products-section h2 {
                font-size: 1.3rem;
            }
            .products-table th, .products-table td {
                font-size: 0.85rem;
                padding: 10px;
            }
            .action-button, .cancel-button, .renew-button {
                padding: 6px 10px;
                font-size: 0.85rem;
            }
        }
        @media (max-width: 480px) {
            .products-table {
                font-size: 0.8rem;
            }
            .products-table th, .products-table td {
                display: block;
                text-align: right;
                padding: 8px;
            }
            .products-table th::before, .products-table td::before {
                content: attr(data-label);
                float: left;
                font-weight: bold;
                color: #293a5e;
            }
            .products-table thead {
                display: none;
            }
            .products-table tr {
                margin-bottom: 10px;
                display: block;
                border-bottom: 2px solid #ddd;
            }
            .action-button, .cancel-button, .renew-button {
                display: inline-block;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/assets/sidebar.php'; ?>

    <main role="main" tabindex="-1">
        <section class="welcome" aria-label="Mensagem de boas vindas">
            <h1>Meus Produtos</h1>
            <p>Veja os produtos alugados e comprados associados à sua conta.</p>
        </section>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php else: ?>
            <section class="products-section" aria-label="Produtos Alugados">
                <h2>Produtos Alugados</h2>
                <?php if (empty($alugueis)): ?>
                    <p class="no-data">Nenhum produto alugado no momento.</p>
                <?php else: ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Data de Aluguel</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alugueis as $aluguel): ?>
                                <tr>
                                    <td data-label="Produto"><?php echo htmlspecialchars($aluguel['produto'] ?? 'Produto #' . $aluguel['produto_id']); ?></td>
                                    <td data-label="Quantidade"><?php echo htmlspecialchars($aluguel['quantidade']); ?></td>
                                    <td data-label="Data de Aluguel"><?php echo date('d/m/Y H:i', strtotime($aluguel['data_aluguel'])); ?></td>
                                    <td data-label="Status"><?php echo htmlspecialchars($aluguel['status']); ?></td>
                                    <td data-label="Ações">
                                        <button class="action-button" onclick="openModal(<?php echo $aluguel['id']; ?>, '<?php echo htmlspecialchars($aluguel['url_download'] ?? ''); ?>', '<?php echo htmlspecialchars($aluguel['site'] ?? ''); ?>', '<?php echo htmlspecialchars($aluguel['usuario'] ?? ''); ?>', '<?php echo htmlspecialchars($aluguel['senha'] ?? ''); ?>')">Ações</button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="aluguel_id" value="<?php echo $aluguel['id']; ?>">
                                            <button type="submit" name="cancelar_aluguel" class="cancel-button" onclick="return confirm('Tem certeza que deseja cancelar este aluguel?')">Cancelar</button>
                                        </form>
                                        <button class="renew-button" onclick="if(confirm('Deseja renovar este aluguel?')) openPixModal(<?php echo $aluguel['id']; ?>, '<?php echo htmlspecialchars($aluguel['chave_pix'] ?? ''); ?>')">Renovar</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <section class="products-section" aria-label="Produtos Comprados">
                <h2>Produtos Comprados</h2>
                <?php if (empty($compras)): ?>
                    <p class="no-data">Nenhuma compra registrada.</p>
                <?php else: ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Produto</th>
                                <th>Quantidade</th>
                                <th>Data de Compra</th>
                                <th>Valor Total (R$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($compras as $compra): ?>
                                <tr>
                                    <td data-label="Produto"><?php echo htmlspecialchars($compra['produto'] ?? 'Produto #' . $compra['produto_id']); ?></td>
                                    <td data-label="Quantidade"><?php echo htmlspecialchars($compra['quantidade']); ?></td>
                                    <td data-label="Data de Compra"><?php echo date('d/m/Y H:i', strtotime($compra['data_compra'])); ?></td>
                                    <td data-label="Valor Total"><?php echo number_format($compra['valor_total'], 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <!-- Modal para ações -->
        <div id="actionModal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closeModal()">×</span>
                <h3>Detalhes do Aluguel</h3>
                <p>Adicionado pelo administrador</p>
                <div id="modal-link-section" style="display: none;">
                    <label for="modal-link">Link de Download:</label>
                    <input type="text" id="modal-link" readonly>
                    <button class="copy-button" onclick="copyLink()">Copiar</button>
                </div>
                <div id="modal-download-section" style="display: none;">
                    <a id="modal-download-link" href="" download><button class="download-button">Download</button></a>
                </div>
                <div id="modal-site-section" style="display: none;">
                    <a id="modal-site-link" href="" target="_blank"><button class="site-button">Acessar Site</button></a>
                    <div class="credentials">
                        <p><span>Usuário:</span> <span id="modal-usuario"></span></p>
                        <p><span>Senha:</span> <span id="modal-senha"></span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para chave Pix -->
        <div id="pixModal" class="modal">
            <div class="modal-content">
                <span class="close-button" onclick="closePixModal()">×</span>
                <h3>Renovar Aluguel</h3>
                <p>Utilize a chave Pix abaixo para realizar o pagamento da renovação.</p>
                <div id="modal-pix-section">
                    <label for="modal-pix">Chave Pix:</label>
                    <input type="text" id="modal-pix" readonly>
                    <button class="pix-button" onclick="copyPix()">Copiar</button>
                </div>
                <form method="POST" id="renovacaoForm">
                    <input type="hidden" name="aluguel_id" id="pix-aluguel-id">
                    <button type="submit" name="confirmar_renovacao" class="renew-button">Confirmar Renovação</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        function openModal(aluguelId, urlDownload, site, usuario, senha) {
            const modal = document.getElementById('actionModal');
            const linkSection = document.getElementById('modal-link-section');
            const downloadSection = document.getElementById('modal-download-section');
            const siteSection = document.getElementById('modal-site-section');
            const linkInput = document.getElementById('modal-link');
            const downloadLink = document.getElementById('modal-download-link');
            const siteLink = document.getElementById('modal-site-link');
            const usuarioSpan = document.getElementById('modal-usuario');
            const senhaSpan = document.getElementById('modal-senha');

            if (urlDownload) {
                linkSection.style.display = 'block';
                linkInput.value = urlDownload;
                downloadSection.style.display = 'block';
                downloadLink.href = urlDownload;
            } else {
                linkSection.style.display = 'none';
                downloadSection.style.display = 'none';
            }

            if (site) {
                siteSection.style.display = 'block';
                siteLink.href = site;
                usuarioSpan.textContent = usuario || 'Não fornecido';
                senhaSpan.textContent = senha || 'Não fornecido';
            } else {
                siteSection.style.display = 'none';
            }

            modal.style.display = 'flex';
        }

        function closeModal() {
            const modal = document.getElementById('actionModal');
            modal.style.display = 'none';
        }

        function copyLink() {
            const linkInput = document.getElementById('modal-link');
            linkInput.select();
            try {
                document.execCommand('copy');
                alert('Link copiado com sucesso!');
            } catch (err) {
                alert('Erro ao copiar o link.');
            }
        }

        function openPixModal(aluguelId, chavePix) {
            const modal = document.getElementById('pixModal');
            const pixInput = document.getElementById('modal-pix');
            const aluguelIdInput = document.getElementById('pix-aluguel-id');

            if (!chavePix) {
                pixInput.value = 'Nenhuma chave Pix fornecida. Contate o administrador.';
                document.querySelector('#modal-pix-section .pix-button').style.display = 'none';
            } else {
                // Formatar a chave Pix
                let formattedPix = chavePix;
                let label = 'Chave Pix';
                if (/^\d{11}$/.test(chavePix)) {
                    // CPF: XXX.XXX.XXX-XX
                    formattedPix = chavePix.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                    label = 'CPF';
                } else if (/^\d{14}$/.test(chavePix)) {
                    // CNPJ: XX.XXX.XXX/XXXX-XX
                    formattedPix = chavePix.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
                    label = 'CNPJ';
                } else if (chavePix.includes('@')) {
                    // E-mail
                    label = 'E-mail';
                } else {
                    // Chave aleatória
                    label = 'Chave Aleatória';
                }
                pixInput.value = `${label}: ${formattedPix}`;
                document.querySelector('#modal-pix-section .pix-button').style.display = 'inline-block';
            }

            aluguelIdInput.value = aluguelId;
            modal.style.display = 'flex';
        }

        function closePixModal() {
            const modal = document.getElementById('pixModal');
            modal.style.display = 'none';
        }

        function copyPix() {
            const pixInput = document.getElementById('modal-pix');
            const pixValue = pixInput.value.replace(/^(CPF|CNPJ|E-mail|Chave Aleatória): /, '');
            const tempInput = document.createElement('input');
            tempInput.value = pixValue;
            document.body.appendChild(tempInput);
            tempInput.select();
            try {
                document.execCommand('copy');
                alert('Chave Pix copiada com sucesso!');
            } catch (err) {
                alert('Erro ao copiar a chave Pix.');
            }
            document.body.removeChild(tempInput);
        }

        // Fechar o modal ao clicar fora
        window.onclick = function(event) {
            const actionModal = document.getElementById('actionModal');
            const pixModal = document.getElementById('pixModal');
            if (event.target === actionModal) {
                closeModal();
            }
            if (event.target === pixModal) {
                closePixModal();
            }
        };
    </script>
</body>
</html>
```