// ==================== INADIMPLENTES ====================
async function carregarInadimplentes() {
    try {
        const response = await fetch('api/notinhas.php?action=inadimplentes');
        notinhasInadimplentes = await response.json();
        renderizarInadimplentes();
        
        const badge = document.getElementById('badge-inadimplentes');
        badge.textContent = notinhasInadimplentes.length > 0 ? notinhasInadimplentes.length : '';
        
        const total = notinhasInadimplentes.reduce((sum, n) => {
            return sum + n.clientes.reduce((s, c) => s + parseFloat(c.valor || 0), 0);
        }, 0);
        document.getElementById('total-inadimplentes').textContent = formatarValor(total);
    } catch (error) {
        console.error('Erro:', error);
    }
}

function renderizarInadimplentes() {
    const container = document.getElementById('inadimplentes-lista');

    if (notinhasInadimplentes.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="icon">✅</div>
                <p>Nenhum inadimplente! Ótimo!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = notinhasInadimplentes.map(n => {
        return n.clientes.map(c => `
            <div class="inadimplente-row">
                <div class="notinha-empresa">${n.empresa_nome}</div>
                <div class="notinha-cliente">${c.nome}</div>
                <div class="notinha-valor" style="color: #f59e0b; font-weight: 600;">${formatarValor(c.valor)}</div>
                <div class="notinha-data">${formatarData(n.data_cobranca)}</div>
                <div class="notinha-acoes">
                    <button class="btn-cobrar-inadimplente" onclick="cobrarInadimplente('${c.telefone}', '${c.nome.replace(/'/g, "\\'")}', '${c.valor}')" title="Enviar cobrança">💬 Cobrar</button>
                    <button class="btn-restaurar" onclick="restaurarDeInadimplente(${n.id})">↩️</button>
                    <button class="btn-excluir-perm" onclick="excluirNotinha(${n.id})">🗑️</button>
                </div>
            </div>
        `).join('');
    }).join('');
}

async function marcarInadimplente(id) {
    if (!confirm('Marcar esta notinha como inadimplente?')) return;
    try {
        const response = await fetch('api/notinhas.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'inadimplente', id })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Marcado como inadimplente!');
            carregarNotinhas();
            carregarInadimplentes();
        }
    } catch (error) {
        showToast('Erro', 'error');
    }
}

async function restaurarDeInadimplente(id) {
    try {
        const response = await fetch('api/notinhas.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'restaurar', id })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Notinha restaurada!');
            carregarNotinhas();
            carregarInadimplentes();
        }
    } catch (error) {
        showToast('Erro', 'error');
    }
}

function cobrarInadimplente(telefone, nomeCompleto, valor) {
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
}

