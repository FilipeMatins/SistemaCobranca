<?php
/**
 * API de Configurações
 * Roteador para ConfiguracaoController
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../app/autoload.php';

use App\Controllers\ConfiguracaoController;
use App\Core\Response;

$controller = new ConfiguracaoController();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            $controller->index();
            break;
            
        case 'POST':
        case 'PUT':
            $controller->store();
            break;
            
        default:
            Response::methodNotAllowed();
    }
} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}
