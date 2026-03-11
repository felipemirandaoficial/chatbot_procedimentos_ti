<?php
require_once "openai.php";
require_once "db.php";
/* ============================
   CONTROLE DE SESSÃO
============================ */
if (!isset($_SESSION['sia_calls'])) {
    $_SESSION['sia_calls'] = 0;
}

/* ============================
   ENTRADA
============================ */
$id  = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$msg = trim($_POST['msg'] ?? '');

/* ============================
   FUNÇÃO OPENAI
============================ */
function chamarOpenAI(string $system, string $user): string
{
    $data = [
        "model" => "gpt-4o-mini",
        "messages" => [
            ["role" => "system", "content" => $system],
            ["role" => "user", "content" => $user]
        ],
        "temperature" => 0.3,
        "max_tokens" => 500
    ];

    $ch = curl_init(OPENAI_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . OPENAI_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);

    return $json['choices'][0]['message']['content'] ?? '';
}

/* ============================
   SYSTEM PROMPT FIXO DO SIA
============================ */

$systemPrompt = <<<PROMPT
Você é um Chatbot, assistente interno de TI da empresa.

Regras obrigatórias:
1. Seu nome é Bia, assistente virtual.
2. Seja claro, direto e profissional
3. Use no máximo 8 frases
4. Evite linguagem excessivamente técnica
5. Se a informação estiver incompleta, diga isso educadamente
6. Nunca invente informações
7. Não use emojis
PROMPT;

/* ============================
	SIA GLOBAL
============================ */

if ($id === 0 && $msg === '') {
    echo "Olá! Posso ajudar a localizar procedimentos, explicar etapas ou tirar dúvidas técnicas.";
    exit;
}

/* ============================
   BUSCA PROCEDIMENTO
============================ */

$proc = null;

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT 
            p.titulo,
            p.sistema,
            p.objetivo,
            p.conteudo,
            ai.resumo
        FROM procedimentos p
        LEFT JOIN procedimentos_ai ai
            ON ai.procedimento_id = p.id
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $proc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proc) {
        echo "Procedimento não encontrado.";
        exit;
    }

    // abertura automática
    if ($msg === '') {
        echo "Procedimento carregado com sucesso.<br><br>"
           . "<b>{$proc['titulo']}</b><br>"
           . "Sistema: {$proc['sistema']}<br>"
           . "Objetivo: {$proc['objetivo']}<br><br>"
           . "Você pode perguntar como executar, validar etapas ou tirar dúvidas.";
        exit;
    }
}

/* ============================
   LIMITE DE USO (150)
============================ */

$limiteAtingido = ($_SESSION['sia_calls'] >= 150);


/* ============================
   FALLBACK 1.0 (SEM OPENAI) - Ativado para caso voce nao tenha OPENAI
============================ */
function buscarProcedimentoPorTexto(PDO $pdo, string $texto)
{
    // normaliza
    $texto = mb_strtolower($texto);
    $texto = preg_replace('/[^\p{L}\p{N}\s]/u', '', $texto);

    $palavras = array_filter(
        preg_split('/\s+/', $texto),
        fn($p) => mb_strlen($p) >= 4
    );

    if (!$palavras) return null;

    $scoreSql = [];
    $whereSql = [];
    $params   = [];

    foreach ($palavras as $i => $p) {
        $k = ":p$i";
        $params[$k] = "%$p%";

        // score ponderado
        $scoreSql[] = "
            (CASE WHEN p.titulo   LIKE $k THEN 5 ELSE 0 END) +
            (CASE WHEN p.objetivo LIKE $k THEN 3 ELSE 0 END) +
            (CASE WHEN p.conteudo LIKE $k THEN 1 ELSE 0 END)
        ";

        $whereSql[] = "
            p.titulo   LIKE $k
            OR p.objetivo LIKE $k
            OR p.conteudo LIKE $k
        ";
    }

    $sql = "
        SELECT 
            p.id,
            p.titulo,
            p.sistema,
            p.objetivo,
            p.conteudo,
            ai.resumo,
            (" . implode(" + ", $scoreSql) . ") AS score
        FROM procedimentos p
        LEFT JOIN procedimentos_ai ai
            ON ai.procedimento_id = p.id
        WHERE p.status = 'ativo'
          AND (" . implode(" OR ", $whereSql) . ")
        ORDER BY score DESC
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}


if ($limiteAtingido) {

    if ($proc) {
        echo "Com base no procedimento <b>{$proc['titulo']}</b>:<br><br>"
           . nl2br(htmlspecialchars(
                $proc['resumo']
                ?: mb_substr(strip_tags($proc['conteudo']), 0, 500)
             ));
    } else {
        echo "Limite de uso da inteligência atingido nesta sessão. Consulte os procedimentos cadastrados.";
    }

    exit;
}

/* ============================
   USER PROMPT
============================ */
if ($proc) {
    $userPrompt = <<<TXT
Procedimento: {$proc['titulo']}
Sistema: {$proc['sistema']}
Objetivo: {$proc['objetivo']}

Conteúdo do procedimento:
{$proc['conteudo']}

Pergunta do usuário:
{$msg}
TXT;
} else {

    // Chat GLOBAL INTELIGENTE
    $procEncontrado = buscarProcedimentoPorTexto($pdo, $msg);

    if ($procEncontrado) {

        $userPrompt = <<<TXT
Procedimento identificado automaticamente:
{$procEncontrado['titulo']}
Sistema: {$procEncontrado['sistema']}
Objetivo: {$procEncontrado['objetivo']}

Conteúdo:
{$procEncontrado['conteudo']}

Pergunta do usuário:
{$msg}
TXT;

        // força contexto correto
        $id = $procEncontrado['id'];

    } else {
        // fallback genérico
        $userPrompt = $msg;
    }
}


/* ============================
   CHAMADA OPENAI
============================ */
$resposta = chamarOpenAI($systemPrompt, $userPrompt);

/* ============================
   SE FALHAR → FALLBACK 1.0
============================ */
if (!$resposta) {
    echo "Com base nas informações disponíveis, revise o objetivo do procedimento e siga o passo a passo descrito.";
    exit;
}

/* ============================
   INCREMENTA CONTADOR
============================ */
$_SESSION['sia_calls']++;

/* ============================
   LOG DE USO DA IA
============================ */
$pdo->prepare("
    INSERT INTO procedimentos_log
    (procedimento_id, session_id, pergunta, resposta)
    VALUES
    (:pid, :sid, :pergunta, :resposta)
")->execute([
    ':pid'      => $id ?: null,
    ':sid'      => session_id(),
    ':pergunta' => $msg,
    ':resposta' => $resposta
]);

/* ============================
   SAÍDA
============================ */
echo nl2br(htmlspecialchars($resposta));
exit;
