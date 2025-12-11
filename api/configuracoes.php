<?php
// API de Configurações
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getConnection();

    switch ($method) {
        case 'GET':
            // Buscar todas as configurações
            $stmt = $pdo->query("SELECT chave, valor FROM configuracoes");
            $configs = [];
            
            while ($row = $stmt->fetch()) {
                $configs[$row['chave']] = $row['valor'];
            }

            jsonResponse($configs);
            break;

        case 'POST':
        case 'PUT':
            // Atualizar configurações
            $data = json_decode(file_get_contents('php://input'), true);

            $stmt = $pdo->prepare("
                INSERT INTO configuracoes (chave, valor) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)
            ");

            foreach ($data as $chave => $valor) {
                $stmt->execute([$chave, $valor]);
            }

            jsonResponse(['success' => true, 'message' => 'Configurações salvas!']);
            break;

        default:
            jsonResponse(['error' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}

