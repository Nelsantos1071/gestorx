<?php
session_start();
require_once '../includes/db.php';

// Verifica se está logado como admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

// Inicializa CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$mensagem = "";

// Verifica se $pdo está definido
if (!isset($pdo)) {
    die("Erro: Conexão com o banco de dados não foi estabelecida. Verifique o arquivo db.php.");
}

// Adicionar novo servidor
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensagem = "Erro: Token CSRF inválido.";
    } else {
        $marca = trim($_POST['marca']);
        $mensal = floatval(str_replace(',', '.', str_replace('.', '', $_POST['plano_mensal'])));
        $trimestral = floatval(str_replace(',', '.', str_replace('.', '', $_POST['plano_trimestral'])));
        $semestral = floatval(str_replace(',', '.', str_replace('.', '', $_POST['plano_semestral'])));
        $anual = floatval(str_replace(',', '.', str_replace('.', '', $_POST['plano_anual'])));

        if ($marca && $mensal >= 0 && $trimestral >= 0 && $semestral >= 0 && $anual >= 0) {
            try {
                $stmt = $pdo->prepare("INSERT INTO servidores (marca, plano_mensal, plano_trimestral, plano_semestral, plano_anual) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$marca, $mensal, $trimestral, $semestral, $anual]);
                $mensagem = "Servidor cadastrado com sucesso!";
                header("Location: servidores.php");
                exit;
            } catch (PDOException $e) {
                $mensagem = "Erro ao cadastrar servidor: " . $e->getMessage();
            }
        } else {
            $mensagem = "Por favor, preencha todos os campos corretamente.";
        }
    }
}

// Editar servidor
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensagem = "Erro: Token CSRF inválido.";
    } else {
        $id = intval($_POST['id']);
        $marca = trim($_POST['marca']);
        $mensal = floatval(str_replace(',', '.', str_replace('.', '', $_POST['plano_mensal'])));
        $trimestral = floatval(str_replace(',', '.', str_replace('.', '', $_POST['plano_trimestral'])));
        $semestral = floatval(str_replace(',', '.', str_replace('.', '', $_POST['plano_semestral'])));
        $anual = floatval(str_replace(',', '.', str_replace('.', '', $_POST['plano_anual'])));

        if ($marca && $mensal >= 0 && $trimestral >= 0 && $semestral >= 0 && $anual >= 0) {
            try {
                $stmt = $pdo->prepare("UPDATE servidores SET marca=?, plano_mensal=?, plano_trimestral=?, plano_semestral=?, plano_anual=? WHERE id=?");
                $stmt->execute([$marca, $mensal, $trimestral, $semestral, $anual, $id]);
                $mensagem = "Servidor atualizado com sucesso!";
                header("Location: servidores.php");
                exit;
            } catch (PDOException $e) {
                $mensagem = "Erro ao atualizar servidor: " . $e->getMessage();
            }
        } else {
            $mensagem = "Por favor, preencha todos os campos corretamente.";
        }
    }
}

// Excluir servidor
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensagem = "Erro: Token CSRF inválido.";
    } else {
        $id = intval($_POST['id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM servidores WHERE id=?");
            $stmt->execute([$id]);
            $mensagem = "Servidor excluído com sucesso!";
            header("Location: servidores.php");
            exit;
        } catch (PDOException $e) {
            $mensagem = "Erro ao excluir servidor: " . $e->getMessage();
        }
    }
}

