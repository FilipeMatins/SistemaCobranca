// ==================== BUSCA GLOBAL ====================

let resultadosBuscaGlobal = [];

function abrirBuscaGlobal() {
    const modal = document.getElementById('modal-busca-global');
    if (modal) {
        modal.classList.add('show');
        document.getElementById('busca-global-input').focus();
        document.getElementById('busca-global-resultados').innerHTML = `
            <div class="busca-instrucao">
                Digite para buscar em notinhas, clientes e empresas...
            </div>
        `;
    }
}

function fecharBuscaGlobal() {
    const modal = document.getElementById('modal-busca-global');
    if (modal) {
        modal.classList.remove('show');
        document.getElementById('busca-global-input').value = '';
    }
}

async function executarBuscaGlobal() {
    const termo = document.getElementById('busca-global-input').value.trim();
    const container = document.getElementById('busca-global-resultados');
    
    if (termo.length < 2) {
        container.innerHTML = `
            <div class="busca-instrucao">
                Digite pelo menos 2 caracteres...
            </div>
        `;
        return;
    }
    
    container.innerHTML = '<div class="busca-loading">🔍 Buscando...</div>';
    
    try {
        const response = await fetch(`api/busca.php?termo=${encodeURIComponent(termo)}`);
        const resultados = await response.json();
        
        renderizarResultadosBusca(resultados, termo);
        
    } catch (error) {
        console.error('Erro na busca:', error);
        container.innerHTML = '<div class="busca-erro">Erro ao buscar</div>';
    }
}

function renderizarResultadosBusca(resultados, termo) {
    const container = document.getElementById('busca-global-resultados');
    
    const totalResultados = 
        (resultados.notinhas?.length || 0) + 
        (resultados.clientes?.length || 0) + 
        (resultados.empresas?.length || 0);
    
    if (totalResultados === 0) {
        container.innerHTML = `
            <div class="busca-vazio">
                <span class="busca-vazio-icon">🔍</span>
                <p>Nenhum resultado para "${termo}"</p>
            </div>
        `;
        return;
    }
    
    let html = `<div class="busca-total">${totalResultados} resultado(s) encontrado(s)</div>`;
    
    // Notinhas
    if (resultados.notinhas?.length > 0) {
        html += `
            <div class="busca-secao">
                <h4>📋 Notinhas (${resultados.notinhas.length})</h4>
                ${resultados.notinhas.map(n => `
                    <div class="busca-item" onclick="irParaNotinha(${n.id})">
                        <div class="busca-item-principal">
                            <span class="busca-item-titulo">${destacarTermo(n.empresa_nome, termo)}</span>
                            <span class="busca-item-valor">${formatarValor(n.total)}</span>
                        </div>
                        <div class="busca-item-detalhes">
                            <span>${destacarTermo(n.clientes_nomes, termo)}</span>
                            <span class="busca-item-data">${formatarData(n.data_cobranca)}</span>
                            <span class="status-badge ${n.status_classe}">${n.status_texto}</span>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    // Clientes
    if (resultados.clientes?.length > 0) {
        html += `
            <div class="busca-secao">
                <h4>👥 Clientes (${resultados.clientes.length})</h4>
                ${resultados.clientes.map(c => `
                    <div class="busca-item" onclick="irParaCliente('${c.nome.replace(/'/g, "\\'")}')">
                        <div class="busca-item-principal">
                            <span class="busca-item-titulo">${destacarTermo(c.nome, termo)}</span>
                            <span class="busca-item-telefone">📱 ${c.telefone || '-'}</span>
                        </div>
                        ${c.total_compras ? `
                        <div class="busca-item-detalhes">
                            <span>${c.total_compras} compra(s)</span>
                            <span>Total: ${formatarValor(c.total_gasto)}</span>
                        </div>
                        ` : ''}
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    // Empresas
    if (resultados.empresas?.length > 0) {
        html += `
            <div class="busca-secao">
                <h4>🏢 Empresas (${resultados.empresas.length})</h4>
                ${resultados.empresas.map(e => `
                    <div class="busca-item" onclick="filtrarPorEmpresa('${e.nome.replace(/'/g, "\\'")}')">
                        <div class="busca-item-principal">
                            <span class="busca-item-titulo">${destacarTermo(e.nome, termo)}</span>
                        </div>
                        <div class="busca-item-detalhes">
                            <span>${e.total_notinhas} notinha(s)</span>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    container.innerHTML = html;
}

function destacarTermo(texto, termo) {
    if (!texto || !termo) return texto || '';
    const regex = new RegExp(`(${termo})`, 'gi');
    return texto.replace(regex, '<mark>$1</mark>');
}

function irParaNotinha(id) {
    fecharBuscaGlobal();
    document.querySelector('[data-tab="lista"]')?.click();
    
    // Tenta expandir a notinha
    setTimeout(() => {
        const detalhes = document.getElementById(`detalhes-${id}`);
        if (detalhes && !detalhes.classList.contains('show')) {
            toggleDetalhes(id);
        }
        // Scroll para a notinha
        const row = detalhes?.closest('.notinha-row');
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.style.animation = 'highlight 1s ease';
        }
    }, 300);
}

function irParaCliente(nome) {
    fecharBuscaGlobal();
    document.querySelector('[data-tab="clientes"]')?.click();
    
    setTimeout(() => {
        document.getElementById('filtro-clientes').value = nome;
        filtrarClientes();
    }, 300);
}

function filtrarPorEmpresa(nome) {
    fecharBuscaGlobal();
    document.querySelector('[data-tab="lista"]')?.click();
    
    setTimeout(() => {
        document.getElementById('filtro-busca').value = nome;
        aplicarFiltros();
    }, 300);
}

// Debounce para busca enquanto digita
let timeoutBusca;
function onBuscaGlobalInput() {
    clearTimeout(timeoutBusca);
    timeoutBusca = setTimeout(executarBuscaGlobal, 300);
}

