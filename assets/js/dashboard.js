// ==================== DASHBOARD ====================
let graficoVendasMes = null;
let graficoVendasSemana = null;

async function carregarDashboard() {
    try {
        const response = await fetch('api/dashboard.php');
        const dados = await response.json();
        
        atualizarMetricas(dados);
        renderizarGraficoMes(dados.vendas_por_mes);
        renderizarGraficoInadimplentes(dados.inadimplentes_por_mes);
        renderizarRankingClientes(dados.top_clientes);
        renderizarProximosVencimentos(dados.proximos_vencimentos);
    } catch (error) {
        console.error('Erro ao carregar dashboard:', error);
    }
}

function atualizarMetricas(dados) {
    document.getElementById('metrica-recebido-mes').textContent = formatarValor(dados.recebido_mes || 0);
    document.getElementById('metrica-previsao').textContent = formatarValor(dados.previsao_recebimentos || 0);
    document.getElementById('metrica-inadimplencia').textContent = (dados.taxa_inadimplencia || 0).toFixed(1) + '%';
    document.getElementById('metrica-clientes').textContent = dados.total_clientes || 0;
}

function renderizarGraficoMes(dados) {
    const ctx = document.getElementById('grafico-vendas-mes');
    if (!ctx) return;
    
    // Destruir gráfico anterior se existir
    if (graficoVendasMes) {
        graficoVendasMes.destroy();
    }
    
    const meses = dados.map(d => d.mes);
    const lancados = dados.map(d => parseFloat(d.lancado || d.total || 0));
    const recebidos = dados.map(d => parseFloat(d.recebido || 0));
    
    graficoVendasMes = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: meses,
            datasets: [
                {
                    label: 'Lançado',
                    data: lancados,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                },
                {
                    label: 'Recebido',
                    data: recebidos,
                    backgroundColor: 'rgba(34, 197, 94, 0.7)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        color: '#94a3b8',
                        usePointStyle: true,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': R$ ' + context.raw.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#94a3b8',
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    },
                    grid: {
                        color: 'rgba(255,255,255,0.05)'
                    }
                },
                x: {
                    ticks: {
                        color: '#94a3b8'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function renderizarGraficoInadimplentes(dados) {
    const ctx = document.getElementById('grafico-inadimplentes');
    if (!ctx) return;
    
    if (graficoVendasSemana) {
        graficoVendasSemana.destroy();
    }
    
    const meses = dados.map(d => d.mes);
    const valores = dados.map(d => parseFloat(d.total));
    
    graficoVendasSemana = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: meses,
            datasets: [{
                label: 'Inadimplentes',
                data: valores,
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                borderColor: 'rgba(239, 68, 68, 1)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Perdas: R$ ' + context.raw.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#94a3b8',
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    },
                    grid: {
                        color: 'rgba(255,255,255,0.05)'
                    }
                },
                x: {
                    ticks: {
                        color: '#94a3b8'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function renderizarRankingClientes(clientes) {
    const container = document.getElementById('ranking-clientes');
    if (!container) return;
    
    if (!clientes || clientes.length === 0) {
        container.innerHTML = `
            <div class="dashboard-empty">
                <div class="dashboard-empty-icon">👥</div>
                <p>Nenhum cliente ainda</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = clientes.map((cliente, index) => {
        let classPosicao = 'outros';
        if (index === 0) classPosicao = 'top1';
        else if (index === 1) classPosicao = 'top2';
        else if (index === 2) classPosicao = 'top3';
        
        return `
            <div class="ranking-item" onclick="abrirHistoricoCliente('${cliente.nome.replace(/'/g, "\\'")}', '${cliente.telefone || ''}')">
                <div class="ranking-posicao ${classPosicao}">${index + 1}</div>
                <div class="ranking-info">
                    <div class="ranking-nome">${cliente.nome}</div>
                    <div class="ranking-detalhe">${cliente.total_compras} compra(s)</div>
                </div>
                <div class="ranking-valor">${formatarValor(cliente.total_gasto)}</div>
            </div>
        `;
    }).join('');
}

function renderizarProximosVencimentos(vencimentos) {
    const container = document.getElementById('proximos-vencimentos');
    if (!container) return;
    
    if (!vencimentos || vencimentos.length === 0) {
        container.innerHTML = `
            <div class="dashboard-empty">
                <div class="dashboard-empty-icon">✅</div>
                <p>Nenhum vencimento próximo</p>
            </div>
        `;
        return;
    }
    
    const hoje = getHojeLocal();
    const amanha = new Date();
    amanha.setDate(amanha.getDate() + 1);
    const amanhaStr = amanha.toISOString().split('T')[0];
    
    container.innerHTML = vencimentos.map(v => {
        let classeVencimento = '';
        let labelDia = formatarData(v.data_cobranca);
        
        if (v.data_cobranca === hoje) {
            classeVencimento = 'vence-hoje';
            labelDia = 'Hoje';
        } else if (v.data_cobranca === amanhaStr) {
            classeVencimento = 'vence-amanha';
            labelDia = 'Amanhã';
        }
        
        return `
            <div class="vencimento-item ${classeVencimento}">
                <div class="vencimento-info">
                    <div class="vencimento-cliente">${v.cliente_nome}</div>
                    <div class="vencimento-empresa">${v.empresa_nome}</div>
                </div>
                <div class="vencimento-data">
                    <div class="vencimento-dia">${labelDia}</div>
                    <div class="vencimento-valor">${formatarValor(v.valor)}</div>
                </div>
            </div>
        `;
    }).join('');
}

