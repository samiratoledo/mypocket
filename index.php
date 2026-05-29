<?php
declare(strict_types=1);

// 1º Sempre carregamos as estruturas das classes primeiro
require_once 'classes/Transacao.php';
require_once 'classes/Receita.php';
require_once 'classes/Despesa.php';
require_once 'classes/Carteira.php';

// 2º Iniciamos a sessão com as classes já na memória
session_start();

if (!isset($_SESSION['carteira'])) {
    $_SESSION['carteira'] = new Carteira();
}
$carteira = $_SESSION['carteira'];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="website icon" href="real.svg" type="svg">
    <title>MyPocket</title>
</head>

<body>
    <div class="container py-5">

        <div class="d-flex justify-content-end mb-3">
            <button id="btnTema" class="btn btn-outline-secondary">
                🌙 Modo Escuro
            </button>
        </div>

        <div class="mb-4">
            <?php
            if (isset($_SESSION['erro'])) {
                echo "<div class='alert alert-danger'>" . $_SESSION['erro'] . "</div>";
                unset($_SESSION['erro']);
            }
            if (isset($_SESSION['sucesso'])) {
                echo "<div class='alert alert-success'>" . $_SESSION['sucesso'] . "</div>";
                unset($_SESSION['sucesso']);
            }
            ?>
        </div>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card mb-4 p-3 shadow-sm">
                    <h5 class="text-muted text-uppercase small">Saldo Disponível</h5>
                    <h2>R$ <?php echo number_format($carteira->getSaldo(), 2, ',', '.'); ?></h2>
                </div>

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
                                <option value="Saída">Despesa/Gasto</option>
                                <option value="Entrada">Receita/Ganho</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Data</label>
                            <input type="date" name="data" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descrição</label>
                            <input type="text" name="descricao" class="form-control" required>
                        </div>

                        <button class="btn btn-primary w-100 mt-2">Enviar!</button>
                    </form>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card p-3 shadow-sm">
                    <h5 class="mb-3">Extrato Consolidado</h5>
                    <table class="table table-striped table-hover vertical-align-middle">
                        <thead>
                            <tr>
                                <th>Valor</th>
                                <th>Tipo</th>
                                <th>Data</th>
                                <th>Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $historico = $carteira->getHistorico();
                            foreach ($historico as $transacao) {
                                // Aplica a cor certa baseada no tipo (Requisito RF05)
                                if ($transacao->getTipo() == "Entrada") {
                                    $classeCor = "text-success fw-bold";
                                } else {
                                    $classeCor = "text-danger fw-bold";
                                }

                                $timestamp = strtotime($transacao->getData());
                                $dataFormatada = ($timestamp !== false) ? date('d/m/Y', $timestamp) : '---';

                                echo "<tr>";
                                echo "<td class='" . $classeCor . "'>R$ " . number_format($transacao->getValor(), 2, ',', '.') . "</td>";
                                echo "<td>" . $transacao->getTipo() . "</td>";
                                echo "<td>" . $dataFormatada . "</td>";
                                echo "<td>" . $transacao->getDescricao() . "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    const btnTema = document.getElementById('btnTema');
    const htmlTag = document.documentElement;

    // 1. Verifica se o usuário já tinha escolhido um tema antes
    const temaSalvo = localStorage.getItem('tema') || 'light';
    htmlTag.setAttribute('data-bs-theme', temaSalvo);
    atualizarBotao(temaSalvo);

    // 2. Escuta o clique no botão
    btnTema.addEventListener('click', () => {
        // Se estiver light, muda pra dark. Se estiver dark, muda pra light
        const novoTema = htmlTag.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        
        htmlTag.setAttribute('data-bs-theme', novoTema);
        localStorage.setItem('tema', novoTema); // Salva no navegador
        atualizarBotao(novoTema);
    });

    // 3. Função para mudar o texto/ícone do botão
    function atualizarBotao(tema) {
        if (tema === 'dark') {
            btnTema.innerHTML = '☀️ Modo Claro';
            btnTema.className = 'btn btn-outline-warning';
        } else {
            btnTema.innerHTML = '🌙 Modo Escuro';
            btnTema.className = 'btn btn-outline-dark';
        }
    }
</script>
</body>

</html>