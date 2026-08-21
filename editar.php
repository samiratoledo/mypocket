<?php
declare(strict_types=1);

require_once 'conexao.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$usuarioId = (int) $_SESSION['usuario_id'];

if (!$id) {
    header('Location: index.php');
    exit;
}

/* Busca a transação */
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

/* Atualização */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $valor = (float) ($_POST['valor'] ?? 0);
    $tipo = trim($_POST['tipo'] ?? '');
    $data = trim($_POST['data'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');

    $tipos = [
        'Entrada' => 'Entrada',
        'Saida' => 'Saida',
        'Saída' => 'Saida',
        'Diario' => 'Diario',
        'Diário' => 'Diario'
    ];

    $tipo = $tipos[$tipo] ?? null;

    if ($valor <= 0 || !$tipo || !$data || !$descricao) {
        $_SESSION['erro'] = 'Preencha todos os campos corretamente.';
        header("Location: editar.php?id=$id");
        exit;
    }

    /* Saldo sem considerar a transação atual */
    $stmt = $pdo->prepare("
        SELECT COALESCE(
            SUM(
                CASE
                    WHEN tipo = 'Entrada' THEN valor
                    ELSE -valor
                END
            ), 0
        )
        FROM transacoes
        WHERE usuario_id = :usuario_id
        AND id != :id
    ");

    $stmt->execute([
        'usuario_id' => $usuarioId,
        'id' => $id
    ]);

    $saldo = (float) $stmt->fetchColumn();

    if ($tipo !== 'Entrada' && $valor > $saldo) {
        $_SESSION['erro'] = 'Saldo insuficiente para essa alteração.';
        header("Location: editar.php?id=$id");
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE transacoes
        SET valor = :valor,
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

    $_SESSION['sucesso'] = 'Transação atualizada com sucesso.';

    header('Location: index.php');
    exit;
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

    <title>Editar Transação - MyPocket</title>
</head>

<body>

<div class="container py-5">

    <div class="card p-4 shadow-sm mx-auto" style="max-width: 600px">

        <h2 class="mb-4">Editar Transação</h2>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Valor</label>

                <input
                    type="number"
                    name="valor"
                    step="0.01"
                    min="0.01"
                    class="form-control"
                    value="<?= htmlspecialchars($transacao['valor']) ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Tipo</label>

                <select name="tipo" class="form-select" required>

                    <option
                        value="Entrada"
                        <?= $transacao['tipo'] === 'Entrada' ? 'selected' : '' ?>
                    >
                        Entrada/Ganho
                    </option>

                    <option
                        value="Saida"
                        <?= $transacao['tipo'] === 'Saida' ? 'selected' : '' ?>
                    >
                        Saída/Gasto Fixo
                    </option>

                    <option
                        value="Diario"
                        <?= $transacao['tipo'] === 'Diario' ? 'selected' : '' ?>
                    >
                        Diário/Gasto Menor
                    </option>

                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Data</label>

                <input
                    type="date"
                    name="data"
                    class="form-control"
                    value="<?= htmlspecialchars($transacao['data']) ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Descrição</label>

                <input
                    type="text"
                    name="descricao"
                    class="form-control"
                    value="<?= htmlspecialchars($transacao['descricao']) ?>"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary">
                Salvar alterações
            </button>

            <a href="index.php" class="btn btn-secondary">
                Cancelar
            </a>

        </form>

    </div>

</div>

</body>
</html>