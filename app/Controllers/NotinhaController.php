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
    
    public function __construct()
    {
        $this->model = new Notinha();
        $this->empresaModel = new Empresa();
        $this->clienteModel = new Cliente();
    }
    
    public function index(): void
    {
        $this->model->limparExcluidosAntigos();
        
        $action = $this->getQueryParam('action', '');
        
        if ($action === 'excluidos') {
            $notinhas = $this->model->listarExcluidas();
        } else {
            $notinhas = $this->model->listarAtivas();
        }
        
        Response::json($notinhas);
    }
    
    public function store(): void
    {
        $data = $this->getJsonInput();
        
        $empresaNome = trim($data['empresa'] ?? '');
        $dataCobranca = $data['data_cobranca'] ?? '';
        $clientes = $data['clientes'] ?? [];
        
        if (empty($empresaNome)) {
            Response::error('Nome da empresa é obrigatório');
        }
        
        if (empty($dataCobranca)) {
            Response::error('Data da cobrança é obrigatória');
        }
        
        if (empty($clientes)) {
            Response::error('Adicione pelo menos um cliente');
        }
        
        $this->model->iniciarTransacao();
        
        try {
            // Busca ou cria empresa
            $empresa = $this->empresaModel->buscarOuCriar($empresaNome);
            
            // Cria notinha
            $notinhaId = $this->model->criar($empresa['id'], $dataCobranca);
            
            // Adiciona clientes
            foreach ($clientes as $cliente) {
                $nome = trim($cliente['nome'] ?? '');
                $valor = $this->parseValor($cliente['valor'] ?? '0');
                $telefone = trim($cliente['telefone'] ?? '');
                
                if (!empty($nome)) {
                    $this->model->adicionarCliente($notinhaId, $nome, $valor, $telefone);
                    $this->clienteModel->salvarOuAtualizar($nome, $telefone);
                }
            }
            
            $this->model->confirmarTransacao();
            
            Response::created([
                'id' => $notinhaId
            ], 'Notinha salva com sucesso!');
            
        } catch (\Exception $e) {
            $this->model->cancelarTransacao();
            Response::error($e->getMessage(), 500);
        }
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

