<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Notinha;
use App\Models\Empresa;
use App\Models\Cliente;

class NotinhaController extends Controller
{
    private Notinha $model;
    private Empresa $empresaModel;
    private Cliente $clienteModel;
    private ?int $usuarioId;
    
    public function __construct(?int $usuarioId = null)
    {
        $this->usuarioId = $usuarioId;
        $this->model = new Notinha($usuarioId);
        $this->empresaModel = new Empresa($usuarioId);
        $this->clienteModel = new Cliente($usuarioId);
    }
    
    public function index(): void
    {
        $this->model->limparExcluidosAntigos();
        
        $action = $this->getQueryParam('action', '');
        
        if ($action === 'excluidos') {
            $notinhas = $this->model->listarExcluidas();
        } elseif ($action === 'inadimplentes') {
            $notinhas = $this->model->listarInadimplentes();
        } else {
            $notinhas = $this->model->listarAtivas();
        }
        
        Response::json($notinhas);
    }
    
    public function store(): void
    {
        $data = $this->getJsonInput();
        
        $empresaNome = trim($data['empresa'] ?? '');
        $clientes = $data['clientes'] ?? [];
        
        if (empty($empresaNome)) {
            Response::error('Nome da empresa é obrigatório');
        }
        
        if (empty($clientes)) {
            Response::error('Adicione pelo menos um cliente');
        }
        
        $this->model->iniciarTransacao();
        
        try {
            // Busca ou cria empresa
            $empresa = $this->empresaModel->buscarOuCriar($empresaNome);
            
            $notinhasIds = [];
            $temParcelas = false;
            
            // Separa clientes à vista dos parcelados
            $clientesAVista = [];
            $clientesParcelados = [];
            
            foreach ($clientes as $cliente) {
                $nome = trim($cliente['nome'] ?? '');
                if (empty($nome)) continue;
                
                $numParcelas = intval($cliente['parcelas'] ?? 1);
                
                if ($numParcelas > 1) {
                    $clientesParcelados[] = $cliente;
                } else {
                    $clientesAVista[] = $cliente;
                }
            }
            
            // Agrupa clientes à vista por data de cobrança
            $clientesPorData = [];
            foreach ($clientesAVista as $cliente) {
                $dataCobranca = $cliente['datasParcelas'][0] ?? date('Y-m-d');
                if (!isset($clientesPorData[$dataCobranca])) {
                    $clientesPorData[$dataCobranca] = [];
                }
                $clientesPorData[$dataCobranca][] = $cliente;
            }
            
            // Cria uma notinha para cada data (agrupa clientes à vista da mesma data)
            foreach ($clientesPorData as $dataCobranca => $clientesDaData) {
                $notinhaId = $this->model->criar($empresa['id'], $dataCobranca);
                $notinhasIds[] = $notinhaId;
                
                foreach ($clientesDaData as $cliente) {
                    $nome = trim($cliente['nome']);
                    $valor = $this->parseValor($cliente['valor'] ?? '0');
                    $telefone = trim($cliente['telefone'] ?? '');
                    
                    $this->model->adicionarCliente($notinhaId, $nome, $valor, $telefone);
                    $this->clienteModel->salvarOuAtualizar($nome, $telefone);
                }
            }
            
            // Processa clientes parcelados (cada cliente parcelado tem suas próprias notinhas)
            foreach ($clientesParcelados as $cliente) {
                $temParcelas = true;
                
                $nome = trim($cliente['nome']);
                $valor = $this->parseValor($cliente['valor'] ?? '0');
                $telefone = trim($cliente['telefone'] ?? '');
                $numParcelas = intval($cliente['parcelas']);
                $datasParcelas = $cliente['datasParcelas'] ?? [date('Y-m-d')];
                
                $valorParcela = $valor / $numParcelas;
                $parcelaOrigemId = null;
                
                // Salva cliente no cadastro
                $this->clienteModel->salvarOuAtualizar($nome, $telefone);
                
                for ($i = 0; $i < $numParcelas; $i++) {
                    $dataParcela = $datasParcelas[$i] ?? date('Y-m-d');
                    
                    $notinhaId = $this->model->criar($empresa['id'], $dataParcela, $i + 1, $numParcelas, $parcelaOrigemId);
                    
                    if ($i === 0) {
                        $parcelaOrigemId = $notinhaId;
                    }
                    
                    $this->model->adicionarCliente($notinhaId, $nome, $valorParcela, $telefone);
                    $notinhasIds[] = $notinhaId;
                }
            }
            
            $this->model->confirmarTransacao();
            
            $msg = $temParcelas ? 'Notinha salva com parcelas!' : 'Notinha salva com sucesso!';
            Response::created([
                'ids' => $notinhasIds
            ], $msg);
            
        } catch (\Exception $e) {
            $this->model->cancelarTransacao();
            Response::error($e->getMessage(), 500);
        }
    }
    
