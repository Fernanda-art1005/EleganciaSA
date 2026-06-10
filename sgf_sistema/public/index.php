<?php
/**
 * SGF — Front Controller
 * Único ponto de entrada da aplicação.
 * Recebe toda requisição HTTP e despacha para o roteador interno.
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

// Autoloader PSR-4
require_once BASE_PATH . '/vendor/autoload.php';

// Captura da rota requisitada
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// --- Roteamento básico ---
switch (true) {

    // API: KPIs do Dashboard (JSON)
    case $uri === '/api/dashboard/kpis' && $method === 'GET':
        header('Content-Type: application/json; charset=utf-8');
        $ctrl = new \App\Controllers\DashboardController();
        echo json_encode($ctrl->getKpis());
        break;

    // Dashboard principal
    case $uri === '/' || $uri === '/dashboard':
        $ctrl = new \App\Controllers\DashboardController();
        $kpis = $ctrl->getKpis();
        require BASE_PATH . '/app/views/dashboard/index.php';
        break;

    // Listagem de ferramentas
    case $uri === '/ferramentas' && $method === 'GET':
        $modelo = new \App\Models\Ferramenta();
        $ferramentas = $modelo->listar([
            'status'    => $_GET['status']   ?? '',
            'categoria' => $_GET['categoria'] ?? '',
            'termo'     => $_GET['termo']     ?? '',
        ]);
        require BASE_PATH . '/app/views/ferramentas/listar.php';
        break;

    // Registrar saída de ferramenta (empréstimo)
    case $uri === '/emprestimos/saida' && $method === 'POST':
        header('Content-Type: application/json; charset=utf-8');
        $ctrl = new \App\Controllers\EmprestimoController();
        $ok = $ctrl->registrarSaida(
            (int) ($_POST['id_ferramenta'] ?? 0),
            (int) ($_POST['id_usuario']    ?? 0),
            (int) ($_POST['prazo_horas']   ?? 8)
        );
        echo json_encode(['sucesso' => $ok]);
        break;

    // 404
    default:
        http_response_code(404);
        echo '<h1>404 — Rota não encontrada</h1>';
        break;
}