// Buscar todos os servidores
try {
    $stmt = $pdo->query("SELECT * FROM servidores ORDER BY criado_em DESC");
    $servidores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensagem = "Erro ao buscar servidores: " . $e->getMessage();
    $servidores = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestor de Servidores</title>
    <style>
        :root {
            --primary-color: #293a5e;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --bg-color: #f9f9f9;
            --card-bg: #ffffff;
            --text-color: #333;
            --shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: clamp(1.5rem, 5vw, 1.8rem);
            padding: 0 1rem;
        }

        main.content {
            margin-left: 220px;
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .add-btn {
            display: block;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 1rem;
            margin: 0 auto 1.5rem;
            max-width: 200px;
            text-align: center;
            transition: background 0.3s ease;
        }

        .add-btn:hover {
            background: #293a5e;
        }

        .alert {
            background: #f8d7da;
            padding: 10px;
            margin-bottom: 1.5rem;
            border-radius: 5px;
            color: #721c24;
            text-align: center;
            max-width: 960px;
            margin-left: auto;
            margin-right: auto;
        }

        form {
            background: var(--card-bg);
            padding: 15px;
            border-radius: 5px;
            box-shadow: var(--shadow);
            max-width: 400px;
            margin: 0 auto;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }

        input[type="text"] {
            width: 100%;
            padding: 7px;
            margin-top: 5px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 1rem;
        }

        button {
            margin-top: 15px;
            padding: 10px 20px;
            background: var(--primary-color);
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 3px;
            width: 100%;
            font-size: 1rem;
        }

        button:hover {
            background: #293a5e;
        }

        table {
            width: 100%;
            max-width: 960px;
            border-collapse: collapse;
            background: var(--card-bg);
            box-shadow: var(--shadow);
            border-radius: 5px;
            margin: 0 auto;
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #ccc;
            text-align: left;
        }

        thead {
            background: var(--primary-color);
            color: white;
        }

        .actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .actions button {
            margin: 0;
            padding: 6px 12px;
            font-size: 0.9rem;
            border-radius: 3px;
            cursor: pointer;
            text-align: center;
            min-width: 80px;
        }

        .actions .action-btn {
            background: var(--success-color);
        }

        .actions .action-btn:hover {
            background: #1e7e34;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            padding: 15px;
        }

        .modal-content {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 5px;
            width: 100%;
            max-width: 400px;
            position: relative;
            box-shadow: var(--shadow);
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            border: none;
            background: none;
            color: var(--text-color);
        }

        .close-btn:hover {
            color: var(--primary-color);
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            main.content {
                margin-left: 200px;
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            main.content {
                margin-left: 0;
                padding: 1rem;
            }

            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead tr {
                display: none;
            }

            tbody tr {
                margin-bottom: 15px;
                background: var(--card-bg);
                border-radius: 5px;
                box-shadow: var(--shadow);
                padding: 10px;
            }

            tbody td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: right;
                font-size: 0.9rem;
            }

            tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                top: 10px;
                width: calc(50% - 20px);
                font-weight: bold;
                text-transform: uppercase;
                font-size: 0.75rem;
                color: #555;
            }

            .actions {
                justify-content: flex-end;
                gap: 8px;
            }

            .actions button {
                min-width: 70px;
                padding: 5px 10px;
                font-size: 0.85rem;
            }

            form, .modal-content {
                max-width: 100%;
                padding: 15px;
            }

            .add-btn {
                max-width: 100%;
                padding: 10px;
            }

            .alert {
                margin: 0 0.5rem 1.5rem;
                max-width: 100%;
            }

            table {
                max-width: 100%;
                margin: 0 0.5rem;
            }
        }

        @media (max-width: 576px) {
            main.content {
                padding: 0.5rem;
            }

            h1 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }

            .add-btn {
                font-size: 0.9rem;
                padding: 8px;
            }

            form, .modal-content {
                padding: 10px;
            }

            input[type="text"] {
                font-size: 0.9rem;
                padding: 6px;
            }

            button {
                font-size: 0.9rem;
                padding: 8px;
            }

            .actions button {
                font-size: 0.8rem;
                padding: 4px 8px;
                min-width: 60px;
            }

            tbody td {
                font-size: 0.85rem;
                padding-left: 40%;
            }

            tbody td::before {
                font-size: 0.7rem;
                width: calc(40% - 20px);
            }
        }
    </style>
