<?php
declare(strict_types=1);

require_once 'classes/Carteira.php';
require_once 'conexao.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';


/* =====================================================
   TRANSAÇÕES
   ===================================================== */

$stmt = $pdo->prepare("
    SELECT id, valor, tipo, data, descricao
    FROM transacoes
    WHERE usuario_id = :usuario_id
    ORDER BY data DESC, id DESC
");

$stmt->execute([
    'usuario_id' => $usuarioId
]);

$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   SALDO ATUAL
   ===================================================== */

$saldo = 0;

foreach ($transacoes as $t) {

    $valor = (float) $t['valor'];

    if ($t['tipo'] === 'Entrada') {
        $saldo += $valor;
    } else {
        $saldo -= $valor;
    }
}

$carteira = new Carteira();
$carteira->definirSaldo($saldo);


/* =====================================================
   ANO ATUAL
   ===================================================== */

$anoAtual = (int) date('Y');
$mesAtual = (int) date('m');


/* =====================================================
   RESUMO MENSAL REAL
   ===================================================== */

$stmt = $pdo->prepare("
    SELECT
        DATE_FORMAT(data, '%Y-%m') AS mes,

        SUM(
            CASE
                WHEN tipo = 'Entrada' THEN valor
                ELSE 0
            END
        ) AS entrada,

        SUM(
            CASE
                WHEN tipo = 'Saida' THEN valor
                ELSE 0
            END
        ) AS saida,

        SUM(
            CASE
                WHEN tipo = 'Diario' THEN valor
                ELSE 0
            END
        ) AS diario

    FROM transacoes

    WHERE usuario_id = :usuario_id

    GROUP BY DATE_FORMAT(data, '%Y-%m')

    ORDER BY mes ASC
");

$stmt->execute([
    'usuario_id' => $usuarioId
]);

$transacoesMensais = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* =====================================================
   ORGANIZA OS DADOS POR MÊS
   ===================================================== */

$dadosMensais = [];

foreach ($transacoesMensais as $mes) {

    $dadosMensais[$mes['mes']] = [
        'entrada' => (float) $mes['entrada'],
        'saida'   => (float) $mes['saida'],
        'diario'  => (float) $mes['diario']
    ];
}


/* =====================================================
   MONTA TODOS OS 12 MESES DO ANO
   ===================================================== */

$resumoAnual = [];

for ($mes = 1; $mes <= 12; $mes++) {

    $chave = sprintf(
        '%04d-%02d',
        $anoAtual,
        $mes
    );

    $entrada = $dadosMensais[$chave]['entrada'] ?? 0;
    $saida   = $dadosMensais[$chave]['saida'] ?? 0;
    $diario  = $dadosMensais[$chave]['diario'] ?? 0;

    $resultado = $entrada - $saida - $diario;

    if ($mes < $mesAtual) {

        $situacao = 'Real';

    } elseif ($mes === $mesAtual) {

        $situacao = 'Atual';

    } else {

        $situacao = 'Previsão';
    }

    $resumoAnual[] = [
        'mes'       => $chave,
        'entrada'   => $entrada,
        'saida'     => $saida,
        'diario'    => $diario,
        'resultado' => $resultado,
        'situacao'  => $situacao
    ];
}


/* =====================================================
   SALDO ANTERIOR AO ANO ATUAL
   ===================================================== */

$stmt = $pdo->prepare("
    SELECT
        COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Entrada' THEN valor
                    WHEN tipo IN ('Saida', 'Diario') THEN -valor
                    ELSE 0
                END
            ),
            0
        ) AS saldo_anterior

    FROM transacoes

    WHERE usuario_id = :usuario_id
      AND data < :inicio_ano
");

$stmt->execute([
    'usuario_id' => $usuarioId,
    'inicio_ano' => $anoAtual . '-01-01'
]);

$saldoAcumulado = (float) $stmt->fetchColumn();


/* =====================================================
   SALDO ACUMULADO MÊS A MÊS
   ===================================================== */

foreach ($resumoAnual as &$mes) {

    $saldoAcumulado += $mes['resultado'];

    $mes['saldo'] = $saldoAcumulado;
}

unset($mes);


/* =====================================================
   PRÓXIMO ANO — PREVISÃO
   ===================================================== */

$proximoAno = $anoAtual + 1;

$previsaoProximoAno = [];

for ($mes = 1; $mes <= 12; $mes++) {

    $chave = sprintf(
        '%04d-%02d',
        $proximoAno,
        $mes
    );

    $previsaoProximoAno[] = [
        'mes'       => $chave,
        'entrada'   => 0,
        'saida'     => 0,
        'diario'    => 0,
        'resultado' => 0
    ];
}


/* =====================================================
   ORÇAMENTO DIÁRIO
   ===================================================== */

$hoje = new DateTime();

$diaAtual = (int) $hoje->format('d');

$diasNoMes = (int) $hoje->format('t');

$diasRestantes = $diasNoMes - $diaAtual + 1;


/* =====================================================
   MOVIMENTAÇÃO DO MÊS ATUAL
   ===================================================== */

$stmt = $pdo->prepare("
    SELECT

        COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Entrada' THEN valor
                    ELSE 0
                END
            ),
            0
        ) AS entradas,

        COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Saida' THEN valor
                    ELSE 0
                END
            ),
            0
        ) AS saidas,

        COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Diario' THEN valor
                    ELSE 0
                END
            ),
            0
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

