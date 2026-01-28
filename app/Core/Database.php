<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    
    private function __construct() {}
    
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            // Carrega configurações
            $configFile = __DIR__ . '/../../config.php';
            if (file_exists($configFile)) {
                require_once $configFile;
            }
            
            // Usa constantes ou valores padrão
            $host = defined('DB_HOST') ? DB_HOST : 'localhost';
            $dbname = defined('DB_NAME') ? DB_NAME : 'sistema_cobranca';
            $username = defined('DB_USER') ? DB_USER : 'root';
            $password = defined('DB_PASS') ? DB_PASS : '';
            
            try {
                $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
                self::$instance = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (PDOException $e) {
                if (defined('APP_DEBUG') && APP_DEBUG) {
                    Response::json(['error' => 'Erro de conexão: ' . $e->getMessage()], 500);
                } else {
                    Response::json(['error' => 'Erro de conexão com o banco de dados'], 500);
                }
                exit;
            }
        }
        
        return self::$instance;
    }
}

