<?php
// API de Clientes Cadastrados
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
            // Buscar clientes (autocomplete)
            $termo = $_GET['termo'] ?? '';
            
            if ($termo) {
                $stmt = $pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE nome LIKE ? ORDER BY nome LIMIT 10");
                $stmt->execute(["%$termo%"]);
            } else {
                $stmt = $pdo->query("SELECT id, nome, telefone FROM clientes ORDER BY nome LIMIT 50");
            }
            
            jsonResponse($stmt->fetchAll());
            break;

        case 'POST':
            // Criar ou atualizar cliente
            $data = json_decode(file_get_contents('php://input'), true);
            $nome = trim($data['nome'] ?? '');
            $telefone = trim($data['telefone'] ?? '');

            if (empty($nome)) {
                jsonResponse(['error' => 'Nome é obrigatório'], 400);
            }

            // Verifica se já existe
            $stmt = $pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE nome = ?");
            $stmt->execute([$nome]);
            $cliente = $stmt->fetch();

            if ($cliente) {
                // Atualiza telefone se mudou
                if ($telefone && $telefone !== $cliente['telefone']) {
                    $stmt = $pdo->prepare("UPDATE clientes SET telefone = ? WHERE id = ?");
                    $stmt->execute([$telefone, $cliente['id']]);
                    $cliente['telefone'] = $telefone;
                }
                jsonResponse($cliente);
            }

            // Cria novo cliente
            $stmt = $pdo->prepare("INSERT INTO clientes (nome, telefone) VALUES (?, ?)");
            $stmt->execute([$nome, $telefone]);
            
            jsonResponse([
                'id' => $pdo->lastInsertId(),
                'nome' => $nome,
                'telefone' => $telefone
            ], 201);
            break;

        default:
            jsonResponse(['error' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
