<?php
declare(strict_types=1);

require_once 'classes/Transacao.php';

require_once 'classes/Despesa.php';
require_once 'classes/Receita.php';

require_once 'classes/Carteira.php';

session_start();

if (!isset($_SESSION['carteira'])) {
    $_SESSION['carteira'] = new Carteira();
}

$tipo = (string) $_POST['tipo'];
$valor = (float) $_POST['valor'];
$data = (string) $_POST['data'];
$descricao = (string) $_POST['descricao'];
$carteira = $_SESSION['carteira'];

try {
    if ($tipo == "Entrada") {
        $registro = new Receita($valor, $data, $descricao);
    } else if ($tipo == "Saída") {
        $registro = new Despesa($valor, $data, $descricao);
    }
    $carteira->addTransacao($registro);
    $_SESSION['sucesso'] = "Transação realizada com sucesso!";
} catch (Exception $e) {
    $_SESSION['erro'] = $e->getMessage();
}

header("Location: index.php");
exit;

?>