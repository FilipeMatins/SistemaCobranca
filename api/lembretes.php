<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../app/autoload.php';
use App\Core\Database;
use App\Core\Auth;

// Verificar se está logado
Auth::verificarLoginAPI();
$usuarioId = Auth::getUsuarioId();

try {
    $pdo = Database::getInstance();
    
    $dias = intval($_GET['dias'] ?? 3);
    $listar = isset($_GET['listar']) && $_GET['listar'] == '1';
    $hoje = date('Y-m-d');
    $dataLimite = date('Y-m-d', strtotime("+$dias days"));
    
    // Buscar cobranças que vencem nos próximos X dias (excluindo hoje, que já tem banner próprio)
    $amanha = date('Y-m-d', strtotime('+1 day'));
    
    // Conta NOTINHAS (não clientes) para ser consistente com "Ver Detalhes"
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT n.id) as total_notinhas,
            COUNT(*) as total_clientes,
            COALESCE(SUM(nc.valor), 0) as valor_total
        FROM notinha_clientes nc
        JOIN notinhas n ON nc.notinha_id = n.id
        WHERE n.data_cobranca BETWEEN ? AND ?
        AND n.deleted_at IS NULL
        AND n.inadimplente_at IS NULL
        AND n.recebido_at IS NULL
        AND nc.deleted_at IS NULL
        AND n.usuario_id = ?
    ");
    $stmt->execute([$amanha, $dataLimite, $usuarioId]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response = [
        'total' => intval($resultado['total_notinhas']),
        'total_clientes' => intval($resultado['total_clientes']),
        'valor_total' => floatval($resultado['valor_total'])
    ];
    
    // Se solicitado, retorna as notinhas completas para exibição
    if ($listar) {
        $stmt = $pdo->prepare("
            SELECT 
                n.id,
                n.data_cobranca,
                n.enviada,
                n.created_at,
                e.id as empresa_id,
                e.nome as empresa_nome
            FROM notinhas n
            JOIN empresas e ON n.empresa_id = e.id
            WHERE n.data_cobranca BETWEEN ? AND ?
            AND n.deleted_at IS NULL
            AND n.inadimplente_at IS NULL
            AND n.recebido_at IS NULL
            AND n.usuario_id = ?
            ORDER BY n.data_cobranca ASC
        ");
        $stmt->execute([$amanha, $dataLimite, $usuarioId]);
        $notinhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Busca os clientes de cada notinha
        foreach ($notinhas as &$notinha) {
            $stmtClientes = $pdo->prepare("
                SELECT id, nome, valor, telefone, msg_enviada, data_envio
                FROM notinha_clientes
                WHERE notinha_id = ? AND deleted_at IS NULL
                ORDER BY id
            ");
            $stmtClientes->execute([$notinha['id']]);
            $notinha['clientes'] = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $response['notinhas'] = $notinhas;
    } else {
        // Buscar detalhes resumidos das cobranças (para preview)
        $stmt = $pdo->prepare("
            SELECT 
                nc.nome as cliente_nome,
                nc.valor,
                nc.telefone,
                n.data_cobranca,
                e.nome as empresa_nome
            FROM notinha_clientes nc
            JOIN notinhas n ON nc.notinha_id = n.id
            JOIN empresas e ON n.empresa_id = e.id
            WHERE n.data_cobranca BETWEEN ? AND ?
            AND n.deleted_at IS NULL
            AND n.inadimplente_at IS NULL
            AND n.recebido_at IS NULL
            AND nc.deleted_at IS NULL
            AND n.usuario_id = ?
            ORDER BY n.data_cobranca ASC
            LIMIT 20
        ");
        $stmt->execute([$amanha, $dataLimite, $usuarioId]);
        $response['cobrancas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

