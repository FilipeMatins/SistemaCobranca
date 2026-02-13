<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Empresa;

class EmpresaController extends Controller
{
    private Empresa $model;
    private ?int $usuarioId;
    
    public function __construct(?int $usuarioId = null)
    {
        $this->usuarioId = $usuarioId;
        $this->model = new Empresa($usuarioId);
    }
    
    public function index(): void
    {
        $termo = $this->getQueryParam('termo', '');
        $empresas = $this->model->buscar($termo);
        Response::json($empresas);
    }
    
    public function store(): void
    {
        $data = $this->getJsonInput();
        $nome = trim($data['nome'] ?? '');
        
        if (empty($nome)) {
            Response::error('Nome da empresa é obrigatório');
        }
        
        $empresa = $this->model->buscarOuCriar($nome);
        Response::json($empresa, isset($empresa['created']) ? 201 : 200);
    }
}