    private function buscarNotinhaPorEmpresaData(int $empresaId, string $data, array $idsExistentes): ?int
    {
        // Busca uma notinha já criada nesta mesma requisição com mesma empresa e data
        foreach ($idsExistentes as $id) {
            // Como estamos na mesma transação, verificamos as notinhas que criamos
            // Para simplificar, retorna null e sempre cria uma nova
        }
        return null;
    }
    
    public function update(): void
    {
        $data = $this->getJsonInput();
        
        $id = $data['id'] ?? 0;
        $empresaNome = trim($data['empresa'] ?? '');
        $dataCobranca = $data['data_cobranca'] ?? '';
        $clientes = $data['clientes'] ?? [];
        
        if (!$id) {
            Response::error('ID não informado');
        }
        
        if (empty($empresaNome) || empty($dataCobranca) || empty($clientes)) {
            Response::error('Preencha todos os campos');
        }
        
        $this->model->iniciarTransacao();
        
        try {
            // Busca ou cria empresa
            $empresa = $this->empresaModel->buscarOuCriar($empresaNome);
            
            // Atualiza notinha
            $this->model->atualizar($id, $empresa['id'], $dataCobranca);
            
            // Remove clientes antigos e adiciona novos
            $this->model->removerClientes($id);
            
            foreach ($clientes as $cliente) {
                $nome = trim($cliente['nome'] ?? '');
                $valor = $this->parseValor($cliente['valor'] ?? '0');
                $telefone = trim($cliente['telefone'] ?? '');
                
                if (!empty($nome)) {
                    $this->model->adicionarCliente($id, $nome, $valor, $telefone);
                    $this->clienteModel->salvarOuAtualizar($nome, $telefone);
                }
            }
            
            $this->model->confirmarTransacao();
            
            Response::success(null, 'Notinha atualizada!');
            
        } catch (\Exception $e) {
            $this->model->cancelarTransacao();
            Response::error($e->getMessage(), 500);
        }
    }
    
    public function marcarEnviado(): void
    {
        $data = $this->getJsonInput();
        $clienteIds = $data['cliente_ids'] ?? [];
        
        if (empty($clienteIds)) {
            Response::error('IDs não informados');
        }
        
        $this->model->marcarClientesEnviados($clienteIds);
        Response::success(null, 'Marcado como enviado!');
    }
    
    public function restaurar(): void
    {
        $data = $this->getJsonInput();
        $id = $data['id'] ?? 0;
        
        if (!$id) {
            Response::error('ID não informado');
        }
        
        if ($this->model->restaurar($id)) {
            Response::success(null, 'Notinha restaurada!');
        } else {
            Response::notFound('Notinha não encontrada');
        }
    }
    
