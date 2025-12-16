<?php
/**
 * API de Clientes
 * Roteador para ClienteController
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\ClienteController;
use App\Core\Response;

$controller = new ClienteController();
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
            
        case 'DELETE':
            $controller->destroy();
            break;
            
        default:
            Response::methodNotAllowed();
    }
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}
