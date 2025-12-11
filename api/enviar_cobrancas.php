<?php
/**
 * Script para enviar cobranças automaticamente
 * 
 * Este script deve ser executado via CRON/Agendador de Tarefas
 * Exemplo de CRON: 0 7 * * * php /caminho/para/enviar_cobrancas.php
 * 
 * Para Windows (Agendador de Tarefas):
 * Executar: php C:\htdocs\htdocs\SistemaCobranca\api\enviar_cobrancas.php
 * Horário: 07:00 todos os dias
 */

require_once __DIR__ . '/../config/database.php';

// Permite execução via web também (para testes)
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}

try {
    $pdo = getConnection();

    // Busca configurações
    $stmt = $pdo->query("SELECT chave, valor FROM configuracoes");
    $config = [];
    while ($row = $stmt->fetch()) {
        $config[$row['chave']] = $row['valor'];
    }

    $chave_pix = $config['chave_pix'] ?? '';
    $nome_vendedor = $config['nome_vendedor'] ?? 'Filipe que vende requeijão e doces';

    // Data de hoje
    $hoje = date('Y-m-d');

    // Busca clientes que precisam receber cobrança hoje
    $stmt = $pdo->prepare("
        SELECT 
            nc.id,
            nc.nome,
            nc.valor,
            nc.telefone,
            n.id as notinha_id
        FROM notinha_clientes nc
        JOIN notinhas n ON nc.notinha_id = n.id
        WHERE n.data_cobranca = ?
        AND nc.msg_enviada = 0
        AND nc.telefone != ''
    ");
    $stmt->execute([$hoje]);
    $clientes = $stmt->fetchAll();

    $enviados = [];
    $erros = [];

    foreach ($clientes as $cliente) {
        // Formata telefone
        $telefone = preg_replace('/\D/', '', $cliente['telefone']);
        if (strlen($telefone) == 11 && substr($telefone, 0, 2) != '55') {
            $telefone = '55' . $telefone;
        } elseif (strlen($telefone) == 10) {
            $telefone = '55' . $telefone;
        }

        // Formata valor
        $valor = 'R$ ' . number_format($cliente['valor'], 2, ',', '.');

        // Monta mensagem
        $mensagem = "Bom dia {$cliente['nome']} tudo bem? {$nome_vendedor}, passando para deixar meu pix e o valor dos produtos 🙏 {$valor} Chave pix {$chave_pix}";

        // Aqui você pode integrar com uma API de WhatsApp
        // Exemplos de APIs:
        // - Evolution API (gratuita, self-hosted)
        // - Z-API
        // - Twilio
        // - Meta Business API
        
        // Por enquanto, vamos apenas marcar como enviado e logar
        // Você precisará configurar a integração com sua API de WhatsApp preferida
        
        $resultado = enviarWhatsApp($telefone, $mensagem);

        if ($resultado['success']) {
            // Marca como enviado
            $stmt = $pdo->prepare("
                UPDATE notinha_clientes 
                SET msg_enviada = 1, data_envio = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$cliente['id']]);

            $enviados[] = [
                'nome' => $cliente['nome'],
                'telefone' => $telefone
            ];
        } else {
            $erros[] = [
                'nome' => $cliente['nome'],
                'telefone' => $telefone,
                'erro' => $resultado['error']
            ];
        }
    }

    // Marca notinhas como enviadas se todos os clientes foram enviados
    $stmt = $pdo->query("
        UPDATE notinhas n
        SET enviada = 1
        WHERE NOT EXISTS (
            SELECT 1 FROM notinha_clientes nc 
            WHERE nc.notinha_id = n.id AND nc.msg_enviada = 0
        )
        AND enviada = 0
    ");

    $response = [
        'success' => true,
        'data' => $hoje,
        'total_clientes' => count($clientes),
        'enviados' => count($enviados),
        'erros' => count($erros),
        'detalhes_enviados' => $enviados,
        'detalhes_erros' => $erros
    ];

    if (php_sapi_name() === 'cli') {
        echo "=== Envio de Cobranças ===\n";
        echo "Data: {$hoje}\n";
        echo "Total de clientes: " . count($clientes) . "\n";
        echo "Enviados: " . count($enviados) . "\n";
        echo "Erros: " . count($erros) . "\n";
        
        if (!empty($erros)) {
            echo "\nErros:\n";
            foreach ($erros as $erro) {
                echo "- {$erro['nome']}: {$erro['erro']}\n";
            }
        }
    } else {
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

} catch (Exception $e) {
    $error = ['error' => $e->getMessage()];
    
    if (php_sapi_name() === 'cli') {
        echo "ERRO: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode($error);
    }
}

/**
 * Função para enviar WhatsApp
 * CONFIGURE AQUI SUA INTEGRAÇÃO COM API DE WHATSAPP
 */
function enviarWhatsApp($telefone, $mensagem) {
    // =====================================================
    // OPÇÃO 1: Evolution API (Gratuita - Self Hosted)
    // =====================================================
    // $evolution_url = 'http://localhost:8080';
    // $instance = 'sua_instancia';
    // $apikey = 'sua_apikey';
    // 
    // $response = file_get_contents($evolution_url . '/message/sendText/' . $instance, false, stream_context_create([
    //     'http' => [
    //         'method' => 'POST',
    //         'header' => "Content-Type: application/json\r\napikey: {$apikey}\r\n",
    //         'content' => json_encode([
    //             'number' => $telefone,
    //             'text' => $mensagem
    //         ])
    //     ]
    // ]));
    // return json_decode($response, true);

    // =====================================================
    // OPÇÃO 2: Z-API (Paga - Fácil de usar)
    // =====================================================
    // $zapi_instance = 'sua_instancia';
    // $zapi_token = 'seu_token';
    // 
    // $ch = curl_init("https://api.z-api.io/instances/{$zapi_instance}/token/{$zapi_token}/send-text");
    // curl_setopt_array($ch, [
    //     CURLOPT_POST => true,
    //     CURLOPT_RETURNTRANSFER => true,
    //     CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    //     CURLOPT_POSTFIELDS => json_encode([
    //         'phone' => $telefone,
    //         'message' => $mensagem
    //     ])
    // ]);
    // $response = curl_exec($ch);
    // curl_close($ch);
    // return json_decode($response, true);

    // =====================================================
    // MODO SIMULAÇÃO (Apenas para testes)
    // Remove esta parte quando configurar uma API real
    // =====================================================
    
    // Salva em um arquivo de log para você ver as mensagens que seriam enviadas
    $log = date('Y-m-d H:i:s') . " | Para: {$telefone} | Msg: {$mensagem}\n";
    file_put_contents(__DIR__ . '/../logs/whatsapp.log', $log, FILE_APPEND | LOCK_EX);
    
    return [
        'success' => true,
        'modo' => 'simulacao',
        'message' => 'Mensagem registrada no log (configure uma API de WhatsApp para envio real)'
    ];
}

