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
    
    <!-- Styles -->
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📝 Bloco de Cobranças</h1>
            <button class="btn-config" onclick="abrirConfiguracoes()">
                ⚙️ Configurações
            </button>
        </div>

        <!-- Alertas -->
        <div class="alertas">
            <div class="alerta alerta-hoje" onclick="filtrarPorStatus('hoje')">
                <span class="alerta-icon">📅</span>
                <div class="alerta-info">
                    <h3>Vencem Hoje</h3>
                    <span class="numero" id="contador-hoje">0</span>
                </div>
                <button class="btn-cobrar-todos" id="btn-cobrar-hoje" onclick="event.stopPropagation(); cobrarTodosHoje()">
                    💬 Cobrar Todos
                </button>
            </div>
            <div class="alerta alerta-atrasado" onclick="filtrarPorStatus('atrasado')">
                <span class="alerta-icon">⚠️</span>
                <div class="alerta-info">
                    <h3>Atrasadas</h3>
                    <span class="numero" id="contador-atrasado">0</span>
                </div>
                <button class="btn-cobrar-todos" id="btn-cobrar-atrasadas" onclick="event.stopPropagation(); cobrarTodasAtrasadas()">
                    💬 Cobrar Todos
                </button>
            </div>
            <div class="alerta alerta-total">
                <span class="alerta-icon">💰</span>
                <div class="alerta-info">
                    <h3>Total Geral</h3>
                    <span class="numero" id="total-geral">R$ 0,00</span>
                </div>
            </div>
        </div>

        <!-- Banner de Notificação -->
        <div class="banner-notificacao" id="banner-notificacao" style="display: none;">
            <div class="banner-content">
                <span class="banner-icon">🔔</span>
                <div class="banner-texto">
                    <strong>Você tem cobranças para hoje!</strong>
                    <p id="banner-detalhes"></p>
                </div>
                <button class="btn-cobrar-banner" onclick="cobrarTodosHoje()">💬 Cobrar Agora</button>
                <button class="btn-fechar-banner" onclick="fecharBanner()">✕</button>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab active" data-tab="nova">➕ Nova</button>
            <button class="tab" data-tab="lista">📋 Notinhas</button>
            <button class="tab" data-tab="clientes">👥 Clientes <span class="badge" id="badge-clientes"></span></button>
            <button class="tab" data-tab="excluidos">🗑️ Excluídos <span class="badge" id="badge-excluidos"></span></button>
        </div>

        <!-- Nova Notinha -->
        <div id="tab-nova" class="tab-content">
            <div class="form-card">
                <h2>✏️ Nova Notinha</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label>Empresa (onde vendeu)</label>
                        <input type="text" id="empresa" placeholder="Digite o nome..." autocomplete="off">
                        <div class="autocomplete-list" id="autocomplete-empresa"></div>
                    </div>
                    <div class="form-group">
                        <label>Data da Cobrança</label>
                        <input type="date" id="data-cobranca">
                    </div>
                </div>

                <div class="clientes-table">
                    <div class="clientes-table-header">
                        <span>Nome do Cliente</span>
                        <span>Valor</span>
                        <span>Telefone</span>
                        <span></span>
                    </div>
                    <div id="clientes-lista"></div>
                </div>

                <button class="btn-add" onclick="adicionarCliente()">
                    + Adicionar Cliente
                </button>

                <br>
                <button class="btn-salvar" onclick="salvarNotinha()">
                    💾 Salvar Notinha
                </button>
            </div>
        </div>

        <!-- Lista de Notinhas -->
        <div id="tab-lista" class="tab-content" style="display: none;">
            <!-- Filtros -->
            <div class="filtros">
                <input type="text" class="filtro-busca" id="filtro-busca" placeholder="🔍 Buscar empresa ou cliente...">
                <select class="filtro-status" id="filtro-status">
                    <option value="">Todos os status</option>
                    <option value="hoje">Vencem hoje</option>
                    <option value="atrasado">Atrasadas</option>
                    <option value="futuro">Futuras</option>
                </select>
                <input type="date" class="filtro-data" id="filtro-data" title="Filtrar por data">
                <button class="btn-limpar-filtro" onclick="limparFiltros()">✕ Limpar</button>
            </div>

            <!-- Tabela -->
            <div class="notinhas-table" id="notinhas-container">
                <div class="notinhas-header">
                    <span>Empresa</span>
                    <span>Clientes</span>
                    <span>Data</span>
                    <span>Total</span>
                    <span>Status</span>
                    <span>Ações</span>
                </div>
                <div id="notinhas-lista"></div>
            </div>

            <!-- Total Filtrado -->
            <div class="total-filtrado" id="total-filtrado">
                <span>Total exibido: <strong id="valor-filtrado">R$ 0,00</strong></span>
            </div>
        </div>

        <!-- Clientes -->
        <div id="tab-clientes" class="tab-content" style="display: none;">
            <div class="clientes-header-section">
                <div class="clientes-acoes">
                    <button class="btn-novo-cliente" onclick="abrirModalCliente()">
                        ➕ Novo Cliente
                    </button>
                    <button class="btn-promocao" onclick="abrirModalPromocao()">
                        📢 Enviar Promoção
                    </button>
                </div>
                <input type="text" class="filtro-busca" id="filtro-clientes" placeholder="🔍 Buscar cliente..." oninput="filtrarClientes()">
            </div>

            <div class="clientes-lista" id="clientes-cadastrados-container">
                <div class="clientes-lista-header">
                    <span>Nome</span>
                    <span>Telefone</span>
                    <span>Ações</span>
                </div>
                <div id="clientes-cadastrados-lista">
                    <!-- Lista de clientes será carregada aqui -->
                </div>
            </div>
            
            <div class="paginacao" id="paginacao-clientes">
                <!-- Paginação será carregada aqui -->
            </div>
        </div>

        <!-- Excluídos -->
        <div id="tab-excluidos" class="tab-content" style="display: none;">
            <div class="info-lixeira">
                <span>🗑️</span>
                <p>Notinhas excluídas ficam aqui por <strong>15 dias</strong> antes de serem removidas permanentemente.</p>
            </div>

            <div class="notinhas-table">
                <div class="notinhas-header excluidos-header">
                    <span>Empresa</span>
                    <span>Clientes</span>
                    <span>Total</span>
                    <span>Dias Restantes</span>
                    <span>Ações</span>
                </div>
                <div id="excluidos-lista"></div>
            </div>
        </div>
    </div>

    <!-- Modal Configurações -->
    <div class="modal-overlay" id="modal-config">
        <div class="modal" style="max-width: 550px;">
            <h2>⚙️ Configurações</h2>
            
            <div class="form-group">
                <label>Chave PIX</label>
                <input type="text" id="config-pix" placeholder="Sua chave PIX">
            </div>

            <div class="form-group">
                <label>Nome do Vendedor</label>
                <input type="text" id="config-nome" placeholder="Ex: Filipe que vende requeijão e doces">
            </div>

            <div class="form-group">
                <label>Mensagem de Cobrança</label>
                <textarea id="config-mensagem" rows="4" placeholder="Digite a mensagem..."></textarea>
            </div>

            <div class="info-box">
                💡 <strong>Variáveis disponíveis:</strong><br>
                <code>{nome}</code> = Primeiro nome do cliente<br>
                <code>{vendedor}</code> = Seu nome<br>
                <code>{valor}</code> = Valor da cobrança<br>
                <code>{pix}</code> = Sua chave PIX
            </div>

            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharConfiguracoes()">Cancelar</button>
                <button class="btn-salvar-config" onclick="salvarConfiguracoes()">💾 Salvar</button>
            </div>
        </div>
    </div>

    <!-- Modal Editar Notinha -->
    <div class="modal-overlay" id="modal-editar">
        <div class="modal" style="max-width: 600px;">
            <h2>✏️ Editar Notinha</h2>
            
            <input type="hidden" id="editar-id">
            
            <div class="form-row">
                <div class="form-group">
                    <label>Empresa</label>
                    <input type="text" id="editar-empresa" placeholder="Nome da empresa">
                </div>
                <div class="form-group">
                    <label>Data da Cobrança</label>
                    <input type="date" id="editar-data">
                </div>
            </div>

            <div class="clientes-table">
                <div class="clientes-table-header">
                    <span>Nome</span>
                    <span>Valor</span>
                    <span>Telefone</span>
                    <span></span>
                </div>
                <div id="editar-clientes-lista"></div>
            </div>

            <button class="btn-add" onclick="adicionarClienteEdicao()" style="margin: 10px 0;">
                + Adicionar Cliente
            </button>

            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharEdicao()">Cancelar</button>
                <button class="btn-salvar-config" onclick="salvarEdicao()">💾 Salvar</button>
            </div>
        </div>
    </div>

    <!-- Modal Cobrança em Lote -->
    <div class="modal-overlay" id="modal-cobranca">
        <div class="modal modal-cobranca">
            <div class="cobranca-header">
                <h2 id="titulo-modal-cobranca">💬 Enviar Cobranças</h2>
                <span class="cobranca-progresso" id="cobranca-progresso">1 de 5</span>
            </div>
            
            <div class="cobranca-barra-container">
                <div class="cobranca-barra" id="cobranca-barra"></div>
            </div>

            <div class="cobranca-cliente">
                <div class="cobranca-info">
                    <span class="cobranca-label">Cliente</span>
                    <span class="cobranca-nome" id="cobranca-nome">Maria Silva</span>
                    <span class="badge-reenvio" id="badge-reenvio" style="display: none;">🔄 Reenvio</span>
                </div>
                <div class="cobranca-info">
                    <span class="cobranca-label">Valor</span>
                    <span class="cobranca-valor" id="cobranca-valor">R$ 50,00</span>
                </div>
                <div class="cobranca-info">
                    <span class="cobranca-label">Telefone</span>
                    <span class="cobranca-telefone" id="cobranca-telefone">(67) 99999-9999</span>
                </div>
                <div class="cobranca-info">
                    <span class="cobranca-label">Empresa</span>
                    <span class="cobranca-empresa" id="cobranca-empresa">Loja X</span>
                </div>
            </div>

            <div class="cobranca-instrucao">
                👆 Clique em <strong>Enviar</strong> → WhatsApp abre → Aperte <strong>Enter</strong> → Volte aqui
            </div>

            <div class="cobranca-botoes">
                <button class="btn-pular" onclick="pularCobrancaAtual()">Pular</button>
                <button class="btn-enviar-cobranca" id="btn-enviar" onclick="enviarCobrancaAtual()">
                    💬 Enviar no WhatsApp
                </button>
                <button class="btn-proximo" id="btn-proximo" onclick="proximoCliente()" style="display: none;">
                    ✓ Enviado! Próximo →
                </button>
            </div>

            <button class="btn-fechar-cobranca" onclick="fecharModalCobranca()">✕ Fechar</button>
        </div>
    </div>

    <!-- Modal Novo/Editar Cliente -->
    <div class="modal-overlay" id="modal-cliente">
        <div class="modal" style="max-width: 400px;">
            <h2 id="titulo-modal-cliente">➕ Novo Cliente</h2>
            
            <input type="hidden" id="cliente-id">
            
            <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" id="cliente-nome" placeholder="Nome e sobrenome">
            </div>

            <div class="form-group">
                <label>Telefone (WhatsApp)</label>
                <input type="text" id="cliente-telefone" placeholder="67999999999">
            </div>

            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharModalCliente()">Cancelar</button>
                <button class="btn-salvar-config" onclick="salvarCliente()">💾 Salvar</button>
            </div>
        </div>
    </div>

    <!-- Modal Promoção -->
    <div class="modal-overlay" id="modal-promocao">
        <div class="modal" style="max-width: 550px;">
            <h2>📢 Enviar Promoção</h2>
            
            <div class="form-group">
                <label>Mensagem da Promoção</label>
                <textarea id="promocao-mensagem" rows="5" placeholder="Digite a mensagem da promoção..."></textarea>
            </div>

            <div class="info-box">
                💡 <strong>Variável disponível:</strong><br>
                <code>{nome}</code> = Primeiro nome do cliente
            </div>

            <div class="promocao-seletor">
                <label class="checkbox-container">
                    <input type="checkbox" id="selecionar-todos-clientes" onchange="toggleSelecionarTodosClientes()">
                    <span class="checkmark"></span>
                    Selecionar todos (<span id="total-clientes-selecionados">0</span> clientes)
                </label>
            </div>

            <div class="promocao-lista" id="promocao-lista-clientes">
                <!-- Lista de clientes com checkbox -->
            </div>

            <div class="modal-buttons">
                <button class="btn-cancelar" onclick="fecharModalPromocao()">Cancelar</button>
                <button class="btn-enviar-promocao" onclick="iniciarEnvioPromocao()">
                    📢 Enviar para Selecionados
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Envio Promoção -->
    <div class="modal-overlay" id="modal-envio-promocao">
        <div class="modal modal-cobranca">
            <div class="cobranca-header">
                <h2>📢 Enviando Promoção</h2>
                <span class="cobranca-progresso" id="promocao-progresso">1 de 5</span>
            </div>
            
            <div class="cobranca-barra-container">
                <div class="cobranca-barra" id="promocao-barra"></div>
            </div>

            <div class="cobranca-cliente">
                <div class="cobranca-info">
                    <span class="cobranca-label">Cliente</span>
                    <span class="cobranca-nome" id="promocao-nome">Maria Silva</span>
                </div>
                <div class="cobranca-info">
                    <span class="cobranca-label">Telefone</span>
                    <span class="cobranca-telefone" id="promocao-telefone">(67) 99999-9999</span>
                </div>
            </div>

            <div class="cobranca-instrucao">
                👆 Clique em <strong>Enviar</strong> → WhatsApp abre → Aperte <strong>Enter</strong> → Volte aqui
            </div>

            <div class="cobranca-botoes">
                <button class="btn-pular" onclick="pularPromocaoAtual()">Pular</button>
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

    <!-- Toast -->
    <div class="toast" id="toast"></div>

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
