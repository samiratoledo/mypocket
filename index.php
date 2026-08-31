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

function dinheiro(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

$anoAtual = (int) date('Y');
$mesAtual = (int) date('m');
$anoFinal = $anoAtual + 5;

$stmt = $pdo->prepare("
    SELECT id, valor, tipo, data, descricao
    FROM transacoes
    WHERE usuario_id = :usuario_id
    ORDER BY data DESC, id DESC
");
$stmt->execute(['usuario_id' => $usuarioId]);

$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$saldo = 0;

foreach ($transacoes as $t) {
    $valor = (float) $t['valor'];
    $saldo += $t['tipo'] === 'Entrada' ? $valor : -$valor;
}

$carteira = new Carteira();
$carteira->definirSaldo($saldo);

$stmt = $pdo->prepare("
    SELECT
        DATE_FORMAT(data, '%Y-%m') AS mes,
        COALESCE(SUM(CASE WHEN tipo = 'Entrada' THEN valor ELSE 0 END), 0) AS entrada,
        COALESCE(SUM(CASE WHEN tipo = 'Saida' THEN valor ELSE 0 END), 0) AS saida,
        COALESCE(SUM(CASE WHEN tipo = 'Diario' THEN valor ELSE 0 END), 0) AS diario
    FROM transacoes
    WHERE usuario_id = :usuario_id
    GROUP BY DATE_FORMAT(data, '%Y-%m')
    ORDER BY mes
");
$stmt->execute(['usuario_id' => $usuarioId]);

$dadosMensais = [];

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $m) {
    $dadosMensais[$m['mes']] = [
        'entrada' => (float) $m['entrada'],
        'saida' => (float) $m['saida'],
        'diario' => (float) $m['diario']
    ];
}

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(
        CASE
            WHEN tipo = 'Entrada' THEN valor
            WHEN tipo IN ('Saida', 'Diario') THEN -valor
            ELSE 0
        END
    ), 0)
    FROM transacoes
    WHERE usuario_id = :usuario_id
      AND data < :inicio
");

$stmt->execute([
    'usuario_id' => $usuarioId,
    'inicio' => "$anoAtual-01-01"
]);

$saldoAnterior = (float) $stmt->fetchColumn();

$anosResumo = [];

for ($ano = $anoAtual; $ano <= $anoFinal; $ano++) {
    $saldoAno = $ano === $anoAtual ? $saldoAnterior : $saldo;

    for ($mes = 1; $mes <= 12; $mes++) {
        $chave = sprintf('%04d-%02d', $ano, $mes);

        if ($ano === $anoAtual) {
            $dados = $dadosMensais[$chave] ?? [];

            $entrada = (float) ($dados['entrada'] ?? 0);
            $saida = (float) ($dados['saida'] ?? 0);
            $diario = (float) ($dados['diario'] ?? 0);

            $situacao = $mes < $mesAtual
                ? 'Real'
                : ($mes === $mesAtual ? 'Atual' : 'Previsão');
        } else {
            $entrada = 0;
            $saida = 0;
            $diario = 0;
            $situacao = 'Previsão';
        }

        $resultado = $entrada - $saida - $diario;
        $saldoAno += $resultado;

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

$hoje = new DateTime();

$diaAtual = (int) $hoje->format('d');
$diasNoMes = (int) $hoje->format('t');
$diasRestantes = $diasNoMes - $diaAtual + 1;

$stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN tipo = 'Entrada' THEN valor ELSE 0 END), 0) AS entradas,
        COALESCE(SUM(CASE WHEN tipo = 'Saida' THEN valor ELSE 0 END), 0) AS saidas,
        COALESCE(SUM(CASE WHEN tipo = 'Diario' THEN valor ELSE 0 END), 0) AS diarios
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

if ($disponivelMes < 0) {
    $notificacaoClasse = 'danger';
    $notificacaoTitulo = '🚨 Atenção!';
    $notificacaoTexto = 'Seu orçamento do mês está negativo em ' .
        dinheiro(abs($disponivelMes)) . '.';
} elseif ($disponivelHoje < 0) {
    $notificacaoClasse = 'warning';
    $notificacaoTitulo = '⚠️ Cuidado!';
    $notificacaoTexto = 'Você ultrapassou o limite de hoje em ' .
        dinheiro(abs($disponivelHoje)) . '.';
} else {
    $notificacaoClasse = 'success';
    $notificacaoTitulo = '💡 Orçamento diário';
    $notificacaoTexto = 'Você pode gastar até ' .
        dinheiro(max(0, $limiteDiario)) .
        ' por dia para não negativar.';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

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
    </style>
</head>

<body>

<div class="container py-5">

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

    <?php if ($diasRestantes > 0): ?>
        <div class="alert alert-<?= $notificacaoClasse ?> shadow-sm mb-4">
            <div class="d-flex align-items-center">
                <div class="fs-3 me-3">
                    <?= $notificacaoClasse === 'danger'
                        ? '🚨'
                        : ($notificacaoClasse === 'warning' ? '⚠️' : '💡') ?>
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
                            <strong><?= dinheiro($diarioHoje) ?></strong>
                            em gastos diários.
                        </small>

                        <small class="d-block">
                            Ainda pode gastar hoje:
                            <strong><?= dinheiro(max(0, $disponivelHoje)) ?></strong>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">

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
                        <label class="form-label">Valor</label>

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
                        <label class="form-label">Tipo</label>

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

                    <div class="mb-3">
                        <label class="form-label">Data</label>

                        <input
                            type="date"
                            name="data"
                            class="form-control"
                            value="<?= date('Y-m-d') ?>"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descrição</label>

                        <input
                            type="text"
                            name="descricao"
                            class="form-control"
                            placeholder="Ex: Mercado, Almoço"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Cadastrar
                    </button>

                </form>
            </div>

        </div>

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

                                <tr>

                                    <td class="<?= $cor ?> fw-bold">
                                        <?= dinheiro((float) $t['valor']) ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= $badge ?>">
                                            <?= htmlspecialchars($t['tipo']) ?>
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

    <div class="card p-3 shadow-sm">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="m-0">
                📊 Resumo Anual
            </h5>

            <div class="d-flex align-items-center gap-2">

                <button
                    class="btn btn-sm btn-outline-secondary"
                    id="btnVoltar"
                    onclick="mudarAno(-1)"
                >
                    ← Voltar
                </button>

                <span id="anoExibido" class="badge bg-primary fs-6">
                    <?= $anoAtual ?>
                </span>

                <button
                    class="btn btn-sm btn-outline-secondary"
                    id="btnAvancar"
                    onclick="mudarAno(1)"
                >
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

    const anoAtual = <?= $anoAtual ?>;
    const anoFinal = <?= $anoFinal ?>;

    const dadosAnos = <?= json_encode(
        $anosResumo,
        JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK
    ) ?>;

    let anoSelecionado = anoAtual;

    const meses = [
        'Janeiro',
        'Fevereiro',
        'Março',
        'Abril',
        'Maio',
        'Junho',
        'Julho',
        'Agosto',
        'Setembro',
        'Outubro',
        'Novembro',
        'Dezembro'
    ];

    function dinheiroJS(valor) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(valor);
    }

    function mostrarAno() {
        const tabela = document.getElementById('tabelaResumo');
        const ano = document.getElementById('anoExibido');
        const voltar = document.getElementById('btnVoltar');
        const avancar = document.getElementById('btnAvancar');

        ano.textContent = anoSelecionado;
        tabela.innerHTML = '';

        (dadosAnos[anoSelecionado] || []).forEach((mes, index) => {
            const resultadoClass =
                mes.resultado >= 0
                    ? 'text-success'
                    : 'text-danger';

            const saldoClass =
                mes.saldo >= 0
                    ? 'text-primary'
                    : 'text-danger';

            let situacao;

            if (mes.situacao === 'Real') {
                situacao = '<span class="badge bg-secondary">Real</span>';
            } else if (mes.situacao === 'Atual') {
                situacao = '<span class="badge bg-primary">Atual</span>';
            } else {
                situacao =
                    '<span class="badge bg-warning text-dark">Previsão</span>';
            }

            tabela.innerHTML += `
                <tr>
                    <td class="fw-bold">
                        ${meses[index]} de ${anoSelecionado}
                    </td>

                    <td class="text-success fw-bold">
                        + ${dinheiroJS(mes.entrada)}
                    </td>

                    <td class="text-danger">
                        - ${dinheiroJS(mes.saida)}
                    </td>

                    <td class="text-warning text-dark">
                        - ${dinheiroJS(mes.diario)}
                    </td>

                    <td class="${resultadoClass} fw-bold">
                        ${mes.resultado >= 0 ? '+' : ''}
                        ${dinheiroJS(mes.resultado)}
                    </td>

                    <td class="${saldoClass} fw-bold">
                        ${dinheiroJS(mes.saldo)}
                    </td>

                    <td>
                        ${situacao}
                    </td>
                </tr>
            `;
        });

        voltar.disabled = anoSelecionado <= anoAtual;
        avancar.disabled = anoSelecionado >= anoFinal;
    }

    function mudarAno(direcao) {
        const novoAno = anoSelecionado + direcao;

        if (novoAno < anoAtual || novoAno > anoFinal) {
            return;
        }

        anoSelecionado = novoAno;
        mostrarAno();
    }

    mostrarAno();
</script>

</body>
</html>
