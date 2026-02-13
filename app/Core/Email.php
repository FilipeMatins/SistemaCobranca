<?php
namespace App\Core;

/**
 * Classe para envio de emails via SMTP
 */

class Email {
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromName;
    private $fromAddress;
    
    public function __construct() {
        // Carregar configurações
        $configFile = __DIR__ . '/../../config_email.php';
        if (file_exists($configFile)) {
            require_once $configFile;
        }
        
        $this->host = defined('EMAIL_HOST') ? EMAIL_HOST : 'smtp.gmail.com';
        $this->port = defined('EMAIL_PORT') ? EMAIL_PORT : 587;
        $this->username = defined('EMAIL_USERNAME') ? EMAIL_USERNAME : '';
        $this->password = defined('EMAIL_PASSWORD') ? EMAIL_PASSWORD : '';
        $this->fromName = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Sistema';
        $this->fromAddress = defined('EMAIL_FROM_ADDRESS') ? EMAIL_FROM_ADDRESS : '';
    }
    
    /**
     * Enviar email via SMTP
     */
    public function enviar($para, $assunto, $mensagem, $html = false) {
        // Verificar se tem configurações
        if (empty($this->username) || empty($this->password)) {
            return ['erro' => 'Email não configurado'];
        }
        
        try {
            // Conectar ao servidor SMTP
            $socket = @fsockopen('ssl://' . $this->host, 465, $errno, $errstr, 30);
            
            if (!$socket) {
                // Tentar porta 587 com STARTTLS
                $socket = @fsockopen($this->host, 587, $errno, $errstr, 30);
                if (!$socket) {
                    throw new Exception("Não foi possível conectar ao servidor de email: $errstr ($errno)");
                }
                
                // Ler resposta inicial
                $this->getResponse($socket);
                
                // EHLO
                $this->sendCommand($socket, "EHLO localhost");
                
                // STARTTLS
                $this->sendCommand($socket, "STARTTLS");
                
                // Ativar criptografia
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                // EHLO novamente após TLS
                $this->sendCommand($socket, "EHLO localhost");
            } else {
                // Conexão SSL direta
                $this->getResponse($socket);
                $this->sendCommand($socket, "EHLO localhost");
            }
            
            // Autenticação
            $this->sendCommand($socket, "AUTH LOGIN");
            $this->sendCommand($socket, base64_encode($this->username));
            $this->sendCommand($socket, base64_encode($this->password));
            
            // Remetente
            $this->sendCommand($socket, "MAIL FROM:<{$this->fromAddress}>");
            
            // Destinatário
            $this->sendCommand($socket, "RCPT TO:<{$para}>");
            
            // Dados
            $this->sendCommand($socket, "DATA");
            
            // Cabeçalhos e corpo
            $contentType = $html ? 'text/html' : 'text/plain';
            $headers = "From: {$this->fromName} <{$this->fromAddress}>\r\n";
            $headers .= "To: {$para}\r\n";
            $headers .= "Subject: {$assunto}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
            $headers .= "\r\n";
            $headers .= $mensagem;
            $headers .= "\r\n.";
            
            $this->sendCommand($socket, $headers);
            
            // Encerrar
            $this->sendCommand($socket, "QUIT");
            fclose($socket);
            
            return ['sucesso' => true];
            
        } catch (Exception $e) {
            return ['erro' => $e->getMessage()];
        }
    }
    
    /**
     * Enviar comando SMTP
     */
    private function sendCommand($socket, $command) {
        fwrite($socket, $command . "\r\n");
        return $this->getResponse($socket);
    }
    
    /**
     * Obter resposta do servidor
     */
    private function getResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $response;
    }
    
    /**
     * Enviar email de recuperação de senha
     */
    public function enviarRecuperacaoSenha($para, $nome, $link) {
        $assunto = "🔐 Recuperação de Senha - Bloco de Cobranças";
        
        $mensagem = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8fafc; padding: 30px; border: 1px solid #e2e8f0; }
                .button { display: inline-block; background: #3b82f6; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 20px 0; }
                .footer { background: #1e293b; color: #94a3b8; padding: 20px; text-align: center; font-size: 12px; border-radius: 0 0 10px 10px; }
                .warning { background: #fef3c7; border: 1px solid #f59e0b; padding: 15px; border-radius: 8px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>📝 Bloco de Cobranças</h1>
                </div>
                <div class='content'>
                    <h2>Olá, {$nome}!</h2>
                    <p>Você solicitou a recuperação de senha da sua conta.</p>
                    <p>Clique no botão abaixo para criar uma nova senha:</p>
                    
                    <center>
                        <a href='{$link}' class='button'>🔑 Criar Nova Senha</a>
                    </center>
                    
                    <div class='warning'>
                        ⚠️ <strong>Importante:</strong> Este link expira em <strong>1 hora</strong>.
                    </div>
                    
                    <p>Se você não solicitou esta recuperação, ignore este email. Sua senha permanecerá a mesma.</p>
                    
                    <p>Se o botão não funcionar, copie e cole este link no navegador:</p>
                    <p style='word-break: break-all; background: #e2e8f0; padding: 10px; border-radius: 5px; font-size: 12px;'>{$link}</p>
                </div>
                <div class='footer'>
                    <p>Este é um email automático. Por favor, não responda.</p>
                    <p>© " . date('Y') . " Bloco de Cobranças</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->enviar($para, $assunto, $mensagem, true);
    }
    
    /**
     * Enviar email de boas-vindas
     */
    public function enviarBoasVindas($para, $nome) {
        $assunto = "🎉 Bem-vindo ao Bloco de Cobranças!";
        
        $mensagem = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #22c55e, #16a34a); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f8fafc; padding: 30px; border: 1px solid #e2e8f0; }
                .feature { display: flex; align-items: center; margin: 15px 0; padding: 15px; background: white; border-radius: 8px; }
                .feature-icon { font-size: 24px; margin-right: 15px; }
                .footer { background: #1e293b; color: #94a3b8; padding: 20px; text-align: center; font-size: 12px; border-radius: 0 0 10px 10px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Bem-vindo!</h1>
                </div>
                <div class='content'>
                    <h2>Olá, {$nome}!</h2>
                    <p>Sua conta foi criada com sucesso no <strong>Bloco de Cobranças</strong>!</p>
                    
                    <p>Com o sistema você pode:</p>
                    
                    <div class='feature'>
                        <span class='feature-icon'>📝</span>
                        <div>Criar e gerenciar suas notinhas de cobrança</div>
                    </div>
                    
                    <div class='feature'>
                        <span class='feature-icon'>💬</span>
                        <div>Enviar cobranças pelo WhatsApp com um clique</div>
                    </div>
                    
                    <div class='feature'>
                        <span class='feature-icon'>📊</span>
                        <div>Acompanhar seus recebimentos no Dashboard</div>
                    </div>
                    
                    <div class='feature'>
                        <span class='feature-icon'>👥</span>
                        <div>Gerenciar seus clientes</div>
                    </div>
                    
                    <p>Acesse agora e comece a usar!</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Bloco de Cobranças</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        return $this->enviar($para, $assunto, $mensagem, true);
    }
}

