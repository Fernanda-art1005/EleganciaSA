<div class="grid grid-cols-1 xl:grid-cols-3 gap-8">

    <!-- Left/Center: Products List Table (RF-ES-001) -->
    <div class="xl:col-span-2 bg-white p-6 rounded-2xl border border-sand-100 shadow-sm flex flex-col">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="font-serif text-2xl font-bold text-gray-800">Controle de Estoque</h1>
                <p class="text-xs text-gray-400">Gerencie o catálogo de produtos e seus níveis de abastecimento.</p>
            </div>

            <!-- Search Filter form (RF-ES-001) -->
            <form action="<?= $basePath ?>/estoque" method="GET" class="flex items-center gap-2">
                <div class="relative w-64">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                    </span>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nome ou categoria..." 
                           class="block w-full pl-8 pr-4 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                </div>
                <?php if (!empty($q)): ?>
                    <a href="<?= $basePath ?>/estoque" class="p-2 bg-gray-100 text-gray-500 hover:text-gray-700 rounded-lg text-xs" title="Limpar Filtro">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Products Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-sand-50 text-[#BAAE79] font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Cód</th>
                        <th class="py-3 px-4">Nome do Produto</th>
                        <th class="py-3 px-4">Categoria</th>
                        <th class="py-3 px-4">Preço Custo</th>
                        <th class="py-3 px-4">Preço Venda</th>
                        <th class="py-3 px-4">Estoque</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand-50/50">
                    <?php if (empty($produtos)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-400 font-medium">Nenhum produto cadastrado ou correspondente localizado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produtos as $prod): 
                            $isLow = $prod['quantidade'] <= $prod['estoque_minimo'];
                        ?>
                            <tr class="hover:bg-sand-50/10 transition-all duration-150">
                                <td class="py-3.5 px-4 font-mono text-gray-400 font-semibold">#<?= $prod['id_produto'] ?></td>
                                <td class="py-3.5 px-4 font-semibold text-gray-700 max-w-[220px] truncate" title="<?= htmlspecialchars($prod['nome']) ?>">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-lg overflow-hidden bg-gray-50 border border-sand-100 flex-shrink-0">
                                            <img src="<?= getProductImageUrl($prod['nome']) ?>" alt="<?= htmlspecialchars($prod['nome']) ?>" class="w-full h-full object-cover">
                                        </div>
                                        <span class="truncate"><?= htmlspecialchars($prod['nome']) ?></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-gray-500">
                                    <span class="bg-sand-50 border border-sand-100/50 text-gray-600 px-2 py-0.5 rounded-md font-medium text-[10px]">
                                        <?= htmlspecialchars($prod['categoria'] ?: 'Geral') ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-gray-500">R$ <?= number_format($prod['preco_custo'], 2, ',', '.') ?></td>
                                <td class="py-3.5 px-4 font-mono text-gray-700 font-semibold">R$ <?= number_format($prod['preco'], 2, ',', '.') ?></td>
                                <td class="py-3.5 px-4 font-mono">
                                    <span class="font-bold <?= $isLow ? 'text-rose-600' : 'text-gray-700' ?>">
                                        <?= $prod['quantidade'] ?>
                                    </span>
                                    <span class="text-gray-400 text-[10px]">/ <?= $prod['estoque_minimo'] ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <?php if ($prod['quantidade'] <= 0): ?>
                                        <span class="px-2 py-0.5 text-[10px] font-semibold text-red-600 bg-red-50 border border-red-100 rounded-full">Zerado</span>
                                    <?php elseif ($isLow): ?>
                                        <span class="px-2 py-0.5 text-[10px] font-semibold text-rose-600 bg-rose-50 border border-rose-100 rounded-full">Crítico</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 text-[10px] font-semibold text-sage-600 bg-sage-50 border border-sage-100 rounded-full">Abastecido</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex gap-2">
                                        <!-- Edit trigger -->
                                        <button onclick="window.editarProduto(<?= htmlspecialchars(json_encode($prod)) ?>)" 
                                                class="p-1.5 hover:bg-sage-50 text-sage-500 hover:text-sage-600 rounded-lg transition-all"
                                                title="Editar Produto">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </button>
                                        
                                        <!-- Delete trigger with ACID safe blocks validation -->
                                        <a href="<?= $basePath ?>/estoque/excluir?id_produto=<?= $prod['id_produto'] ?>" 
                                           onclick="return confirm('Tem certeza que deseja excluir o produto \'<?= htmlspecialchars(addslashes($prod['nome'])) ?>\'? Esta operação registrará um log de auditoria e falhará se o produto tiver vendas faturadas.')"
                                           class="p-1.5 hover:bg-rose-50 text-rose-500 hover:text-rose-600 rounded-lg transition-all"
                                           title="Excluir Produto">
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

    <!-- Right: Add / Edit Product Panel (RF-ES-002 / RF-ES-003) -->
    <div class="bg-white p-6 rounded-2xl border border-sand-100 shadow-sm h-fit">
        <div class="border-b border-sand-50 pb-4 mb-4">
            <h2 id="form-title" class="font-serif text-lg font-bold text-gray-800">Cadastrar Novo Produto</h2>
            <p id="form-desc" class="text-xs text-gray-400">Insira as informações de venda do item.</p>
        </div>

        <form action="<?= $basePath ?>/estoque/salvar" method="POST" id="produto-form" class="space-y-4">
            <input type="hidden" name="id_produto" id="id_produto" value="">

            <div>
                <label for="nome" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Descrição / Nome do Produto</label>
                <input type="text" id="nome" name="nome" required placeholder="ex: Brinco Pérola Lapidado"
                       class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
            </div>

            <div>
                <label for="categoria" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Categoria / Coleção</label>
                <input type="text" id="categoria" name="categoria" placeholder="ex: Joias, Acessórios"
                       class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="preco_custo" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Preço de Custo (R$)</label>
                    <input type="text" id="preco_custo" name="preco_custo" required placeholder="0.00"
                           class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs font-mono placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                </div>

                <div>
                    <label for="preco" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Preço de Venda (R$)</label>
                    <input type="text" id="preco" name="preco" required placeholder="0.00"
                           class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs font-mono placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="quantidade" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Qtd Inicial</label>
                    <input type="number" id="quantidade" name="quantidade" required min="0" value="0"
                           class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs font-mono focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                </div>

                <div>
                    <label for="estoque_minimo" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Qtd Mínima Alerta</label>
                    <input type="number" id="estoque_minimo" name="estoque_minimo" required min="0" value="5"
                           class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs font-mono focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                </div>
            </div>

            <div class="flex gap-2.5 pt-4 border-t border-sand-50 mt-4">
                <button type="submit" 
                        class="flex-1 py-2.5 px-4 bg-[#8DA574] hover:bg-[#849B48] text-white text-xs font-semibold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-1.5">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span id="btn-submit-text">Cadastrar</span>
                </button>
                <button type="button" onclick="window.limparFormulario()" id="cancelar-btn"
                        class="hidden py-2.5 px-3 border border-sand-500 text-sand-600 hover:bg-sand-50 rounded-xl text-xs font-semibold transition-all">
                    Cancelar
                </button>
            </div>
        </form>
    </div>

</div>

<!-- Inventory Interaction Client-side Scripts -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('produto-form');
    const formTitle = document.getElementById('form-title');
    const formDesc = document.getElementById('form-desc');
    const submitBtnText = document.getElementById('btn-submit-text');
    const cancelarBtn = document.getElementById('cancelar-btn');

    const inputId = document.getElementById('id_produto');
    const inputNome = document.getElementById('nome');
    const inputCategoria = document.getElementById('categoria');
    const inputPrecoCusto = document.getElementById('preco_custo');
    const inputPrecoVenda = document.getElementById('preco');
    const inputQtd = document.getElementById('quantidade');
    const inputMin = document.getElementById('estoque_minimo');

    // 1. Popula dados p/ edição
    window.editarProduto = function(prod) {
        formTitle.textContent = "Editar Produto";
        formDesc.textContent = `Ajustando detalhes do Produto Cód #${prod.id_produto}.`;
        submitBtnText.textContent = "Salvar Alterações";
        cancelarBtn.classList.remove('hidden');

        inputId.value = prod.id_produto;
        inputNome.value = prod.nome;
        inputCategoria.value = prod.categoria || '';
        inputPrecoCusto.value = parseFloat(prod.preco_custo).toFixed(2);
        inputPrecoVenda.value = parseFloat(prod.preco).toFixed(2);
        inputQtd.value = prod.quantidade;
        inputMin.value = prod.estoque_minimo;

        inputNome.focus();
    };

    // 2. Limpa formulário
    window.limparFormulario = function() {
        formTitle.textContent = "Cadastrar Novo Produto";
        formDesc.textContent = "Insira as informações de venda do item.";
        submitBtnText.textContent = "Cadastrar";
        cancelarBtn.classList.add('hidden');

        inputId.value = '';
        form.reset();
    };

    // 3. Validação do formulário (Regra de Negócio RN-ES-002)
    form.addEventListener('submit', (e) => {
        const custo = parseFloat(inputPrecoCusto.value.replace(',', '.'));
        const venda = parseFloat(inputPrecoVenda.value.replace(',', '.'));

        if (venda <= custo) {
            e.preventDefault();
            alert(`Erro de Validação: O preço de venda (R$ ${venda.toFixed(2)}) deve ser estritamente maior do que o preço de custo (R$ ${custo.toFixed(2)}) de acordo com a regra de negócio do estabelecimento (RN-ES-002).`);
            inputPrecoVenda.focus();
        }
    });
});
</script>
