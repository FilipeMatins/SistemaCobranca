// ==================== CLIENTES DA NOTINHA (FORMULÁRIO) ====================

// Navegação entre campos com Tab ou Enter
function navegarCampo(event, proximoCampoId) {
    // Tab navega para o próximo campo
    if (event.key === 'Tab' && !event.shiftKey) {
        event.preventDefault();
        event.stopPropagation();
        setTimeout(() => {
            const proximoCampo = document.getElementById(proximoCampoId);
            if (proximoCampo) {
                proximoCampo.focus();
            }
        }, 10);
    }
    // Enter também navega para o próximo campo
    if (event.key === 'Enter') {
        event.preventDefault();
        event.stopPropagation();
        setTimeout(() => {
            const proximoCampo = document.getElementById(proximoCampoId);
            if (proximoCampo) {
                proximoCampo.focus();
            }
        }, 10);
    }
}

// Atualizar valor sem re-renderizar (evita perder foco)
function atualizarValorCliente(id, valor) {
    const cliente = clientes.find(c => c.id === id);
    if (cliente) cliente.valor = valor;
}

async function buscarClientes(termo, clienteId) {
    try {
        const response = await fetch(`api/clientes.php?termo=${encodeURIComponent(termo)}`);
        const clientesCadastrados = await response.json();
        mostrarAutocompleteCliente(clientesCadastrados, clienteId);
    } catch (error) {
        console.error('Erro:', error);
    }
}

function mostrarAutocompleteCliente(clientesCadastrados, clienteId) {
    const lista = document.getElementById(`autocomplete-cliente-${clienteId}`);
    if (!lista) return;
    if (clientesCadastrados.length === 0) {
        lista.classList.remove('show');
        return;
    }
    lista.innerHTML = clientesCadastrados.map(c => `
        <div class="autocomplete-item" onclick="selecionarCliente(${clienteId}, '${c.nome.replace(/'/g, "\\'")}', '${(c.telefone || '').replace(/'/g, "\\'")}')">
            <span>${c.nome}</span>
            ${c.telefone ? `<span class="telefone">${c.telefone}</span>` : ''}
        </div>
    `).join('');
    lista.classList.add('show');
}

function selecionarCliente(clienteId, nome, telefone) {
    const cliente = clientes.find(c => c.id === clienteId);
    if (cliente) {
        cliente.nome = nome;
        cliente.telefone = telefone;
        renderizarClientes();
    }
    const lista = document.getElementById(`autocomplete-cliente-${clienteId}`);
    if (lista) lista.classList.remove('show');
}

function adicionarCliente() {
    const id = Date.now();
    const dataCobrancaGeral = document.getElementById('data-cobranca')?.value || '';
    clientes.push({ id, nome: '', valor: '', telefone: '', parcelas: 1, datasParcelas: [dataCobrancaGeral] });
    renderizarClientes();
    setTimeout(() => {
        const input = document.getElementById(`cliente-nome-${id}`);
        if (input) input.focus();
    }, 50);
}

function removerCliente(id) {
    clientes = clientes.filter(c => c.id !== id);
    renderizarClientes();
}

function atualizarCliente(id, campo, valor) {
    const cliente = clientes.find(c => c.id === id);
    if (cliente) cliente[campo] = valor;
}

function onNomeInput(id, valor) {
    atualizarCliente(id, 'nome', valor);
    if (valor.length >= 2) {
        buscarClientes(valor, id);
    } else {
        const lista = document.getElementById(`autocomplete-cliente-${id}`);
        if (lista) lista.classList.remove('show');
    }
}

