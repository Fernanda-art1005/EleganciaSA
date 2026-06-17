document.addEventListener("DOMContentLoaded", () => {
    const INTERVALO_ATUALIZACAO = 30000;

    function sincronizarKpisIndustriais() {
        fetch('/api/dashboard/kpis')
            .then(response => {
                if (!response.ok) throw new Error("Falha de comunicação.");
                return response.json();
            })
            .then(dados => {
                document.querySelector('.card-disponibilidade').textContent = dados.taxa_disponibilidade + '%';
                document.querySelector('.card-alocados').textContent = dados.ativos_alocados;
                document.querySelector('.card-atrasos').textContent = dados.atrasos_críticos;
            })
            .catch(erro => console.error("Erro crítico na automação do painel:", erro));
    }

    setInterval(sincronizarKpisIndustriais, INTERVALO_ATUALIZACAO);
});
