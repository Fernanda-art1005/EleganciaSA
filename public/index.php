<?php
/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

// Se for execução em servidor de desenvolvimento do PHP, permite servir arquivos estáticos diretamente
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (file_exists(__DIR__ . $path) && is_file(__DIR__ . $path)) {
        return false;
    }
}

// 1. Autoloader nativo compatível com a norma PSR-4 e robusto para sistemas com case-sensitivity (Linux/Docker)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    
    // Primeiro, tenta encontrar o arquivo mapeando diretórios intermediários para minúsculo (compatível com pastas físicas em minúsculo: config, controllers, models)
    $parts = explode('\\', $relative_class);
    for ($i = 0; $i < count($parts) - 1; $i++) {
        $parts[$i] = strtolower($parts[$i]);
    }
    $file = $base_dir . implode('/', $parts) . '.php';

    if (file_exists($file)) {
        require $file;
        return;
    }

    // Fallback: tenta o caminho original (caso haja pastas com capitalização na estrutura)
    $original_file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($original_file)) {
        require $original_file;
    }
});

// 2. Inicialização de Sessão Segura (configurado para compatibilidade com Iframe / SameSite=None; Secure)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'None'
    ]);
    session_start();
}

// 3. Roteamento Amigável (Front Controller)
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Detecção inteligente de Base Path para suporte a XAMPP (subdiretórios) e PHP CLI Server
$script_name = $_SERVER['SCRIPT_NAME'];
$dir = dirname($script_name);
$base_path = '';

if ($dir !== '/') {
    // Se a URI solicitada começar com o diretório do script, este é o nosso base_path (ex: XAMPP /elegancia_premium/public)
    if (strpos($request_uri, $dir) === 0) {
        $base_path = $dir;
    } else {
        // Se não, o PHP CLI Server está roteando de forma limpa a partir do root (ex: /estoque)
        $base_path = '';
    }
}

// Se a URL do request contém /index.php, nosso base_path para links e requisições deve incluí-lo
// para garantir funcionamento absoluto em servidores XAMPP sem mod_rewrite ativo
if (strpos($request_uri, '/index.php') !== false) {
    $base_path = rtrim($base_path, '/') . '/index.php';
}

define('BASE_PATH', $base_path);

$route = substr($request_uri, strlen($base_path));

// Normaliza barras consecutivas e barra final (evita erro 404 em URLs como /kanban/)
if ($route !== '/' && substr($route, -1) === '/') {
    $route = rtrim($route, '/');
}
if (empty($route)) {
    $route = '/';
}

