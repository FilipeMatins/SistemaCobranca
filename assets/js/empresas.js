// ==================== EMPRESAS ====================
async function buscarEmpresas() {
    const termo = document.getElementById('empresa').value;
    try {
        const response = await fetch(`api/empresas.php?termo=${encodeURIComponent(termo)}`);
        const empresas = await response.json();
        mostrarAutocompleteEmpresa(empresas);
    } catch (error) {
        console.error('Erro:', error);
    }
}

function mostrarAutocompleteEmpresa(empresas) {
    const lista = document.getElementById('autocomplete-empresa');
    if (empresas.length === 0) {
        lista.classList.remove('show');
        return;
    }
    lista.innerHTML = empresas.map(e => `
        <div class="autocomplete-item" onclick="selecionarEmpresa('${e.nome.replace(/'/g, "\\'")}')">
            ${e.nome}
        </div>
    `).join('');
    lista.classList.add('show');
}

function selecionarEmpresa(nome) {
    document.getElementById('empresa').value = nome;
    document.getElementById('autocomplete-empresa').classList.remove('show');
}

