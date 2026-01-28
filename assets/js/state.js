// ==================== ESTADO GLOBAL ====================
let clientes = [];
let configuracoes = {};
let todasNotinhas = [];
let notinhasExcluidas = [];
let notinhasInadimplentes = [];
let clientesEdicao = [];
let clientesExcluidosGlobal = [];
let todosClientes = [];
let clientesFiltrados = [];
let paginaAtualClientes = 1;
const clientesPorPagina = 20;

// Cobrança em lote
let filaCobranca = [];
let indiceAtual = 0;
let modoReenvio = false;

// Edição
let notinhaIdEdicao = null;
let notinhaEdicaoAtual = null;

// Promoções
let clientesSelecionados = [];
let filaPromocao = [];
let indicePromocao = 0;
let mensagemPromocao = '';

