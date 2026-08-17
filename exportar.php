<?php

declare(strict_types=1);

require_once 'conexao.php';

session_start();

/*
 * Verifica se o usuário está logado
 */
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = $_SESSION['usuario_id'];


/*
 * Busca somente as transações
 * do usuário que está logado
 */
$stmt = $pdo->prepare("
    SELECT valor, tipo, data, descricao
    FROM transacoes
    WHERE usuario_id = :usuario_id
    ORDER BY data DESC, id DESC
");

$stmt->execute([
    'usuario_id' => $usuarioId
]);

$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
 * Verifica se existem transações
 */
if (empty($transacoes)) {

    $_SESSION['erro'] = "Não há transações para exportar.";

    header("Location: index.php");
    exit;
}


/*
 * Nome do arquivo
 */
$filename = "mypocket_extrato_" . date('Y-m-d') . ".csv";


/*
 * Configura o download do CSV
 */
header('Content-Type: text/csv; charset=utf-8');

header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);


/*
 * Abre a Saida para gerar o arquivo
 */
$output = fopen('php://output', 'w');


/*
 * BOM para o Excel reconhecer
 * os acentos corretamente
 */
fprintf(
    $output,
    chr(0xEF) . chr(0xBB) . chr(0xBF)
);


/*
 * Cabeçalho do CSV
 */
fputcsv(
    $output,
    ['Valor', 'Tipo', 'Data', 'Descrição'],
    ';'
);


/*
 * Adiciona cada transação
 */
foreach ($transacoes as $transacao) {

    $timestamp = strtotime($transacao['data']);

    $dataFormatada = (
        $timestamp !== false
    )
        ? date('d/m/Y', $timestamp)
        : '---';


    $linha = [

        'R$ ' . number_format(
            (float) $transacao['valor'],
            2,
            ',',
            '.'
        ),

        $transacao['tipo'],

        $dataFormatada,

        $transacao['descricao']

    ];


    fputcsv(
        $output,
        $linha,
        ';'
    );
}


fclose($output);

exit;
?>