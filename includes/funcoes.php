<?php

declare(strict_types=1);

function dinheiro(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function nomeMes(string $mes): string
{
    $meses = [
        '01' => 'Janeiro',
        '02' => 'Fevereiro',
        '03' => 'Março',
        '04' => 'Abril',
        '05' => 'Maio',
        '06' => 'Junho',
        '07' => 'Julho',
        '08' => 'Agosto',
        '09' => 'Setembro',
        '10' => 'Outubro',
        '11' => 'Novembro',
        '12' => 'Dezembro'
    ];

    [$ano, $numero] = explode('-', $mes);

    return $meses[$numero] . ' de ' . $ano;
}