    public function marcarInadimplente(): void
    {
        $data = $this->getJsonInput();
        $id = $data['id'] ?? 0;
        
        if (!$id) {
            Response::error('ID não informado');
        }
        
        if ($this->model->moverParaInadimplentes($id)) {
            Response::success(null, 'Notinha marcada como inadimplente!');
        } else {
            Response::notFound('Notinha não encontrada');
        }
    }
    
    public function excluirCliente(): void
    {
        $data = $this->getJsonInput();
        $clienteId = $data['cliente_id'] ?? 0;
        
        if (!$clienteId) {
            Response::error('ID do cliente não informado');
        }
        
        if ($this->model->excluirCliente($clienteId)) {
            Response::success(null, 'Cliente removido da notinha!');
        } else {
            Response::notFound('Cliente não encontrado');
        }
    }
    
    public function restaurarCliente(): void
    {
        $data = $this->getJsonInput();
        $clienteId = $data['cliente_id'] ?? 0;
        
        if (!$clienteId) {
            Response::error('ID do cliente não informado');
        }
        
        if ($this->model->restaurarCliente($clienteId)) {
            Response::success(null, 'Cliente restaurado!');
        } else {
            Response::notFound('Cliente não encontrado');
        }
    }
    
    public function clientesExcluidos(): void
    {
        $notinhaId = intval($this->getQueryParam('notinha_id', 0));
        
        if ($notinhaId) {
            // Busca clientes excluídos de uma notinha específica
            $clientes = $this->model->buscarClientesExcluidos($notinhaId);
        } else {
            // Busca todos os clientes excluídos
            $clientes = $this->model->listarTodosClientesExcluidos();
        }
        
        Response::json($clientes);
    }
    
    public function excluirClientePermanente(): void
    {
        $data = $this->getJsonInput();
        $clienteId = $data['cliente_id'] ?? 0;
        
        if (!$clienteId) {
            Response::error('ID do cliente não informado');
        }
        
        if ($this->model->excluirClientePermanente($clienteId)) {
            Response::success(null, 'Cliente excluído permanentemente!');
        } else {
            Response::notFound('Cliente não encontrado');
        }
    }
    
    public function receberCliente(): void
    {
        $data = $this->getJsonInput();
        $clienteId = $data['cliente_id'] ?? 0;
        
        if (!$clienteId) {
            Response::error('ID do cliente não informado');
        }
        
        // Busca a notinha do cliente
        $notinhaId = $this->model->buscarNotinhaIdDoCliente($clienteId);
        
        if (!$notinhaId) {
            Response::notFound('Cliente não encontrado');
            return;
        }
        
        // Marca o cliente como recebido (soft delete com flag especial)
        if ($this->model->marcarClienteRecebido($clienteId)) {
            // Verifica se ainda existem clientes ativos na notinha
            $clientesAtivos = $this->model->contarClientesAtivos($notinhaId);
            
            if ($clientesAtivos === 0) {
                // Todos os clientes foram recebidos, marca a notinha toda como recebida
                $this->model->marcarComoRecebido($notinhaId);
            }
            
            Response::success(['notinha_recebida' => $clientesAtivos === 0], 'Cliente marcado como recebido!');
        } else {
            Response::error('Erro ao marcar como recebido');
        }
    }
    
    public function destroy(): void
    {
        $id = intval($this->getQueryParam('id', 0));
        $permanent = $this->getQueryParam('permanent', '0');
        
        if (!$id) {
            Response::error('ID não informado');
        }
        
        if ($permanent === '1') {
            $sucesso = $this->model->excluirPermanente($id);
            $mensagem = 'Notinha excluída permanentemente';
        } else {
            $sucesso = $this->model->moverParaLixeira($id);
            $mensagem = 'Notinha movida para lixeira';
        }
        
        if ($sucesso) {
            Response::success(null, $mensagem);
        } else {
            Response::notFound('Notinha não encontrada');
        }
    }
    
    private function parseValor($valor): float
    {
        return floatval(str_replace([',', 'R$', ' '], ['.', '', ''], $valor));
    }
}

