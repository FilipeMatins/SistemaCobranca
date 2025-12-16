// ==================== UTILS ====================
function getHojeLocal() {
    const hoje = new Date();
    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = 'toast show ' + type;
    setTimeout(() => toast.classList.remove('show'), 3000);
}

function formatarData(data) {
    return new Date(data + 'T00:00:00').toLocaleDateString('pt-BR');
}

function formatarValor(valor) {
    return 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',');
}

// ==================== ESTADO ====================
let clientes = [];
let configuracoes = {};
let todasNotinhas = [];
let notinhasExcluidas = [];
let clientesEdicao = [];

// ==================== INIT ====================
document.addEventListener('DOMContentLoaded', () => {
    carregarConfiguracoes();
    carregarNotinhas();
    carregarExcluidos();
    carregarTodosClientes(); // Carrega para mostrar badge
    adicionarCliente();
    document.getElementById('data-cobranca').valueAsDate = new Date();

    // Tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            document.getElementById('tab-' + tab.dataset.tab).style.display = 'block';
            
            // Carregar clientes ao abrir a aba
            if (tab.dataset.tab === 'clientes') {
                carregarTodosClientes();
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
    });

    // Fechar modal com ESC e atalhos Enter
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            fecharConfiguracoes();
            fecharEdicao();
            fecharModalCliente();
            fecharModalPromocao();
            fecharModalEnvioPromocao();
        }
        
        // Enter para enviar promoção
        if (e.key === 'Enter' && document.getElementById('modal-envio-promocao').classList.contains('show')) {
            const btnEnviar = document.getElementById('btn-enviar-promocao');
            const btnProximo = document.getElementById('btn-proximo-promocao');
            
            if (btnEnviar.style.display !== 'none') {
                enviarPromocaoAtual();
            } else if (btnProximo.style.display !== 'none') {
                proximaPromocao();
            }
        }
    });
});

// ==================== EMPRESAS ====================
async function buscarEmpresas() {
    const termo = document.getElementById('empresa').value;
    try {
        const response = await fetch(`api/empresas.php?termo=${encodeURIComponent(termo)}`);
        const empresas = await response.json();
        mostrarAutocompleteEmpresa(empresas);
    } catch (error) {
        console.error('Erro:', error);
    }
}

function mostrarAutocompleteEmpresa(empresas) {
    const lista = document.getElementById('autocomplete-empresa');
    if (empresas.length === 0) {
        lista.classList.remove('show');
        return;
    }
    lista.innerHTML = empresas.map(e => `
        <div class="autocomplete-item" onclick="selecionarEmpresa('${e.nome.replace(/'/g, "\\'")}')">
            ${e.nome}
        </div>
    `).join('');
    lista.classList.add('show');
}

function selecionarEmpresa(nome) {
    document.getElementById('empresa').value = nome;
    document.getElementById('autocomplete-empresa').classList.remove('show');
}

// ==================== CLIENTES CADASTRADOS ====================
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

// ==================== CLIENTES DA NOTINHA ====================
function adicionarCliente() {
    const id = Date.now();
    clientes.push({ id, nome: '', valor: '', telefone: '' });
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
    document.getElementById('clientes-lista').innerHTML = clientes.map(c => `
        <div class="cliente-row">
            <div class="nome-wrapper">
                <input type="text" id="cliente-nome-${c.id}" placeholder="Nome completo"
                       value="${c.nome}" oninput="onNomeInput(${c.id}, this.value)" autocomplete="off">
                <div class="autocomplete-list" id="autocomplete-cliente-${c.id}"></div>
            </div>
            <input type="text" class="valor" placeholder="R$ 0,00"
                   value="${c.valor}" oninput="atualizarCliente(${c.id}, 'valor', this.value)">
            <input type="text" placeholder="(67) 99999-9999"
                   value="${c.telefone}" oninput="atualizarCliente(${c.id}, 'telefone', this.value)">
            <button class="btn-remove" onclick="removerCliente(${c.id})">×</button>
        </div>
    `).join('');
}

