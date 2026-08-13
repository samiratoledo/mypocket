<?php

declare(strict_types=1);

require_once 'classes/Transacao.php';
require_once 'classes/Despesa.php';
require_once 'classes/Receita.php';
require_once 'classes/Carteira.php';
require_once 'conexao.php';

session_start();

if (!isset($_SESSION['usuario_id'])) {

    header('Location: login.php');
    exit;

}

$tipo = (string) $_POST['tipo'];
$valor = (float) $_POST['valor'];
$data = (string) $_POST['data'];
$descricao = trim((string) $_POST['descricao']);

$usuarioId = $_SESSION['usuario_id'];

try {

    if ($tipo === 'Entrada') {

        $registro = new Receita(
            $valor,
            $data,
            $descricao
        );

    } elseif ($tipo === 'Saída') {

        $registro = new Despesa(
            $valor,
            $data,
            $descricao
        );

    } else {

        throw new Exception("Tipo inválido.");

    }

    // CREATE - Inserir transação no banco
    $stmt = $pdo->prepare("
        INSERT INTO transacoes
        (valor, tipo, data, descricao, usuario_id)
        VALUES
        (:valor, :tipo, :data, :descricao, :usuario_id)
    ");

    $stmt->execute([
        'valor' => $registro->getValor(),
        'tipo' => $registro->getTipo(),
        'data' => $registro->getData(),
        'descricao' => $registro->getDescricao(),
        'usuario_id' => $usuarioId
    ]);

    $_SESSION['sucesso'] = "Transação realizada com sucesso!";

} catch (Exception $e) {

    $_SESSION['erro'] = $e->getMessage();

}

header('Location: index.php');
exit;