// 4. Mapeamento de Rotas
$routes = [
    '/' => ['App\Controllers\DashboardController', 'index'],
    '/api/dashboard/kpis' => ['App\Controllers\DashboardController', 'apiKpis'],
    
    '/login' => ['App\Controllers\AuthController', 'login'],
    '/register' => ['App\Controllers\AuthController', 'register'],
    '/logout' => ['App\Controllers\AuthController', 'logout'],
    
    '/pdv' => ['App\Controllers\PDVController', 'index'],
    '/pdv/checkout' => ['App\Controllers\PDVController', 'checkout'],
    '/pdv/produtos' => ['App\Controllers\PDVController', 'searchProducts'],
    '/pdv/clientes' => ['App\Controllers\PDVController', 'searchClients'],
    '/pdv/excluir' => ['App\Controllers\PDVController', 'delete'],
    
    '/estoque' => ['App\Controllers\EstoqueController', 'index'],
    '/estoque/salvar' => ['App\Controllers\EstoqueController', 'save'],
    '/estoque/excluir' => ['App\Controllers\EstoqueController', 'delete'],
    
    '/financeiro' => ['App\Controllers\FinanceiroController', 'index'],
    '/financeiro/salvar' => ['App\Controllers\FinanceiroController', 'save'],
    '/financeiro/excluir' => ['App\Controllers\FinanceiroController', 'delete'],
    
    '/crm' => ['App\Controllers\CRMController', 'index'],
    '/crm/salvar' => ['App\Controllers\CRMController', 'save'],
    '/crm/excluir' => ['App\Controllers\CRMController', 'delete'],
    '/crm/receber' => ['App\Controllers\CRMController', 'receive'],
    
    '/kanban' => ['App\Controllers\TaskController', 'index'],
    '/kanban/salvar' => ['App\Controllers\TaskController', 'update'], // fallback
    '/kanban/excluir' => ['App\Controllers\TaskController', 'destroy'], // fallback
    '/kanban/mover' => ['App\Controllers\TaskController', 'move'],
    '/kanban/coluna/salvar' => ['App\Controllers\TaskController', 'saveColumn'],
    '/kanban/coluna/excluir' => ['App\Controllers\TaskController', 'deleteColumn'],
    
    '/tasks' => ['App\Controllers\TaskController', 'index'],
    '/task/create' => ['App\Controllers\TaskController', 'create'],
    '/task/store' => ['App\Controllers\TaskController', 'store'],
    '/task/edit' => ['App\Controllers\TaskController', 'edit'],
    '/task/update' => ['App\Controllers\TaskController', 'update'],
    '/task/destroy' => ['App\Controllers\TaskController', 'destroy'],
    '/task/concluir' => ['App\Controllers\TaskController', 'complete'],
    
    '/relatorio' => ['App\Controllers\RelatorioController', 'index'],
    '/relatorio/exportar' => ['App\Controllers\RelatorioController', 'export'],
    
    '/equipe' => ['App\Controllers\EquipeController', 'index'],
    '/equipe/salvar' => ['App\Controllers\EquipeController', 'save'],
    '/equipe/convidar' => ['App\Controllers\EquipeController', 'invite'],
    '/equipe/excluir' => ['App\Controllers\EquipeController', 'delete'],
];

