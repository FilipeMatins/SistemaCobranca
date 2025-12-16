<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Configuracao;

class ConfiguracaoController extends Controller
{
    private Configuracao $model;
    
    public function __construct()
    {
        $this->model = new Configuracao();
    }
    
    public function index(): void
    {
        $configs = $this->model->buscarTodas();
        Response::json($configs);
    }
    
    public function store(): void
    {
        $data = $this->getJsonInput();
        $this->model->salvarVarias($data);
        Response::success(null, 'Configurações salvas!');
    }
}

