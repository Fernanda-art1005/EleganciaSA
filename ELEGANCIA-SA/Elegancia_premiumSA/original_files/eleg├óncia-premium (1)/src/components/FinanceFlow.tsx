import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  DollarSign, 
  ArrowUpRight, 
  ArrowDownRight, 
  Calendar, 
  Filter, 
  PlusCircle, 
  Trash2, 
  Edit, 
  CheckCircle, 
  AlertTriangle,
  Receipt,
  Tag
} from 'lucide-react';
import { Transacao, Cliente } from '../types';

interface FinanceFlowProps {
  transacoes: Transacao[];
  clientes: Cliente[];
  onAdicionarTransacao: (t: Omit<Transacao, 'id'>) => void;
  onAtualizarTransacao: (id: number, t: Omit<Transacao, 'id'>) => void;
  onExcluirTransacao: (id: number) => void;
  podeEditar: boolean;
}

export default function FinanceFlow({
  transacoes,
  clientes,
  onAdicionarTransacao,
  onAtualizarTransacao,
  onExcluirTransacao,
  podeEditar
}: FinanceFlowProps) {
  // Estados de Filtro
  const [dataInicio, setDataInicio] = useState('');
  const [dataFim, setDataFim] = useState('');
  const [filtroTipo, setFiltroTipo] = useState<'todos' | 'receita_venda' | 'receita_manual' | 'despesa'>('todos');
  const [filtroStatus, setFiltroStatus] = useState<'todos' | 'pendente' | 'concluido' | 'saida'>('todos');
  const [buscaNome, setBuscaNome] = useState('');

  // Modais de Cadastro de Transação
  const [mostrarModal, setMostrarModal] = useState(false);
  const [transacaoEditando, setTransacaoEditando] = useState<Transacao | null>(null);

  // Campos do Formulário
  const [nome, setNome] = useState('');
  const [tipo, setTipo] = useState<'receita_manual' | 'despesa'>('receita_manual');
  const [valor, setValor] = useState('');
  const [status, setStatus] = useState<'pendente' | 'concluido' | 'saida'>('concluido');
  const [data, setData] = useState(new Date().toISOString().substring(0, 10));

  // Toast
  const [toast, setToast] = useState<{ texto: string; tipo: 'sucesso' | 'erro' } | null>(null);
  const [transacaoParaExcluir, setTransacaoParaExcluir] = useState<Transacao | null>(null);

  const exibirToast = (texto: string, tipo: 'sucesso' | 'erro' = 'sucesso') => {
    setToast({ texto, tipo });
    setTimeout(() => {
      setToast(null);
    }, 4000);
  };

  const handleSalvar = (e: React.FormEvent) => {
    e.preventDefault();
    if (!nome.trim()) {
      exibirToast("Preencha a descrição da transação.", "erro");
      return;
    }

    const valorNum = parseFloat(valor) || 0;
    if (valorNum <= 0) {
      exibirToast("O valor deve ser de pelo menos R$ 0,01.", "erro");
      return;
    }

    // Determina status correto se for despesa (geralmente status 'saida')
    const statusFinal = tipo === 'despesa' ? 'saida' : status;

    const payload = {
      nome: nome.trim(),
      data: new Date(data).toISOString(),
      tipo,
      valor: valorNum,
      status: statusFinal
    };

    if (transacaoEditando) {
      onAtualizarTransacao(transacaoEditando.id, payload as any);
      exibirToast(`Transação "${payload.nome}" atualizada com êxito!`);
    } else {
      onAdicionarTransacao(payload);
      exibirToast(`Registro de "${payload.nome}" lançado no livro caixa!`);
    }

    setMostrarModal(false);
  };

  const abrirAdicionar = (tipoNovo: 'receita_manual' | 'despesa') => {
    setTransacaoEditando(null);
    setNome('');
    setTipo(tipoNovo);
    setValor('');
    setStatus(tipoNovo === 'despesa' ? 'saida' : 'concluido');
    setData(new Date().toISOString().substring(0, 10));
    setMostrarModal(true);
  };

  const abrirEditar = (t: Transacao) => {
    if (t.tipo === 'receita_venda') {
      exibirToast("Transações de vendas concluídas pelo PDV não podem ser alteradas por este painel.", "erro");
      return;
    }
    setTransacaoEditando(t);
    setNome(t.nome);
    setTipo(t.tipo as any);
    setValor(t.valor.toString());
    setStatus(t.status as any);
    setData(new Date(t.data).toISOString().substring(0, 10));
    setMostrarModal(true);
  };

  const handleExcluir = (id: number, desc: string) => {
    const t = transacoes.find(tr => tr.id === id);
    if (t) {
      setTransacaoParaExcluir(t);
    }
  };

  const confirmarExcluirTransacao = () => {
    if (!transacaoParaExcluir) return;
    onExcluirTransacao(transacaoParaExcluir.id);
    exibirToast("Lançamento removido do fluxo de caixa com sucesso.");
    setTransacaoParaExcluir(null);
  };

  // Cálculos consolidados para as métricas da boutique
  // Saldo total = receitas concluídas (vendas e manuais) - despesas
  const totalReceitasvendas = transacoes
    .filter(t => t.tipo === 'receita_venda' && t.status === 'concluido')
    .reduce((sum, t) => sum + t.valor, 0);

  const totalReceitasManuais = transacoes
    .filter(t => t.tipo === 'receita_manual' && t.status === 'concluido')
    .reduce((sum, t) => sum + t.valor, 0);

  const totalDespesas = transacoes
    .filter(t => t.tipo === 'despesa' && t.status === 'saida')
    .reduce((sum, t) => sum + t.valor, 0);

  // RF-FI-001: "Saldo total da loja é calculado como: Saldo Total = Soma de todas as receitas (status 'concluido') - Soma de todas as despesas e saídas."
  const saldoTotal = (totalReceitasvendas + totalReceitasManuais) - totalDespesas;
  
  // RF-FI-002: "Total de vendas: O valor inclui apenas transações originadas de vendas (PDV)."
  const totalVendasPDVPendenteOuConcluido = transacoes
    .filter(t => t.tipo === 'receita_venda')
    .reduce((sum, t) => sum + t.valor, 0);

  // RF-FI-003: "Crédito pendente: Soma total de saldo devedor dos clientes."
  const totalCreditoPendente = clientes.reduce((sum, c) => sum + c.saldoDevedor, 0);

  // Filtragem da lista
  const transacoesFiltradas = transacoes.filter(t => {
    // Busca nome
    if (buscaNome && !t.nome.toLowerCase().includes(buscaNome.toLowerCase())) return false;
    // Filtro tipo
    if (filtroTipo !== 'todos' && t.tipo !== filtroTipo) return false;
    // Filtro status
    if (filtroStatus !== 'todos' && t.status !== filtroStatus) return false;
    // Filtro datas
    if (dataInicio) {
      const dInicio = new Date(dataInicio);
      const dTrans = new Date(t.data);
      if (dTrans < dInicio) return false;
    }
    if (dataFim) {
      const dFim = new Date(dataFim);
      dFim.setHours(23, 59, 59, 999); // fim do dia
      const dTrans = new Date(t.data);
      if (dTrans > dFim) return false;
    }
    return true;
  }).sort((a, b) => new Date(b.data).getTime() - new Date(a.data).getTime());

  return (
    <div className="space-y-6">
      
      {/* Toast Notificação */}
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
          <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Financeiro e Caixa Total</h1>
          <p className="text-slate-500 text-sm mt-1">Inscreva lançamentos, depure notas fiscais, friture custos operacionais e certifique o fluxo de caixa.</p>
        </div>
        
        {podeEditar && (
          <div className="flex gap-2 ml-auto sm:ml-0">
            <button
              onClick={() => abrirAdicionar('receita_manual')}
              className="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3.5 py-2.5 rounded-lg text-xs tracking-wider uppercase transition flex items-center gap-1.5 cursor-pointer animate-pulse"
            >
              <PlusCircle size={15} />
              <span>Receita Manual</span>
            </button>
            <button
              onClick={() => abrirAdicionar('despesa')}
              className="bg-ebony hover:bg-[#a64b2a] text-white font-bold px-3.5 py-2.5 rounded-lg text-xs tracking-wider uppercase transition flex items-center gap-1.5 cursor-pointer"
            >
              <ArrowDownRight size={15} />
              <span>Nova Despesa</span>
            </button>
          </div>
        )}
      </div>

      {/* KPIs Financeiros */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-white p-5 rounded-xl border border-slate-100 shadow-sm">
          <span className="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider block">Saldo Geral Caixa</span>
          <h3 className={`text-2xl font-bold font-mono mt-1 ${saldoTotal >= 0 ? 'text-emerald-800' : 'text-red-800'}`}>
            R$ {saldoTotal.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
          </h3>
          <span className="text-[10px] text-slate-400 font-medium block mt-1">Disponível consolidado na conta</span>
        </div>

        <div className="bg-white p-5 rounded-xl border border-slate-100 shadow-sm">
          <span className="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider block">Faturamento PDV</span>
          <h3 className="text-2xl font-bold font-mono text-slate-800 mt-1">
            R$ {totalVendasPDVPendenteOuConcluido.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
          </h3>
          <span className="text-[10px] text-slate-400 font-medium block mt-1">Vendas geradas direto de caixa</span>
        </div>

        <div className="bg-white p-5 rounded-xl border border-slate-100 shadow-sm">
          <span className="text-[10px] text-slate-400 font-extrabold uppercase tracking-wider block">Lendas Extra Caixa</span>
          <h3 className="text-2xl font-bold font-mono text-emerald-700 mt-1">
            R$ {totalReceitasManuais.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
          </h3>
          <span className="text-[10px] text-slate-400 font-medium block mt-1">Entradas e investimentos manuais</span>
        </div>

        <div className="bg-white p-5 rounded-xl border border-slate-100 shadow-sm">
          <span className="text-[10px] text-[#a64b2a] font-extrabold uppercase tracking-wider block">Créditos de Clientes</span>
          <h3 className="text-2xl font-bold font-mono text-[#a64b2a] mt-1">
            R$ {totalCreditoPendente.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
          </h3>
          <span className="text-[10px] text-slate-400 font-medium block mt-1">Contas pendentes de fiador</span>
        </div>
      </div>

      {/* Filtros e Busca de Transações */}
      <div className="bg-white p-4 rounded-xl border border-slate-100 shadow-sm space-y-4">
        <div className="flex items-center gap-2">
          <Filter className="text-slate-400" size={16} />
          <span className="text-xs font-bold text-slate-500 uppercase tracking-widest">Painel de Filtragem de Lançamentos</span>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-5 gap-3 text-xs">
          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase mb-1">Status Lançado</label>
            <select
              value={filtroStatus}
              onChange={(e) => setFiltroStatus(e.target.value as any)}
              className="w-full bg-slate-50 border border-slate-100 rounded-lg p-2.5 text-slate-705 focus:outline-none"
            >
              <option value="todos">Todos os Status</option>
              <option value="concluido">Concluído</option>
              <option value="pendente">Pendente de Recebimento</option>
              <option value="saida">Saídas / Despesas</option>
            </select>
          </div>

          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase mb-1">Linha do Lançamento</label>
            <select
              value={filtroTipo}
              onChange={(e) => setFiltroTipo(e.target.value as any)}
              className="w-full bg-slate-50 border border-slate-100 rounded-lg p-2.5 text-slate-705 focus:outline-none"
            >
              <option value="todos">Todos os Lançamentos</option>
              <option value="receita_venda">Receitas de Vendas</option>
              <option value="receita_manual">Receitas Manuais</option>
              <option value="despesa">Despesas / Saídas</option>
            </select>
          </div>

          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase mb-1">Data Início</label>
            <input
              type="date"
              value={dataInicio}
              onChange={(e) => setDataInicio(e.target.value)}
              className="w-full bg-slate-50 border border-slate-100 rounded-lg p-2 text-slate-705 focus:outline-none"
            />
          </div>

          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase mb-1">Data Fim</label>
            <input
              type="date"
              value={dataFim}
              onChange={(e) => setDataFim(e.target.value)}
              className="w-full bg-slate-50 border border-slate-100 rounded-lg p-2 text-slate-705 focus:outline-none"
            />
          </div>

          <div>
            <label className="block text-[10px] font-bold text-slate-400 uppercase mb-1">Pesquisar Descrição</label>
            <input
              type="text"
              value={buscaNome}
              onChange={(e) => setBuscaNome(e.target.value)}
              placeholder="Ex: Aluguel..."
              className="w-full bg-slate-50 border border-slate-100 rounded-lg p-2.5 text-slate-705 focus:outline-none"
            />
          </div>
        </div>
      </div>

      {/* Lista de Transações */}
      <div className="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-left text-xs font-sans">
            <thead className="bg-slate-50 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
              <tr>
                <th className="p-4">Descrição</th>
                <th className="p-4">Data Registro</th>
                <th className="p-4">Tipo Lançamento</th>
                <th className="p-4 text-right">Valor Financeiro</th>
                <th className="p-4 text-center">Status</th>
                {podeEditar && <th className="p-4 text-center">Controles</th>}
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-slate-700 font-medium">
              {transacoesFiltradas.length === 0 ? (
                <tr>
                  <td colSpan={6} className="p-16 text-center text-slate-400">
                    <Receipt className="mx-auto text-slate-205 mb-2.5" size={40} />
                    <p className="font-semibold text-xs uppercase tracking-wider text-slate-500">Nenhum lançamento no período filtrado</p>
                  </td>
                </tr>
              ) : (
                transacoesFiltradas.map((tra) => (
                  <tr key={tra.id} className="hover:bg-slate-50/40 transition">
                    <td className="p-4">
                      <div>
                        <span className="font-bold text-slate-900 text-sm">{tra.nome}</span>
                        {tra.vendaId && <span className="ml-2 text-[10px] px-1.5 py-0.5 rounded-lg bg-slate-100 text-slate-500">Venda: #{tra.vendaId}</span>}
                      </div>
                    </td>
                    <td className="p-4">
                      <div className="flex items-center gap-1 text-slate-500">
                        <Calendar size={13} />
                        <span>{new Date(tra.data).toLocaleString('pt-BR')}</span>
                      </div>
                    </td>
                    <td className="p-4">
                      <span className={`inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase ${
                        tra.tipo === 'receita_venda' 
                          ? 'bg-emerald-50 text-emerald-700'
                          : tra.tipo === 'receita_manual'
                          ? 'bg-sky-50 text-sky-700'
                          : 'bg-rose-50 text-[#a64b2a]'
                      }`}>
                        {tra.tipo === 'receita_venda' ? 'Venda PDV' : tra.tipo === 'receita_manual' ? 'Rec Entrada Manual' : 'Custos/Despesas'}
                      </span>
                    </td>
                    <td className="p-4 text-right font-mono text-[14px]">
                      <span className={tra.tipo === 'despesa' ? 'text-[#a64b2a]' : 'text-slate-900 font-bold'}>
                        {tra.tipo === 'despesa' ? '-' : '+'} R$ {tra.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                      </span>
                    </td>
                    <td className="p-4 text-center">
                      <span className={`inline-flex px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase border ${
                        tra.status === 'concluido' 
                          ? 'bg-emerald-55 border-emerald-200 text-emerald-800'
                          : tra.status === 'pendente'
                          ? 'bg-amber-50 border-amber-200 text-amber-700 animate-pulse'
                          : 'bg-slate-50 border-slate-200 text-slate-600'
                      }`}>
                        {tra.status === 'concluido' ? 'Conferido' : tra.status === 'pendente' ? 'Pendente' : 'Despesa/Pago'}
                      </span>
                    </td>
                    {podeEditar && (
                      <td className="p-4 text-center">
                        {tra.tipo !== 'receita_venda' ? (
                          <div className="flex items-center justify-center gap-1.5">
                            <button
                              onClick={() => abrirEditar(tra)}
                              className="p-1.5 bg-stone-50 hover:bg-[#CCBFA3]/20 rounded-md transition text-ebony cursor-pointer"
                              title="Editar Lançamento"
                            >
                              <Edit size={13} />
                            </button>
                            <button
                              onClick={() => handleExcluir(tra.id, tra.nome)}
                              className="p-1.5 text-rose-600 hover:bg-rose-55 hover:text-white rounded-md transition cursor-pointer"
                              title="Deletar Lançamento"
                            >
                              <Trash2 size={13} />
                            </button>
                          </div>
                        ) : (
                          <span className="text-[10px] text-slate-400 font-medium">PDV Integrado</span>
                        )}
                      </td>
                    )}
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* MODAL CADASTRAR OU EDITAR TRANSAÇÃO */}
      <AnimatePresence>
        {mostrarModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMostrarModal(false)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-sm bg-white rounded-xl shadow-xl overflow-hidden z-10 p-6 space-y-5 text-left"
            >
              <div>
                <h3 className="text-lg font-serif text-slate-905">
                  {transacaoEditando ? "Editar Lançamento" : tipo === 'despesa' ? "Registrar Gasto de Loja" : "Lançar Receita Avulsa"}
                </h3>
                <p className="text-slate-400 text-xs">Aporte ou sangria financeira do caixa gerencial.</p>
              </div>

              <form onSubmit={handleSalvar} className="space-y-4 text-xs">
                <div>
                  <label className="block text-xs font-bold text-slate-500 mb-1 uppercase">Descrição do Lançamento *</label>
                  <input
                    type="text"
                    required
                    value={nome}
                    onChange={(e) => setNome(e.target.value)}
                    placeholder={tipo === 'despesa' ? "Conta de Água Copasa..." : "Dinheiro de Parceria..."}
                    className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 text-xs focus:ring-1 focus:ring-ebony focus:outline-none"
                  />
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-bold text-slate-505 mb-1 uppercase font-mono">Valor Financeiro (R$) *</label>
                    <div className="relative">
                      <span className="absolute left-2.5 top-2.5 text-slate-400 font-bold font-mono">R$</span>
                      <input
                        type="number"
                        step="0.01"
                        min="0.01"
                        required
                        value={valor}
                        onChange={(e) => setValor(e.target.value)}
                        placeholder="150.00"
                        className="w-full bg-slate-55 border border-slate-100 rounded-lg pl-7 pr-2.5 py-2 text-slate-802 text-xs font-mono focus:ring-1 focus:ring-ebony focus:outline-none font-bold"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-slate-505 mb-1 uppercase">Data Lançamento</label>
                    <input
                      type="date"
                      required
                      value={data}
                      onChange={(e) => setData(e.target.value)}
                      className="w-full bg-slate-55 border border-slate-100 rounded-lg px-2.5 py-2 text-slate-802 text-xs focus:ring-1 focus:ring-ebony focus:outline-none"
                    />
                  </div>
                </div>

                {tipo !== 'despesa' && (
                  <div>
                    <label className="block text-xs font-bold text-slate-505 mb-1 uppercase">Status Compensado</label>
                    <select
                      value={status}
                      onChange={(e) => setStatus(e.target.value as any)}
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg p-2 text-slate-705 focus:outline-none"
                    >
                      <option value="concluido">Concluído (Dinheiro em conta)</option>
                      <option value="pendente">Pendente de Compensação</option>
                    </select>
                  </div>
                )}

                <div className="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setMostrarModal(false)}
                    className="px-4 py-2 bg-stone-55 text-stone-605 hover:bg-stone-100 rounded-lg transition"
                  >
                    Retroceder
                  </button>
                  <button
                    type="submit"
                    className="px-5 py-2 bg-ebony hover:bg-reseda text-white rounded-lg font-bold transition uppercase tracking-wider"
                  >
                    Confirmar Lançar
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* MODAL CONFIRMACAO EXCLUSÃO TRANSAÇÃO */}
      <AnimatePresence>
        {transacaoParaExcluir && (
          <div className="fixed inset-0 z-55 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setTransacaoParaExcluir(null)}
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
                <h3 className="text-lg font-serif text-slate-900 font-semibold">Excluir Lançamento</h3>
                <p className="text-slate-500 text-xs leading-relaxed">
                  Tem certeza de que deseja apagar permanentemente o registro de <strong className="text-rose-900">"{transacaoParaExcluir.nome}"</strong> de valor <strong className="text-slate-900 font-mono">R$ {transacaoParaExcluir.valor.toFixed(2)}</strong>? Esse lançamento será omitido do livro caixa gerencial.
                </p>
              </div>

              <div className="flex items-center justify-center gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setTransacaoParaExcluir(null)}
                  className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-650 rounded-xl transition text-xs font-semibold cursor-pointer"
                >
                  Cancelar
                </button>
                <button
                  type="button"
                  onClick={confirmarExcluirTransacao}
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
