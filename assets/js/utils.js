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
    const locale = window.formatarDataLocale || 'pt-BR';
    return new Date(data + 'T00:00:00').toLocaleDateString(locale);
}

function formatarValor(valor) {
    if (idiomaAtual === 'en') {
        return '$ ' + parseFloat(valor).toFixed(2);
    }
    return 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',');
}

function formatarTelefone(tel) {
    const numeros = tel.replace(/\D/g, '');
    if (numeros.length === 11) {
        return `(${numeros.slice(0,2)}) ${numeros.slice(2,7)}-${numeros.slice(7)}`;
    }
    return tel;
}

function getStatus(data) {
    const hoje = getHojeLocal();
    if (data === hoje) return { classe: 'status-hoje', texto: t('hoje') };
    if (data < hoje) return { classe: 'status-atrasado', texto: t('atrasado') };
    return { classe: 'status-futuro', texto: t('agendado') };
}

