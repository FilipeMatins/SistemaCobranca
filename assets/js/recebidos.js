// ==================== RECEBIDOS ====================

let recebidosCache = [];

async function carregarRecebidos() {
    try {
        const response = await fetch('api/recebidos.php');
        const data = await response.json();
        
        if (data.success) {
            recebidosCache = data.recebidos || [];
            
            // Atualiza totais
            document.getElementById('total-recebido-mes').textContent = formatarMoeda(data.totalMes || 0);
            document.getElementById('total-recebido-geral').textContent = formatarMoeda(data.totalGeral || 0);
            
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
        const total = clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0);
        const dataRecebido = n.recebido_at ? new Date(n.recebido_at).toLocaleDateString('pt-BR') : '-';
        
        return `
            <div class="notinha-row notinha-recebida">
                <div class="notinha-main">
                    <span class="empresa-nome">${n.empresa_nome || 'Sem empresa'}</span>
                    <span class="clientes-nomes">${clientes.map(c => c.nome).join(', ') || '-'}</span>
                    <span class="total">${formatarMoeda(total)}</span>
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

async function marcarComoRecebido(id) {
    if (!confirm('Confirma que recebeu o pagamento desta notinha?')) return;
    
    try {
        const response = await fetch('api/recebidos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, acao: 'receber' })
        });
        
        const result = await response.json();
        if (result.success) {
            showToast('✅ Marcado como recebido!');
            carregarNotinhas();
            carregarRecebidos();
            carregarDashboard();
        } else {
            showToast(result.error || 'Erro ao marcar como recebido', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    }
}

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

