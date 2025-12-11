<?php
// API de Notinhas
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $pdo = getConnection();

    // Limpa excluídos com mais de 15 dias automaticamente
    $pdo->exec("DELETE FROM notinhas WHERE deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL 15 DAY)");

    switch ($method) {
        case 'GET':
            if ($action === 'excluidos') {
                // Listar notinhas excluídas (lixeira)
                $stmt = $pdo->query("
                    SELECT 
                        n.id,
                        n.data_cobranca,
                        n.deleted_at,
                        n.created_at,
                        e.id as empresa_id,
                        e.nome as empresa_nome,
                        DATEDIFF(DATE_ADD(n.deleted_at, INTERVAL 15 DAY), NOW()) as dias_restantes
                    FROM notinhas n
                    JOIN empresas e ON n.empresa_id = e.id
                    WHERE n.deleted_at IS NOT NULL
                    ORDER BY n.deleted_at DESC
                ");
                $notinhas = $stmt->fetchAll();

                foreach ($notinhas as &$notinha) {
                    $stmt = $pdo->prepare("
                        SELECT id, nome, valor, telefone
                        FROM notinha_clientes
                        WHERE notinha_id = ?
                    ");
                    $stmt->execute([$notinha['id']]);
                    $notinha['clientes'] = $stmt->fetchAll();
                }

                jsonResponse($notinhas);
            } else {
                // Listar notinhas ativas
                $stmt = $pdo->query("
                    SELECT 
                        n.id,
                        n.data_cobranca,
                        n.enviada,
                        n.created_at,
                        e.id as empresa_id,
                        e.nome as empresa_nome
                    FROM notinhas n
                    JOIN empresas e ON n.empresa_id = e.id
                    WHERE n.deleted_at IS NULL
                    ORDER BY n.data_cobranca DESC, n.created_at DESC
                ");
                $notinhas = $stmt->fetchAll();

                foreach ($notinhas as &$notinha) {
                    $stmt = $pdo->prepare("
                        SELECT id, nome, valor, telefone, msg_enviada, data_envio
                        FROM notinha_clientes
                        WHERE notinha_id = ?
                        ORDER BY id
                    ");
                    $stmt->execute([$notinha['id']]);
                    $notinha['clientes'] = $stmt->fetchAll();
                }

                jsonResponse($notinhas);
            }
            break;

        case 'POST':
            // Criar nova notinha
            $data = json_decode(file_get_contents('php://input'), true);
            
            $empresa_nome = trim($data['empresa'] ?? '');
            $data_cobranca = $data['data_cobranca'] ?? '';
            $clientes = $data['clientes'] ?? [];

            if (empty($empresa_nome)) {
                jsonResponse(['error' => 'Nome da empresa é obrigatório'], 400);
            }

            if (empty($data_cobranca)) {
                jsonResponse(['error' => 'Data da cobrança é obrigatória'], 400);
            }

            if (empty($clientes)) {
                jsonResponse(['error' => 'Adicione pelo menos um cliente'], 400);
            }

            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare("SELECT id FROM empresas WHERE nome = ?");
                $stmt->execute([$empresa_nome]);
                $empresa = $stmt->fetch();

                if (!$empresa) {
                    $stmt = $pdo->prepare("INSERT INTO empresas (nome) VALUES (?)");
                    $stmt->execute([$empresa_nome]);
                    $empresa_id = $pdo->lastInsertId();
                } else {
                    $empresa_id = $empresa['id'];
                }

                $stmt = $pdo->prepare("INSERT INTO notinhas (empresa_id, data_cobranca) VALUES (?, ?)");
                $stmt->execute([$empresa_id, $data_cobranca]);
                $notinha_id = $pdo->lastInsertId();

                $stmtNotinha = $pdo->prepare("
                    INSERT INTO notinha_clientes (notinha_id, nome, valor, telefone) 
                    VALUES (?, ?, ?, ?)
                ");

                $stmtCliente = $pdo->prepare("
                    INSERT INTO clientes (nome, telefone) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE telefone = IF(VALUES(telefone) != '', VALUES(telefone), telefone)
                ");

                foreach ($clientes as $cliente) {
                    $nome = trim($cliente['nome'] ?? '');
                    $valor = floatval(str_replace([',', 'R$', ' '], ['.', '', ''], $cliente['valor'] ?? '0'));
                    $telefone = trim($cliente['telefone'] ?? '');

                    if (!empty($nome)) {
                        $stmtNotinha->execute([$notinha_id, $nome, $valor, $telefone]);
                        $stmtCliente->execute([$nome, $telefone]);
                    }
                }

                $pdo->commit();

                jsonResponse([
                    'success' => true,
                    'id' => $notinha_id,
                    'message' => 'Notinha salva com sucesso!'
                ], 201);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'PUT':
            // Atualizar notinha
            $data = json_decode(file_get_contents('php://input'), true);
            
            $id = $data['id'] ?? 0;
            $empresa_nome = trim($data['empresa'] ?? '');
            $data_cobranca = $data['data_cobranca'] ?? '';
            $clientes = $data['clientes'] ?? [];

            if (!$id) {
                jsonResponse(['error' => 'ID não informado'], 400);
            }

            if (empty($empresa_nome) || empty($data_cobranca) || empty($clientes)) {
                jsonResponse(['error' => 'Preencha todos os campos'], 400);
            }

            $pdo->beginTransaction();

            try {
                $stmt = $pdo->prepare("SELECT id FROM empresas WHERE nome = ?");
                $stmt->execute([$empresa_nome]);
                $empresa = $stmt->fetch();

                if (!$empresa) {
                    $stmt = $pdo->prepare("INSERT INTO empresas (nome) VALUES (?)");
                    $stmt->execute([$empresa_nome]);
                    $empresa_id = $pdo->lastInsertId();
                } else {
                    $empresa_id = $empresa['id'];
                }

                $stmt = $pdo->prepare("UPDATE notinhas SET empresa_id = ?, data_cobranca = ? WHERE id = ?");
                $stmt->execute([$empresa_id, $data_cobranca, $id]);

                $stmt = $pdo->prepare("DELETE FROM notinha_clientes WHERE notinha_id = ?");
                $stmt->execute([$id]);

                $stmtNotinha = $pdo->prepare("
                    INSERT INTO notinha_clientes (notinha_id, nome, valor, telefone) 
                    VALUES (?, ?, ?, ?)
                ");

                $stmtCliente = $pdo->prepare("
                    INSERT INTO clientes (nome, telefone) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE telefone = IF(VALUES(telefone) != '', VALUES(telefone), telefone)
                ");

                foreach ($clientes as $cliente) {
                    $nome = trim($cliente['nome'] ?? '');
                    $valor = floatval(str_replace([',', 'R$', ' '], ['.', '', ''], $cliente['valor'] ?? '0'));
                    $telefone = trim($cliente['telefone'] ?? '');

                    if (!empty($nome)) {
                        $stmtNotinha->execute([$id, $nome, $valor, $telefone]);
                        $stmtCliente->execute([$nome, $telefone]);
                    }
                }

                $pdo->commit();

                jsonResponse([
                    'success' => true,
                    'message' => 'Notinha atualizada!'
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'PATCH':
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? 'restaurar';

            if ($action === 'marcar_enviado') {
                // Marcar clientes como enviados
                $cliente_ids = $data['cliente_ids'] ?? [];
                
                if (empty($cliente_ids)) {
                    jsonResponse(['error' => 'IDs não informados'], 400);
                }

                $placeholders = str_repeat('?,', count($cliente_ids) - 1) . '?';
                $stmt = $pdo->prepare("UPDATE notinha_clientes SET msg_enviada = 1, data_envio = NOW() WHERE id IN ($placeholders)");
                $stmt->execute($cliente_ids);

                jsonResponse(['success' => true, 'message' => 'Marcado como enviado!']);
            } else {
                // Restaurar notinha da lixeira
                $id = $data['id'] ?? 0;

                if (!$id) {
                    jsonResponse(['error' => 'ID não informado'], 400);
                }

                $stmt = $pdo->prepare("UPDATE notinhas SET deleted_at = NULL WHERE id = ?");
                $stmt->execute([$id]);

                if ($stmt->rowCount() > 0) {
                    jsonResponse(['success' => true, 'message' => 'Notinha restaurada!']);
                } else {
                    jsonResponse(['error' => 'Notinha não encontrada'], 404);
                }
            }
            break;

        case 'DELETE':
            $id = $_GET['id'] ?? 0;
            $permanent = $_GET['permanent'] ?? '0';

            if (!$id) {
                jsonResponse(['error' => 'ID não informado'], 400);
            }

            if ($permanent === '1') {
                // Exclusão permanente
                $stmt = $pdo->prepare("DELETE FROM notinhas WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Notinha excluída permanentemente';
            } else {
                // Soft delete - mover para lixeira
                $stmt = $pdo->prepare("UPDATE notinhas SET deleted_at = NOW() WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Notinha movida para lixeira';
            }

            if ($stmt->rowCount() > 0) {
                jsonResponse(['success' => true, 'message' => $message]);
            } else {
                jsonResponse(['error' => 'Notinha não encontrada'], 404);
            }
            break;

        default:
            jsonResponse(['error' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}
