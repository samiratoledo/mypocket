<?php
declare(strict_types=1);

require_once 'conexao.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];

$valor = (float) ($_POST['valor'] ?? 0);
$tipo = trim($_POST['tipo'] ?? '');
$data = trim($_POST['data'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

$tipos = [
    'Entrada' => 'Entrada',
    'Saida' => 'Saida',
    'Saída' => 'Saida',
    'Diario' => 'Diario',
    'Diário' => 'Diario'
];

$tipo = $tipos[$tipo] ?? null;

if ($valor <= 0 || !$tipo || !$data || !$descricao) {
    $_SESSION['erro'] = 'Preencha todos os campos corretamente.';
    header('Location: index.php');
    exit;
}

/* Verifica saldo para Saída e Diário */
if ($tipo !== 'Entrada') {
    $stmt = $pdo->prepare("
        SELECT COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Entrada' THEN valor
                    ELSE -valor
                END
            ), 0
        ) AS saldo
        FROM transacoes
        WHERE usuario_id = :usuario_id
    ");

    $stmt->execute(['usuario_id' => $usuarioId]);
    $saldo = (float) $stmt->fetchColumn();

    if ($valor > $saldo) {
        $_SESSION['erro'] = 'Saldo insuficiente para realizar esta transação.';
        header('Location: index.php');
        exit;
    }
}

$stmt = $pdo->prepare("
    INSERT INTO transacoes
        (valor, tipo, data, descricao, usuario_id)
    VALUES
        (:valor, :tipo, :data, :descricao, :usuario_id)
");

$stmt->execute([
    'valor' => $valor,
    'tipo' => $tipo,
    'data' => $data,
    'descricao' => $descricao,
    'usuario_id' => $usuarioId
]);

$_SESSION['sucesso'] = 'Transação cadastrada com sucesso!';

header('Location: index.php');
exit;