// ==================== NOTINHAS ====================
let recebimentoContexto = {
    notinhaId: null,
    valorSugerido: 0,
    origem: null,
    clienteId: null // quando recebe de um cliente na edição, para ele sair da lista
};
async function salvarNotinha() {
    const empresa = document.getElementById('empresa').value.trim();
    const dataCobrancaPadrao = document.getElementById('data-cobranca').value;
    
    const clientesValidos = clientes.filter(c => c.nome.trim() !== '').map(c => {
        const numParcelas = parseInt(c.parcelas) || 1;
        
        // Para clientes à vista (1 parcela): usa SEMPRE a data do campo de cobrança
        // Para parcelados: usa as datas específicas de cada parcela
        let datasParcelas;
        if (numParcelas === 1) {
            // Cliente à vista: sempre usa a data padrão atual
            datasParcelas = [dataCobrancaPadrao];
        } else {
            // Parcelado: usa as datas específicas, garantindo que a primeira tenha valor
            datasParcelas = c.datasParcelas || [];
            if (!datasParcelas[0]) datasParcelas[0] = dataCobrancaPadrao;
        }
        
        return {
            nome: c.nome,
            valor: c.valor,
            telefone: c.telefone,
            parcelas: numParcelas,
            datasParcelas: datasParcelas.slice(0, numParcelas)
        };
    });

    if (!empresa) return showToast('Informe a empresa!', 'error');
    if (!dataCobrancaPadrao) return showToast('Informe a data de cobrança!', 'error');
    if (clientesValidos.length === 0) return showToast('Adicione um cliente!', 'error');
    
    // Verifica se clientes parcelados têm todas as datas preenchidas
    for (const c of clientesValidos) {
        if (c.parcelas > 1) {
            for (let i = 0; i < c.parcelas; i++) {
                if (!c.datasParcelas[i]) {
                    return showToast(`Informe a data da ${i + 1}ª parcela de ${c.nome}!`, 'error');
                }
            }
        }
    }

    try {
        const response = await fetch('api/notinhas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                empresa, 
                clientes: clientesValidos
            })
        });
        const result = await response.json();
        if (result.success) {
            const temParcelas = clientesValidos.some(c => c.parcelas > 1);
            const msg = temParcelas ? 'Notinha salva com parcelas! 🎉' : 'Notinha salva! 🎉';
            showToast(msg);
            document.getElementById('empresa').value = '';
            document.getElementById('data-cobranca').valueAsDate = new Date();
            clientes = [];
            adicionarCliente();
            carregarNotinhas();
        } else {
            showToast(result.error || 'Erro', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    }
}

async function carregarNotinhas() {
    try {
        const response = await fetch('api/notinhas.php');
        todasNotinhas = await response.json();
        atualizarContadores();
        aplicarFiltros();
    } catch (error) {
        console.error('Erro:', error);
    }
}

function abrirModalRecebimento(notinhaId, valorSugerido, descricao, clienteId) {
    recebimentoContexto.notinhaId = notinhaId;
    recebimentoContexto.valorSugerido = valorSugerido;
    recebimentoContexto.origem = descricao;
    recebimentoContexto.clienteId = clienteId || null;
    
    const overlay = document.getElementById('modal-recebimento');
    const input = document.getElementById('recebimento-valor');
    const texto = document.getElementById('recebimento-descricao');
    
    if (!overlay || !input || !texto) return;
    
    texto.textContent = descricao;
    input.value = formatarValor(valorSugerido).replace('R$ ', '');
    
    overlay.classList.add('show');
    input.focus();
}

function fecharModalRecebimento() {
    const overlay = document.getElementById('modal-recebimento');
    if (overlay) {
        overlay.classList.remove('show');
    }
    recebimentoContexto.notinhaId = null;
    recebimentoContexto.valorSugerido = 0;
    recebimentoContexto.origem = null;
    recebimentoContexto.clienteId = null;
}

