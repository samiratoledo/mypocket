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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valor = (float) ($_POST['valor'] ?? 0);
    $tipoInput = trim($_POST['tipo'] ?? '');
    $data = trim($_POST['data'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $usuarioId = (int) $_SESSION['usuario_id'];

    // Normaliza para comparação ignorando acentos e maiúsculas
    $tipoNormalizado = mb_strtolower($tipoInput);

    if ($tipoNormalizado === 'entrada') {
        $tipoBanco = 'Entrada';
    } elseif ($tipoNormalizado === 'saida' || $tipoNormalizado === 'saída') {
        $tipoBanco = 'Saída';
    } else {
        $_SESSION['erro'] = 'Tipo de transação inválido!';
        header('Location: index.php');
        exit;
    }

    if ($valor <= 0 || empty($data) || empty($descricao)) {
        $_SESSION['erro'] = 'Preencha todos os campos corretamente!';
        header('Location: index.php');
        exit;
    }

    // Inserção no Banco de Dados
    $stmt = $pdo->prepare("
        INSERT INTO transacoes (valor, tipo, data, descricao, usuario_id)
        VALUES (:valor, :tipo, :data, :descricao, :usuario_id)
    ");

    $sucesso = $stmt->execute([
        'valor'      => $valor,
        'tipo'       => $tipoBanco,
        'data'       => $data,
        'descricao'  => $descricao,
        'usuario_id' => $usuarioId
    ]);

    if ($sucesso) {
        $_SESSION['sucesso'] = 'Transação cadastrada com sucesso!';
    } else {
        $_SESSION['erro'] = 'Erro ao salvar transação no banco de dados.';
    }

    header('Location: index.php');
    exit;
}