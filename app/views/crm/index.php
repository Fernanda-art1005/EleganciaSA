<div class="space-y-8">
    
    <!-- Top Row: Split Layout for CRM operations -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        
        <!-- Left: Clients Listing and CRM Records (RF-CL-001) -->
        <div class="xl:col-span-2 bg-white p-6 rounded-2xl border border-sand-100 shadow-sm flex flex-col">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h1 class="font-serif text-2xl font-bold text-gray-800">Clientes & CRM</h1>
                    <p class="text-xs text-gray-400">Gerenciamento de contatos, limites de crédito e histórico de compras.</p>
                </div>

                <!-- Client Search bar (RF-CL-001) -->
                <form action="<?= $basePath ?>/crm" method="GET" class="flex items-center gap-2">
                    <div class="relative w-64">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i data-lucide="search" class="w-3.5 h-3.5"></i>
                        </span>
                        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nome, e-mail ou telefone..." 
                               class="block w-full pl-8 pr-4 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                    </div>
                    <?php if (!empty($q)): ?>
                        <a href="<?= $basePath ?>/crm" class="p-2 bg-gray-100 text-gray-500 hover:text-gray-700 rounded-lg text-xs" title="Limpar Filtro">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Clients Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-sand-50 text-[#BAAE79] font-bold uppercase tracking-wider">
                            <th class="py-3 px-4">Cliente</th>
                            <th class="py-3 px-4">Contatos</th>
                            <th class="py-3 px-4 text-right">Limite</th>
                            <th class="py-3 px-4 text-right">Saldo Devedor</th>
                            <th class="py-3 px-4 text-right">Crédito Disponível</th>
                            <th class="py-3 px-4 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand-50/50">
                        <?php if (empty($clientes)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-8 text-gray-400 font-medium">Nenhum cliente cadastrado ou localizado no sistema.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($clientes as $cli): 
                                $disponivel = max(0.00, $cli['limite_credito'] - $cli['saldo_devedor']);
                                $isOverdue = $cli['saldo_devedor'] > $cli['limite_credito'];
                            ?>
                                <tr class="hover:bg-sand-50/10 transition-all duration-150">
                                    <td class="py-3.5 px-4">
                                        <p class="font-semibold text-gray-700"><?= htmlspecialchars($cli['nome']) ?></p>
                                        <span class="text-[9px] text-gray-400 font-mono">Cód: #<?= $cli['id_cliente'] ?></span>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <p class="text-gray-600"><?= htmlspecialchars($cli['email'] ?: 'N/A') ?></p>
                                        <p class="text-gray-400 text-[10px]"><?= htmlspecialchars($cli['telefone'] ?: 'N/A') ?></p>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono text-gray-500">R$ <?= number_format($cli['limite_credito'], 2, ',', '.') ?></td>
                                    <td class="py-3.5 px-4 text-right font-mono font-semibold <?= ($cli['saldo_devedor'] > 0) ? 'text-rose-600' : 'text-gray-500' ?>">
                                        R$ <?= number_format($cli['saldo_devedor'], 2, ',', '.') ?>
                                    </td>
                                    <td class="py-3.5 px-4 text-right font-mono text-gray-700">R$ <?= number_format($disponivel, 2, ',', '.') ?></td>
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="inline-flex gap-1.5">
                                            <!-- Payment receiver (Fiado receipt, RF-CL-007) -->
                                            <?php if ($cli['saldo_devedor'] > 0): ?>
                                                <button onclick="window.abrirModalPagamento(<?= htmlspecialchars(json_encode($cli)) ?>)" 
                                                        class="p-1.5 hover:bg-emerald-50 text-emerald-500 hover:text-emerald-600 rounded-lg transition-all"
                                                        title="Receber Pagamento">
                                                    <i data-lucide="receipt" class="w-4 h-4"></i>
                                                </button>
                                            <?php endif; ?>

                                            <!-- Purchase history trigger (RF-CL-004) -->
                                            <a href="?historico_id=<?= $cli['id_cliente'] ?><?= $q ? '&q=' . urlencode($q) : '' ?>" 
                                               class="p-1.5 hover:bg-indigo-50 text-indigo-500 hover:text-indigo-600 rounded-lg transition-all"
                                               title="Ver Histórico de Compras">
                                                <i data-lucide="history" class="w-4 h-4"></i>
                                            </a>

                                            <!-- Edit manual button -->
                                            <button onclick="window.editarCliente(<?= htmlspecialchars(json_encode($cli)) ?>)" 
                                                    class="p-1.5 hover:bg-sage-50 text-sage-500 hover:text-sage-600 rounded-lg transition-all"
                                                    title="Editar Cliente">
                                                <i data-lucide="edit" class="w-4 h-4"></i>
                                            </button>
                                            
                                            <!-- Delete client (Safe blocks warning if balance > 0, RF-CL-006) -->
                                            <a href="<?= $basePath ?>/crm/excluir?id_cliente=<?= $cli['id_cliente'] ?>" 
                                               onclick="return confirm('Confirmar remoção deste cliente? Operações que excluam clientes com saldo devedor pendente serão rejeitadas pelo sistema (RF-CL-006).')"
                                               class="p-1.5 hover:bg-rose-50 text-rose-500 hover:text-rose-600 rounded-lg transition-all"
                                               title="Remover Cliente">
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

        <!-- Right Side: Add / Edit Client Form (RF-CL-003 / RF-CL-005) -->
        <div class="bg-white p-6 rounded-2xl border border-sand-100 shadow-sm h-fit">
            <div class="border-b border-sand-50 pb-4 mb-4">
                <h2 id="form-title" class="font-serif text-lg font-bold text-gray-800">Cadastrar Novo Cliente</h2>
                <p id="form-desc" class="text-xs text-gray-400">Insira as informações de contato do cliente.</p>
            </div>

            <form action="<?= $basePath ?>/crm/salvar" method="POST" id="cliente-form" class="space-y-4">
                <input type="hidden" name="id_cliente" id="id_cliente" value="">

                <div>
                    <label for="nome" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Nome Completo</label>
                    <input type="text" id="nome" name="nome" required placeholder="ex: Sofia Constantino"
                           class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                </div>

                <div>
                    <label for="email" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Endereço de E-mail</label>
                    <input type="email" id="email" name="email" placeholder="ex: sofia@elegancia.com"
                           class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                </div>

                <div>
                    <label for="telefone" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Telefone / WhatsApp</label>
                    <input type="text" id="telefone" name="telefone" placeholder="ex: (11) 98765-4321"
                           class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="limite_credito" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Limite Crédito (R$)</label>
                        <input type="text" id="limite_credito" name="limite_credito" placeholder="0.00" value="500.00"
                               <?= ($_SESSION['nivel_acesso'] !== 'ADMIN') ? 'readonly class="block w-full px-3 py-2 bg-gray-100 border border-sand-100 rounded-xl text-xs font-mono text-gray-400 cursor-not-allowed"' : 'class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs font-mono focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all"' ?>
                               title="Apenas administradores podem gerenciar o limite de crédito.">
                    </div>

                    <div>
                        <label for="saldo_devedor" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Saldo Devedor (R$)</label>
                        <input type="text" id="saldo_devedor" name="saldo_devedor" readonly value="0.00"
                               class="block w-full px-3 py-2 bg-gray-100 border border-sand-100 rounded-xl text-xs font-mono text-gray-400 cursor-not-allowed"
                               title="Saldo devedor acumulado pelas compras faturadas em fiado no PDV.">
                    </div>
                </div>

                <div class="flex gap-2.5 pt-4 border-t border-sand-50 mt-4">
                    <button type="submit" 
                            class="flex-1 py-2.5 px-4 bg-[#8DA574] hover:bg-[#849B48] text-white text-xs font-semibold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-1.5">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span id="btn-submit-text">Cadastrar Cliente</span>
                    </button>
                    <button type="button" onclick="window.limparFormulario()" id="cancelar-btn"
                            class="hidden py-2.5 px-3 border border-sand-500 text-sand-600 hover:bg-sand-50 rounded-xl text-xs font-semibold transition-all">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bottom Row: Dynamic Purchase History Log panel (RF-CL-004) -->
    <?php if ($clienteHistorico): ?>
        <div class="bg-white p-6 rounded-2xl border border-sand-100 shadow-sm" id="purchase-history-panel">
            <div class="flex items-center justify-between border-b border-sand-50 pb-4 mb-6">
                <div>
                    <h2 class="font-serif text-lg font-bold text-gray-800">Histórico de Compras: <?= htmlspecialchars($clienteHistorico['nome']) ?></h2>
                    <p class="text-xs text-gray-400">Linha do tempo de compras efetuadas pelo cliente cadastrado.</p>
                </div>
                <a href="?<?= $q ? 'q=' . urlencode($q) : '' ?>" class="p-2 hover:bg-sand-50 rounded-lg text-gray-500" title="Fechar Histórico">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-sand-50 text-[#BAAE79] font-bold uppercase tracking-wider">
                            <th class="py-2.5 px-4">Cód Venda</th>
                            <th class="py-2.5 px-4">Data / Hora</th>
                            <th class="py-2.5 px-4">Produtos Adquiridos</th>
                            <th class="py-2.5 px-4 text-center">Faturamento</th>
                            <th class="py-2.5 px-4 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-sand-50/50">
                        <?php if (empty($compras)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-6 text-gray-400 font-medium">Nenhuma compra faturada no caixa para este cliente.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($compras as $venda): ?>
                                <tr class="hover:bg-sand-50/10">
                                    <td class="py-3 px-4 font-mono font-semibold text-gray-400">#<?= $venda['id_venda'] ?></td>
                                    <td class="py-3 px-4 text-gray-500"><?= date('d/m/Y H:i', strtotime($venda['data_venda'])) ?></td>
                                    <td class="py-3 px-4 font-medium text-gray-600 max-w-[300px] truncate" title="<?= htmlspecialchars($venda['itens_comprados']) ?>">
                                        <?= htmlspecialchars($venda['itens_comprados']) ?>
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold <?= ($venda['forma_pagamento'] === 'CREDITO_LOJA') ? 'bg-amber-50 text-amber-600 border border-amber-100' : 'bg-sage-50 text-sage-600 border border-sage-100' ?>">
                                            <?= ($venda['forma_pagamento'] === 'CREDITO_LOJA') ? 'Crédito da Loja (Fiado)' : $venda['forma_pagamento'] ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-gray-700">R$ <?= number_format($venda['total'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Payment Receipt overlay modal (RF-CL-007) -->
<div id="pagamento-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-sand-100 max-w-sm w-full overflow-hidden">
        <div class="p-6 border-b border-sand-50 flex items-center justify-between bg-sand-50/30">
            <h3 class="font-serif text-lg font-bold text-gray-800">Quitar Débito CRM</h3>
            <button onclick="window.fecharModalPagamento()" class="p-1 hover:bg-sand-100 rounded-lg text-gray-400">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="<?= $basePath ?>/crm/receber" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="id_cliente_pagamento" id="id_cliente_pagamento" value="">
            
            <div class="p-3 bg-rose-50 rounded-xl text-xs border border-rose-100">
                <p class="text-rose-800 font-semibold mb-1" id="modal-cliente-nome">Cliente</p>
                <p class="text-[10px] text-rose-500 font-mono">Saldo devedor total: <strong id="modal-cliente-saldo">R$ 0,00</strong></p>
            </div>

            <div>
                <label for="valor_pagamento" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Valor do Pagamento (R$)</label>
                <input type="text" id="valor_pagamento" name="valor_pagamento" required placeholder="0.00"
                       class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs font-mono placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                <p class="text-[9px] text-gray-400 mt-1">Este valor abaterá o saldo devedor e registrará uma receita líquida no financeiro.</p>
            </div>

            <div class="flex gap-2.5 pt-4">
                <button type="submit" class="flex-1 py-2 px-4 bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-semibold rounded-xl shadow-md transition-all flex items-center justify-center gap-1">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Efetuar Recebimento</span>
                </button>
                <button type="button" onclick="window.fecharModalPagamento()" class="py-2 px-3 border border-sand-500 text-sand-600 hover:bg-sand-50 rounded-xl text-xs font-semibold">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- CRM Interactions Client-side Scripts -->
<?php
$clientesMap = [];
if (!empty($clientes)) {
    foreach ($clientes as $cli) {
        $clientesMap[$cli['id_cliente']] = $cli;
    }
}
?>
<script>
const clientesMap = <?= json_encode($clientesMap) ?>;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('cliente-form');
    const formTitle = document.getElementById('form-title');
    const formDesc = document.getElementById('form-desc');
    const submitBtnText = document.getElementById('btn-submit-text');
    const cancelarBtn = document.getElementById('cancelar-btn');

    const inputId = document.getElementById('id_cliente');
    const inputNome = document.getElementById('nome');
    const inputEmail = document.getElementById('email');
    const inputTelefone = document.getElementById('telefone');
    const inputLimite = document.getElementById('limite_credito');
    const inputSaldo = document.getElementById('saldo_devedor');

    // Modal de pagamento
    const modal = document.getElementById('pagamento-modal');
    const modalId = document.getElementById('id_cliente_pagamento');
    const modalNome = document.getElementById('modal-cliente-nome');
    const modalSaldo = document.getElementById('modal-cliente-saldo');
    const inputPagamento = document.getElementById('valor_pagamento');

    // 1. Popula dados p/ edição
    window.editarCliente = function(cli) {
        formTitle.textContent = "Editar Cliente";
        formDesc.textContent = `Ajustando detalhes do Cliente Cód #${cli.id_cliente}.`;
        submitBtnText.textContent = "Salvar Alterações";
        cancelarBtn.classList.remove('hidden');

        inputId.value = cli.id_cliente;
        inputNome.value = cli.nome;
        inputEmail.value = cli.email || '';
        inputTelefone.value = cli.telefone || '';
        if (inputLimite) inputLimite.value = parseFloat(cli.limite_credito).toFixed(2);
        inputSaldo.value = parseFloat(cli.saldo_devedor).toFixed(2);

        inputNome.focus();
    };

    // Auto-carrega para edição caso id_cliente venha via URL (origem Dashboard)
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('id_cliente');
    if (editId && clientesMap[editId]) {
        setTimeout(() => {
            window.editarCliente(clientesMap[editId]);
            // Rola até o formulário de cadastro/edição
            const formContainer = document.getElementById('secao-formulario-cliente') || form;
            if (formContainer) {
                formContainer.scrollIntoView({ behavior: 'smooth' });
            }
        }, 150);
    }

    // 2. Limpa formulário
    window.limparFormulario = function() {
        formTitle.textContent = "Cadastrar Novo Cliente";
        formDesc.textContent = "Insira as informações de contato do cliente.";
        submitBtnText.textContent = "Cadastrar Cliente";
        cancelarBtn.classList.add('hidden');

        inputId.value = '';
        form.reset();
        if (inputLimite) inputLimite.value = "500.00";
        inputSaldo.value = "0.00";
    };

    // 3. Gerenciamento do Modal de pagamento
    window.abrirModalPagamento = function(cli) {
        modal.classList.remove('hidden');
        modalId.value = cli.id_cliente;
        modalNome.textContent = cli.nome;
        modalSaldo.textContent = `R$ ${parseFloat(cli.saldo_devedor).toFixed(2).replace('.', ',')}`;
        inputPagamento.value = parseFloat(cli.saldo_devedor).toFixed(2);
        inputPagamento.focus();
    };

    window.fecharModalPagamento = function() {
        modal.classList.add('hidden');
    };
});
</script>
