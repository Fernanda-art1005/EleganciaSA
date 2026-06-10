/**
 * SGF — Dashboard Gerencial
 * Atualização assíncrona dos KPIs industriais via Fetch API (ES6)
 */

document.addEventListener("DOMContentLoaded", () => {
    const INTERVALO_ATUALIZACAO = 30_000; // 30 segundos

    function sincronizarKpisIndustriais() {
        fetch('/api/dashboard/kpis')
            .then(response => {
                if (!response.ok) throw new Error("Falha de comunicação com a API.");
                return response.json();
            })
            .then(dados => {
                const elDisp   = document.querySelector('.card-disponibilidade');
                const elAloc   = document.querySelector('.card-alocados');
                const elAtraso = document.querySelector('.card-atrasos');

                if (elDisp)   elDisp.textContent   = dados.taxa_disponibilidade + '%';
                if (elAloc)   elAloc.textContent   = dados.ativos_alocados;
                if (elAtraso) elAtraso.textContent = dados.ativos_atrasados;

                // Alerta visual quando há atrasos críticos
                const cardAtraso = elAtraso?.closest('.card');
                if (cardAtraso) {
                    cardAtraso.classList.toggle('card-vermelho', dados.ativos_atrasados > 0);
                    cardAtraso.classList.toggle('card-cinza',    dados.ativos_atrasados === 0);
                }
            })
            .catch(erro => console.error("Erro crítico na automação do painel:", erro));
    }

    // Executa imediatamente ao carregar e depois em intervalos
    sincronizarKpisIndustriais();
    setInterval(sincronizarKpisIndustriais, INTERVALO_ATUALIZACAO);
});
