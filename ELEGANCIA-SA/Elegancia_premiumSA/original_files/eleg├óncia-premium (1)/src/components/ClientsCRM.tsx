import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  User, 
  Search, 
  Plus, 
  Trash2, 
  Edit, 
  DollarSign, 
  History, 
  CheckCircle, 
  AlertTriangle, 
  ArrowDownCircle, 
  X,
  CreditCard
} from 'lucide-react';
import { Cliente, Venda } from '../types';

interface ClientsCRMProps {
  clientes: Cliente[];
  vendas: Venda[];
  onAdicionarCliente: (c: Omit<Cliente, 'id'>) => void;
  onAtualizarCliente: (id: number, c: Omit<Cliente, 'id'>) => void;
  onExcluirCliente: (id: number) => void;
  onReceberPagamento: (clienteId: number, valor: number) => void;
  podeEditar: boolean;
}

export default function ClientsCRM({
  clientes,
  vendas,
  onAdicionarCliente,
  onAtualizarCliente,
  onExcluirCliente,
  onReceberPagamento,
  podeEditar
}: ClientsCRMProps) {
  const [busca, setBusca] = useState('');
  const [clienteAtivoId, setClienteAtivoId] = useState<number | null>(null);
  
  // Modal de Adicionar/Editar Cliente
  const [mostrarModal, setMostrarModal] = useState(false);
  const [clienteEditando, setClienteEditando] = useState<Cliente | null>(null);
  
  // Campos do formulário
  const [nome, setNome] = useState('');
  const [email, setEmail] = useState('');
  const [telefone, setTelefone] = useState('');
  const [cpf, setCpf] = useState('');
  const [limiteCredito, setLimiteCredito] = useState(1000);
  const [saldoDevedor, setSaldoDevedor] = useState(0);
  const [status, setStatus] = useState<'ativo' | 'inadimplente' | 'bloqueado'>('ativo');

  // Modal Receber Pagamento
  const [mostrarReceberModal, setMostrarReceberModal] = useState(false);
  const [clientePagando, setClientePagando] = useState<Cliente | null>(null);
  const [valorPagamento, setValorPagamento] = useState('');
  const [confirmouProcessar, setConfirmouProcessar] = useState(false);

  // Toast feedback state
  const [toast, setToast] = useState<{ texto: string; tipo: 'sucesso' | 'erro' } | null>(null);
  const [clienteParaExcluir, setClienteParaExcluir] = useState<Cliente | null>(null);

  const exibirToast = (texto: string, tipo: 'sucesso' | 'erro' = 'sucesso') => {
    setToast({ texto, tipo });
    setTimeout(() => {
      setToast(null);
    }, 4000);
  };

  const handleSalvar = (e: React.FormEvent) => {
    e.preventDefault();
    if (!nome.trim()) {
      exibirToast("Preencha o nome do cliente.", "erro");
      return;
    }

    const dados = {
      nome: nome.trim(),
      email: email.trim(),
      telefone: telefone.replace(/\D/g, ''),
      cpf: cpf.replace(/\D/g, ''),
      limiteCredito: Number(limiteCredito) || 0,
      saldoDevedor: Number(saldoDevedor) || 0,
      status
    };

    if (clienteEditando) {
      onAtualizarCliente(clienteEditando.id, dados);
      exibirToast(`Cliente "${dados.nome}" atualizado com sucesso!`);
    } else {
      onAdicionarCliente(dados);
      exibirToast(`Cliente "${dados.nome}" cadastrado com sucesso!`);
    }
    setMostrarModal(false);
  };

  const abrirAdicionar = () => {
    setClienteEditando(null);
    setNome('');
    setEmail('');
    setTelefone('');
    setCpf('');
    setLimiteCredito(1000);
    setSaldoDevedor(0);
    setStatus('ativo');
    setMostrarModal(true);
  };

  const abrirdireto = (c: Cliente) => {
    setNome(c.nome || '');
    setEmail(c.email || '');
    setTelefone(c.telefone || '');
    setCpf(c.cpf || '');
    setLimiteCredito(typeof c.limiteCredito === 'number' ? c.limiteCredito : 1000);
    setSaldoDevedor(typeof c.saldoDevedor === 'number' ? c.saldoDevedor : 0);
    setStatus(c.status || 'ativo');
  };

  const abrirEditar = (c: Cliente) => {
    if (c.id === 1) {
      exibirToast("Não é permitido editar o Consumidor Final padrão.", "erro");
      return;
    }
    setClienteEditando(c);
    abrirdireto(c);
    setMostrarModal(true);
  };

  const abrirPagamento = (c: Cliente) => {
    if (c.id === 1) {
      exibirToast("Consumidor Final não possui saldo devedor ou crédito.", "erro");
      return;
    }
    const devedorNum = typeof c.saldoDevedor === 'number' ? c.saldoDevedor : 0;
    if (devedorNum <= 0) {
      exibirToast("Este cliente não possui saldo devedor em aberto.", "erro");
      return;
    }
    setClientePagando(c);
    setValorPagamento(devedorNum.toFixed(2));
    setConfirmouProcessar(false);
    setMostrarReceberModal(true);
  };

  const handleProcessarPagamento = (e: React.FormEvent) => {
    e.preventDefault();
    if (!clientePagando) return;

    const valorNum = parseFloat(valorPagamento) || 0;
    if (valorNum <= 0) {
      exibirToast("Insira um valor maior que zero.", "erro");
      return;
    }

    const devedorNum = typeof clientePagando.saldoDevedor === 'number' ? clientePagando.saldoDevedor : 0;
    if (valorNum > devedorNum) {
      exibirToast(`O valor informado excede o saldo devedor (R$ ${devedorNum.toFixed(2)}).`, "erro");
      return;
    }

    if (!confirmouProcessar) {
      setConfirmouProcessar(true);
      return; // Requer segundo clique para confirmar o processo
    }

    onReceberPagamento(clientePagando.id, valorNum);
    exibirToast(`Pagamento de R$ ${valorNum.toFixed(2)} creditado para ${clientePagando.nome}!`);
    setMostrarReceberModal(false);
    setConfirmouProcessar(false);
  };

  const handleExcluir = (c: Cliente) => {
    if (c.id === 1) {
      exibirToast("Não é permitido excluir o Consumidor Final padrão.", "erro");
      return;
    }
    setClienteParaExcluir(c);
  };

  const confirmarExcluirCliente = () => {
    if (!clienteParaExcluir) return;
    onExcluirCliente(clienteParaExcluir.id);
    exibirToast("Cadastro de cliente excluído com sucesso.");
    setClienteParaExcluir(null);
  };

  const buscaLimpa = busca.replace(/\D/g, '');

  const clientesFiltrados = (clientes || []).filter(c => {
    if (!c) return false;
    const nomeLower = (c.nome || '').toLowerCase();
    const emailLower = (c.email || '').toLowerCase();
    const cpfStr = c.cpf || '';
    const telStr = c.telefone || '';
    const buscaLower = (busca || '').toLowerCase();

    return (
      nomeLower.includes(buscaLower) ||
      (buscaLimpa && cpfStr.includes(buscaLimpa)) ||
      (buscaLimpa && telStr.includes(buscaLimpa)) ||
      cpfStr.includes(busca) ||
      telStr.includes(busca) ||
      emailLower.includes(buscaLower)
    );
  });

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
          <h1 className="text-3xl font-serif text-slate-900 tracking-tight">CRM e Crédito de Clientes</h1>
          <p className="text-slate-500 text-sm mt-1">Gestão de relacionamento, análise de limite de fidelidade e recebimento de débitos.</p>
        </div>
        
        {podeEditar && (
          <button
            onClick={abrirAdicionar}
            className="bg-ebony hover:bg-reseda text-white font-bold px-4 py-2.5 rounded-lg text-xs tracking-wider uppercase transition flex items-center gap-2 cursor-pointer ml-auto sm:ml-0"
          >
            <Plus size={16} />
            <span>Cadastrar Cliente</span>
          </button>
        )}
      </div>

      {/* Busca */}
      <div className="bg-white p-4 rounded-xl border border-slate-100 shadow-sm flex items-center gap-2.5">
        <Search className="text-slate-400" size={18} />
        <input
          type="text"
          value={busca}
          onChange={(e) => setBusca(e.target.value)}
          placeholder="Buscar por nome, e-mail ou CPF do cliente..."
          className="w-full bg-transparent border-none text-slate-800 focus:outline-none placeholder:text-slate-400 text-sm"
        />
      </div>

      {/* Tabela de Clientes */}
      <div className="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full border-collapse text-left text-xs font-sans">
            <thead className="bg-slate-50 text-slate-400 font-bold uppercase tracking-wider border-b border-slate-100">
              <tr>
                <th className="p-4">Cliente</th>
                <th className="p-4">Contato / CPF</th>
                <th className="p-4">Limite de Crédito</th>
                <th className="p-4">Saldo Devedor</th>
                <th className="p-4">Disponível</th>
                <th className="p-4">Status</th>
                <th className="p-4 text-center">Ações</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 text-slate-700 font-medium">
              {clientesFiltrados.map((cli) => {
                const limit = cli.limiteCredito || 0;
                const debito = cli.saldoDevedor || 0;
                const creditoDisponivel = Math.max(0, limit - debito);
                const cliNomeLower = (cli.nome || '').toLowerCase();
                const historicVendas = (vendas || []).filter(v => 
                  v && typeof v.clienteNome === 'string' && v.clienteNome.toLowerCase() === cliNomeLower
                );

                return (
                  <React.Fragment key={cli.id}>
                    <tr className={`hover:bg-slate-50/50 transition ${clienteAtivoId === cli.id ? 'bg-slate-50/70' : ''}`}>
                      <td className="p-4">
                        <div className="flex items-center gap-3">
                          <div className="w-9 h-9 rounded-full bg-bone text-ebony flex items-center justify-center font-bold font-serif text-sm">
                            {(cli.nome || '?').charAt(0)}
                          </div>
                          <div>
                            <div className="text-sm font-bold text-slate-900">{cli.nome || 'Consumidor'}</div>
                            <div className="text-slate-400 text-[10px] lowercase">{cli.email || 'Não informado'}</div>
                          </div>
                        </div>
                      </td>
                      <td className="p-4 text-left">
                        <div>
                          {(() => {
                            const telStr = cli.telefone ? String(cli.telefone).replace(/\D/g, '') : '';
                            if (!telStr) return 'Sem telefone';
                            if (telStr.length <= 2) return `(${telStr}`;
                            if (telStr.length <= 7) return `(${telStr.slice(0, 2)}) ${telStr.slice(2)}`;
                            return `(${telStr.slice(0, 2)}) ${telStr.slice(2, 7)}-${telStr.slice(7, 11)}`;
                          })()}
                        </div>
                        <div className="text-slate-400 font-mono text-[10px] mt-0.5">
                          {(() => {
                            const cpfStr = cli.cpf ? String(cli.cpf).replace(/\D/g, '') : '';
                            if (!cpfStr) return 'Sem CPF';
                            if (cpfStr.length !== 11) return cpfStr;
                            return cpfStr.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
                          })()}
                        </div>
                      </td>
                      <td className="p-4 font-mono text-[13px] text-slate-800">
                        {cli.id === 1 ? 'N/A' : `R$ ${limit.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}
                      </td>
                      <td className={`p-4 font-mono text-[13px] ${debito > 0 ? 'text-[#a64b2a] font-bold' : 'text-slate-400'}`}>
                        {cli.id === 1 ? 'N/A' : `R$ ${debito.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}
                      </td>
                      <td className="p-4 font-mono text-[13px] text-emerald-700 font-bold">
                        {cli.id === 1 ? 'N/A' : `R$ ${creditoDisponivel.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}
                      </td>
                      <td className="p-4">
                        <span className={`inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide border ${
                          cli.status === 'ativo' 
                            ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                            : cli.status === 'inadimplente'
                            ? 'bg-amber-50 border-amber-200 text-amber-700'
                            : 'bg-rose-50 border-rose-200 text-rose-700'
                        }`}>
                          {cli.status}
                        </span>
                      </td>
                      <td className="p-4 text-center">
                        <div className="flex items-center justify-center gap-2">
                          <button
                            onClick={() => setClienteAtivoId(clienteAtivoId === cli.id ? null : cli.id)}
                            className="bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-1.5 rounded-md font-semibold transition flex items-center gap-1 cursor-pointer"
                            title="Ver histórico de compras"
                          >
                            <History size={13} />
                            <span>({historicVendas.length})</span>
                          </button>

                          {cli.id !== 1 && podeEditar && (
                            <>
                              <button
                                onClick={() => abrirPagamento(cli)}
                                className="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 px-2 py-1.5 rounded-md font-semibold transition flex items-center gap-1 cursor-pointer"
                                title="Receber débito"
                              >
                                <DollarSign size={13} />
                                <span>Pagar</span>
                              </button>
                              <button
                                onClick={() => abrirEditar(cli)}
                                className="bg-stone-50 hover:bg-[#CCBFA3]/20 text-ebony p-1.5 rounded-md transition cursor-pointer"
                                title="Editar"
                              >
                                <Edit size={13} />
                              </button>
                              <button
                                onClick={() => handleExcluir(cli)}
                                className="p-1.5 text-rose-600 hover:bg-rose-50 rounded-md transition cursor-pointer"
                                title="Excluir"
                              >
                                <Trash2 size={13} />
                              </button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>

                    {/* Histórico Desdobrável Accordion */}
                    {clienteAtivoId === cli.id && (
                      <tr>
                        <td colSpan={7} className="bg-slate-50/55 p-5 border-y border-slate-100">
                          <div className="max-w-4xl space-y-3.5 text-left text-xs font-sans">
                            <h4 className="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 flex items-center gap-1.5">
                              <History size={12} />
                              <span>Diário de Compras Realizadas de {cli.nome || 'Consumidor'}</span>
                            </h4>

                            {historicVendas.length === 0 ? (
                              <p className="text-slate-400 font-normal">Nenhum registro de venda encontrado para este cliente.</p>
                            ) : (
                              <div className="space-y-2 max-h-60 overflow-y-auto pr-1">
                                {historicVendas.map((vend) => {
                                  if (!vend) return null;
                                  const totalVenda = typeof vend.total === 'number' ? vend.total : 0;
                                  const itensArray = vend.itens || [];
                                  const dataLabel = vend.dataCriacao ? new Date(vend.dataCriacao).toLocaleString('pt-BR') : 'Sem data';

                                  return (
                                    <div key={vend.id} className="bg-white p-3.5 rounded-lg border border-slate-100 flex items-center justify-between gap-4 shadow-xs">
                                      <div className="space-y-1">
                                        <div className="font-bold text-slate-800">Cód Venda: #{vend.id}</div>
                                        <div className="text-slate-550 text-[11px]">
                                          {itensArray.map(it => `${it && typeof it.quantidade === 'number' ? it.quantidade : 0}x ${it && typeof it.nomeProduto === 'string' ? it.nomeProduto : 'Produto'}`).join(', ') || 'Nenhum item discriminado'}
                                        </div>
                                        <span className="text-[10px] font-bold text-slate-400">
                                          Data: {dataLabel}
                                        </span>
                                      </div>
                                      <div className="text-right flex items-center gap-6">
                                        <div className="space-y-0.5">
                                          <span className="text-[10px] text-slate-400 uppercase tracking-wider block font-semibold">Valor Total</span>
                                          <span className="font-mono text-sm font-bold text-slate-900">R$ {totalVenda.toFixed(2)}</span>
                                        </div>
                                        <span className={`px-2 py-0.5 rounded-full text-[9px] font-extrabold uppercase font-sans border ${
                                          vend.status === 'concluido' 
                                            ? 'bg-emerald-50 border-emerald-200 text-emerald-700' 
                                            : 'bg-amber-50 border-amber-200 text-amber-700 animate-pulse'
                                        }`}>
                                          {vend.status === 'concluido' ? 'Concluído' : 'Pendente (Crédito)'}
                                        </span>
                                      </div>
                                    </div>
                                  );
                                })}
                              </div>
                            )}
                          </div>
                        </td>
                      </tr>
                    )}
                  </React.Fragment>
                );
              })}
            </tbody>
          </table>
        </div>
      </div>

      {/* MODAL CADASTRAR / EDITAR CLIENTE */}
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
              className="relative w-full max-w-md bg-white rounded-xl shadow-xl overflow-hidden z-10 p-6 space-y-5 text-left"
            >
              <div>
                <h3 className="text-lg font-serif text-slate-900">
                  {clienteEditando ? "Editar Ficha de Cliente" : "Cadastrar Novo Cliente"}
                </h3>
                <p className="text-slate-400 text-xs">Atualize os limites, contatos e status comerciais.</p>
              </div>

              <form onSubmit={handleSalvar} className="space-y-4 text-xs">
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-550 mb-1 uppercase">Nome Completo *</label>
                    <input
                      type="text"
                      required
                      value={nome}
                      onChange={(e) => setNome(e.target.value)}
                      placeholder="Amanda Duarte"
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 text-xs focus:ring-1 focus:ring-ebony"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-550 mb-1 uppercase">E-mail</label>
                    <input
                      type="email"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      placeholder="amanda@fidelizado.com"
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 text-xs focus:ring-1 focus:ring-ebony"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-550 mb-1 uppercase">CPF</label>
                    <input
                      type="text"
                      maxLength={14}
                      value={cpf}
                      onChange={(e) => setCpf(e.target.value)}
                      placeholder="000.000.000-00"
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 text-xs font-mono focus:ring-1 focus:ring-ebony"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-550 mb-1 uppercase">Telefone</label>
                    <input
                      type="text"
                      value={telefone}
                      onChange={(e) => setTelefone(e.target.value)}
                      placeholder="(11) 99999-9999"
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 text-xs font-mono focus:ring-1 focus:ring-ebony"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-3 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-550 mb-1 uppercase font-mono">Limite Crédito</label>
                    <div className="relative">
                      <span className="absolute left-2.5 top-2.5 text-slate-400 font-semibold text-[10px]">R$</span>
                      <input
                        type="number"
                        required
                        value={limiteCredito}
                        onChange={(e) => setLimiteCredito(Math.max(0, Number(e.target.value) || 0))}
                        className="w-full bg-slate-50 border border-slate-100 rounded-lg pl-7 pr-2.5 py-2 text-slate-800 text-xs font-mono focus:ring-1 focus:ring-ebony font-bold"
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-550 mb-1 uppercase font-mono">Saldo Devedor</label>
                    <div className="relative">
                      <span className="absolute left-2.5 top-2.5 text-slate-400 font-semibold text-[10px]">R$</span>
                      <input
                        type="number"
                        required
                        value={saldoDevedor}
                        disabled={clienteEditando !== null} // saldo devedor is usually derived or updated via payment/PDV
                        onChange={(e) => setSaldoDevedor(Math.max(0, Number(e.target.value) || 0))}
                        className="w-full bg-slate-55 disabled:bg-slate-100 disabled:text-slate-400 border border-slate-100 rounded-lg pl-7 pr-2.5 py-2 text-slate-800 text-xs font-mono focus:ring-1 focus:ring-ebony font-bold"
                      />
                    </div>
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-550 mb-1 uppercase">Status</label>
                    <select
                      value={status}
                      onChange={(e) => setStatus(e.target.value as any)}
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg px-2.5 py-2 text-slate-800 text-xs font-semibold focus:ring-1 focus:ring-ebony"
                    >
                      <option value="ativo">Ativo</option>
                      <option value="inadimplente">Inadimplente</option>
                      <option value="bloqueado">Bloqueado</option>
                    </select>
                  </div>
                </div>

                <div className="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setMostrarModal(false)}
                    className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-650 rounded-lg font-bold transition uppercase"
                  >
                    Mudar de Ideia
                  </button>
                  <button
                    type="submit"
                    className="px-5 py-2.5 bg-ebony hover:bg-reseda text-white rounded-lg font-bold transition uppercase tracking-wider"
                  >
                    Confirmar Salvar
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* MODAL RECEBER PAGAMENTO */}
      <AnimatePresence>
        {mostrarReceberModal && clientePagando && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMostrarReceberModal(false)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-sm bg-white rounded-xl shadow-xl overflow-hidden z-10 p-6 space-y-5 text-left font-sans"
            >
              <div className="flex items-center gap-3">
                <div className="w-10 h-10 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center">
                  <ArrowDownCircle size={22} />
                </div>
                <div>
                  <h3 className="text-base font-bold text-slate-950">Receber e Abater Crédito</h3>
                  <p className="text-slate-400 text-xs">Amortização de saldo devedor de {clientePagando.nome}.</p>
                </div>
              </div>

               <div className="bg-slate-50 p-4 rounded-lg border border-slate-100 space-y-2 text-slate-650 text-xs">
                 <div className="flex justify-between font-medium">
                   <span>Limite de Fiador</span>
                   <span>R$ {(typeof clientePagando.limiteCredito === 'number' ? clientePagando.limiteCredito : 0).toFixed(2)}</span>
                 </div>
                 <div className="flex justify-between font-bold text-[#a64b2a]">
                   <span>Saldo Devedor Ativo</span>
                   <span>R$ {(typeof clientePagando.saldoDevedor === 'number' ? clientePagando.saldoDevedor : 0).toFixed(2)}</span>
                 </div>
               </div>

              <form onSubmit={handleProcessarPagamento} className="space-y-4 text-xs">
                <div>
                  <label className="block text-xs font-bold text-slate-550 mb-1.5 uppercase font-mono">Valor de Entrada para Quitar (R$)</label>
                  <div className="relative">
                    <span className="absolute left-3 top-3.5 text-slate-400 font-bold font-mono">R$</span>
                    <input
                      type="number"
                      step="0.01"
                      min="0.01"
                      max={clientePagando.saldoDevedor}
                      required
                      value={valorPagamento}
                      onChange={(e) => {
                        setValorPagamento(e.target.value);
                        setConfirmouProcessar(false); // reseta confirmação se alterou valor
                      }}
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg pl-8 pr-3 py-3 font-mono font-bold text-sm text-slate-800 focus:outline-none"
                    />
                  </div>
                </div>

                {confirmouProcessar && (
                  <motion.div
                    initial={{ opacity: 0, scale: 0.98 }}
                    animate={{ opacity: 1, scale: 1 }}
                    className="bg-amber-50 border border-amber-200 text-amber-800 p-3 rounded-lg text-center"
                  >
                    <p className="font-bold flex items-center gap-1.5 justify-center">
                      <AlertTriangle size={15} />
                      Confirmar Operação?
                    </p>
                    <p className="font-normal text-[10.5px] mt-0.5">Clique no botão abaixo novamente para consolidar.</p>
                  </motion.div>
                )}

                <div className="flex items-center gap-2 pt-2">
                  <button
                    type="button"
                    onClick={() => {
                      setMostrarReceberModal(false);
                      setConfirmouProcessar(false);
                    }}
                    className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-650 rounded-xl transition font-semibold"
                  >
                    Retroceder
                  </button>
                  <button
                    type="submit"
                    className={`w-full py-2.5 text-white rounded-xl font-bold transition uppercase tracking-wider ${
                      confirmouProcessar ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-[#414833] hover:bg-[#737A5D]'
                    }`}
                  >
                    {confirmouProcessar ? 'Processar Pagamento' : 'Amortizar'}
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
 
      {/* MODAL CONFIRMAÇÃO EDITÁVEL EXCLUSÃO CLIENTE */}
      <AnimatePresence>
        {clienteParaExcluir && (
          <div className="fixed inset-0 z-55 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setClienteParaExcluir(null)}
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
                <h3 className="text-lg font-serif text-slate-900 font-semibold">Excluir Cadastro</h3>
                <p className="text-slate-500 text-xs leading-relaxed">
                  Tem certeza de que deseja banir ou arquivar a ficha cadastral do cliente <strong className="text-slate-900">"{clienteParaExcluir.nome}"</strong>?
                </p>

                {(clienteParaExcluir.saldoDevedor || 0) > 0 && (
                  <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 text-left">
                    <p className="font-extrabold text-[11px] text-[#a64b2a] uppercase tracking-wider">Atenção Especial:</p>
                    <p className="text-slate-650 text-[10.5px] mt-0.5">
                      Este usuário possui um saldo pendente de <strong className="text-red-800 font-mono">R$ {(clienteParaExcluir.saldoDevedor || 0).toFixed(2)}</strong>. Ao prosseguir, essa dívida será permanentemente omitida ou perdida.
                    </p>
                  </div>
                )}
              </div>

              <div className="flex items-center justify-center gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setClienteParaExcluir(null)}
                  className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-650 rounded-xl transition text-xs font-semibold cursor-pointer"
                >
                  Cancelar
                </button>
                <button
                  type="button"
                  onClick={confirmarExcluirCliente}
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
