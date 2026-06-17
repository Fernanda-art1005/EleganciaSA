import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  ShoppingCart, 
  Trash2, 
  Tag, 
  AlertTriangle, 
  CheckCircle, 
  Package, 
  Plus, 
  Minus, 
  CreditCard, 
  PlusCircle, 
  Search,
  UserCheck,
  RefreshCw,
  Wallet,
  Smartphone,
  Banknote,
  Percent
} from 'lucide-react';
import { Produto, Cliente, ItemCarrinho } from '../types';

interface PDVProps {
  produtos: Produto[];
  clientes: Cliente[];
  onAdicionarCliente: (c: Omit<Cliente, 'id'>) => void;
  onAtualizarCliente: (id: number, c: Omit<Cliente, 'id'>) => void;
  onExcluirCliente: (id: number) => void;
  onConfirmarVenda: (
    clienteNome: string,
    carrinho: ItemCarrinho[],
    total: number,
    desconto: number,
    imposto: number,
    formaPagamento: 'debito' | 'credito_loja' | 'pix' | 'cartao',
    clienteId?: number
  ) => void;
}

export default function PDV({
  produtos,
  clientes,
  onAdicionarCliente,
  onAtualizarCliente,
  onExcluirCliente,
  onConfirmarVenda
}: PDVProps) {
  const [carrinho, setCarrinho] = useState<ItemCarrinho[]>([]);
  const [idClienteAtivo, setIdClienteAtivo] = useState<number>(1); // 1 = Consumidor Final como padrão

  const [produtoSelecionadoId, setProdutoSelecionadoId] = useState<number>(produtos[0]?.id || 0);
  const [quantidadeDesejada, setQuantidadeDesejada] = useState<number>(1);
  const [buscaProduto, setBuscaProduto] = useState('');

  const [descontoTexto, setDescontoTexto] = useState<string>('0.00');
  
  // RF-CX-006: Formas de pagamento
  const [formaPagamento, setFormaPagamento] = useState<'debito' | 'credito_loja' | 'pix' | 'cartao'>('pix');

  // Controle de Modais de Cliente
  const [mostrarModalCliente, setMostrarModalCliente] = useState(false);
  const [clienteEditando, setClienteEditando] = useState<Cliente | null>(null);
  
  // Campos do formulário de cliente
  const [cliNome, setCliNome] = useState('');
  const [cliEmail, setCliEmail] = useState('');
  const [cliCpf, setCliCpf] = useState('');
  const [cliTelefone, setCliTelefone] = useState('');
  const [cliLimite, setCliLimite] = useState(1000);

  // Notificação de Compra Realizada ou Erros
  const [notificacao, setNotificacao] = useState<{ texto: string; tipo: 'sucesso' | 'erro' } | null>(null);
  const [mostrarConfirmacaoExcluirCliente, setMostrarConfirmacaoExcluirCliente] = useState(false);
  const [mostrarConfirmacaoLimparCarrinho, setMostrarConfirmacaoLimparCarrinho] = useState(false);

  const mostrarNotificacao = (texto: string, tipo: 'sucesso' | 'erro' = 'sucesso') => {
    setNotificacao({ texto, tipo });
    setTimeout(() => {
      setNotificacao(current => current?.texto === texto ? null : current);
    }, 4000);
  };

  const lidarComEntradaNumerica = (valor: string, setValor: (v: string) => void) => {
    const limpo = valor.replace(/\D/g, ''); // Remove não-números
    if (limpo.length <= 11) {
      setValor(limpo);
    }
  };

  // Encontrar correspondentes ativos
  const produtoAtivo = produtos.find(p => p.id === produtoSelecionadoId);
  const clienteAtivo = clientes.find(c => c.id === idClienteAtivo) || clientes[0];

  // RF-CX-002: Busca de produto em tempo real (filtra se >= 3 caracteres)
  const produtosFiltrados = produtos.filter(p => {
    if (buscaProduto.trim().length < 3) return true;
    return p.nome.toLowerCase().includes(buscaProduto.toLowerCase());
  });

  // Adicionar item ao carrinho
  const lidarComAdicionarItem = () => {
    if (!produtoAtivo) return;

    if (quantidadeDesejada <= 0) {
      mostrarNotificacao("Selecione uma quantidade maior que zero.", 'erro');
      return;
    }

    // Verificar se já existe no carrinho para validar estoque cumulativo
    const itemExistente = carrinho.find(item => item.id === produtoAtivo.id);
    const qtdTotalNoCarrinho = itemExistente ? itemExistente.qtd : 0;
    const qtdEstoque = produtoAtivo.estoque;

    if (qtdTotalNoCarrinho + quantidadeDesejada > qtdEstoque) {
      mostrarNotificacao(`Quantidade inválida! Estoque atual: ${qtdEstoque}. Já no caixa: ${qtdTotalNoCarrinho}.`, 'erro');
      return;
    }

    if (itemExistente) {
      setCarrinho(carrinho.map(item => {
        if (item.id === produtoAtivo.id) {
          const novaQtd = item.qtd + quantidadeDesejada;
          return {
            ...item,
            qtd: novaQtd,
            total: novaQtd * item.preco
          };
        }
        return item;
      }));
    } else {
      setCarrinho([...carrinho, {
        id: produtoAtivo.id,
        nome: produtoAtivo.nome,
        qtd: quantidadeDesejada,
        preco: produtoAtivo.preco,
        total: quantidadeDesejada * produtoAtivo.preco,
        imagemUrl: produtoAtivo.imagemUrl
      }]);
    }

    setQuantidadeDesejada(1);
    mostrarNotificacao(`Adicionado ao carrinho: ${quantidadeDesejada}x "${produtoAtivo.nome}"`);
  };

  const lidarComRemoverItem = (id: number) => {
    setCarrinho(carrinho.filter(item => item.id !== id));
    mostrarNotificacao("Item removido do caixa.", "sucesso");
  };

  const lidarComLimparCarrinho = () => {
    setMostrarConfirmacaoLimparCarrinho(true);
  };

  const confirmarLimparCarrinho = () => {
    setCarrinho([]);
    setDescontoTexto('0.00');
    setMostrarConfirmacaoLimparCarrinho(false);
    mostrarNotificacao("Carrinho limpo com sucesso!", "sucesso");
  };

  // Cálculo matemático com imposto conforme RF-CX-004
  const subtotal = carrinho.reduce((sum, item) => sum + item.total, 0);
  const desconto = Math.max(0, parseFloat(descontoTexto.replace(',', '.')) || 0);
  const total = Math.max(0, subtotal - desconto);
  
  // Alíquota de imposto padrão (e.g., 5%)
  const imposto = total * 0.05;

  // Modais de Cliente
  const abrirNovoClienteModal = () => {
    setClienteEditando(null);
    setCliNome('');
    setCliEmail('');
    setCliCpf('');
    setCliTelefone('');
    setCliLimite(1000);
    setMostrarModalCliente(true);
  };

  const abrirEditarClienteModal = () => {
    if (clienteAtivo.id === 1) {
      mostrarNotificacao("Não é permitido editar o Consumidor Final padrão.", 'erro');
      return;
    }
    setClienteEditando(clienteAtivo);
    setCliNome(clienteAtivo.nome);
    setCliEmail(clienteAtivo.email || '');
    setCliCpf(clienteAtivo.cpf || '');
    setCliTelefone(clienteAtivo.telefone || '');
    setCliLimite(clienteAtivo.limiteCredito || 1000);
    setMostrarModalCliente(true);
  };

  const lidarComSalvarCliente = (e: React.FormEvent) => {
    e.preventDefault();
    if (!cliNome.trim()) {
      mostrarNotificacao("Insira o nome do cliente.", 'erro');
      return;
    }

    const payload = {
      nome: cliNome.trim(),
      email: cliEmail.trim().toLowerCase(),
      cpf: cliCpf,
      telefone: cliTelefone,
      limiteCredito: Number(cliLimite) || 1000,
      saldoDevedor: clienteEditando ? clienteEditando.saldoDevedor : 0,
      status: clienteEditando ? clienteEditando.status : 'ativo' as const
    };

    if (clienteEditando) {
      onAtualizarCliente(clienteEditando.id, payload);
      mostrarNotificacao(`Cadastro de "${cliNome}" atualizado com sucesso!`, 'sucesso');
    } else {
      onAdicionarCliente(payload);
      mostrarNotificacao(`Cliente "${cliNome}" cadastrado e pronto para faturar!`, 'sucesso');
    }

    setMostrarModalCliente(false);
  };

  const lidarComExcluirCliente = () => {
    if (clienteAtivo.id === 1) {
      mostrarNotificacao("Não é permitido excluir o Consumidor Final padrão.", 'erro');
      return;
    }
    setMostrarConfirmacaoExcluirCliente(true);
  };

  const confirmarExcluirCliente = () => {
    onExcluirCliente(clienteAtivo.id);
    setIdClienteAtivo(1);
    setMostrarConfirmacaoExcluirCliente(false);
    mostrarNotificacao(`Cliente excluído com sucesso!`, 'sucesso');
  };

  // RF-CX-008: Dados de Limite de Crédito
  const creditoDisponivel = Math.max(0, clienteAtivo.limiteCredito - clienteAtivo.saldoDevedor);

  // RF-CX-009: Bloqueio por limite excedido se selecionado 'credito_loja'
  const limiteExcedido = formaPagamento === 'credito_loja' && total > creditoDisponivel;

  // RF-CX-007: Sem selecionar cliente cadastrado, botão concluir fica desabilitado
  const requerClienteParaCredito = formaPagamento === 'credito_loja' && clienteAtivo.id === 1;

  // Conclusão Fiscal da Venda
  const lidarComFinalizarVenda = () => {
    if (carrinho.length === 0) {
      mostrarNotificacao("Por favor, adicione pelo menos um produto para concluir a venda.", 'erro');
      return;
    }

    // RF-CX-007
    if (requerClienteParaCredito) {
      mostrarNotificacao("Para pagamento a prazo (Crédito Loja), você deve selecionar um cliente cadastrado específico.", 'erro');
      return;
    }

    // RF-CX-009
    if (limiteExcedido) {
      mostrarNotificacao(`Venda bloqueada! Limite de crédito do cliente excedido. Disponível: R$ ${creditoDisponivel.toFixed(2)} - Compra: R$ ${total.toFixed(2)}.`, 'erro');
      return;
    }

    // Valida se cliente está bloqueado
    if (formaPagamento === 'credito_loja' && clienteAtivo.status === 'bloqueado') {
      mostrarNotificacao(`Venda bloqueada! O crédito de ${clienteAtivo.nome} no momento está classificado como BLOQUEADO no CRM.`, 'erro');
      return;
    }

    onConfirmarVenda(clienteAtivo.nome, carrinho, total, desconto, imposto, formaPagamento, clienteAtivo.id);
    
    mostrarNotificacao(`Compra concluída! Pago: R$ ${total.toFixed(2)} [Modalidade: ${formaPagamento}] - Cliente: ${clienteAtivo.nome}`, 'sucesso');
    setCarrinho([]);
    setDescontoTexto('0.00');
  };

  return (
    <div className="space-y-8 relative">
      
      {/* Toast Notificação de Sucesso ou Erro */}
      <AnimatePresence>
        {notificacao && (
          <motion.div
            initial={{ opacity: 0, y: -50, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, scale: 0.9 }}
            className={`fixed top-6 right-6 z-55 p-4 rounded-xl shadow-xl flex items-center gap-3 border ${
              notificacao.tipo === 'erro' 
                ? 'bg-rose-950 border-rose-900/40 text-rose-205' 
                : 'bg-stone-900 border-stone-800 text-white'
            }`}
          >
            {notificacao.tipo === 'erro' ? (
              <AlertTriangle className="text-rose-450 shrink-0" size={22} />
            ) : (
              <CheckCircle className="text-sage shrink-0" size={22} />
            )}
            <div className="text-left text-xs">
              <p className={`font-bold uppercase tracking-wider ${notificacao.tipo === 'erro' ? 'text-rose-400' : 'text-sage'}`}>
                {notificacao.tipo === 'erro' ? 'Atenção' : 'Operação Efetuada'}
              </p>
              <p className="text-stone-300 font-medium">{notificacao.texto}</p>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Header */}
      <div>
        <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Ponto de Venda (PDV)</h1>
        <p className="text-slate-500 text-sm mt-1">Gere novas vendas, decresça roupas do estoque do ateliê, valide limite de fiadores e emita cupons.</p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        {/* Lado Esquerdo: Formulários e Lançamentos (Columns: 5) */}
        <div className="lg:col-span-5 space-y-6">
          
          {/* Sessão Cliente */}
          <div className="bg-white p-5 rounded-xl border border-slate-100 shadow-sm space-y-4 text-left">
            <div className="flex items-center justify-between">
              <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Perfil Cliente do Caixa</span>
              <div className="flex gap-2">
                <button
                  onClick={abrirNovoClienteModal}
                  className="p-1 px-2.5 bg-bone hover:bg-dun/60 text-ebony rounded-md text-[11px] font-bold transition flex items-center gap-1 cursor-pointer"
                  title="Fidelizar cliente"
                >
                  <PlusCircle size={13} />
                  <span>Cadastrar</span>
                </button>
                {clienteAtivo.id !== 1 && (
                  <>
                    <button
                      onClick={abrirEditarClienteModal}
                      className="p-1 px-2 bg-slate-50 hover:bg-slate-100 text-slate-600 rounded-md text-[11px] font-semibold transition"
                      title="Editar selecionado"
                    >
                      EditarFicha
                    </button>
                    <button
                      onClick={lidarComExcluirCliente}
                      className="p-1 px-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-md text-[11px] font-semibold transition animate-pulse"
                      title="Excluir cadastro"
                    >
                      Excluir
                    </button>
                  </>
                )}
              </div>
            </div>

             <div>
               <select
                 value={idClienteAtivo}
                 onChange={(e) => setIdClienteAtivo(Number(e.target.value))}
                 className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3.5 py-3 text-slate-805 text-sm font-semibold focus:outline-none focus:ring-1 focus:ring-ebony"
               >
                 {clientes.map(cli => (
                   <option key={cli.id} value={cli.id}>
                     {cli.nome || 'Consumidor'} {cli.cpf ? `[CPF: ${String(cli.cpf).replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4")}]` : ''}
                   </option>
                 ))}
               </select>
             </div>

             {/* RF-CX-008: Exibição do limite de crédito do cliente no PDV antes da confirmação da venda */}
             {clienteAtivo.id !== 1 && (
               <div className="bg-slate-50 p-3.5 rounded-lg text-xs space-y-1.5 text-slate-500 border border-slate-100">
                 <p className="flex justify-between">
                   <strong className="text-slate-655">Saldo Devedor Ativo:</strong> 
                   <span className="font-mono text-rose-700 font-bold">R$ {(clienteAtivo.saldoDevedor || 0).toFixed(2)}</span>
                 </p>
                 <p className="flex justify-between border-t border-slate-200/50 pt-1.5">
                   <strong className="text-slate-655">Limite de Crédito Total:</strong> 
                   <span className="font-mono font-semibold">R$ {(clienteAtivo.limiteCredito || 0).toFixed(2)}</span>
                 </p>
                 <p className="flex justify-between font-bold border-t border-slate-200/50 pt-1.5 text-emerald-800">
                   <span>Crédito Disponível:</span> 
                   <span className="font-mono">R$ {(creditoDisponivel || 0).toFixed(2)}</span>
                 </p>
               </div>
             )}
          </div>

          {/* Sessão Escolher Roupas */}
          <div className="bg-white p-5 rounded-xl border border-slate-100 shadow-sm space-y-4 text-left">
            <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">Escolha Vestuários</span>
            
            {/* RF-CX-002: Campo de pesquisa */}
            <div className="relative">
              <Search className="absolute left-3 top-3 text-slate-400" size={14} />
              <input
                type="text"
                value={buscaProduto}
                onChange={(e) => setBuscaProduto(e.target.value)}
                placeholder="Digitar pelo menos 3 caracteres para buscar..."
                className="w-full bg-slate-50 border border-slate-100 rounded-lg pl-9 pr-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-ebony text-xs"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-500 mb-1.5">Selecionar Produto do Catálogo</label>
              <select
                value={produtoSelecionadoId}
                onChange={(e) => {
                  setProdutoSelecionadoId(Number(e.target.value));
                  setQuantidadeDesejada(1);
                }}
                className="w-full bg-slate-50 border border-slate-100 rounded-lg p-3 text-slate-800 text-xs font-semibold focus:outline-none"
              >
                {produtosFiltrados.map(prod => (
                  <option key={prod.id} value={prod.id} disabled={prod.estoque <= 0}>
                    {prod.nome} {prod.estoque <= 0 ? ' [ESGOTADO]' : `(Est: ${prod.estoque})`} - R$ {prod.preco.toFixed(2)}
                  </option>
                ))}
              </select>
            </div>

            {/* Prévia do Produto */}
            {produtoAtivo && (
              <div className="flex gap-4 p-3 bg-slate-50/50 rounded-xl border border-dashed border-slate-205 items-center">
                <img
                  src={produtoAtivo.imagemUrl}
                  alt={produtoAtivo.nome}
                  referrerPolicy="no-referrer"
                  className="w-14 h-14 rounded-md object-cover border border-slate-100 shrink-0"
                />
                <div>
                  <h4 className="text-xs font-bold text-slate-900 leading-tight">{produtoAtivo.nome}</h4>
                  <p className="text-[10px] text-slate-400 mt-0.5">Tamanho: {produtoAtivo.tamanho || "M"} | Categoria: {produtoAtivo.categoria || "Geral"}</p>
                  <p className="text-sm font-bold text-ebony mt-1 font-mono">R$ {produtoAtivo.preco.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</p>
                </div>
              </div>
            )}

            <div className="grid grid-cols-2 gap-4 items-end">
              <div>
                <label className="block text-[10px] font-bold text-slate-450 uppercase mb-1">Quantidade de Peças</label>
                <div className="flex items-center">
                  <button
                    type="button"
                    onClick={() => setQuantidadeDesejada(Math.max(1, quantidadeDesejada - 1))}
                    className="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-l-lg transition cursor-pointer"
                  >
                    <Minus size={13} />
                  </button>
                  <input
                    type="number"
                    min="1"
                    value={quantidadeDesejada}
                    onChange={(e) => setQuantidadeDesejada(Math.max(1, parseInt(e.target.value) || 1))}
                    className="w-full bg-slate-50 border-y border-slate-100 text-center py-2 text-xs text-slate-800 font-mono font-bold focus:outline-none"
                  />
                  <button
                    type="button"
                    onClick={() => setQuantidadeDesejada(quantidadeDesejada + 1)}
                    className="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-r-lg transition cursor-pointer"
                  >
                    <Plus size={13} />
                  </button>
                </div>
              </div>

              <motion.button
                whileHover={{ scale: 1.02 }}
                whileTap={{ scale: 0.98 }}
                onClick={lidarComAdicionarItem}
                className="w-full bg-ebony hover:bg-reseda text-white font-bold py-2.5 rounded-lg text-xs transition tracking-widest uppercase cursor-pointer"
              >
                Incluir Peça
              </motion.button>
            </div>
          </div>
        </div>

        {/* Lado Direito: Carrinho de Compras e Fechamento (Columns: 7) */}
        <div className="lg:col-span-7 bg-white p-6 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between min-h-[500px]">
          
          <div>
            <div className="flex justify-between items-center pb-4 border-b border-slate-50 text-left">
              <div className="flex items-center gap-2 text-slate-900 font-serif">
                <ShoppingCart className="text-ebony" size={20} />
                <h3 className="text-lg">Checkout da Venda</h3>
              </div>
              
              {carrinho.length > 0 && (
                <button
                  onClick={lidarComLimparCarrinho}
                  className="text-xs font-semibold text-slate-400 hover:text-[#a64b2a] transition cursor-pointer"
                >
                  Limpar Carrinho
                </button>
              )}
            </div>

            {/* Listagem de Itens no Carrinho */}
            {carrinho.length === 0 ? (
              <div className="py-20 text-center text-slate-400 space-y-3">
                <Package className="mx-auto text-slate-150" size={44} />
                <p className="text-xs font-bold uppercase tracking-wider text-slate-400">Caixa Registradora Vazia</p>
                <p className="text-xs font-normal max-w-xs mx-auto text-slate-400">Insira as roupas do ateliê para calcular os totais e notas fiscais do ateliê.</p>
              </div>
            ) : (
              <div className="divide-y divide-slate-50 max-h-[280px] overflow-y-auto pr-2 mt-4 space-y-1.5 text-xs text-left">
                {carrinho.map(item => (
                  <div key={item.id} className="py-3 flex items-center justify-between group">
                    <div className="flex items-center gap-3">
                      <img
                        src={item.imagemUrl}
                        alt={item.nome}
                        referrerPolicy="no-referrer"
                        className="w-10 h-10 object-cover rounded border border-slate-100"
                      />
                      <div>
                        <p className="font-bold text-slate-800 line-clamp-1">{item.nome}</p>
                        <p className="text-[10px] text-slate-400 font-mono mt-0.5">{item.qtd}x de R$ {item.preco.toFixed(2)}</p>
                      </div>
                    </div>

                    <div className="flex items-center gap-4">
                      <p className="font-bold text-slate-850 font-mono">
                        R$ {item.total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                      </p>
                      <button
                        onClick={() => lidarComRemoverItem(item.id)}
                        className="p-1.5 text-slate-400 hover:text-[#a64b2a] rounded transition hover:bg-rose-50"
                        title="Remover produto"
                      >
                        <Trash2 size={13} />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Área de Desconto e Fechamento */}
          <div className="pt-6 border-t border-slate-50 mt-8 space-y-5 text-left">
            
            {/* Escolha de Forma de Pagamento */}
            <div className="space-y-2">
              <label className="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Modalidade de Pagamento</label>
              <div className="grid grid-cols-4 gap-2">
                {(['debito', 'credito_loja', 'pix', 'cartao'] as const).map(option => {
                  const label = option === 'debito' ? 'Débito' : option === 'credito_loja' ? 'Crédito Loja' : option === 'pix' ? 'PIX' : 'Cartão';
                  const active = formaPagamento === option;
                  return (
                    <button
                      key={option}
                      type="button"
                      onClick={() => setFormaPagamento(option)}
                      className={`py-3 rounded-lg border transition text-center flex flex-col items-center justify-center gap-1 cursor-pointer ${
                        active 
                          ? 'bg-[#414833] border-[#414833] text-white shadow-sm font-bold' 
                          : 'bg-slate-50 border-slate-100 text-slate-500 hover:bg-slate-100'
                      }`}
                    >
                      {option === 'debito' && <Wallet size={15} />}
                      {option === 'credito_loja' && <CreditCard size={15} />}
                      {option === 'pix' && <Smartphone size={15} />}
                      {option === 'cartao' && <Banknote size={15} />}
                      <span className="text-[10px] uppercase font-sans tracking-wide block leading-none">{label}</span>
                    </button>
                  );
                })}
              </div>
            </div>

            <div className="flex items-center justify-between gap-4">
              <div className="flex items-center gap-2 text-slate-500">
                <Tag size={16} className="text-slate-400" />
                <span className="text-xs font-semibold">Cupom Desconto Concedido:</span>
              </div>
              <div className="relative max-w-[140px]">
                <span className="absolute left-2.5 top-2 text-slate-400 text-xs font-bold font-mono">R$</span>
                <input
                  type="text"
                  value={descontoTexto}
                  onChange={(e) => setDescontoTexto(e.target.value)}
                  className="w-full text-right bg-slate-50 border border-slate-100 rounded-md pl-7 pr-2.5 py-1.5 focus:outline-none focus:ring-1 focus:ring-ebony font-mono font-bold text-slate-800 text-xs"
                />
              </div>
            </div>

            {/* Alertas e restrições de limites de crédito (RF-CX-007, RF-CX-009) */}
            {requerClienteParaCredito && (
              <div className="bg-rose-50 border border-rose-150 p-3 rounded-lg flex items-center gap-2 text-rose-800 text-xs text-left">
                <AlertTriangle size={15} className="shrink-0 text-rose-600 animate-bounce" />
                <p className="font-medium font-sans leading-tight">Escolha obrigatória de cliente com saldo ativo no CRM para faturar no modo de crédito.</p>
              </div>
            )}

            {limiteExcedido && (
              <div className="bg-[#a64b2a]/5 border border-[#a64b2a]/20 p-3.5 rounded-lg flex items-start gap-2.5 text-[#a64b2a] text-xs text-left font-sans">
                <AlertTriangle size={16} className="shrink-0 text-[#a64b2a] animate-pulse" />
                <div className="space-y-0.5 leading-tight">
                  <p className="font-bold">Limite de Fiador Excedido!</p>
                  <p className="font-normal text-[11px] text-slate-500">O total da transação excede o crédito outorgado de R$ {creditoDisponivel.toFixed(2)}.</p>
                </div>
              </div>
            )}

            {/* Visor Fiscal de Subtotal e Total */}
            <div className="bg-slate-50/75 p-4 rounded-xl border border-slate-100/90 space-y-1.5 font-sans">
              <div className="flex justify-between text-xs text-slate-500 font-medium">
                <span>Subtotal Lançamentos</span>
                <span className="font-mono">R$ {subtotal.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
              </div>
              <div className="flex justify-between text-xs text-[#a64b2a] font-medium font-bold">
                <span>Descontos de Cupom (-)</span>
                <span className="font-mono">- R$ {desconto.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
              </div>
              <div className="flex justify-between text-xs text-slate-500 font-medium border-t border-dashed border-slate-200/50 pt-1.5">
                <span className="flex items-center gap-1">
                  <Percent size={12} className="text-slate-400" />
                  Imposto Simples Incluso (5%)
                </span>
                <span className="font-mono">R$ {imposto.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</span>
              </div>
              <div className="flex justify-between text-sm font-bold text-slate-930 pt-2 border-t border-slate-100/70">
                <span className="uppercase text-[9px] tracking-wider text-slate-450 align-middle">TOTAL DO CLIENTE</span>
                <span className="text-xl text-ebony font-mono font-bold">
                  R$ {total.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                </span>
              </div>
            </div>

            {/* Confirmação Master */}
            <motion.button
              whileHover={{ scale: 1.01 }}
              whileTap={{ scale: 0.99 }}
              onClick={lidarComFinalizarVenda}
              disabled={limiteExcedido || requerClienteParaCredito}
              className="w-full bg-ebony disabled:bg-slate-200 disabled:text-slate-400 disabled:shadow-none hover:bg-reseda text-white font-bold py-4 rounded-xl text-xs tracking-widest uppercase transition flex items-center justify-center gap-2 shadow-md shadow-stone-200 cursor-pointer"
            >
              <CreditCard size={16} className={limiteExcedido || requerClienteParaCredito ? 'text-slate-400' : 'text-sage'} />
              <span>Concluir Venda e Despachar</span>
            </motion.button>
          </div>
        </div>

      </div>

      {/* MODAL CADASTRAR / EDITAR CLIENTE */}
      <AnimatePresence>
        {mostrarModalCliente && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMostrarModalCliente(false)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden z-10 p-6 space-y-5"
            >
              <div className="text-left">
                <h3 className="text-lg font-serif text-slate-900">
                  {clienteEditando ? "Editar Ficha de Cliente" : "Cadastrar Novo Cliente"}
                </h3>
                <p className="text-slate-400 text-xs">Insira limites financeiros de fidelidade para faturamento.</p>
              </div>

              <form onSubmit={lidarComSalvarCliente} className="space-y-4 text-xs text-left">
                <div>
                  <label className="block text-xs font-bold text-slate-500 mb-1 uppercase">Nome Completo *</label>
                  <input
                    type="text"
                    required
                    value={cliNome}
                    onChange={(e) => setCliNome(e.target.value)}
                    placeholder="Amanda Duarte"
                    className="w-full bg-slate-55 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 text-xs focus:ring-1 focus:ring-ebony focus:outline-none"
                  />
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-500 mb-1 uppercase">E-mail Corporativo</label>
                  <input
                    type="email"
                    value={cliEmail}
                    onChange={(e) => setCliEmail(e.target.value)}
                    placeholder="amanda@ateliemarket.com"
                    className="w-full bg-slate-55 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 text-xs focus:ring-1 focus:ring-ebony focus:outline-none"
                  />
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1 uppercase">CPF (Apenas Números)</label>
                    <input
                      type="text"
                      value={cliCpf}
                      onChange={(e) => lidarComEntradaNumerica(e.target.value, setCliCpf)}
                      placeholder="11122233344"
                      className="w-full bg-slate-55 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 text-xs font-mono focus:ring-1 focus:ring-ebony focus:outline-none"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 mb-1 uppercase">Telefone (Apenas Números)</label>
                    <input
                      type="text"
                      value={cliTelefone}
                      onChange={(e) => lidarComEntradaNumerica(e.target.value, setCliTelefone)}
                      placeholder="11912345678"
                      className="w-full bg-slate-55 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 text-xs font-mono focus:ring-1 focus:ring-ebony focus:outline-none"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-bold text-slate-550 mb-1 uppercase">Limite Padrão Crédito Outorgado (R$)</label>
                  <div className="relative">
                    <span className="absolute left-2.5 top-2.5 text-slate-400 font-bold font-mono">R$</span>
                    <input
                      type="number"
                      required
                      value={cliLimite}
                      onChange={(e) => setCliLimite(Math.max(0, Number(e.target.value) || 0))}
                      className="w-full bg-slate-50 border border-slate-105 rounded-lg pl-7 pr-2.5 py-2.5 text-slate-800 font-bold font-mono text-xs focus:ring-1 focus:ring-ebony focus:outline-none"
                    />
                  </div>
                </div>

                <div className="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                  <button
                    type="button"
                    onClick={() => setMostrarModalCliente(false)}
                    className="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-650 rounded-lg font-bold transition uppercase cursor-pointer"
                  >
                    Retroceder
                  </button>
                  <button
                    type="submit"
                    className="px-5 py-2.5 bg-ebony hover:bg-reseda text-white rounded-lg font-bold transition uppercase tracking-wider cursor-pointer"
                  >
                    Salvar Ficha
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* CONFIRMAÇÃO EXCLUIR CLIENTE */}
      <AnimatePresence>
        {mostrarConfirmacaoExcluirCliente && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMostrarConfirmacaoExcluirCliente(false)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden z-10 p-6 space-y-5 text-slate-850 text-left font-sans"
            >
              <div className="text-center space-y-2">
                <div className="mx-auto w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center animate-bounce">
                  <AlertTriangle className="text-[#a64b2a]" size={22} />
                </div>
                <h3 className="text-lg font-serif text-slate-950 font-semibold">Excluir Cadastro</h3>
                <p className="text-slate-500 text-xs">
                  Deseja realmente excluir permanentemente o cadastro de <strong className="text-slate-800">"{clienteAtivo.nome}"</strong>?
                </p>
              </div>

              <div className="flex items-center justify-center gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setMostrarConfirmacaoExcluirCliente(false)}
                  className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition text-xs font-semibold cursor-pointer"
                >
                  Retroceder
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

      {/* CONFIRMAÇÃO LIMPAR CARRINHO */}
      <AnimatePresence>
        {mostrarConfirmacaoLimparCarrinho && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMostrarConfirmacaoLimparCarrinho(false)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden z-10 p-6 space-y-5 text-slate-850 text-left font-sans"
            >
              <div className="text-center space-y-2">
                <div className="mx-auto w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center">
                  <Trash2 className="text-[#a64b2a]" size={22} />
                </div>
                <h3 className="text-lg font-serif text-slate-950 font-semibold">Limpar Venda</h3>
                <p className="text-slate-500 text-xs">
                  Deseja realmente limpar todos os itens e descontos da venda atual?
                </p>
              </div>

              <div className="flex items-center justify-center gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setMostrarConfirmacaoLimparCarrinho(false)}
                  className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition text-xs font-semibold cursor-pointer"
                >
                  Continuar
                </button>
                <button
                  type="button"
                  onClick={confirmarLimparCarrinho}
                  className="w-full py-2.5 bg-[#a64b2a] hover:bg-[#8b2611] text-white rounded-xl font-bold transition text-xs uppercase tracking-wider cursor-pointer"
                >
                  Sim, Limpar
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

    </div>
  );
}