// ==================== NOTINHAS ====================
async function salvarNotinha() {
    const empresa = document.getElementById('empresa').value.trim();
    const dataCobranca = document.getElementById('data-cobranca').value;
    const clientesValidos = clientes.filter(c => c.nome.trim() !== '');

    if (!empresa) return showToast('Informe a empresa!', 'error');
    if (!dataCobranca) return showToast('Informe a data!', 'error');
    if (clientesValidos.length === 0) return showToast('Adicione um cliente!', 'error');

    try {
        const response = await fetch('api/notinhas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ empresa, data_cobranca: dataCobranca, clientes: clientesValidos })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Notinha salva! 🎉');
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

function calcularTotalGeral() {
    return todasNotinhas.reduce((total, n) => {
        return total + n.clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0);
    }, 0);
}

function atualizarContadores() {
    const hoje = getHojeLocal();
    let contHoje = 0, contAtrasado = 0;
    let clientesHoje = 0, clientesAtrasados = 0;
    
    todasNotinhas.forEach(n => {
        // Conta apenas clientes não enviados
        const naoEnviados = n.clientes.filter(c => c.msg_enviada != 1).length;
        
        if (n.data_cobranca === hoje && naoEnviados > 0) {
            contHoje++;
            clientesHoje += naoEnviados;
        } else if (n.data_cobranca < hoje && naoEnviados > 0) {
            contAtrasado++;
            clientesAtrasados += naoEnviados;
        }
    });

    document.getElementById('contador-hoje').textContent = contHoje;
    document.getElementById('contador-atrasado').textContent = contAtrasado;
    document.getElementById('total-geral').textContent = formatarValor(calcularTotalGeral());

    // Atualiza botões
    document.getElementById('btn-cobrar-hoje').style.display = contHoje > 0 ? 'block' : 'none';
    document.getElementById('btn-cobrar-atrasadas').style.display = contAtrasado > 0 ? 'block' : 'none';

    // Mostra banner se tiver cobranças hoje
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

    renderizarNotinhas(filtradas);

    // Atualiza total filtrado
    const totalFiltrado = filtradas.reduce((total, n) => {
        return total + n.clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0);
    }, 0);
    document.getElementById('valor-filtrado').textContent = formatarValor(totalFiltrado);
}

function getStatus(data) {
    const hoje = getHojeLocal();
    if (data === hoje) return { classe: 'status-hoje', texto: 'Hoje' };
    if (data < hoje) return { classe: 'status-atrasado', texto: 'Atrasado' };
    return { classe: 'status-futuro', texto: 'Agendado' };
}

function renderizarNotinhas(notinhas) {
    const container = document.getElementById('notinhas-lista');

    if (notinhas.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="icon">📋</div>
                <p>Nenhuma notinha encontrada</p>
            </div>
        `;
        return;
    }

    container.innerHTML = notinhas.map(n => {
        const total = n.clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0);
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
                    <div class="notinha-valor">${formatarValor(total)}</div>
                    <div class="notinha-status">
                        <span class="status-badge ${status.classe}">${status.texto}</span>
                    </div>
                    <div class="notinha-acoes">
                        <button class="btn-acao btn-cobrar" onclick="cobrarTodos(${n.id})" title="Cobrar todos">💬</button>
                        <button class="btn-acao btn-editar" onclick='abrirEdicao(${notinhaJSON})' title="Editar">✏️</button>
                        <button class="btn-acao btn-excluir-linha" onclick="excluirNotinha(${n.id})" title="Excluir">🗑️</button>
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
                                    `<span class="status-badge status-enviado">✓ Enviado</span>
                                    <button class="btn-reenviar" onclick="reenviarMensagem('${c.telefone}', '${c.nome.replace(/'/g, "\\'")}', '${c.valor}')">
                                        🔄 Reenviar
                                    </button>` : 
                                    `<button class="btn-whatsapp" onclick="enviarWhatsApp('${c.telefone}', '${c.nome.replace(/'/g, "\\'")}', '${c.valor}', ${c.id})">
                                        <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        Cobrar
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

function cobrarTodos(notinhaId) {
    const notinha = todasNotinhas.find(n => n.id == notinhaId);
    if (!notinha) return;
    
    const hoje = getHojeLocal();
    const isAtrasada = notinha.data_cobranca < hoje;
    
    if (isAtrasada) {
        // Notinha atrasada: pega TODOS para reenvio
        filaCobranca = notinha.clientes.map(c => ({ 
            ...c, 
            empresa: notinha.empresa_nome, 
            reenvio: c.msg_enviada == 1 
        }));
        modoReenvio = true;
    } else {
        // Notinha do dia: só os não enviados
        const clientesNaoEnviados = notinha.clientes.filter(c => c.msg_enviada != 1);
        
        if (clientesNaoEnviados.length === 0) {
            showToast('Todos os clientes já foram cobrados!', 'error');
            return;
        }
        
        filaCobranca = clientesNaoEnviados.map(c => ({ ...c, empresa: notinha.empresa_nome, reenvio: false }));
        modoReenvio = false;
    }

    if (filaCobranca.length === 0) {
        showToast('Nenhum cliente para cobrar!', 'error');
        return;
    }

    indiceAtual = 0;
    mostrarModalCobranca();
}