$orcamentoMes = $stmt->fetch(PDO::FETCH_ASSOC);


/* =====================================================
   VALORES DO MÊS
   ===================================================== */

$entradasMes = (float) ($orcamentoMes['entradas'] ?? 0);

$saidasMes = (float) ($orcamentoMes['saidas'] ?? 0);

$diariosMes = (float) ($orcamentoMes['diarios'] ?? 0);


/* =====================================================
   DISPONÍVEL NO MÊS
   ===================================================== */

$disponivelMes =
    $entradasMes
    - $saidasMes
    - $diariosMes;


/* =====================================================
   LIMITE DIÁRIO
   ===================================================== */

$limiteDiario = $diasRestantes > 0
    ? $disponivelMes / $diasRestantes
    : 0;


/* =====================================================
   GASTOS DIÁRIOS DE HOJE
   ===================================================== */

$stmt = $pdo->prepare("
    SELECT
        COALESCE(
            SUM(valor),
            0
        )

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


/* =====================================================
   QUANTO AINDA PODE GASTAR HOJE
   ===================================================== */

$disponivelHoje =
    $limiteDiario - $diarioHoje;


/* =====================================================
   NOTIFICAÇÃO
   ===================================================== */

if ($disponivelMes < 0) {

    $notificacaoClasse = 'danger';

    $notificacaoTitulo = '🚨 Atenção!';

    $notificacaoTexto =
        'Seu orçamento do mês está negativo em '
        . dinheiro(abs($disponivelMes))
        . '.';

} elseif ($disponivelHoje < 0) {

    $notificacaoClasse = 'warning';

    $notificacaoTitulo = '⚠️ Cuidado!';

    $notificacaoTexto =
        'Você ultrapassou o limite de hoje em '
        . dinheiro(abs($disponivelHoje))
        . '.';

} else {

    $notificacaoClasse = 'success';

    $notificacaoTitulo = '💡 Orçamento diário';

    $notificacaoTexto =
        'Você pode gastar até '
        . dinheiro(max(0, $limiteDiario))
        . ' por dia para não negativar.';
}


/* =====================================================
   FORMATAÇÃO
   ===================================================== */

function dinheiro(float $valor): string
{
    return 'R$ ' . number_format(
        $valor,
        2,
        ',',
        '.'
    );
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

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <script
        src="https://cdn.jsdelivr.net/npm/sweetalert2@11">
    </script>

    <link
        rel="website icon"
        href="real.svg"
        type="svg"
    >

    <title>
        MyPocket - Controle Financeiro
    </title>

    <style>

        body {
            background: #76a5af;
        }

        .descricao {
            max-width: 300px;
            overflow-wrap: anywhere;
        }

        .card {
            border: 0;
            border-radius: 12px;
        }

        .alert {
            border: 0;
            border-radius: 12px;
        }

    </style>

</head>

<body>

<div class="container py-5">


    <!-- =================================================
         CABEÇALHO
         ================================================= -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="text-white m-0">
                💰 MyPocket
            </h1>

            <p class="text-white mb-0">
                Olá,
                <?= htmlspecialchars($usuarioNome) ?>!
            </p>

        </div>

        <div class="d-flex gap-2">

            <a
                href="exportar.php"
                class="btn btn-sm btn-dark"
            >
                📊 Exportar CSV
            </a>

            <a
                href="logout.php"
                class="btn btn-sm btn-danger"
            >
                Sair
            </a>

        </div>

    </div>


    <!-- =================================================
         ALERTAS DA SESSÃO
         ================================================= -->

    <?php if (isset($_SESSION['erro'])): ?>

        <script>

            Swal.fire({
                icon: 'error',
                title: 'Ops!',
                text: <?= json_encode($_SESSION['erro']) ?>
            });

        </script>

        <?php unset($_SESSION['erro']); ?>

    <?php endif; ?>


    <?php if (isset($_SESSION['sucesso'])): ?>

        <script>

            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: <?= json_encode($_SESSION['sucesso']) ?>
            });

        </script>

        <?php unset($_SESSION['sucesso']); ?>

    <?php endif; ?>


    <!-- =================================================
         NOTIFICAÇÃO DE ORÇAMENTO
         ================================================= -->

    <?php if ($diasRestantes > 0): ?>

        <div
            class="alert alert-<?= $notificacaoClasse ?> shadow-sm mb-4"
        >

            <div class="d-flex align-items-center">

                <div class="fs-3 me-3">

                    <?= $notificacaoClasse === 'danger'
                        ? '🚨'
                        : ($notificacaoClasse === 'warning'
                            ? '⚠️'
                            : '💡')
                    ?>

                </div>

                <div>

                    <h5 class="mb-1">
                        <?= $notificacaoTitulo ?>
                    </h5>

                    <div>
                        <?= htmlspecialchars($notificacaoTexto) ?>
                    </div>

                    <?php if ($notificacaoClasse !== 'danger'): ?>

                        <small class="d-block mt-1">

                            Hoje você já gastou

                            <strong>
                                <?= dinheiro($diarioHoje) ?>
                            </strong>

                            em gastos diários.

                        </small>

                        <small class="d-block">

                            Ainda pode gastar hoje:

                            <strong>
                                <?= dinheiro(max(0, $disponivelHoje)) ?>
                            </strong>

                        </small>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    <?php endif; ?>


    <!-- =================================================
         CONTEÚDO PRINCIPAL
         ================================================= -->

    <div class="row">


        <!-- =================================================
             COLUNA ESQUERDA
             ================================================= -->

        <div class="col-md-4 mb-4">


            <!-- SALDO -->

            <div class="card p-3 shadow-sm mb-4">

                <h6 class="text-muted text-uppercase">
                    Saldo disponível
                </h6>

                <h2
                    class="<?= $saldo < 0
                        ? 'text-danger'
                        : 'text-success'
                    ?>"
                >

                    <?= dinheiro($saldo) ?>

                </h2>

            </div>


            <!-- NOVA TRANSAÇÃO -->

            <div class="card p-3 shadow-sm">

                <h5 class="mb-3">
                    Nova Transação
                </h5>

                <form
                    action="processa.php"
                    method="POST"
                >


                    <!-- VALOR -->

                    <div class="mb-3">

                        <label class="form-label">
                            Valor
                        </label>

                        <input
                            type="number"
                            name="valor"
                            step="0.01"
                            min="0.01"
                            class="form-control"
                            placeholder="0,00"
                            required
                        >

                    </div>


                    <!-- TIPO -->

                    <div class="mb-3">

                        <label class="form-label">
                            Tipo
                        </label>

                        <select
                            name="tipo"
                            class="form-select"
                            required
                        >

                            <option value="Saida">
                                Saída/Gasto Fixo
                            </option>

                            <option value="Diario">
                                Diário/Gasto Menor
                            </option>

                            <option value="Entrada">
                                Entrada/Ganho
                            </option>

                        </select>

                    </div>


                    <!-- DATA -->

                    <div class="mb-3">

                        <label class="form-label">
                            Data
                        </label>

                        <input
                            type="date"
                            name="data"
                            class="form-control"
                            value="<?= date('Y-m-d') ?>"
                            required
                        >

                    </div>


                    <!-- DESCRIÇÃO -->

                    <div class="mb-3">

                        <label class="form-label">
                            Descrição
                        </label>

                        <input
                            type="text"
                            name="descricao"
                            class="form-control"
                            placeholder="Ex: Mercado, Almoço"
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        Cadastrar
                    </button>

                </form>

            </div>

        </div>


        <!-- =================================================
             EXTRATO
             ================================================= -->

        <div class="col-md-8 mb-4">

            <div class="card p-3 shadow-sm h-100">

                <h5 class="mb-3">
                    Extrato de Transações
                </h5>


                <?php if (!$transacoes): ?>

                    <p class="text-muted">
                        Nenhuma transação encontrada.
                    </p>

                <?php else: ?>

                    <div class="table-responsive">

                        <table
                            class="table table-striped table-hover align-middle"
                        >

                            <thead>

                                <tr>

                                    <th>
                                        Valor
                                    </th>

                                    <th>
                                        Tipo
                                    </th>

                                    <th>
                                        Data
                                    </th>

                                    <th>
                                        Descrição
                                    </th>

                                    <th class="text-center">
                                        Ações
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($transacoes as $t): ?>

                                <?php

                                $tipo = $t['tipo'];

                                $entrada =
                                    $tipo === 'Entrada';

                                $diario =
                                    $tipo === 'Diario';

                                $cor = $entrada

                                    ? 'text-success'

                                    : ($diario
                                        ? 'text-warning text-dark'
                                        : 'text-danger');

                                $badge = $entrada

                                    ? 'bg-success'

                                    : ($diario
                                        ? 'bg-warning text-dark'
                                        : 'bg-danger');

                                ?>

                                <tr>

                                    <td
                                        class="<?= $cor ?> fw-bold"
                                    >

                                        <?= dinheiro(
                                            (float) $t['valor']
                                        ) ?>

                                    </td>

                                    <td>

                                        <span
                                            class="badge <?= $badge ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $tipo
                                            ) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <?= date(
                                            'd/m/Y',
                                            strtotime($t['data'])
                                        ) ?>

                                    </td>

                                    <td class="descricao">

                                        <?= htmlspecialchars(
                                            $t['descricao']
                                        ) ?>

                                    </td>

                                    <td class="text-center">

                                        <a
                                            href="editar.php?id=<?= $t['id'] ?>"
                                            class="btn btn-sm btn-warning"
                                            title="Editar"
                                        >
                                            ✏️
                                        </a>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-danger"
                                            onclick="confirmarExclusao(<?= $t['id'] ?>)"
                                            title="Excluir"
                                        >
                                            🗑️
                                        </button>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- =================================================
         RESUMO ANUAL
         ================================================= -->

    <div class="card p-3 shadow-sm">

        <div
            class="d-flex justify-content-between align-items-center mb-3"
        >

            <h5 class="m-0">
                📊 Resumo Anual
            </h5>

            <span class="badge bg-secondary">
                <?= $anoAtual ?>
            </span>

        </div>


        <div class="table-responsive">

            <table
                class="table table-bordered table-striped text-center align-middle"
            >

                <thead class="table-dark">

                    <tr>

                        <th>
                            Mês
                        </th>

                        <th>
                            Entradas
                        </th>

                        <th>
                            Saídas
                        </th>

                        <th>
                            Diário
                        </th>

                        <th>
                            Resultado
                        </th>

                        <th>
                            Saldo
                        </th>

                        <th>
                            Situação
                        </th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($resumoAnual as $mes): ?>

                    <tr>

                        <td class="fw-bold">

                            <?= nomeMes(
                                $mes['mes']
                            ) ?>

                        </td>


                        <td class="text-success fw-bold">

                            + <?= dinheiro(
                                $mes['entrada']
                            ) ?>

                        </td>


                        <td class="text-danger">

                            - <?= dinheiro(
                                $mes['saida']
                            ) ?>

                        </td>


                        <td class="text-warning text-dark">

                            - <?= dinheiro(
                                $mes['diario']
                            ) ?>

                        </td>


                        <td
                            class="<?= $mes['resultado'] >= 0
                                ? 'text-success'
                                : 'text-danger'
                            ?> fw-bold"
                        >

                            <?= $mes['resultado'] >= 0
                                ? '+'
                                : ''
                            ?>

                            <?= dinheiro(
                                $mes['resultado']
                            ) ?>

                        </td>


                        <td
                            class="<?= $mes['saldo'] >= 0
                                ? 'text-primary'
                                : 'text-danger'
                            ?> fw-bold"
                        >

                            <?= dinheiro(
                                $mes['saldo']
                            ) ?>

                        </td>


                        <td>

                            <?php if (
                                $mes['situacao'] === 'Real'
                            ): ?>

                                <span class="badge bg-secondary">
                                    Real
                                </span>

                            <?php elseif (
                                $mes['situacao'] === 'Atual'
                            ): ?>

                                <span class="badge bg-primary">
                                    Atual
                                </span>

                            <?php else: ?>

                                <span
                                    class="badge bg-warning text-dark"
                                >
                                    Previsão
                                </span>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>


        <!-- =================================================
             PRÓXIMO ANO
             ================================================= -->

        <div class="mt-5">

            <div
                class="d-flex justify-content-between align-items-center mb-3"
            >

                <h5 class="m-0">
                    🔮 <?= $proximoAno ?> — Próximo ano / Previsão
                </h5>

                <span class="badge bg-warning text-dark">
                    Previsão
                </span>

            </div>


            <div class="table-responsive">

                <table
                    class="table table-bordered table-striped text-center align-middle"
                >

                    <thead class="table-secondary">

                        <tr>

                            <th>
                                Mês
                            </th>

                            <th>
                                Entradas previstas
                            </th>

                            <th>
                                Saídas previstas
                            </th>

                            <th>
                                Diário previsto
                            </th>

                            <th>
                                Resultado
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach (
                        $previsaoProximoAno
                        as $mes
                    ): ?>

                        <tr>

                            <td class="fw-bold">

                                <?= nomeMes(
                                    $mes['mes']
                                ) ?>

                            </td>

                            <td class="text-success">

                                + <?= dinheiro(
                                    $mes['entrada']
                                ) ?>

                            </td>

                            <td class="text-danger">

                                - <?= dinheiro(
                                    $mes['saida']
                                ) ?>

                            </td>

                            <td class="text-warning text-dark">

                                - <?= dinheiro(
                                    $mes['diario']
                                ) ?>

                            </td>

                            <td class="fw-bold">

                                <?= dinheiro(
                                    $mes['resultado']
                                ) ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<script>

function confirmarExclusao(id) {

    Swal.fire({

        title: 'Excluir transação?',

        text: 'Essa ação não poderá ser desfeita.',

        icon: 'warning',

        showCancelButton: true,

        confirmButtonText: 'Sim, excluir',

        cancelButtonText: 'Cancelar',

        reverseButtons: true

    }).then(resultado => {

        if (resultado.isConfirmed) {

            window.location.href =
                `deletar.php?id=${id}`;

        }

    });

}

</script>

</body>

</html>
