<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegância Premium - Entrar no Sistema</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <!-- Tailwind CSS Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        serif: ['Playfair Display', 'serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-[#F5F5F0] min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <!-- Logo and Brand -->
        <div class="text-center mb-8">
            <h1 class="font-serif text-3xl font-extrabold tracking-tight text-gray-800">Elegância Premium</h1>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-[#C4BC96]/10 border border-[#C4BC96]/20 overflow-hidden">
            <div class="p-8">
                <!-- Status Toast/Message boxes -->
                <?php if (!empty($erro)): ?>
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-xs font-semibold flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0"></i>
                        <span><?= htmlspecialchars($erro) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_GET['sucesso'])): ?>
                    <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs font-semibold flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-500 shrink-0"></i>
                        <span><?= htmlspecialchars($_GET['sucesso']) ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?= $this->base_path ?>/login" method="POST" class="space-y-4">
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Login</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </span>
                            <input type="text" id="email" name="email" required placeholder="Digite seu nome" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all duration-200">
                        </div>
                    </div>

                    <div>
                        <label for="senha" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Senha</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </span>
                            <input type="password" id="senha" name="senha" required placeholder="••••••••"
                                class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all duration-200">
                        </div>
                    </div>

                    <button type="submit" 
                        class="w-full py-3 px-4 bg-[#8DA574] hover:bg-[#849B48] text-white text-sm font-medium rounded-xl shadow-md shadow-[#8DA574]/15 hover:shadow-lg hover:shadow-[#849B48]/20 transition-all duration-200 focus:outline-none flex items-center justify-center gap-2">
                        <span>Entrar</span>
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </form>

            </div>
        </div>

        <div class="text-center mt-6">
            <p class="text-xs text-gray-500">Copyright &copy; 2026 Elegância Premium. Todos os direitos reservados.</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>
