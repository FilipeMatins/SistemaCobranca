// ==================== ACESSIBILIDADE ====================

// Níveis de fonte
const niveisFont = ['fonte-pequena', 'fonte-normal', 'fonte-grande', 'fonte-muito-grande'];
let nivelFonteAtual = 1; // Começa no normal

// Inicializar acessibilidade
function inicializarAcessibilidade() {
    // Carregar preferências salvas
    const fonteSalva = localStorage.getItem('nivelFonte');
    const modoClaro = localStorage.getItem('modoClaro') === 'true';
    
    if (fonteSalva !== null) {
        nivelFonteAtual = parseInt(fonteSalva);
        aplicarNivelFonte();
    }
    
    if (modoClaro) {
        document.documentElement.classList.add('modo-claro');
        atualizarBotaoTema(true);
    }
    
    // Registrar atalhos de teclado
    registrarAtalhosTeclado();
    
    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.acessibilidade-selector')) {
            const dropdown = document.getElementById('acessibilidade-dropdown');
            if (dropdown) dropdown.classList.remove('show');
        }
    });
}

// Toggle dropdown de acessibilidade
function toggleAcessibilidadeDropdown() {
    const dropdown = document.getElementById('acessibilidade-dropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
        // Fechar dropdown de idioma se estiver aberto
        const idiomaDropdown = document.getElementById('idioma-dropdown');
        if (idiomaDropdown) idiomaDropdown.classList.remove('show');
    }
}

// Aumentar fonte
function aumentarFonte() {
    if (nivelFonteAtual < niveisFont.length - 1) {
        nivelFonteAtual++;
        aplicarNivelFonte();
        localStorage.setItem('nivelFonte', nivelFonteAtual);
        showToast(`Fonte: ${getNomeFonte()}`);
    }
}

// Diminuir fonte
function diminuirFonte() {
    if (nivelFonteAtual > 0) {
        nivelFonteAtual--;
        aplicarNivelFonte();
        localStorage.setItem('nivelFonte', nivelFonteAtual);
        showToast(`Fonte: ${getNomeFonte()}`);
    }
}

// Aplicar nível de fonte
function aplicarNivelFonte() {
    niveisFont.forEach(nivel => document.documentElement.classList.remove(nivel));
    document.documentElement.classList.add(niveisFont[nivelFonteAtual]);
}

// Nome do nível de fonte
function getNomeFonte() {
    const nomes = ['Pequena', 'Normal', 'Grande', 'Muito Grande'];
    return nomes[nivelFonteAtual];
}

// Toggle Modo Claro/Escuro
function toggleModoTema() {
    const modoClaro = document.documentElement.classList.toggle('modo-claro');
    localStorage.setItem('modoClaro', modoClaro);
    atualizarBotaoTema(modoClaro);
    showToast(modoClaro ? '☀️ Modo claro' : '🌙 Modo escuro');
}

function atualizarBotaoTema(modoClaro) {
    const btn = document.getElementById('btn-tema');
    if (btn) {
        btn.innerHTML = modoClaro ? '🌙 Escuro' : '☀️ Claro';
    }
}

// Atalhos de Teclado
function registrarAtalhosTeclado() {
    document.addEventListener('keydown', (e) => {
        // Ignorar se estiver em input/textarea
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            return;
        }
        
        // Alt + tecla
        if (e.altKey) {
            switch (e.key.toLowerCase()) {
                case 'n': // Nova notinha
                    e.preventDefault();
                    document.querySelector('[data-tab="nova"]')?.click();
                    break;
                case 'l': // Lista de notinhas
                    e.preventDefault();
                    document.querySelector('[data-tab="lista"]')?.click();
                    break;
                case 'd': // Dashboard
                    e.preventDefault();
                    document.querySelector('[data-tab="dashboard"]')?.click();
                    break;
                case 'c': // Clientes
                    e.preventDefault();
                    document.querySelector('[data-tab="clientes"]')?.click();
                    break;
                case 'r': // Recebidos
                    e.preventDefault();
                    document.querySelector('[data-tab="recebidos"]')?.click();
                    break;
                case '+': // Aumentar fonte
                case '=':
                    e.preventDefault();
                    aumentarFonte();
                    break;
                case '-': // Diminuir fonte
                    e.preventDefault();
                    diminuirFonte();
                    break;
                case 't': // Toggle tema
                    e.preventDefault();
                    toggleModoTema();
                    break;
                case 'h': // Ajuda/Atalhos
                    e.preventDefault();
                    abrirModalAtalhos();
                    break;
            }
        }
        
        // Ctrl + tecla
        if (e.ctrlKey) {
            switch (e.key.toLowerCase()) {
                case 's': // Salvar
                    e.preventDefault();
                    // Tenta salvar no contexto atual
                    if (document.getElementById('tab-nova').style.display !== 'none') {
                        salvarNotinha();
                    } else if (document.getElementById('modal-editar').classList.contains('show')) {
                        salvarEdicao();
                    } else if (document.getElementById('modal-config').classList.contains('show')) {
                        salvarConfiguracoes();
                    }
                    break;
                case 'b': // Backup
                    e.preventDefault();
                    exportarDados();
                    break;
                case 'f': // Busca global
                    e.preventDefault();
                    abrirBuscaGlobal();
                    break;
            }
        }
    });
}

// Modal de Atalhos
function abrirModalAtalhos() {
    const modal = document.getElementById('modal-atalhos');
    if (modal) {
        modal.classList.add('show');
    }
}

function fecharModalAtalhos() {
    const modal = document.getElementById('modal-atalhos');
    if (modal) {
        modal.classList.remove('show');
    }
}

// Inicializar quando DOM carregar
document.addEventListener('DOMContentLoaded', inicializarAcessibilidade);

