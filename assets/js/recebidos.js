// ==================== RECEBIDOS ====================

let recebidosCache = [];
let totalRecebidoMesOriginal = 0;
let totalRecebidoGeralOriginal = 0;

async function carregarRecebidos() {
    try {
        const response = await fetch('api/recebidos.php');
        const data = await response.json();
        
        if (data.success) {
            recebidosCache = data.recebidos || [];
            
            // Guarda totais originais vindos da API
            totalRecebidoMesOriginal = data.totalMes || 0;
            totalRecebidoGeralOriginal = data.totalGeral || 0;
            
            // Atualiza totais iniciais (sem filtro)
            const elTotalMes = document.getElementById('total-recebido-mes');
            const elTotalGeral = document.getElementById('total-recebido-geral');
            if (elTotalMes) {
                elTotalMes.textContent = formatarValor(totalRecebidoMesOriginal);
            }
            if (elTotalGeral) {
                elTotalGeral.textContent = formatarValor(totalRecebidoGeralOriginal);
            }
            
            // Atualiza badge
            const badge = document.getElementById('badge-recebidos');
            if (badge) {
                badge.textContent = recebidosCache.length || '';
                badge.style.display = recebidosCache.length ? 'inline' : 'none';
            }
            
            renderizarRecebidos();
        }
    } catch (error) {
        console.error('Erro ao carregar recebidos:', error);
    }
}

function renderizarRecebidos() {
    const container = document.getElementById('recebidos-lista');
    if (!container) return;
    
    const busca = document.getElementById('filtro-recebidos-busca')?.value?.toLowerCase() || '';
    const mesFiltro = document.getElementById('filtro-recebidos-mes')?.value || '';
    
    let recebidosFiltrados = recebidosCache;
    
    // Filtro de busca
    if (busca) {
        recebidosFiltrados = recebidosFiltrados.filter(n => {
            const empresaMatch = n.empresa_nome?.toLowerCase().includes(busca);
            const clienteMatch = n.clientes?.some(c => c.nome?.toLowerCase().includes(busca));
            return empresaMatch || clienteMatch;
        });
    }
    
    // Filtro de mês
    if (mesFiltro) {
        recebidosFiltrados = recebidosFiltrados.filter(n => {
            const dataRecebido = n.recebido_at?.substring(0, 7);
            return dataRecebido === mesFiltro;
        });
    }
    
    // Atualiza totais conforme filtro (total do filtro no card "Recebido este Mês")
    const totalFiltrado = recebidosFiltrados.reduce((sumNotinhas, n) => {
        const totalNotinha = parseFloat(n.total_recebido || 0);
        return sumNotinhas + totalNotinha;
    }, 0);
    
    const elTotalMes = document.getElementById('total-recebido-mes');
    const elTotalGeral = document.getElementById('total-recebido-geral');
    const temFiltro = !!busca || !!mesFiltro;
    
    if (elTotalMes) {
        if (temFiltro) {
            elTotalMes.textContent = formatarValor(totalFiltrado);
        } else {
            elTotalMes.textContent = formatarValor(totalRecebidoMesOriginal || 0);
        }
    }
    if (elTotalGeral) {
        // Mantém sempre o total histórico vindo da API
        elTotalGeral.textContent = formatarValor(totalRecebidoGeralOriginal || 0);
    }
    
    if (recebidosFiltrados.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <span class="empty-icon">✅</span>
                <p>${busca || mesFiltro ? 'Nenhum recebimento encontrado com esse filtro' : 'Nenhuma notinha marcada como recebida ainda'}</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = recebidosFiltrados.map(n => {
        const clientes = n.clientes || [];
        const total = parseFloat(n.total_recebido || 0);
        const dataRecebido = n.recebido_at ? new Date(n.recebido_at).toLocaleDateString('pt-BR') : '-';
        
        return `
            <div class="notinha-row notinha-recebida">
                <div class="notinha-main">
                    <span class="empresa-nome">${n.empresa_nome || 'Sem empresa'}</span>
                    <span class="clientes-nomes">${clientes.map(c => c.nome).join(', ') || '-'}</span>
                    <span class="total">${formatarValor(total)}</span>
                    <span class="data-recebimento">${dataRecebido}</span>
                    <div class="acoes">
                        <button class="btn-desfazer-recebido" onclick="desfazerRecebido(${n.id})" title="Desfazer recebimento">
                            ↩️ Desfazer
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// A função marcarComoRecebido(id) agora é definida em notinhas.js

async function desfazerRecebido(id) {
    if (!confirm('Deseja desfazer o recebimento? A notinha voltará para a lista ativa.')) return;
    
    try {
        const response = await fetch('api/recebidos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, acao: 'desfazer' })
        });
        
        const result = await response.json();
        if (result.success) {
            showToast('↩️ Recebimento desfeito');
            carregarNotinhas();
            carregarRecebidos();
            carregarDashboard();
        } else {
            showToast(result.error || 'Erro ao desfazer', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    }
}

function limparFiltrosRecebidos() {
    document.getElementById('filtro-recebidos-busca').value = '';
    document.getElementById('filtro-recebidos-mes').value = '';
    renderizarRecebidos();
}

// Event listeners para filtros
document.addEventListener('DOMContentLoaded', () => {
    const buscaRecebidos = document.getElementById('filtro-recebidos-busca');
    const mesRecebidos = document.getElementById('filtro-recebidos-mes');
    
    if (buscaRecebidos) {
        buscaRecebidos.addEventListener('input', renderizarRecebidos);
    }
    if (mesRecebidos) {
        mesRecebidos.addEventListener('change', renderizarRecebidos);
    }
});


