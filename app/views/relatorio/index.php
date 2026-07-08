<div class="space-y-6">

    <!-- Header Actions (RF-RE-001 / RF-RE-002) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="font-serif text-2xl font-bold text-gray-800">Relatórios & Auditoria Imutável</h1>
            <p class="text-xs text-gray-400">Rastreabilidade completa de todas as operações críticas efetuadas no sistema (RN-AU-001 / RN-AU-002).</p>
        </div>
        
        <!-- Export to CSV Trigger (RF-RE-002) -->
        <a href="<?= $basePath ?>/relatorio/exportar" 
           class="py-2.5 px-4 bg-[#8DA574] hover:bg-[#849B48] text-white text-xs font-semibold rounded-xl shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-1.5 self-start">
            <i data-lucide="download-cloud" class="w-4 h-4"></i>
            <span>Exportar Planilha (CSV)</span>
        </a>
    </div>

    <!-- Alert Box: Immuntability notice -->
    <div class="p-4 bg-sand-100/35 border border-[#C4BC96]/35 rounded-2xl flex items-start gap-3 text-xs text-gray-600">
        <i data-lucide="shield-check" class="w-5 h-5 text-[#8DA574] shrink-0 mt-0.5"></i>
        <div>
            <p class="font-bold text-gray-800 mb-0.5">Aviso de Integridade Operacional (Audit Trail)</p>
            <p class="leading-relaxed">Este painel exibe a trilha de auditoria criptograficamente consistente do estabelecimento. De acordo com os regulamentos internos (RN-AU-001 / RN-AU-002), estes registros são **100% imutáveis** e protegidos contra modificações de qualquer perfil de usuário, garantindo transparência fiscal e contábil.</p>
        </div>
    </div>

    <!-- Chronological Audit Trail Table -->
    <div class="bg-white p-6 rounded-2xl border border-sand-100 shadow-sm flex flex-col">
        <div class="border-b border-sand-50 pb-4 mb-4 flex items-center justify-between">
            <h2 class="font-serif text-lg font-bold text-gray-800">Histórico Completo de Auditoria</h2>
            <span class="text-xs font-bold text-gray-500 bg-sand-50 border border-sand-100 px-2.5 py-0.5 rounded-full font-mono">
                <?= count($logs) ?> registros
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-sand-50 text-[#BAAE79] font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Data/Hora</th>
                        <th class="py-3 px-4">Ação</th>
                        <th class="py-3 px-4">Operador</th>
                        <th class="py-3 px-4">Valor Relacionado</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Descrição do Evento</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand-50/50">
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-8 text-gray-400 font-medium">Nenhum evento registrado no log de auditoria ainda.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): 
                            $badgeClass = 'bg-gray-50 text-gray-600 border border-gray-100';
                            if ($log['status'] === 'SUCESSO') {
                                $badgeClass = 'bg-emerald-50 text-emerald-600 border border-emerald-100';
                            } elseif ($log['status'] === 'FALHA' || $log['status'] === 'BLOQUEADO') {
                                $badgeClass = 'bg-rose-50 text-rose-600 border border-rose-100';
                            }
                        ?>
                            <tr class="hover:bg-sand-50/10">
                                <td class="py-3 px-4 font-mono text-gray-400 text-[10px]"><?= date('d/m/Y H:i:s', strtotime($log['data_hora'])) ?></td>
                                <td class="py-3 px-4 font-bold text-gray-700 font-mono text-[10px]"><?= $log['tipo_acao'] ?></td>
                                <td class="py-3 px-4 font-semibold text-gray-600"><?= htmlspecialchars($log['usuario']) ?></td>
                                <td class="py-3 px-4 font-mono text-gray-500">
                                    <?= ($log['valor'] !== 'N/A') ? 'R$ ' . number_format((float)$log['valor'], 2, ',', '.') : 'N/A' ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-block text-[9px] font-bold tracking-wider px-2 py-0.5 rounded uppercase <?= $badgeClass ?>">
                                        <?= $log['status'] ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-500 leading-relaxed max-w-sm" title="<?= htmlspecialchars($log['descricao']) ?>">
                                    <?= htmlspecialchars($log['descricao']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
