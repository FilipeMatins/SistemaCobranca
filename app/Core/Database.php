<?php
namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    
    private static string $host = 'localhost';
    private static string $dbname = 'sistema_cobranca';
    private static string $username = 'root';
    private static string $password = '';
    
    private function __construct() {}
    
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8mb4";
                self::$instance = new PDO($dsn, self::$username, self::$password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (PDOException $e) {
                Response::json(['error' => 'Erro de conexão com o banco de dados'], 500);
                exit;
            }
        }
        
        return self::$instance;
    }
    
    public static function setConfig(string $host, string $dbname, string $username, string $password): void
    {
        self::$host = $host;
        self::$dbname = $dbname;
        self::$username = $username;
        self::$password = $password;
        self::$instance = null;
    }
}

