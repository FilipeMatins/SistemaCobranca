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
    $hoje = date('Y-m-d');
    $primeiroDiaMes = date('Y-m-01');
    $ultimoDiaMes = date('Y-m-t');
    
    // 1. Total recebido este mês (somando todos os recebimentos registrados no mês)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(nr.valor), 0) as total
        FROM notinha_recebimentos nr
        JOIN notinhas n ON nr.notinha_id = n.id
        WHERE MONTH(nr.recebido_em) = MONTH(CURRENT_DATE())
        AND YEAR(nr.recebido_em) = YEAR(CURRENT_DATE())
        AND n.deleted_at IS NULL
        AND nr.usuario_id = ?
    ");
    $stmt->execute([$usuarioId]);
    $recebidoMes = $stmt->fetch()['total'];
    
    // 2. Previsão de recebimentos (pendentes do mês)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(nc.valor), 0) as total
        FROM notinha_clientes nc
        JOIN notinhas n ON nc.notinha_id = n.id
        WHERE nc.msg_enviada = 0 
        AND n.data_cobranca >= ?
        AND n.deleted_at IS NULL
        AND n.inadimplente_at IS NULL
        AND nc.deleted_at IS NULL
        AND n.usuario_id = ?
    ");
    $stmt->execute([$hoje, $usuarioId]);
    $previsao = $stmt->fetch()['total'];
    
    // 3. Taxa de inadimplência
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM notinhas WHERE inadimplente_at IS NOT NULL AND usuario_id = ?
    ");
    $stmt->execute([$usuarioId]);
    $totalInadimplentes = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM notinhas WHERE deleted_at IS NULL AND usuario_id = ?
    ");
    $stmt->execute([$usuarioId]);
    $totalNotinhas = $stmt->fetch()['total'];
    
    $taxaInadimplencia = $totalNotinhas > 0 ? ($totalInadimplentes / $totalNotinhas) * 100 : 0;
    
    // 4. Total de clientes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM clientes WHERE usuario_id = ?");
    $stmt->execute([$usuarioId]);
    $totalClientes = $stmt->fetch()['total'];
    
    // 5. Vendas por mês - Lançado vs Recebido (3 meses atrás até 2 meses à frente)
    $vendasPorMes = [];
    $mesesNomes = [
        'Jan' => 'Jan', 'Feb' => 'Fev', 'Mar' => 'Mar', 'Apr' => 'Abr',
        'May' => 'Mai', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago',
        'Sep' => 'Set', 'Oct' => 'Out', 'Nov' => 'Nov', 'Dec' => 'Dez'
    ];
    
    // De 3 meses atrás até 2 meses à frente (6 meses no total)
    for ($i = -3; $i <= 2; $i++) {
        if ($i < 0) {
            $mes = date('Y-m', strtotime("$i months"));
        } elseif ($i == 0) {
            $mes = date('Y-m');
        } else {
            $mes = date('Y-m', strtotime("+$i months"));
        }
        
        $primeiroDia = $mes . '-01';
        $ultimoDia = date('Y-m-t', strtotime($primeiroDia));
        $nomeMes = $mesesNomes[date('M', strtotime($primeiroDia))] ?? date('M', strtotime($primeiroDia));
        
        // Marca o mês atual
        $mesAtual = ($mes === date('Y-m'));
        
        // Valor LANÇADO (todas as notinhas com VENCIMENTO no mês - soma todos os clientes, inclusive já recebidos)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(nc.valor), 0) as total
            FROM notinha_clientes nc
            JOIN notinhas n ON nc.notinha_id = n.id
            WHERE n.data_cobranca BETWEEN ? AND ?
            AND n.deleted_at IS NULL
            AND n.usuario_id = ?
        ");
        $stmt->execute([$primeiroDia, $ultimoDia, $usuarioId]);
        $lancado = $stmt->fetch()['total'];
        
        // Valor RECEBIDO (todos os recebimentos do mês - independente do vencimento)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(nr.valor), 0) as total
            FROM notinha_recebimentos nr
            JOIN notinhas n ON nr.notinha_id = n.id
            WHERE nr.recebido_em BETWEEN ? AND ?
            AND n.deleted_at IS NULL
            AND nr.usuario_id = ?
        ");
        $stmt->execute([$primeiroDia . ' 00:00:00', $ultimoDia . ' 23:59:59', $usuarioId]);
        $recebido = $stmt->fetch()['total'];
        
        $vendasPorMes[] = [
            'mes' => $nomeMes . ($mesAtual ? '*' : ''),
            'lancado' => floatval($lancado),
            'recebido' => floatval($recebido)
        ];
    }
    
    // 6. Inadimplentes por mês (últimos 6 meses)
    $inadimplentesPorMes = [];
    for ($i = 5; $i >= 0; $i--) {
        $mes = date('Y-m', strtotime("-$i months"));
        $primeiroDia = $mes . '-01';
        $ultimoDia = date('Y-m-t', strtotime($primeiroDia));
        $nomeMes = $mesesNomes[date('M', strtotime($primeiroDia))] ?? date('M', strtotime($primeiroDia));
        
        // Valor que foi para INADIMPLENTE no mês (pela data inadimplente_at)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(nc.valor), 0) as total
            FROM notinha_clientes nc
            JOIN notinhas n ON nc.notinha_id = n.id
            WHERE n.inadimplente_at IS NOT NULL
            AND n.inadimplente_at BETWEEN ? AND ?
            AND nc.deleted_at IS NULL
            AND n.usuario_id = ?
        ");
        $stmt->execute([$primeiroDia . ' 00:00:00', $ultimoDia . ' 23:59:59', $usuarioId]);
        $total = $stmt->fetch()['total'];
        
        $inadimplentesPorMes[] = [
            'mes' => $nomeMes,
            'total' => floatval($total)
        ];
    }
    
    // 7. Top 10 clientes que mais compram
    $stmt = $pdo->prepare("
        SELECT 
            nc.nome,
            nc.telefone,
            COUNT(*) as total_compras,
            SUM(nc.valor) as total_gasto
        FROM notinha_clientes nc
        JOIN notinhas n ON nc.notinha_id = n.id
        WHERE n.deleted_at IS NULL
        AND nc.deleted_at IS NULL
        AND n.usuario_id = ?
        GROUP BY nc.nome, nc.telefone
        ORDER BY total_gasto DESC
        LIMIT 10
    ");
    $stmt->execute([$usuarioId]);
    $topClientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 8. Próximos vencimentos (próximos 7 dias)
    $proximaSemana = date('Y-m-d', strtotime('+7 days'));
    $stmt = $pdo->prepare("
        SELECT 
            nc.nome as cliente_nome,
            nc.valor,
            n.data_cobranca,
            e.nome as empresa_nome
        FROM notinha_clientes nc
        JOIN notinhas n ON nc.notinha_id = n.id
        JOIN empresas e ON n.empresa_id = e.id
        WHERE n.data_cobranca BETWEEN ? AND ?
        AND nc.msg_enviada = 0
        AND n.deleted_at IS NULL
        AND n.inadimplente_at IS NULL
        AND nc.deleted_at IS NULL
        AND n.usuario_id = ?
        ORDER BY n.data_cobranca ASC
        LIMIT 10
    ");
    $stmt->execute([$hoje, $proximaSemana, $usuarioId]);
    $proximosVencimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'recebido_mes' => $recebidoMes,
        'previsao_recebimentos' => $previsao,
        'taxa_inadimplencia' => $taxaInadimplencia,
        'total_clientes' => $totalClientes,
        'vendas_por_mes' => $vendasPorMes,
        'inadimplentes_por_mes' => $inadimplentesPorMes,
        'top_clientes' => $topClientes,
        'proximos_vencimentos' => $proximosVencimentos
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

