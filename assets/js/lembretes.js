// ==================== LEMBRETES ====================
let diasAntecedencia = 3; // Dias antes do vencimento para notificar

async function verificarLembretes() {
    try {
        const response = await fetch(`api/lembretes.php?dias=${diasAntecedencia}`);
        const dados = await response.json();
        
        if (dados.total > 0) {
            mostrarBannerLembretes(dados);
        }
    } catch (error) {
        console.error('Erro ao verificar lembretes:', error);
    }
}

function mostrarBannerLembretes(dados) {
    const banner = document.getElementById('banner-lembretes');
    const detalhes = document.getElementById('lembretes-detalhes');
    
    if (!banner) return;
    
    // Mostra notinhas e clientes para clareza
    let texto = `${dados.total} notinha(s)`;
    if (dados.total_clientes && dados.total_clientes > dados.total) {
        texto += ` (${dados.total_clientes} cliente(s))`;
    }
    texto += ` vencendo nos próximos ${diasAntecedencia} dias`;
    
    if (dados.valor_total) {
        texto += ` • Total: ${formatarValor(dados.valor_total)}`;
    }
    
    detalhes.textContent = texto;
    banner.style.display = 'block';
}

function fecharBannerLembretes() {
    const banner = document.getElementById('banner-lembretes');
    if (banner) {
        banner.style.display = 'none';
    }
}

async function verCobrancasProximas() {
    // Muda para aba de notinhas
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelector('[data-tab="lista"]').classList.add('active');
    document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
    document.getElementById('tab-lista').style.display = 'block';
    
    // Limpa filtros visuais
    document.getElementById('filtro-busca').value = '';
    document.getElementById('filtro-status').value = '';
    document.getElementById('filtro-data').value = '';
    
    try {
        // Busca as notinhas diretamente da API de lembretes (mesma fonte do banner)
        const response = await fetch(`api/lembretes.php?dias=${diasAntecedencia}&listar=1`);
        const dados = await response.json();
        
        if (dados.notinhas && dados.notinhas.length > 0) {
            renderizarNotinhas(dados.notinhas);
            
            // Conta clientes
            const totalClientes = dados.notinhas.reduce((sum, n) => sum + (n.clientes?.length || 0), 0);
            
            // Atualiza total filtrado
            const totalFiltrado = dados.notinhas.reduce((total, n) => {
                return total + n.clientes.reduce((sum, c) => sum + parseFloat(c.valor || 0), 0);
            }, 0);
            document.getElementById('valor-filtrado').textContent = formatarValor(totalFiltrado);
            
            let msg = `🔔 ${dados.notinhas.length} notinha(s)`;
            if (totalClientes > dados.notinhas.length) {
                msg += ` (${totalClientes} clientes)`;
            }
            msg += ` nos próximos ${diasAntecedencia} dias`;
            showToast(msg);
        } else {
            document.getElementById('notinhas-lista').innerHTML = `
                <div class="empty-state">
                    <div class="icon">✅</div>
                    <p>Nenhuma cobrança nos próximos ${diasAntecedencia} dias</p>
                </div>
            `;
            document.getElementById('valor-filtrado').textContent = 'R$ 0,00';
            showToast('Nenhuma cobrança nos próximos dias');
        }
    } catch (error) {
        console.error('Erro ao carregar lembretes:', error);
        showToast('Erro ao carregar cobranças', 'error');
    }
    
    fecharBannerLembretes();
}

// ==================== PARCELAMENTO ====================
function toggleParcelamento() {
    const numParcelas = parseInt(document.getElementById('num-parcelas').value);
    const preview = document.getElementById('parcelas-preview');
    const resumo = document.getElementById('parcelas-resumo');
    
    if (numParcelas > 1) {
        preview.style.display = 'block';
        
        // Calcula o total dos clientes
        let totalGeral = 0;
        clientes.forEach(c => {
            const valor = parseFloat(c.valor.replace(',', '.').replace(/[^\d.]/g, '')) || 0;
            totalGeral += valor;
        });
        
        if (totalGeral > 0) {
            const valorParcela = totalGeral / numParcelas;
            resumo.textContent = `${numParcelas}x de ${formatarValor(valorParcela)}`;
        } else {
            resumo.textContent = `${numParcelas} parcelas`;
        }
    } else {
        preview.style.display = 'none';
    }
}

// Atualiza o preview quando muda o valor dos clientes
function atualizarPreviewParcelas() {
    const numParcelas = parseInt(document.getElementById('num-parcelas').value);
    if (numParcelas > 1) {
        toggleParcelamento();
    }
}

// Salvar notinha com parcelas
async function salvarNotinhaComParcelas() {
    const empresa = document.getElementById('empresa').value.trim();
    const dataCobranca = document.getElementById('data-cobranca').value;
    const numParcelas = parseInt(document.getElementById('num-parcelas').value);
    const clientesValidos = clientes.filter(c => c.nome.trim() !== '');

    if (!empresa) return showToast('Informe a empresa!', 'error');
    if (!dataCobranca) return showToast('Informe a data!', 'error');
    if (clientesValidos.length === 0) return showToast('Adicione um cliente!', 'error');

    try {
        const response = await fetch('api/notinhas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                empresa, 
                data_cobranca: dataCobranca, 
                clientes: clientesValidos,
                parcelas: numParcelas
            })
        });
        const result = await response.json();
        if (result.success) {
            showToast(numParcelas > 1 ? `Notinha salva em ${numParcelas} parcelas! 🎉` : 'Notinha salva! 🎉');
            document.getElementById('empresa').value = '';
            document.getElementById('data-cobranca').valueAsDate = new Date();
            document.getElementById('num-parcelas').value = '1';
            document.getElementById('parcelas-preview').style.display = 'none';
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

// Função para marcar parcela como paga
async function marcarParcelaPaga(parcelaId) {
    try {
        const response = await fetch('api/notinhas.php', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'pagar_parcela',
                parcela_id: parcelaId 
            })
        });
        const result = await response.json();
        if (result.success) {
            showToast('Parcela marcada como paga!');
            carregarNotinhas();
        }
    } catch (error) {
        showToast('Erro ao marcar parcela', 'error');
    }
}

