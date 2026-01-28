// ==================== GESTÃO DE CLIENTES ====================
async function carregarTodosClientes() {
    try {
        const response = await fetch('api/clientes.php');
        todosClientes = await response.json();
        clientesFiltrados = [...todosClientes];
        paginaAtualClientes = 1;
        renderizarListaClientes();
        atualizarBadgeClientes();
    } catch (error) {
        console.error('Erro:', error);
    }
}

function atualizarBadgeClientes() {
    const badge = document.getElementById('badge-clientes');
    if (todosClientes.length > 0) {
        badge.textContent = todosClientes.length;
        badge.style.display = 'inline-block';
    } else {
        badge.style.display = 'none';
    }
}

function renderizarListaClientes() {
    const container = document.getElementById('clientes-cadastrados-lista');
    if (!container) return;
    
    if (clientesFiltrados.length === 0) {
        container.innerHTML = `
            <div class="clientes-vazio">
                <div class="clientes-vazio-icon">👥</div>
                <p>Nenhum cliente encontrado</p>
                <p style="font-size: 0.8rem; margin-top: 5px;">Clique em "Novo Cliente" para adicionar</p>
            </div>
        `;
        renderizarPaginacao();
        return;
    }

    const inicio = (paginaAtualClientes - 1) * clientesPorPagina;
    const fim = inicio + clientesPorPagina;
    const clientesPagina = clientesFiltrados.slice(inicio, fim);

    container.innerHTML = clientesPagina.map(c => `
        <div class="cliente-item">
            <span class="cliente-item-nome">${c.nome}</span>
            <span class="cliente-item-telefone">
                ${c.telefone ? formatarTelefone(c.telefone) : '<span class="cliente-sem-telefone">Sem telefone</span>'}
            </span>
            <div class="cliente-item-acoes">
                <button class="btn-historico" onclick="abrirHistoricoCliente('${c.nome.replace(/'/g, "\\'")}', '${c.telefone || ''}')" title="Ver histórico">📊</button>
                <button class="btn-editar" onclick="editarCliente(${c.id})" title="Editar">✏️</button>
                <button class="btn-excluir" onclick="excluirCliente(${c.id})" title="Excluir">🗑️</button>
            </div>
        </div>
    `).join('');

    renderizarPaginacao();
}

function renderizarPaginacao() {
    const container = document.getElementById('paginacao-clientes');
    if (!container) return;

    const totalPaginas = Math.ceil(clientesFiltrados.length / clientesPorPagina);
    
    if (totalPaginas <= 1) {
        container.innerHTML = clientesFiltrados.length > 0 
            ? `<span class="paginacao-info">${clientesFiltrados.length} cliente(s)</span>`
            : '';
        return;
    }

    const inicio = (paginaAtualClientes - 1) * clientesPorPagina + 1;
    const fim = Math.min(paginaAtualClientes * clientesPorPagina, clientesFiltrados.length);

    let html = `
        <span class="paginacao-info">
            Mostrando ${inicio}-${fim} de ${clientesFiltrados.length}
        </span>
        <div class="paginacao-botoes">
            <button class="paginacao-btn" onclick="mudarPaginaClientes(1)" ${paginaAtualClientes === 1 ? 'disabled' : ''}>
                ««
            </button>
            <button class="paginacao-btn" onclick="mudarPaginaClientes(${paginaAtualClientes - 1})" ${paginaAtualClientes === 1 ? 'disabled' : ''}>
                ‹
            </button>
    `;

    let paginaInicio = Math.max(1, paginaAtualClientes - 2);
    let paginaFim = Math.min(totalPaginas, paginaInicio + 4);
    
    if (paginaFim - paginaInicio < 4) {
        paginaInicio = Math.max(1, paginaFim - 4);
    }

    for (let i = paginaInicio; i <= paginaFim; i++) {
        html += `
            <button class="paginacao-btn ${i === paginaAtualClientes ? 'active' : ''}" 
                    onclick="mudarPaginaClientes(${i})">
                ${i}
            </button>
        `;
    }

    html += `
            <button class="paginacao-btn" onclick="mudarPaginaClientes(${paginaAtualClientes + 1})" ${paginaAtualClientes === totalPaginas ? 'disabled' : ''}>
                ›
            </button>
            <button class="paginacao-btn" onclick="mudarPaginaClientes(${totalPaginas})" ${paginaAtualClientes === totalPaginas ? 'disabled' : ''}>
                »»
            </button>
        </div>
    `;

    container.innerHTML = html;
}

function mudarPaginaClientes(pagina) {
    const totalPaginas = Math.ceil(clientesFiltrados.length / clientesPorPagina);
    if (pagina < 1 || pagina > totalPaginas) return;
    
    paginaAtualClientes = pagina;
    renderizarListaClientes();
    
    document.getElementById('clientes-cadastrados-container')?.scrollIntoView({ behavior: 'smooth' });
}

function filtrarClientes() {
    const termo = document.getElementById('filtro-clientes').value.toLowerCase();
    clientesFiltrados = todosClientes.filter(c => 
        c.nome.toLowerCase().includes(termo) || 
        (c.telefone && c.telefone.includes(termo))
    );
    paginaAtualClientes = 1;
    renderizarListaClientes();
}

function abrirModalCliente(id = null) {
    const modal = document.getElementById('modal-cliente');
    const titulo = document.getElementById('titulo-modal-cliente');
    
    if (id) {
        const cliente = todosClientes.find(c => c.id == id);
        if (cliente) {
            titulo.textContent = '✏️ Editar Cliente';
            document.getElementById('cliente-id').value = cliente.id;
            document.getElementById('cliente-nome').value = cliente.nome;
            document.getElementById('cliente-telefone').value = cliente.telefone || '';
        }
    } else {
        titulo.textContent = '➕ Novo Cliente';
        document.getElementById('cliente-id').value = '';
        document.getElementById('cliente-nome').value = '';
        document.getElementById('cliente-telefone').value = '';
    }
    
    modal.classList.add('show');
}

function fecharModalCliente() {
    document.getElementById('modal-cliente').classList.remove('show');
}

function editarCliente(id) {
    abrirModalCliente(id);
}

async function salvarCliente() {
    const id = document.getElementById('cliente-id').value;
    const nome = document.getElementById('cliente-nome').value.trim();
    const telefone = document.getElementById('cliente-telefone').value.trim();
    
    if (!nome) {
        showToast('Preencha o nome!', 'error');
        return;
    }
    
    try {
        const method = id ? 'PUT' : 'POST';
        const body = id 
            ? { id, nome, telefone }
            : { nome, telefone };
            
        const response = await fetch('api/clientes.php', {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast(id ? 'Cliente atualizado!' : 'Cliente cadastrado!');
            fecharModalCliente();
            carregarTodosClientes();
        } else {
            showToast(result.error || 'Erro ao salvar', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    }
}

async function excluirCliente(id) {
    if (!confirm('Excluir este cliente?')) return;
    
    try {
        const response = await fetch(`api/clientes.php?id=${id}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        
        if (result.success) {
            showToast('Cliente excluído!');
            carregarTodosClientes();
        } else {
            showToast(result.error || 'Erro', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    }
}

