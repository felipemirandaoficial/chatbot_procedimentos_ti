<?php
require_once "db.php";

$baseFs = realpath(__DIR__ . "/upload");

$stmt = $pdo->query("
    SELECT id, titulo, sistema, objetivo, quando_usar
    FROM procedimentos
    WHERE status = 'ativo'
    ORDER BY criado_em DESC
");

$dados = [];

while ($row = $stmt->fetch()) {

    $pdfFs = $baseFs . "/" . $row['id'] . "/procedimento.pdf";

	if ($baseFs && file_exists($pdfFs)) {

		$btnPdf = '
			<a href="proc_download.php?id='.$row['id'].'"
			   target="_blank"
			   class="btn btn-sm btn-outline-danger">
			   📄 PDF
			</a>';

	} else {

		$btnPdf = '
			<button class="btn btn-sm btn-outline-primary"
					onclick="abrirTextoProcedimento('.$row['id'].')">
				📝 Texto
			</button>';
	}


    $btnSia = '
        <button class="btn btn-sm btn-outline-success"
                onclick="abrirSia('.$row['id'].')">
            🤖 Chat
        </button>';

    $dados[] = [
        'titulo'      => htmlspecialchars($row['titulo']),
        'sistema'     => htmlspecialchars($row['sistema']),
        'objetivo'    => htmlspecialchars($row['objetivo']),
        'quando_usar' => htmlspecialchars($row['quando_usar']),
        'acoes'       => $btnPdf . ' ' . $btnSia
    ];
}

echo json_encode(['data' => $dados]);