// ==================== COBRAR EM LOTE ====================
function mostrarBannerNotificacao(clientesHoje, clientesAtrasados) {
    const banner = document.getElementById('banner-notificacao');
    const detalhes = document.getElementById('banner-detalhes');
    
    if (clientesHoje > 0 || clientesAtrasados > 0) {
        let texto = [];
        if (clientesHoje > 0) texto.push(`${clientesHoje} cliente(s) para hoje`);
        if (clientesAtrasados > 0) texto.push(`${clientesAtrasados} atrasado(s)`);
        
        detalhes.textContent = texto.join(' • ');
        banner.style.display = 'block';
        
        // Pede permissão e envia notificação do navegador
        enviarNotificacaoNavegador(clientesHoje, clientesAtrasados);
    } else {
        banner.style.display = 'none';
    }
}

function fecharBanner() {
    document.getElementById('banner-notificacao').style.display = 'none';
}

function enviarNotificacaoNavegador(hoje, atrasados) {
    if (!("Notification" in window)) return;
    
    if (Notification.permission === "granted") {
        criarNotificacao(hoje, atrasados);
    } else if (Notification.permission !== "denied") {
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                criarNotificacao(hoje, atrasados);
            }
        });
    }
}

function criarNotificacao(hoje, atrasados) {
    const total = hoje + atrasados;
    if (total === 0) return;
    
    // Só notifica uma vez por sessão
    if (sessionStorage.getItem('notificado')) return;
    sessionStorage.setItem('notificado', 'true');
    
    const notification = new Notification("📝 Bloco de Cobranças", {
        body: `Você tem ${total} cobrança(s) pendente(s)!`,
        icon: "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📝</text></svg>",
        requireInteraction: true
    });
    
    notification.onclick = function() {
        window.focus();
        notification.close();
    };
}

// Fila de cobranças
let filaCobranca = [];
let indiceAtual = 0;
let modoReenvio = false;

async function cobrarTodosHoje() {
    const hoje = getHojeLocal();
    const notinhasHoje = todasNotinhas.filter(n => n.data_cobranca === hoje);
    
    filaCobranca = [];
    notinhasHoje.forEach(n => {
        n.clientes.filter(c => c.msg_enviada != 1).forEach(c => {
            filaCobranca.push({ ...c, empresa: n.empresa_nome, reenvio: false });
        });
    });

    if (filaCobranca.length === 0) {
        showToast('Nenhuma cobrança pendente para hoje!', 'error');
        return;
    }

    indiceAtual = 0;
    modoReenvio = false; // Modo normal, marca como enviado
    mostrarModalCobranca();
}

async function cobrarTodasAtrasadas() {
    const hoje = getHojeLocal();
    const notinhasAtrasadas = todasNotinhas.filter(n => n.data_cobranca < hoje);
    
    filaCobranca = [];
    // Pega TODOS os clientes (para permitir reenvio)
    notinhasAtrasadas.forEach(n => {
        n.clientes.forEach(c => {
            filaCobranca.push({ ...c, empresa: n.empresa_nome, reenvio: c.msg_enviada == 1 });
        });
    });

    if (filaCobranca.length === 0) {
        showToast('Nenhuma cobrança atrasada!', 'error');
        return;
    }

    indiceAtual = 0;
    modoReenvio = true; // Ativa modo reenvio (não marca como enviado de novo)
    mostrarModalCobranca();
}

function mostrarModalCobranca() {
    const modal = document.getElementById('modal-cobranca');
    modal.classList.add('show');
    
    // Reseta os botões
    document.getElementById('btn-enviar').style.display = 'block';
    document.getElementById('btn-proximo').style.display = 'none';
    
    // Ativa atalho do Enter
    document.addEventListener('keydown', atalhoEnterCobranca);
    
    atualizarModalCobranca();
}

