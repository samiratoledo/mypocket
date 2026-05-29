<?php
declare(strict_types=1);

require_once 'classes/Transacao.php';
require_once 'classes/Receita.php';
require_once 'classes/Despesa.php';
require_once 'classes/Carteira.php';


session_start();

if (!isset($_SESSION['carteira']) || empty($_SESSION['carteira']->getHistorico())) {
    $_SESSION['erro'] = "Não há transações para exportar.";
    header("Location: index.php");
    exit;
}

$carteira = $_SESSION['carteira'];
$historico = $carteira->getHistorico();

$dados = [
    ['Valor', 'Tipo', 'Data', 'Descrição'] 
];

foreach ($historico as $transacao) {
    // Formata a data para (DD/MM/AAAA)
    $timestamp = strtotime($transacao->getData());
    $dataFormatada = ($timestamp !== false) ? date('d/m/Y', $timestamp) : '---';

    $dados[] = [
        'R$ ' . number_format($transacao->getValor(), 2, ',', '.'),
        $transacao->getTipo(),
        $dataFormatada,
        $transacao->getDescricao()
    ];
}

$filename = "mypocket_extrato_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Adiciona o BOM para o Excel não quebrar acentos como "Saída" ou "Descrição"
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

foreach ($dados as $linha) {
    fputcsv($output, $linha, ';');
}

fclose($output);
exit;
?>