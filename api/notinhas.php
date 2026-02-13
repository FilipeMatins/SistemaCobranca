<?php
/**
 * API de Notinhas
 * Roteador para NotinhaController
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\NotinhaController;
use App\Core\Response;
use App\Core\Auth;

// Verificar se está logado
Auth::verificarLoginAPI();
$usuarioId = Auth::getUsuarioId();

$controller = new NotinhaController($usuarioId);
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? '';
            if ($action === 'clientes_excluidos') {
                $controller->clientesExcluidos();
            } else {
                $controller->index();
            }
            break;
            
        case 'POST':
            $controller->store();
            break;
            
        case 'PUT':
            $controller->update();
            break;
            
        case 'PATCH':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? 'restaurar';
            
            if ($action === 'marcar_enviado') {
                $controller->marcarEnviado();
            } elseif ($action === 'inadimplente') {
                $controller->marcarInadimplente();
            } elseif ($action === 'excluir_cliente') {
                $controller->excluirCliente();
            } elseif ($action === 'restaurar_cliente') {
                $controller->restaurarCliente();
            } elseif ($action === 'excluir_cliente_permanente') {
                $controller->excluirClientePermanente();
            } elseif ($action === 'receber_cliente') {
                $controller->receberCliente();
            } else {
                $controller->restaurar();
            }
            break;
            
        case 'DELETE':
            $controller->destroy();
            break;
            
        default:
            Response::methodNotAllowed();
    }
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}
