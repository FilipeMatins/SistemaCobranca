<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../app/autoload.php';
use App\Core\Database;

$pdo = Database::getInstance();

// Exportar dados
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'export') {
    try {
        $dados = [
            'versao' => '1.0',
            'data_export' => date('Y-m-d H:i:s'),
            'empresas' => [],
            'clientes' => [],
            'notinhas' => [],
            'configuracoes' => []
        ];
        
        // Empresas
        $stmt = $pdo->query("SELECT * FROM empresas");
        $dados['empresas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Clientes
        $stmt = $pdo->query("SELECT * FROM clientes");
        $dados['clientes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Notinhas com clientes
        $stmt = $pdo->query("
            SELECT n.*, e.nome as empresa_nome
            FROM notinhas n
            LEFT JOIN empresas e ON n.empresa_id = e.id
        ");
        $notinhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($notinhas as &$notinha) {
            $stmtClientes = $pdo->prepare("SELECT * FROM notinha_clientes WHERE notinha_id = ?");
            $stmtClientes->execute([$notinha['id']]);
            $notinha['clientes'] = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);
        }
        $dados['notinhas'] = $notinhas;
        
        // Configurações
        $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($config) {
            $dados['configuracoes'] = $config;
        }
        
        echo json_encode($dados);
        
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Importar dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (($input['action'] ?? '') !== 'import' || !isset($input['dados'])) {
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        exit;
    }
    
    $dados = $input['dados'];
    
    try {
        $pdo->beginTransaction();
        
        // Limpar tabelas existentes
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE notinha_clientes");
        $pdo->exec("TRUNCATE TABLE notinhas");
        $pdo->exec("TRUNCATE TABLE clientes");
        $pdo->exec("TRUNCATE TABLE empresas");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        // Importar empresas
        if (!empty($dados['empresas'])) {
            $stmt = $pdo->prepare("INSERT INTO empresas (id, nome, created_at) VALUES (?, ?, ?)");
            foreach ($dados['empresas'] as $empresa) {
                $stmt->execute([$empresa['id'], $empresa['nome'], $empresa['created_at'] ?? date('Y-m-d H:i:s')]);
            }
        }
        
        // Importar clientes
        if (!empty($dados['clientes'])) {
            $stmt = $pdo->prepare("INSERT INTO clientes (id, nome, telefone, created_at) VALUES (?, ?, ?, ?)");
            foreach ($dados['clientes'] as $cliente) {
                $stmt->execute([
                    $cliente['id'], 
                    $cliente['nome'], 
                    $cliente['telefone'] ?? '', 
                    $cliente['created_at'] ?? date('Y-m-d H:i:s')
                ]);
            }
        }
        
        // Importar notinhas
        if (!empty($dados['notinhas'])) {
            $stmtNotinha = $pdo->prepare("
                INSERT INTO notinhas (id, empresa_id, data_cobranca, enviada, deleted_at, inadimplente_at, recebido_at, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtCliente = $pdo->prepare("
                INSERT INTO notinha_clientes (id, notinha_id, nome, valor, telefone, msg_enviada, data_envio, deleted_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($dados['notinhas'] as $notinha) {
                $stmtNotinha->execute([
                    $notinha['id'],
                    $notinha['empresa_id'],
                    $notinha['data_cobranca'],
                    $notinha['enviada'] ?? 0,
                    $notinha['deleted_at'],
                    $notinha['inadimplente_at'],
                    $notinha['recebido_at'],
                    $notinha['created_at'] ?? date('Y-m-d H:i:s')
                ]);
                
                if (!empty($notinha['clientes'])) {
                    foreach ($notinha['clientes'] as $cliente) {
                        $stmtCliente->execute([
                            $cliente['id'],
                            $cliente['notinha_id'],
                            $cliente['nome'],
                            $cliente['valor'],
                            $cliente['telefone'] ?? '',
                            $cliente['msg_enviada'] ?? 0,
                            $cliente['data_envio'],
                            $cliente['deleted_at']
                        ]);
                    }
                }
            }
        }
        
        // Importar configurações
        if (!empty($dados['configuracoes'])) {
            $pdo->exec("DELETE FROM configuracoes");
            $stmt = $pdo->prepare("
                INSERT INTO configuracoes (chave_pix, nome_vendedor, mensagem_padrao) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $dados['configuracoes']['chave_pix'] ?? '',
                $dados['configuracoes']['nome_vendedor'] ?? '',
                $dados['configuracoes']['mensagem_padrao'] ?? ''
            ]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Dados importados com sucesso!']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

