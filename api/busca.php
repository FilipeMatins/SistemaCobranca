<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../app/autoload.php';
use App\Core\Database;

$pdo = Database::getInstance();

$termo = trim($_GET['termo'] ?? '');

if (strlen($termo) < 2) {
    echo json_encode(['error' => 'Termo muito curto']);
    exit;
}

$termoBusca = '%' . $termo . '%';
$hoje = date('Y-m-d');

try {
    $resultados = [
        'notinhas' => [],
        'clientes' => [],
        'empresas' => []
    ];
    
    // Buscar notinhas
    $stmt = $pdo->prepare("
        SELECT 
            n.id,
            n.data_cobranca,
            e.nome as empresa_nome,
            GROUP_CONCAT(nc.nome SEPARATOR ', ') as clientes_nomes,
            SUM(nc.valor) as total,
            CASE 
                WHEN n.recebido_at IS NOT NULL THEN 'recebido'
                WHEN n.inadimplente_at IS NOT NULL THEN 'inadimplente'
                WHEN n.deleted_at IS NOT NULL THEN 'excluido'
                WHEN n.data_cobranca < ? THEN 'atrasado'
                WHEN n.data_cobranca = ? THEN 'hoje'
                ELSE 'futuro'
            END as status
        FROM notinhas n
        JOIN empresas e ON n.empresa_id = e.id
        LEFT JOIN notinha_clientes nc ON nc.notinha_id = n.id AND nc.deleted_at IS NULL
        WHERE (e.nome LIKE ? OR nc.nome LIKE ?)
        GROUP BY n.id
        ORDER BY n.data_cobranca DESC
        LIMIT 20
    ");
    $stmt->execute([$hoje, $hoje, $termoBusca, $termoBusca]);
    $notinhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adicionar classe e texto do status
    foreach ($notinhas as &$n) {
        switch ($n['status']) {
            case 'recebido':
                $n['status_classe'] = 'status-enviado';
                $n['status_texto'] = 'Recebido';
                break;
            case 'inadimplente':
                $n['status_classe'] = 'status-atrasado';
                $n['status_texto'] = 'Inadimplente';
                break;
            case 'excluido':
                $n['status_classe'] = 'status-atrasado';
                $n['status_texto'] = 'Excluído';
                break;
            case 'atrasado':
                $n['status_classe'] = 'status-atrasado';
                $n['status_texto'] = 'Atrasado';
                break;
            case 'hoje':
                $n['status_classe'] = 'status-hoje';
                $n['status_texto'] = 'Hoje';
                break;
            default:
                $n['status_classe'] = 'status-futuro';
                $n['status_texto'] = 'Agendado';
        }
    }
    $resultados['notinhas'] = $notinhas;
    
    // Buscar clientes cadastrados
    $stmt = $pdo->prepare("
        SELECT 
            c.nome,
            c.telefone,
            COUNT(DISTINCT nc.notinha_id) as total_compras,
            COALESCE(SUM(nc.valor), 0) as total_gasto
        FROM clientes c
        LEFT JOIN notinha_clientes nc ON nc.nome = c.nome AND nc.deleted_at IS NULL
        LEFT JOIN notinhas n ON nc.notinha_id = n.id AND n.deleted_at IS NULL
        WHERE c.nome LIKE ? OR c.telefone LIKE ?
        GROUP BY c.id
        ORDER BY c.nome
        LIMIT 20
    ");
    $stmt->execute([$termoBusca, $termoBusca]);
    $resultados['clientes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar empresas
    $stmt = $pdo->prepare("
        SELECT 
            e.nome,
            COUNT(n.id) as total_notinhas
        FROM empresas e
        LEFT JOIN notinhas n ON n.empresa_id = e.id AND n.deleted_at IS NULL
        WHERE e.nome LIKE ?
        GROUP BY e.id
        ORDER BY e.nome
        LIMIT 10
    ");
    $stmt->execute([$termoBusca]);
    $resultados['empresas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($resultados);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

