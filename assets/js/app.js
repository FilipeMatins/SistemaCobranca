// ==================== INICIALIZAÇÃO ====================
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar idioma
    inicializarIdioma();
    
    // Carregar dados
    carregarConfiguracoes();
    carregarNotinhas();
    carregarExcluidos();
    carregarInadimplentes();
    carregarRecebidos();
    carregarTodosClientes();
    adicionarCliente();
    document.getElementById('data-cobranca').valueAsDate = new Date();
    
    // Verificar lembretes (cobranças próximas)
    verificarLembretes();

    // Tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            document.getElementById('tab-' + tab.dataset.tab).style.display = 'block';
            
            // Carregar dados específicos da aba
            if (tab.dataset.tab === 'dashboard') {
                carregarDashboard();
            }
            if (tab.dataset.tab === 'clientes') {
                carregarTodosClientes();
            }
            if (tab.dataset.tab === 'inadimplentes') {
                carregarInadimplentes();
            }
            if (tab.dataset.tab === 'recebidos') {
                carregarRecebidos();
            }
        });
    });

    // Autocomplete empresa
    document.getElementById('empresa').addEventListener('input', debounce(buscarEmpresas, 300));

    // Filtros
    document.getElementById('filtro-busca').addEventListener('input', debounce(aplicarFiltros, 300));
    document.getElementById('filtro-status').addEventListener('change', aplicarFiltros);
    document.getElementById('filtro-data').addEventListener('change', aplicarFiltros);

    // Fechar autocomplete
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.form-group') && !e.target.closest('.nome-wrapper')) {
            document.querySelectorAll('.autocomplete-list').forEach(el => el.classList.remove('show'));
        }
        
        // Fechar dropdown idioma
        if (!e.target.closest('.idioma-selector')) {
            const dropdown = document.getElementById('idioma-dropdown');
            if (dropdown) dropdown.classList.remove('show');
        }
    });

    // Fechar modal com ESC e atalhos Enter
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            fecharConfiguracoes();
            fecharEdicao();
            fecharModalCliente();
            fecharModalPromocao();
            fecharModalEnvioPromocao();
            fecharModalHistorico();
            fecharModalAtalhos();
            fecharBuscaGlobal();
            fecharModalRelatorio();
            fecharModalRecebimento();
        }
        
        // Enter para confirmar envio de promoção
        if (e.key === 'Enter' && document.getElementById('modal-envio-promocao').classList.contains('show')) {
            const btnEnviar = document.getElementById('btn-enviar-promocao');
            const btnProximo = document.getElementById('btn-proximo-promocao');
            
            if (btnEnviar.style.display !== 'none') {
                enviarPromocaoAtual();
            } else if (btnProximo.style.display !== 'none') {
                proximaPromocao();
            }
        }
        
        // Enter para confirmar recebimento (quando modal estiver aberto)
        if (e.key === 'Enter' && document.getElementById('modal-recebimento').classList.contains('show')) {
            e.preventDefault();
            confirmarRecebimento();
        }
    });
});
