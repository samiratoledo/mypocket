<?php

require_once 'conexao.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
$usuarioId = $_SESSION['usuario_id'];

if (!$id) {
    header('Location: index.php');
    exit;
}

/* UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $valor = (float) $_POST['valor'];
    $tipo = $_POST['tipo'];
    $data = $_POST['data'];
    $descricao = trim($_POST['descricao']);

    if (
        $valor > 0 &&
        !empty($tipo) &&
        !empty($data) &&
        !empty($descricao)
    ) {

        $stmt = $pdo->prepare("
            UPDATE transacoes
            SET
                valor = :valor,
                tipo = :tipo,
                data = :data,
                descricao = :descricao
            WHERE id = :id
            AND usuario_id = :usuario_id
        ");

        $stmt->execute([
            'valor' => $valor,
            'tipo' => $tipo,
            'data' => $data,
            'descricao' => $descricao,
            'id' => $id,
            'usuario_id' => $usuarioId
        ]);

        header('Location: index.php');
        exit;
    }
}

/* READ */
$stmt = $pdo->prepare("
    SELECT *
    FROM transacoes
    WHERE id = :id
    AND usuario_id = :usuario_id
");

$stmt->execute([
    'id' => $id,
    'usuario_id' => $usuarioId
]);

$transacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transacao) {
    header('Location: index.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <title>Editar Transação - MyPocket</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body>

<div class="container py-5">

    <div class="card p-4 shadow-sm">

        <h2 class="mb-4">
            Editar Transação
        </h2>

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">
                    Valor
                </label>

                <input
                    type="number"
                    name="valor"
                    step="0.01"
                    class="form-control"
                    value="<?= htmlspecialchars($transacao['valor']) ?>"
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

                    <option
                        value="Entrada"
                        <?= $transacao['tipo'] === 'Entrada' ? 'selected' : '' ?>
                    >
                        Receita/Ganho
                    </option>

                    <option
                        value="Saída"
                        <?= $transacao['tipo'] === 'Saída' ? 'selected' : '' ?>
                    >
                        Despesa/Gasto
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
                    value="<?= htmlspecialchars($transacao['data']) ?>"
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
                    value="<?= htmlspecialchars($transacao['descricao']) ?>"
                    required
                >

            </div>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Salvar alterações
            </button>

            <a
                href="index.php"
                class="btn btn-secondary"
            >
                Cancelar
            </a>

        </form>

    </div>

</div>

</body>

</html>