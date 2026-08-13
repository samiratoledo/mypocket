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

$usuarioId = $_SESSION['usuario_id'];

$carteira = new Carteira();

/*
 * BUSCAR TRANSAÇÕES DO USUÁRIO
 */
$stmt = $pdo->prepare("
    SELECT *
    FROM transacoes
    WHERE usuario_id = :usuario_id
    ORDER BY data DESC, id DESC
");

$stmt->execute([
    'usuario_id' => $usuarioId
]);

$transacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);


/*
 * CALCULAR O SALDO
 *
 * Aqui NÃO usamos addTransacao(),
 * porque essas transações já estão salvas no banco.
 */
$saldo = 0;

foreach ($transacoes as $t) {

    if ($t['tipo'] === 'Entrada') {
        $saldo += (float) $t['valor'];
    } else {
        $saldo -= (float) $t['valor'];
    }
}


/*
 * COLOCAR O SALDO CALCULADO NA CARTEIRA
 */
$carteira->definirSaldo($saldo);


/*
 * CARREGAR O HISTÓRICO
 *
 * Aqui também NÃO usamos addTransacao(),
 * pois ele verifica saldo e isso só deve acontecer
 * quando uma nova transação é criada.
 */
foreach ($transacoes as $t) {

    if ($t['tipo'] === 'Entrada') {

        $registro = new Receita(
            (float) $t['valor'],
            $t['data'],
            $t['descricao'],
            (int) $t['id']
        );

    } else {

        $registro = new Despesa(
            (float) $t['valor'],
            $t['data'],
            $t['descricao'],
            (int) $t['id']
        );
    }

    $carteira->carregarTransacao($registro);
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous"
    >

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="website icon" href="real.svg" type="svg">

    <title>MyPocket</title>

</head>

<body>

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

</style>

<div class="container py-5">

    <!-- BOTÕES DO TOPO -->

    <h1>💰 MyPocket</h1>
    <div class="d-flex justify-content-end gap-3 mb-3">


        <a href="exportar.php" class="btn btn-sm btn-dark">
            📊 Exportar Excel (.CSV)
        </a>

        <a href="logout.php" class="btn btn-sm btn-danger">
            Sair
        </a>

    </div>


    <!-- MENSAGENS -->

    <div class="mb-4">

        <?php

        if (isset($_SESSION['erro'])) {

            echo "<div class='alert alert-danger'>"
                . htmlspecialchars($_SESSION['erro'])
                . "</div>";

            unset($_SESSION['erro']);
        }


        if (isset($_SESSION['sucesso'])) {

            echo "<div class='alert alert-success'>"
                . htmlspecialchars($_SESSION['sucesso'])
                . "</div>";

            unset($_SESSION['sucesso']);
        }

        ?>

    </div>


    <div class="row">

        <!-- COLUNA ESQUERDA -->

        <div class="col-md-4 mb-4">

            <!-- SALDO -->

            <div class="card mb-4 p-3 shadow-sm">

                <h5 class="text-muted text-uppercase small">
                    Saldo Disponível
                </h5>

                <h2>
                    R$
                    <?php
                    echo number_format(
                        $carteira->getSaldo(),
                        2,
                        ',',
                        '.'
                    );
                    ?>
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
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Tipo
                        </label>

                        <select
                            class="form-select"
                            name="tipo"
                            required
                        >

                            <option value="Saída">
                                Despesa/Gasto
                            </option>

                            <option value="Entrada">
                                Receita/Ganho
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
                            required
                        >

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary w-100 mt-2"
                    >
                        Enviar!
                    </button>

                </form>

            </div>

        </div>


        <!-- COLUNA DIREITA -->

        <div class="col-md-8">

            <div class="card p-3 shadow-sm">

                <h5 class="mb-3">
                    Extrato Consolidado
                </h5>


                <table class="table table-striped table-hover">

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

                            <th>
                                Ações
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    $historico = $carteira->getHistorico();

                    foreach ($historico as $transacao):

                        if ($transacao->getTipo() === "Entrada") {

                            $classeCor = "text-success fw-bold";

                        } else {

                            $classeCor = "text-danger fw-bold";

                        }

                        $timestamp = strtotime(
                            $transacao->getData()
                        );

                        $dataFormatada =
                            ($timestamp !== false)
                            ? date('d/m/Y', $timestamp)
                            : '---';

                    ?>

                        <tr>

                            <td class="<?= $classeCor ?>">

                                R$
                                <?= number_format(
                                    $transacao->getValor(),
                                    2,
                                    ',',
                                    '.'
                                ) ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $transacao->getTipo()
                                ) ?>

                            </td>


                            <td>

                                <?= $dataFormatada ?>

                            </td>


                            <td>

                                <?= htmlspecialchars(
                                    $transacao->getDescricao()
                                ) ?>

                            </td>


                            <td>

                                <!-- EDITAR -->

                                <a
                                    href="editar.php?id=<?= $transacao->getId() ?>"
                                    class="btn btn-sm btn-warning me-1"
                                >
                                    ✏️
                                </a>


                                <!-- EXCLUIR -->

                                <button
                                    type="button"
                                    class="btn btn-sm btn-danger"
                                    onclick="confirmarExclusao(<?= $transacao->getId() ?>)"
                                >
                                    🗑️
                                </button>

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

    }).then((resultado) => {

        if (resultado.isConfirmed) {

            window.location.href = 'deletar.php?id=' + id;

        }

    });

}

</script>


</body>

</html>