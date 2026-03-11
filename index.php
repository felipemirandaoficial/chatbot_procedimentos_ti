<?php
date_default_timezone_set('America/La_Paz');
session_start();
require_once "db.php";

/* ============================
	banco Mysql/MariaDB
	Crie o banco 'tecnologia' e importe os arquivos .sql na pasta banco
	**Caso queira manter a conversao de PDF para Texto**
		Instale o python ( eu geralmente uso o 'venv' ) 
		instale o 'pdfplumber' usando comando pip
	OPENAI CHATGPT - em openai.php coloque sua API KEY
	
============================ */ 

//Tem restrição de Login? - coloque isso no db.php pois ele é chamado em todas as paginas;
if (!isset($_SESSION['login'])) {
    $id = urlencode($_SERVER['REQUEST_URI']);
    //header("Location: login.php/?id={$id}");
    //exit();
}


/* ============================
   CONFIG
============================ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['novo_procedimento'])) {

    $titulo       = trim($_POST['titulo']);
    $categoria_id = $_POST['categoria_id'];
    $sistema      = trim($_POST['sistema']);
    $objetivo     = trim($_POST['objetivo']);
    $quando_usar  = trim($_POST['quando_usar']);
    $conteudo     = trim($_POST['conteudo']);

    // INSERT PROCEDIMENTO
    $stmt = $pdo->prepare("
        INSERT INTO procedimentos
        (titulo, categoria_id, sistema, objetivo, quando_usar, conteudo)
        VALUES
        (:titulo, :categoria, :sistema, :objetivo, :quando, :conteudo)
    ");
    $stmt->execute([
        ':titulo'    => $titulo,
        ':categoria' => $categoria_id,
        ':sistema'   => $sistema,
        ':objetivo'  => $objetivo,
        ':quando'    => $quando_usar,
        ':conteudo'  => $conteudo
    ]);

    $id = $pdo->lastInsertId();

    // GARANTE REGISTRO NA TABELA AI
    $pdo->prepare("
        INSERT INTO procedimentos_ai
        (procedimento_id, resumo, palavras_chave)
        VALUES (:id, '', '')
    ")->execute([':id' => $id]);

    // PDF
    if (!empty($_FILES['pdf']['tmp_name'])) {

        $baseDir = realpath(__DIR__ . 'upload/');
        if ($baseDir === false) {
            die('Diretório base não encontrado');
        }

        $procDir = $baseDir . DIRECTORY_SEPARATOR . $id;
        if (!is_dir($procDir)) {
            mkdir($procDir, 0777, true);
        }

        $pdfPath = $procDir . DIRECTORY_SEPARATOR . 'procedimento.pdf';
        move_uploaded_file($_FILES['pdf']['tmp_name'], $pdfPath);

		$pythonExe = 'python.exe';
		$script    = realpath(__DIR__ . 'upload/extrair_pdf.py');

		// entra na pasta do procedimento
		chdir($procDir);
		$cmd = $pythonExe . ' "' . $script . '" procedimento.pdf procedimento.txt';
		$out = shell_exec($cmd . ' 2>&1');

		// debug
		file_put_contents($procDir . '/debug_python.log', $cmd . PHP_EOL . $out);

		// valida
		$txtPath = $procDir . DIRECTORY_SEPARATOR . 'procedimento.txt';

		if (file_exists($txtPath) && filesize($txtPath) > 0) {

			$textoExtraido = file_get_contents($txtPath);

			// procedimentos
			$pdo->prepare("
				UPDATE procedimentos
				SET conteudo = :conteudo
				WHERE id = :id
			")->execute([
				':conteudo' => $textoExtraido,
				':id' => $id
			]);

			// procedimentos_ai
			$pdo->prepare("
				UPDATE procedimentos_ai
				SET resumo = :resumo
				WHERE procedimento_id = :id
			")->execute([
				':resumo' => mb_substr($textoExtraido, 0, 2000),
				':id' => $id
			]);

			unlink($txtPath);
		}


    }

    header("Location: index.php");
    exit;
}



/* ============================
   CATEGORIAS
============================ */
$categorias = $pdo->query("
    SELECT id, nome 
    FROM categorias 
    WHERE ativo = 1 
    ORDER BY nome
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Procedimentos de TI</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<style>
body { background:#f5f6f8; }
.btn-categoria { margin-right:5px; margin-bottom:5px; }
</style>
</head>
<body>

<div class="container-fluid mt-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>📚 Documentação e Procedimentos de TI</h3>
	<div class="d-flex gap-2">
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNovo">
        ➕ Novo Procedimento
    </button>
	</div>
</div>
<div class="card shadow-sm mt-3">
  <div class="card-body">
    <table id="tabelaProcedimentos" class="table table-striped table-hover w-100">
      <thead>
        <tr>
          <th>Procedimento</th>
          <th>Onde</th>
          <th>Pra quê</th>
          <th>Quando</th>
          <th width="120">Ações</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="modalNovo" tabindex="-1">
<div class="modal-dialog modal-xl modal-dialog-scrollable">
<div class="modal-content">
<form method="post" enctype="multipart/form-data">

<div class="modal-header">
<h5 class="modal-title">Novo Procedimento</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body row g-3">

<input type="hidden" name="novo_procedimento" value="1">

<div class="col-md-6">
<label class="form-label">Título</label>
<input type="text" name="titulo" class="form-control" required>
</div>

<div class="col-md-3">
<label class="form-label">Categoria</label>
<select name="categoria_id" class="form-select" required>
<?php foreach ($categorias as $c): ?>
<option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-3">
<label class="form-label">Sistema</label>
<input type="text" name="sistema" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Objetivo</label>
<input type="text" name="objetivo" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Quando usar</label>
<input type="text" name="quando_usar" class="form-control">
</div>

<div class="col-12">
<label class="form-label">Conteúdo (manual)</label>
<textarea name="conteudo" class="form-control" rows="6"
placeholder="Se enviar PDF, este campo será sobrescrito"></textarea>
</div>

<div class="col-12">
<label class="form-label">Upload PDF (opcional)</label>
<input type="file" name="pdf" class="form-control" accept="application/pdf">
</div>

</div>

<div class="modal-footer">
<button type="submit" class="btn btn-success">Salvar</button>
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
</div>

</form>
</div>
</div>
</div>

</div>

<button class="btn btn-primary rounded-circle shadow"
        style="
            position:fixed;
            bottom:20px;
            right:20px;
            width:60px;
            height:60px;
            font-size:26px;
        "
        onclick="abrirSiaGlobal()">
    🤖
</button>
<div id="siaChat" style="
    position:fixed;
    bottom:20px;
    right:20px;
    width:420px;
    height:520px;
    background:#fff;
    border:1px solid #ccc;
    border-radius:12px;
    display:none;
    box-shadow:0 0 15px rgba(0,0,0,.25);
    z-index:9999;
    flex-direction:column;
">

    <!-- HEADER -->
    <div style="
        background:#0d6efd;
        color:#fff;
        padding:12px;
        border-radius:12px 12px 0 0;
        font-weight:500;
        flex-shrink:0;
    ">
        Chatbot • Procedimento
        <span style="float:right;cursor:pointer" onclick="fecharSia()">✖</span>
    </div>

    <!-- MENSAGENS -->
    <div id="siaMensagens" style="
        padding:10px;
        flex:1;
        overflow:auto;
        font-size:14px;
        background:#f8f9fa;
    "></div>

    <!-- INPUT (ÁREA FIXA) -->
    <div style="
        padding:10px;
        border-top:1px solid #ddd;
        flex-shrink:0;
        background:#fff;
    ">
        <textarea id="siaInput"
            class="form-control"
            rows="1"
            maxlength="500"
            placeholder="Digite sua dúvida (até 500 caracteres)..."
            oninput="ajustarTextarea(this)"
            onkeydown="if(event.key==='Enter' && !event.shiftKey){ event.preventDefault(); enviarSia(); }"
            style="
        resize:none;
        overflow-y:auto;
        max-height:110px;
    "></textarea>
        <small class="text-muted" id="siaContador">0 / 500</small>
    </div>
</div>

<div class="modal fade" id="modalTextoProc" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Procedimento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body"
           id="textoProcedimento"
           style="white-space:pre-wrap;font-size:14px;">
      </div>

    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
function abrirTextoProcedimento(id) {

    const box = document.getElementById('textoProcedimento');
    box.innerHTML = 'Carregando conteúdo...';

    fetch('proc_texto.php?id=' + id)
        .then(r => r.text())
        .then(r => {
            box.innerText = r || 'Conteúdo não disponível.';
            new bootstrap.Modal(
                document.getElementById('modalTextoProc')
            ).show();
        });
}
</script>

<script>
let procedimentoAtual = null;

/* ============================
   STORAGE HELPERS
============================ */
function getChatKey() {
    return procedimentoAtual ? 'sia_chat_proc_' + procedimentoAtual : 'sia_chat_global';
}

function carregarChat() {
    if (getChatKey() == 'sia_chat_global' || getChatKey() == 'sia_chat_proc_sia_chat_global') {
        document.getElementById('siaMensagens').innerHTML = '';
        return;
    }

    const historico = localStorage.getItem(getChatKey());
    document.getElementById('siaMensagens').innerHTML = historico || '';
}

function salvarChat() {
    if (getChatKey() == 'sia_chat_global' || getChatKey() == 'sia_chat_proc_sia_chat_global') return;

    localStorage.setItem(
        getChatKey(),
        document.getElementById('siaMensagens').innerHTML
    );
}

/* ============================
   ABRIR SIA
============================ */
function abrirSia(id) {
    procedimentoAtual = id;
    const box = document.getElementById('siaChat');
	box.style.display = 'flex';

    carregarChat();

    // se não houver histórico, carrega contexto automático
    if (!document.getElementById('siaMensagens').innerHTML) {
        mostrarPensando();

        fetch('sia_chat.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'id=' + procedimentoAtual
        })
        .then(r => r.text())
        .then(r => {
            removerPensando();
            appendSia(r);
            salvarChat();
        });
    }
}

function abrirSiaGlobal() {
    procedimentoAtual = 'sia_chat_global';
    const box = document.getElementById('siaChat');
	box.style.display = 'flex';
    carregarChat();

    if (!document.getElementById('siaMensagens').innerHTML) {
        appendSia('SIA: Olá! Posso te ajudar a localizar ou executar procedimentos.');
        salvarChat();
    }
}

/* ============================
   FECHAR
============================ */
function fecharSia() {
    salvarChat();
    document.getElementById('siaChat').style.display = 'none';
}

/* ============================
   MENSAGENS
============================ */
function appendUsuario(msg) {
    const box = document.getElementById('siaMensagens');
    box.innerHTML += '<b>Você:</b> ' + msg + '<br>';
    box.scrollTop = box.scrollHeight;
}

function appendSia(msg) {
    const box = document.getElementById('siaMensagens');
    box.innerHTML += '<b>Chatbot:</b> ' + msg + '<br><br>';
    box.scrollTop = box.scrollHeight;
}

/* ============================
   INDICADOR DE PROCESSAMENTO
============================ */


function removerPensando() {
    const el = document.getElementById('siaPensando');
    if (el) el.remove();
}

/* ============================
   ENVIAR MENSAGEM
============================ */
function enviarSia() {
    const input = document.getElementById('siaInput');
    let msg = input.value.trim();

    if (!msg) return;

    if (msg.length > 500) {
        msg = msg.substring(0, 500);
    }

    appendUsuario(msg);
    mostrarPensando();
    salvarChat();

    let payload = 'msg=' + encodeURIComponent(msg);
    if (procedimentoAtual) payload += '&id=' + procedimentoAtual;

    fetch('sia_chat.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: payload
    })
    .then(r => r.text())
    .then(r => {
        removerPensando();
        appendSia(r);
        salvarChat();
    });

    input.value = '';
input.style.height = 'auto';
document.getElementById('siaContador').innerText = '0 / 500';
}
</script>

<script>
function ajustarTextarea(el) {
    const contador = document.getElementById('siaContador');
    contador.innerText = el.value.length + ' / 500';

    el.style.height = 'auto';

    const maxHeight = 110;
    if (el.scrollHeight > maxHeight) {
        el.style.height = maxHeight + 'px';
        el.style.overflowY = 'auto';
    } else {
        el.style.height = el.scrollHeight + 'px';
        el.style.overflowY = 'hidden';
    }
	}

</script>


<script>
$(function () {
  $('#tabelaProcedimentos').DataTable({
    ajax: 'ajax_listar_procedimentos.php',
    columns: [
      { data: 'titulo' },
      { data: 'sistema' },
      { data: 'objetivo' },
      { data: 'quando_usar' },
      { data: 'acoes', orderable:false, searchable:false }
    ],
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json"
    }
  });
});

function mostrarPensando() {
    const box = document.getElementById('siaMensagens');
    box.innerHTML += `<div id="siaPensando" style="color:#666;font-style:italic;">Chatbot está digitando...</div>`;
    box.scrollTop = box.scrollHeight;
}
</script>

</body>
</html>
