<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../app/autoload.php';
use App\Core\Database;

try {
    $pdo = Database::getInstance();
    
    $nome = $_GET['nome'] ?? '';
    $telefone = $_GET['telefone'] ?? '';
    
    if (empty($nome)) {
        throw new Exception('Nome do cliente é obrigatório');
    }
    
    $hoje = date('Y-m-d');
    
    // Buscar todas as compras do cliente
    $sql = "
        SELECT 
            nc.valor,
            nc.msg_enviada,
            n.data_cobranca,
            e.nome as empresa_nome
        FROM notinha_clientes nc
        JOIN notinhas n ON nc.notinha_id = n.id
        JOIN empresas e ON n.empresa_id = e.id
        WHERE nc.nome = ?
        AND n.deleted_at IS NULL
        AND nc.deleted_at IS NULL
        ORDER BY n.data_cobranca DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nome]);
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular métricas
    $totalGasto = 0;
    $totalCompras = count($compras);
    $pagosEmDia = 0;
    $totalPagaveis = 0;
    
    foreach ($compras as $compra) {
        $totalGasto += floatval($compra['valor']);
        
        // Conta pagamentos em dia (msg enviada antes ou no dia do vencimento)
        if ($compra['data_cobranca'] <= $hoje) {
            $totalPagaveis++;
            if ($compra['msg_enviada'] == 1) {
                $pagosEmDia++;
            }
        }
    }
    
    $mediaTicket = $totalCompras > 0 ? $totalGasto / $totalCompras : 0;
    $taxaPagamento = $totalPagaveis > 0 ? ($pagosEmDia / $totalPagaveis) * 100 : 100;
    
    echo json_encode([
        'nome' => $nome,
        'telefone' => $telefone,
        'total_gasto' => $totalGasto,
        'media_ticket' => $mediaTicket,
        'total_compras' => $totalCompras,
        'taxa_pagamento' => $taxaPagamento,
        'compras' => $compras
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

