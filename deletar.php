<?php

require_once 'conexao.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$usuarioId = (int) $_SESSION['usuario_id'];

if (!$id) {
    header('Location: index.php');
    exit;
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

$_SESSION['sucesso'] = 'Transação excluída com sucesso.';

header('Location: index.php');
exit;