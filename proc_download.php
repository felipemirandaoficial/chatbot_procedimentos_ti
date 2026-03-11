<?php
// proc_download.php
require_once "db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit("ID inválido");
}

$id = (int) $_GET['id'];

/* ============================
   VERIFICA SE PROCEDIMENTO EXISTE
============================ */
$stmt = $pdo->prepare("SELECT id FROM procedimentos WHERE id = :id");
$stmt->execute([':id' => $id]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    exit("Procedimento não encontrado");
}

/* ============================
   CAMINHO REAL DO PDF
============================ */
$baseDir = realpath(__DIR__ . "/upload");
$pdfPath = $baseDir . "/" . $id . "/procedimento.pdf";

if (!file_exists($pdfPath)) {
    http_response_code(404);
    exit("PDF não encontrado");
}

/* ============================
   HEADERS PARA PDF
============================ */
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=procedimento_$id.pdf");
header("Content-Length: " . filesize($pdfPath));
header("Cache-Control: private");
header("Pragma: public");

readfile($pdfPath);
exit;
