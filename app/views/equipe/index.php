<div class="space-y-8">

    <!-- Split layout for team management -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        
        <!-- Left: Team list with full RBAC editing capabilities (RF-EQ-005 / RN-AC-003) -->
        <div class="xl:col-span-2 bg-white p-6 rounded-2xl border border-sand-100 shadow-sm flex flex-col">
            <div class="mb-6">
                <h1 class="font-serif text-2xl font-bold text-gray-800">Equipe & Controle de Acesso (RBAC)</h1>
                <p class="text-xs text-gray-400">Gerencie cargos e conceda acessos específicos para cada módulo comercial.</p>
            </div>

            <!-- Members Directory list with checkboxes form -->
            <div class="space-y-6">
                <?php foreach ($membros as $memb): 
                    $isSelf = $memb['id_usuario'] === (int)$_SESSION['user_id'];
                ?>
                    <form action="<?= $basePath ?>/equipe/salvar" method="POST" class="p-5 bg-sand-50/45 rounded-2xl border border-sand-100/50 flex flex-col gap-4">
                        <input type="hidden" name="id_usuario" value="<?= $memb['id_usuario'] ?>">
                        
                        <!-- Top header of member row -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-sand-100 pb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-[#BAAE79] text-white flex items-center justify-center font-bold text-xs font-mono">
                                    <?= strtoupper(substr($memb['nome'], 0, 2)) ?>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-800"><?= htmlspecialchars($memb['nome']) ?> <?= $isSelf ? '<span class="text-[9px] font-bold text-[#8DA574] bg-sage-50 border border-sage-100 px-1.5 py-0.5 rounded-md ml-1.5">Você</span>' : '' ?></h3>
                                    <p class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($memb['email']) ?></p>
                                    
                                    <!-- Comissões e Vendas Acumuladas -->
                                    <div class="flex items-center gap-3 text-[10px] mt-2 bg-white/85 px-2.5 py-1 rounded-lg border border-sand-100/50 w-fit">
                                        <span class="text-gray-500">Vendas: <strong class="text-gray-700 font-mono">R$ <?= number_format($memb['total_vendas'] ?? 0, 2, ',', '.') ?></strong></span>
                                        <span class="text-gray-300">|</span>
                                        <span class="text-sage-600">Comissão (5%): <strong class="font-mono text-[#8DA574] font-bold">R$ <?= number_format($memb['comissao_acumulada'] ?? 0, 2, ',', '.') ?></strong></span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5">
                                <!-- Profile select (RF-EQ-005) -->
                                <select name="nivel_acesso" required <?= $isSelf ? 'disabled class="text-[10px] font-bold bg-gray-100 border border-sand-100 text-gray-400 cursor-not-allowed px-2.5 py-1.5 rounded-lg"' : 'class="text-[10px] font-bold bg-white border border-[#C4BC96]/30 text-gray-700 px-2.5 py-1.5 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8DA574]"' ?>>
                                    <option value="ADMIN" <?= ($memb['nivel_acesso'] === 'ADMIN') ? 'selected' : '' ?>>Administrador</option>
                                    <option value="CAIXA" <?= ($memb['nivel_acesso'] === 'CAIXA') ? 'selected' : '' ?>>Operador de Caixa</option>
                                    <option value="ESTOQUE" <?= ($memb['nivel_acesso'] === 'ESTOQUE') ? 'selected' : '' ?>>Estoque / Almoxarifado</option>
                                </select>

                                <!-- Status / Permissão de Entrada -->
                                <select name="status" required <?= $isSelf ? 'disabled class="text-[10px] font-bold bg-gray-100 border border-sand-100 text-gray-400 cursor-not-allowed px-2.5 py-1.5 rounded-lg"' : 'class="text-[10px] font-bold bg-white border border-[#C4BC96]/30 text-gray-700 px-2.5 py-1.5 rounded-lg focus:outline-none focus:ring-1 focus:ring-[#8DA574]"' ?>>
                                    <option value="ATIVO" <?= ($memb['status'] === 'ATIVO') ? 'selected' : '' ?>>Ativo (Acesso Liberado)</option>
                                    <option value="INATIVO" <?= ($memb['status'] === 'INATIVO') ? 'selected' : '' ?>>Inativo / Pendente (Bloqueado)</option>
                                </select>

                                <!-- Delete button (RN-AC-004 auto block) -->
                                <?php if (!$isSelf): ?>
                                    <a href="<?= $basePath ?>/equipe/excluir?id_usuario=<?= $memb['id_usuario'] ?>" 
                                       onclick="return confirm('Deseja realmente desligar o colaborador \'<?= htmlspecialchars(addslashes($memb['nome'])) ?>\' do sistema?')"
                                       class="p-2 hover:bg-rose-50 text-rose-500 hover:text-rose-600 rounded-lg transition-all"
                                       title="Remover Colaborador">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Granular permissions grid (RN-AC-003 / RF-EQ-005) -->
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-wider text-[#BAAE79] mb-3 flex items-center gap-1">
                                <i data-lucide="shield-alert" class="w-3.5 h-3.5"></i>
                                <span>Permissões Modulares Granulares</span>
                            </p>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-[11px] text-gray-600 font-medium">
                                <label class="flex items-center gap-2 cursor-pointer hover:text-gray-900">
                                    <input type="checkbox" name="perm_dashboard" value="1" <?= $memb['perm_dashboard'] ? 'checked' : '' ?> <?= $isSelf ? 'disabled class="rounded border-sand-300 text-sage-500 focus:ring-sage-500 cursor-not-allowed"' : 'class="rounded border-sand-300 text-sage-500 focus:ring-sage-500"' ?>>
                                    <span>Dashboard</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-gray-900">
                                    <input type="checkbox" name="perm_caixa" value="1" <?= $memb['perm_caixa'] ? 'checked' : '' ?> <?= $isSelf ? 'disabled class="rounded border-sand-300 text-sage-500 focus:ring-sage-500 cursor-not-allowed"' : 'class="rounded border-sand-300 text-sage-500 focus:ring-sage-500"' ?>>
                                    <span>Caixa / PDV</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-gray-900">
                                    <input type="checkbox" name="perm_estoque" value="1" <?= $memb['perm_estoque'] ? 'checked' : '' ?> <?= $isSelf ? 'disabled class="rounded border-sand-300 text-sage-500 focus:ring-sage-500 cursor-not-allowed"' : 'class="rounded border-sand-300 text-sage-500 focus:ring-sage-500"' ?>>
                                    <span>Estoque</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-gray-900">
                                    <input type="checkbox" name="perm_financeiro" value="1" <?= $memb['perm_financeiro'] ? 'checked' : '' ?> <?= $isSelf ? 'disabled class="rounded border-sand-300 text-sage-500 focus:ring-sage-500 cursor-not-allowed"' : 'class="rounded border-sand-300 text-sage-500 focus:ring-sage-500"' ?>>
                                    <span>Financeiro</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-gray-900">
                                    <input type="checkbox" name="perm_crm" value="1" <?= $memb['perm_crm'] ? 'checked' : '' ?> <?= $isSelf ? 'disabled class="rounded border-sand-300 text-sage-500 focus:ring-sage-500 cursor-not-allowed"' : 'class="rounded border-sand-300 text-sage-500 focus:ring-sage-500"' ?>>
                                    <span>CRM / Clientes</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-gray-900">
                                    <input type="checkbox" name="perm_kanban" value="1" <?= $memb['perm_kanban'] ? 'checked' : '' ?> <?= $isSelf ? 'disabled class="rounded border-sand-300 text-sage-500 focus:ring-sage-500 cursor-not-allowed"' : 'class="rounded border-sand-300 text-sage-500 focus:ring-sage-500"' ?>>
                                    <span>Quadro Kanban</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-gray-900">
                                    <input type="checkbox" name="perm_relatorios" value="1" <?= $memb['perm_relatorios'] ? 'checked' : '' ?> <?= $isSelf ? 'disabled class="rounded border-sand-300 text-sage-500 focus:ring-sage-500 cursor-not-allowed"' : 'class="rounded border-sand-300 text-sage-500 focus:ring-sage-500"' ?>>
                                    <span>Relatórios</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer hover:text-gray-900">
                                    <input type="checkbox" name="perm_equipe" value="1" <?= $memb['perm_equipe'] ? 'checked' : '' ?> <?= $isSelf ? 'disabled class="rounded border-sand-300 text-sage-500 focus:ring-sage-500 cursor-not-allowed"' : 'class="rounded border-sand-300 text-sage-500 focus:ring-sage-500"' ?>>
                                    <span>Equipe</span>
                                </label>
                            </div>
                        </div>

                        <!-- Update button for row -->
                        <?php if (!$isSelf): ?>
                            <div class="flex justify-end pt-2">
                                <button type="submit" class="py-1.5 px-3 bg-sage-500 hover:bg-sage-600 text-white font-semibold rounded-lg text-[10px] shadow-sm hover:shadow transition-all flex items-center gap-1">
                                    <i data-lucide="check" class="w-3 h-3"></i>
                                    <span>Salvar Permissões de <?= explode(' ', $memb['nome'])[0] ?></span>
                                </button>
                            </div>
                        <?php endif; ?>

                    </form>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right Side: SMTP simulated Team Invitation (RF-EQ-001 / RF-EQ-002) -->
        <div class="space-y-6">
            
            <div class="bg-white p-6 rounded-2xl border border-sand-100 shadow-sm">
                <div class="border-b border-sand-50 pb-4 mb-4">
                    <h2 class="font-serif text-lg font-bold text-gray-800">Convidar Colaborador</h2>
                    <p class="text-xs text-gray-400">Gere um link de onboarding de equipe com cargo predefinido.</p>
                </div>

                <form action="<?= $basePath ?>/equipe/convidar" method="POST" class="space-y-4">
                    <div>
                        <label for="email" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">E-mail Corporativo do Convidado</label>
                        <input type="email" id="email" name="email" required placeholder="ex: colaborador@elegancia.com"
                               class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                    </div>

                    <div>
                        <label for="perfil" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Perfil de Onboarding</label>
                        <select id="perfil" name="perfil" required
                                class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                            <option value="CAIXA">Operador de Caixa (Vendas / PDV)</option>
                            <option value="ESTOQUE">Responsável pelo Estoque</option>
                            <option value="ADMIN">Administrador / Gerente</option>
                        </select>
                    </div>

                    <button type="submit" 
                            class="w-full py-2.5 px-4 bg-[#8DA574] hover:bg-[#849B48] text-white text-xs font-semibold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-1.5">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Gerar Convite de Integração</span>
                    </button>
                </form>

                <!-- If invite link was just generated (RF-EQ-003) -->
                <?php if (!empty($_GET['invite_link'])): ?>
                    <div class="mt-5 p-4 bg-sage-50 border border-sage-100 rounded-xl text-[11px] space-y-2 text-gray-700 animate-pulse">
                        <p class="font-bold text-sage-700 flex items-center gap-1">
                            <i data-lucide="mail-open" class="w-4 h-4"></i>
                            <span>Link Gerado com Sucesso!</span>
                        </p>
                        <p class="text-gray-500">Copie o link gerado abaixo e envie para o novo integrante se registrar no XAMPP:</p>
                        <div class="relative flex items-center bg-white border border-[#C4BC96]/20 p-2 rounded-lg gap-2 overflow-hidden">
                            <input type="text" readonly id="generated-invite-url" value="<?= htmlspecialchars($_GET['invite_link']) ?>" 
                                   class="bg-transparent text-gray-700 font-mono text-[10px] flex-1 outline-none select-all truncate">
                            <button onclick="window.copiarLink()" class="p-1 bg-sand-50 hover:bg-sand-100 rounded text-gray-500" title="Copiar Link">
                                <i data-lucide="copy" class="w-3.5 h-3.5" id="copy-icon"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pending Invitations panel (RF-EQ-006) -->
            <div class="bg-white p-6 rounded-2xl border border-sand-100 shadow-sm flex flex-col">
                <div class="border-b border-sand-50 pb-4 mb-4 flex items-center justify-between">
                    <h3 class="font-serif text-sm font-bold text-gray-800">Convites Pendentes</h3>
                    <span class="text-[10px] font-bold text-[#BAAE79] bg-sand-50 px-2 py-0.5 rounded-full font-mono"><?= count($convites) ?></span>
                </div>

                <div class="space-y-3 max-h-[220px] overflow-y-auto pr-1">
                    <?php if (empty($convites)): ?>
                        <p class="text-[11px] text-gray-400 text-center py-6 font-medium">Nenhum convite pendente de aceitação.</p>
                    <?php else: ?>
                        <?php foreach ($convites as $con): ?>
                            <div class="p-3 bg-sand-50/50 rounded-xl border border-sand-100/30 text-[11px] flex items-center justify-between">
                                <div class="overflow-hidden pr-2">
                                    <p class="font-semibold text-gray-700 font-mono truncate"><?= htmlspecialchars($con['email']) ?></p>
                                    <span class="text-[9px] uppercase tracking-wider font-semibold text-gray-400 font-mono">Cargo: <?= $con['perfil'] ?></span>
                                </div>
                                <span class="px-2 py-0.5 text-[8px] font-bold text-amber-600 bg-amber-50 border border-amber-100 rounded-full shrink-0 font-mono">Pendente</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</div>

<!-- Team interactive helpers -->
<script>
window.copiarLink = function() {
    const input = document.getElementById('generated-invite-url');
    if (input) {
        input.select();
        document.execCommand('copy');
        
        const icon = document.getElementById('copy-icon');
        if (icon && typeof lucide !== 'undefined') {
            alert('Link de convite copiado com sucesso para a área de transferência!');
        }
    }
};
</script>
