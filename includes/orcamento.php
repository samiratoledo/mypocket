<?php

$hoje = new DateTime();

$diaAtual = (int) $hoje->format('d');
$diasNoMes = (int) $hoje->format('t');

$diasRestantes = $diasNoMes - $diaAtual + 1;
$diasFuturos = $diasNoMes - $diaAtual;


/*
 * DINHEIRO DISPONÍVEL PARA O MÊS
 *
 * Entradas
 * - gastos fixos
 *
 * Os gastos diários NÃO são descontados aqui.
 */
$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN tipo = 'Entrada' THEN valor ELSE 0 END), 0) AS entradas,
        COALESCE(SUM(CASE WHEN tipo = 'Saida' THEN valor ELSE 0 END), 0) AS saidas,
        COALESCE(SUM(CASE WHEN tipo = 'Diario' THEN valor ELSE 0 END), 0) AS diarios
    FROM transacoes
    WHERE usuario_id = :usuario
      AND data BETWEEN :inicio AND :fim
");

$stmt->execute([
    'usuario' => $usuarioId,
    'inicio' => $hoje->format('Y-m-01'),
    'fim' => $hoje->format('Y-m-t')
]);

$orcamento = $stmt->fetch(PDO::FETCH_ASSOC);

$entradasMes = (float) $orcamento['entradas'];
$saidasMes = (float) $orcamento['saidas'];
$diariosMes = (float) $orcamento['diarios'];


/*
 * VALOR QUE PODE SER DESTINADO
 * AOS GASTOS DIÁRIOS DURANTE O MÊS
 */
$orcamentoDiarioMes = $entradasMes - $saidasMes;


/*
 * LIMITE DIÁRIO ORIGINAL
 */
$limiteDiarioOriginal = $diasNoMes > 0
    ? $orcamentoDiarioMes / $diasNoMes
    : 0;


/*
 * QUANTO JÁ FOI GASTO HOJE
 */
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(valor), 0)
    FROM transacoes
    WHERE usuario_id = :usuario
      AND tipo = 'Diario'
      AND data = :data
");

$stmt->execute([
    'usuario' => $usuarioId,
    'data' => $hoje->format('Y-m-d')
]);

$diarioHoje = (float) $stmt->fetchColumn();


/*
 * QUANTO AINDA PODE GASTAR HOJE
 */
$disponivelHoje = $limiteDiarioOriginal - $diarioHoje;


/*
 * GASTO DIÁRIO FEITO ANTES DE HOJE
 */
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(valor), 0)
    FROM transacoes
    WHERE usuario_id = :usuario
      AND tipo = 'Diario'
      AND data >= :inicio
      AND data < :hoje
");

$stmt->execute([
    'usuario' => $usuarioId,
    'inicio' => $hoje->format('Y-m-01'),
    'hoje' => $hoje->format('Y-m-d')
]);

$diariosAnteriores = (float) $stmt->fetchColumn();


/*
 * QUANTO SOBRA DO ORÇAMENTO
 * DEPOIS DOS GASTOS ANTERIORES
 */
$saldoParaDiasRestantes =
    $orcamentoDiarioMes
    - $diariosAnteriores
    - $diarioHoje;


/*
 * SE HOJE JÁ PASSOU DO LIMITE,
 * O EXCESSO É AUTOMATICAMENTE
 * DISTRIBUÍDO PELOS PRÓXIMOS DIAS.
 */
if ($diasFuturos > 0) {

    $limiteProximosDias =
        $saldoParaDiasRestantes / $diasFuturos;

} else {

    $limiteProximosDias = 0;
}


/*
 * SITUAÇÃO
 */
if ($orcamentoDiarioMes < 0) {

    $notificacaoClasse = 'danger';

    $notificacaoTitulo = '🔴 Cuidado!';

    $notificacaoTexto =
        'Você está ' .
        dinheiro(abs($orcamentoDiarioMes)) .
        ' acima do orçamento previsto.';

} elseif ($disponivelHoje < 0) {

    $notificacaoClasse = 'warning';

    $notificacaoTitulo = '🟡 Atenção!';

    $notificacaoTexto =
        'Você ultrapassou seu limite diário em ' .
        dinheiro(abs($disponivelHoje)) .
        '.';

} else {

    $notificacaoClasse = 'success';

    $notificacaoTitulo = '🟢 Orçamento diário';

    $notificacaoTexto =
        'Você pode gastar até ' .
        dinheiro(max(0, $limiteDiarioOriginal)) .
        ' por dia sem negativar.';
}
