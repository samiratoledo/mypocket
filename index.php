<?php

declare(strict_types=1);

require_once 'classes/Transacao.php';
require_once 'classes/Receita.php';
require_once 'classes/Despesa.php';
require_once 'classes/Carteira.php';
require_once 'conexao.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$usuarioId = (int) $_SESSION['usuario_id'];

$carteira = new Carteira();

/*
 * 1. BUSCAR TRANSAÇÕES NO BANCO DE DADOS
 */
$stmt = $pdo->prepare("
    SELECT id, valor, tipo, data, descricao
    FROM transacoes
    WHERE usuario_id = :usuario_id
    ORDER BY data DESC, id DESC
");

$stmt->execute(['usuario_id' => $usuarioId]);
$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
 * 2. CALCULAR SALDO E ALIMENTAR A CARTEIRA
 */
$saldo = 0;

foreach ($transacoes as $t) {
    $valor = (float) $t['valor'];
    $tipoLido = mb_strtolower(trim((string) $t['tipo']));

    if ($tipoLido === 'entrada') {
        $saldo += $valor;
        $registro = new Receita($valor, $t['data'], $t['descricao'], (int) $t['id']);
    } else {
        $saldo -= $valor;
        $registro = new Despesa($valor, $t['data'], $t['descricao'], (int) $t['id']);
    }

    // Adiciona no histórico interno da carteira
    $carteira->carregarTransacao($registro);
}

// Define o saldo final
$carteira->definirSaldo($saldo);

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="website icon" href="real.svg" type="svg">

    <title>MyPocket</title>

    <style>
        body {
            background: #76a5af;
        }

        .descricao {
            max-width: 300px;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
    </style>
</head>

<body>

    <div class="container py-5">

        <!-- BOTÕES DO TOPO -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-white m-0">💰 MyPocket</h1>
            <div class="d-flex gap-2">
                <a href="exportar.php" class="btn btn-sm btn-dark">
                    📊 Exportar Excel (.CSV)
                </a>
                <a href="logout.php" class="btn btn-sm btn-danger">
                    Sair
                </a>
            </div>
        </div>

        <!-- MENSAGENS SWEETALERT2 -->
        <?php if (isset($_SESSION['erro'])): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Ops!',
                    text: '<?= htmlspecialchars($_SESSION['erro']) ?>'
                });
            </script>
            <?php unset($_SESSION['erro']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['sucesso'])): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: '<?= htmlspecialchars($_SESSION['sucesso']) ?>'
                });
            </script>
            <?php unset($_SESSION['sucesso']); ?>
        <?php endif; ?>

        <div class="row">

            <!-- COLUNA ESQUERDA -->
            <div class="col-md-4 mb-4">

                <!-- SALDO -->
                <div class="card mb-4 p-3 shadow-sm">
                    <h5 class="text-muted text-uppercase small">Saldo Disponível</h5>
                    <h2 class="<?= $carteira->getSaldo() < 0 ? 'text-danger' : 'text-success' ?>">
                        R$ <?= number_format($carteira->getSaldo(), 2, ',', '.') ?>
                    </h2>
                </div>

                <!-- NOVA TRANSAÇÃO -->
                <div class="card p-3 shadow-sm">
                    <h5 class="mb-3">Nova Transação</h5>
                    <form action="processa.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Valor</label>
                            <input type="number" name="valor" step="0.01" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="tipo" required>
                                <option value="Saida">Despesa/Gasto</option>
                                <option value="Entrada">Receita/Ganho</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Data</label>
                            <input type="date" name="data" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <input type="text" name="descricao" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mt-2">
                            Enviar!
                        </button>
                    </form>
                </div>

            </div>


            <!-- COLUNA DIREITA - EXTRATO CONSOLIDADO -->
            <div class="col-md-8">
                <div class="card p-3 shadow-sm">
                    <h5 class="mb-3">Extrato Consolidado</h5>

                    <?php if (empty($transacoes)): ?>
                        <p class="text-muted m-0">Nenhuma transação encontrada.</p>
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
                                        $tipoLido = mb_strtolower(trim((string) $t['tipo']));
                                        $isEntrada = ($tipoLido === 'entrada');
                                        $classeCor = $isEntrada ? "text-success fw-bold" : "text-danger fw-bold";

                                        $timestamp = strtotime($t['data']);
                                        $dataFormatada = ($timestamp !== false) ? date('d/m/Y', $timestamp) : '---';
                                        ?>
                                        <tr>
                                            <td class="<?= $classeCor ?>">
                                                R$ <?= number_format((float) $t['valor'], 2, ',', '.') ?>
                                            </td>

                                            <td>
                                                <span class="badge <?= $isEntrada ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= htmlspecialchars($t['tipo']) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?= $dataFormatada ?>
                                            </td>

                                            <td class="descricao">
                                                <?= htmlspecialchars($t['descricao']) ?>
                                            </td>

                                            <td class="text-center">
                                                <a href="editar.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-warning me-1"
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
            }).then((resultado) => {
                if (resultado.isConfirmed) {
                    window.location.href = 'deletar.php?id=' + id;
                }
            });
        }
    </script>

</body>

</html>