function renderizarClientes() {
    const dataCobrancaGeral = document.getElementById('data-cobranca').value;
    
    document.getElementById('clientes-lista').innerHTML = clientes.map(c => {
        const numParcelas = parseInt(c.parcelas) || 1;
        const temParcelamento = numParcelas > 1;
        
        // Inicializa array de datas se não existir
        if (!c.datasParcelas) {
            c.datasParcelas = [dataCobrancaGeral];
        }
        
        // Garante que temos datas para todas as parcelas
        while (c.datasParcelas.length < numParcelas) {
            c.datasParcelas.push('');
        }
        
        // Gera HTML para campos de data de cada parcela
        let parcelasHtml = '';
        if (temParcelamento) {
            parcelasHtml = `
            <div class="parcelas-datas-container">
                <div class="parcelas-datas-header">
                    <span class="parcela-info-texto">${numParcelas}x de ${formatarValorParcela(c.valor, numParcelas)}</span>
                </div>
                <div class="parcelas-datas-grid">
                    ${Array.from({length: numParcelas}, (_, i) => `
                        <div class="parcela-data-item">
                            <label>${i + 1}ª Parcela:</label>
                            <input type="date" 
                                   id="data-parcela-${c.id}-${i}" 
                                   value="${c.datasParcelas[i] || (i === 0 ? dataCobrancaGeral : '')}"
                                   onchange="atualizarDataParcela(${c.id}, ${i}, this.value)">
                        </div>
                    `).join('')}
                </div>
            </div>`;
        }
        
        return `
        <div class="cliente-row-completo">
            <div class="cliente-row">
                <div class="nome-wrapper">
                    <input type="text" id="cliente-nome-${c.id}" placeholder="Nome completo"
                           value="${c.nome}" oninput="onNomeInput(${c.id}, this.value)" autocomplete="off"
                           onkeydown="navegarCampo(event, 'cliente-valor-${c.id}')">
                    <div class="autocomplete-list" id="autocomplete-cliente-${c.id}"></div>
                </div>
                <input type="text" class="valor" id="cliente-valor-${c.id}" placeholder="R$ 0,00"
                       value="${c.valor}" oninput="atualizarValorCliente(${c.id}, this.value)"
                       onkeydown="navegarCampo(event, 'cliente-telefone-${c.id}')">
                <input type="text" id="cliente-telefone-${c.id}" placeholder="(67) 99999-9999"
                       value="${c.telefone}" oninput="atualizarCliente(${c.id}, 'telefone', this.value)">
                <button class="btn-remove" onclick="removerCliente(${c.id})">×</button>
            </div>
            <div class="cliente-parcelas-row">
                <div class="parcela-grupo">
                    <label>Parcelas:</label>
                    <select id="parcelas-${c.id}" onchange="atualizarParcelas(${c.id}, this.value)">
                        <option value="1" ${numParcelas == 1 ? 'selected' : ''}>À vista</option>
                        <option value="2" ${numParcelas == 2 ? 'selected' : ''}>2x</option>
                        <option value="3" ${numParcelas == 3 ? 'selected' : ''}>3x</option>
                        <option value="4" ${numParcelas == 4 ? 'selected' : ''}>4x</option>
                        <option value="5" ${numParcelas == 5 ? 'selected' : ''}>5x</option>
                        <option value="6" ${numParcelas == 6 ? 'selected' : ''}>6x</option>
                        <option value="10" ${numParcelas == 10 ? 'selected' : ''}>10x</option>
                        <option value="12" ${numParcelas == 12 ? 'selected' : ''}>12x</option>
                    </select>
                </div>
            </div>
            ${parcelasHtml}
        </div>
    `}).join('');
}

function atualizarParcelas(id, valor) {
    const cliente = clientes.find(c => c.id === id);
    if (cliente) {
        cliente.parcelas = valor;
        // Reseta datas das parcelas ao mudar quantidade
        const dataCobrancaGeral = document.getElementById('data-cobranca').value;
        cliente.datasParcelas = [dataCobrancaGeral];
    }
    renderizarClientes();
}

function atualizarDataParcela(clienteId, parcelaIndex, data) {
    const cliente = clientes.find(c => c.id === clienteId);
    if (cliente) {
        if (!cliente.datasParcelas) {
            cliente.datasParcelas = [];
        }
        cliente.datasParcelas[parcelaIndex] = data;
    }
}

function formatarValorParcela(valorStr, parcelas) {
    const valor = parseFloat(String(valorStr).replace(',', '.').replace(/[^\d.]/g, '')) || 0;
    const valorParcela = valor / parcelas;
    return formatarValor(valorParcela);
}

// Atualiza data de todos os clientes que ainda não têm data definida
function atualizarDatasClientes() {
    const dataPadrao = document.getElementById('data-cobranca').value;
    clientes.forEach(c => {
        if (!c.dataVencimento) {
            c.dataVencimento = dataPadrao;
        }
    });
    renderizarClientes();
}

