<?php
// Verificar autenticação
require_once __DIR__ . '/app/autoload.php';
use App\Core\Auth;
Auth::verificarLogin('login.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>📝 Bloco de Cobranças</title>
    
    <!-- PWA Meta Tags -->
    <meta name="description" content="Sistema de gerenciamento de cobranças e notinhas">
    <meta name="theme-color" content="#3b82f6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Cobranças">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#3b82f6">
    <meta name="msapplication-tap-highlight" content="no">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="assets/icons/icon-152.png">
    <link rel="apple-touch-icon" sizes="72x72" href="assets/icons/icon-72.png">
    <link rel="apple-touch-icon" sizes="96x96" href="assets/icons/icon-96.png">
    <link rel="apple-touch-icon" sizes="128x128" href="assets/icons/icon-128.png">
    <link rel="apple-touch-icon" sizes="144x144" href="assets/icons/icon-144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="assets/icons/icon-152.png">
    <link rel="apple-touch-icon" sizes="192x192" href="assets/icons/icon-192.png">
    <link rel="apple-touch-icon" sizes="384x384" href="assets/icons/icon-384.png">
    <link rel="apple-touch-icon" sizes="512x512" href="assets/icons/icon-512.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icons/icon-96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/icons/icon-72.png">
    
    <!-- Splash Screens iOS -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link rel="stylesheet" href="assets/css/alertas.css">
    <link rel="stylesheet" href="assets/css/tabs.css">
    <link rel="stylesheet" href="assets/css/forms.css">
    <link rel="stylesheet" href="assets/css/notinhas.css">
    <link rel="stylesheet" href="assets/css/clientes.css">
    <link rel="stylesheet" href="assets/css/inadimplentes.css">
    <link rel="stylesheet" href="assets/css/excluidos.css">
    <link rel="stylesheet" href="assets/css/modais.css">
    <link rel="stylesheet" href="assets/css/cobranca.css">
    <link rel="stylesheet" href="assets/css/promocao.css">
    <link rel="stylesheet" href="assets/css/toast.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/historico.css">
    <link rel="stylesheet" href="assets/css/lembretes.css">
    <link rel="stylesheet" href="assets/css/recebidos.css">
    <link rel="stylesheet" href="assets/css/acessibilidade.css">
    <link rel="stylesheet" href="assets/css/busca-global.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 data-i18n="titulo">📝 Bloco de Cobranças</h1>
            <div class="header-buttons">
                <!-- Busca Global -->
                <button class="btn-header-icon" onclick="abrirBuscaGlobal()" title="Busca global (Ctrl+F)">
                    🔍
                </button>

                <!-- Dropdown de Acessibilidade -->
                <div class="acessibilidade-selector">
                    <button class="btn-acessibilidade-toggle" onclick="toggleAcessibilidadeDropdown()">
                        ⚡ ▼
                    </button>
                    <div class="acessibilidade-dropdown" id="acessibilidade-dropdown">
                        <div class="dropdown-section">
                            <span class="dropdown-label">Fonte</span>
                            <div class="dropdown-buttons">
                                <button class="dropdown-btn" onclick="diminuirFonte()">A-</button>
                                <button class="dropdown-btn" onclick="aumentarFonte()">A+</button>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <div class="dropdown-section">
                            <span class="dropdown-label">Tema</span>
                            <div class="dropdown-buttons">
                                <button class="dropdown-btn" id="btn-tema" onclick="toggleModoTema()">
                                    ☀️ Claro
                                </button>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <button class="dropdown-item" onclick="exportarDados()">
                            💾 Backup
                        </button>
                        <button class="dropdown-item" onclick="abrirModalAtalhos()">
                            ⌨️ Atalhos
                        </button>
                    </div>
                </div>

                <!-- Dropdown de Idioma -->
                <div class="idioma-selector">
                    <button class="btn-idioma" onclick="toggleIdiomaDropdown()">
                        🌐 <span id="idioma-atual">PT</span> ▼
                    </button>
                    <div class="idioma-dropdown" id="idioma-dropdown">
                        <button onclick="mudarIdioma('pt')" class="idioma-opcao active" data-lang="pt">
                            🇧🇷 Português
                        </button>
                        <button onclick="mudarIdioma('en')" class="idioma-opcao" data-lang="en">
                            🇺🇸 English
                        </button>
                        <button onclick="mudarIdioma('es')" class="idioma-opcao" data-lang="es">
                            🇪🇸 Español
                        </button>
                    </div>
                </div>

                <!-- Botões de Ação -->
                <button class="btn-config" onclick="abrirModalRelatorio()" title="Gerar relatório PDF">
                    📄
                </button>
                <button class="btn-config" onclick="abrirConfiguracoes()">
                    ⚙️ <span data-i18n="configuracoes">Configurações</span>
                </button>
                <button class="btn-config btn-logout" onclick="fazerLogout()" title="Sair do sistema">
                    🚪
                </button>
            </div>
        </div>

        <!-- Alertas -->
        <div class="alertas">
            <div class="alerta alerta-hoje" onclick="filtrarPorStatus('hoje')">
                <span class="alerta-icon">📅</span>
                <div class="alerta-info">
                    <h3 data-i18n="vencemHoje">Vencem Hoje</h3>
                    <span class="numero" id="contador-hoje">0</span>
                </div>
                <button class="btn-cobrar-todos" id="btn-cobrar-hoje" onclick="event.stopPropagation(); cobrarTodosHoje()">
                    💬 <span data-i18n="cobrarTodos">Cobrar Todos</span>
                </button>
            </div>
            <div class="alerta alerta-atrasado" onclick="filtrarPorStatus('atrasado')">
                <span class="alerta-icon">⚠️</span>
                <div class="alerta-info">
                    <h3 data-i18n="atrasadas">Atrasadas</h3>
                    <span class="numero" id="contador-atrasado">0</span>
                </div>
                <button class="btn-cobrar-todos" id="btn-cobrar-atrasadas" onclick="event.stopPropagation(); cobrarTodasAtrasadas()">
                    💬 <span data-i18n="cobrarTodos">Cobrar Todos</span>
                </button>
            </div>
            <div class="alerta alerta-total">
                <span class="alerta-icon">💰</span>
                <div class="alerta-info">
                    <h3 data-i18n="totalGeral">Total Geral</h3>
                    <span class="numero" id="total-geral">R$ 0,00</span>
                </div>
            </div>
        </div>

        <!-- Banner de Notificação -->
        <div class="banner-notificacao" id="banner-notificacao" style="display: none;">
            <div class="banner-content">
                <span class="banner-icon">🔔</span>
                <div class="banner-texto">
                    <strong data-i18n="temCobrancasHoje">Você tem cobranças para hoje!</strong>
                    <p id="banner-detalhes"></p>
                </div>
                <button class="btn-cobrar-banner" onclick="cobrarTodosHoje()">💬 <span data-i18n="cobrarAgora">Cobrar Agora</span></button>
                <button class="btn-fechar-banner" onclick="fecharBanner()">✕</button>
            </div>
        </div>

        <!-- Banner de Lembretes (Vencimentos Próximos) -->
        <div class="banner-lembretes" id="banner-lembretes" style="display: none;">
            <div class="banner-content">
                <span class="banner-icon">⏰</span>
                <div class="banner-texto">
                    <strong>Atenção! Cobranças vencendo em breve</strong>
                    <p id="lembretes-detalhes"></p>
                </div>
                <button class="btn-ver-lembretes" onclick="verCobrancasProximas()">📋 Ver Detalhes</button>
                <button class="btn-fechar-banner" onclick="fecharBannerLembretes()">✕</button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab" data-tab="dashboard">📊 Dashboard</button>
            <button class="tab active" data-tab="nova">➕ <span data-i18n="nova">Nova</span></button>
            <button class="tab" data-tab="lista">📋 <span data-i18n="notinhas">Notinhas</span></button>
            <button class="tab" data-tab="recebidos">✅ <span data-i18n="recebidos">Recebidos</span> <span class="badge badge-success" id="badge-recebidos"></span></button>
            <button class="tab" data-tab="clientes">👥 <span data-i18n="clientes">Clientes</span> <span class="badge" id="badge-clientes"></span></button>
            <button class="tab" data-tab="inadimplentes">💸 <span data-i18n="inadimplentes">Inadimplentes</span> <span class="badge" id="badge-inadimplentes"></span></button>
            <button class="tab" data-tab="excluidos">🗑️ <span data-i18n="excluidos">Excluídos</span> <span class="badge" id="badge-excluidos"></span></button>
        </div>

        <!-- Dashboard -->
        <div id="tab-dashboard" class="tab-content" style="display: none;">
            <!-- Métricas Principais -->
            <div class="dashboard-metricas">
                <div class="metrica-card">
                    <div class="metrica-icon">💰</div>
                    <div class="metrica-info">
                        <span class="metrica-label">Recebido este Mês</span>
                        <span class="metrica-valor" id="metrica-recebido-mes">R$ 0,00</span>
                    </div>
                </div>
                <div class="metrica-card">
                    <div class="metrica-icon">📈</div>
                    <div class="metrica-info">
                        <span class="metrica-label">Previsão de Recebimentos</span>
                        <span class="metrica-valor" id="metrica-previsao">R$ 0,00</span>
                    </div>
                </div>
                <div class="metrica-card metrica-warning">
                    <div class="metrica-icon">⚠️</div>
                    <div class="metrica-info">
                        <span class="metrica-label">Taxa de Inadimplência</span>
                        <span class="metrica-valor" id="metrica-inadimplencia">0%</span>
                    </div>
                </div>
                <div class="metrica-card">
                    <div class="metrica-icon">👥</div>
                    <div class="metrica-info">
                        <span class="metrica-label">Total de Clientes</span>
                        <span class="metrica-valor" id="metrica-clientes">0</span>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="dashboard-graficos">
                <div class="grafico-card">
                    <h3>📊 Lançado vs Recebido por Mês</h3>
                    <canvas id="grafico-vendas-mes"></canvas>
                </div>
                <div class="grafico-card">
                    <h3>💸 Inadimplentes por Mês</h3>
                    <canvas id="grafico-inadimplentes"></canvas>
                </div>
            </div>

            <!-- Ranking de Clientes -->
            <div class="dashboard-ranking">
                <div class="ranking-card">
                    <h3>🏆 Top 10 Clientes (Mais Compram)</h3>
                    <div id="ranking-clientes"></div>
                </div>
                <div class="ranking-card">
                    <h3>⏰ Próximos Vencimentos</h3>
                    <div id="proximos-vencimentos"></div>
                </div>
            </div>
        </div>

        <!-- Nova Notinha -->
        <div id="tab-nova" class="tab-content">
            <div class="form-card">
                <h2>✏️ <span data-i18n="novaNotinha">Nova Notinha</span></h2>

                <div class="form-row">
                    <div class="form-group">
                        <label data-i18n="empresa">Empresa (onde vendeu)</label>
                        <input type="text" id="empresa" data-i18n-placeholder="digiteNome" placeholder="Digite o nome..." autocomplete="off">
                        <div class="autocomplete-list" id="autocomplete-empresa"></div>
                    </div>
                    <div class="form-group">
                        <label data-i18n="dataCobranca">Data Padrão (1º Vencimento)</label>
                        <input type="date" id="data-cobranca" onchange="atualizarDatasClientes()">
                    </div>
                </div>
                
                <div class="info-parcelas-cliente">
                    💡 Configure parcelas e data de vencimento individualmente para cada cliente abaixo
                </div>

                <div class="clientes-table">
                    <div class="clientes-table-header">
                        <span data-i18n="nomeCliente">Nome do Cliente</span>
                        <span data-i18n="valor">Valor</span>
                        <span data-i18n="telefone">Telefone</span>
                        <span></span>
                    </div>
                    <div id="clientes-lista"></div>
                </div>

                <button class="btn-add" onclick="adicionarCliente()">
                    + <span data-i18n="adicionarCliente">Adicionar Cliente</span>
                </button>

                <br>
                <button class="btn-salvar" onclick="salvarNotinha()">
                    💾 <span data-i18n="salvarNotinha">Salvar Notinha</span>
                </button>
            </div>
        </div>

        <!-- Lista de Notinhas -->
        <div id="tab-lista" class="tab-content" style="display: none;">
            <!-- Filtros -->
            <div class="filtros">
                <input type="text" class="filtro-busca" id="filtro-busca" data-i18n-placeholder="buscarEmpresaCliente" placeholder="🔍 Buscar empresa ou cliente...">
                <select class="filtro-status" id="filtro-status">
                    <option value="" data-i18n="todosStatus">Todos os status</option>
                    <option value="hoje" data-i18n="vencemHoje">Vencem hoje</option>
                    <option value="atrasado" data-i18n="atrasadas">Atrasadas</option>
                    <option value="futuro" data-i18n="futuras">Futuras</option>
                </select>
                <input type="date" class="filtro-data" id="filtro-data" data-i18n-title="filtrarData" title="Filtrar por data">
                <button class="btn-limpar-filtro" onclick="limparFiltros()">✕ <span data-i18n="limpar">Limpar</span></button>
            </div>

            <!-- Tabela -->
            <div class="notinhas-table" id="notinhas-container">
                <div class="notinhas-header">
                    <span class="coluna-ordenavel" data-ordenar="empresa" onclick="ordenarPor('empresa')" data-i18n="empresaCol">Empresa ↕</span>
                    <span data-i18n="clientesCol">Clientes</span>
                    <span class="coluna-ordenavel" data-ordenar="data" onclick="ordenarPor('data')" data-i18n="dataCol">Data ↕</span>
                    <span class="coluna-ordenavel" data-ordenar="valor" onclick="ordenarPor('valor')" data-i18n="totalCol">Total ↕</span>
                    <span data-i18n="statusCol">Status</span>
                    <span data-i18n="acoesCol">Ações</span>
                </div>
                <div id="notinhas-lista"></div>
            </div>

            <!-- Total Filtrado -->
            <div class="total-filtrado" id="total-filtrado">
                <span><span data-i18n="totalExibido">Total exibido</span>: <strong id="valor-filtrado">R$ 0,00</strong></span>
            </div>
        </div>

        <!-- Recebidos -->
        <div id="tab-recebidos" class="tab-content" style="display: none;">
            <div class="info-recebidos">
                <span>✅</span>
                <p data-i18n="infoRecebidos">Notinhas que você recebeu o pagamento. Aqui você tem o controle do que foi pago.</p>
            </div>

            <div class="recebidos-resumo">
                <div class="resumo-card recebido">
                    <span class="resumo-label" data-i18n="recebidoEsteMes">Recebido este Mês</span>
                    <span class="resumo-valor" id="total-recebido-mes">R$ 0,00</span>
                </div>
                <div class="resumo-card">
                    <span class="resumo-label" data-i18n="totalRecebido">Total Recebido (Histórico)</span>
                    <span class="resumo-valor" id="total-recebido-geral">R$ 0,00</span>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filtros">
                <input type="text" class="filtro-busca" id="filtro-recebidos-busca" data-i18n-placeholder="buscarEmpresaCliente" placeholder="🔍 Buscar empresa ou cliente...">
                <input type="month" class="filtro-mes" id="filtro-recebidos-mes" title="Filtrar por mês">
                <button class="btn-limpar-filtro" onclick="limparFiltrosRecebidos()">✕ <span data-i18n="limpar">Limpar</span></button>
            </div>

            <div class="notinhas-table">
                <div class="notinhas-header recebidos-header">
                    <span data-i18n="empresaCol">Empresa</span>
                    <span data-i18n="clientesCol">Clientes</span>
                    <span data-i18n="totalCol">Total</span>
                    <span data-i18n="dataRecebimento">Data Recebimento</span>
                    <span data-i18n="acoesCol">Ações</span>
                </div>
                <div id="recebidos-lista"></div>
            </div>
        </div>

        <!-- Clientes -->
        <div id="tab-clientes" class="tab-content" style="display: none;">
            <div class="clientes-header-section">
                <div class="clientes-acoes">
                    <button class="btn-novo-cliente" onclick="abrirModalCliente()">
                        ➕ <span data-i18n="novoCliente">Novo Cliente</span>
                    </button>
                    <button class="btn-promocao" onclick="abrirModalPromocao()">
                        📢 <span data-i18n="enviarPromocao">Enviar Promoção</span>
                    </button>
                </div>
                <input type="text" class="filtro-busca" id="filtro-clientes" data-i18n-placeholder="buscarCliente" placeholder="🔍 Buscar cliente..." oninput="filtrarClientes()">
            </div>

            <div class="clientes-lista" id="clientes-cadastrados-container">
                <div class="clientes-lista-header">
                    <span data-i18n="nome">Nome</span>
                    <span data-i18n="telefone">Telefone</span>
                    <span data-i18n="acoesCol">Ações</span>
                </div>
                <div id="clientes-cadastrados-lista">
                    <!-- Lista de clientes será carregada aqui -->
                </div>
            </div>
            
            <div class="paginacao" id="paginacao-clientes">
                <!-- Paginação será carregada aqui -->
            </div>
        </div>

        <!-- Inadimplentes -->
        <div id="tab-inadimplentes" class="tab-content" style="display: none;">
            <div class="info-inadimplentes">
                <span>💸</span>
                <p data-i18n="infoInadimplentes">Clientes com pagamento pendente ou que você acredita que não vai receber.</p>
            </div>

            <div class="inadimplentes-resumo">
                <div class="resumo-card">
                    <span class="resumo-label" data-i18n="totalInadimplencia">Total em Inadimplência</span>
                    <span class="resumo-valor" id="total-inadimplentes">R$ 0,00</span>
                </div>
            </div>

            <div class="notinhas-table">
                <div class="notinhas-header inadimplentes-header">
                    <span data-i18n="empresaCol">Empresa</span>
                    <span data-i18n="clienteCol">Cliente</span>
                    <span data-i18n="valorCol">Valor</span>
                    <span data-i18n="dataOriginal">Data Original</span>
                    <span data-i18n="acoesCol">Ações</span>
                </div>
                <div id="inadimplentes-lista"></div>
            </div>
        </div>

        <!-- Excluídos -->
        <div id="tab-excluidos" class="tab-content" style="display: none;">
            <div class="info-lixeira">
                <span>🗑️</span>
                <p data-i18n="infoLixeira">Itens excluídos ficam aqui por <strong>15 dias</strong> antes de serem removidos permanentemente.</p>
            </div>

            <!-- Notinhas Excluídas -->
            <h3 class="secao-titulo">📋 <span data-i18n="notinhasExcluidas">Notinhas Excluídas</span></h3>
            <div class="notinhas-table">
                <div class="notinhas-header excluidos-header">
                    <span data-i18n="empresaCol">Empresa</span>
                    <span data-i18n="clientesCol">Clientes</span>
                    <span data-i18n="totalCol">Total</span>
                    <span data-i18n="diasRestantes">Dias Restantes</span>
                    <span data-i18n="acoesCol">Ações</span>
                </div>
                <div id="excluidos-lista"></div>
            </div>

            <!-- Clientes Excluídos -->
            <h3 class="secao-titulo" style="margin-top: 30px;">👤 <span data-i18n="clientesRemovidos">Clientes Removidos de Notinhas</span></h3>
            <div class="notinhas-table">
                <div class="notinhas-header clientes-excluidos-header">
                    <span data-i18n="clienteCol">Cliente</span>
                    <span data-i18n="empresaCol">Empresa</span>
                    <span data-i18n="valorCol">Valor</span>
                    <span data-i18n="diasRestantes">Dias Restantes</span>
                    <span data-i18n="acoesCol">Ações</span>
                </div>
                <div id="clientes-excluidos-lista"></div>
            </div>
        </div>
    </div>

    <!-- Modal Configurações -->
    <div class="modal-overlay" id="modal-config">
        <div class="modal" style="max-width: 550px;">
            <h2>⚙️ <span data-i18n="configuracoes">Configurações</span></h2>
            
            <div class="form-group">
                <label data-i18n="chavePix">Chave PIX</label>
                <input type="text" id="config-pix" data-i18n-placeholder="suaChavePix" placeholder="Sua chave PIX">
            </div>

            <div class="form-group">
                <label data-i18n="nomeVendedor">Nome do Vendedor</label>
                <input type="text" id="config-nome" data-i18n-placeholder="exemploVendedor" placeholder="Ex: Filipe que vende requeijão e doces">
            </div>

            <div class="form-group">
                <label data-i18n="mensagemCobranca">Mensagem de Cobrança</label>
                <textarea id="config-mensagem" rows="4" data-i18n-placeholder="digiteMensagem" placeholder="Digite a mensagem..."></textarea>
            </div>

            <div class="info-box">
                💡 <strong data-i18n="variaveisDisponiveis">Variáveis disponíveis:</strong><br>
                <code>{nome}</code> = <span data-i18n="varNome">Primeiro nome do cliente</span><br>
                <code>{vendedor}</code> = <span data-i18n="varVendedor">Seu nome</span><br>
                <code>{valor}</code> = <span data-i18n="varValor">Valor da cobrança</span><br>
                <code>{pix}</code> = <span data-i18n="varPix">Sua chave PIX</span>
            </div>

            <div class="config-section-divider"></div>
            
            <div class="config-seguranca">
                <h3>🔐 Segurança</h3>
                <button class="btn-alterar-senha" onclick="fecharConfiguracoes(); abrirModalAlterarSenha();">
                    🔑 Alterar Senha
                </button>
            </div>

            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharConfiguracoes()" data-i18n="cancelar">Cancelar</button>
                <button class="btn-salvar-config" onclick="salvarConfiguracoes()">💾 <span data-i18n="salvar">Salvar</span></button>
            </div>
        </div>
    </div>

    <!-- Modal Editar Notinha -->
    <div class="modal-overlay" id="modal-editar">
        <div class="modal" style="max-width: 600px;">
            <h2>✏️ <span data-i18n="editarNotinha">Editar Notinha</span></h2>
            
            <input type="hidden" id="editar-id">
            
            <div class="form-row">
                <div class="form-group">
                    <label data-i18n="empresaCol">Empresa</label>
                    <input type="text" id="editar-empresa" data-i18n-placeholder="nomeEmpresa" placeholder="Nome da empresa">
                </div>
                <div class="form-group">
                    <label data-i18n="dataCobranca">Data da Cobrança</label>
                    <input type="date" id="editar-data">
                </div>
            </div>

            <div class="clientes-table">
                <div class="clientes-table-header">
                    <span data-i18n="nome">Nome</span>
                    <span data-i18n="valor">Valor</span>
                    <span data-i18n="telefone">Telefone</span>
                    <span></span>
                </div>
                <div id="editar-clientes-lista"></div>
            </div>

            <button class="btn-add" onclick="adicionarClienteEdicao()" style="margin: 10px 0;">
                + <span data-i18n="adicionarCliente">Adicionar Cliente</span>
            </button>

            <!-- Clientes Excluídos da Notinha -->
            <div id="clientes-excluidos-edicao" class="clientes-excluidos-container" style="display: none;">
            </div>

            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharEdicao()" data-i18n="cancelar">Cancelar</button>
                <button class="btn-salvar-config" onclick="salvarEdicao()">💾 <span data-i18n="salvar">Salvar</span></button>
            </div>
        </div>
    </div>

    <!-- Modal Cobrança em Lote -->
    <div class="modal-overlay" id="modal-cobranca">
        <div class="modal modal-cobranca">
            <div class="cobranca-header">
                <h2 id="titulo-modal-cobranca">💬 <span data-i18n="enviarCobrancas">Enviar Cobranças</span></h2>
                <span class="cobranca-progresso" id="cobranca-progresso">1 de 5</span>
            </div>
            
            <div class="cobranca-barra-container">
                <div class="cobranca-barra" id="cobranca-barra"></div>
            </div>

            <div class="cobranca-cliente">
                <div class="cobranca-info">
                    <span class="cobranca-label" data-i18n="clienteCol">Cliente</span>
                    <span class="cobranca-nome" id="cobranca-nome">Maria Silva</span>
                    <span class="badge-reenvio" id="badge-reenvio" style="display: none;">🔄 <span data-i18n="reenvio">Reenvio</span></span>
                </div>
                <div class="cobranca-info">
                    <span class="cobranca-label" data-i18n="valorCol">Valor</span>
                    <span class="cobranca-valor" id="cobranca-valor">R$ 50,00</span>
                </div>
                <div class="cobranca-info">
                    <span class="cobranca-label" data-i18n="telefone">Telefone</span>
                    <span class="cobranca-telefone" id="cobranca-telefone">(67) 99999-9999</span>
                </div>
                <div class="cobranca-info">
                    <span class="cobranca-label" data-i18n="empresaCol">Empresa</span>
                    <span class="cobranca-empresa" id="cobranca-empresa">Loja X</span>
                </div>
            </div>

            <div class="cobranca-instrucao" data-i18n="instrucaoCobranca">
                👆 Clique em <strong>Enviar</strong> → WhatsApp abre → Aperte <strong>Enter</strong> → Volte aqui
            </div>

            <div class="cobranca-botoes">
                <button class="btn-pular" onclick="pularCobrancaAtual()" data-i18n="pular">Pular</button>
                <button class="btn-enviar-cobranca" id="btn-enviar" onclick="enviarCobrancaAtual()">
                    💬 <span data-i18n="enviarWhatsApp">Enviar no WhatsApp</span>
                </button>
                <button class="btn-proximo" id="btn-proximo" onclick="proximoCliente()" style="display: none;">
                    ✓ <span data-i18n="enviadoProximo">Enviado! Próximo</span> →
                </button>
            </div>

            <button class="btn-fechar-cobranca" onclick="fecharModalCobranca()">✕ <span data-i18n="fechar">Fechar</span></button>
        </div>
    </div>

    <!-- Modal Novo/Editar Cliente -->
    <div class="modal-overlay" id="modal-cliente">
        <div class="modal" style="max-width: 400px;">
            <h2 id="titulo-modal-cliente">➕ <span data-i18n="novoCliente">Novo Cliente</span></h2>
            
            <input type="hidden" id="cliente-id">
            
            <div class="form-group">
                <label data-i18n="nomeCompleto">Nome Completo</label>
                <input type="text" id="cliente-nome" data-i18n-placeholder="nomeSobrenome" placeholder="Nome e sobrenome">
            </div>

            <div class="form-group">
                <label data-i18n="telefoneWhatsApp">Telefone (WhatsApp)</label>
                <input type="text" id="cliente-telefone" placeholder="67999999999">
            </div>

            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharModalCliente()" data-i18n="cancelar">Cancelar</button>
                <button class="btn-salvar-config" onclick="salvarCliente()">💾 <span data-i18n="salvar">Salvar</span></button>
            </div>
        </div>
    </div>

    <!-- Modal Promoção -->
    <div class="modal-overlay" id="modal-promocao">
        <div class="modal" style="max-width: 550px;">
            <h2>📢 <span data-i18n="enviarPromocao">Enviar Promoção</span></h2>
            
            <div class="form-group">
                <label data-i18n="mensagemPromocao">Mensagem da Promoção</label>
                <textarea id="promocao-mensagem" rows="5" data-i18n-placeholder="digiteMensagemPromocao" placeholder="Digite a mensagem da promoção..."></textarea>
            </div>

            <div class="info-box">
                💡 <strong data-i18n="variavelDisponivel">Variável disponível:</strong><br>
                <code>{nome}</code> = <span data-i18n="varNome">Primeiro nome do cliente</span>
            </div>

            <div class="promocao-seletor">
                <label class="checkbox-container">
                    <input type="checkbox" id="selecionar-todos-clientes" onchange="toggleSelecionarTodosClientes()">
                    <span class="checkmark"></span>
                    <span data-i18n="selecionarTodos">Selecionar todos</span> (<span id="total-clientes-selecionados">0</span> <span data-i18n="clientes">clientes</span>)
                </label>
            </div>

            <div class="promocao-lista" id="promocao-lista-clientes">
                <!-- Lista de clientes com checkbox -->
            </div>

            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharModalPromocao()" data-i18n="cancelar">Cancelar</button>
                <button class="btn-enviar-promocao" onclick="iniciarEnvioPromocao()">
                    📢 <span data-i18n="enviarSelecionados">Enviar para Selecionados</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Envio Promoção -->
    <div class="modal-overlay" id="modal-envio-promocao">
        <div class="modal modal-cobranca">
            <div class="cobranca-header">
                <h2>📢 <span data-i18n="enviandoPromocao">Enviando Promoção</span></h2>
                <span class="cobranca-progresso" id="promocao-progresso">1 de 5</span>
            </div>
            
            <div class="cobranca-barra-container">
                <div class="cobranca-barra" id="promocao-barra"></div>
            </div>

            <div class="cobranca-cliente">
                <div class="cobranca-info">
                    <span class="cobranca-label" data-i18n="clienteCol">Cliente</span>
                    <span class="cobranca-nome" id="promocao-nome">Maria Silva</span>
                </div>
                <div class="cobranca-info">
                    <span class="cobranca-label" data-i18n="telefone">Telefone</span>
                    <span class="cobranca-telefone" id="promocao-telefone">(67) 99999-9999</span>
                </div>
            </div>

            <div class="cobranca-instrucao" data-i18n="instrucaoCobranca">
                👆 Clique em <strong>Enviar</strong> → WhatsApp abre → Aperte <strong>Enter</strong> → Volte aqui
            </div>

            <div class="cobranca-botoes">
                <button class="btn-pular" onclick="pularPromocaoAtual()" data-i18n="pular">Pular</button>
                <button class="btn-enviar-cobranca" id="btn-enviar-promocao" onclick="enviarPromocaoAtual()">
                    💬 Enviar no WhatsApp
                </button>
                <button class="btn-proximo" id="btn-proximo-promocao" onclick="proximaPromocao()" style="display: none;">
                    ✓ Enviado! Próximo →
                </button>
            </div>

            <button class="btn-fechar-cobranca" onclick="fecharModalEnvioPromocao()">✕ Fechar</button>
        </div>
    </div>

    <!-- Modal Histórico do Cliente -->
    <div class="modal-overlay" id="modal-historico">
        <div class="modal modal-historico">
            <div class="historico-header">
                <h2>📊 Histórico do Cliente</h2>
                <button class="btn-fechar-modal" onclick="fecharModalHistorico()">✕</button>
            </div>
            
            <!-- Resumo do Cliente -->
            <div class="historico-resumo">
                <div class="historico-cliente-info">
                    <h3 id="historico-nome">Nome do Cliente</h3>
                    <span id="historico-telefone">📱 (67) 99999-9999</span>
                </div>
                
                <div class="historico-metricas">
                    <div class="historico-metrica">
                        <span class="metrica-valor" id="historico-total-gasto">R$ 0,00</span>
                        <span class="metrica-label">Total Gasto</span>
                    </div>
                    <div class="historico-metrica">
                        <span class="metrica-valor" id="historico-media-ticket">R$ 0,00</span>
                        <span class="metrica-label">Ticket Médio</span>
                    </div>
                    <div class="historico-metrica">
                        <span class="metrica-valor" id="historico-total-compras">0</span>
                        <span class="metrica-label">Compras</span>
                    </div>
                    <div class="historico-metrica" id="historico-status-container">
                        <span class="metrica-valor" id="historico-status">⭐</span>
                        <span class="metrica-label">Status</span>
                    </div>
                </div>
            </div>
            
            <!-- Lista de Compras -->
            <div class="historico-compras">
                <h4>📋 Histórico de Compras</h4>
                <div id="historico-lista">
                    <!-- Lista de compras será carregada aqui -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Busca Global -->
    <div class="modal-overlay" id="modal-busca-global">
        <div class="modal modal-busca-global">
            <h2>🔍 Busca Global</h2>
            <div class="busca-global-container">
                <div class="busca-global-input-container">
                    <input type="text" id="busca-global-input" placeholder="Buscar em notinhas, clientes, empresas..." 
                           oninput="onBuscaGlobalInput()" autocomplete="off">
                </div>
                <div id="busca-global-resultados"></div>
            </div>
            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharBuscaGlobal()">Fechar</button>
            </div>
        </div>
    </div>

    <!-- Modal Atalhos de Teclado -->
    <div class="modal-overlay" id="modal-atalhos">
        <div class="modal modal-atalhos">
            <h2>⌨️ Atalhos de Teclado</h2>
            <div class="atalhos-lista">
                <div class="atalho-item">
                    <div class="atalho-tecla"><span class="tecla">Alt</span><span class="tecla">N</span></div>
                    <span class="atalho-descricao">Nova notinha</span>
                </div>
                <div class="atalho-item">
                    <div class="atalho-tecla"><span class="tecla">Alt</span><span class="tecla">L</span></div>
                    <span class="atalho-descricao">Lista de notinhas</span>
                </div>
                <div class="atalho-item">
                    <div class="atalho-tecla"><span class="tecla">Alt</span><span class="tecla">D</span></div>
                    <span class="atalho-descricao">Dashboard</span>
                </div>
                <div class="atalho-item">
                    <div class="atalho-tecla"><span class="tecla">Alt</span><span class="tecla">C</span></div>
                    <span class="atalho-descricao">Clientes</span>
                </div>
                <div class="atalho-item">
                    <div class="atalho-tecla"><span class="tecla">Alt</span><span class="tecla">R</span></div>
                    <span class="atalho-descricao">Recebidos</span>
                </div>
                <div class="atalho-item">
                    <div class="atalho-tecla"><span class="tecla">Alt</span><span class="tecla">T</span></div>
                    <span class="atalho-descricao">Alternar tema claro/escuro</span>
                </div>
                <div class="atalho-item">
                    <div class="atalho-tecla"><span class="tecla">Alt</span><span class="tecla">+</span></div>
                    <span class="atalho-descricao">Aumentar fonte</span>
                </div>
                <div class="atalho-item">
                    <div class="atalho-tecla"><span class="tecla">Alt</span><span class="tecla">-</span></div>
                    <span class="atalho-descricao">Diminuir fonte</span>
                </div>
                <div class="atalho-item">
                    <div class="atalho-tecla"><span class="tecla">Ctrl</span><span class="tecla">F</span></div>
                    <span class="atalho-descricao">Busca global</span>
                </div>
                <div class="atalho-item">
                    <div class="atalho-tecla"><span class="tecla">Ctrl</span><span class="tecla">S</span></div>
                    <span class="atalho-descricao">Salvar (contexto atual)</span>
                </div>
                <div class="atalho-item">
                    <div class="atalho-tecla"><span class="tecla">Ctrl</span><span class="tecla">B</span></div>
                    <span class="atalho-descricao">Exportar backup</span>
                </div>
                <div class="atalho-item">
                    <div class="atalho-tecla"><span class="tecla">Esc</span></div>
                    <span class="atalho-descricao">Fechar modal</span>
                </div>
            </div>
            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharModalAtalhos()">Fechar</button>
            </div>
        </div>
    </div>

    <!-- Modal Relatório -->
    <div class="modal-overlay" id="modal-relatorio">
        <div class="modal" style="max-width: 400px;">
            <h2>📄 Gerar Relatório</h2>
            
            <div class="form-group">
                <label>Mês</label>
                <input type="month" id="relatorio-mes">
            </div>
            
            <div class="form-group">
                <label>Tipo</label>
                <select id="relatorio-tipo">
                    <option value="completo">Completo (com detalhes)</option>
                    <option value="resumido">Resumido (só totais)</option>
                </select>
            </div>
            
            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharModalRelatorio()">Cancelar</button>
                <button class="btn-salvar-config" onclick="gerarRelatorio()">📄 Gerar PDF</button>
            </div>
        </div>
    </div>

    <!-- Modal Alterar Senha -->
    <div class="modal-overlay" id="modal-alterar-senha">
        <div class="modal" style="max-width: 400px;">
            <h2>🔑 Alterar Senha</h2>
            
            <div class="form-group">
                <label>Senha Atual</label>
                <input type="password" id="senha-atual" placeholder="Digite sua senha atual">
            </div>
            
            <div class="form-group">
                <label>Nova Senha</label>
                <input type="password" id="nova-senha" placeholder="Senha forte" oninput="verificarForcaSenhaModal()">
                <div class="password-requirements-modal">
                    <div id="modal-req-length" class="req-fail">✓ Mínimo 8 caracteres</div>
                    <div id="modal-req-lower" class="req-fail">✓ Letra minúscula</div>
                    <div id="modal-req-upper" class="req-fail">✓ Letra maiúscula</div>
                    <div id="modal-req-number" class="req-fail">✓ Número</div>
                    <div id="modal-req-special" class="req-fail">✓ Caractere especial</div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Confirmar Nova Senha</label>
                <input type="password" id="confirmar-senha" placeholder="Digite novamente a nova senha">
            </div>
            
            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharModalAlterarSenha()">Cancelar</button>
                <button class="btn-salvar-config" onclick="alterarSenha()">🔐 Alterar Senha</button>
            </div>
        </div>
    </div>

    <!-- Modal Recebimento (total ou parcial) -->
    <div class="modal-overlay" id="modal-recebimento">
        <div class="modal" style="max-width: 400px;">
            <h2>✅ Registrar Recebimento</h2>
            
            <p id="recebimento-descricao" style="margin-bottom: 10px; font-size: 0.9rem; color: #cbd5f5;"></p>
            
            <div class="form-group">
                <label>Valor recebido</label>
                <input type="text" id="recebimento-valor" placeholder="Ex: 150,00">
            </div>
            
            <div class="info-box">
                💡 Você pode receber o valor completo ou apenas uma parte. O restante continua na notinha.
            </div>
            
            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharModalRecebimento()">Cancelar</button>
                <button class="btn-salvar-config" onclick="confirmarRecebimento()">💾 Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <!-- JavaScript Modules -->
    <script src="assets/js/state.js"></script>
    <script src="assets/js/traducoes.js"></script>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/empresas.js"></script>
    <script src="assets/js/clientes-notinha.js"></script>
    <script src="assets/js/notinhas.js"></script>
    <script src="assets/js/cobranca.js"></script>
    <script src="assets/js/excluidos.js"></script>
    <script src="assets/js/inadimplentes.js"></script>
    <script src="assets/js/edicao.js"></script>
    <script src="assets/js/configuracoes.js"></script>
    <script src="assets/js/clientes.js"></script>
    <script src="assets/js/promocao.js"></script>
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/historico.js"></script>
    <script src="assets/js/lembretes.js"></script>
    <script src="assets/js/recebidos.js"></script>
    <script src="assets/js/acessibilidade.js"></script>
    <script src="assets/js/backup.js"></script>
    <script src="assets/js/busca-global.js"></script>
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/app.js"></script>
    
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('service-worker.js')
                    .then(registration => {
                        console.log('✅ Service Worker registrado:', registration.scope);
                    })
                    .catch(error => {
                        console.log('❌ Falha ao registrar Service Worker:', error);
                    });
            });
        }
        
        // Prompt de instalação PWA
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Mostra botão de instalar se não estiver instalado
            const btnInstalar = document.getElementById('btn-instalar-app');
            if (btnInstalar) {
                btnInstalar.style.display = 'block';
            }
        });
        
        function instalarApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('✅ App instalado');
                    }
                    deferredPrompt = null;
                });
            }
        }
        
        // Detecta se está rodando como PWA
        if (window.matchMedia('(display-mode: standalone)').matches) {
            console.log('📱 Rodando como PWA instalado');
            document.body.classList.add('pwa-mode');
        }
    </script>
</body>
</html>
