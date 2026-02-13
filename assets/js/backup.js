// ==================== BACKUP E EXPORTAÇÃO ====================

// Exportar todos os dados para JSON
async function exportarDados() {
    try {
        showToast('⏳ Preparando backup...');
        
        const response = await fetch('api/backup.php?action=export');
        const dados = await response.json();
        
        if (dados.error) {
            showToast(dados.error, 'error');
            return;
        }
        
        // Criar arquivo para download
        const dataStr = JSON.stringify(dados, null, 2);
        const dataBlob = new Blob([dataStr], { type: 'application/json' });
        
        const dataHora = new Date().toISOString().slice(0, 19).replace(/[T:]/g, '-');
        const nomeArquivo = `backup-cobrancas-${dataHora}.json`;
        
        // Criar link de download
        const link = document.createElement('a');
        link.href = URL.createObjectURL(dataBlob);
        link.download = nomeArquivo;
        link.click();
        
        URL.revokeObjectURL(link.href);
        showToast('✅ Backup exportado com sucesso!');
        
    } catch (error) {
        console.error('Erro ao exportar:', error);
        showToast('Erro ao exportar dados', 'error');
    }
}

// Importar dados de um arquivo JSON
async function importarDados(arquivo) {
    if (!arquivo) return;
    
    if (!confirm('⚠️ ATENÇÃO: Importar vai SUBSTITUIR todos os dados atuais. Deseja continuar?')) {
        return;
    }
    
    try {
        showToast('⏳ Importando dados...');
        
        const texto = await arquivo.text();
        const dados = JSON.parse(texto);
        
        const response = await fetch('api/backup.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'import', dados })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showToast('✅ Dados importados com sucesso! Recarregando...');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(result.error || 'Erro ao importar', 'error');
        }
        
    } catch (error) {
        console.error('Erro ao importar:', error);
        showToast('Erro ao ler arquivo. Verifique se é um backup válido.', 'error');
    }
}

// Abrir seletor de arquivo para importar
function abrirImportarDados() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    input.onchange = (e) => importarDados(e.target.files[0]);
    input.click();
}

// Exportar relatório em PDF (usando impressão do navegador)
function exportarRelatorioPDF() {
    // Abre modal com opções de relatório
    abrirModalRelatorio();
}

// Gerar relatório
async function gerarRelatorio() {
    const mes = document.getElementById('relatorio-mes')?.value;
    const tipo = document.getElementById('relatorio-tipo')?.value || 'completo';
    
    try {
        showToast('⏳ Gerando relatório...');
        
        const response = await fetch(`api/relatorio.php?mes=${mes}&tipo=${tipo}`);
        const dados = await response.json();
        
        if (dados.error) {
            showToast(dados.error, 'error');
            return;
        }
        
        // Abre nova janela com o relatório formatado para impressão
        const janela = window.open('', '_blank');
        janela.document.write(gerarHTMLRelatorio(dados, mes, tipo));
        janela.document.close();
        
        // Auto print
        setTimeout(() => {
            janela.print();
        }, 500);
        
        fecharModalRelatorio();
        showToast('📄 Relatório gerado! Use Ctrl+P para imprimir ou salvar como PDF.');
        
    } catch (error) {
        console.error('Erro ao gerar relatório:', error);
        showToast('Erro ao gerar relatório', 'error');
    }
}

// Gerar HTML do relatório
function gerarHTMLRelatorio(dados, mes, tipo) {
    const mesNome = mes ? new Date(mes + '-01').toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' }) : 'Todos os meses';
    
    return `
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Cobranças - ${mesNome}</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: Arial, sans-serif; padding: 20px; color: #333; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
            .header h1 { font-size: 24px; margin-bottom: 5px; }
            .header p { color: #666; }
            .resumo { display: flex; justify-content: space-around; margin-bottom: 30px; padding: 15px; background: #f5f5f5; }
            .resumo-item { text-align: center; }
            .resumo-item .valor { font-size: 24px; font-weight: bold; color: #333; }
            .resumo-item .label { font-size: 12px; color: #666; }
            .resumo-item.recebido .valor { color: #22c55e; }
            .resumo-item.pendente .valor { color: #f59e0b; }
            .resumo-item.inadimplente .valor { color: #ef4444; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background: #f5f5f5; font-weight: bold; }
            .secao { margin-top: 30px; }
            .secao h2 { font-size: 18px; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #ddd; }
            .valor-positivo { color: #22c55e; }
            .valor-negativo { color: #ef4444; }
            .rodape { margin-top: 40px; text-align: center; font-size: 12px; color: #999; }
            @media print {
                body { padding: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>📝 Relatório de Cobranças</h1>
            <p>${mesNome} • Gerado em ${new Date().toLocaleDateString('pt-BR')}</p>
        </div>
        
        <div class="resumo">
            <div class="resumo-item">
                <div class="valor">R$ ${(dados.total_lancado || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                <div class="label">LANÇADO</div>
            </div>
            <div class="resumo-item recebido">
                <div class="valor">R$ ${(dados.total_recebido || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                <div class="label">RECEBIDO</div>
            </div>
            <div class="resumo-item pendente">
                <div class="valor">R$ ${(dados.total_pendente || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                <div class="label">PENDENTE</div>
            </div>
            <div class="resumo-item inadimplente">
                <div class="valor">R$ ${(dados.total_inadimplente || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</div>
                <div class="label">INADIMPLENTE</div>
            </div>
        </div>
        
        ${dados.notinhas && dados.notinhas.length > 0 ? `
        <div class="secao">
            <h2>📋 Detalhamento</h2>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Empresa</th>
                        <th>Cliente</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${dados.notinhas.map(n => `
                        <tr>
                            <td>${new Date(n.data_cobranca + 'T00:00:00').toLocaleDateString('pt-BR')}</td>
                            <td>${n.empresa_nome}</td>
                            <td>${n.cliente_nome}</td>
                            <td>R$ ${parseFloat(n.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</td>
                            <td>${n.status}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        ` : ''}
        
        <div class="rodape">
            Sistema de Cobranças • ${new Date().getFullYear()}
        </div>
    </body>
    </html>
    `;
}

// Modal de Relatório
function abrirModalRelatorio() {
    const modal = document.getElementById('modal-relatorio');
    if (modal) {
        // Define mês atual como padrão
        const mesAtual = new Date().toISOString().slice(0, 7);
        document.getElementById('relatorio-mes').value = mesAtual;
        modal.classList.add('show');
    }
}

function fecharModalRelatorio() {
    const modal = document.getElementById('modal-relatorio');
    if (modal) {
        modal.classList.remove('show');
    }
}


