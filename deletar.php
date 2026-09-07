<?php

declare(strict_types=1);

require_once 'conexao.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header('Location: index.php');
    exit;
}


/* =========================
   EXCLUIR TRANSAÇÃO
========================= */

$stmt = $pdo->prepare("
    DELETE FROM transacoes
    WHERE id = :id
      AND usuario_id = :usuario_id
");

$stmt->execute([
    'id' => $id,
    'usuario_id' => $usuarioId
]);


/* =========================
   MENSAGEM
========================= */

if ($stmt->rowCount() > 0) {

    $_SESSION['sucesso'] =
        'Transação excluída com sucesso.';

} else {

    $_SESSION['erro'] =
        'Transação não encontrada.';
}

header('Location: index.php');
exit;