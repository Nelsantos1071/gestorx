<?php
session_start();
require_once '../includes/db.php';

if (empty($_SESSION['cliente_id']) || !filter_var($_SESSION['cliente_id'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]])) {
    header('Location: login.php');
    exit;
}
$clienteId = (int)$_SESSION['cliente_id'];

// Busca templates do cliente
$stmt = $pdo->prepare("SELECT id, titulo, template_text, updated_at FROM template_users WHERE cliente_id = ? ORDER BY updated_at DESC");
$stmt->execute([$clienteId]);
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Templates do Cliente</title>
<style>
/* --- Estilos do modal e tabela --- */
body { font-family: Arial,sans-serif; margin: 2rem auto; max-width: 900px; background:#f0f0f0; color:#333; }
h2 { color: #293a5e; text-align:center; }
table { width:100%; border-collapse: collapse; margin-bottom:2rem;}
th, td {padding:0.6rem; border:1px solid #ccc; text-align:left;}
th {background:#293a5e; color:#fff;}
button {background:#293a5e; color:#fff; border:none; padding:0.5rem 1rem; border-radius:4px; cursor:pointer; transition:background 0.3s;}
button:hover {background:#1f2c4a;}
.modal {display:none; position:fixed; z-index:10; left:0;top:0;right:0;bottom:0; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;}
.modal.active {display:flex;}
.modal-content {background:#fff; padding:1.5rem; border-radius:6px; width:90%; max-width:500px; max-height:90vh; overflow-y:auto;}
.modal-content h3 {margin-top:0; color:#293a5e;}
.modal-content label {font-weight:bold; margin-top:1rem; display:block;}
.modal-content input[type="text"], .modal-content textarea {width:100%; padding:0.5rem; margin-top:0.25rem; border-radius:4px; border:1px solid #ccc; font-family: monospace;}
.modal-content textarea {min-height:120px; resize:vertical; white-space: pre-wrap;}
#marcadores button {margin:0.2rem 0.3rem 0.2rem 0; padding:0.3rem 0.7rem; font-size:0.9rem; border-radius:4px; border:1px solid #293a5e; background:#f0f0f0; color:#293a5e;}
#marcadores button:hover {background:#293a5e; color:#fff;}
.modal-buttons {margin-top:1rem; text-align:right;}
.modal-buttons button {margin-left:0.5rem;}
#response-message, #response-edit-message {margin-top:1rem; font-weight:bold; color:red;}

/* Novo estilo para fixar botões Editar e Excluir lado a lado */
table td:nth-child(5) { /* Seleciona a coluna Ações */
  display: flex;
  gap: 0.5rem; /* Espaçamento entre os botões */
  justify-content: flex-start; /* Alinha os botões à esquerda */
  align-items: center; /* Centraliza verticalmente */
}
table td:nth-child(5) button {
  flex: 0 0 auto; /* Garante que os botões não se estiquem */
  min-width: 80px; /* Largura mínima para consistência */
  text-align: center;
}

/* Ajustes responsivos */
@media (max-width: 768px) {
  table td:nth-child(5) {
    flex-direction: row; /* Mantém lado a lado em mobile */
    gap: 0.3rem; /* Reduz espaçamento em mobile */
  }
  table td:nth-child(5) button {
    min-width: 70px; /* Ajusta largura mínima em mobile */
    padding: 0.4rem 0.8rem; /* Ajusta padding em mobile */
    font-size: 0.9rem; /* Reduz tamanho da fonte */
  }
}
</style>
</head>
<body>

<?php include __DIR__ . '/assets/sidebar.php'; ?>

<h2>Templates do Cliente</h2>

<button id="btn-add-template">Adicionar Template</button>

<table aria-label="Lista de templates do cliente">
<thead>
<tr>
<th>ID</th>
<th>Título</th>
<th>Template</th>
<th>Atualizado em</th>
<th>Ações</th>
</tr>
</thead>
<tbody>
<?php if ($templates): ?>
  <?php foreach ($templates as $t): ?>
    <tr data-id="<?= htmlspecialchars($t['id']) ?>">
      <td><?= htmlspecialchars($t['id']) ?></td>
      <td><?= htmlspecialchars($t['titulo']) ?></td>
      <td>
        <span class="template-summary" data-full="<?= htmlspecialchars($t['template_text'], ENT_QUOTES) ?>">
          <?= htmlspecialchars(mb_strimwidth($t['template_text'], 0, 50, '...')) ?>
        </span>
        <button class="btn-view-template" type="button" aria-label="Ver template completo do <?= htmlspecialchars($t['titulo'], ENT_QUOTES) ?>">Ver mais</button>
      </td>
      <td><?= htmlspecialchars($t['updated_at']) ?></td>
      <td>
        <button class="btn-edit" 
          data-id="<?= htmlspecialchars($t['id']) ?>" 
          data-titulo="<?= htmlspecialchars($t['titulo'], ENT_QUOTES) ?>" 
          data-template="<?= htmlspecialchars($t['template_text'], ENT_QUOTES) ?>">Editar</button>
        <button class="btn-delete" data-id="<?= htmlspecialchars($t['id']) ?>">Excluir</button>
      </td>
    </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr><td colspan="5" style="text-align:center;">Nenhum template encontrado.</td></tr>
<?php endif; ?>
</tbody>
</table>

<!-- Modal Adicionar Template -->
<div id="modal-template" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
  <div class="modal-content">
    <h3 id="modal-title">Adicionar Novo Template</h3>
    <form id="form-add-template" method="post" action="salvar_template.php" novalidate>
      <label for="titulo">Título</label>
      <input type="text" id="titulo" name="titulo" required />

      <label>Marcadores disponíveis (clique para inserir):</label>
      <div id="marcadores" aria-label="Marcadores de texto para inserir">
        <button type="button" data-marcador="${nome}">${nome}</button>
        <button type="button" data-marcador="${user_id}">${user_id}</button>
        <button type="button" data-marcador="${valor_1}">${valor_1}</button>
        <button type="button" data-marcador="${valor_2}">${valor_2}</button>
        <button type="button" data-marcador="${valor_3}">${valor_3}</button>
        <button type="button" data-marcador="${valor_4}">${valor_4}</button>
        <button type="button" data-marcador="${cliente_id}">${cliente_id}</button>
        <button type="button" data-marcador="${pix}">${pix}</button>
      </div>

      <label for="template_text">Template</label>
      <textarea id="template_text" name="template_text" required></textarea>

      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>" />
      <input type="hidden" name="cliente_id" value="<?= $clienteId ?>" />

      <div class="modal-buttons">
        <button type="button" id="btn-cancel-modal">Cancelar</button>
        <button type="submit">Salvar</button>
      </div>
    </form>
    <div id="response-message" role="alert" aria-live="polite"></div>
  </div>
</div>

<!-- Modal Editar Template -->
<div id="modal-edit-template" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-edit-title">
  <div class="modal-content">
    <h3 id="modal-edit-title">Editar Template</h3>
    <form id="form-edit-template" method="post" action="editar_template.php" novalidate>
      <input type="hidden" id="edit_id" name="id" />
      <label for="edit_titulo">Título</label>
      <input type="text" id="edit_titulo" name="titulo" required />

      <label>Marcadores disponíveis (clique para inserir):</label>
      <div id="marcadores-edit" aria-label="Marcadores de texto para inserir">
        <button type="button" data-marcador="${nome}">${nome}</button>
        <button type="button" data-marcador="${user_id}">${user_id}</button>
        <button type="button" data-marcador="${valor_1}">${valor_1}</button>
        <button type="button" data-marcador="${valor_2}">${valor_2}</button>
        <button type="button" data-marcador="${valor_3}">${valor_3}</button>
        <button type="button" data-marcador="${valor_4}">${valor_4}</button>
        <button type="button" data-marcador="${cliente_id}">${cliente_id}</button>
        <button type="button" data-marcador="${pix}">${pix}</button>
      </div>

      <label for="edit_template_text">Template</label>
      <textarea id="edit_template_text" name="template_text" required></textarea>

      <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>" />
      <input type="hidden" name="cliente_id" value="<?= $clienteId ?>" />

      <div class="modal-buttons">
        <button type="button" id="btn-cancel-edit-modal">Cancelar</button>
        <button type="submit">Salvar Alterações</button>
      </div>
    </form>
    <div id="response-edit-message" role="alert" aria-live="polite"></div>
  </div>
</div>

<script>
// Funções para abrir/fechar modais
function openModal(modal) {
  modal.classList.add('active');
  // Foco no primeiro input
  const input = modal.querySelector('input, textarea, button');
  if (input) input.focus();
}
function closeModal(modal) {
  modal.classList.remove('active');
}

// Insere marcador no textarea
function insertAtCursor(textarea, text) {
  const start = textarea.selectionStart;
  const end = textarea.selectionEnd;
  const before = textarea.value.substring(0, start);
  const after = textarea.value.substring(end);
  textarea.value = before + text + after;
  textarea.selectionStart = textarea.selectionEnd = start + text.length;
  textarea.focus();
}

// Marcadores no modal adicionar
document.querySelectorAll('#marcadores button').forEach(btn => {
  btn.addEventListener('click', () => {
    const textarea = document.getElementById('template_text');
    insertAtCursor(textarea, btn.dataset.marcador);
  });
});
// Marcadores no modal editar
document.querySelectorAll('#marcadores-edit button').forEach(btn => {
  btn.addEventListener('click', () => {
    const textarea = document.getElementById('edit_template_text');
    insertAtCursor(textarea, btn.dataset.marcador);
  });
});

// Botão abrir modal adicionar
document.getElementById('btn-add-template').addEventListener('click', () => {
  document.getElementById('form-add-template').reset();
  document.getElementById('response-message').textContent = '';
  openModal(document.getElementById('modal-template'));
});

// Botão cancelar modal adicionar
document.getElementById('btn-cancel-modal').addEventListener('click', () => {
  closeModal(document.getElementById('modal-template'));
});

// Botão cancelar modal editar
document.getElementById('btn-cancel-edit-modal').addEventListener('click', () => {
  closeModal(document.getElementById('modal-edit-template'));
});

// Mostrar template completo no modal (usar alert simples aqui para facilitar)
document.querySelectorAll('.btn-view-template').forEach(btn => {
  btn.addEventListener('click', () => {
    const fullText = btn.previousElementSibling.dataset.full || '';
    alert(fullText);
  });
});

// Abrir modal editar e preencher campos
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('edit_id').value = btn.dataset.id;
    document.getElementById('edit_titulo').value = btn.dataset.titulo;
    document.getElementById('edit_template_text').value = btn.dataset.template;
    document.getElementById('response-edit-message').textContent = '';
    openModal(document.getElementById('modal-edit-template'));
  });
});

// Excluir template com confirmação
document.querySelectorAll('.btn-delete').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!confirm('Tem certeza que deseja excluir este template? Esta ação não pode ser desfeita.')) return;

    const id = btn.dataset.id;
    try {
      const res = await fetch('excluir_template.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          id: id,
          csrf_token: '<?= $csrf_token ?>'
        }),
        credentials: 'same-origin'
      });
      const data = await res.json();
      if (data.success) {
        const tr = document.querySelector(`tr[data-id="${id}"]`);
        if (tr) tr.remove();
        alert('Template excluído com sucesso!');
      } else {
        alert('Erro: ' + (data.message || 'Não foi possível excluir.'));
      }
    } catch (e) {
      alert('Erro na requisição.');
      console.error(e);
    }
  });
});

