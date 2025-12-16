<?php
namespace App\Core;

class Response
{
    public static function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    public static function success($data = null, string $message = 'Sucesso', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    public static function error(string $message, int $statusCode = 400): void
    {
        self::json([
            'success' => false,
            'error' => $message
        ], $statusCode);
    }
    
    public static function created($data, string $message = 'Criado com sucesso'): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 201);
    }
    
    public static function notFound(string $message = 'Não encontrado'): void
    {
        self::error($message, 404);
    }
    
    public static function methodNotAllowed(): void
    {
        self::error('Método não permitido', 405);
    }
}

