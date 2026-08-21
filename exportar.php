<?php
declare(strict_types=1);

require_once 'conexao.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT valor, tipo, data, descricao
    FROM transacoes
    WHERE usuario_id = :usuario_id
    ORDER BY data DESC, id DESC
");

$stmt->execute([
    'usuario_id' => $_SESSION['usuario_id']
]);

$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$transacoes) {
    $_SESSION['erro'] = 'Não há transações para exportar.';
    header('Location: index.php');
    exit;
}

header('Content-Type: text/csv; charset=utf-8');
header(
    'Content-Disposition: attachment; filename="mypocket_extrato_' .
    date('Y-m-d') .
    '.csv"'
);

$output = fopen('php://output', 'w');

fprintf($output, "\xEF\xBB\xBF");

fputcsv(
    $output,
    ['Valor', 'Tipo', 'Data', 'Descrição'],
    ';'
);

foreach ($transacoes as $t) {
    fputcsv($output, [
        'R$ ' . number_format((float) $t['valor'], 2, ',', '.'),
        $t['tipo'],
        date('d/m/Y', strtotime($t['data'])),
        $t['descricao']
    ], ';');
}

fclose($output);
exit;