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


