<?php
/**
 * Autoloader para classes do namespace App
 */
spl_autoload_register(function ($class) {
    // Verifica se a classe pertence ao namespace App
    if (strpos($class, 'App\\') !== 0) {
        return;
    }
    
    // Remove o prefixo 'App\' e converte para caminho do arquivo
    $relativeClass = substr($class, 4);
    $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});



