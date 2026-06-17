import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Plus, 
  Trash2, 
  Edit, 
  ChevronLeft, 
  ChevronRight, 
  CheckCircle, 
  X, 
  Calendar, 
  User, 
  Flag,
  ListFilter,
  AlertTriangle,
  FolderPlus
} from 'lucide-react';
import { Tarefa, MembroEquipe } from '../types';

interface TasksKanbanProps {
  tarefas: Tarefa[];
  membros: MembroEquipe[];
  onAdicionarTarefa: (t: Omit<Tarefa, 'id'>) => void;
  onAtualizarTarefa: (id: number, t: Omit<Tarefa, 'id'>) => void;
  onExcluirTarefa: (id: number) => void;
  podeEditar: boolean;
}

export default function TasksKanban({
  tarefas,
  membros,
  onAdicionarTarefa,
  onAtualizarTarefa,
  onExcluirTarefa,
  podeEditar
}: TasksKanbanProps) {
  // Lista de colunas (funis) padrão, persistida em estado local e mutável
  const [funis, setFunis] = useState<string[]>(['A Fazer', 'Em Progresso', 'Concluído']);
  
  // Filtro por prioridade
  const [filtroPrioridade, setFiltroPrioridade] = useState<'todos' | 'baixa' | 'media' | 'alta' | 'urgente'>('todos');

  // Modal para Criar/Editar Tarefa
  const [mostrarModalTarefa, setMostrarModalTarefa] = useState(false);
  const [tarefaEditando, setTarefaEditando] = useState<Tarefa | null>(null);

  // Campos do Formulário de Tarefa
  const [titulo, setTitulo] = useState('');
  const [descricao, setDescricao] = useState('');
  const [dataVencimento, setDataVencimento] = useState('');
  const [responsavel, setResponsavel] = useState('');
  const [prioridade, setPrioridade] = useState<'baixa' | 'media' | 'alta' | 'urgente'>('media');
  const [funilDestino, setFunilDestino] = useState('A Fazer');

  // Gerenciamento de Colunas (Modais de Adicionar/Renomear Funil)
  const [mostrarModalFunil, setMostrarModalFunil] = useState(false);
  const [novoFunilNome, setNovoFunilNome] = useState('');
  const [funilSendoEditado, setFunilSendoEditado] = useState<string | null>(null);

  // Toasts
  const [toast, setToast] = useState<{ texto: string; tipo: 'sucesso' | 'erro' } | null>(null);
  const [tarefaParaExcluir, setTarefaParaExcluir] = useState<Tarefa | null>(null);

  const exibirToast = (texto: string, tipo: 'sucesso' | 'erro' = 'sucesso') => {
    setToast({ texto, tipo });
    setTimeout(() => {
      setToast(null);
    }, 4000);
  };

  const handleSalvarTarefa = (e: React.FormEvent) => {
    e.preventDefault();
    if (!titulo.trim()) {
      exibirToast("Preencha o título da tarefa.", "erro");
      return;
    }

    // RN-TA-001: "Toda tarefa deve ter obrigatoriamente um responsável atribuído no momento da criação."
    if (!responsavel) {
      exibirToast("Selecione um responsável para a tarefa.", "erro");
      return;
    }

    // RN-TA-003: "A data de vencimento de uma tarefa não pode ser anterior à data de criação."
    if (dataVencimento) {
      const dVenc = new Date(dataVencimento);
      const hHoje = new Date();
      hHoje.setHours(0, 0, 0, 0);
      if (dVenc < hHoje) {
        exibirToast("A data de vencimento não pode ser anterior a hoje.", "erro");
        return;
      }
    }

    const dados = {
      titulo: titulo.trim(),
      descricao: descricao.trim(),
      dataVencimento: dataVencimento || new Date().toISOString().substring(0, 10),
      responsavel,
      prioridade,
      funil: funilDestino
    };

    if (tarefaEditando) {
      onAtualizarTarefa(tarefaEditando.id, dados);
      exibirToast(`Tarefa "${dados.titulo}" atualizada!`);
    } else {
      onAdicionarTarefa(dados);
      exibirToast(`Tarefa "${dados.titulo}" posta no Kanban!`);
    }

    setMostrarModalTarefa(false);
  };

  const abrirAdicionarTarefa = (col: string) => {
    setTarefaEditando(null);
    setTitulo('');
    setDescricao('');
    setDataVencimento(new Date().toISOString().substring(0, 10));
    setResponsavel(membros[0]?.nome || '');
    setPrioridade('media');
    setFunilDestino(col);
    setMostrarModalTarefa(true);
  };

  const abrirEditarTarefa = (t: Tarefa) => {
    setTarefaEditando(t);
    setTitulo(t.titulo);
    setDescricao(t.descricao);
    setDataVencimento(t.dataVencimento);
    setResponsavel(t.responsavel);
    setPrioridade(t.prioridade);
    setFunilDestino(t.funil);
    setMostrarModalTarefa(true);
  };

  const handleExcluirTarefa = (id: number) => {
    const t = tarefas.find(tar => tar.id === id);
    if (t) {
      setTarefaParaExcluir(t);
    }
  };

  const confirmarExcluirTarefa = () => {
    if (!tarefaParaExcluir) return;
    onExcluirTarefa(tarefaParaExcluir.id);
    exibirToast("Tarefa excluída com sucesso.");
    setTarefaParaExcluir(null);
  };

  // Mover tarefa entre funis/colunas
  const moverTarefa = (t: Tarefa, direcao: 'esquerda' | 'direita') => {
    const idxAtual = funis.indexOf(t.funil);
    if (idxAtual === -1) return;

    let novoIdx = idxAtual;
    if (direcao === 'esquerda') novoIdx--;
    if (direcao === 'direita') novoIdx++;

    if (novoIdx >= 0 && novoIdx < funis.length) {
      const novaColuna = funis[novoIdx];
      onAtualizarTarefa(t.id, {
        titulo: t.titulo,
        descricao: t.descricao,
        dataVencimento: t.dataVencimento,
        responsavel: t.responsavel,
        prioridade: t.prioridade,
        funil: novaColuna
      });
      exibirToast(`Tarefa movida para "${novaColuna}"`);
    }
  };

  // Gerenciamento de Funis (Colunas)
  const handleSalvarFunil = (e: React.FormEvent) => {
    e.preventDefault();
    if (!novoFunilNome.trim()) return;

    if (funilSendoEditado) {
      // Renomear funil existente
      const antigoNome = funilSendoEditado;
      const novoNome = novoFunilNome.trim();
      
      // Atualizar no array de colunas
      setFunis(funis.map(f => f === antigoNome ? novoNome : f));
      
      // Atualizar todas as tarefas que estavam no funil antigo para o novo
      tarefas.forEach(t => {
        if (t.funil === antigoNome) {
          onAtualizarTarefa(t.id, { ...t, funil: novoNome });
        }
      });

      exibirToast(`Coluna renomeada para "${novoNome}"`);
    } else {
      // Criar nova coluna vazia
      if (funis.includes(novoFunilNome.trim())) {
        exibirToast("Já existe uma coluna com esse nome.", "erro");
        return;
      }
      setFunis([...funis, novoFunilNome.trim()]);
      exibirToast(`Coluna "${novoFunilNome.trim()}" adicionada!`);
    }

    setMostrarModalFunil(false);
  };

  const abrirAdicionarFunil = () => {
    setFunilSendoEditado(null);
    setNovoFunilNome('');
    setMostrarModalFunil(true);
  };

  const abrirRenomearFunil = (f: string) => {
    setFunilSendoEditado(f);
    setNovoFunilNome(f);
    setMostrarModalFunil(true);
  };

  // RF-TA-006: "Excluir funil: solicitar confirmação se houver tarefas na coluna."
  const excluirFunil = (f: string) => {
    const tarefasAfetadas = tarefas.filter(t => t.funil === f);
    
    if (tarefasAfetadas.length > 0) {
      // RN-TA-002: "Ao excluir um funil que contenha tarefas, o sistema exibe alerta informando a quantidade de tarefas afetadas..."
      const confirma = confirm(
        `ALERTA DO SISTEMA:\nEsta coluna contém ${tarefasAfetadas.length} tarefa(s) ativas!\n\nAs tarefas NÃO podem ser perdidas. Mova-as para outra coluna antes de excluir, ou confirme se deseja excluí-las permanentemente junto com este funil.`
      );
      if (!confirma) return;
      
      // Se confirmou deletar as tarefas também
      tarefasAfetadas.forEach(t => onExcluirTarefa(t.id));
    }

    setFunis(funis.filter(col => col !== f));
    exibirToast(`Coluna "${f}" removida do quadro.`);
  };

  // Cores de prioridade
  const badgePrioridade = (p: 'baixa' | 'media' | 'alta' | 'urgente') => {
    switch (p) {
      case 'baixa': return 'bg-slate-100 text-slate-700 border-slate-200';
      case 'media': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
      case 'alta': return 'bg-amber-50 text-amber-700 border-amber-200 animate-pulse';
      case 'urgente': return 'bg-rose-50 text-rose-700 border-rose-300 font-extrabold animate-bounce';
    }
  };

  const corPrioridadeBorda = (p: 'baixa' | 'media' | 'alta' | 'urgente') => {
    switch (p) {
      case 'baixa': return 'border-l-4 border-l-slate-300';
      case 'media': return 'border-l-4 border-l-emerald-400';
      case 'alta': return 'border-l-4 border-l-amber-500';
      case 'urgente': return 'border-l-4 border-l-rose-600';
    }
  };

  // Filtra as tarefas de acordo com o filtro de prioridades
  const tarefasFiltradas = tarefas.filter(t => 
    filtroPrioridade === 'todos' || t.prioridade === filtroPrioridade
  );

  return (
    <div className="space-y-6">
      
      {/* Toast */}
      <AnimatePresence>
        {toast && (
          <motion.div
            initial={{ opacity: 0, y: -50 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0 }}
            className={`fixed top-6 right-6 z-55 p-4 rounded-xl shadow-xl flex items-center gap-3 border ${
              toast.tipo === 'erro' ? 'bg-red-950 border-red-900 text-red-200' : 'bg-slate-900 border-slate-800 text-white'
            }`}
          >
            {toast.tipo === 'erro' ? <AlertTriangle className="text-red-400" size={18} /> : <CheckCircle className="text-sage" size={18} />}
            <span className="text-xs font-semibold">{toast.texto}</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Gestão de Tarefas Kanban</h1>
          <p className="text-slate-500 text-sm mt-1">Coordene as vendas, controle o recebimento do estoque e designe tarefas diárias para a equipe.</p>
        </div>

        {podeEditar && (
          <button
            onClick={abrirAdicionarFunil}
            className="bg-ebony hover:bg-[#a4ac86] text-white font-bold px-4 py-2.5 rounded-lg text-xs tracking-wider uppercase transition flex items-center gap-1.5 cursor-pointer ml-auto sm:ml-0"
          >
            <FolderPlus size={16} />
            <span>Adicionar Coluna</span>
          </button>
        )}
      </div>

      {/* Barra de Filtro e Ordenação por Prioridade */}
      <div className="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 text-xs font-sans">
        <div className="flex items-center gap-2 text-slate-505 font-bold uppercase tracking-wider">
          <ListFilter size={15} className="text-slate-400" />
          <span>Filtro por Urgência</span>
        </div>
        
        <div className="flex flex-wrap gap-1.5">
          {(['todos', 'baixa', 'media', 'alta', 'urgente'] as const).map(prio => (
            <button
              key={prio}
              onClick={() => setFiltroPrioridade(prio)}
              className={`px-3 py-1.5 rounded-md font-semibold text-[11px] capitalize border transition cursor-pointer ${
                filtroPrioridade === prio 
                  ? 'bg-ebony text-white border-ebony' 
                  : 'bg-slate-50 border-slate-100 text-slate-500 hover:bg-slate-100'
              }`}
            >
              {prio === 'todos' ? 'Todas as tarefas' : `${prio} priority`}
            </button>
          ))}
        </div>
      </div>

      {/* Kanban Board Container */}
      <div className="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-3 gap-6 align-stretch">
        {funis.map((colName) => {
          const tarefasDaColuna = tarefasFiltradas.filter(t => t.funil === colName);

          return (
            <div key={colName} className="bg-slate-50/50 rounded-2xl p-4 border border-slate-100 flex flex-col min-h-[480px]">
              
              {/* Header Coluna */}
              <div className="flex items-center justify-between pb-3.5 border-b border-slate-100 mb-4">
                <div className="text-left">
                  <span className="font-bold text-slate-905 font-serif text-sm">{colName}</span>
                  <span className="ml-1.5 bg-slate-100 text-slate-550 border border-slate-200/50 px-2 py-0.5 rounded-full text-[10px] font-bold font-mono">
                    {tarefasDaColuna.length}
                  </span>
                </div>

                {podeEditar && colName !== 'Concluído' && colName !== 'A Fazer' && colName !== 'Em Progresso' ? (
                  <div className="flex items-center gap-1">
                    <button
                      onClick={() => abrirRenomearFunil(colName)}
                      className="text-[10px] font-semibold text-slate-400 hover:text-ebony transition px-1 py-0.5"
                    >
                      Renomear
                    </button>
                    <button
                      onClick={() => excluirFunil(colName)}
                      className="p-1 text-slate-400 hover:text-rose-600 transition"
                      title="Excluir Coluna"
                    >
                      <Trash2 size={12} />
                    </button>
                  </div>
                ) : null}
              </div>

              {/* Botão Rapido Incluir Cartão */}
              {podeEditar && (
                <button
                  onClick={() => abrirAdicionarTarefa(colName)}
                  className="w-full flex items-center justify-center gap-1.5 py-2.5 bg-white hover:bg-slate-50 border border-dashed border-slate-200 rounded-xl text-xs text-slate-400 hover:text-ebony font-medium transition cursor-pointer mb-3.5 shadow-xs"
                >
                  <Plus size={14} />
                  <span>Nova Tarefa</span>
                </button>
              )}

              {/* Cartões da Coluna */}
              <div className="flex-1 space-y-3 overflow-y-auto max-h-[500px] pr-1 scrollbar-none">
                {tarefasDaColuna.length === 0 ? (
                  <div className="py-12 text-center text-slate-400 text-[11px] font-normal italic">
                    Sem tarefas nesta etapa.
                  </div>
                ) : (
                  tarefasDaColuna.map((tar) => (
                    <motion.div
                      key={tar.id}
                      initial={{ opacity: 0, y: 5 }}
                      animate={{ opacity: 1, y: 0 }}
                      className={`bg-white p-3.5 rounded-xl border border-slate-200/60 shadow-xs flex flex-col justify-between text-xs space-y-3.5 text-left ${corPrioridadeBorda(tar.prioridade)}`}
                    >
                      <div className="space-y-1.5">
                        <div className="flex justify-between items-start gap-2">
                          <h4 className="font-bold text-slate-900 line-clamp-1">{tar.titulo}</h4>
                          <span className={`px-2 py-0.5 rounded text-[8px] font-extrabold uppercase tracking-wider font-mono border ${badgePrioridade(tar.prioridade)}`}>
                            {tar.prioridade}
                          </span>
                        </div>
                        <p className="text-slate-500 font-normal leading-relaxed line-clamp-2">{tar.descricao}</p>
                      </div>

                      {/* Footer do Card */}
                      <div className="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-450">
                        <div className="space-y-1">
                          <div className="flex items-center gap-1 font-semibold text-slate-700">
                            <User size={12} className="text-slate-400" />
                            <span className="truncate max-w-[120px]">{tar.responsavel}</span>
                          </div>
                          <div className="flex items-center gap-1 font-mono text-[9px] text-slate-400">
                            <Calendar size={11} />
                            <span>venc: {tar.dataVencimento}</span>
                          </div>
                        </div>

                        {/* Controles de Navegação no Board */}
                        <div className="flex items-center gap-1 shrink-0">
                          <button
                            onClick={() => moverTarefa(tar, 'esquerda')}
                            disabled={funis.indexOf(tar.funil) === 0}
                            className="p-1 border border-slate-100 rounded-md text-slate-400 hover:text-ebony hover:bg-slate-50 disabled:opacity-30 transition cursor-pointer"
                            title="Mover para esquerda"
                          >
                            <ChevronLeft size={12} />
                          </button>
                          
                          {podeEditar && (
                            <button
                              onClick={() => abrirEditarTarefa(tar)}
                              className="p-1 border border-slate-100 rounded-md text-slate-400 hover:text-[#CCBFA3] hover:bg-slate-50 transition cursor-pointer"
                              title="Editar"
                            >
                              <Edit size={12} />
                            </button>
                          )}

                          <button
                            onClick={() => moverTarefa(tar, 'direita')}
                            disabled={funis.indexOf(tar.funil) === funis.length - 1}
                            className="p-1 border border-slate-100 rounded-md text-slate-400 hover:text-ebony hover:bg-slate-50 disabled:opacity-30 transition cursor-pointer"
                            title="Mover para direita"
                          >
                            <ChevronRight size={12} />
                          </button>
                          
                          {podeEditar && (
                            <button
                              onClick={() => handleExcluirTarefa(tar.id)}
                              className="p-1 text-rose-55 hover:bg-rose-50 rounded-md transition cursor-pointer"
                              title="Deletar tarefa"
                            >
                              <Trash2 size={12} />
                            </button>
                          )}
                        </div>
                      </div>
                    </motion.div>
                  ))
                )}
              </div>

            </div>
          );
        })}
      </div>

      {/* MODAL CADASTRAR OU EDITAR TAREFA */}
      <AnimatePresence>
        {mostrarModalTarefa && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMostrarModalTarefa(false)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-sm bg-white rounded-xl shadow-xl overflow-hidden z-10 p-6 space-y-4 text-left font-sans text-xs"
            >
              <div>
                <h3 className="text-base font-bold text-slate-900 font-serif">
                  {tarefaEditando ? "Editar Cartão de Tarefa" : "Criar Nova Atividade"}
                </h3>
                <p className="text-slate-400 text-xs">Agende uma tarefa focado nas operações da loja.</p>
              </div>

              <form onSubmit={handleSalvarTarefa} className="space-y-4">
                <div>
                  <label className="block font-bold text-slate-550 mb-1 uppercase">Título da Tarefa *</label>
                  <input
                    type="text"
                    required
                    value={titulo}
                    onChange={(e) => setTitulo(e.target.value)}
                    placeholder="Ex: Repor estoque de blazers femininos..."
                    className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 text-xs focus:ring-1 focus:ring-ebony"
                  />
                </div>

                <div>
                  <label className="block font-bold text-slate-550 mb-1 uppercase">Descrição detalhada</label>
                  <textarea
                    rows={3}
                    value={descricao}
                    onChange={(e) => setDescricao(e.target.value)}
                    placeholder="Insira notas do gerente..."
                    className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 text-slate-800 text-xs focus:ring-1 focus:ring-ebony"
                  />
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block font-bold text-slate-550 mb-1 uppercase">Data Vencimento</label>
                    <input
                      type="date"
                      required
                      value={dataVencimento}
                      onChange={(e) => setDataVencimento(e.target.value)}
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg p-2 text-slate-800 focus:outline-none"
                    />
                  </div>
                  <div>
                    <label className="block font-bold text-slate-550 mb-1 uppercase">Nível Urgência</label>
                    <select
                      value={prioridade}
                      onChange={(e) => setPrioridade(e.target.value as any)}
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg p-2 text-slate-800 font-semibold focus:outline-none"
                    >
                      <option value="baixa">Baixa prioridade</option>
                      <option value="media">Média prioridade</option>
                      <option value="alta">Alta prioridade</option>
                      <option value="urgente">Urgente</option>
                    </select>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block font-bold text-slate-550 mb-1 uppercase">Responsável *</label>
                    <select
                      value={responsavel}
                      required
                      onChange={(e) => setResponsavel(e.target.value)}
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg p-2.5 text-slate-800 font-medium focus:outline-none"
                    >
                      <option value="">Selecione...</option>
                      {membros.map(m => (
                        <option key={m.id} value={m.nome}>{m.nome} ({m.cargo})</option>
                      ))}
                    </select>
                  </div>
                  <div>
                    <label className="block font-bold text-slate-550 mb-1 uppercase">Coluna/Etapa</label>
                    <select
                      value={funilDestino}
                      onChange={(e) => setFunilDestino(e.target.value)}
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg p-2.5 text-slate-800 focus:outline-none"
                    >
                      {funis.map(f => (
                        <option key={f} value={f}>{f}</option>
                      ))}
                    </select>
                  </div>
                </div>

                <div className="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setMostrarModalTarefa(false)}
                    className="px-4 py-2 bg-stone-55 text-stone-605 hover:bg-stone-100 rounded-lg font-bold transition uppercase"
                  >
                    Mudar de Ideia
                  </button>
                  <button
                    type="submit"
                    className="px-5 py-2 bg-ebony hover:bg-reseda text-white rounded-lg font-bold transition uppercase tracking-wider"
                  >
                    Salvar Cartão
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* MODAL ADICIONAR / RENOMEAR COLUNA (FUNIL) */}
      <AnimatePresence>
        {mostrarModalFunil && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMostrarModalFunil(false)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-xs bg-white rounded-xl shadow-xl overflow-hidden z-10 p-6 space-y-4 text-left font-sans text-xs"
            >
              <div>
                <h3 className="text-sm font-bold text-slate-900 font-serif uppercase tracking-wider">
                  {funilSendoEditado ? "Renomear Coluna" : "Adicionar Nova Coluna"}
                </h3>
                <p className="text-slate-400 text-[11px]">Dê um nome para o estágio do seu fluxo de trabalho.</p>
              </div>

              <form onSubmit={handleSalvarFunil} className="space-y-4">
                <div>
                  <label className="block font-bold text-slate-550 mb-1 uppercase">Nome do Funil/Coluna *</label>
                  <input
                    type="text"
                    required
                    value={novoFunilNome}
                    onChange={(e) => setNovoFunilNome(e.target.value)}
                    placeholder="Ex: Em Auditoria..."
                    className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 focus:ring-1 focus:ring-ebony"
                  />
                </div>

                <div className="flex items-center justify-end gap-2 pt-2">
                  <button
                    type="button"
                    onClick={() => setMostrarModalFunil(false)}
                    className="px-3.5 py-2 bg-stone-50 hover:bg-stone-100 text-stone-600 rounded-lg transition"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    className="px-4 py-2 bg-ebony hover:bg-reseda text-white rounded-lg font-bold transition uppercase tracking-wider"
                  >
                    {funilSendoEditado ? 'Salvar Nome' : 'Criar Coluna'}
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* MODAL CONFIRMAÇÃO EXCLUSÃO TAREFA */}
      <AnimatePresence>
        {tarefaParaExcluir && (
          <div className="fixed inset-0 z-55 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setTarefaParaExcluir(null)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden z-10 p-6 space-y-5 text-slate-800 text-left font-sans"
            >
              <div className="text-center space-y-2">
                <div className="mx-auto w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center">
                  <AlertTriangle className="text-[#a64b2a]" size={22} />
                </div>
                <h3 className="text-lg font-serif text-slate-900 font-semibold">Excluir Tarefa</h3>
                <p className="text-slate-500 text-xs leading-relaxed">
                  Tem certeza de que deseja apagar permanentemente a tarefa <strong className="text-rose-900">"{tarefaParaExcluir.titulo}"</strong> do painel Kanban? Esta operação é definitiva.
                </p>
              </div>

              <div className="flex items-center justify-center gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setTarefaParaExcluir(null)}
                  className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-650 rounded-xl transition text-xs font-semibold cursor-pointer"
                >
                  Cancelar
                </button>
                <button
                  type="button"
                  onClick={confirmarExcluirTarefa}
                  className="w-full py-2.5 bg-[#a64b2a] hover:bg-[#8b2611] text-white rounded-xl font-bold transition text-xs uppercase tracking-wider cursor-pointer"
                >
                  Sim, Excluir
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

    </div>
  );
}
