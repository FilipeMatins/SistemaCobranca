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

$controller = new NotinhaController();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $controller->index();
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
