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

/* Transações */
$stmt = $pdo->prepare("
    SELECT id, valor, tipo, data, descricao
    FROM transacoes
    WHERE usuario_id = :usuario_id
    ORDER BY data DESC, id DESC
");

$stmt->execute(['usuario_id' => $usuarioId]);
$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Saldo atual */
$saldo = 0;

foreach ($transacoes as $t) {
    $valor = (float) $t['valor'];
    $saldo += $t['tipo'] === 'Entrada' ? $valor : -$valor;
}

$carteira = new Carteira();
$carteira->definirSaldo($saldo);

/* Resumo mensal */
$stmt = $pdo->prepare("
    SELECT
        DATE_FORMAT(data, '%Y-%m') AS mes,
        SUM(CASE WHEN tipo = 'Entrada' THEN valor ELSE 0 END) AS entrada,
        SUM(CASE WHEN tipo = 'Saida' THEN valor ELSE 0 END) AS saida,
        SUM(CASE WHEN tipo = 'Diario' THEN valor ELSE 0 END) AS diario
    FROM transacoes
    WHERE usuario_id = :usuario_id
    GROUP BY DATE_FORMAT(data, '%Y-%m')
    ORDER BY mes ASC
");

$stmt->execute(['usuario_id' => $usuarioId]);
$resumoMensal = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Saldo acumulado */
$saldoAcumulado = 0;

foreach ($resumoMensal as &$mes) {
    $entrada = (float) $mes['entrada'];
    $saida = (float) $mes['saida'];
    $diario = (float) $mes['diario'];

    $saldoAcumulado += $entrada - $saida - $diario;
    $mes['saldo'] = $saldoAcumulado;
}

unset($mes);

/* Formatação */
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link
        rel="website icon"
        href="real.svg"
        type="svg"
    >

    <title>MyPocket - Controle Financeiro</title>

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
    </style>
</head>

<body>

<div class="container py-5">

    <!-- CABEÇALHO -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h1 class="text-white m-0">💰 MyPocket</h1>

            <p class="text-white mb-0">
                Olá, <?= htmlspecialchars($usuarioNome) ?>!
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


    <!-- ALERTAS -->

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


    <div class="row">

        <!-- ESQUERDA -->

        <div class="col-md-4 mb-4">

            <!-- SALDO -->

            <div class="card p-3 shadow-sm mb-4">

                <h6 class="text-muted text-uppercase">
                    Saldo disponível
                </h6>

                <h2 class="<?= $saldo < 0 ? 'text-danger' : 'text-success' ?>">
                    <?= dinheiro($saldo) ?>
                </h2>

            </div>


            <!-- NOVA TRANSAÇÃO -->

            <div class="card p-3 shadow-sm">

                <h5 class="mb-3">
                    Nova Transação
                </h5>

                <form action="processa.php" method="POST">

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


        <!-- EXTRATO -->

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

                        <table class="table table-striped table-hover align-middle">

                            <thead>

                                <tr>
                                    <th>Valor</th>
                                    <th>Tipo</th>
                                    <th>Data</th>
                                    <th>Descrição</th>
                                    <th class="text-center">Ações</th>
                                </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($transacoes as $t): ?>

                                <?php
                                $tipo = $t['tipo'];
                                $entrada = $tipo === 'Entrada';
                                $diario = $tipo === 'Diario';

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

                                    <td class="<?= $cor ?> fw-bold">
                                        <?= dinheiro((float) $t['valor']) ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= $badge ?>">
                                            <?= htmlspecialchars($tipo) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= date('d/m/Y', strtotime($t['data'])) ?>
                                    </td>

                                    <td class="descricao">
                                        <?= htmlspecialchars($t['descricao']) ?>
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


    <!-- RESUMO MENSAL -->

    <div class="card p-3 shadow-sm">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="m-0">
                📊 Resumo Mensal
            </h5>

            <span class="badge bg-secondary">
                Controle financeiro
            </span>

        </div>


        <?php if (!$resumoMensal): ?>

            <p class="text-muted mb-0">
                Nenhuma movimentação registrada.
            </p>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-bordered table-striped text-center align-middle">

                    <thead class="table-dark">

                        <tr>
                            <th>Mês</th>
                            <th>Entradas</th>
                            <th>Saídas</th>
                            <th>Diário</th>
                            <th>Saldo</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($resumoMensal as $mes): ?>

                        <tr>

                            <td class="fw-bold">
                                <?= nomeMes($mes['mes']) ?>
                            </td>

                            <td class="text-success fw-bold">
                                + <?= dinheiro((float) $mes['entrada']) ?>
                            </td>

                            <td class="text-danger">
                                - <?= dinheiro((float) $mes['saida']) ?>
                            </td>

                            <td class="text-warning text-dark">
                                - <?= dinheiro((float) $mes['diario']) ?>
                            </td>

                            <td class="<?= $mes['saldo'] >= 0
                                ? 'text-primary'
                                : 'text-danger'
                            ?> fw-bold">
                                <?= dinheiro((float) $mes['saldo']) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

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
            window.location.href = `deletar.php?id=${id}`;
        }
    });
}
</script>

</body>
</html>