async function confirmarRecebimento() {
    const overlay = document.getElementById('modal-recebimento');
    const input = document.getElementById('recebimento-valor');
    
    if (!overlay || !input || !recebimentoContexto.notinhaId) return;
    
    const valorStr = input.value.trim();
    if (!valorStr) {
        showToast('Informe o valor recebido!', 'error');
        return;
    }
    
    try {
        const body = { id: recebimentoContexto.notinhaId, acao: 'parcial', valor: valorStr };
        if (recebimentoContexto.clienteId) body.cliente_id = recebimentoContexto.clienteId;
        
        const response = await fetch('api/recebidos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        
        const result = await response.json();
        if (result.success) {
            showToast('✅ Recebimento registrado!');
            if (recebimentoContexto.clienteId && typeof notinhaIdEdicao !== 'undefined' && notinhaIdEdicao === recebimentoContexto.notinhaId) {
                clientesEdicao = clientesEdicao.filter(c => c.id != recebimentoContexto.clienteId);
                renderizarClientesEdicao();
            }
            fecharModalRecebimento();
            carregarNotinhas();
            carregarRecebidos();
            carregarDashboard();
        } else {
            showToast(result.error || 'Erro ao registrar recebimento', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    }
}

function calcularTotalGeral() {
    return todasNotinhas.reduce((total, n) => {
        const totalOriginal = parseFloat(n.total_original || 0) || n.clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0);
        const totalRecebido = parseFloat(n.total_recebido || 0);
        return total + Math.max(totalOriginal - totalRecebido, 0);
    }, 0);
}

// Abrir modal de recebimento a partir da lista de notinhas (não passa clienteId)
function marcarComoRecebido(id) {
    const notinha = todasNotinhas.find(n => n.id == id);
    if (!notinha) return;
    
    const totalOriginal = parseFloat(notinha.total_original || 0) || notinha.clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0);
    const totalRecebido = parseFloat(notinha.total_recebido || 0);
    const emAberto = Math.max(totalOriginal - totalRecebido, 0);
    const descricao = `Valor recebido da notinha da empresa "${notinha.empresa_nome}" (total R$ ${totalOriginal.toFixed(2).replace('.', ',')}, em aberto R$ ${emAberto.toFixed(2).replace('.', ',')}). Você pode ajustar o valor se recebeu apenas uma parte.`;
    
    abrirModalRecebimento(id, emAberto || totalOriginal, descricao);
}

function atualizarContadores() {
    const hoje = getHojeLocal();
    let contHoje = 0, contAtrasado = 0;
    let clientesHoje = 0, clientesAtrasados = 0;
    
    todasNotinhas.forEach(n => {
        const naoEnviados = n.clientes.filter(c => c.msg_enviada != 1).length;
        const todosClientes = n.clientes.length;
        
        if (n.data_cobranca === hoje && naoEnviados > 0) {
            contHoje++;
            clientesHoje += naoEnviados;
        } else if (n.data_cobranca < hoje && todosClientes > 0) {
            contAtrasado++;
            clientesAtrasados += todosClientes;
        }
    });

    document.getElementById('contador-hoje').textContent = clientesHoje;
    document.getElementById('contador-atrasado').textContent = clientesAtrasados;
    document.getElementById('total-geral').textContent = formatarValor(calcularTotalGeral());

    document.getElementById('btn-cobrar-hoje').style.display = clientesHoje > 0 ? 'block' : 'none';
    document.getElementById('btn-cobrar-atrasadas').style.display = clientesAtrasados > 0 ? 'block' : 'none';

    mostrarBannerNotificacao(clientesHoje, clientesAtrasados);
}

function filtrarPorStatus(status) {
    document.getElementById('filtro-status').value = status;
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelector('[data-tab="lista"]').classList.add('active');
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
    document.getElementById('tab-lista').style.display = 'block';
    aplicarFiltros();
}

function limparFiltros() {
    document.getElementById('filtro-busca').value = '';
    document.getElementById('filtro-status').value = '';
    document.getElementById('filtro-data').value = '';
    aplicarFiltros();
}

// Ordenação
let ordenacaoAtual = { campo: 'data', direcao: 'desc' };

function aplicarFiltros() {
    const busca = document.getElementById('filtro-busca').value.toLowerCase();
    const status = document.getElementById('filtro-status').value;
    const data = document.getElementById('filtro-data').value;
    const hoje = getHojeLocal();

    let filtradas = todasNotinhas.filter(n => {
        if (busca) {
            const matchEmpresa = n.empresa_nome.toLowerCase().includes(busca);
            const matchCliente = n.clientes.some(c => c.nome.toLowerCase().includes(busca));
            if (!matchEmpresa && !matchCliente) return false;
        }
        if (status === 'hoje' && n.data_cobranca !== hoje) return false;
        if (status === 'atrasado' && n.data_cobranca >= hoje) return false;
        if (status === 'futuro' && n.data_cobranca <= hoje) return false;
        if (data && n.data_cobranca !== data) return false;
        return true;
    });

    // Aplicar ordenação
    filtradas = ordenarNotinhas(filtradas);

    renderizarNotinhas(filtradas);

    const totalFiltrado = filtradas.reduce((total, n) => {
        const totalOriginal = parseFloat(n.total_original || 0) || n.clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0);
        const totalRecebido = parseFloat(n.total_recebido || 0);
        const emAberto = Math.max(totalOriginal - totalRecebido, 0);
        return total + emAberto;
    }, 0);
    document.getElementById('valor-filtrado').textContent = formatarValor(totalFiltrado);
}

function ordenarNotinhas(notinhas) {
    return [...notinhas].sort((a, b) => {
        let valorA, valorB;
        
        switch (ordenacaoAtual.campo) {
            case 'empresa':
                valorA = a.empresa_nome.toLowerCase();
                valorB = b.empresa_nome.toLowerCase();
                break;
            case 'data':
                valorA = a.data_cobranca;
                valorB = b.data_cobranca;
                break;
            case 'valor':
                valorA = (parseFloat(a.total_original || 0) || a.clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0)) - parseFloat(a.total_recebido || 0);
                valorB = (parseFloat(b.total_original || 0) || b.clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0)) - parseFloat(b.total_recebido || 0);
                break;
            default:
                valorA = a.data_cobranca;
                valorB = b.data_cobranca;
        }
        
        if (valorA < valorB) return ordenacaoAtual.direcao === 'asc' ? -1 : 1;
        if (valorA > valorB) return ordenacaoAtual.direcao === 'asc' ? 1 : -1;
        return 0;
    });
}