function atalhoEnterCobranca(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        
        const btnEnviar = document.getElementById('btn-enviar');
        const btnProximo = document.getElementById('btn-proximo');
        
        // Se o botão "Enviar" está visível, clica nele
        if (btnEnviar.style.display !== 'none') {
            enviarCobrancaAtual();
        } 
        // Se o botão "Próximo" está visível, clica nele
        else if (btnProximo.style.display !== 'none') {
            proximoCliente();
        }
    }
}

function fecharModalCobranca() {
    document.getElementById('modal-cobranca').classList.remove('show');
    
    // Remove o atalho do Enter
    document.removeEventListener('keydown', atalhoEnterCobranca);
    
    carregarNotinhas(); // Atualiza a lista
}

function atualizarModalCobranca() {
    const cliente = filaCobranca[indiceAtual];
    const total = filaCobranca.length;
    
    document.getElementById('cobranca-progresso').textContent = `${indiceAtual + 1} de ${total}`;
    document.getElementById('cobranca-nome').textContent = cliente.nome;
    document.getElementById('cobranca-valor').textContent = formatarValor(cliente.valor);
    document.getElementById('cobranca-telefone').textContent = cliente.telefone;
    document.getElementById('cobranca-empresa').textContent = cliente.empresa;
    
    // Mostra badge de reenvio
    const badgeReenvio = document.getElementById('badge-reenvio');
    if (cliente.reenvio) {
        badgeReenvio.style.display = 'inline-block';
    } else {
        badgeReenvio.style.display = 'none';
    }
    
    // Título do modal
    const tituloModal = document.getElementById('titulo-modal-cobranca');
    if (modoReenvio) {
        tituloModal.textContent = '🔄 Reenviar Cobranças';
    } else {
        tituloModal.textContent = '💬 Enviar Cobranças';
    }
    
    // Barra de progresso
    const porcentagem = ((indiceAtual + 1) / total) * 100;
    document.getElementById('cobranca-barra').style.width = porcentagem + '%';
}

function enviarCobrancaAtual() {
    const cliente = filaCobranca[indiceAtual];
    
    // Abre o WhatsApp (sem marcar como enviado ainda)
    let tel = cliente.telefone.replace(/\D/g, '');
    if (tel.length === 11 && !tel.startsWith('55')) tel = '55' + tel;
    
    const primeiroNome = cliente.nome.split(' ')[0];
    const valorFormatado = formatarValor(cliente.valor);
    const nomeVendedor = configuracoes.nome_vendedor || 'Filipe que vende requeijão e doces';
    const chavePix = configuracoes.chave_pix || '67991233362';
    
    let mensagem = configuracoes.mensagem_cobranca || 'Bom dia {nome} tudo bem? {vendedor}, passando para deixar meu pix e o valor dos produtos 🙏 {valor} Chave pix {pix}';
    mensagem = mensagem
        .replace(/{nome}/g, primeiroNome)
        .replace(/{vendedor}/g, nomeVendedor)
        .replace(/{valor}/g, valorFormatado)
        .replace(/{pix}/g, chavePix);
    
    // Abre direto no App do WhatsApp (sem aba intermediária)
    window.location.href = `whatsapp://send?phone=${tel}&text=${encodeURIComponent(mensagem)}`;
    
    // Mostra o botão "Enviado, Próximo!"
    document.getElementById('btn-enviar').style.display = 'none';
    document.getElementById('btn-proximo').style.display = 'block';
}

function proximoCliente() {
    const cliente = filaCobranca[indiceAtual];
    
    // Marca o atual como enviado (apenas se não for reenvio)
    if (!modoReenvio && !cliente.reenvio) {
        marcarComoEnviado([cliente.id]);
    }
    
    // Vai para o próximo
    indiceAtual++;
    
    if (indiceAtual < filaCobranca.length) {
        document.getElementById('btn-enviar').style.display = 'block';
        document.getElementById('btn-proximo').style.display = 'none';
        atualizarModalCobranca();
    } else {
        fecharModalCobranca();
        if (modoReenvio) {
            showToast('🎉 Reenvio concluído!');
        } else {
            showToast('🎉 Todas as cobranças enviadas!');
        }
    }
}