</head>
<body>
    
    <?php include __DIR__ . '/assets/sidebar.php'; ?>
    
    <main class="content">
        <h1>Gestor de Servidores</h1>

        <!-- Exibir mensagens -->
        <?php if ($mensagem): ?>
            <div class="alert"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <!-- Botão para abrir o modal de adição -->
        <button class="add-btn" id="openAddModal">Adicionar Novo Servidor</button>

        <!-- Modal para adição -->
        <div class="modal" id="addModal" role="dialog" aria-labelledby="addModalLabel">
            <div class="modal-content">
                <button class="close-btn" id="closeAddModal" aria-label="Fechar modal">×</button>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="add" value="1">
                    <h3 id="addModalLabel">Adicionar Novo Servidor</h3>
                    <label for="marca">Marca do Servidor:</label>
                    <input type="text" name="marca" id="marca" required>
                    <label for="plano_mensal">Plano Mensal (R$):</label>
                    <input type="text" name="plano_mensal" id="plano_mensal" required>
                    <label for="plano_trimestral">Plano Trimestral (R$):</label>
                    <input type="text" name="plano_trimestral" id="plano_trimestral" required>
                    <label for="plano_semestral">Plano Semestral (R$):</label>
                    <input type="text" name="plano_semestral" id="plano_semestral" required>
                    <label for="plano_anual">Plano Anual (R$):</label>
                    <input type="text" name="plano_anual" id="plano_anual" required>
                    <button type="submit">Cadastrar</button>
                </form>
            </div>
        </div>

        <!-- Tabela de servidores -->
        <table>
            <thead>
                <tr>
                    <th>Marca</th>
                    <th>Mensal (R$)</th>
                    <th>Trimestral (R$)</th>
                    <th>Semestral (R$)</th>
                    <th>Anual (R$)</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($servidores): ?>
                    <?php foreach ($servidores as $srv): ?>
                        <tr>
                            <td data-label="Marca"><?= htmlspecialchars($srv['marca']) ?></td>
                            <td data-label="Mensal"><?= number_format($srv['plano_mensal'], 2, ',', '.') ?></td>
                            <td data-label="Trimestral"><?= number_format($srv['plano_trimestral'], 2, ',', '.') ?></td>
                            <td data-label="Semestral"><?= number_format($srv['plano_semestral'], 2, ',', '.') ?></td>
                            <td data-label="Anual"><?= number_format($srv['plano_anual'], 2, ',', '.') ?></td>
                            <td data-label="Ações" class="actions">
                                <button 
                                    class="action-btn" 
                                    data-id="<?= $srv['id'] ?>"
                                    data-marca="<?= htmlspecialchars($srv['marca'], ENT_QUOTES) ?>"
                                    data-mensal="<?= number_format($srv['plano_mensal'], 2, ',', '.') ?>"
                                    data-trimestral="<?= number_format($srv['plano_trimestral'], 2, ',', '.') ?>"
                                    data-semestral="<?= number_format($srv['plano_semestral'], 2, ',', '.') ?>"
                                    data-anual="<?= number_format($srv['plano_anual'], 2, ',', '.') ?>"
                                >Ações</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#777;">Nenhum servidor cadastrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Modal para ações (editar, excluir) -->
        <div class="modal" id="actionModal" role="dialog" aria-labelledby="actionModalLabel">
            <div class="modal-content">
                <button class="close-btn" id="closeActionModal" aria-label="Fechar modal">×</button>
                <h3 id="actionModalLabel">Gerenciar Servidor</h3>
                <!-- Formulário para edição -->
                <form method="post" id="editForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="edit" value="1">
                    <input type="hidden" name="id" id="actionId">
                    <label for="actionMarca">Marca do Servidor:</label>
                    <input type="text" name="marca" id="actionMarca" required>
                    <label for="actionMensal">Plano Mensal (R$):</label>
                    <input type="text" name="plano_mensal" id="actionMensal" required>
                    <label for="actionTrimestral">Plano Trimestral (R$):</label>
                    <input type="text" name="plano_trimestral" id="actionTrimestral" required>
                    <label for="actionSemestral">Plano Semestral (R$):</label>
                    <input type="text" name="plano_semestral" id="actionSemestral" required>
                    <label for="actionAnual">Plano Anual (R$):</label>
                    <input type="text" name="plano_anual" id="actionAnual" required>
                    <button type="submit" style="background: var(--primary-color);">Salvar Alterações</button>
                </form>
                <!-- Formulário para exclusão -->
                <form method="post" id="deleteForm">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="delete" value="1">
                    <input type="hidden" name="id" id="deleteId">
                    <button type="submit" style="background: var(--danger-color); margin-top: 10px;" onclick="return confirm('Tem certeza que deseja excluir este servidor?')">Excluir Servidor</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Função para formatar o campo de moeda
        function formatarMoeda(input) {
            let value = input.value.replace(/\D/g, '');
            if (value === '') return;
            value = (parseInt(value) / 100).toFixed(2);
            value = value.replace('.', ',');
            value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            input.value = value;
        }

        // Modal de adição
        const addModal = document.getElementById('addModal');
        const openAddModalBtn = document.getElementById('openAddModal');
        const closeAddModalBtn = document.getElementById('closeAddModal');
        const planoMensalAdd = document.getElementById('plano_mensal');
        const planoTrimestralAdd = document.getElementById('plano_trimestral');
        const planoSemestralAdd = document.getElementById('plano_semestral');
        const planoAnualAdd = document.getElementById('plano_anual');

        openAddModalBtn.addEventListener('click', () => {
            addModal.style.display = 'flex';
            document.getElementById('marca').focus();
            planoMensalAdd.value = '';
            planoTrimestralAdd.value = '';
            planoSemestralAdd.value = '';
            planoAnualAdd.value = '';
        });

        closeAddModalBtn.addEventListener('click', () => {
            addModal.style.display = 'none';
        });

        // Modal de ações
        const actionModal = document.getElementById('actionModal');
        const closeActionModalBtn = document.getElementById('closeActionModal');
        const actionButtons = document.querySelectorAll('.action-btn');
        const actionId = document.getElementById('actionId');
        const actionMarca = document.getElementById('actionMarca');
        const actionMensal = document.getElementById('actionMensal');
        const actionTrimestral = document.getElementById('actionTrimestral');
        const actionSemestral = document.getElementById('actionSemestral');
        const actionAnual = document.getElementById('actionAnual');
        const deleteId = document.getElementById('deleteId');

        actionButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                actionId.value = btn.getAttribute('data-id');
                actionMarca.value = btn.getAttribute('data-marca');
                actionMensal.value = btn.getAttribute('data-mensal');
                actionTrimestral.value = btn.getAttribute('data-trimestral');
                actionSemestral.value = btn.getAttribute('data-semestral');
                actionAnual.value = btn.getAttribute('data-anual');
                deleteId.value = btn.getAttribute('data-id');
                actionModal.style.display = 'flex';
                actionMarca.focus();
            });
        });

        closeActionModalBtn.addEventListener('click', () => {
            actionModal.style.display = 'none';
        });

        // Fechar modais ao clicar fora
        window.addEventListener('click', e => {
            if (e.target === addModal) {
                addModal.style.display = 'none';
            }
            if (e.target === actionModal) {
                actionModal.style.display = 'none';
            }
        });

        // Fechar modais com tecla Esc
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                addModal.style.display = 'none';
                actionModal.style.display = 'none';
            }
        });

        // Aplicar formatação de moeda aos campos de preço
        [planoMensalAdd, planoTrimestralAdd, planoSemestralAdd, planoAnualAdd, 
         actionMensal, actionTrimestral, actionSemestral, actionAnual].forEach(input => {
            input.addEventListener('input', () => formatarMoeda(input));
        });
    </script>
</body>
</html>