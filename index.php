<?php

declare(strict_types=1);

require_once 'conexao.php';
require_once 'gerador_fixas.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];
$usuarioNome = $_SESSION['usuario_nome'] ?? 'Usuário';

$anoAtual = (int) date('Y');
$mesAtual = (int) date('m');
$anoFinal = $anoAtual + 5;

gerarFixas($pdo, $usuarioId);

require_once 'funcoes.php';
require_once 'dashboard/transacoes.php';
require_once 'dashboard/resumo.php';
require_once 'dashboard/orcamento.php';

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="website icon" href="real.svg" type="svg">
    <title>MyPocket - Controle Financeiro</title>

    <style>
        body {
            background: #76a5af;
        }

        .descricao {
            max-width: 300px;
            overflow-wrap: anywhere;
        }

        .card,
        .alert {
            border: 0;
            border-radius: 12px;
        }

        .extrato-scroll {
            max-height: 580px;
            overflow-y: auto;
            transition: max-height 0.3s;
        }

        .extrato-scroll.maior {
            max-height: 840px;
        }

        .extrato-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #212529;
            color: white;
        }
    </style>

</head>

<body>

    <div class="container py-5">

        <!-- CABEÇALHO -->

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h1 class="text-white m-0">
                💰 MyPocket - Olá, <?= htmlspecialchars($usuarioNome) ?>!
            </h1>

            <div class="d-flex gap-2">

                <a href="exportar.php" class="btn btn-sm btn-dark">
                    📊 Exportar CSV
                </a>

                <a href="logout.php" class="btn btn-sm btn-danger">
                    🚪 Sair
                </a>

            </div>

        </div>


        <!-- MENSAGENS -->

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


        <!-- NOTIFICAÇÃO -->

        <?php if ($diasRestantes > 0): ?>

            <div class="alert alert-<?= $notificacaoClasse ?> shadow-sm mb-4">

                <div class="d-flex align-items-center">

                    <div class="fs-3 me-3">

                        <?= $notificacaoClasse === 'danger'
                            ? '🚨'
                            : ($notificacaoClasse === 'warning'
                                ? '⚠️'
                                : '💡') ?>

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


        <!-- PRINCIPAL -->

        <div class="row">


            <!-- FORMULÁRIO -->

            <div class="col-md-4 mb-4">

                <div class="card p-3 shadow-sm mb-4">

                    <h6 class="text-muted text-uppercase">
                        Saldo disponível
                    </h6>

                    <h2 class="<?= $saldo < 0 ? 'text-danger' : 'text-success' ?>">

                        <?= dinheiro($saldo) ?>

                    </h2>

                </div>


                <div class="card p-3 shadow-sm">

                    <h5 class="mb-3">
                        Nova Transação
                    </h5>

                    <form action="processa.php" method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Valor
                            </label>

                            <input type="number" name="valor" step="0.01" min="0.01" class="form-control"
                                placeholder="0,00" required>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Tipo
                            </label>

                            <select name="tipo" class="form-select" required>

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


                        <!-- TIPO DE COBRANÇA -->

                        <div class="mb-3">

                            <label class="form-label">
                                Tipo de cobrança
                            </label>

                            <select name="tipo_cobranca" id="tipoCobranca" class="form-select">

                                <option value="normal">
                                    Normal
                                </option>

                                <option value="fixa">
                                    Fixa
                                </option>

                                <option value="parcelada">
                                    Parcelada
                                </option>

                            </select>

                        </div>

                        <!-- CONFIGURAÇÕES DA COBRANÇA -->

                        <div id="configCobranca" style="display: none;">

                            <div class="mb-3">

                                <label class="form-label">
                                    Dia da cobrança
                                </label>

                                <input type="number" name="dia" id="dia" min="1" max="31" class="form-control"
                                    value="<?= date('d') ?>">

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Frequência
                                </label>

                                <select name="frequencia" class="form-select">

                                    <option value="mensal">
                                        Mensal
                                    </option>

                                    <option value="anual">
                                        Anual
                                    </option>

                                </select>

                            </div>


                            <div class="mb-3" id="parcelasCampo" style="display: none;">

                                <label class="form-label">
                                    Total de parcelas
                                </label>

                                <input type="number" name="total_parcelas" min="1" class="form-control"
                                    placeholder="Ex: 4">

                            </div>


                            <div class="mb-3">

                                <label class="form-label">
                                    Data de início
                                </label>

                                <input type="date" name="data_inicio" class="form-control" value="<?= date('Y-m-d') ?>">

                            </div>

                        </div>




                        <div class="mb-3">

                            <label class="form-label">
                                Data
                            </label>

                            <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Descrição
                            </label>

                            <input type="text" name="descricao" class="form-control" placeholder="Ex: Mercado, Almoço"
                                required>

                        </div>


                        <button type="submit" class="btn btn-primary w-100">
                            Cadastrar
                        </button>

                    </form>

                </div>

            </div>


            <!-- EXTRATO -->

            <div class="col-md-8 mb-4">

                <div class="card p-3 shadow-sm h-100">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h5 class="m-0">
                            Extrato de Transações
                        </h5>

                        <select id="filtroMes" class="form-select form-select-sm" style="width: auto;">

                            <option value="todos">
                                Todos os meses
                            </option>

                        </select>

                    </div>


                    <?php if (!$transacoes): ?>

                        <p class="text-muted">
                            Nenhuma transação encontrada.
                        </p>

                    <?php else: ?>

                        <div class="table-responsive extrato-scroll">

                            <table class="table table-striped table-hover align-middle">

                                <thead>

                                    <tr>

                                        <th>Valor</th>
                                        <th>Tipo</th>
                                        <th>Data</th>
                                        <th>Descrição</th>
                                        <th class="text-center">
                                            Ações
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php foreach ($transacoes as $t): ?>

                                        <?php

                                        $entrada = $t['tipo'] === 'Entrada';
                                        $diario = $t['tipo'] === 'Diario';

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

                                        <tr data-mes="<?= date(
                                            'Y-m',
                                            strtotime($t['data'])
                                        ) ?>">

                                            <td class="<?= $cor ?> fw-bold">

                                                <?= dinheiro(
                                                    (float) $t['valor']
                                                ) ?>

                                            </td>


                                            <td>

                                                <span class="badge <?= $badge ?>">

                                                    <?= htmlspecialchars(
                                                        $t['tipo']
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


                                                <?php if (!empty($t['parcela'])): ?>

                                                    <small class="d-block text-muted">

                                                        Parcela
                                                        <?= $t['parcela'] ?>/<?= $t['total_parcelas'] ?>

                                                    </small>

                                                <?php endif; ?>

                                            </td>


                                            <td class="text-center">

                                                <a href="editar.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-warning"
                                                    title="Editar">
                                                    ✏️
                                                </a>

                                                <button type="button" class="btn btn-sm btn-danger"
                                                    onclick="confirmarExclusao(<?= $t['id'] ?>)" title="Excluir">
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


        <!-- RESUMO ANUAL -->

        <div class="card p-3 shadow-sm">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="m-0">
                    📊 Resumo Anual
                </h5>

                <div class="d-flex align-items-center gap-2">

                    <button class="btn btn-sm btn-outline-secondary" id="btnVoltar" onclick="mudarAno(-1)">
                        ← Voltar
                    </button>

                    <span id="anoExibido" class="badge bg-primary fs-6">
                        <?= $anoAtual ?>
                    </span>

                    <button class="btn btn-sm btn-outline-secondary" id="btnAvancar" onclick="mudarAno(1)">
                        Avançar →
                    </button>

                </div>

            </div>


            <div class="table-responsive">

                <table class="table table-bordered table-striped text-center align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>Mês</th>
                            <th>Entradas</th>
                            <th>Saídas</th>
                            <th>Diário</th>
                            <th>Resultado</th>
                            <th>Saldo</th>
                            <th>Situação</th>

                        </tr>

                    </thead>

                    <tbody id="tabelaResumo"></tbody>

                </table>

            </div>

        </div>

    </div>

    <script>
        const dadosResumoAnual = <?= json_encode($anosResumo ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const anoAtualInicial = <?= (int) ($anoAtual ?? date('Y')); ?>;
    </script>
    <script src="js/dashboard.js"></script>

</body>

</html>