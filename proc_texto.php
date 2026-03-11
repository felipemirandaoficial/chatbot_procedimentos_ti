<?php
require_once "db.php";

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT titulo, conteudo
    FROM procedimentos
    WHERE id = :id
");
$stmt->execute([':id' => $id]);

$proc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proc || trim($proc['conteudo']) === '') {
    echo "Conteúdo não disponível.";
    exit;
}

echo $proc['conteudo'];
