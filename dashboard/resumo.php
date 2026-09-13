<?php

declare(strict_types=1);

/* RESUMO MENSAL */

$stmt = $pdo->prepare("
    SELECT
        YEAR(data) AS ano,
        MONTH(data) AS mes,

        COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Entrada' THEN valor
                    ELSE 0
                END
            ),
            0
        ) AS entrada,

        COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Saida' THEN valor
                    ELSE 0
                END
            ),
            0
        ) AS saida,

        COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Diario' THEN valor
                    ELSE 0
                END
            ),
            0
        ) AS diario

    FROM transacoes

    WHERE usuario_id = :usuario_id

    GROUP BY YEAR(data), MONTH(data)

    ORDER BY YEAR(data), MONTH(data)
");

$stmt->execute([
    'usuario_id' => $usuarioId
]);

$dadosMensais = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $mes) {

    $chave = sprintf(
        '%04d-%02d',
        (int) $mes['ano'],
        (int) $mes['mes']
    );

    $dadosMensais[$chave] = [
        'entrada' => (float) $mes['entrada'],
        'saida' => (float) $mes['saida'],
        'diario' => (float) $mes['diario']
    ];
}

/* SALDO ANTERIOR AO ANO ATUAL */

$dataInicioAno = $anoAtual . '-01-01';

$stmt = $pdo->prepare("
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Entrada' THEN valor
                    ELSE -valor
                END
            ),
            0
        )
    FROM transacoes

    WHERE usuario_id = :usuario_id
      AND data < :inicio
");

$stmt->execute([
    'usuario_id' => $usuarioId,
    'inicio' => $dataInicioAno
]);

$saldoAnterior = (float) $stmt->fetchColumn();

/* RESUMO DOS ANOS */

$anosResumo = [];

for ($ano = $anoAtual; $ano <= $anoFinal; $ano++) {
    if ($ano === $anoAtual) {
        $saldoAno = $saldoAnterior;
    } else {
        $saldoAno = $saldo;
    }

    for ($mes = 1; $mes <= 12; $mes++) {

        $chave = sprintf(
            '%04d-%02d',
            $ano,
            $mes
        );

        $dados = $dadosMensais[$chave] ?? [];

        $entrada = (float) ($dados['entrada'] ?? 0);
        $saida = (float) ($dados['saida'] ?? 0);
        $diario = (float) ($dados['diario'] ?? 0);

        /* Resultado do mês */
        $resultado =
            $entrada
            - $saida
            - $diario;

        /* Atualiza o saldo acumulado.*/
        $saldoAno += $resultado;

        /* Define a situação do mês */
        if ($ano < $anoAtual) {
            $situacao = 'Real';
        } elseif ($ano > $anoAtual) {
            $situacao = 'Previsão';
        } elseif ($mes < $mesAtual) {
            $situacao = 'Real';
        } elseif ($mes === $mesAtual) {
            $situacao = 'Atual';
        } else {
            $situacao = 'Previsão';
        }

        /* Guarda os dados */
        $anosResumo[$ano][] = [
            'mes' => $chave,
            'entrada' => $entrada,
            'saida' => $saida,
            'diario' => $diario,
            'resultado' => $resultado,
            'saldo' => $saldoAno,
            'situacao' => $situacao
        ];
    }
}