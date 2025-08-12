<?php
session_start();
include '../includes/db.php';

$message = '';
$error = '';

// Predefined titles and their default messages with placeholders
$predefined_titles = [
    'Lembrete de Vencimento' => "Olá, seu plano <strong>{nome}</strong> de {duracao} dias na <strong>{marca}</strong> está próximo do vencimento.\nO valor é R${valor_1}.\nPor favor, efetue o pagamento.",
    'Agradecimento' => "Obrigado por escolher a <strong>{marca}</strong>!\nSeu pagamento para o plano <strong>{nome}</strong> (R${valor_1}) foi confirmado.\nAproveite seu acesso por {duracao} dias!"
];

// Fetch plan data for placeholders
try {
    $stmt = $pdo->prepare("SELECT nome, valor_1, duracao FROM planos WHERE cliente_id = :cliente_id");
    $stmt->execute([':cliente_id' => 1]); // Adjust cliente_id as needed
    $plano = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao buscar planos: " . $e->getMessage();
    $plano = ['nome' => 'Completo', 'valor_1' => '10.00', 'duracao' => '30']; // Fallback
}

// Fetch marca from servidores
try {
    $stmt = $pdo->prepare("SELECT marca FROM servidores WHERE cliente_id = :cliente_id");
    $stmt->execute([':cliente_id' => 1]); // Adjust cliente_id as needed
    $servidor = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao buscar servidores: " . $e->getMessage();
    $servidor = ['marca' => 'MatileTV']; // Fallback
}

// Create whatsapp_predefinidas table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_predefinidas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        titulo VARCHAR(255) NOT NULL UNIQUE,
        texto TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    $error = "Erro ao criar tabela whatsapp_predefinidas: " . $e->getMessage();
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_predef'])) {
    $id = (int)($_POST['delete_id'] ?? 0);
    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM whatsapp_predefinidas WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $message = "Mensagem pré-pronta excluída com sucesso!";
        } catch (PDOException $e) {
            $error = "Erro ao excluir mensagem: " . $e->getMessage();
        }
    } else {
        $error = "ID inválido para exclusão.";
    }
}

// Handle inline edit request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_inline'])) {
    $id = (int)($_POST['edit_id'] ?? 0);
    $texto = trim($_POST['texto'] ?? '');
    if ($id && !empty($texto)) {
        try {
            $stmt = $pdo->prepare("UPDATE whatsapp_predefinidas SET texto = :texto WHERE id = :id");
            $stmt->execute([':texto' => $texto, ':id' => $id]);
            $message = "Mensagem pré-pronta atualizada com sucesso!";
        } catch (PDOException $e) {
            $error = "Erro ao atualizar mensagem: " . $e->getMessage();
        }
    } else {
        $error = "Texto ou ID inválido para edição.";
    }
}

// Inserir ou atualizar mensagem pré-pronta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_predef']) || isset($_POST['edit_predef']))) {
    $titulo = trim($_POST['titulo'] ?? '');
    $texto = trim($_POST['texto'] ?? '');
    $id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : null;

    // Debug: Log the received data
    error_log("Received: titulo=$titulo, texto=$texto, id=$id");

    if (empty($titulo) || empty($texto)) {
        $error = "Por favor, preencha todos os campos.";
    } elseif (!array_key_exists($titulo, $predefined_titles)) {
        $error = "Título inválido. Escolha um título da lista.";
    } else {
        try {
            if (isset($_POST['edit_predef']) && $id) {
                $stmt = $pdo->prepare("UPDATE whatsapp_predefinidas SET titulo = :titulo, texto = :texto WHERE id = :id");
                $stmt->execute([':titulo' => $titulo, ':texto' => $texto, ':id' => $id]);
                $message = "Mensagem pré-pronta atualizada com sucesso!";
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM whatsapp_predefinidas WHERE titulo = :titulo AND id != :id");
                $stmt->execute([':titulo' => $titulo, ':id' => $id ?: 0]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Já existe uma mensagem com este título.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO whatsapp_predefinidas (titulo, texto) VALUES (:titulo, :texto)");
                    $stmt->execute([':titulo' => $titulo, ':texto' => $texto]);
                    $message = "Mensagem pré-pronta adicionada com sucesso!";
                }
            }
        } catch (PDOException $e) {
            $error = "Erro ao salvar mensagem: " . $e->getMessage();
        }
    }
}

