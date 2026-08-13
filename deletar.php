<?php

require_once 'conexao.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
$usuarioId = $_SESSION['usuario_id'];

if (!$id) {
    die("ID da transação não recebido.");
}

$stmt = $pdo->prepare("
    DELETE FROM transacoes
    WHERE id = :id
    AND usuario_id = :usuario_id
");

$stmt->execute([
    'id' => $id,
    'usuario_id' => $usuarioId
]);

header('Location: index.php');
exit;