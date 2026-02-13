<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../app/autoload.php';
use App\Core\Database;
use App\Core\Auth;

// Verificar se está logado
Auth::verificarLoginAPI();
$usuarioId = Auth::getUsuarioId();

$pdo = Database::getInstance();

$mes = $_GET['mes'] ?? date('Y-m');
$tipo = $_GET['tipo'] ?? 'completo';

try {
    $primeiroDia = $mes . '-01';
    $ultimoDia = date('Y-m-t', strtotime($primeiroDia));
    
    // Total Lançado (vencimento no mês)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(nc.valor), 0) as total
        FROM notinha_clientes nc
        JOIN notinhas n ON nc.notinha_id = n.id
        WHERE n.data_cobranca BETWEEN ? AND ?
        AND n.deleted_at IS NULL
        AND nc.deleted_at IS NULL
        AND n.usuario_id = ?
    ");
    $stmt->execute([$primeiroDia, $ultimoDia, $usuarioId]);
    $totalLancado = $stmt->fetch()['total'];
    
    // Total Recebido (recebido no mês)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(nc.valor), 0) as total
        FROM notinha_clientes nc
        JOIN notinhas n ON nc.notinha_id = n.id
        WHERE n.recebido_at BETWEEN ? AND ?
        AND nc.deleted_at IS NULL
        AND n.usuario_id = ?
    ");
    $stmt->execute([$primeiroDia . ' 00:00:00', $ultimoDia . ' 23:59:59', $usuarioId]);
    $totalRecebido = $stmt->fetch()['total'];
    
    // Total Inadimplente (marcado como inadimplente no mês)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(nc.valor), 0) as total
        FROM notinha_clientes nc
        JOIN notinhas n ON nc.notinha_id = n.id
        WHERE n.inadimplente_at BETWEEN ? AND ?
        AND nc.deleted_at IS NULL
        AND n.usuario_id = ?
    ");
    $stmt->execute([$primeiroDia . ' 00:00:00', $ultimoDia . ' 23:59:59', $usuarioId]);
    $totalInadimplente = $stmt->fetch()['total'];
    
    // Total Pendente (vencimento no mês, não recebido, não inadimplente)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(nc.valor), 0) as total
        FROM notinha_clientes nc
        JOIN notinhas n ON nc.notinha_id = n.id
        WHERE n.data_cobranca BETWEEN ? AND ?
        AND n.recebido_at IS NULL
        AND n.inadimplente_at IS NULL
        AND n.deleted_at IS NULL
        AND nc.deleted_at IS NULL
        AND n.usuario_id = ?
    ");
    $stmt->execute([$primeiroDia, $ultimoDia, $usuarioId]);
    $totalPendente = $stmt->fetch()['total'];
    
    // Detalhamento
    $notinhas = [];
    if ($tipo === 'completo') {
        $stmt = $pdo->prepare("
            SELECT 
                n.data_cobranca,
                e.nome as empresa_nome,
                nc.nome as cliente_nome,
                nc.valor,
                CASE 
                    WHEN n.recebido_at IS NOT NULL THEN 'Recebido'
                    WHEN n.inadimplente_at IS NOT NULL THEN 'Inadimplente'
                    WHEN n.data_cobranca < CURDATE() THEN 'Atrasado'
                    ELSE 'Pendente'
                END as status
            FROM notinha_clientes nc
            JOIN notinhas n ON nc.notinha_id = n.id
            JOIN empresas e ON n.empresa_id = e.id
            WHERE n.data_cobranca BETWEEN ? AND ?
            AND n.deleted_at IS NULL
            AND nc.deleted_at IS NULL
            AND n.usuario_id = ?
            ORDER BY n.data_cobranca, e.nome, nc.nome
        ");
        $stmt->execute([$primeiroDia, $ultimoDia, $usuarioId]);
        $notinhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'mes' => $mes,
        'total_lancado' => floatval($totalLancado),
        'total_recebido' => floatval($totalRecebido),
        'total_pendente' => floatval($totalPendente),
        'total_inadimplente' => floatval($totalInadimplente),
        'notinhas' => $notinhas
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