function pularCobrancaAtual() {
    indiceAtual++;
    
    if (indiceAtual < filaCobranca.length) {
        document.getElementById('btn-enviar').style.display = 'block';
        document.getElementById('btn-proximo').style.display = 'none';
        atualizarModalCobranca();
    } else {
        fecharModalCobranca();
        showToast('Finalizado!');
    }
}

async function marcarComoEnviado(clienteIds) {
    try {
        await fetch('api/notinhas.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'marcar_enviado',
                cliente_ids: clienteIds 
            })
        });
        
        // Recarrega para atualizar a lista
        setTimeout(() => carregarNotinhas(), 1000);
    } catch (error) {
        console.error('Erro ao marcar como enviado:', error);
    }
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

function reenviarMensagem(telefone, nomeCompleto, valor) {
    // Reenvia sem marcar novamente (já está marcado)
    let tel = telefone.replace(/\D/g, '');
    if (tel.length === 11 && !tel.startsWith('55')) tel = '55' + tel;
    
    const primeiroNome = nomeCompleto.split(' ')[0];
    const valorFormatado = formatarValor(valor);
    const nomeVendedor = configuracoes.nome_vendedor || 'Filipe que vende requeijão e doces';
    const chavePix = configuracoes.chave_pix || '67991233362';
    
    let mensagem = configuracoes.mensagem_cobranca || 'Bom dia {nome} tudo bem? {vendedor}, passando para deixar meu pix e o valor dos produtos 🙏 {valor} Chave pix {pix}';
    
    mensagem = mensagem
        .replace(/{nome}/g, primeiroNome)
        .replace(/{vendedor}/g, nomeVendedor)
        .replace(/{valor}/g, valorFormatado)
        .replace(/{pix}/g, chavePix);
    
    window.location.href = `whatsapp://send?phone=${tel}&text=${encodeURIComponent(mensagem)}`;
    showToast('Reenviando mensagem...');
}

function enviarWhatsApp(telefone, nomeCompleto, valor, clienteId = null) {
    let tel = telefone.replace(/\D/g, '');
    if (tel.length === 11 && !tel.startsWith('55')) tel = '55' + tel;
    
    const primeiroNome = nomeCompleto.split(' ')[0];
    const valorFormatado = formatarValor(valor);
    const nomeVendedor = configuracoes.nome_vendedor || 'Filipe que vende requeijão e doces';
    const chavePix = configuracoes.chave_pix || '67991233362';
    
    let mensagem = configuracoes.mensagem_cobranca || 'Bom dia {nome} tudo bem? {vendedor}, passando para deixar meu pix e o valor dos produtos 🙏 {valor} Chave pix {pix}';
    
    mensagem = mensagem
        .replace(/{nome}/g, primeiroNome)
        .replace(/{vendedor}/g, nomeVendedor)
        .replace(/{valor}/g, valorFormatado)
        .replace(/{pix}/g, chavePix);
    
    // Abre direto no App do WhatsApp (sem aba intermediária)
    window.location.href = `whatsapp://send?phone=${tel}&text=${encodeURIComponent(mensagem)}`;
    
    // Marca como enviado se tiver ID
    if (clienteId) {
        marcarComoEnviado([clienteId]);
    }
}

// ==================== EXCLUÍDOS (LIXEIRA) ====================
async function carregarExcluidos() {
    try {
        const response = await fetch('api/notinhas.php?action=excluidos');
        notinhasExcluidas = await response.json();
        renderizarExcluidos();
        
        // Atualiza badge
        const badge = document.getElementById('badge-excluidos');
        badge.textContent = notinhasExcluidas.length > 0 ? notinhasExcluidas.length : '';
    } catch (error) {
        console.error('Erro:', error);
    }
}

