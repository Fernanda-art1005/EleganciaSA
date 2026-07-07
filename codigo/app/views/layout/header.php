<?php
$basePath = defined('BASE_PATH') ? BASE_PATH : '';
$current_route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$relative_route = substr($current_route, strlen($basePath));
if ($relative_route !== '/' && substr($relative_route, -1) === '/') {
    $relative_route = rtrim($relative_route, '/');
}
if (empty($relative_route)) {
    $relative_route = '/';
}

$user_name = $_SESSION['user_name'] ?? 'Usuário';
$user_role = $_SESSION['nivel_acesso'] ?? 'CAIXA';
$perms = $_SESSION['perms'] ?? [];

if (!function_exists('getProductImageUrl')) {
    function getProductImageUrl($nome) {
        $term = mb_strtolower($nome, 'UTF-8');
        if (strpos($term, 'vestido') !== false) {
            return 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=300&auto=format&fit=crop&q=60';
        } elseif (strpos($term, 'blusa') !== false || strpos($term, 'seda') !== false) {
            return 'https://images.unsplash.com/photo-1548624149-f190c658d5f3?w=300&auto=format&fit=crop&q=60';
        } elseif (strpos($term, 'calça') !== false || strpos($term, 'calca') !== false) {
            return 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=300&auto=format&fit=crop&q=60';
        } elseif (strpos($term, 'bolsa') !== false || strpos($term, 'couro') !== false) {
            return 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=300&auto=format&fit=crop&q=60';
        } elseif (strpos($term, 'blazer') !== false || strpos($term, 'casaco') !== false || strpos($term, 'paletó') !== false || strpos($term, 'paleto') !== false) {
            return 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=300&auto=format&fit=crop&q=60';
        } elseif (strpos($term, 'brinco') !== false || strpos($term, 'anel') !== false || strpos($term, 'joia') !== false || strpos($term, 'jóia') !== false) {
            return 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=300&auto=format&fit=crop&q=60';
        } elseif (strpos($term, 'sapato') !== false || strpos($term, 'salto') !== false || strpos($term, 'scarpin') !== false) {
            return 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=300&auto=format&fit=crop&q=60';
        } elseif (strpos($term, 'perfume') !== false || strpos($term, 'fragrância') !== false || strpos($term, 'fragrancia') !== false) {
            return 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=300&auto=format&fit=crop&q=60';
        }
        return 'https://images.unsplash.com/photo-1544441893-675973e31985?w=300&auto=format&fit=crop&q=60';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegância Premium - Sistema de Controle e Gestão</title>
    <!-- Google Fonts (Playfair Display para cabeçalhos e Inter para a UI) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Tailwind Custom Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        sage: {
                            50: '#F4F7F2',
                            100: '#E6EEE2',
                            500: '#8DA574', // Cor principal Verde Sálvia
                            600: '#7B9363',
                        },
                        olive: {
                            500: '#849B48', // Verde Oliva
                            600: '#71853C',
                        },
                        khaki: {
                            500: '#BAAE79', // Caqui Claro
                            600: '#A39965',
                        },
                        sand: {
                            50: '#F7F6F2',
                            100: '#EFECE2',
                            500: '#C4BC96', // Bege Areia
                            600: '#AFA67E',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F5F5F0;
        }
        .font-serif-header {
            font-family: 'Playfair Display', serif;
        }
        /* Custom scrollbar for premium look */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #F5F5F0;
        }
        ::-webkit-scrollbar-thumb {
            background: #C4BC96;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #BAAE79;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col md:flex-row text-[#1A1A1A] bg-[#F5F5F0]">

    <!-- Mobile Top Navigation -->
    <div class="md:hidden w-full bg-[#8DA574] border-b border-[#849B48]/20 flex items-center justify-between px-4 py-3 z-30">
        <div class="flex items-center gap-2">
            <span class="text-2xl text-white font-serif font-bold">EP</span>
            <span class="font-serif text-lg font-bold tracking-tight text-white">Elegância Premium</span>
        </div>
        <button id="mobile-menu-btn" class="p-2 text-white/80 hover:text-white focus:outline-none">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>
    </div>

    <!-- Sidebar Layout -->
    <aside id="sidebar-menu" class="fixed inset-y-0 left-0 transform -translate-x-full md:translate-x-0 md:relative md:flex flex-col w-64 bg-[#8DA574] text-white border-r border-[#849B48]/20 min-h-screen z-40 transition-transform duration-300 ease-in-out">
        <!-- Logo Header -->
        <div class="p-6 border-b border-[#849B48]/20 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center border border-white/20">
                <i data-lucide="sparkles" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <h1 class="font-serif text-lg font-bold tracking-tight text-white leading-tight">Elegância</h1>
                <p class="text-xs font-serif uppercase tracking-widest text-[#F5F5F0]/70">Premium</p>
            </div>
        </div>

        <!-- Logged-in User Profile Card -->
        <div class="px-6 py-4 border-b border-[#849B48]/20 bg-white/5">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-[#BAAE79] text-[#1A1A1A] flex items-center justify-center text-xs font-semibold font-mono">
                    <?= strtoupper(substr($user_name, 0, 2)) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-sm font-medium text-white truncate" title="<?= htmlspecialchars($user_name) ?>"><?= htmlspecialchars($user_name) ?></p>
                    <span class="inline-block text-[10px] font-semibold font-mono tracking-wider px-2 py-0.5 rounded-full bg-white/10 text-white uppercase">
                        <?= htmlspecialchars($user_role) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
            <?php if (!empty($perms['dashboard'])): ?>
            <a href="<?= $basePath ?>/" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= ($relative_route === '/') ? 'bg-white/10 text-white font-semibold border-l-4 border-white' : 'text-[#F5F5F0]/80 hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                <span>Dashboard</span>
            </a>
            <?php endif; ?>

            <?php if (!empty($perms['caixa'])): ?>
            <a href="<?= $basePath ?>/pdv" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= (strpos($relative_route, '/pdv') === 0) ? 'bg-white/10 text-white font-semibold border-l-4 border-white' : 'text-[#F5F5F0]/80 hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="shopping-bag" class="w-4 h-4"></i>
                <span>Ponto de Venda (PDV)</span>
            </a>
            <?php endif; ?>

            <?php if (!empty($perms['estoque'])): ?>
            <a href="<?= $basePath ?>/estoque" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= (strpos($relative_route, '/estoque') === 0) ? 'bg-white/10 text-white font-semibold border-l-4 border-white' : 'text-[#F5F5F0]/80 hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="package" class="w-4 h-4"></i>
                <span>Estoque</span>
            </a>
            <?php endif; ?>

            <?php if (!empty($perms['financeiro'])): ?>
            <a href="<?= $basePath ?>/financeiro" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= (strpos($relative_route, '/financeiro') === 0) ? 'bg-white/10 text-white font-semibold border-l-4 border-white' : 'text-[#F5F5F0]/80 hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="dollar-sign" class="w-4 h-4"></i>
                <span>Financeiro</span>
            </a>
            <?php endif; ?>

            <?php if (!empty($perms['crm'])): ?>
            <a href="<?= $basePath ?>/crm" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= (strpos($relative_route, '/crm') === 0) ? 'bg-white/10 text-white font-semibold border-l-4 border-white' : 'text-[#F5F5F0]/80 hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span>Clientes (CRM)</span>
            </a>
            <?php endif; ?>

            <?php if (!empty($perms['kanban'])): ?>
            <a href="<?= $basePath ?>/kanban" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= (strpos($relative_route, '/kanban') === 0) ? 'bg-white/10 text-white font-semibold border-l-4 border-white' : 'text-[#F5F5F0]/80 hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="kanban" class="w-4 h-4"></i>
                <span>Tarefas (Kanban)</span>
            </a>
            <?php endif; ?>

            <?php if (!empty($perms['relatorios'])): ?>
            <a href="<?= $basePath ?>/relatorio" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= (strpos($relative_route, '/relatorio') === 0) ? 'bg-white/10 text-white font-semibold border-l-4 border-white' : 'text-[#F5F5F0]/80 hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Relatórios / Auditoria</span>
            </a>
            <?php endif; ?>

            <?php if (!empty($perms['equipe'])): ?>
            <a href="<?= $basePath ?>/equipe" class="flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm font-medium transition-all <?= (strpos($relative_route, '/equipe') === 0) ? 'bg-white/10 text-white font-semibold border-l-4 border-white' : 'text-[#F5F5F0]/80 hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                <span>Equipe / Permissões</span>
            </a>
            <?php endif; ?>
        </nav>

        <!-- Sidebar Footer Action -->
        <div class="p-4 border-t border-[#849B48]/20">
            <a href="<?= $basePath ?>/logout" class="flex items-center gap-3 px-4 py-2.5 w-full rounded-lg text-sm font-medium text-rose-100 hover:bg-white/5 hover:text-white transition-all">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span>Sair com Segurança</span>
            </a>
        </div>
    </aside>

    <!-- Overlay backdrops on mobile -->
    <div id="mobile-overlay" class="hidden fixed inset-0 bg-black/40 z-30 transition-opacity duration-300 md:hidden"></div>

    <!-- Main Content Area Container -->
    <main class="flex-1 flex flex-col min-w-0 min-h-screen">
        <!-- Toast Status Alerts (Success, Error, General alerts per RNF-012) -->
        <div class="px-6 pt-4 max-w-7xl mx-auto w-full">
            <?php if (!empty($_GET['sucesso'])): ?>
                <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-lg shadow-sm" role="alert" id="status-toast-success">
                    <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                    <span class="text-sm font-medium"><?= htmlspecialchars($_GET['sucesso']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($_GET['erro'])): ?>
                <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg shadow-sm" role="alert" id="status-toast-error">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600"></i>
                    <span class="text-sm font-medium"><?= htmlspecialchars($_GET['erro']) ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="p-6 md:p-8 flex-1 max-w-7xl w-full mx-auto">
