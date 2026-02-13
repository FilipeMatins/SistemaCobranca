<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../app/autoload.php';

use App\Models\Notinha;
use App\Core\Auth;

// Verificar se está logado
Auth::verificarLoginAPI();
$usuarioId = Auth::getUsuarioId();

$model = new Notinha($usuarioId);

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
    
    try {
        if ($acao === 'parcial') {
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                exit;
            }
            
            $valorBruto = (string)($data['valor'] ?? '0');
            $valorNum = (float) str_replace([',', 'R$', ' '], ['.', '', ''], $valorBruto);
            
            if ($valorNum <= 0) {
                echo json_encode(['success' => false, 'error' => 'Valor inválido']);
                exit;
            }
            
            $model->registrarRecebimentoParcial($id, $valorNum, null);
            echo json_encode(['success' => true]);
        } elseif ($acao === 'receber') {
            // Mantém compatibilidade com fluxo antigo (recebimento total direto)
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                exit;
            }
            $result = $model->marcarComoRecebido($id);
            echo json_encode(['success' => $result]);
        } elseif ($acao === 'desfazer') {
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'ID inválido']);
                exit;
            }
            // Desfazer = voltar para lista ativa (remove recebido_at) - só do próprio usuário
            $pdo = \App\Core\Database::getInstance();
            $stmt = $pdo->prepare("UPDATE notinhas SET recebido_at = NULL WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $usuarioId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

