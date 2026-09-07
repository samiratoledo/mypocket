/* =========================
   EXCLUSÃO
========================= */
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

/* =========================
   COBRANÇA
========================= */
const tipoCobranca = document.getElementById('tipoCobranca');
const configCobranca = document.getElementById('configCobranca');
const parcelasCampo = document.getElementById('parcelasCampo');

if (tipoCobranca) {
    tipoCobranca.addEventListener('change', () => {
        const parcelada = tipoCobranca.value === 'parcelada';
        const normal = tipoCobranca.value === 'normal';

        if (configCobranca) configCobranca.style.display = normal ? 'none' : 'block';
        if (parcelasCampo) parcelasCampo.style.display = parcelada ? 'block' : 'none';

        document.querySelector('.extrato-scroll')?.classList.toggle('maior', !normal);
    });
}

/* =========================
   FILTRO DO EXTRATO
========================= */
const filtroMes = document.getElementById('filtroMes');
const linhas = document.querySelectorAll('tr[data-mes]');

const meses = [
    'Janeiro', 'Fevereiro', 'Março', 'Abril',
    'Maio', 'Junho', 'Julho', 'Agosto',
    'Setembro', 'Outubro', 'Novembro', 'Dezembro'
];

if (filtroMes && linhas.length > 0) {
    const mesesExtrato = [...linhas]
        .map(linha => linha.dataset.mes)
        .filter((mes, i, lista) => lista.indexOf(mes) === i)
        .sort()
        .reverse();

    mesesExtrato.forEach(mes => {
        const [ano, numero] = mes.split('-');
        filtroMes.innerHTML += `
            <option value="${mes}">
                ${meses[Number(numero) - 1]} de ${ano}
            </option>
        `;
    });

    filtroMes.addEventListener('change', () => {
        linhas.forEach(linha => {
            linha.style.display =
                filtroMes.value === 'todos' || linha.dataset.mes === filtroMes.value
                    ? ''
                    : 'none';
        });
    });
}

/* =========================
   RESUMO ANUAL
========================= */
// Garante o recebimento das variáveis injetadas pelo PHP com valores padrão
let anoSelecionado = typeof anoAtualInicial !== 'undefined' ? Number(anoAtualInicial) : new Date().getFullYear();
const dadosResumo = typeof dadosResumoAnual !== 'undefined' ? dadosResumoAnual : {};

function dinheiroJS(valor) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(valor || 0);
}

function mostrarAno() {
    const tabela = document.getElementById('tabelaResumo');
    const elementoAno = document.getElementById('anoExibido');
    const btnVoltar = document.getElementById('btnVoltar');
    const btnAvancar = document.getElementById('btnAvancar');

    if (!tabela || !elementoAno) return;

    elementoAno.textContent = anoSelecionado;
    tabela.innerHTML = '';

    const dadosDoAno = dadosResumo[anoSelecionado] || [];

    if (dadosDoAno.length === 0) {
        tabela.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-3">
                    Nenhum dado registrado para o ano de ${anoSelecionado}.
                </td>
            </tr>
        `;
    } else {
        dadosDoAno.forEach(mesData => {
            const resultadoClass = mesData.resultado >= 0 ? 'text-success' : 'text-danger';
            const saldoClass = mesData.saldo >= 0 ? 'text-primary' : 'text-danger';

            let situacao;
            if (mesData.situacao === 'Real') {
                situacao = '<span class="badge bg-secondary">Real</span>';
            } else if (mesData.situacao === 'Atual') {
                situacao = '<span class="badge bg-primary">Atual</span>';
            } else {
                situacao = '<span class="badge bg-warning text-dark">Previsão</span>';
            }

            // Extrai o mês a partir da string 'YYYY-MM'
            const partesMes = mesData.mes.split('-');
            const numeroMes = parseInt(partesMes[1], 10) - 1;
            const nomeMesFormatado = `${meses[numeroMes]} de ${anoSelecionado}`;

            tabela.innerHTML += `
                <tr>
                    <td class="fw-bold">${nomeMesFormatado}</td>
                    <td class="text-success fw-bold">+ ${dinheiroJS(mesData.entrada)}</td>
                    <td class="text-danger">- ${dinheiroJS(mesData.saida)}</td>
                    <td class="text-warning text-dark">- ${dinheiroJS(mesData.diario)}</td>
                    <td class="${resultadoClass} fw-bold">
                        ${mesData.resultado >= 0 ? '+' : ''} ${dinheiroJS(mesData.resultado)}
                    </td>
                    <td class="${saldoClass} fw-bold">${dinheiroJS(mesData.saldo)}</td>
                    <td>${situacao}</td>
                </tr>
            `;
        });
    }

    // Controle opcional de desabilitação de botões (desativado limite mínimo para permitir anos anteriores)
    if (btnVoltar) btnVoltar.disabled = false;
    if (btnAvancar) btnAvancar.disabled = false;
}

function mudarAno(direcao) {
    anoSelecionado += direcao;
    mostrarAno();
}

// Inicialização após o carregamento do DOM
document.addEventListener('DOMContentLoaded', () => {
    mostrarAno();
});