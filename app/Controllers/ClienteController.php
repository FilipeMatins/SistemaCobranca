<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Cliente;

class ClienteController extends Controller
{
    private Cliente $model;
    
    public function __construct()
    {
        $this->model = new Cliente();
    }
    
    public function index(): void
    {
        $termo = $this->getQueryParam('termo', '');
        $clientes = $this->model->buscar($termo);
        Response::json($clientes);
    }
    
    public function store(): void
    {
        $data = $this->getJsonInput();
        $nome = trim($data['nome'] ?? '');
        $telefone = trim($data['telefone'] ?? '');
        
        if (empty($nome)) {
            Response::error('Nome é obrigatório');
        }
        
        // Verifica se nome já existe
        if ($this->model->buscarPorNome($nome)) {
            Response::error('Cliente já cadastrado com este nome');
        }
        
        // Verifica se telefone já existe
        if (!empty($telefone)) {
            $clienteExistente = $this->model->buscarPorTelefone($telefone);
            if ($clienteExistente) {
                Response::error('Este telefone já está cadastrado para: ' . $clienteExistente['nome']);
            }
        }
        
        $cliente = $this->model->criar($nome, $telefone);
        Response::created($cliente, 'Cliente criado com sucesso');
    }
    
    public function update(): void
    {
        $data = $this->getJsonInput();
        $id = intval($data['id'] ?? 0);
        $nome = trim($data['nome'] ?? '');
        $telefone = trim($data['telefone'] ?? '');
        
        if (!$id || empty($nome)) {
            Response::error('ID e nome são obrigatórios');
        }
        
        // Verifica se nome já existe em outro cliente
        $clienteComNome = $this->model->buscarPorNome($nome);
        if ($clienteComNome && $clienteComNome['id'] != $id) {
            Response::error('Já existe outro cliente com este nome');
        }
        
        // Verifica se telefone já existe em outro cliente
        if (!empty($telefone)) {
            $clienteExistente = $this->model->buscarPorTelefone($telefone, $id);
            if ($clienteExistente) {
                Response::error('Este telefone já está cadastrado para: ' . $clienteExistente['nome']);
            }
        }
        
        $this->model->atualizar($id, $nome, $telefone);
        Response::success(null, 'Cliente atualizado com sucesso');
    }
    
    public function destroy(): void
    {
        $id = intval($this->getQueryParam('id', 0));
        
        if (!$id) {
            Response::error('ID é obrigatório');
        }
        
        $this->model->excluir($id);
        Response::success(null, 'Cliente excluído com sucesso');
    }
}


