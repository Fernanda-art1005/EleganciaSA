<?php
/**
 * Front Controller - Ponto de entrada único do sistema SGF.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

// Exemplo básico de roteamento para demonstração (conforme o relatório sugere URLs amigáveis)
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Simulação de roteamento básico
if ($path === '/' || $path === '/dashboard') {
    require_once __DIR__ . '/../app/views/dashboard/index.php';
} elseif ($path === '/ferramentas') {
    require_once __DIR__ . '/../app/views/ferramentas/listar.php';
} else {
    // Caso não encontre a rota, poderia redirecionar ou mostrar 404
    echo "<h1>404 - Página não encontrada</h1>";
    echo "<p>O SGF está operando. Rota solicitada: " . htmlspecialchars($path) . "</p>";
}