function ordenarPor(campo) {
    if (ordenacaoAtual.campo === campo) {
        // Inverte direção se já está ordenando por esse campo
        ordenacaoAtual.direcao = ordenacaoAtual.direcao === 'asc' ? 'desc' : 'asc';
    } else {
        ordenacaoAtual.campo = campo;
        ordenacaoAtual.direcao = campo === 'valor' ? 'desc' : 'asc';
    }
    
    // Atualiza indicadores visuais
    atualizarIndicadoresOrdenacao();
    aplicarFiltros();
}

function atualizarIndicadoresOrdenacao() {
    document.querySelectorAll('.coluna-ordenavel').forEach(el => {
        el.classList.remove('ordenando-asc', 'ordenando-desc');
    });
    
    const coluna = document.querySelector(`[data-ordenar="${ordenacaoAtual.campo}"]`);
    if (coluna) {
        coluna.classList.add(`ordenando-${ordenacaoAtual.direcao}`);
    }
}

function renderizarNotinhas(notinhas) {
    const container = document.getElementById('notinhas-lista');

    if (notinhas.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="icon">📋</div>
                <p>${t('nenhumaNotinha')}</p>
            </div>
        `;
        return;
    }

    container.innerHTML = notinhas.map(n => {
        const totalOriginal = parseFloat(n.total_original || 0) || n.clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0);
        const totalRecebido = parseFloat(n.total_recebido || 0);
        const emAberto = Math.max(totalOriginal - totalRecebido, 0);
        const status = getStatus(n.data_cobranca);
        const clientesNomes = n.clientes.map(c => c.nome.split(' ')[0]).join(', ');
        const notinhaJSON = JSON.stringify(n).replace(/'/g, "\\'").replace(/"/g, '&quot;');

        return `
            <div class="notinha-row">
                <div class="notinha-main">
                    <div class="notinha-empresa">
                        <button class="btn-expand" id="btn-expand-${n.id}" onclick="toggleDetalhes(${n.id})">▼</button>
                        ${n.empresa_nome}
                    </div>
                    <div class="notinha-clientes-preview">${clientesNomes}</div>
                    <div class="notinha-data">${formatarData(n.data_cobranca)}</div>
                    <div class="notinha-valor">${formatarValor(emAberto)}</div>
                    <div class="notinha-status">
                        <span class="status-badge ${status.classe}">${status.texto}</span>
                    </div>
                    <div class="notinha-acoes">
                        <button class="btn-acao btn-cobrar" onclick="cobrarTodos(${n.id})" title="${t('cobrar')}">💬</button>
                        <button class="btn-recebido" onclick="marcarComoRecebido(${n.id})" title="Marcar como Recebido">✅</button>
                        <button class="btn-acao btn-editar" onclick='abrirEdicao(${notinhaJSON})' title="${t('editar')}">✏️</button>
                        <button class="btn-acao btn-inadimplente" onclick="marcarInadimplente(${n.id})" title="${t('inadimplentes')}">💸</button>
                        <button class="btn-acao btn-excluir-linha" onclick="excluirNotinha(${n.id})" title="${t('excluir')}">🗑️</button>
                    </div>
                </div>
                <div class="notinha-detalhes" id="detalhes-${n.id}">
                    ${n.clientes.map(c => `
                        <div class="detalhe-cliente ${c.msg_enviada == 1 ? 'enviado' : ''}">
                            <div class="detalhe-info">
                                <span class="detalhe-nome">${c.nome}</span>
                                <span class="detalhe-valor">${formatarValor(c.valor)}</span>
                                <span class="detalhe-telefone">📱 ${c.telefone}</span>
                            </div>
                            <div class="detalhe-acoes">
                                ${c.msg_enviada == 1 ? 
                                    `<span class="status-badge status-enviado">✓ ${t('enviado')}</span>
                                    <button class="btn-reenviar" onclick="reenviarMensagem('${c.telefone}', '${c.nome.replace(/'/g, "\\'")}', '${c.valor}')">
                                        🔄 ${t('reenviar')}
                                    </button>` : 
                                    `<button class="btn-whatsapp" onclick="enviarWhatsApp('${c.telefone}', '${c.nome.replace(/'/g, "\\'")}', '${c.valor}', ${c.id})">
                                        <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        ${t('cobrar')}
                                    </button>`
                                }
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }).join('');
}

function toggleDetalhes(id) {
    const detalhes = document.getElementById(`detalhes-${id}`);
    const btn = document.getElementById(`btn-expand-${id}`);
    detalhes.classList.toggle('show');
    btn.classList.toggle('expanded');
}

async function excluirNotinha(id) {
    if (!confirm('Mover para lixeira?')) return;
    try {
        const response = await fetch(`api/notinhas.php?id=${id}`, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
            showToast('Movido para lixeira!');
            carregarNotinhas();
            carregarExcluidos();
        }
    } catch (error) {
        showToast('Erro', 'error');
    }
}