// Resolutor dinâmico de rotas genéricas (/store, /salvar, /excluir, /deletar, /delete, /destroy, /update)
// Projetado para evitar qualquer 404 caso ações de formulário ou URLs sejam acionadas sem os prefixos corretos.
if (in_array($route, ['/store', '/salvar', '/excluir', '/deletar', '/delete', '/destroy', '/update'])) {
    $targetController = null;
    $targetMethod = null;
    
    // 1. Identificação com base nos parâmetros da requisição
    if (isset($_REQUEST['id_produto']) || isset($_POST['id_produto'])) {
        $targetController = 'App\Controllers\EstoqueController';
        $targetMethod = in_array($route, ['/excluir', '/deletar', '/delete', '/destroy']) ? 'delete' : 'save';
    } elseif (isset($_REQUEST['id_transacao']) || isset($_POST['id_transacao'])) {
        $targetController = 'App\Controllers\FinanceiroController';
        $targetMethod = in_array($route, ['/excluir', '/deletar', '/delete', '/destroy']) ? 'delete' : 'save';
    } elseif (isset($_REQUEST['id_cliente']) || isset($_POST['id_cliente'])) {
        $targetController = 'App\Controllers\CRMController';
        $targetMethod = in_array($route, ['/excluir', '/deletar', '/delete', '/destroy']) ? 'delete' : 'save';
    } elseif (isset($_REQUEST['id_venda']) || isset($_POST['id_venda'])) {
        $targetController = 'App\Controllers\PDVController';
        $targetMethod = 'delete';
    } elseif (isset($_REQUEST['id_tarefa']) || isset($_POST['id_tarefa']) || isset($_REQUEST['id_coluna']) || isset($_POST['id_coluna'])) {
        $targetController = 'App\Controllers\TaskController';
        if (in_array($route, ['/excluir', '/deletar', '/delete', '/destroy'])) {
            $targetMethod = isset($_REQUEST['id_coluna']) ? 'deleteColumn' : 'destroy';
        } else {
            $targetMethod = isset($_POST['id_coluna']) ? 'saveColumn' : ((isset($_POST['id_tarefa']) && !empty($_POST['id_tarefa'])) ? 'update' : 'store');
        }
    } elseif (isset($_REQUEST['id_usuario']) || isset($_POST['id_usuario'])) {
        $targetController = 'App\Controllers\EquipeController';
        $targetMethod = in_array($route, ['/excluir', '/deletar', '/delete', '/destroy']) ? 'delete' : 'save';
    }
    
    // 2. Identificação com base no HTTP Referer (origem do fluxo na UI)
    if (!$targetController) {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referer, '/estoque') !== false) {
            $targetController = 'App\Controllers\EstoqueController';
            $targetMethod = in_array($route, ['/excluir', '/deletar', '/delete', '/destroy']) ? 'delete' : 'save';
        } elseif (strpos($referer, '/financeiro') !== false) {
            $targetController = 'App\Controllers\FinanceiroController';
            $targetMethod = in_array($route, ['/excluir', '/deletar', '/delete', '/destroy']) ? 'delete' : 'save';
        } elseif (strpos($referer, '/crm') !== false) {
            $targetController = 'App\Controllers\CRMController';
            $targetMethod = in_array($route, ['/excluir', '/deletar', '/delete', '/destroy']) ? 'delete' : 'save';
        } elseif (strpos($referer, '/pdv') !== false) {
            $targetController = 'App\Controllers\PDVController';
            $targetMethod = in_array($route, ['/excluir', '/deletar', '/delete', '/destroy']) ? 'delete' : 'checkout';
        } elseif (strpos($referer, '/kanban') !== false || strpos($referer, '/tasks') !== false) {
            $targetController = 'App\Controllers\TaskController';
            if (in_array($route, ['/excluir', '/deletar', '/delete', '/destroy'])) {
                $targetMethod = 'destroy';
            } else {
                $targetMethod = (isset($_POST['id_tarefa']) && !empty($_POST['id_tarefa'])) ? 'update' : 'store';
            }
        } elseif (strpos($referer, '/equipe') !== false) {
            $targetController = 'App\Controllers\EquipeController';
            $targetMethod = in_array($route, ['/excluir', '/deletar', '/delete', '/destroy']) ? 'delete' : 'save';
        }
    }
    
    // 3. Fallbacks genéricos padrão para salvamento e exclusão
    if (!$targetController) {
        if (in_array($route, ['/store'])) {
            $targetController = 'App\Controllers\TaskController';
            $targetMethod = 'store';
        } elseif (in_array($route, ['/salvar', '/update'])) {
            $targetController = 'App\Controllers\TaskController';
            $targetMethod = 'store'; // fallback para criar tarefa
        } else {
            $targetController = 'App\Controllers\TaskController';
            $targetMethod = 'destroy';
        }
    }
    
    // Registra a rota resolvida dinamicamente na tabela
    $routes[$route] = [$targetController, $targetMethod];
}

if (array_key_exists($route, $routes)) {
    list($controllerClass, $method) = $routes[$route];
    try {
        $controller = new $controllerClass();
        $controller->$method();
    } catch (\Throwable $e) {
        http_response_code(500);
        echo "<h1>Erro 500 - Erro Interno do Servidor</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} else {
    // Rota não encontrada
    http_response_code(404);
    echo "<!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Erro 404 - Página Não Encontrada</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-[#F7F6F2] min-h-screen flex items-center justify-center font-sans'>
        <div class='text-center p-8 bg-white rounded-xl shadow-md border border-[#C4BC96]/35 max-w-md w-full'>
            <div class='text-5xl text-[#8DA574] mb-4'>📍</div>
            <h1 class='text-3xl font-serif text-gray-800 tracking-tight mb-2'>Erro 404</h1>
            <p class='text-gray-500 mb-6'>A página solicitada (" . htmlspecialchars($route) . ") não foi localizada no sistema.</p>
            <a href='" . htmlspecialchars($base_path) . "/' class='px-5 py-2.5 bg-[#8DA574] text-white hover:bg-[#849B48] font-medium rounded-lg shadow-sm transition-all duration-200 inline-block'>Voltar para o Início</a>
        </div>
    </body>
    </html>";
}