// Enviar formulário adicionar via AJAX
document.getElementById('form-add-template').addEventListener('submit', async e => {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const responseMsg = document.getElementById('response-message');
  responseMsg.style.color = 'red';
  responseMsg.textContent = '';

  try {
    const res = await fetch(form.action, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    });
    const data = await res.json();

    if (data.success) {
      responseMsg.style.color = 'green';
      responseMsg.textContent = 'Template adicionado com sucesso!';
      // Opcional: atualizar tabela sem reload (recarregar página é mais simples)
      setTimeout(() => {
        location.reload();
      }, 1200);
    } else {
      responseMsg.textContent = data.message || 'Erro ao adicionar template.';
    }
  } catch (err) {
    responseMsg.textContent = 'Erro na requisição. Tente novamente.';
    console.error(err);
  }
});

// Enviar formulário editar via AJAX
document.getElementById('form-edit-template').addEventListener('submit', async e => {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const responseMsg = document.getElementById('response-edit-message');
  responseMsg.style.color = 'red';
  responseMsg.textContent = '';

  try {
    const res = await fetch(form.action, {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    });
    const data = await res.json();

    if (data.success) {
      responseMsg.style.color = 'green';
      responseMsg.textContent = 'Template atualizado com sucesso!';

      // Atualiza linha da tabela
      const row = document.querySelector(`tr[data-id="${formData.get('id')}"]`);
      if (row) {
        row.querySelector('td:nth-child(2)').textContent = formData.get('titulo');
        const summarySpan = row.querySelector('td:nth-child(3) > .template-summary');
        summarySpan.textContent = formData.get('template_text').length > 50
          ? formData.get('template_text').substring(0, 50) + '...'
          : formData.get('template_text');
        summarySpan.dataset.full = formData.get('template_text');

        // Atualiza os dados do botão editar para futuros edits
        const btnEdit = row.querySelector('td:nth-child(5) > .btn-edit');
        btnEdit.dataset.titulo = formData.get('titulo');
        btnEdit.dataset.template = formData.get('template_text');

        if (data.updated_at) {
          row.querySelector('td:nth-child(4)').textContent = data.updated_at;
        }
      }
      setTimeout(() => {
        closeModal(document.getElementById('modal-edit-template'));
      }, 1500);
    } else {
      responseMsg.textContent = data.message || 'Erro ao atualizar template.';
    }
  } catch (err) {
    responseMsg.textContent = 'Erro na requisição. Tente novamente.';
    console.error(err);
  }
});
</script>

</body>
</html>
