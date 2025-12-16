<?php
// API de Clientes Cadastrados
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getConnection();

    switch ($method) {
        case 'GET':
            // Buscar clientes (autocomplete ou listagem completa)
            $termo = $_GET['termo'] ?? '';
            $todos = isset($_GET['todos']); // Se passar ?todos lista todos
            
            if ($termo) {
                $stmt = $pdo->prepare("SELECT id, nome, telefone FROM clientes WHERE nome LIKE ? ORDER BY nome LIMIT 10");
                $stmt->execute(["%$termo%"]);
            } else {
                // Lista todos os clientes ordenados por nome
                $stmt = $pdo->query("SELECT id, nome, telefone FROM clientes ORDER BY nome");
            }
            
            jsonResponse($stmt->fetchAll());
            break;

        case 'POST':
            // Criar cliente
            $data = json_decode(file_get_contents('php://input'), true);
            $nome = trim($data['nome'] ?? '');
            $telefone = trim($data['telefone'] ?? '');

            if (empty($nome)) {
                jsonResponse(['error' => 'Nome é obrigatório'], 400);
            }

            // Verifica se nome já existe
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE nome = ?");
            $stmt->execute([$nome]);
            if ($stmt->fetch()) {
                jsonResponse(['error' => 'Cliente já cadastrado com este nome'], 400);
            }

            // Verifica se telefone já existe em outro cliente
            if (!empty($telefone)) {
                $stmt = $pdo->prepare("SELECT nome FROM clientes WHERE telefone = ?");
                $stmt->execute([$telefone]);
                $clienteExistente = $stmt->fetch();
                if ($clienteExistente) {
                    jsonResponse(['error' => 'Este telefone já está cadastrado para: ' . $clienteExistente['nome']], 400);
                }
            }

            // Cria novo cliente
            $stmt = $pdo->prepare("INSERT INTO clientes (nome, telefone) VALUES (?, ?)");
            $stmt->execute([$nome, $telefone]);
            
            jsonResponse([
                'success' => true,
                'id' => $pdo->lastInsertId(),
                'nome' => $nome,
                'telefone' => $telefone
            ], 201);
            break;

        case 'PUT':
            // Atualizar cliente
            $data = json_decode(file_get_contents('php://input'), true);
            $id = intval($data['id'] ?? 0);
            $nome = trim($data['nome'] ?? '');
            $telefone = trim($data['telefone'] ?? '');

            if (!$id || empty($nome)) {
                jsonResponse(['error' => 'ID e nome são obrigatórios'], 400);
            }

            // Verifica se nome já existe em outro cliente
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE nome = ? AND id != ?");
            $stmt->execute([$nome, $id]);
            if ($stmt->fetch()) {
                jsonResponse(['error' => 'Já existe outro cliente com este nome'], 400);
            }

            // Verifica se telefone já existe em outro cliente
            if (!empty($telefone)) {
                $stmt = $pdo->prepare("SELECT nome FROM clientes WHERE telefone = ? AND id != ?");
                $stmt->execute([$telefone, $id]);
                $clienteExistente = $stmt->fetch();
                if ($clienteExistente) {
                    jsonResponse(['error' => 'Este telefone já está cadastrado para: ' . $clienteExistente['nome']], 400);
                }
            }

            $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, telefone = ? WHERE id = ?");
            $stmt->execute([$nome, $telefone, $id]);
            
            jsonResponse(['success' => true]);
            break;

        case 'DELETE':
            // Excluir cliente
            $id = intval($_GET['id'] ?? 0);
            
            if (!$id) {
                jsonResponse(['error' => 'ID é obrigatório'], 400);
            }

            $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ?");
            $stmt->execute([$id]);
            
            jsonResponse(['success' => true]);
            break;

        default:
            jsonResponse(['error' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
