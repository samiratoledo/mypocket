# mypocket
projeto 2 bimestre de pw2 

1. O Script de Exportação (exportar.php)
Este código força o navegador a baixar o arquivo em vez de exibi-lo na tela.

PHP
<?php
// Seus dados (podem vir do banco de dados ou arrays)
$dados = [
    ['ID', 'Nome', 'Email', 'Data de Cadastro'],
    [1, 'Fulano de Tal', 'fulano@email.com', '2024-05-28'],
    [2, 'Beltrana Silva', 'beltrana@email.com', '2024-05-27'],
    [3, 'Ciclano Souza', 'ciclano@email.com', '2024-05-26']
];

// Define o nome do arquivo
$filename = "relatorio_" . date('Y-m-d') . ".csv";

// Configura os headers para forçar o download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Abre a saída (output) como se fosse um arquivo
$output = fopen('php://output', 'w');

// Adiciona o BOM (Byte Order Mark) para que o Excel reconheça acentos corretamente
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Percorre o array e escreve no CSV
foreach ($dados as $linha) {
    fputcsv($output, $linha, ';'); // O ';' é o delimitador comum no Brasil
}

fclose($output);
exit;
2. Pontos Importantes para seu Projeto
Delimitador: Usei o ponto e vírgula (;) no fputcsv. Embora o nome seja Comma (vírgula), o Excel em português costuma abrir melhor arquivos separados por ;.

Acentuação (UTF-8): Adicionei o fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));. Sem isso, nomes com acentos (como "João") podem aparecer bugados no Excel.

Segurança: Se você estiver puxando esses dados de um banco de dados (MySQL), lembre-se de tratar a conexão e usar um loop while para buscar as linhas e passá-las para o fputcsv.

3. Como usar no HTML
Basta criar um link ou botão que aponte para o seu arquivo PHP:

HTML
<a href="exportar.php" class="btn">Baixar Relatório em CSV</a>