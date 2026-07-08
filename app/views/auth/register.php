<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegância Premium - Registrar-se</title>
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
<body class="bg-[#F7F6F2] min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <!-- Logo and Brand -->
        <div class="text-center mb-8">
            <div class="w-14 h-14 rounded-full bg-white border border-[#C4BC96]/40 flex items-center justify-center mx-auto shadow-inner mb-3">
                <i data-lucide="sparkles" class="w-6 h-6 text-[#8DA574]"></i>
            </div>
            <h1 class="font-serif text-3xl font-extrabold tracking-tight text-gray-800">Elegância Premium</h1>
            <p class="text-xs uppercase tracking-widest text-[#BAAE79] font-semibold mt-1">Onboarding de Colaborador</p>
        </div>

        <!-- Register Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-[#C4BC96]/10 border border-[#C4BC96]/20 overflow-hidden">
            <div class="p-8">
                <h2 class="font-serif text-xl font-bold text-gray-800 mb-1">Criar sua Conta</h2>
                
                <?php if ($invite): ?>
                    <p class="text-xs text-sage-600 font-semibold mb-6 flex items-center gap-1.5 bg-sage-50 border border-sage-100 p-3 rounded-lg">
                        <i data-lucide="mail-open" class="w-4 h-4"></i>
                        <span>Você foi convidado para assumir o cargo de <strong><?= htmlspecialchars($invite['perfil']) ?></strong>!</span>
                    </p>
                <?php else: ?>
                    <p class="text-xs text-gray-500 mb-6">Insira seus dados para obter credenciais no sistema.</p>
                <?php endif; ?>

                <!-- Status Toast/Message boxes -->
                <?php if (!empty($erro)): ?>
                    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-xl text-xs font-semibold flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0"></i>
                        <span><?= htmlspecialchars($erro) ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?= $this->base_path ?>/register<?= $token ? '?token=' . urlencode($token) : '' ?>" method="POST" class="space-y-4">
                    <div>
                        <label for="nome" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Nome Completo</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i data-lucide="user" class="w-4 h-4"></i>
                            </span>
                            <input type="text" id="nome" name="nome" required placeholder="ex: João da Silva" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>"
                                class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all duration-200">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">E-mail Corporativo</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i data-lucide="mail" class="w-4 h-4"></i>
                            </span>
                            <input type="email" id="email" name="email" required placeholder="ex: joao@elegancia.com" 
                                   value="<?= htmlspecialchars($invite ? $invite['email'] : ($_POST['email'] ?? '')) ?>" 
                                   <?= $invite ? 'readonly class="block w-full pl-10 pr-4 py-3 bg-gray-100 border border-sand-100 text-gray-500 cursor-not-allowed rounded-xl text-sm font-mono"' : 'class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all duration-200"' ?>>
                        </div>
                    </div>

                    <?php if (!$invite): ?>
                    <div>
                        <label for="nivel_acesso" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Nível de Acesso (Cargo)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i data-lucide="shield" class="w-4 h-4"></i>
                            </span>
                            <select id="nivel_acesso" name="nivel_acesso" required
                                    class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all duration-200 appearance-none">
                                <option value="CAIXA">Operador de Caixa (Vendas / PDV)</option>
                                <option value="ESTOQUE">Responsável pelo Estoque</option>
                                <option value="ADMIN">Administrador / Gerente</option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div>
                        <label for="senha" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Senha de Acesso</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i data-lucide="lock" class="w-4 h-4"></i>
                            </span>
                            <input type="password" id="senha" name="senha" required placeholder="Mínimo 6 caracteres"
                                class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all duration-200">
                        </div>
                    </div>

                    <div>
                        <label for="confirmar_senha" class="block text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1.5">Confirmar Senha</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i data-lucide="shield-check" class="w-4 h-4"></i>
                            </span>
                            <input type="password" id="confirmar_senha" name="confirmar_senha" required placeholder="Repita a senha"
                                class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all duration-200">
                        </div>
                    </div>

                    <button type="submit" 
                        class="w-full py-3 px-4 bg-[#8DA574] hover:bg-[#849B48] text-white text-sm font-medium rounded-xl shadow-md shadow-[#8DA574]/15 hover:shadow-lg hover:shadow-[#849B48]/20 transition-all duration-200 focus:outline-none flex items-center justify-center gap-2">
                        <span>Registrar Minha Conta</span>
                        <i data-lucide="check" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
            
            <div class="p-4 bg-sand-50/50 border-t border-[#C4BC96]/15 text-center">
                <p class="text-xs text-gray-500">Já possui uma conta? <a href="<?= $this->base_path ?>/login" class="text-[#8DA574] hover:underline font-semibold">Fazer Login</a></p>
            </div>
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
