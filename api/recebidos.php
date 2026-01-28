<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/Notinha.php';

use App\Models\Notinha;

$model = new Notinha();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $recebidos = $model->listarRecebidos();
        $totalMes = $model->totalRecebidoMes();
        $totalGeral = $model->totalRecebidoGeral();
        
        echo json_encode([
            'success' => true,
            'recebidos' => $recebidos,
            'totalMes' => $totalMes,
            'totalGeral' => $totalGeral
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    $acao = $data['acao'] ?? '';
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID inválido']);
        exit;
    }
    
    try {
        if ($acao === 'receber') {
            $result = $model->marcarComoRecebido($id);
            echo json_encode(['success' => $result]);
        } elseif ($acao === 'desfazer') {
            // Desfazer = voltar para lista ativa (remove recebido_at)
            $pdo = \App\Core\Database::getInstance();
            $stmt = $pdo->prepare("UPDATE notinhas SET recebido_at = NULL WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

