import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  FileText, 
  ChevronDown, 
  ChevronUp, 
  Calendar, 
  DollarSign, 
  User, 
  Package, 
  Trash2, 
  AlertTriangle,
  History,
  Search,
  CheckCircle,
  Filter
} from 'lucide-react';
import { Venda, Produto, RegistroAuditoria } from '../types';

interface ReportsProps {
  vendas: Venda[];
  produtos: Produto[];
  auditoria: RegistroAuditoria[];
  onExcluirVenda: (id: number) => void;
  podeEditar: boolean;
}

export default function Reports({ 
  vendas, 
  produtos, 
  auditoria, 
  onExcluirVenda,
  podeEditar
}: ReportsProps) {
  const [subAba, setSubAba] = useState<'vendas' | 'auditoria'>('vendas');
  
  // Sanfona de detalhes da venda em Faturamento
  const [linhaAbertaId, setLinhaAbertaId] = useState<number | null>(null);
  const [vendaIdConfirmacaoExclusao, setVendaIdConfirmacaoExclusao] = useState<number | null>(null);

  // Estados de Filtro para Auditoria
  const [tipoFiltro, setTipoFiltro] = useState('todos');
  const [periodoInicio, setPeriodoInicio] = useState('');
  const [periodoFim, setPeriodoFim] = useState('');
  const [usuarioFiltro, setUsuarioFiltro] = useState('');

  const lidarComConfirmarExclusaoVenda = () => {
    if (vendaIdConfirmacaoExclusao !== null) {
      onExcluirVenda(vendaIdConfirmacaoExclusao);
      setVendaIdConfirmacaoExclusao(null);
    }
  };

  const alternarLinha = (id: number) => {
    setLinhaAbertaId(linhaAbertaId === id ? null : id);
  };

  const formatarData = (dataIso: string) => {
    try {
      const data = new Date(dataIso);
      return data.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    } catch {
      return dataIso;
    }
  };

  // Filtragem dos Logs de Auditoria (RF-RE-005)
  const logsFiltrados = (auditoria || []).filter(log => {
    if (!log) return false;
    // Filtro por tipo de ação
    if (tipoFiltro !== 'todos' && !(log.tipoAcao || '').toLowerCase().includes(tipoFiltro.toLowerCase())) return false;
    
    // Filtro por termo de usuário
    if (usuarioFiltro && !(log.usuario || '').toLowerCase().includes(usuarioFiltro.toLowerCase())) return false;
    
    // Filtro pelo intervalo de datas
    const dLog = new Date(log.data || '');
    if (periodoInicio) {
      const dInicio = new Date(periodoInicio);
      if (dLog < dInicio) return false;
    }
    if (periodoFim) {
      const dFim = new Date(periodoFim);
      dFim.setHours(23, 59, 59, 999);
      if (dLog > dFim) return false;
    }
    
    return true;
  }).sort((a, b) => new Date(b.data || '').getTime() - new Date(a.data || '').getTime());

  // Limpar filtros de auditoria
  const restaurarFiltros = () => {
    setTipoFiltro('todos');
    setPeriodoInicio('');
    setPeriodoFim('');
    setUsuarioFiltro('');
  };

  return (
    <div className="space-y-6">
      
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Relatórios e Auditoria</h1>
          <p className="text-slate-500 text-sm mt-1">
            {subAba === 'vendas' 
              ? 'Consulte o histórico de faturamento completo e detalhe comprovantes de venda.' 
              : 'Verifique o diário de auditoria fiscal imutável atendendo diretrizes nacionais.'}
          </p>
        </div>
      </div>

      {/* Navegação SubAbas */}
      <div className="border-b border-slate-100 flex gap-6 text-xs font-bold uppercase tracking-wider">
        <button
          onClick={() => setSubAba('vendas')}
          className={`pb-3 border-b-2 transition ${
            subAba === 'vendas' ? 'border-ebony text-slate-900' : 'border-transparent text-slate-400'
          }`}
        >
          Notas e Faturamento ({vendas.length})
        </button>
        <button
          onClick={() => setSubAba('auditoria')}
          className={`pb-3 border-b-2 transition ${
            subAba === 'auditoria' ? 'border-ebony text-slate-900' : 'border-transparent text-slate-400'
          }`}
        >
          Livro de Auditoria Imutável ({auditoria.length})
        </button>
      </div>

      {/* ABA 1: HISTÓRICO DE VENDAS COMPLETAS */}
      {subAba === 'vendas' && (
        vendas.length === 0 ? (
          <div className="bg-white rounded-xl border border-slate-100 text-center py-20 px-4 shadow-sm">
            <FileText className="mx-auto text-slate-200 mb-4" size={48} />
            <h3 className="text-lg font-medium text-slate-700">Não há registros fiscais</h3>
            <p className="text-slate-400 text-sm max-w-md mx-auto mt-1">Conclua vendas no módulo de Caixa para popular o seu histórico financeiro.</p>
          </div>
        ) : (
          <div className="bg-white rounded-xl border border-slate-100 overflow-hidden shadow-sm">
            <div className="p-4 bg-slate-50 border-b border-bone flex items-center justify-between text-xs text-slate-400 font-bold uppercase tracking-wider">
              <span>Notas Fiscais de Entrada</span>
              <span>{vendas.length} Registros de Caixa</span>
            </div>

            <div className="divide-y divide-slate-100 font-sans">
              {(vendas || []).map((venda) => {
                if (!venda) return null;
                const estaAberto = linhaAbertaId === venda.id;
                const totalPecas = (venda.itens || []).reduce((sum, item) => sum + (item?.quantidade || 0), 0);

                return (
                  <div key={venda.id} className="transition-all duration-200 hover:bg-slate-50/20">
                    
                    {/* Linha Principal (Clicável) */}
                    <div
                      onClick={() => alternarLinha(venda.id)}
                      className="flex flex-col md:flex-row md:items-center justify-between p-5 gap-4 cursor-pointer select-none"
                    >
                      <div className="flex items-center gap-4 text-left">
                        <span className="w-10 h-10 rounded-full bg-bone text-ebony font-mono font-bold text-xs flex items-center justify-center shrink-0 border border-slate-100">
                          #{venda.id}
                        </span>
                        
                        <div>
                          <div className="flex items-center gap-2">
                            <span className="font-bold text-slate-900">{venda.clienteNome || 'Cliente'}</span>
                            <span className="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-slate-100 text-slate-500 font-sans">
                              {venda.formaPagamento === 'credito_loja' ? 'Crédito Loja' : (venda.formaPagamento || 'Outro')}
                            </span>
                          </div>
                          
                          <div className="flex items-center gap-3 text-xs text-slate-450 mt-1">
                            <span className="flex items-center gap-1">
                              <Calendar size={12} />
                              {formatarData(venda.dataCriacao)}
                            </span>
                            <span>•</span>
                            <span className="flex items-center gap-1 font-semibold text-slate-600">
                              <Package size={12} className="text-slate-400" />
                              {totalPecas} {totalPecas === 1 ? 'peça' : 'peças'} de roupa
                            </span>
                          </div>
                        </div>
                      </div>

                      <div className="flex items-center justify-between md:justify-end gap-6 border-t md:border-t-0 pt-3 md:pt-0">
                        {/* Status Financeiro */}
                        <div className="text-left md:text-right">
                          <span className="text-[10px] text-slate-400 font-extrabold uppercase block tracking-wider">Total Transacionado</span>
                          <span className="text-lg font-bold text-ebony font-mono">
                            R$ {(venda.total || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                          </span>
                          {(venda.desconto || 0) > 0 && (
                            <span className="text-[10px] text-[#a64b2a] font-bold block">
                              Cupom: - R$ {(venda.desconto || 0).toFixed(2)}
                            </span>
                          )}
                        </div>

                        {estaAberto ? (
                          <ChevronUp className="text-slate-400" size={18} />
                        ) : (
                          <ChevronDown className="text-slate-400" size={18} />
                        )}
                      </div>
                    </div>

                    {/* Detecção Detalhes Collapsible */}
                    <AnimatePresence initial={false}>
                      {estaAberto && (
                        <motion.div
                          initial={{ height: 0, opacity: 0 }}
                          animate={{ height: "auto", opacity: 1 }}
                          exit={{ height: 0, opacity: 0 }}
                          transition={{ duration: 0.15 }}
                          className="overflow-hidden bg-slate-50/50"
                        >
                          <div className="px-5 pb-5 pt-1 border-t border-slate-100">
                            <h4 className="text-[10px] font-extrabold text-slate-400 uppercase tracking-widest mb-3 text-left">Demonstrativo de Peças Registradas</h4>
                            
                            <div className="bg-white rounded-xl border border-slate-100 overflow-hidden text-xs">
                              <div className="grid grid-cols-12 bg-slate-50 p-2.5 font-bold text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-100">
                                <span className="col-span-6 text-left">Especificação da Peça</span>
                                <span className="col-span-2 text-center">Qtde</span>
                                <span className="col-span-2 text-right">Preço Unit</span>
                                <span className="col-span-2 text-right">Lançado</span>
                              </div>

                              <div className="divide-y divide-slate-55 font-medium text-slate-700">
                                {(venda.itens || []).map((item, idx) => {
                                  if (!item) return null;
                                  const prodRef = item ? produtos.find(p => p.id === item.idProduto) : null;
                                  const imgUrl = prodRef?.imagemUrl || "https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80";
                                  return (
                                    <div key={idx} className="grid grid-cols-12 p-3 items-center hover:bg-slate-50/20">
                                      <div className="col-span-6 flex items-center gap-3 text-left">
                                        <img
                                          src={imgUrl}
                                          alt={item.nomeProduto || 'Produto'}
                                          referrerPolicy="no-referrer"
                                          className="w-8 h-8 object-cover rounded border border-slate-100 shrink-0"
                                        />
                                        <span className="font-bold text-slate-800">{item.nomeProduto || 'Produto'}</span>
                                      </div>
                                      <span className="col-span-2 text-center font-mono font-bold">{item.quantidade || 0} un</span>
                                      <span className="col-span-2 text-right font-mono text-slate-500">R$ {(item.precoUnitario || 0).toFixed(2)}</span>
                                      <span className="col-span-2 text-right font-mono font-bold text-slate-900">
                                        R$ {((item.quantidade || 0) * (item.precoUnitario || 0)).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                                      </span>
                                    </div>
                                  );
                                })}
                              </div>

                              {/* Rodapé Interno Detalhes */}
                              <div className="bg-slate-50/80 p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-t border-slate-100 font-sans">
                                {podeEditar ? (
                                  <button
                                    onClick={(e) => {
                                      e.stopPropagation();
                                      setVendaIdConfirmacaoExclusao(venda.id);
                                    }}
                                    className="flex items-center gap-1 text-[11px] text-[#a64b2a] hover:text-white hover:bg-[#a64b2a] border border-rose-100 bg-white shadow-xs transition px-3 py-2 rounded-lg font-bold cursor-pointer"
                                  >
                                    <Trash2 size={13} />
                                    <span>Cancelar Comercial</span>
                                  </button>
                                ) : (
                                  <div className="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">Apenas Visualização</div>
                                )}

                                <div className="flex gap-6 text-right ml-auto sm:ml-0 font-mono text-xs">
                                  <div>
                                    <span className="text-slate-400 font-medium uppercase text-[9px] block">Imposto Simples ({(venda.imposto || 0) > 0 ? 'Incluso' : 'Isento'})</span>
                                    <span className="text-slate-605">R$ {(venda.imposto || 0).toFixed(2)}</span>
                                  </div>
                                  <div className="border-l border-slate-200 pl-6">
                                    <span className="text-slate-400 font-medium uppercase text-[9px] block">Total Pago Liquidado</span>
                                    <span className="text-base text-ebony font-bold">
                                      R$ {(venda.total || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                                    </span>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </motion.div>
                      )}
                    </AnimatePresence>
                  </div>
                );
              })}
            </div>
          </div>
        )
      )}

      {/* ABA 2: LOG DE AUDITORIA IMUTÁVEL (RF-RE-001, RF-RE-002, RF-RE-003, RF-RE-004) */}
      {subAba === 'auditoria' && (
        <div className="space-y-6">
          {/* Painel de Filtros Exclusivos */}
          <div className="bg-white p-5 rounded-xl border border-slate-100 shadow-sm space-y-4 text-left">
            <div className="flex items-center gap-2 text-slate-500">
              <Filter className="text-slate-400" size={17} />
              <span className="text-xs font-bold text-slate-500 uppercase tracking-widest font-sans">Busca no Audit Trail</span>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
              <div>
                <label className="block text-[10px] font-bold text-slate-405 uppercase mb-1">Ação Auditada</label>
                <select
                  value={tipoFiltro}
                  onChange={(e) => setTipoFiltro(e.target.value)}
                  className="w-full bg-slate-55 border border-slate-100 rounded-lg p-2.5 text-slate-800 focus:outline-none focus:ring-1 focus:ring-ebony"
                >
                  <option value="todos">Todas as Operações</option>
                  <option value="venda">Vendas Concluídas</option>
                  <option value="crédito">Créditos de Clientes</option>
                  <option value="produto">Produtos / Estoque</option>
                  <option value="exclusão">Exclusões / Eliminação</option>
                  <option value="movimentação">Movimentação Estoque</option>
                </select>
              </div>

              <div>
                <label className="block text-[10px] font-bold text-slate-405 uppercase mb-1">Operador Ativo (Nome)</label>
                <input
                  type="text"
                  value={usuarioFiltro}
                  onChange={(e) => setUsuarioFiltro(e.target.value)}
                  placeholder="Ex: Carlos, Maria, Sistema..."
                  className="w-full bg-slate-55 border border-slate-100 rounded-lg p-2.5 text-slate-800 focus:outline-none focus:ring-1 focus:ring-ebony"
                />
              </div>

              <div>
                <label className="block text-[10px] font-bold text-slate-405 uppercase mb-1">Iniciado Em</label>
                <input
                  type="date"
                  value={periodoInicio}
                  onChange={(e) => setPeriodoInicio(e.target.value)}
                  className="w-full bg-slate-55 border border-slate-100 rounded-lg p-2 text-slate-800 focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-[10px] font-bold text-slate-405 uppercase mb-1">Terminado Em</label>
                <input
                  type="date"
                  value={periodoFim}
                  onChange={(e) => setPeriodoFim(e.target.value)}
                  className="w-full bg-slate-55 border border-slate-100 rounded-lg p-2 text-slate-800 focus:outline-none"
                />
              </div>
            </div>

            <div className="flex justify-end gap-2 pt-1">
              <button
                onClick={restaurarFiltros}
                className="px-3 py-1.5 bg-slate-150 hover:bg-slate-200 text-slate-600 rounded-lg font-semibold text-xs transition cursor-pointer"
              >
                Limpar Filtros
              </button>
            </div>
          </div>

          {/* Lista de Registros Fiscais / Diários de Auditoria - COMPLETAMENTE IMUTÁVEIS, Sem Botões de Exclusão (RF-RE-004) */}
          <div className="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div className="p-4 bg-slate-50 border-b border-bone flex items-center justify-between text-xs text-slate-405 font-bold uppercase tracking-wider">
              <span>Registro de Operações Imutáveis (ACID Compliant)</span>
              <span>{logsFiltrados.length} Ações Registradas</span>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full border-collapse text-left text-xs font-sans">
                <thead className="bg-[#414833]/5 text-[#414833] font-bold uppercase tracking-wider border-b border-slate-100">
                  <tr>
                    <th className="p-4">ID Ação</th>
                    <th className="p-4">Instantâneo (Data/Hora)</th>
                    <th className="p-4">Tipo de Ação</th>
                    <th className="p-4">Descrição de Evento</th>
                    <th className="p-4">Operador Responsável</th>
                    <th className="p-4 text-right">Valor Auditado</th>
                    <th className="p-4 text-center">Status Audit</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 text-slate-650 font-medium whitespace-nowrap md:whitespace-normal">
                  {logsFiltrados.length === 0 ? (
                    <tr>
                      <td colSpan={7} className="p-16 text-center text-slate-400">
                        <History className="mx-auto text-slate-200 mb-2" size={40} />
                        <p className="font-semibold text-slate-505 uppercase tracking-wider">Nenhum registro cronológico encontrado</p>
                      </td>
                    </tr>
                  ) : (
                    logsFiltrados.map((log) => (
                      <tr key={log.id} className="hover:bg-slate-50/30 transition text-xs">
                        {/* ID */}
                        <td className="p-4 font-mono font-bold text-slate-900 shrink-0">
                          #{log.id}
                        </td>

                        {/* Data Hora */}
                        <td className="p-4 text-slate-500 font-mono text-[10.5px]">
                          {formatarData(log.data)}
                        </td>

                        {/* Tipo Ação */}
                        <td className="p-4">
                          <span className="inline-flex px-2 py-0.5 rounded font-extrabold uppercase text-[10px] bg-slate-100 text-slate-700 font-sans border border-slate-205">
                            {log.tipoAcao}
                          </span>
                        </td>

                        {/* Descrição */}
                        <td className="p-4 text-xs font-normal text-slate-800 leading-relaxed max-w-sm whitespace-pre-wrap">
                          {log.descricao}
                        </td>

                        {/* Operador (Nome + Cargo / RF-RE-003 compliance) */}
                        <td className="p-4">
                          <div className="flex items-center gap-1.5 font-bold text-slate-900">
                            <User size={12} className="text-slate-400" />
                            <span>{log.usuario}</span>
                          </div>
                        </td>

                        {/* Valor Auditado (monetário se houver, senão 'N/A' - RF-RE-002) */}
                        <td className="p-4 text-right font-mono font-bold text-[13px] text-slate-900">
                          {typeof log.valor === 'number' 
                            ? `R$ ${log.valor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}` 
                            : 'N/A'}
                        </td>

                        {/* Status */}
                        <td className="p-4 text-center">
                          <span className={`inline-flex px-1.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase ${
                            log.status.toLowerCase() === 'concluído' 
                              ? 'bg-emerald-50 text-emerald-700' 
                              : 'bg-slate-100 text-slate-500'
                          }`}>
                            {log.status}
                          </span>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>

            <div className="p-4 bg-slate-50 border-t border-slate-100 text-left text-[11px] font-medium text-slate-400 flex items-center gap-2">
              <span className="w-1.5 h-1.5 rounded-full bg-emerald-55 animate-ping" />
              <span>Proteção e Criptografia do Audit Trail Ativada. Os dados nesta aba são estritamente READ-ONLY (Imutabilidade Registrada).</span>
            </div>
          </div>
        </div>
      )}

      {/* MODAL DE CONFIRMAÇÃO PARA EXCLUIR RELATÓRIO DE VENDA */}
      <AnimatePresence>
        {vendaIdConfirmacaoExclusao !== null && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setVendaIdConfirmacaoExclusao(null)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden z-10 p-6 space-y-5 text-slate-800 text-left"
            >
              <div className="text-center space-y-2">
                <div className="mx-auto w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center">
                  <AlertTriangle className="text-[#a64b2a] animate-pulse" size={22} />
                </div>
                <h3 className="text-lg font-serif text-slate-950 font-semibold">Excluir Relatório</h3>
                <p className="text-slate-500 text-xs leading-relaxed">
                  Deseja realmente apagar o relatório de faturamento <strong className="text-slate-800">#{vendaIdConfirmacaoExclusao}</strong>? Esta ação é irreversível e removerá permanentemente a transação.
                </p>
              </div>

              <div className="flex items-center justify-center gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setVendaIdConfirmacaoExclusao(null)}
                  className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-650 rounded-xl transition text-xs font-semibold cursor-pointer"
                >
                  Retroceder
                </button>
                <button
                  type="button"
                  onClick={lidarComConfirmarExclusaoVenda}
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
