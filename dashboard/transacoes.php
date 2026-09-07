<?php

declare(strict_types=1);

/*
 * TRANSAÇÕES DO USUÁRIO
 */

$stmt = $pdo->prepare("
    SELECT
        id,
        valor,
        tipo,
        data,
        descricao,
        parcela,
        total_parcelas
    FROM transacoes
    WHERE usuario_id = :usuario_id
    ORDER BY data DESC, id DESC
");

$stmt->execute([
    'usuario_id' => $usuarioId
]);

$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
 * SALDO ATUAL
 */

$saldo = 0.0;

foreach ($transacoes as $transacao) {

    $valor = (float) $transacao['valor'];

    if ($transacao['tipo'] === 'Entrada') {
        $saldo += $valor;
    } else {
        $saldo -= $valor;
    }
}