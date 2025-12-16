<?php
namespace App\Core;

abstract class Controller
{
    protected function getJsonInput(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
    
    protected function getQueryParam(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
}

