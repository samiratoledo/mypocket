<?php

declare(strict_types=1);

$hoje = new DateTime();

$diaAtual = (int) $hoje->format('d');
$diasNoMes = (int) $hoje->format('t');

$diasRestantes = $diasNoMes - $diaAtual + 1;


/* ORÇAMENTO DO MÊS */

$stmt = $pdo->prepare("
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Entrada' THEN valor
                    ELSE 0
                END
            ), 0
        ) AS entradas,

        COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Saida' THEN valor
                    ELSE 0
                END
            ), 0
        ) AS saidas,

        COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Diario' THEN valor
                    ELSE 0
                END
            ), 0
        ) AS diarios

    FROM transacoes

    WHERE usuario_id = :usuario_id
      AND data BETWEEN :inicio AND :fim
");

$stmt->execute([
    'usuario_id' => $usuarioId,
    'inicio' => $hoje->format('Y-m-01'),
    'fim' => $hoje->format('Y-m-t')
]);

$orcamento = $stmt->fetch(PDO::FETCH_ASSOC);

$entradasMes = (float) ($orcamento['entradas'] ?? 0);
$saidasMes = (float) ($orcamento['saidas'] ?? 0);
$diariosMes = (float) ($orcamento['diarios'] ?? 0);

$disponivelMes = $entradasMes - $saidasMes - $diariosMes;

$limiteDiario = $diasRestantes > 0
    ? $disponivelMes / $diasRestantes
    : 0;


/* GASTOS DIÁRIOS DE HOJE */

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(valor), 0)
    FROM transacoes
    WHERE usuario_id = :usuario_id
      AND tipo = 'Diario'
      AND data = :data
");

$stmt->execute([
    'usuario_id' => $usuarioId,
    'data' => $hoje->format('Y-m-d')
]);

$diarioHoje = (float) $stmt->fetchColumn();

$disponivelHoje = $limiteDiario - $diarioHoje;


/* NOTIFICAÇÃO */

if ($disponivelMes < 0) {

    $notificacaoClasse = 'danger';
    $notificacaoTitulo = '🚨 Atenção!';

    $notificacaoTexto =
        'Seu orçamento do mês está negativo em ' .
        dinheiro(abs($disponivelMes)) .
        '.';

} elseif ($disponivelHoje < 0) {

    $notificacaoClasse = 'warning';
    $notificacaoTitulo = '⚠️ Cuidado!';

    $notificacaoTexto =
        'Você ultrapassou o limite de hoje em ' .
        dinheiro(abs($disponivelHoje)) .
        '.';

} else {

    $notificacaoClasse = 'success';
    $notificacaoTitulo = 'Orçamento diário';

    $notificacaoTexto =
        'Você pode gastar até ' .
        dinheiro(max(0, $limiteDiario)) .
        ' por dia para não negativar.';
}