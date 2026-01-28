// ==================== COBRANÇA EM LOTE ====================
function mostrarBannerNotificacao(clientesHoje, clientesAtrasados) {
    const banner = document.getElementById('banner-notificacao');
    const detalhes = document.getElementById('banner-detalhes');
    
    if (clientesHoje > 0 || clientesAtrasados > 0) {
        let texto = [];
        if (clientesHoje > 0) texto.push(`${clientesHoje} cliente(s) para hoje`);
        if (clientesAtrasados > 0) texto.push(`${clientesAtrasados} atrasado(s)`);
        
        detalhes.textContent = texto.join(' • ');
        banner.style.display = 'block';
        
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

function cobrarTodos(notinhaId) {
    const notinha = todasNotinhas.find(n => n.id == notinhaId);
    if (!notinha) return;
    
    const hoje = getHojeLocal();
    const isAtrasada = notinha.data_cobranca < hoje;
    
    if (isAtrasada) {
        filaCobranca = notinha.clientes.map(c => ({ 
            ...c, 
            empresa: notinha.empresa_nome, 
            reenvio: c.msg_enviada == 1 
        }));
        modoReenvio = true;
    } else {
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
    modoReenvio = false;
    mostrarModalCobranca();
}

async function cobrarTodasAtrasadas() {
    const hoje = getHojeLocal();
    const notinhasAtrasadas = todasNotinhas.filter(n => n.data_cobranca < hoje);
    
    filaCobranca = [];
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
    modoReenvio = true;
    mostrarModalCobranca();
}

function mostrarModalCobranca() {
    const modal = document.getElementById('modal-cobranca');
    modal.classList.add('show');
    
    document.getElementById('btn-enviar').style.display = 'block';
    document.getElementById('btn-proximo').style.display = 'none';
    
    document.addEventListener('keydown', atalhoEnterCobranca);
    
    atualizarModalCobranca();
}

function atalhoEnterCobranca(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        
        const btnEnviar = document.getElementById('btn-enviar');
        const btnProximo = document.getElementById('btn-proximo');
        
        if (btnEnviar.style.display !== 'none') {
            enviarCobrancaAtual();
        } 
        else if (btnProximo.style.display !== 'none') {
            proximoCliente();
        }
    }
}

function fecharModalCobranca() {
    document.getElementById('modal-cobranca').classList.remove('show');
    document.removeEventListener('keydown', atalhoEnterCobranca);
    carregarNotinhas();
}

function atualizarModalCobranca() {
    const cliente = filaCobranca[indiceAtual];
    const total = filaCobranca.length;
    
    document.getElementById('cobranca-progresso').textContent = `${indiceAtual + 1} de ${total}`;
    document.getElementById('cobranca-nome').textContent = cliente.nome;
    document.getElementById('cobranca-valor').textContent = formatarValor(cliente.valor);
    document.getElementById('cobranca-telefone').textContent = cliente.telefone;
    document.getElementById('cobranca-empresa').textContent = cliente.empresa;
    
    const badgeReenvio = document.getElementById('badge-reenvio');
    badgeReenvio.style.display = cliente.reenvio ? 'inline-block' : 'none';
    
    const tituloModal = document.getElementById('titulo-modal-cobranca');
    tituloModal.textContent = modoReenvio ? '🔄 Reenviar Cobranças' : '💬 Enviar Cobranças';
    
    const porcentagem = ((indiceAtual + 1) / total) * 100;
    document.getElementById('cobranca-barra').style.width = porcentagem + '%';
}

function enviarCobrancaAtual() {
    const cliente = filaCobranca[indiceAtual];
    
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
    
    window.location.href = `whatsapp://send?phone=${tel}&text=${encodeURIComponent(mensagem)}`;
    
    document.getElementById('btn-enviar').style.display = 'none';
    document.getElementById('btn-proximo').style.display = 'block';
}

function proximoCliente() {
    const cliente = filaCobranca[indiceAtual];
    
    if (!modoReenvio && !cliente.reenvio) {
        marcarComoEnviado([cliente.id]);
    }
    
    indiceAtual++;
    
    if (indiceAtual < filaCobranca.length) {
        document.getElementById('btn-enviar').style.display = 'block';
        document.getElementById('btn-proximo').style.display = 'none';
        atualizarModalCobranca();
    } else {
        fecharModalCobranca();
        showToast(modoReenvio ? '🎉 Reenvio concluído!' : '🎉 Todas as cobranças enviadas!');
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
        
        setTimeout(() => carregarNotinhas(), 1000);
    } catch (error) {
        console.error('Erro ao marcar como enviado:', error);
    }
}

function reenviarMensagem(telefone, nomeCompleto, valor) {
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
    
    window.location.href = `whatsapp://send?phone=${tel}&text=${encodeURIComponent(mensagem)}`;
    
    if (clienteId) {
        marcarComoEnviado([clienteId]);
    }
}