// Buscar templates cadastrados
try {
    $stmt = $pdo->prepare("SELECT id, cliente_id, template_text, updated_at, 'Template' AS tipo FROM whatsapp_template");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao buscar templates: " . $e->getMessage();
    $templates = [];
}

// Buscar mensagens pré-prontas cadastradas
try {
    $stmt2 = $pdo->prepare("SELECT id, NULL AS cliente_id, titulo, texto, created_at AS updated_at, 'Pré-Pronta' AS tipo FROM whatsapp_predefinidas");
    $stmt2->execute();
    $predefs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao buscar mensagens pré-prontas: " . $e->getMessage();
    $predefs = [];
}

// Garantir que as variáveis estejam definidas
$predefs = $predefs ?? [];
$templates = $templates ?? [];

// Normalizar dados para um único array
$mensagens = [];
foreach ($templates as $t) {
    $mensagens[] = [
        'id' => $t['id'],
        'cliente_id' => $t['cliente_id'],
        'titulo' => 'Template',
        'texto' => $t['template_text'],
        'updated_at' => $t['updated_at'],
        'tipo' => $t['tipo'],
    ];
}
foreach ($predefs as $p) {
    $mensagens[] = $p;
}

// Ordenar por updated_at decrescente
usort($mensagens, function($a, $b) {
    return strtotime($b['updated_at']) <=> strtotime($a['updated_at']);
});
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Modelos de Mensagem para WhatsApp - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #007BFF;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #e8f0fe;
        }
        a.button, button.button {
            display: inline-block;
            margin: 5px;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        .add-button {
            background-color: #28a745;
        }
        .add-button:hover {
            background-color: #218838;
        }
        .edit-button {
            background-color: #ffc107;
        }
        .edit-button:hover {
            background-color: #e0a800;
        }
        .delete-button {
            background-color: #dc3545;
        }
        .delete-button:hover {
            background-color: #c82333;
        }
        .save-button {
            background-color: #17a2b8;
        }
        .save-button:hover {
            background-color: #138496;
        }
        .center {
            text-align: center;
        }
        p {
            text-align: center;
            color: #666;
        }
        .action-buttons {
            display: flex;
            flex-wrap: nowrap;
            gap: 10px;
            align-items: center;
        }
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                display: none;
            }
            tr {
                margin-bottom: 15px;
                background: #fff;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
                border-radius: 8px;
                padding: 10px;
            }
            td {
                position: relative;
                padding-left: 50%;
                text-align: left;
                border-bottom: 1px solid #eee;
                white-space: normal;
                word-wrap: break-word;
            }
            td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                top: 12px;
                font-weight: bold;
                color: #333;
                white-space: nowrap;
            }
            .action-buttons {
                padding-left: 0;
                justify-content: flex-start;
            }
            a.button, button.button {
                width: auto;
                padding: 8px 15px;
                margin: 0;
            }
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0;
            width: 100%; height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            position: relative;
        }
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 10px;
        }
        .close-modal:hover,
        .close-modal:focus {
            color: #000;
        }
        label {
            display: block;
            margin: 15px 0 5px 0;
            font-weight: bold;
        }
        select {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 15px;
        }
        .error-msg {
            color: #b00020;
            text-align: center;
            margin-bottom: 15px;
        }
        .success-msg {
            color: #006400;
            text-align: center;
            margin-bottom: 15px;
        }
        .editable-text {
            cursor: pointer;
            border: 1px solid transparent;
            padding: 5px;
            min-height: 50px;
        }
        .editable-text:hover {
            border: 1px solid #ccc;
            background-color: #f0f0f0;
        }
        .editable-textarea {
            width: 100%;
            min-height: 100px;
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-family: 'Segoe UI', sans-serif;
            font-size: 15px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/assets/sidebar.php'; ?>
    
    <h2>Modelos de Mensagem para WhatsApp</h2>

    <?php if($error): ?>
        <p class="error-msg"><?= htmlspecialchars($error) ?></p>
    <?php elseif($message): ?>
        <p class="success-msg"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <?php if ($mensagens): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>ID Cliente</th>
                    <th>Título</th>
                    <th>Texto</th>
                    <th>Última Atualização</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mensagens as $msg): ?>
                <tr>
                    <td data-label="ID"><?= htmlspecialchars($msg['id']) ?></td>
                    <td data-label="Tipo"><?= htmlspecialchars($msg['tipo']) ?></td>
                    <td data-label="ID Cliente"><?= $msg['cliente_id'] !== null ? htmlspecialchars($msg['cliente_id']) : '-' ?></td>
                    <td data-label="Título"><?= htmlspecialchars($msg['titulo']) ?></td>
                    <td data-label="Texto">
                        <?php if ($msg['tipo'] === 'Pré-Pronta'): ?>
                            <div class="editable-text" data-id="<?= $msg['id'] ?>" onclick="makeEditable(this)">
                                <?= nl2br(htmlspecialchars($msg['texto'])) ?>
                            </div>
                            <form method="post" action="" style="display:none;" class="edit-form" data-id="<?= $msg['id'] ?>">
                                <input type="hidden" name="edit_id" value="<?= $msg['id'] ?>">
                                <textarea name="texto" class="editable-textarea"><?= htmlspecialchars($msg['texto']) ?></textarea>
                                <button type="submit" name="edit_inline" class="button save-button">Salvar</button>
                            </form>
                        <?php else: ?>
                            <?= nl2br(htmlspecialchars($msg['texto'])) ?>
                        <?php endif; ?>
                    </td>
                    <td data-label="Última Atualização"><?= htmlspecialchars($msg['updated_at']) ?></td>
                    <td data-label="Ações">
                        <?php if ($msg['tipo'] === 'Pré-Pronta'): ?>
                            <div class="action-buttons">
                                <button class="button edit-button" data-id="<?= $msg['id'] ?>" data-titulo="<?= htmlspecialchars($msg['titulo'], ENT_QUOTES) ?>" data-texto="<?= htmlspecialchars($msg['texto'], ENT_QUOTES) ?>">Editar</button>
                                <form method="post" action="" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?= $msg['id'] ?>">
                                    <button type="submit" name="delete_predef" class="button delete-button" onclick="return confirm('Confirmar exclusão da mensagem?')">Excluir</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="center">Nenhuma mensagem cadastrada ainda.</p>
    <?php endif; ?>

    <div class="center">
        <button class="button add-button" id="btnOpenModal">Adicionar Mensagem Pré-Pronta</button>
    </div>

    <!-- Modal para adicionar/editar mensagem -->
    <div id="modalAdd" class="modal">
        <div class="modal-content">
            <span class="close-modal" id="closeModal">×</span>
            <h3 id="modalTitle">Adicionar Mensagem Pré-Pronta</h3>
            <form method="post" action="" id="modalForm">
                <input type="hidden" name="edit_id" id="edit_id">
                <label for="titulo">Título:</label>
                <select name="titulo" id="titulo" required onchange="updateTexto()">
                    <option value="">Selecione um título</option>
                    <?php foreach ($predefined_titles as $title => $default_text): ?>
                        <option value="<?= htmlspecialchars($title) ?>"><?= htmlspecialchars($title) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="texto">Texto:</label>
                <textarea name="texto" id="texto" required style="width: 100%; min-height: 100px; padding: 5px; border-radius: 5px; border: 1px solid #ccc; font-family: 'Segoe UI', sans-serif; font-size: 15px;"></textarea>

                <button type="submit" name="add_predef" id="submitButton" class="button add-button">Salvar</button>
            </form>
        </div>
    </div>

    <script>
        // DOM elements
        const modal = document.getElementById('modalAdd');
        const btnOpen = document.getElementById('btnOpenModal');
        const btnClose = document.getElementById('closeModal');
        const modalTitle = document.getElementById('modalTitle');
        const submitButton = document.getElementById('submitButton');
        const editId = document.getElementById('edit_id');
        const tituloSelect = document.getElementById('titulo');
        const modalForm = document.getElementById('modalForm');
        const textoTextarea = document.getElementById('texto');

        // Predefined messages with placeholders replaced
        const predefinedMessages = {
            <?php foreach ($predefined_titles as $title => $text): ?>
                '<?= htmlspecialchars($title, ENT_QUOTES) ?>': `<?= htmlspecialchars(str_replace(
                    ['{nome}', '{valor_1}', '{duracao}', '{marca}'],
                    [$plano['nome'], $plano['valor_1'], $plano['duracao'], $servidor['marca']],
                    $text
                ), ENT_QUOTES) ?>`,
            <?php endforeach; ?>
        };

        function updateTexto() {
            const selectedTitle = tituloSelect.value;
            console.log('Selected title:', selectedTitle);
            const text = predefinedMessages[selectedTitle] || '';
            textoTextarea.value = text;
        }

        // Inline editing
        function makeEditable(element) {
            const id = element.getAttribute('data-id');
            const form = document.querySelector(`.edit-form[data-id="${id}"]`);
            element.style.display = 'none';
            form.style.display = 'block';
            form.querySelector('textarea').focus();
        }

        // Adicionar button handler
        if (btnOpen) {
            btnOpen.addEventListener('click', function() {
                console.log('Adicionar button clicked');
                modalTitle.textContent = 'Adicionar Mensagem Pré-Pronta';
                submitButton.name = 'add_predef';
                editId.value = '';
                tituloSelect.value = '';
                textoTextarea.value = '';
                if (modal) {
                    modal.style.display = 'block';
                } else {
                    console.error('Modal element not found');
                }
            });
        } else {
            console.error('Adicionar button not found');
        }

        // Close modal handler
        if (btnClose) {
            btnClose.addEventListener('click', function() {
                console.log('Close modal clicked');
                if (modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                console.log('Clicked outside modal');
                modal.style.display = 'none';
            }
        });

        // Edit button handler
        document.querySelectorAll('.edit-button').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const titulo = this.getAttribute('data-titulo');
                const texto = this.getAttribute('data-texto');
                console.log('Edit button clicked for ID:', id, 'Titulo:', titulo, 'Texto:', texto);

                modalTitle.textContent = 'Editar Mensagem Pré-Pronta';
                submitButton.name = 'edit_predef';
                editId.value = id;
                tituloSelect.value = titulo;
                textoTextarea.value = texto || predefinedMessages[titulo] || '';
                if (modal) {
                    modal.style.display = 'block';
                } else {
                    console.error('Modal element not found');
                }
            });
        });

        // Form submission handler
        if (modalForm) {
            modalForm.addEventListener('submit', function(event) {
                console.log('Form submitted with Titulo:', tituloSelect.value, 'Texto:', textoTextarea.value);
                // Allow default form submission
            });
        } else {
            console.error('Modal form not found');
        }

        <?php if ($message): ?>
            console.log('Form submitted successfully');
            modal.style.display = 'none';
            tituloSelect.value = '';
            textoTextarea.value = '';
        <?php endif; ?>
    </script>
</body>
</html>