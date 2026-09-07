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


/*
 * =========================
 * DADOS
 * =========================
 */

$valor = (float) ($_POST['valor'] ?? 0);

$tipo = trim(
    $_POST['tipo'] ?? ''
);

$data = trim(
    $_POST['data'] ?? ''
);

$descricao = trim(
    $_POST['descricao'] ?? ''
);

$cobranca =
    $_POST['tipo_cobranca'] ?? 'normal';

$dia = (int) (
    $_POST['dia'] ?? date('d')
);

$dataInicio =
    $_POST['data_inicio'] ?? $data;

$frequencia =
    $_POST['frequencia'] ?? 'mensal';

$totalParcelas =
    (int) (
        $_POST['total_parcelas'] ?? 0
    );


/*
 * =========================
 * NORMALIZA TIPO
 * =========================
 */

$tiposValidos = [
    'Entrada' => 'Entrada',
    'Saida' => 'Saida',
    'Saída' => 'Saida',
    'Diario' => 'Diario',
    'Diário' => 'Diario'
];

$tipo =
    $tiposValidos[$tipo] ?? null;


/*
 * =========================
 * VALIDAÇÃO BÁSICA
 * =========================
 */

if (
    $valor <= 0 ||
    $tipo === null ||
    $data === '' ||
    $descricao === ''
) {

    $_SESSION['erro'] =
        'Preencha todos os campos corretamente.';

    header('Location: index.php');
    exit;
}


/*
 * =========================
 * COBRANÇA VÁLIDA
 * =========================
 */

if (
    !in_array(
        $cobranca,
        ['normal', 'fixa', 'parcelada'],
        true
    )
) {

    $_SESSION['erro'] =
        'Tipo de cobrança inválido.';

    header('Location: index.php');
    exit;
}


/*
 * =========================
 * REGRAS DE NEGÓCIO
 * =========================
 */

/*
 * Entrada e Diário são somente
 * transações normais.
 */

if (
    $tipo !== 'Saida' &&
    $cobranca !== 'normal'
) {

    $_SESSION['erro'] =
        'Entradas e gastos diários não podem ser fixos ou parcelados.';

    header('Location: index.php');
    exit;
}


/*
 * =========================
 * TRANSAÇÃO NORMAL
 * =========================
 */

if ($cobranca === 'normal') {

    /*
     * Entrada não precisa verificar saldo.
     *
     * Saída e Diário precisam.
     */

    if ($tipo !== 'Entrada') {

        $stmt = $pdo->prepare("
            SELECT COALESCE(
                SUM(
                    CASE
                        WHEN tipo = 'Entrada'
                            THEN valor
                        ELSE -valor
                    END
                ),
                0
            )
            FROM transacoes
            WHERE usuario_id = :usuario_id
        ");

        $stmt->execute([
            'usuario_id' => $usuarioId
        ]);

        $saldo =
            (float) $stmt->fetchColumn();


        if ($valor > $saldo) {

            $_SESSION['erro'] =
                'Saldo insuficiente para realizar esta transação.';

            header('Location: index.php');
            exit;
        }
    }


    /*
     * CADASTRA
     */

    $stmt = $pdo->prepare("
        INSERT INTO transacoes
        (
            valor,
            tipo,
            data,
            descricao,
            usuario_id,
            competencia
        )
        VALUES
        (
            :valor,
            :tipo,
            :data,
            :descricao,
            :usuario_id,
            :competencia
        )
    ");

    $stmt->execute([
        'valor' => $valor,
        'tipo' => $tipo,
        'data' => $data,
        'descricao' => $descricao,
        'usuario_id' => $usuarioId,
        'competencia' =>
            date('Y-m', strtotime($data))
    ]);


    $_SESSION['sucesso'] =
        'Transação cadastrada com sucesso!';

    header('Location: index.php');
    exit;
}


/*
 * =========================
 * FIXA / PARCELADA
 * =========================
 */


/*
 * Só Saída chega aqui.
 */

if ($tipo !== 'Saida') {

    $_SESSION['erro'] =
        'Somente despesas podem ser fixas ou parceladas.';

    header('Location: index.php');
    exit;
}


/*
 * Dia válido.
 */

if ($dia < 1 || $dia > 31) {

    $_SESSION['erro'] =
        'O dia da cobrança deve estar entre 1 e 31.';

    header('Location: index.php');
    exit;
}


/*
 * Frequência válida.
 */

if (
    !in_array(
        $frequencia,
        ['mensal', 'anual'],
        true
    )
) {

    $_SESSION['erro'] =
        'Frequência inválida.';

    header('Location: index.php');
    exit;
}


/*
 * Parcelamento.
 */

if ($cobranca === 'parcelada') {

    if ($totalParcelas < 1) {

        $_SESSION['erro'] =
            'Informe a quantidade de parcelas.';

        header('Location: index.php');
        exit;
    }


    /*
     * Parcelamento será mensal.
     */

    if ($frequencia !== 'mensal') {

        $_SESSION['erro'] =
            'Parcelamento deve ter frequência mensal.';

        header('Location: index.php');
        exit;
    }


    /*
     * Data final.
     */

    $dataFim =
        (new DateTime($dataInicio))
            ->modify(
                '+' .
                ($totalParcelas - 1) .
                ' month'
            )
            ->format('Y-m-d');

} else {

    /*
     * Fixa não possui fim.
     */

    $dataFim = null;
}


/*
 * =========================
 * CADASTRA COBRANÇA
 * =========================
 */

$stmt = $pdo->prepare("
    INSERT INTO transacoes_fixas
    (
        usuario_id,
        valor,
        tipo,
        descricao,
        dia,
        data_inicio,
        data_fim,
        frequencia,
        tipo_cobranca,
        total_parcelas
    )
    VALUES
    (
        :usuario_id,
        :valor,
        :tipo,
        :descricao,
        :dia,
        :data_inicio,
        :data_fim,
        :frequencia,
        :tipo_cobranca,
        :total_parcelas
    )
");

$stmt->execute([
    'usuario_id' => $usuarioId,
    'valor' => $valor,
    'tipo' => $tipo,
    'descricao' => $descricao,
    'dia' => $dia,
    'data_inicio' => $dataInicio,
    'data_fim' => $dataFim,
    'frequencia' => $frequencia,
    'tipo_cobranca' => $cobranca,
    'total_parcelas' =>
        $cobranca === 'parcelada'
            ? $totalParcelas
            : null
]);


/*
 * =========================
 * MENSAGEM
 * =========================
 */

$_SESSION['sucesso'] =
    $cobranca === 'parcelada'
        ? 'Despesa parcelada cadastrada com sucesso!'
        : 'Despesa fixa cadastrada com sucesso!';


header('Location: index.php');
exit;