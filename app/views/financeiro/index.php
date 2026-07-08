<div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

    <!-- Left/Center: Financial Transactions Ledger (RF-FI-006) -->
    <div class="xl:col-span-2 space-y-6">
        
        <!-- Metric Summary Cards inside Finance module -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-xl border border-sand-100 shadow-sm">
                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Saldo Líquido Caixa</p>
                <h4 class="text-lg font-serif font-bold text-gray-800 font-mono">
                    R$ <?= number_format($saldoTotal, 2, ',', '.') ?>
                </h4>
                <span class="text-[9px] text-gray-400">Receitas menos saídas</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-sand-100 shadow-sm">
                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Total de Vendas PDV</p>
                <h4 class="text-lg font-serif font-bold text-sage-600 font-mono">
                    R$ <?= number_format($totalVendas, 2, ',', '.') ?>
                </h4>
                <span class="text-[9px] text-gray-400">Origem vendas faturadas</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-sand-100 shadow-sm">
                <p class="text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Crédito Fiado Ativo</p>
                <h4 class="text-lg font-serif font-bold text-rose-500 font-mono">
                    R$ <?= number_format($creditoPendente, 2, ',', '.') ?>
                </h4>
                <span class="text-[9px] text-gray-400">Contas em aberto CRM</span>
            </div>
        </div>

        <!-- Ledger container -->
        <div class="bg-white p-6 rounded-2xl border border-sand-100 shadow-sm flex flex-col">
            <!-- Header and Filter Form (RF-FI-006) -->
            <div class="flex flex-col gap-4 mb-6">
                <div>
                    <h1 class="font-serif text-2xl font-bold text-gray-800">Fluxo de Caixa e Lançamentos</h1>
                    <p class="text-xs text-gray-400">Controle de receitas corporativas, despesas de custeio e fiados.</p>
                </div>

                <form action="<?= $basePath ?>/financeiro" method="GET" class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-sand-50/50 p-4 rounded-xl border border-sand-100/50">
                    <div>
                        <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Filtrar Tipo</label>
                        <select name="tipo" class="block w-full py-1.5 px-2 bg-white border border-[#C4BC96]/30 rounded-lg text-[10px] focus:outline-none">
                            <option value="">Todos</option>
                            <option value="RECEITA" <?= ($filters['tipo'] === 'RECEITA') ? 'selected' : '' ?>>Receitas</option>
                            <option value="DESPESA" <?= ($filters['tipo'] === 'DESPESA') ? 'selected' : '' ?>>Despesas</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Filtrar Status</label>
                        <select name="status" class="block w-full py-1.5 px-2 bg-white border border-[#C4BC96]/30 rounded-lg text-[10px] focus:outline-none">
                            <option value="">Todos</option>
                            <option value="CONCLUIDO" <?= ($filters['status'] === 'CONCLUIDO') ? 'selected' : '' ?>>Concluido</option>
                            <option value="PENDENTE" <?= ($filters['status'] === 'PENDENTE') ? 'selected' : '' ?>>Pendente</option>
                            <option value="SAIDA" <?= ($filters['status'] === 'SAIDA') ? 'selected' : '' ?>>Saída</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Início</label>
                        <input type="date" name="data_inicio" value="<?= htmlspecialchars($filters['data_inicio']) ?>" class="block w-full py-1 px-2 bg-white border border-[#C4BC96]/30 rounded-lg text-[10px] focus:outline-none">
                    </div>
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-[9px] font-bold uppercase tracking-wider text-gray-400 mb-1">Fim</label>
                            <input type="date" name="data_fim" value="<?= htmlspecialchars($filters['data_fim']) ?>" class="block w-full py-1 px-2 bg-white border border-[#C4BC96]/30 rounded-lg text-[10px] focus:outline-none">
                        </div>
                        <button type="submit" class="p-2 bg-sage-500 hover:bg-sage-600 text-white rounded-lg" title="Aplicar Filtros">
                            <i data-lucide="filter" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Transactions Ledger Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-sand-50 text-[#BAAE79] font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Data</th>
                            <th class="py-3 px-4">Lançamento / Histórico</th>
                            <th class="py-3 px-4 text-center">Origem</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Valor</th>
                            <th class="py-3 px-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand-50/50">
                        <?php if (empty($transacoes)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-gray-400 font-medium">Nenhum lançamento financeiro registrado ou localizado com os filtros selecionados.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transacoes as $tr): 
                                $isDespesa = $tr['tipo'] === 'DESPESA' || $tr['status'] === 'SAIDA';
                            ?>
                                <tr class="hover:bg-sand-50/10 transition-all duration-150">
                                    <td class="py-3.5 px-4 font-mono text-gray-400 text-[10px]"><?= date('d/m/Y H:i', strtotime($tr['data_transacao'])) ?></td>
                                    <td class="py-3.5 px-4 font-semibold text-gray-700 max-w-[200px] truncate" title="<?= htmlspecialchars($tr['descricao']) ?>">
                                        <?= htmlspecialchars($tr['descricao']) ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-center font-mono text-[9px]">
                                        <span class="px-2 py-0.5 rounded <?= ($tr['origem'] === 'VENDA') ? 'bg-indigo-50 text-indigo-600 border border-indigo-100' : 'bg-sand-50 text-gray-500 border border-sand-100/50' ?>">
                                            <?= $tr['origem'] ?>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <?php if ($tr['status'] === 'SAIDA'): ?>
                                            <span class="px-2 py-0.5 text-[10px] font-semibold text-rose-600 bg-rose-50 border border-rose-100 rounded-full">Saída</span>
                                        <?php elseif ($tr['status'] === 'CONCLUIDO'): ?>
                                            <span class="px-2 py-0.5 text-[10px] font-semibold text-emerald-600 bg-emerald-50 border border-emerald-100 rounded-full">Liquidado</span>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 text-[10px] font-semibold text-amber-600 bg-amber-50 border border-amber-100 rounded-full">Fiado / Aberto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-semibold font-mono <?= $isDespesa ? 'text-rose-600' : 'text-emerald-600' ?>">
                                        <?= $isDespesa ? '-' : '+' ?> R$ <?= number_format($tr['valor'], 2, ',', '.') ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="inline-flex gap-2">
                                            <!-- Edit manual button -->
                                            <button onclick="window.editarTransacao(<?= htmlspecialchars(json_encode($tr)) ?>)" 
                                                    class="p-1.5 hover:bg-sage-50 text-sage-500 hover:text-sage-600 rounded-lg transition-all"
                                                    title="Editar Lançamento">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </button>
                                            
                                            <!-- Delete manual button (Blocks automatic POS tickets, RN-FI-004) -->
                                            <a href="<?= $basePath ?>/financeiro/excluir?id_transacao=<?= $tr['id_transacao'] ?>" 
                                               onclick="return confirm('Confirmar exclusão desta transação? Se a transação originou-se no PDV, o sistema bloqueará a exclusão automaticamente para preservação de auditoria (RN-FI-004).')"
                                               class="p-1.5 hover:bg-rose-50 text-rose-500 hover:text-rose-600 rounded-lg transition-all"
                                               title="Excluir Lançamento">
                                                <i data-lucide="trash" class="w-4 h-4"></i>
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

    <!-- Right: Add / Edit Manual Transaction Panel (RF-FI-004 / RF-FI-005) -->
    <div class="bg-white p-6 rounded-2xl border border-sand-100 shadow-sm h-fit">
        <div class="border-b border-sand-50 pb-4 mb-4">
            <h2 id="form-title" class="font-serif text-lg font-bold text-gray-800">Registrar Lançamento Manual</h2>
            <p id="form-desc" class="text-xs text-gray-400">Insira faturamentos manuais ou saídas administrativas.</p>
        </div>

        <form action="<?= $basePath ?>/financeiro/salvar" method="POST" id="transacao-form" class="space-y-4">
            <input type="hidden" name="id_transacao" id="id_transacao" value="">

            <div>
                <label for="descricao" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Descrição do Lançamento</label>
                <input type="text" id="descricao" name="descricao" required placeholder="ex: Compra embalagens presente, Luz mensal"
                       class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
            </div>

            <div>
                <label for="tipo" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Tipo de Movimentação</label>
                <select id="tipo" name="tipo" required
                        class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                    <option value="RECEITA">Receita (+) Entrada</option>
                    <option value="DESPESA">Despesa (-) Saída</option>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="valor" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Valor (R$)</label>
                    <input type="text" id="valor" name="valor" required placeholder="0.00"
                           class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs font-mono placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                </div>

                <div id="container-status">
                    <label for="status" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Status Recebimento</label>
                    <select id="status" name="status"
                            class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                        <option value="CONCLUIDO">Pago / Liquidado</option>
                        <option value="PENDENTE">A receber (Fiado)</option>
                    </select>
                </div>
            </div>

            <div class="flex gap-2.5 pt-4 border-t border-sand-50 mt-4">
                <button type="submit" 
                        class="flex-1 py-2.5 px-4 bg-[#8DA574] hover:bg-[#849B48] text-white text-xs font-semibold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span id="btn-submit-text">Registrar</span>
                </button>
                <button type="button" onclick="window.limparFormulario()" id="cancelar-btn"
                        class="hidden py-2.5 px-3 border border-sand-500 text-sand-600 hover:bg-sand-50 rounded-xl text-xs font-semibold transition-all">
                    Cancelar
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Financial Interactive Frontend Scripts -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('transacao-form');
    const formTitle = document.getElementById('form-title');
    const formDesc = document.getElementById('form-desc');
    const submitBtnText = document.getElementById('btn-submit-text');
    const cancelarBtn = document.getElementById('cancelar-btn');

    const inputId = document.getElementById('id_transacao');
    const inputDesc = document.getElementById('descricao');
    const selectTipo = document.getElementById('tipo');
    const inputValor = document.getElementById('valor');
    const selectStatus = document.getElementById('status');
    const statusContainer = document.getElementById('container-status');

    // Despesa força status saída automaticamente
    selectTipo.addEventListener('change', () => {
        if (selectTipo.value === 'DESPESA') {
            statusContainer.style.display = 'none';
        } else {
            statusContainer.style.display = 'block';
        }
    });

    // 1. Popula dados p/ edição
    window.editarTransacao = function(tr) {
        if (tr.origem === 'VENDA' && "<?= $_SESSION['nivel_acesso'] ?>" !== 'ADMIN') {
            alert("Bloqueado: Transações originadas de vendas do PDV só podem ser modificadas pelo perfil Administrador.");
            return;
        }

        formTitle.textContent = "Editar Lançamento";
        formDesc.textContent = `Ajustando detalhes da Transação ID #${tr.id_transacao}.`;
        submitBtnText.textContent = "Salvar Alterações";
        cancelarBtn.classList.remove('hidden');

        inputId.value = tr.id_transacao;
        inputDesc.value = tr.descricao;
        selectTipo.value = tr.tipo;
        inputValor.value = parseFloat(tr.valor).toFixed(2);
        selectStatus.value = tr.status;

        // Dispara trigger de visualização de status
        if (tr.tipo === 'DESPESA') {
            statusContainer.style.display = 'none';
        } else {
            statusContainer.style.display = 'block';
        }

        inputDesc.focus();
    };

    // 2. Limpa formulário
    window.limparFormulario = function() {
        formTitle.textContent = "Registrar Lançamento Manual";
        formDesc.textContent = "Insira faturamentos manuais ou saídas administrativas.";
        submitBtnText.textContent = "Registrar";
        cancelarBtn.classList.add('hidden');

        inputId.value = '';
        form.reset();
        statusContainer.style.display = 'block';
    };
});
</script>