function renderizarExcluidos() {
    const container = document.getElementById('excluidos-lista');

    if (notinhasExcluidas.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="icon">🗑️</div>
                <p>Lixeira vazia</p>
            </div>
        `;
        return;
    }

    container.innerHTML = notinhasExcluidas.map(n => {
        const total = n.clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0);
        const clientesNomes = n.clientes.map(c => c.nome.split(' ')[0]).join(', ');
        const diasRestantes = parseInt(n.dias_restantes);
        const classeUrgencia = diasRestantes <= 3 ? 'poucos' : '';

        return `
            <div class="excluido-row">
                <div class="notinha-empresa">${n.empresa_nome}</div>
                <div class="notinha-clientes-preview">${clientesNomes}</div>
                <div class="notinha-valor">${formatarValor(total)}</div>
                <div class="dias-restantes ${classeUrgencia}">${diasRestantes} dias</div>
                <div class="notinha-acoes">
                    <button class="btn-restaurar" onclick="restaurarNotinha(${n.id})">↩️ Restaurar</button>
                    <button class="btn-excluir-perm" onclick="excluirPermanente(${n.id})">❌</button>
                </div>
            </div>
        `;
    }).join('');
}

async function restaurarNotinha(id) {
    try {
        const response = await fetch('api/notinhas.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Notinha restaurada!');
            carregarNotinhas();
            carregarExcluidos();
        }
    } catch (error) {
        showToast('Erro', 'error');
    }
}

async function excluirPermanente(id) {
    if (!confirm('Excluir PERMANENTEMENTE? Esta ação não pode ser desfeita!')) return;
    try {
        const response = await fetch(`api/notinhas.php?id=${id}&permanent=1`, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
            showToast('Excluído permanentemente!');
            carregarExcluidos();
        }
    } catch (error) {
        showToast('Erro', 'error');
    }
}

// ==================== EDIÇÃO ====================
function abrirEdicao(notinha) {
    document.getElementById('editar-id').value = notinha.id;
    document.getElementById('editar-empresa').value = notinha.empresa_nome;
    document.getElementById('editar-data').value = notinha.data_cobranca;
    
    clientesEdicao = notinha.clientes.map(c => ({
        id: c.id,
        nome: c.nome,
        valor: parseFloat(c.valor).toFixed(2).replace('.', ','),
        telefone: c.telefone
    }));
    
    renderizarClientesEdicao();
    document.getElementById('modal-editar').classList.add('show');
}

function fecharEdicao() {
    document.getElementById('modal-editar').classList.remove('show');
}

function adicionarClienteEdicao() {
    clientesEdicao.push({ id: 'novo_' + Date.now(), nome: '', valor: '', telefone: '' });
    renderizarClientesEdicao();
}

function removerClienteEdicao(id) {
    clientesEdicao = clientesEdicao.filter(c => c.id != id);
    renderizarClientesEdicao();
}

function atualizarClienteEdicao(id, campo, valor) {
    const cliente = clientesEdicao.find(c => c.id == id);
    if (cliente) cliente[campo] = valor;
}

function renderizarClientesEdicao() {
    document.getElementById('editar-clientes-lista').innerHTML = clientesEdicao.map(c => `
        <div class="cliente-row">
            <input type="text" placeholder="Nome" value="${c.nome}" 
                   oninput="atualizarClienteEdicao('${c.id}', 'nome', this.value)">
            <input type="text" class="valor" placeholder="R$ 0,00" value="${c.valor}"
                   oninput="atualizarClienteEdicao('${c.id}', 'valor', this.value)">
            <input type="text" placeholder="Telefone" value="${c.telefone}"
                   oninput="atualizarClienteEdicao('${c.id}', 'telefone', this.value)">
            <button class="btn-remove" onclick="removerClienteEdicao('${c.id}')">×</button>
        </div>
    `).join('');
}

async function salvarEdicao() {
    const id = document.getElementById('editar-id').value;
    const empresa = document.getElementById('editar-empresa').value.trim();
    const dataCobranca = document.getElementById('editar-data').value;
    const clientesValidos = clientesEdicao.filter(c => c.nome.trim() !== '');

    if (!empresa || !dataCobranca || clientesValidos.length === 0) {
        showToast('Preencha todos os campos!', 'error');
        return;
    }

    try {
        const response = await fetch('api/notinhas.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id,
                empresa,
                data_cobranca: dataCobranca,
                clientes: clientesValidos
            })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Notinha atualizada!');
            fecharEdicao();
            carregarNotinhas();
        } else {
            showToast(result.error || 'Erro', 'error');
        }
    } catch (error) {
        showToast('Erro de conexão', 'error');
    }
}

// ==================== CONFIGURAÇÕES ====================
async function carregarConfiguracoes() {
    try {
        const response = await fetch('api/configuracoes.php');
        configuracoes = await response.json();
        document.getElementById('config-pix').value = configuracoes.chave_pix || '';
        document.getElementById('config-nome').value = configuracoes.nome_vendedor || '';
        document.getElementById('config-mensagem').value = configuracoes.mensagem_cobranca || 'Bom dia {nome} tudo bem? {vendedor}, passando para deixar meu pix e o valor dos produtos 🙏 {valor} Chave pix {pix}';
    } catch (error) {
        console.error('Erro:', error);
    }
}

function abrirConfiguracoes() {
    document.getElementById('modal-config').classList.add('show');
}

function fecharConfiguracoes() {
    document.getElementById('modal-config').classList.remove('show');
}

async function salvarConfiguracoes() {
    const dados = {
        chave_pix: document.getElementById('config-pix').value,
        nome_vendedor: document.getElementById('config-nome').value,
        mensagem_cobranca: document.getElementById('config-mensagem').value
    };
    try {
        const response = await fetch('api/configuracoes.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        });
        const result = await response.json();
        if (result.success) {
            configuracoes = dados;
            showToast('Salvo!');
            fecharConfiguracoes();
        }
    } catch (error) {
        showToast('Erro', 'error');
    }
}

// ==================== GESTÃO DE CLIENTES ====================
let todosClientes = [];
let clientesFiltrados = [];

async function carregarTodosClientes() {
    try {
        const response = await fetch('api/clientes.php');
        todosClientes = await response.json();
        clientesFiltrados = [...todosClientes];
        renderizarClientes();
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

function renderizarClientes() {
    const container = document.getElementById('clientes-grid');
    
    if (clientesFiltrados.length === 0) {
        container.innerHTML = `
            <div class="clientes-vazio">
                <div class="clientes-vazio-icon">👥</div>
                <p>Nenhum cliente cadastrado</p>
                <p style="font-size: 0.8rem; margin-top: 5px;">Clique em "Novo Cliente" para adicionar</p>
            </div>
        `;
        return;
    }

    container.innerHTML = clientesFiltrados.map(c => `
        <div class="cliente-card">
            <div class="cliente-card-header">
                <span class="cliente-card-nome">${c.nome}</span>
                <div class="cliente-card-acoes">
                    <button onclick="editarCliente(${c.id})" title="Editar">✏️</button>
                    <button onclick="excluirCliente(${c.id})" title="Excluir">🗑️</button>
                </div>
            </div>
            <div class="cliente-card-telefone">
                📱 ${c.telefone ? formatarTelefone(c.telefone) : '<span class="cliente-sem-telefone">Sem telefone</span>'}
            </div>
        </div>
    `).join('');
}

function formatarTelefone(tel) {
    const numeros = tel.replace(/\D/g, '');
    if (numeros.length === 11) {
        return `(${numeros.slice(0,2)}) ${numeros.slice(2,7)}-${numeros.slice(7)}`;
    }
    return tel;
}

function filtrarClientes() {
    const termo = document.getElementById('filtro-clientes').value.toLowerCase();
    clientesFiltrados = todosClientes.filter(c => 
        c.nome.toLowerCase().includes(termo) || 
        (c.telefone && c.telefone.includes(termo))
    );
    renderizarClientes();
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

// ==================== PROMOÇÕES ====================
let clientesSelecionados = [];
let filaPromocao = [];
let indicePromocao = 0;
let mensagemPromocao = '';

function abrirModalPromocao() {
    if (todosClientes.length === 0) {
        showToast('Cadastre clientes primeiro!', 'error');
        return;
    }
    
    const modal = document.getElementById('modal-promocao');
    const lista = document.getElementById('promocao-lista-clientes');
    
    // Só clientes com telefone
    const clientesComTelefone = todosClientes.filter(c => c.telefone);
    
    if (clientesComTelefone.length === 0) {
        showToast('Nenhum cliente com telefone cadastrado!', 'error');
        return;
    }
    
    clientesSelecionados = [];
    document.getElementById('selecionar-todos-clientes').checked = false;
    document.getElementById('promocao-mensagem').value = '';
    
    lista.innerHTML = clientesComTelefone.map(c => `
        <div class="promocao-item">
            <input type="checkbox" id="cliente-check-${c.id}" value="${c.id}" onchange="toggleClientePromocao(${c.id})">
            <div class="promocao-item-info">
                <div class="promocao-item-nome">${c.nome}</div>
                <div class="promocao-item-telefone">${formatarTelefone(c.telefone)}</div>
            </div>
        </div>
    `).join('');
    
    atualizarContadorSelecionados();
    modal.classList.add('show');
}

function fecharModalPromocao() {
    document.getElementById('modal-promocao').classList.remove('show');
}

function toggleClientePromocao(id) {
    const checkbox = document.getElementById(`cliente-check-${id}`);
    if (checkbox.checked) {
        clientesSelecionados.push(id);
    } else {
        clientesSelecionados = clientesSelecionados.filter(cid => cid !== id);
    }
    atualizarContadorSelecionados();
}

function toggleSelecionarTodosClientes() {
    const selecionarTodos = document.getElementById('selecionar-todos-clientes').checked;
    const clientesComTelefone = todosClientes.filter(c => c.telefone);
    
    clientesComTelefone.forEach(c => {
        const checkbox = document.getElementById(`cliente-check-${c.id}`);
        if (checkbox) {
            checkbox.checked = selecionarTodos;
        }
    });
    
    if (selecionarTodos) {
        clientesSelecionados = clientesComTelefone.map(c => c.id);
    } else {
        clientesSelecionados = [];
    }
    
    atualizarContadorSelecionados();
}

function atualizarContadorSelecionados() {
    document.getElementById('total-clientes-selecionados').textContent = clientesSelecionados.length;
}

function iniciarEnvioPromocao() {
    mensagemPromocao = document.getElementById('promocao-mensagem').value.trim();
    
    if (!mensagemPromocao) {
        showToast('Digite a mensagem da promoção!', 'error');
        return;
    }
    
    if (clientesSelecionados.length === 0) {
        showToast('Selecione pelo menos um cliente!', 'error');
        return;
    }
    
    // Monta fila de envio
    filaPromocao = clientesSelecionados.map(id => todosClientes.find(c => c.id == id)).filter(c => c && c.telefone);
    indicePromocao = 0;
    
    fecharModalPromocao();
    mostrarModalEnvioPromocao();
}

function mostrarModalEnvioPromocao() {
    document.getElementById('modal-envio-promocao').classList.add('show');
    document.getElementById('btn-enviar-promocao').style.display = 'block';
    document.getElementById('btn-proximo-promocao').style.display = 'none';
    atualizarModalEnvioPromocao();
}

function atualizarModalEnvioPromocao() {
    const cliente = filaPromocao[indicePromocao];
    const total = filaPromocao.length;
    
    document.getElementById('promocao-progresso').textContent = `${indicePromocao + 1} de ${total}`;
    document.getElementById('promocao-nome').textContent = cliente.nome;
    document.getElementById('promocao-telefone').textContent = formatarTelefone(cliente.telefone);
    
    // Barra de progresso
    const porcentagem = ((indicePromocao + 1) / total) * 100;
    document.getElementById('promocao-barra').style.width = porcentagem + '%';
}

function enviarPromocaoAtual() {
    const cliente = filaPromocao[indicePromocao];
    const primeiroNome = cliente.nome.split(' ')[0];
    
    // Substitui variáveis na mensagem
    let msg = mensagemPromocao.replace(/{nome}/gi, primeiroNome);
    
    const telefone = cliente.telefone.replace(/\D/g, '');
    const telefoneFormatado = telefone.startsWith('55') ? telefone : '55' + telefone;
    
    // Abre WhatsApp
    const url = `whatsapp://send?phone=${telefoneFormatado}&text=${encodeURIComponent(msg)}`;
    window.open(url, '_blank');
    
    // Mostra botão próximo
    document.getElementById('btn-enviar-promocao').style.display = 'none';
    document.getElementById('btn-proximo-promocao').style.display = 'block';
}

function pularPromocaoAtual() {
    indicePromocao++;
    
    if (indicePromocao < filaPromocao.length) {
        document.getElementById('btn-enviar-promocao').style.display = 'block';
        document.getElementById('btn-proximo-promocao').style.display = 'none';
        atualizarModalEnvioPromocao();
    } else {
        fecharModalEnvioPromocao();
        showToast('🎉 Promoção enviada para todos!');
    }
}

function proximaPromocao() {
    indicePromocao++;
    
    if (indicePromocao < filaPromocao.length) {
        document.getElementById('btn-enviar-promocao').style.display = 'block';
        document.getElementById('btn-proximo-promocao').style.display = 'none';
        atualizarModalEnvioPromocao();
    } else {
        fecharModalEnvioPromocao();
        showToast('🎉 Promoção enviada para todos!');
    }
}

function fecharModalEnvioPromocao() {
    document.getElementById('modal-envio-promocao').classList.remove('show');
}
