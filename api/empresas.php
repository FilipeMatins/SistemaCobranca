<?php
// API de Empresas
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getConnection();

    switch ($method) {
        case 'GET':
            // Buscar empresas (autocomplete)
            $termo = $_GET['termo'] ?? '';
            
            if ($termo) {
                $stmt = $pdo->prepare("SELECT id, nome FROM empresas WHERE nome LIKE ? ORDER BY nome LIMIT 10");
                $stmt->execute(["%$termo%"]);
            } else {
                $stmt = $pdo->query("SELECT id, nome FROM empresas ORDER BY nome");
            }
            
            jsonResponse($stmt->fetchAll());
            break;

        case 'POST':
            // Criar ou buscar empresa existente
            $data = json_decode(file_get_contents('php://input'), true);
            $nome = trim($data['nome'] ?? '');

            if (empty($nome)) {
                jsonResponse(['error' => 'Nome da empresa é obrigatório'], 400);
            }

            // Verifica se já existe
            $stmt = $pdo->prepare("SELECT id, nome FROM empresas WHERE nome = ?");
            $stmt->execute([$nome]);
            $empresa = $stmt->fetch();

            if ($empresa) {
                jsonResponse($empresa);
            }

            // Cria nova empresa
            $stmt = $pdo->prepare("INSERT INTO empresas (nome) VALUES (?)");
            $stmt->execute([$nome]);
            
            jsonResponse([
                'id' => $pdo->lastInsertId(),
                'nome' => $nome
            ], 201);
            break;

        default:
            jsonResponse(['error' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
