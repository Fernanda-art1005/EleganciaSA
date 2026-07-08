<div class="space-y-8">
    
    <!-- Welcome Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl font-bold tracking-tight text-[#849B48]">Painel Executivo</h1>
            <p class="text-xs text-[#BAAE79] font-medium uppercase tracking-widest mt-1">Acompanhe o desempenho comercial da Elegância Premium em tempo real.</p>
        </div>
        <div class="flex items-center gap-3 bg-white px-4 py-2.5 rounded-xl border border-[#C4BC96]/30 shadow-sm self-start">
            <div class="w-2.5 h-2.5 rounded-full bg-[#8DA574] animate-ping"></div>
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-600">Sistema Conectado (PDO)</span>
        </div>
    </div>

    <!-- KPI Metric Cards Grid (RF-DA-001 / RF-DA-002 / RF-DA-003 / RF-DA-004) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Receita Total -->
        <div class="bg-white p-6 rounded-2xl border border-[#C4BC96]/30 shadow-sm flex items-center justify-between hover:shadow-md transition-all duration-200">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#BAAE79] mb-1">Saldo Total</p>
                <h3 class="text-2xl font-serif font-bold text-[#849B48] font-mono">
                    R$ <?= number_format($receitaTotal, 2, ',', '.') ?>
                </h3>
                <span class="text-[10px] text-[#8DA574] font-medium flex items-center gap-0.5 mt-1.5">
                    <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                    Receitas menos despesas
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-[#8DA574]/10 border border-[#8DA574]/20 flex items-center justify-center text-[#8DA574]">
                <i data-lucide="dollar-sign" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Total de Transações / Vendas -->
        <div class="bg-white p-6 rounded-2xl border border-[#C4BC96]/30 shadow-sm flex items-center justify-between hover:shadow-md transition-all duration-200">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#BAAE79] mb-1">Total de Vendas</p>
                <h3 class="text-2xl font-serif font-bold text-[#849B48] font-mono">
                    <?= $totalVendas ?>
                </h3>
                <span class="text-[10px] text-gray-400 font-medium flex items-center gap-0.5 mt-1.5">
                    <i data-lucide="shopping-cart" class="w-3.5 h-3.5"></i>
                    Vendas concluídas no PDV
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-[#BAAE79]/10 border border-[#BAAE79]/20 flex items-center justify-center text-[#BAAE79]">
                <i data-lucide="shopping-bag" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Produtos Ativos -->
        <div class="bg-white p-6 rounded-2xl border border-[#C4BC96]/30 shadow-sm flex items-center justify-between hover:shadow-md transition-all duration-200">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#BAAE79] mb-1">Produtos em Estoque</p>
                <h3 class="text-2xl font-serif font-bold text-[#849B48] font-mono">
                    <?= $produtosAtivos ?>
                </h3>
                <span class="text-[10px] text-gray-400 font-medium flex items-center gap-0.5 mt-1.5">
                    <i data-lucide="package-check" class="w-3.5 h-3.5"></i>
                    Produtos com saldo > 0
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-[#C4BC96]/10 border border-[#C4BC96]/20 flex items-center justify-center text-[#C4BC96]">
                <i data-lucide="box" class="w-6 h-6"></i>
            </div>
        </div>

        <!-- Crédito Pendente -->
        <div class="bg-white p-6 rounded-2xl border border-[#C4BC96]/30 shadow-sm flex items-center justify-between hover:shadow-md transition-all duration-200">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-[#BAAE79] mb-1">Crédito a Receber</p>
                <h3 class="text-2xl font-serif font-bold text-[#849B48] font-mono">
                    R$ <?= number_format($creditoPendente, 2, ',', '.') ?>
                </h3>
                <span class="text-[10px] text-rose-500 font-medium flex items-center gap-0.5 mt-1.5">
                    <i data-lucide="users" class="w-3.5 h-3.5"></i>
                    Saldos devedores do CRM
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-500">
                <i data-lucide="credit-card" class="w-6 h-6"></i>
            </div>
        </div>
    </div>

    <!-- Bento Grid Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Monthly Revenue Chart (RF-DA-005) - Takes 2 cols on large screen -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-[#C4BC96]/30 shadow-sm flex flex-col">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="font-serif text-lg font-bold text-[#849B48]">Desempenho Comercial Mensal</h2>
                    <p class="text-xs text-[#BAAE79] font-semibold uppercase tracking-widest mt-0.5">Total acumulado de receitas concluídas em <?= $anoAtual ?></p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 bg-[#F5F5F0] rounded-lg text-gray-600">Ano Corrente</span>
            </div>

            <!-- Custom Elegant Bar Chart -->
            <div class="flex-1 flex flex-col justify-end min-h-[260px] pt-4">
                <?php
                $mesesNomes = [
                    1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
                    7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
                ];
                $maxReceita = max($receitaMensal);
                if ($maxReceita <= 0) $maxReceita = 1000.00; // Evita divisão por zero
                ?>
                
                <!-- Bars Container -->
                <div class="flex items-end justify-between h-48 gap-3 sm:gap-4 md:gap-6 px-2">
                    <?php for ($m = 1; $m <= 12; $m++): 
                        $valor = $receitaMensal[$m];
                        $percent = ($valor / $maxReceita) * 100;
                    ?>
                        <div class="flex-1 flex flex-col items-center group relative h-full justify-end">
                            <!-- Tooltip on hover -->
                            <div class="absolute bottom-full mb-2 bg-[#1A1A1A] text-white text-[10px] py-1 px-2 rounded shadow-md opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-10 whitespace-nowrap font-mono">
                                R$ <?= number_format($valor, 2, ',', '.') ?>
                            </div>
                            
                            <!-- Bar fill -->
                            <div style="height: <?= max(4, $percent) ?>%;" 
                                 class="w-full rounded-t-md transition-all duration-300 <?= ($valor > 0) ? 'bg-[#8DA574] hover:bg-[#849B48]' : 'bg-[#F5F5F0] hover:bg-gray-200' ?>">
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                
                <!-- Month Labels -->
                <div class="flex justify-between border-t border-[#F5F5F0] mt-4 pt-3 text-[11px] font-semibold text-gray-400 text-center px-2">
                    <?php foreach ($mesesNomes as $num => $nome): ?>
                        <div class="flex-1"><?= $nome ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Alertas de Estoque Baixo (RF-DA-006 / RF-DA-007) -->
        <div class="bg-white p-6 rounded-2xl border border-[#C4BC96]/30 shadow-sm flex flex-col h-full">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-serif text-lg font-bold text-[#849B48]">Alertas de Estoque</h2>
                    <p class="text-xs text-[#BAAE79] font-semibold uppercase tracking-widest mt-0.5">Produtos abaixo do limite mínimo</p>
                </div>
                <span class="text-xs font-bold text-rose-500 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded-full font-mono">
                    <?= count($alertasEstoque) ?>
                </span>
            </div>

            <!-- List items -->
            <div class="flex-1 overflow-y-auto space-y-3.5 pr-1 max-h-[220px]">
                <?php if (empty($alertasEstoque)): ?>
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-500 flex items-center justify-center mb-2">
                            <i data-lucide="check" class="w-5 h-5"></i>
                        </div>
                        <p class="text-xs font-medium text-gray-400">Nenhum produto abaixo do estoque mínimo.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($alertasEstoque as $produto): ?>
                        <div class="flex items-center justify-between p-3 bg-rose-50/50 hover:bg-rose-50 border border-rose-100/50 rounded-xl transition-all">
                            <div class="overflow-hidden flex-1">
                                <p class="text-xs font-bold text-gray-700 truncate" title="<?= htmlspecialchars($produto['nome']) ?>"><?= htmlspecialchars($produto['nome']) ?></p>
                                <p class="text-[10px] text-gray-400">Min: <span class="font-semibold text-gray-500 font-mono"><?= $produto['estoque_minimo'] ?></span> | Atual: <span class="font-bold text-rose-600"><?= $produto['quantidade'] ?></span></p>
                            </div>
                            <div class="flex items-center gap-1 ml-2 shrink-0">
                                <!-- Editar Produto -->
                                <a href="<?= $basePath ?>/estoque?id_produto=<?= $produto['id_produto'] ?>" 
                                   class="p-1 hover:bg-white hover:shadow-sm text-sage-500 hover:text-sage-600 rounded-lg transition-all"
                                   title="Editar Produto">
                                    <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                </a>
                                <!-- Deletar Produto -->
                                <a href="<?= $basePath ?>/estoque/excluir?id_produto=<?= $produto['id_produto'] ?>" 
                                   onclick="return confirm('ATENÇÃO: Deseja realmente excluir o produto <?= htmlspecialchars($produto['nome']) ?>?')"
                                   class="p-1 hover:bg-white hover:shadow-sm text-rose-500 hover:text-rose-600 rounded-lg transition-all"
                                   title="Excluir/Deletar Produto">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Action Redirection (RF-DA-007) -->
            <div class="mt-6 pt-4 border-t border-[#F5F5F0]">
                <a href="<?= $basePath ?>/estoque" class="w-full py-2.5 px-4 bg-[#8DA574] hover:bg-[#849B48] text-white rounded-xl text-xs font-semibold tracking-wide shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-1.5">
                    <i data-lucide="arrow-right-left" class="w-4 h-4"></i>
                    <span>Gerenciar Estoque Completo</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Clientes com Crédito Vencido (RF-DA-008) -->
    <div class="bg-white p-6 rounded-2xl border border-[#C4BC96]/30 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="font-serif text-lg font-bold text-[#849B48]">Clientes com Débitos Pendentes (CRM)</h2>
                <p class="text-xs text-[#BAAE79] font-semibold uppercase tracking-widest mt-0.5">Controle de inadimplência e limites de crédito</p>
            </div>
        </div>

        <!-- Table list -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-[#F5F5F0] text-[10px] uppercase font-bold text-[#BAAE79] tracking-widest border-b border-[#C4BC96]/20">
                        <th class="py-3 px-4">Nome do Cliente</th>
                        <th class="py-3 px-4">Limite de Crédito</th>
                        <th class="py-3 px-4">Saldo Devedor Atual</th>
                        <th class="py-3 px-4">Crédito Disponível</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F5F5F0]">
                    <?php if (empty($clientesDevedores)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-6 text-gray-400 font-medium">Nenhum cliente com débitos em aberto no momento.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clientesDevedores as $cliente): 
                            $disponivel = max(0.00, $cliente['limite_credito'] - $cliente['saldo_devedor']);
                            $isOverdue = $cliente['saldo_devedor'] > $cliente['limite_credito'];
                        ?>
                            <tr class="hover:bg-[#F5F5F0]/50 transition-all">
                                <td class="py-3.5 px-4 font-semibold text-gray-700"><?= htmlspecialchars($cliente['nome']) ?></td>
                                <td class="py-3.5 px-4 font-mono text-gray-500">R$ <?= number_format($cliente['limite_credito'], 2, ',', '.') ?></td>
                                <td class="py-3.5 px-4 font-mono text-rose-600 font-semibold">R$ <?= number_format($cliente['saldo_devedor'], 2, ',', '.') ?></td>
                                <td class="py-3.5 px-4 font-mono text-gray-500">R$ <?= number_format($disponivel, 2, ',', '.') ?></td>
                                <td class="py-3.5 px-4 text-center">
                                    <?php if ($isOverdue): ?>
                                        <span class="px-2 py-0.5 text-[10px] font-semibold text-red-600 bg-red-50 border border-red-100 rounded-full">Excedido</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 text-[10px] font-semibold text-amber-600 bg-amber-50 border border-amber-100 rounded-full">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <!-- Editar Cliente -->
                                        <a href="<?= $basePath ?>/crm?id_cliente=<?= $cliente['id_cliente'] ?>" 
                                           class="p-1.5 hover:bg-sage-50 text-sage-500 hover:text-sage-600 rounded-lg transition-all"
                                           title="Editar Cliente">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </a>
                                        <!-- Excluir Cliente -->
                                        <a href="<?= $basePath ?>/crm/excluir?id_cliente=<?= $cliente['id_cliente'] ?>" 
                                           onclick="return confirm('ATENÇÃO: Deseja realmente excluir o cliente <?= htmlspecialchars($cliente['nome']) ?> do CRM?')"
                                           class="p-1.5 hover:bg-rose-50 text-rose-500 hover:text-rose-600 rounded-lg transition-all"
                                           title="Excluir/Deletar Cliente">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </a>
                                        <!-- Visualizar CRM -->
                                        <a href="<?= $basePath ?>/crm" 
                                           class="p-1.5 hover:bg-sand-50 text-sand-500 hover:text-sand-600 rounded-lg transition-all"
                                           title="Ir para o CRM">
                                            <i data-lucide="eye" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
