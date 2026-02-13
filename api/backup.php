<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../app/autoload.php';
use App\Core\Database;
use App\Core\Auth;

// Verificar se está logado
Auth::verificarLoginAPI();
$usuarioId = Auth::getUsuarioId();

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
        $stmt = $pdo->prepare("SELECT * FROM empresas WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        $dados['empresas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Clientes
        $stmt = $pdo->prepare("SELECT * FROM clientes WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        $dados['clientes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Notinhas com clientes
        $stmt = $pdo->prepare("
            SELECT n.*, e.nome as empresa_nome
            FROM notinhas n
            LEFT JOIN empresas e ON n.empresa_id = e.id
            WHERE n.usuario_id = ?
        ");
        $stmt->execute([$usuarioId]);
        $notinhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($notinhas as &$notinha) {
            $stmtClientes = $pdo->prepare("SELECT * FROM notinha_clientes WHERE notinha_id = ?");
            $stmtClientes->execute([$notinha['id']]);
            $notinha['clientes'] = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);
        }
        $dados['notinhas'] = $notinhas;
        
        // Configurações
        $stmt = $pdo->prepare("SELECT * FROM configuracoes WHERE usuario_id = ? LIMIT 1");
        $stmt->execute([$usuarioId]);
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
        
        // Limpar dados APENAS do usuário atual
        $pdo->prepare("DELETE FROM notinha_clientes WHERE notinha_id IN (SELECT id FROM notinhas WHERE usuario_id = ?)")->execute([$usuarioId]);
        $pdo->prepare("DELETE FROM notinhas WHERE usuario_id = ?")->execute([$usuarioId]);
        $pdo->prepare("DELETE FROM clientes WHERE usuario_id = ?")->execute([$usuarioId]);
        $pdo->prepare("DELETE FROM empresas WHERE usuario_id = ?")->execute([$usuarioId]);
        $pdo->prepare("DELETE FROM configuracoes WHERE usuario_id = ?")->execute([$usuarioId]);
        
        // Mapeamento de IDs antigos para novos
        $empresaIdMap = [];
        $notinhaIdMap = [];
        
        // Importar empresas
        if (!empty($dados['empresas'])) {
            $stmt = $pdo->prepare("INSERT INTO empresas (nome, usuario_id, created_at) VALUES (?, ?, ?)");
            foreach ($dados['empresas'] as $empresa) {
                $stmt->execute([$empresa['nome'], $usuarioId, $empresa['created_at'] ?? date('Y-m-d H:i:s')]);
                $empresaIdMap[$empresa['id']] = $pdo->lastInsertId();
            }
        }
        
        // Importar clientes
        if (!empty($dados['clientes'])) {
            $stmt = $pdo->prepare("INSERT INTO clientes (nome, telefone, usuario_id, created_at) VALUES (?, ?, ?, ?)");
            foreach ($dados['clientes'] as $cliente) {
                $stmt->execute([
                    $cliente['nome'], 
                    $cliente['telefone'] ?? '', 
                    $usuarioId,
                    $cliente['created_at'] ?? date('Y-m-d H:i:s')
                ]);
            }
        }
        
        // Importar notinhas
        if (!empty($dados['notinhas'])) {
            $stmtNotinha = $pdo->prepare("
                INSERT INTO notinhas (empresa_id, data_cobranca, enviada, deleted_at, inadimplente_at, recebido_at, usuario_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtCliente = $pdo->prepare("
                INSERT INTO notinha_clientes (notinha_id, nome, valor, telefone, msg_enviada, data_envio, deleted_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($dados['notinhas'] as $notinha) {
                $novoEmpresaId = $empresaIdMap[$notinha['empresa_id']] ?? null;
                
                $stmtNotinha->execute([
                    $novoEmpresaId,
                    $notinha['data_cobranca'],
                    $notinha['enviada'] ?? 0,
                    $notinha['deleted_at'],
                    $notinha['inadimplente_at'],
                    $notinha['recebido_at'],
                    $usuarioId,
                    $notinha['created_at'] ?? date('Y-m-d H:i:s')
                ]);
                
                $novoNotinhaId = $pdo->lastInsertId();
                $notinhaIdMap[$notinha['id']] = $novoNotinhaId;
                
                if (!empty($notinha['clientes'])) {
                    foreach ($notinha['clientes'] as $cliente) {
                        $stmtCliente->execute([
                            $novoNotinhaId,
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
            $stmt = $pdo->prepare("
                INSERT INTO configuracoes (chave, valor, usuario_id) 
                VALUES (?, ?, ?)
            ");
            if (isset($dados['configuracoes']['chave_pix'])) {
                $stmt->execute(['chave_pix', $dados['configuracoes']['chave_pix'], $usuarioId]);
            }
            if (isset($dados['configuracoes']['nome_vendedor'])) {
                $stmt->execute(['nome_vendedor', $dados['configuracoes']['nome_vendedor'], $usuarioId]);
            }
            if (isset($dados['configuracoes']['mensagem_padrao'])) {
                $stmt->execute(['mensagem_padrao', $dados['configuracoes']['mensagem_padrao'], $usuarioId]);
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Dados importados com sucesso!']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

