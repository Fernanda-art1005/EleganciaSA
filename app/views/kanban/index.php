<div class="space-y-6">

    <!-- Header Actions (RF-TA-006) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl font-bold text-gray-800">Quadro de Tarefas (Kanban)</h1>
            <p class="text-xs text-gray-400">Gerencie atividades do showroom, metas de vendas e follow-ups de clientes.</p>
        </div>
        
        <div class="flex items-center gap-2.5 self-start">
            <!-- Add Task Trigger -->
            <button onclick="window.abrirModalTarefa()" 
                    class="py-2.5 px-4 bg-[#8DA574] hover:bg-[#849B48] text-white text-xs font-semibold rounded-xl shadow-sm hover:shadow-md transition-all flex items-center gap-1.5">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Nova Tarefa</span>
            </button>

            <!-- Add Column Trigger -->
            <button onclick="window.abrirModalColuna()" 
                    class="py-2.5 px-4 border border-[#C4BC96]/45 text-[#BAAE79] hover:bg-sand-50 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5">
                <i data-lucide="columns" class="w-4 h-4"></i>
                <span>Gerenciar Colunas</span>
            </button>
        </div>
    </div>

    <!-- Horizontal Kanban Columns Wrapper (RF-TA-001) -->
    <div class="flex flex-col md:flex-row items-stretch gap-6 overflow-x-auto pb-4" id="kanban-columns-container">
        
        <?php foreach ($quadro as $id_col => $col): 
            $tasks = $col['tarefas'];
            $qtd = count($tasks);
        ?>
            <!-- Kanban Column Board -->
            <div class="w-full md:w-80 shrink-0 bg-[#EFECE2]/35 border border-sand-100/70 p-4 rounded-2xl flex flex-col min-h-[450px]"
                 data-col-id="<?= $id_col ?>">
                
                <!-- Column Header -->
                <div class="flex items-center justify-between mb-4 border-b border-sand-100/50 pb-2">
                    <div class="overflow-hidden pr-2">
                        <h3 class="font-serif font-bold text-sm text-gray-700 truncate" title="<?= htmlspecialchars($col['titulo']) ?>"><?= htmlspecialchars($col['titulo']) ?></h3>
                        <span class="text-[10px] text-gray-400 font-semibold font-mono"><?= $qtd ?> tarefa<?= ($qtd !== 1) ? 's' : '' ?></span>
                    </div>
                    <div class="flex items-center gap-1">
                        <!-- Edit Column -->
                        <button onclick="window.editarColuna(<?= $id_col ?>, '<?= htmlspecialchars(addslashes($col['titulo'])) ?>')" 
                                class="p-1 text-gray-400 hover:text-sage-500 rounded" title="Renomear Coluna">
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                        </button>
                        <!-- Delete Column (RN-TA-002: safe block if has tasks) -->
                        <a href="<?= $basePath ?>/kanban/coluna/excluir?id_coluna=<?= $id_col ?>" 
                           onclick="return confirm('Deseja realmente remover o funil/coluna \'<?= htmlspecialchars(addslashes($col['titulo'])) ?>\'? O sistema rejeitará se a coluna possuir tarefas pendentes.')"
                           class="p-1 text-gray-400 hover:text-rose-500 rounded" title="Excluir Coluna">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        </a>
                    </div>
                </div>

                <!-- Column Cards Content (RF-TA-003) -->
                <div class="flex-1 space-y-3.5 overflow-y-auto max-h-[480px] pr-1" id="column-cards-<?= $id_col ?>">
                    <?php if (empty($tasks)): ?>
                        <div class="h-28 border-2 border-dashed border-sand-100 rounded-xl flex items-center justify-center text-center text-gray-400 text-[11px] p-4">
                            Nenhuma tarefa nesta etapa.
                        </div>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): 
                            $venceHojeOuAtrasado = false;
                            $formattedDate = '';
                            if (!empty($task['data_vencimento'])) {
                                $formattedDate = date('d/m/Y', strtotime($task['data_vencimento']));
                                $venceHojeOuAtrasado = strtotime($task['data_vencimento']) <= strtotime(date('Y-m-d'));
                            }

                            // Priorities colors (RN-TA-004)
                            $priorityBg = 'bg-gray-100 text-gray-600';
                            if ($task['prioridade'] === 'ALTA') {
                                $priorityBg = 'bg-rose-50 text-rose-600 border border-rose-100';
                            } elseif ($task['prioridade'] === 'MEDIA') {
                                $priorityBg = 'bg-amber-50 text-amber-600 border border-amber-100';
                            } elseif ($task['prioridade'] === 'BAIXA') {
                                $priorityBg = 'bg-emerald-50 text-emerald-600 border border-emerald-100';
                            }
                        ?>
                            <!-- Task card (RF-TA-005: support column moves) -->
                            <div class="bg-white p-4 rounded-xl border border-sand-100 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between"
                                 id="task-card-<?= $task['id_tarefa'] ?>">
                                
                                <div>
                                    <!-- Priority & Move buttons -->
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="text-[9px] font-bold tracking-wider px-2 py-0.5 rounded-full uppercase <?= $priorityBg ?>">
                                            <?= $task['prioridade'] ?>
                                        </span>
                                        
                                        <!-- Rapid Column Mover selector (RF-TA-005) -->
                                        <select onchange="window.moverTarefaRapido(<?= $task['id_tarefa'] ?>, this.value)"
                                                class="text-[9px] font-semibold bg-sand-50 border border-[#C4BC96]/30 px-1 py-0.5 rounded text-gray-500 focus:outline-none">
                                            <option value="">Mudar Etapa...</option>
                                            <?php foreach ($colunas as $c): ?>
                                                <option value="<?= $c['id_coluna'] ?>" <?= ($c['id_coluna'] == $id_col) ? 'disabled class="text-gray-300"' : '' ?>><?= htmlspecialchars($c['titulo']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Title and Description -->
                                    <h4 class="text-xs font-bold leading-tight mb-1 truncate <?= (!empty($task['concluida'])) ? 'line-through text-gray-400' : 'text-gray-800' ?>" title="<?= htmlspecialchars($task['titulo']) ?>">
                                        <?php if (!empty($task['concluida'])): ?>
                                            <span class="text-emerald-500 font-bold mr-1">✓</span>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($task['titulo']) ?>
                                    </h4>
                                    <p class="text-[11px] leading-normal mb-3 line-clamp-2 <?= (!empty($task['concluida'])) ? 'text-gray-300' : 'text-gray-500' ?>" title="<?= htmlspecialchars($task['descricao']) ?>">
                                        <?= htmlspecialchars($task['descricao'] ?: 'Sem descrição informada.') ?>
                                    </p>
                                </div>

                                <!-- Card footer with Dates & Owner info (RN-TA-001 / RN-TA-003) -->
                                <div class="border-t border-sand-50 pt-2.5 mt-2 flex items-center justify-between text-[10px] text-gray-400">
                                    <div class="flex items-center gap-1">
                                        <div class="w-5 h-5 rounded-full bg-sand-100 text-[#BAAE79] flex items-center justify-center font-bold text-[8px] border border-sand-200 font-mono shrink-0" 
                                             title="Responsável: <?= htmlspecialchars($task['responsavel_nome'] ?: 'Sem Atribuição') ?>">
                                            <?= strtoupper(substr($task['responsavel_nome'] ?: 'SA', 0, 2)) ?>
                                        </div>
                                        <span class="font-medium text-gray-600 truncate max-w-[80px]" title="<?= htmlspecialchars($task['responsavel_nome']) ?>">
                                            <?= htmlspecialchars($task['responsavel_nome'] ?: 'Sem Atribuição') ?>
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-1.5 font-semibold font-mono shrink-0">
                                        <?php if (!empty($formattedDate)): ?>
                                            <span class="flex items-center gap-0.5 <?= $venceHojeOuAtrasado ? 'text-rose-600 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-100 animate-pulse' : 'text-gray-400' ?>" title="Prazo final">
                                                <i data-lucide="calendar" class="w-3 h-3"></i>
                                                <?= $formattedDate ?>
                                            </span>
                                        <?php endif; ?>

                                        <!-- Actions triggers -->
                                        <div class="flex items-center gap-0.5 shrink-0 ml-1.5">
                                            <?php if (empty($task['concluida'])): ?>
                                                <a href="<?= $basePath ?>/task/concluir?id_tarefa=<?= $task['id_tarefa'] ?>" class="p-1 hover:bg-emerald-50 text-emerald-500 rounded" title="Concluir Tarefa">
                                                    <i data-lucide="check-circle-2" class="w-3 h-3"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button onclick="window.editarTarefa(<?= htmlspecialchars(json_encode($task)) ?>)" class="p-1 hover:bg-sage-50 text-sage-500 rounded" title="Editar">
                                                <i data-lucide="edit-2" class="w-3 h-3"></i>
                                            </button>
                                            <a href="<?= $basePath ?>/task/destroy?id_tarefa=<?= $task['id_tarefa'] ?>" onclick="return confirm('Deseja realmente remover esta tarefa do quadro?')" class="p-1 hover:bg-rose-50 text-rose-500 rounded" title="Excluir">
                                                <i data-lucide="trash" class="w-3 h-3"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>

    </div>
</div>

<!-- Modal: Criar / Editar Tarefa (RF-TA-002) -->
<div id="tarefa-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-sand-100 max-w-md w-full overflow-hidden">
        <div class="p-5 border-b border-sand-50 flex items-center justify-between bg-sand-50/30">
            <h3 id="modal-tarefa-title" class="font-serif text-lg font-bold text-gray-800">Nova Tarefa</h3>
            <button onclick="window.fecharModalTarefa()" class="p-1 hover:bg-sand-100 rounded-lg text-gray-400">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form id="tarefa-form" action="<?= $basePath ?>/task/store" method="POST" class="p-5 space-y-4">
            <input type="hidden" name="id_tarefa" id="id_tarefa" value="">

            <div>
                <label for="titulo" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Título da Atividade</label>
                <input type="text" id="titulo" name="titulo" required placeholder="ex: Organizar vitrine nova coleção"
                       class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
            </div>

            <div>
                <label for="descricao" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Descrição / Instruções</label>
                <textarea id="descricao" name="descricao" rows="2" placeholder="Descreva os objetivos desta tarefa comercial..."
                          class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="data_vencimento" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Data de Prazo (Vencimento)</label>
                    <input type="date" id="data_vencimento" name="data_vencimento" required
                           class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                </div>

                <div>
                    <label for="prioridade" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Prioridade (Gravidade, RN-TA-004)</label>
                    <select id="prioridade" name="prioridade" required
                            class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                        <option value="BAIXA">Baixa</option>
                        <option value="MEDIA" selected>Média</option>
                        <option value="ALTA">Alta</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="id_responsavel" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Responsável Atribuído</label>
                    <select id="id_responsavel" name="id_responsavel" required
                            class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                        <option value="" disabled selected>Selecione o Responsável...</option>
                        <?php foreach ($responsaveis as $resp): ?>
                            <option value="<?= $resp['id_usuario'] ?>"><?= htmlspecialchars($resp['nome']) ?> (<?= $resp['nivel_acesso'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="id_coluna" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Etapa / Funil</label>
                    <select id="id_coluna" name="id_coluna" required
                            class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
                        <?php foreach ($colunas as $c): ?>
                            <option value="<?= $c['id_coluna'] ?>"><?= htmlspecialchars($c['titulo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex gap-2.5 pt-4 border-t border-sand-50 mt-4">
                <button type="submit" class="flex-1 py-2 px-4 bg-[#8DA574] hover:bg-[#849B48] text-white text-xs font-semibold rounded-xl shadow-md transition-all flex items-center justify-center gap-1">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Salvar Tarefa</span>
                </button>
                <button type="button" onclick="window.fecharModalTarefa()" class="py-2 px-3 border border-sand-500 text-sand-600 hover:bg-sand-50 rounded-xl text-xs font-semibold">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Criar / Editar Coluna / Funil (RF-TA-006) -->
<div id="coluna-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl border border-sand-100 max-w-sm w-full overflow-hidden">
        <div class="p-5 border-b border-sand-50 flex items-center justify-between bg-sand-50/30">
            <h3 id="modal-coluna-title" class="font-serif text-lg font-bold text-gray-800">Gerenciar Colunas</h3>
            <button onclick="window.fecharModalColuna()" class="p-1 hover:bg-sand-100 rounded-lg text-gray-400">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <form action="<?= $basePath ?>/kanban/coluna/salvar" method="POST" class="p-5 space-y-4">
            <input type="hidden" name="id_coluna" id="id_coluna" value="">

            <div>
                <label for="titulo_coluna" class="block text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">Nome da Etapa / Funil</label>
                <input type="text" id="titulo_coluna" name="titulo" required placeholder="ex: Em Andamento, Concluído"
                       class="block w-full px-3 py-2 bg-gray-50 border border-[#C4BC96]/30 rounded-xl text-xs placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#8DA574] focus:border-transparent transition-all">
            </div>

            <div class="flex gap-2.5 pt-4">
                <button type="submit" class="flex-1 py-2 px-4 bg-[#8DA574] hover:bg-[#849B48] text-white text-xs font-semibold rounded-xl shadow-md transition-all flex items-center justify-center gap-1">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Salvar Coluna</span>
                </button>
                <button type="button" onclick="window.fecharModalColuna()" class="py-2 px-3 border border-sand-500 text-sand-600 hover:bg-sand-50 rounded-xl text-xs font-semibold">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Kanban Board Client Interactivity scripts (conforming to RNF-010 speed standards) -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Modal Tarefa
    const tModal = document.getElementById('tarefa-modal');
    const tTitle = document.getElementById('modal-tarefa-title');
    const inputIdTask = document.getElementById('id_tarefa');
    const inputTituloTask = document.getElementById('titulo');
    const inputDescTask = document.getElementById('descricao');
    const inputVencTask = document.getElementById('data_vencimento');
    const selectPrioTask = document.getElementById('prioridade');
    const selectRespTask = document.getElementById('id_responsavel');
    const selectColTask = document.getElementById('id_coluna');

    // Modal Coluna
    const cModal = document.getElementById('coluna-modal');
    const cTitle = document.getElementById('modal-coluna-title');
    const inputIdCol = document.getElementById('id_coluna');
    const inputTituloCol = document.getElementById('titulo_coluna');

    const basePath = "<?= $basePath ?>";

    // 1. Abre modal de tarefa (RF-TA-002)
    window.abrirModalTarefa = function() {
        tModal.classList.remove('hidden');
        tTitle.textContent = "Nova Tarefa";
        document.getElementById('tarefa-form').action = `${basePath}/task/store`;
        inputIdTask.value = '';
        inputTituloTask.value = '';
        inputDescTask.value = '';
        // Define a data de vencimento padrão como hoje
        const hoje = new Date();
        const offset = hoje.getTimezoneOffset();
        const dataLocal = new Date(hoje.getTime() - (offset*60*1000));
        inputVencTask.value = dataLocal.toISOString().split('T')[0];
        selectPrioTask.value = 'MEDIA';
        selectRespTask.value = '';
        selectColTask.value = selectColTask.options[0] ? selectColTask.options[0].value : '';
        inputTituloTask.focus();
    };

    window.fecharModalTarefa = function() {
        tModal.classList.add('hidden');
    };

    // 2. Popula tarefa p/ edição (RF-TA-003)
    window.editarTarefa = function(task) {
        tModal.classList.remove('hidden');
        tTitle.textContent = "Editar Tarefa";
        document.getElementById('tarefa-form').action = `${basePath}/task/update`;
        
        inputIdTask.value = task.id_tarefa;
        inputTituloTask.value = task.titulo;
        inputDescTask.value = task.descricao || '';
        inputVencTask.value = task.data_vencimento || '';
        selectPrioTask.value = task.prioridade;
        selectRespTask.value = task.id_responsavel || '';
        selectColTask.value = task.id_coluna;

        inputTituloTask.focus();
    };

    // 3. Abre modal de coluna (RF-TA-006)
    window.abrirModalColuna = function() {
        cModal.classList.remove('hidden');
        cTitle.textContent = "Cadastrar Nova Coluna";
        inputIdCol.value = '';
        inputTituloCol.value = '';
        inputTituloCol.focus();
    };

    window.editarColuna = function(id, titulo) {
        cModal.classList.remove('hidden');
        cTitle.textContent = "Renomear Coluna / Funil";
        inputIdCol.value = id;
        inputTituloCol.value = titulo;
        inputTituloCol.focus();
    };

    window.fecharModalColuna = function() {
        cModal.classList.add('hidden');
    };

    // 4. Executa movimentação rápida por seleção (RF-TA-005)
    window.moverTarefaRapido = function(id_tarefa, id_coluna_destino) {
        if (!id_coluna_destino) return;

        // AJAX Fetch para disparar movimentação no backend com menos de 300ms de delay
        fetch(`${basePath}/kanban/mover?id_tarefa=${id_tarefa}&id_coluna=${id_coluna_destino}`)
            .then(res => res.json())
            .then(data => {
                if (data.sucesso) {
                    // Recarrega de forma silenciosa e instantânea movendo o card no DOM
                    const card = document.getElementById(`task-card-${id_tarefa}`);
                    const targetContainer = document.getElementById(`column-cards-${id_coluna_destino}`);
                    
                    if (card && targetContainer) {
                        // Se o container de destino continha a mensagem "vazio", limpa-a
                        if (targetContainer.innerHTML.includes("Nenhuma tarefa nesta etapa")) {
                            targetContainer.innerHTML = "";
                        }
                        
                        // Move o card
                        targetContainer.appendChild(card);
                        
                        // Atualiza as opções do select de destino
                        const select = card.querySelector('select');
                        if (select) {
                            select.value = ''; // reseta
                            // Desabilita a opção atual correspondente à nova coluna
                            Array.from(select.options).forEach(opt => {
                                if (opt.value == id_coluna_destino) {
                                    opt.disabled = true;
                                    opt.classList.add('text-gray-300');
                                } else {
                                    opt.disabled = false;
                                    opt.classList.remove('text-gray-300');
                                }
                            });
                        }
                    } else {
                        // Caso falte algum elemento no DOM, recarrega para manter conformidade
                        window.location.reload();
                    }
                } else {
                    alert(`Falha ao mover tarefa: ${data.erro}`);
                }
            })
            .catch(err => {
                console.error("Erro na movimentação rápida:", err);
                alert("Erro interno de comunicação.");
            });
    };
});
</script>
