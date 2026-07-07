<div class="grid grid-cols-1 lg:grid-cols-12 gap-8" id="pdv-root">

    <!-- Left Side: Catalog of Products (RF-CX-001) -->
    <div class="lg:col-span-7 bg-white p-6 rounded-2xl border border-sand-100 shadow-sm flex flex-col h-[calc(100vh-140px)]">
        <!-- Title and Search -->
        <div class="mb-6">
            <h1 class="font-serif text-2xl font-bold text-gray-800 mb-1">Caixa / PDV</h1>
            <p class="text-xs text-gray-400 mb-4">Adicione produtos ao pedido atual clicando nos itens do catálogo.</p>
            
            <!-- Dynamic Search input (RF-CX-002) -->
            <div class="relative">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input type="text" id="catalog-search" placeholder="Buscar produtos por nome (digite no mínimo 3 caracteres)..." 
                       class="block w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
            </div>
        </div>

        <!-- Products Grid Container -->
        <div class="flex-1 overflow-y-auto pr-1" id="products-catalog-container">
            <!-- Catalog is loaded dynamically here via JS -->
            <div class="flex items-center justify-center h-48 text-gray-400 text-xs">
                <span class="animate-pulse">Carregando catálogo de produtos...</span>
            </div>
        </div>
    </div>

    <!-- Right Side: Order Cart Panel (RF-CX-003 / RF-CX-005) -->
    <div class="lg:col-span-5 flex flex-col gap-6">
        
        <!-- Shopping Cart container -->
        <div class="bg-white p-6 rounded-2xl border border-sand-100 shadow-sm flex flex-col h-[calc(100vh-280px)]">
            <div class="flex items-center justify-between border-b border-sand-50 pb-4 mb-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="shopping-cart" class="w-5 h-5 text-sage-500"></i>
                    <h2 class="font-serif text-lg font-bold text-gray-800">Sacola de Compras</h2>
                </div>
                <button id="clear-cart-btn" class="text-rose-500 hover:text-rose-700 text-xs font-semibold flex items-center gap-1 transition-all">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Esvaziar</span>
                </button>
            </div>

            <!-- List of selected Cart items -->
            <div class="flex-1 overflow-y-auto pr-1 space-y-3" id="cart-items-container">
                <!-- Cart list populated dynamically via JS -->
                <div class="flex flex-col items-center justify-center h-full text-center text-gray-400 py-12">
                    <i data-lucide="shopping-bag" class="w-10 h-10 stroke-1 mb-2 text-sand-500"></i>
                    <p class="text-xs font-medium">Sua sacola está vazia.</p>
                </div>
            </div>

            <!-- Checkout calculations subpanel (RF-CX-004) -->
            <div class="border-t border-sand-50 pt-4 mt-4 space-y-2.5">
                <div class="flex justify-between text-xs text-gray-500">
                    <span>Subtotal</span>
                    <span id="calc-subtotal" class="font-semibold font-mono">R$ 0,00</span>
                </div>
                <div class="flex justify-between text-xs text-gray-500">
                    <span>Imposto Estimado (5%)</span>
                    <span id="calc-imposto" class="font-semibold font-mono">R$ 0,00</span>
                </div>
                <div class="flex justify-between text-sm text-gray-800 border-t border-dashed border-sand-100 pt-2.5">
                    <span class="font-bold">Total do Pedido</span>
                    <span id="calc-total" class="text-base font-bold text-sage-500 font-mono">R$ 0,00</span>
                </div>
            </div>
        </div>

        <!-- Checkout Form container (RF-CX-006 / RF-CX-007) -->
        <div class="bg-white p-6 rounded-2xl border border-sand-100 shadow-sm space-y-4">
            <!-- Payment Form fields -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Pagamento</label>
                    <select id="forma-pagamento" class="block w-full py-2 px-3 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                        <option value="DEBITO">Débito</option>
                        <option value="CARTAO">Cartão de Crédito</option>
                        <option value="PIX">PIX</option>
                        <option value="CREDITO_LOJA">Crédito da Loja (Fiado)</option>
                    </select>
                </div>

                <!-- Client Search box (exigido se Crédito Loja, RF-CX-007) -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Cliente (Obrigatório p/ Crédito)</label>
                    <select id="cliente-select" class="block w-full py-2 px-3 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                        <option value="">Selecione um Cliente...</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?= $cli['id_cliente'] ?>" 
                                    data-limite="<?= $cli['limite_credito'] ?>" 
                                    data-saldo="<?= $cli['saldo_devedor'] ?>" 
                                    data-disponivel="<?= $cli['credito_disponivel'] ?>">
                                <?= htmlspecialchars($cli['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Client Credit indicators display (RF-CX-008) -->
            <div id="cliente-credito-paineis" class="hidden p-3 bg-sand-50 rounded-xl border border-sand-100 flex items-center justify-between gap-2 text-[10px] font-mono">
                <div>
                    <p class="text-gray-400 font-bold uppercase text-[8px] tracking-wider">Limite Concedido</p>
                    <span id="display-limite" class="text-gray-700 font-bold">R$ 0,00</span>
                </div>
                <div>
                    <p class="text-gray-400 font-bold uppercase text-[8px] tracking-wider">Saldo Devedor</p>
                    <span id="display-saldo" class="text-rose-500 font-bold">R$ 0,00</span>
                </div>
                <div class="text-right">
                    <p class="text-gray-400 font-bold uppercase text-[8px] tracking-wider">Crédito Disponível</p>
                    <span id="display-disponivel" class="text-sage-500 font-bold">R$ 0,00</span>
                </div>
            </div>

            <!-- Checkout Action buttons -->
            <button id="concluir-venda-btn" 
                    class="w-full py-3 px-4 bg-[#8DA574] hover:bg-[#849B48] text-white text-sm font-semibold rounded-xl shadow-md hover:shadow-lg transition-all duration-200 focus:outline-none flex items-center justify-center gap-2">
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                <span>Concluir Venda (Faturar)</span>
            </button>
        </div>

    </div>
</div>

<!-- PDV Frontend Interaction Scripts (compliant with speed requirements RNF-003) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Array para segurar o carrinho
    let cart = [];
    let productsCatalog = [];
    const aliquotaImposto = <?= $aliquotaImposto ?>;
    const basePath = "<?= $basePath ?>";

    // Elementos do DOM
    const productsContainer = document.getElementById('products-catalog-container');
    const searchInput = document.getElementById('catalog-search');
    const cartItemsContainer = document.getElementById('cart-items-container');
    const clearCartBtn = document.getElementById('clear-cart-btn');
    const concluirVendaBtn = document.getElementById('concluir-venda-btn');
    const formaPagamentoSelect = document.getElementById('forma-pagamento');
    const clienteSelect = document.getElementById('cliente-select');
    
    const calcSubtotal = document.getElementById('calc-subtotal');
    const calcImposto = document.getElementById('calc-imposto');
    const calcTotal = document.getElementById('calc-total');

    const creditPanel = document.getElementById('cliente-credito-paineis');
    const displayLimite = document.getElementById('display-limite');
    const displaySaldo = document.getElementById('display-saldo');
    const displayDisponivel = document.getElementById('display-disponivel');

    // 1. Carrega produtos via API
    function carregarProdutos(query = '') {
        fetch(`${basePath}/pdv/produtos?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                productsCatalog = data;
                renderizarCatalogo();
            })
            .catch(err => console.error("Erro ao carregar catálogo:", err));
    }

    function getProductImageUrlJS(nome) {
        const term = nome.toLowerCase();
        if (term.includes('vestido')) {
            return 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=300&auto=format&fit=crop&q=60';
        } else if (term.includes('blusa') || term.includes('seda')) {
            return 'https://images.unsplash.com/photo-1548624149-f190c658d5f3?w=300&auto=format&fit=crop&q=60';
        } else if (term.includes('calça') || term.includes('calca')) {
            return 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=300&auto=format&fit=crop&q=60';
        } else if (term.includes('bolsa') || term.includes('couro')) {
            return 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=300&auto=format&fit=crop&q=60';
        } else if (term.includes('blazer') || term.includes('casaco') || term.includes('paletó') || term.includes('paleto')) {
            return 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=300&auto=format&fit=crop&q=60';
        } else if (term.includes('brinco') || term.includes('anel') || term.includes('joia') || term.includes('jóia')) {
            return 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=300&auto=format&fit=crop&q=60';
        } else if (term.includes('sapato') || term.includes('salto') || term.includes('scarpin')) {
            return 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=300&auto=format&fit=crop&q=60';
        } else if (term.includes('perfume') || term.includes('fragrância') || term.includes('fragrancia')) {
            return 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=300&auto=format&fit=crop&q=60';
        }
        return 'https://images.unsplash.com/photo-1544441893-675973e31985?w=300&auto=format&fit=crop&q=60';
    }

    // 2. Renderiza produtos no catálogo (RF-CX-001)
    function renderizarCatalogo() {
        if (productsCatalog.length === 0) {
            productsContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12 text-gray-400 text-xs">
                    <i data-lucide="package-search" class="w-8 h-8 stroke-1 mb-2"></i>
                    <p>Nenhum produto correspondente localizado.</p>
                </div>`;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            return;
        }

        let html = '<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">';
        productsCatalog.forEach(prod => {
            const hasStock = parseInt(prod.quantidade) > 0;
            const cardBg = hasStock ? 'bg-gray-50/50 hover:bg-sage-50/20 border-sand-100 hover:border-sage-500/40 cursor-pointer' : 'bg-gray-100 border-gray-200 opacity-60 cursor-not-allowed';
            const imageUrl = getProductImageUrlJS(prod.nome);
            
            html += `
                <div class="p-3 rounded-xl border ${cardBg} transition-all flex flex-col justify-between overflow-hidden group" 
                     data-id="${prod.id_produto}" 
                     data-stock="${prod.quantidade}"
                     onclick="window.adicionarAoCarrinho(${prod.id_produto}, ${hasStock})">
                    <div>
                        <!-- Imagem do Produto (Premium Visual Representation) -->
                        <div class="w-full h-28 rounded-lg overflow-hidden mb-2.5 bg-gray-100 border border-sand-100/40 relative">
                            <img src="${imageUrl}" alt="${prod.nome}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        </div>
                        <span class="inline-block text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Cód: #${prod.id_produto}</span>
                        <h4 class="text-xs font-semibold text-gray-700 leading-tight mb-1 truncate" title="${prod.nome}">${prod.nome}</h4>
                    </div>
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-xs font-bold text-gray-800 font-mono">R$ ${parseFloat(prod.preco).toFixed(2).replace('.', ',')}</span>
                        <span class="text-[9px] font-semibold px-2 py-0.5 rounded-full ${hasStock ? 'bg-sage-100 text-sage-600' : 'bg-red-100 text-red-600'} font-mono">
                            ${hasStock ? prod.quantidade + ' un' : 'Zerar'}
                        </span>
                    </div>
                </div>`;
        });
        html += '</div>';
        productsContainer.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    // Exporta função para escopo global do HTML onclick
    window.adicionarAoCarrinho = function(id, hasStock) {
        if (!hasStock) {
            alert("Este produto está com o estoque zerado!");
            return;
        }

        const prod = productsCatalog.find(p => parseInt(p.id_produto) === id);
        if (!prod) return;

        const cartItem = cart.find(item => item.id_produto === id);
        if (cartItem) {
            if (cartItem.quantidade + 1 > parseInt(prod.quantidade)) {
                alert(`Desculpe! Estoque máximo insuficiente para '${prod.nome}'. Solicitado: ${cartItem.quantidade + 1}, Disponível: ${prod.quantidade}`);
                return;
            }
            cartItem.quantidade++;
        } else {
            cart.push({
                id_produto: prod.id_produto,
                nome: prod.nome,
                preco: parseFloat(prod.preco),
                quantidade: 1,
                estoque_maximo: parseInt(prod.quantidade)
            });
        }

        atualizarCarrinho();
    };

    // 3. Renderiza o Carrinho e calcula totais (RF-CX-004)
    function atualizarCarrinho() {
        if (cart.length === 0) {
            cartItemsContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-center text-gray-400 py-12">
                    <i data-lucide="shopping-bag" class="w-10 h-10 stroke-1 mb-2 text-sand-500"></i>
                    <p class="text-xs font-medium">Sua sacola está vazia.</p>
                </div>`;
            calcSubtotal.textContent = "R$ 0,00";
            calcImposto.textContent = "R$ 0,00";
            calcTotal.textContent = "R$ 0,00";
            if (typeof lucide !== 'undefined') lucide.createIcons();
            validarLimites();
            return;
        }

        let html = '';
        let subtotal = 0.00;

        cart.forEach(item => {
            const rowTotal = item.preco * item.quantidade;
            subtotal += rowTotal;

            html += `
                <div class="flex items-center justify-between p-3 bg-gray-50 border border-sand-50 rounded-xl hover:bg-sand-50/30 transition-all">
                    <div class="overflow-hidden flex-1 pr-3">
                        <h4 class="text-xs font-semibold text-gray-700 truncate">${item.nome}</h4>
                        <span class="text-[10px] text-gray-400 font-mono">R$ ${item.preco.toFixed(2).replace('.', ',')} / un</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Ajustador de quantidade -->
                        <div class="flex items-center border border-sand-100 rounded-lg overflow-hidden bg-white shrink-0">
                            <button onclick="window.alterarQtd(${item.id_produto}, -1)" class="px-2 py-1 hover:bg-sand-50 text-gray-500 text-xs">-</button>
                            <span class="px-3 py-1 font-semibold text-xs font-mono text-gray-700">${item.quantidade}</span>
                            <button onclick="window.alterarQtd(${item.id_produto}, 1)" class="px-2 py-1 hover:bg-sand-50 text-gray-500 text-xs">+</button>
                        </div>
                        
                        <div class="text-right w-20 shrink-0">
                            <span class="text-xs font-bold text-gray-700 font-mono">R$ ${rowTotal.toFixed(2).replace('.', ',')}</span>
                        </div>

                        <button onclick="window.removerItem(${item.id_produto})" class="p-1 hover:bg-rose-50 text-rose-500 hover:text-rose-700 rounded-lg transition-all shrink-0">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>`;
        });

        cartItemsContainer.innerHTML = html;
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Faz os cálculos (RF-CX-004)
        const imposto = subtotal * aliquotaImposto;
        const total = subtotal + imposto;

        calcSubtotal.textContent = `R$ ${subtotal.toFixed(2).replace('.', ',')}`;
        calcImposto.textContent = `R$ ${imposto.toFixed(2).replace('.', ',')}`;
        calcTotal.textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;

        validarLimites();
    }

    window.alterarQtd = function(id, delta) {
        const item = cart.find(i => i.id_produto === id);
        if (!item) return;

        item.quantidade += delta;
        if (item.quantidade <= 0) {
            window.removerItem(id);
        } else if (item.quantidade > item.estoque_maximo) {
            alert(`Quantidade solicitada excede o estoque disponível no momento (${item.estoque_maximo} un).`);
            item.quantidade = item.estoque_maximo;
        } else {
            atualizarCarrinho();
        }
    };

    window.removerItem = function(id) {
        cart = cart.filter(i => i.id_produto !== id);
        atualizarCarrinho();
    };

    // 4. Limpa todo o carrinho
    clearCartBtn.addEventListener('click', () => {
        if (cart.length > 0 && confirm("Deseja realmente remover todos os itens selecionados?")) {
            cart = [];
            atualizarCarrinho();
        }
    });

    // 5. Tratamento de busca de produto em tempo real (RF-CX-002)
    let searchTimeout = null;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const q = e.target.value;
        searchTimeout = setTimeout(() => {
            carregarProdutos(q);
        }, 300); // Debounce de 300ms
    });

    // 6. Tratamento de crédito e seleções de cliente
    formaPagamentoSelect.addEventListener('change', () => {
        const isCredito = formaPagamentoSelect.value === 'CREDITO_LOJA';
        if (isCredito) {
            creditPanel.classList.remove('hidden');
            validarLimites();
        } else {
            creditPanel.classList.add('hidden');
            concluirVendaBtn.disabled = false;
            concluirVendaBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    });

    clienteSelect.addEventListener('change', () => {
        validarLimites();
    });

    function validarLimites() {
        const isCredito = formaPagamentoSelect.value === 'CREDITO_LOJA';
        if (!isCredito) return;

        const opt = clienteSelect.options[clienteSelect.selectedIndex];
        if (!opt || !opt.value) {
            creditPanel.classList.add('hidden');
            // Desabilita botão se for crédito mas não tem cliente selecionado (RF-CX-007)
            concluirVendaBtn.disabled = true;
            concluirVendaBtn.classList.add('opacity-50', 'cursor-not-allowed');
            return;
        }

        creditPanel.classList.remove('hidden');
        
        const limite = parseFloat(opt.getAttribute('data-limite') || '0');
        const saldo = parseFloat(opt.getAttribute('data-saldo') || '0');
        const disponivel = parseFloat(opt.getAttribute('data-disponivel') || '0');

        displayLimite.textContent = `R$ ${limite.toFixed(2).replace('.', ',')}`;
        displaySaldo.textContent = `R$ ${saldo.toFixed(2).replace('.', ',')}`;
        displayDisponivel.textContent = `R$ ${disponivel.toFixed(2).replace('.', ',')}`;

        // Obtém o total do pedido
        let totalPedido = 0.00;
        cart.forEach(i => totalPedido += i.preco * i.quantidade);
        totalPedido = totalPedido + (totalPedido * aliquotaImposto);

        // Bloqueio por limite excedido (RF-CX-009)
        if (totalPedido > disponivel) {
            concluirVendaBtn.disabled = true;
            concluirVendaBtn.classList.add('opacity-50', 'cursor-not-allowed');
            concluirVendaBtn.title = `Limite excedido! O total de R$ ${totalPedido.toFixed(2).replace('.', ',')} ultrapassa o crédito de R$ ${disponivel.toFixed(2).replace('.', ',')}`;
        } else {
            concluirVendaBtn.disabled = false;
            concluirVendaBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            concluirVendaBtn.removeAttribute('title');
        }
    }

    // 7. Conclui a Venda / Checkout (RF-CX-010)
    concluirVendaBtn.addEventListener('click', () => {
        if (cart.length === 0) {
            alert("A sacola de compras está vazia!");
            return;
        }

        const formaPagamento = formaPagamentoSelect.value;
        const id_cliente = clienteSelect.value;

        if (formaPagamento === 'CREDITO_LOJA' && !id_cliente) {
            alert("Para faturamento via Crédito de Loja (Fiado), selecione obrigatoriamente um cliente.");
            return;
        }

        concluirVendaBtn.disabled = true;
        concluirVendaBtn.innerHTML = '<i data-lucide="loader" class="w-5 h-5 animate-spin"></i><span>Faturando pedido...</span>';
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Envia requisição via Fetch AJAX para checkout
        fetch(`${basePath}/pdv/checkout`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id_cliente: id_cliente,
                forma_pagamento: formaPagamento,
                items: cart
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.sucesso) {
                // Alerta de Sucesso
                alert("Venda registrada e faturada com sucesso!");
                
                // Recarrega a página atual para limpar carrinho e atualizar o estoque no catálogo
                window.location.href = `${basePath}/pdv?sucesso=${encodeURIComponent('Venda faturada com sucesso!')}`;
            } else {
                alert(`Falha ao faturar venda: ${data.erro}`);
                
                // Restaura botão de conclusão
                concluirVendaBtn.disabled = false;
                concluirVendaBtn.innerHTML = '<i data-lucide="check-circle" class="w-5 h-5"></i><span>Concluir Venda (Faturar)</span>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }
        })
        .catch(err => {
            console.error("Erro no checkout:", err);
            alert("Erro interno de comunicação com o servidor.");
            concluirVendaBtn.disabled = false;
            concluirVendaBtn.innerHTML = '<i data-lucide="check-circle" class="w-5 h-5"></i><span>Concluir Venda (Faturar)</span>';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
    });

    // Inicializa carregando todos os produtos
    carregarProdutos();
});
</script>
