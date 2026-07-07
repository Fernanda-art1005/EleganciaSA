import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { 
  Lock, 
  User, 
  LogOut, 
  DollarSign, 
  TrendingUp, 
  Package, 
  ShoppingCart, 
  FileText, 
  CheckCircle,
  Menu,
  X,
  Sparkles,
  Users,
  ShieldCheck,
  Briefcase
} from 'lucide-react';

import { 
  Usuario, 
  Produto, 
  Cliente, 
  ItemCarrinho, 
  Venda, 
  Transacao, 
  Tarefa, 
  MembroEquipe, 
  RegistroAuditoria, 
  PermissoesCargo 
} from './types';

import { 
  PRODUTOS_INICIAIS, 
  CLIENTES_INICIAIS, 
  VENDAS_INICIAIS,
  TRANSCOES_INICIAIS,
  TAREFAS_INICIAIS,
  MEMBROS_INICIAIS,
  AUDITORIA_INICIAL,
  PERMISSOES_PADRAO
} from './data';

// Componentes do Sistema Elegância Premium
import Dashboard from './components/Dashboard';
import Inventory from './components/Inventory';
import PDV from './components/PDV';
import ClientsCRM from './components/ClientsCRM';
import FinanceFlow from './components/FinanceFlow';
import TasksKanban from './components/TasksKanban';
import Reports from './components/Reports';
import TeamManagement from './components/TeamManagement';

// Helper para ler o localStorage com segurança contra JSON corrompido
function safeJSONParse<T>(key: string, fallback: T): T {
  try {
    const salvo = localStorage.getItem(key);
    return salvo ? JSON.parse(salvo) : fallback;
  } catch (error) {
    console.warn(`[Elegância Premium] Erro ao deserializar ${key} do localStorage. Usando padrão.`, error);
    return fallback;
  }
}

export default function App() {
  
  // --- ESTADOS LOGÍSTICOS E SINC CARDONA (LocalStorage) ---
  const [produtos, setProdutos] = useState<Produto[]>(() => safeJSONParse('elegancia_produtos', PRODUTOS_INICIAIS));
  const [clientes, setClientes] = useState<Cliente[]>(() => safeJSONParse('elegancia_clientes', CLIENTES_INICIAIS));
  const [vendas, setVendas] = useState<Venda[]>(() => safeJSONParse('elegancia_vendas', VENDAS_INICIAIS));
  const [transacoes, setTransacoes] = useState<Transacao[]>(() => safeJSONParse('elegancia_transacoes', TRANSCOES_INICIAIS));
  const [tarefas, setTarefas] = useState<Tarefa[]>(() => safeJSONParse('elegancia_tarefas', TAREFAS_INICIAIS));
  const [membros, setMembros] = useState<MembroEquipe[]>(() => safeJSONParse('elegancia_membros', MEMBROS_INICIAIS));
  const [auditoria, setAuditoria] = useState<RegistroAuditoria[]>(() => safeJSONParse('elegancia_auditoria', AUDITORIA_INICIAL));
  const [permissoes, setPermissoes] = useState<Record<'gerente' | 'vendedor' | 'estoquista', PermissoesCargo>>(() => safeJSONParse('elegancia_permissoes', PERMISSOES_PADRAO));

  // --- ESTADO DA SESSÃO ---
  const [usuarioLogado, setUsuarioLogado] = useState<Usuario | null>(() => safeJSONParse<Usuario | null>('elegancia_usuario', null));

  // Credenciais Login
  const [loginUsuario, setLoginUsuario] = useState('');
  const [loginSenha, setLoginSenha] = useState('');
  const [erroLogin, setErroLogin] = useState('');

  // Tab Menu Ativo
  const [menuAtivo, setMenuAtivo] = useState<string>('dashboard');
  const [mobileSidebarAberta, setMobileSidebarAberta] = useState(false);

  // --- ESCREVER EM LOCALSTORAGE (Sincronização reativa ativa) ---
  useEffect(() => {
    localStorage.setItem('elegancia_produtos', JSON.stringify(produtos));
  }, [produtos]);

  useEffect(() => {
    localStorage.setItem('elegancia_clientes', JSON.stringify(clientes));
  }, [clientes]);

  useEffect(() => {
    localStorage.setItem('elegancia_vendas', JSON.stringify(vendas));
  }, [vendas]);

  useEffect(() => {
    localStorage.setItem('elegancia_transacoes', JSON.stringify(transacoes));
  }, [transacoes]);

  useEffect(() => {
    localStorage.setItem('elegancia_tarefas', JSON.stringify(tarefas));
  }, [tarefas]);

  useEffect(() => {
    localStorage.setItem('elegancia_membros', JSON.stringify(membros));
  }, [membros]);

  useEffect(() => {
    localStorage.setItem('elegancia_auditoria', JSON.stringify(auditoria));
  }, [auditoria]);

  useEffect(() => {
    localStorage.setItem('elegancia_permissoes', JSON.stringify(permissoes));
  }, [permissoes]);

  // Sincronizar sessão do usuário ativo
  useEffect(() => {
    if (usuarioLogado) {
      localStorage.setItem('elegancia_usuario', JSON.stringify(usuarioLogado));
      
      // Auto-redirecionamento com base nas permissões iniciais ao logar
      const cargoPerms = permissoes[usuarioLogado.cargo];
      if (cargoPerms) {
        if (cargoPerms.dashboard) setMenuAtivo('dashboard');
        else if (cargoPerms.caixa) setMenuAtivo('caixa');
        else if (cargoPerms.estoque) setMenuAtivo('estoque');
        else {
          const primeiraLegal = Object.keys(cargoPerms).find(k => cargoPerms[k as keyof PermissoesCargo]);
          if (primeiraLegal) setMenuAtivo(primeiraLegal);
        }
      }
    } else {
      localStorage.removeItem('elegancia_usuario');
    }
  }, [usuarioLogado]);

  // Se modificadas permissoes em tempo de execucao, certifica que a aba atual do usuario e valida
  useEffect(() => {
    if (usuarioLogado) {
      const cargoPerms = permissoes[usuarioLogado.cargo];
      const isTabAtivaValida = cargoPerms && cargoPerms[menuAtivo as keyof PermissoesCargo];
      
      if (!isTabAtivaValida && cargoPerms) {
        // Encontra qualquer tab liberada para não deixar usuário em tela em branco
        const primeiraLiberada = Object.keys(cargoPerms).find(k => cargoPerms[k as keyof PermissoesCargo]);
        if (primeiraLiberada) {
          setMenuAtivo(primeiraLiberada);
        }
      }
    }
  }, [permissoes, usuarioLogado]);

  // Autenticação correspondente ao script original (senha padrão '123')
  const lidarComLogin = (e: React.FormEvent) => {
    e.preventDefault();
    setErroLogin('');

    if (!loginUsuario.trim() || !loginSenha.trim()) {
      setErroLogin('Por favor, preencha todos os campos do terminal.');
      return;
    }

    // Procura na equipe cadastrada (membros ativos no sistema) ou na lista original estática
    const nomeProcurado = loginUsuario.toLowerCase().trim();
    const membroCadastrado = membros.find(m => 
      m.nome.toLowerCase() === nomeProcurado || 
      m.nome.toLowerCase().split(' ')[0] === nomeProcurado ||
      m.email.split('@')[0] === nomeProcurado
    );
    
    if (membroCadastrado) {
      // Bloquear se o status for especificamente inativo/bloqueado, mas se for convite_pendente ou qualquer outro, permitimos e ativamos
      if (loginSenha === '123') {
        if (membroCadastrado.status !== 'ativo') {
          // Ativa o membro automaticamente ao acessar com a senha padrão pela primeira vez
          setMembros(prev => prev.map(me => me.id === membroCadastrado.id ? { ...me, status: 'ativo' as const } : me));
        }
        setUsuarioLogado({
          id: membroCadastrado.id,
          usuario: membroCadastrado.nome.toLowerCase().replace(/\s/g, ''),
          nome: `${membroCadastrado.nome} (${membroCadastrado.cargo})`,
          cargo: membroCadastrado.cargo
        });
        setLoginUsuario('');
        setLoginSenha('');
      } else {
        setErroLogin('Senha incorreta.');
      }
      return;
    }

    // Fallback para usuários padrão de teste se não existia no localStorage
    const usuarioFicticio = [
      { id: 1, usuario: "maria", nome: "Maria Silva (Gerente)", cargo: "gerente" as const },
      { id: 2, usuario: "carlos", nome: "Carlos Souza (Vendedor)", cargo: "vendedor" as const },
      { id: 3, usuario: "patricia", nome: "Patricia Lima (Estoque)", cargo: "estoquista" as const }
    ].find(u => u.usuario === nomeProcurado);

    if (usuarioFicticio && loginSenha === '123') {
      setUsuarioLogado(usuarioFicticio);
      setLoginUsuario('');
      setLoginSenha('');
    } else {
      setErroLogin('Colaborador ou Senha informados são inválidos comercialmente.');
    }
  };

  const lidarComLogout = () => {
    setUsuarioLogado(null);
  };

  // --- TRANSVERSAL: PDV CONFIRMAR VENDA ---
  const registrarVenda = (
    clienteNome: string,
    itensComp: ItemCarrinho[],
    totalVenda: number,
    descontoVenda: number,
    imposto: number,
    formaPagamento: 'debito' | 'credito_loja' | 'pix' | 'cartao',
    clienteId?: number
  ) => {
    const novoIdVenda = vendas.length > 0 ? Math.max(...vendas.map(v => v.id)) + 1 : 1;
    
    // 1. Atualizar e decrescer estoque dos produtos no ateliê
    setProdutos(prevProdutos => 
      prevProdutos.map(prod => {
        const itemCarrinho = itensComp.find(item => item.id === prod.id);
        if (itemCarrinho) {
          return {
            ...prod,
            estoque: Math.max(0, prod.estoque - itemCarrinho.qtd)
          };
        }
        return prod;
      })
    );

    // 2. Se o pagamento for crédito da loja, atualiza saldo devedor do cliente
    if (formaPagamento === 'credito_loja' && clienteId && clienteId !== 1) {
      setClientes(prevClientes => 
        prevClientes.map(c => c.id === clienteId 
          ? { ...c, saldoDevedor: c.saldoDevedor + totalVenda }
          : c
        )
      );
    }

    // 3. Registrar a Venda
    const novaVenda: Venda = {
      id: novoIdVenda,
      clienteNome,
      total: totalVenda,
      desconto: descontoVenda,
      imposto,
      dataCriacao: new Date().toISOString(),
      formaPagamento,
      status: formaPagamento === 'credito_loja' ? 'pendente' : 'concluido',
      itens: itensComp.map(item => ({
        idProduto: item.id,
        nomeProduto: item.nome,
        quantidade: item.qtd,
        precoUnitario: item.preco
      }))
    };
    setVendas([novaVenda, ...vendas]);

    // 4. Lança transação automática no Livro Financeiro (receita_venda)
    const novaTransacao: Transacao = {
      id: transacoes.length > 0 ? Math.max(...transacoes.map(t => t.id)) + 1 : 1,
      nome: `Venda registrada #${novoIdVenda} - ${clienteNome}`,
      data: new Date().toISOString(),
      tipo: 'receita_venda',
      valor: totalVenda,
      status: formaPagamento === 'credito_loja' ? 'pendente' : 'concluido',
      vendaId: novoIdVenda
    };
    setTransacoes(prev => [novaTransacao, ...prev]);

    // 5. Gera lançamento auditável imutável na Auditoria Histórica
    const dStr = itensComp.map(it => `${it.qtd}x ${it.nome}`).join(', ');
    const novoAuditoriaLog: RegistroAuditoria = {
      id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
      data: new Date().toISOString(),
      tipoAcao: 'Venda concluída',
      descricao: `Faturamento bem-sucedido de ${itensComp.length} itens: (${dStr}) Pago via ${formaPagamento}.`,
      usuario: usuarioLogado ? usuarioLogado.nome : 'Ateliê Caixa',
      valor: totalVenda,
      status: 'Concluído'
    };
    setAuditoria(prev => [novoAuditoriaLog, ...prev]);
  };

  // --- TRANSVERSAL: CANCELAR OU EXCLUIR VENDA ---
  const excluirVenda = (id: number) => {
    const vendaRef = vendas.find(v => v.id === id);
    if (!vendaRef) return;

    // 1. Devolver estoque de roupas para a loja
    setProdutos(prevProdutos => 
      prevProdutos.map(prod => {
        const itemVendido = vendaRef.itens.find(it => it.idProduto === prod.id);
        if (itemVendido) {
          return {
            ...prod,
            estoque: prod.estoque + itemVendido.quantidade
          };
        }
        return prod;
      })
    );

    // 2. Se for débito do fiador (crédito loja), reverte e abate do seu saldo devedor
    if (vendaRef.formaPagamento === 'credito_loja') {
      setClientes(prevClientes => 
        prevClientes.map(c => c.nome === vendaRef.clienteNome 
          ? { ...c, saldoDevedor: Math.max(0, c.saldoDevedor - vendaRef.total) }
          : c
        )
      );
    }

    // 3. Excluir nota da venda
    setVendas(vendas.filter(v => v.id !== id));

    // 4. Excluir do caixa/financeiro
    setTransacoes(prev => prev.filter(t => t.vendaId !== id));

    // 5. Adicionar log de Auditoria do Cancelamento
    const novoAuditoriaLog: RegistroAuditoria = {
      id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
      data: new Date().toISOString(),
      tipoAcao: 'Cancelamento comercial',
      descricao: `Cancelamento operacional total da venda #${id}. O total de R$ ${vendaRef.total.toFixed(2)} foi estornado e os estoques devolvidos.`,
      usuario: usuarioLogado ? usuarioLogado.nome : 'Gerência',
      valor: vendaRef.total,
      status: 'Concluído'
    };
    setAuditoria(prev => [novoAuditoriaLog, ...prev]);
  };

  // --- TRANSVERSAL: RECEBIMENTO FINANCEIRO DE FIADORES EM CRM ---
  const receberPagamentoCliente = (clienteId: number, valorPago: number) => {
    const cli = clientes.find(c => c.id === clienteId);
    if (!cli) return;

    // 1. Abate saldo devedor do cliente
    const novoSaldo = Math.max(0, cli.saldoDevedor - valorPago);
    setClientes(prev => prev.map(c => c.id === clienteId ? { ...c, saldoDevedor: novoSaldo } : c));

    // 1.1 Se quitou ou zerou tudo, podemos atualizar status de vendas e transações desse cliente para 'concluido'
    if (novoSaldo === 0) {
      setVendas(vList => vList.map(v => v.clienteNome === cli.nome && v.status === 'pendente' ? { ...v, status: 'concluido' } : v));
      setTransacoes(tList => tList.map(t => t.nome.includes(cli.nome) && t.status === 'pendente' ? { ...t, status: 'concluido' } : t));
    }

    // 2. Lança recebimento manual como receita no livro caixa
    const novaTransacao: Transacao = {
      id: transacoes.length > 0 ? Math.max(...transacoes.map(t => t.id)) + 1 : 1,
      nome: `Amortização Débito - ${cli.nome}`,
      data: new Date().toISOString(),
      tipo: 'receita_manual',
      valor: valorPago,
      status: 'concluido'
    };
    setTransacoes(prev => [novaTransacao, ...prev]);

    // 3. Lança log auditado
    const novoAuditoriaLog: RegistroAuditoria = {
      id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
      data: new Date().toISOString(),
      tipoAcao: 'Liquidação de conta',
      descricao: `Cobrança resolvida. Recebimento de R$ ${valorPago.toFixed(2)} quitado por ${cli.nome}. Saldo devedor residual: R$ ${novoSaldo.toFixed(2)}.`,
      usuario: usuarioLogado ? usuarioLogado.nome : 'Administrador CRM',
      valor: valorPago,
      status: 'Concluído'
    };
    setAuditoria(prev => [novoAuditoriaLog, ...prev]);
  };

  // --- OPERAÇÕES CAIXA / LIVRO TRANSAÇÕES (Aba Financeiro manual) ---
  const adicionarTransacao = (t: Omit<Transacao, 'id'>) => {
    const novoId = transacoes.length > 0 ? Math.max(...transacoes.map(tr => tr.id)) + 1 : 1;
    const item = { id: novoId, ...t };
    setTransacoes([item as any, ...transacoes]);

    // Auditoria
    const novoAuditoriaLog: RegistroAuditoria = {
      id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
      data: new Date().toISOString(),
      tipoAcao: t.tipo === 'despesa' ? 'Entrada nova de despesa' : 'Aporte de caixa manual',
      descricao: `Lançamento manual no financeiro: "${t.nome}" no valor de R$ ${t.valor.toFixed(2)}.`,
      usuario: usuarioLogado ? usuarioLogado.nome : 'Gerente Financeiro',
      valor: t.valor,
      status: 'Concluído'
    };
    setAuditoria(prev => [novoAuditoriaLog, ...prev]);
  };

  const atualizarTransacao = (id: number, t: Omit<Transacao, 'id'>) => {
    setTransacoes(transacoes.map(tr => tr.id === id ? { id, ...t } as any : tr));

    // Auditoria
    const novoAuditoriaLog: RegistroAuditoria = {
      id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
      data: new Date().toISOString(),
      tipoAcao: 'Alteração financeira',
      descricao: `Transação manual id #${id} alterada para nome: "${t.nome}", valor R$ ${t.valor.toFixed(2)}.`,
      usuario: usuarioLogado ? usuarioLogado.nome : 'Gerente Financeiro',
      valor: t.valor,
      status: 'Concluído'
    };
    setAuditoria(prev => [novoAuditoriaLog, ...prev]);
  };

  const excluirTransacao = (id: number) => {
    const tRef = transacoes.find(tr => tr.id === id);
    setTransacoes(transacoes.filter(tr => tr.id !== id));

    // Auditoria
    if (tRef) {
      const novoAuditoriaLog: RegistroAuditoria = {
        id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
        data: new Date().toISOString(),
        tipoAcao: 'Exclusão financeira',
        descricao: `Lançamento avulso id #${id} ("${tRef.nome}") de R$ ${tRef.valor.toFixed(2)} excluído do caixa.`,
        usuario: usuarioLogado ? usuarioLogado.nome : 'Gerente Financeiro',
        valor: tRef.valor,
        status: 'Concluído'
      };
      setAuditoria(prev => [novoAuditoriaLog, ...prev]);
    }
  };

  // --- OPERAÇÕES KANBAN TAREFAS ---
  const adicionarTarefa = (t: Omit<Tarefa, 'id'>) => {
    const novoId = tarefas.length > 0 ? Math.max(...tarefas.map(tar => tar.id)) + 1 : 1;
    setTarefas([...tarefas, { id: novoId, ...t }]);

    // Log Auditoria
    const novoAuditoriaLog: RegistroAuditoria = {
      id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
      data: new Date().toISOString(),
      tipoAcao: 'Instância de tarefa',
      descricao: `Tarefa "${t.titulo}" agendada para ${t.responsavel}. Prioridade: ${t.prioridade}.`,
      usuario: usuarioLogado ? usuarioLogado.nome : 'Sistema',
      valor: 'N/A',
      status: 'N/A'
    };
    setAuditoria(prev => [novoAuditoriaLog, ...prev]);
  };

  const atualizarTarefa = (id: number, t: Omit<Tarefa, 'id'>) => {
    setTarefas(tarefas.map(tar => tar.id === id ? { id, ...t } : tar));
  };

  const excluirTarefa = (id: number) => {
    const tRef = tarefas.find(tar => tar.id === id);
    setTarefas(tarefas.filter(tar => tar.id !== id));

    // Log Auditoria
    if (tRef) {
      const novoAuditoriaLog: RegistroAuditoria = {
        id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
        data: new Date().toISOString(),
        tipoAcao: 'Remoção de tarefa',
        descricao: `Tarefa "${tRef.titulo}" agendada anteriormente foi removida do quadro.`,
        usuario: usuarioLogado ? usuarioLogado.nome : 'Sistema',
        valor: 'N/A',
        status: 'N/A'
      };
      setAuditoria(prev => [novoAuditoriaLog, ...prev]);
    }
  };

  // --- OPERAÇÕES CRM / CLIENTE ---
  // --- OPERAÇÕES PRODUTOS / ESTOQUE ---
  const adicionarProduto = (novoProd: Omit<Produto, 'id'>) => {
    const novoId = produtos.length > 0 ? Math.max(...produtos.map(p => p.id)) + 1 : 1;
    setProdutos([...produtos, { id: novoId, ...novoProd }]);

    // Log Auditoria
    const novoAuditoriaLog: RegistroAuditoria = {
      id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
      data: new Date().toISOString(),
      tipoAcao: 'Inclusão de produto',
      descricao: `Produto "${novoProd.nome}" (Tamanho: ${novoProd.tamanho}) adentrou o estoque físico com qtde de: ${novoProd.estoque} un.`,
      usuario: usuarioLogado ? usuarioLogado.nome : 'Estoquista',
      valor: 'N/A',
      status: 'N/A'
    };
    setAuditoria(prev => [novoAuditoriaLog, ...prev]);
  };

  const atualizarProduto = (id: number, prodEditado: Omit<Produto, 'id'>) => {
    setProdutos(produtos.map(p => p.id === id ? { id, ...prodEditado } : p));
  };

  const excluirProduto = (id: number) => {
    const pRef = produtos.find(p => p.id === id);
    setProdutos(produtos.filter(p => p.id !== id));

    // Log Auditoria
    if (pRef) {
      const novoAuditoriaLog: RegistroAuditoria = {
        id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
        data: new Date().toISOString(),
        tipoAcao: 'Remoção de produto',
        descricao: `Peça "${pRef.nome}" removida e eliminada do ateliê de estoque.`,
        usuario: usuarioLogado ? usuarioLogado.nome : 'Estoquista',
        valor: 'N/A',
        status: 'N/A'
      };
      setAuditoria(prev => [novoAuditoriaLog, ...prev]);
    }
  };

  const adicionarCliente = (novo: Omit<Cliente, 'id'>) => {
    const novoId = clientes.length > 0 ? Math.max(...clientes.map(c => c.id)) + 1 : 1;
    setClientes([...clientes, { id: novoId, ...novo }]);

    // Log Auditoria
    const novoAuditoriaLog: RegistroAuditoria = {
      id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
      data: new Date().toISOString(),
      tipoAcao: 'Cadastro de fiador',
      descricao: `Ficha cadastrada com sucesso para ${novo.nome}. CPF: ${novo.cpf || 'Não informado'}. Limite: R$ ${novo.limiteCredito.toFixed(2)}.`,
      usuario: usuarioLogado ? usuarioLogado.nome : 'CRM Caixa',
      valor: 'N/A',
      status: 'N/A'
    };
    setAuditoria(prev => [novoAuditoriaLog, ...prev]);
  };

  const atualizarCliente = (id: number, editado: Omit<Cliente, 'id'>) => {
    const antigoCliente = clientes.find(c => c.id === id);
    setClientes(clientes.map(c => c.id === id ? { id, ...editado } : c));

    if (antigoCliente && antigoCliente.nome !== editado.nome) {
      setVendas(prevVendas =>
        prevVendas.map(v => v.clienteNome === antigoCliente.nome ? { ...v, clienteNome: editado.nome } : v)
      );
    }
  };

  const excluirCliente = (id: number) => {
    const cRef = clientes.find(c => c.id === id);
    setClientes(clientes.filter(c => c.id !== id));

    // Log Auditoria
    if (cRef) {
      const novoAuditoriaLog: RegistroAuditoria = {
        id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
        data: new Date().toISOString(),
        tipoAcao: 'Revogação de fiador',
        descricao: `Ficha cadastral de ${cRef.nome} excluído do CRM de fidelização.`,
        usuario: usuarioLogado ? usuarioLogado.nome : 'CRM Caixa',
        valor: 'N/A',
        status: 'N/A'
      };
      setAuditoria(prev => [novoAuditoriaLog, ...prev]);
    }
  };

  // --- OPERAÇÕES EQUIPE / COLABORADORES ---
  const adicionarMembro = (m: Omit<MembroEquipe, 'id'>) => {
    const novoId = membros.length > 0 ? Math.max(...membros.map(me => me.id)) + 1 : 1;
    setMembros([...membros, { id: novoId, ...m }]);

    // Log Auditoria
    const novoAuditoriaLog: RegistroAuditoria = {
      id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
      data: new Date().toISOString(),
      tipoAcao: 'Convite enviado',
      descricao: `Convite de acesso administrativo emitido para ${m.nome} (${m.cargo}) no e-mail: ${m.email}.`,
      usuario: usuarioLogado ? usuarioLogado.nome : 'Gerente HR',
      valor: 'N/A',
      status: 'N/A'
    };
    setAuditoria(prev => [novoAuditoriaLog, ...prev]);
  };

  const atualizarMembro = (id: number, m: Omit<MembroEquipe, 'id'>) => {
    setMembros(membros.map(me => me.id === id ? { id, ...m } : me));
  };

  const excluirMembro = (id: number) => {
    const mRef = membros.find(me => me.id === id);
    setMembros(membros.filter(me => me.id !== id));

    // Log Auditoria
    if (mRef) {
      const novoAuditoriaLog: RegistroAuditoria = {
        id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
        data: new Date().toISOString(),
        tipoAcao: 'Exclusão colaborador',
        descricao: `Colaborador ${mRef.nome} (${mRef.cargo}) foi removido do quadro administrativo e seus privilégios revogados.`,
        usuario: usuarioLogado ? usuarioLogado.nome : 'Gerente HR',
        valor: 'N/A',
        status: 'N/A'
      };
      setAuditoria(prev => [novoAuditoriaLog, ...prev]);
    }
  };

  const atualizarPermissoes = (cargoAlvo: 'gerente' | 'vendedor' | 'estoquista', novaPerm: PermissoesCargo) => {
    setPermissoes(prev => ({
      ...prev,
      [cargoAlvo]: novaPerm
    }));

    // Log Auditoria
    const novoAuditoriaLog: RegistroAuditoria = {
      id: auditoria.length > 0 ? Math.max(...auditoria.map(a => a.id)) + 1 : 1,
      data: new Date().toISOString(),
      tipoAcao: 'Atualização privilégio',
      descricao: `Privilégios e visibilidade do perfil de (${cargoAlvo}) redefinidos pelo administrador.`,
      usuario: usuarioLogado ? usuarioLogado.nome : 'Gerente HR',
      valor: 'N/A',
      status: 'N/A'
    };
    setAuditoria(prev => [novoAuditoriaLog, ...prev]);
  };

  // --- FILTRAGEM DO SIDEBAR / ABAS DISPONÍVEIS POR RBAC (RF-EQ-003) ---
  const tabsDisponiveis = (() => {
    if (!usuarioLogado) return [];
    
    const cargoPerms = permissoes[usuarioLogado.cargo];
    if (!cargoPerms) {
      return [
        { id: 'caixa', nome: 'Caixa (PDV)', icone: <ShoppingCart size={18} /> }
      ];
    }

    const t = [
      { id: 'dashboard', nome: 'Dashboard', icone: <TrendingUp size={18} />, active: cargoPerms.dashboard },
      { id: 'estoque', nome: 'Estoque de Roupas', icone: <Package size={18} />, active: cargoPerms.estoque },
      { id: 'caixa', nome: 'Caixa (PDV)', icone: <ShoppingCart size={18} />, active: cargoPerms.caixa },
      { id: 'clientes', nome: 'Clientes (CRM)', icone: <Users size={18} />, active: cargoPerms.clientes },
      { id: 'financeiro', nome: 'Financeiro Caixa', icone: <DollarSign size={18} />, active: cargoPerms.financeiro },
      { id: 'tarefas', nome: 'Tarefas (Kanban)', icone: <Briefcase size={18} />, active: cargoPerms.tarefas },
      { id: 'relatorios', nome: 'Relatórios Fiscais', icone: <FileText size={18} />, active: cargoPerms.relatorios },
      { id: 'equipe', nome: 'Equipe e Funções', icone: <Lock size={18} />, active: cargoPerms.equipe }
    ];

    return t.filter(item => item.active);
  })();

  // --- RENDERIZADOR DA TELA ATIVA (Conectando todos os novos componentes) ---
  const renderizarTelaAtiva = () => {
    const podeEditar = usuarioLogado?.cargo === 'gerente';

    switch (menuAtivo) {
      case 'dashboard':
        return <Dashboard produtos={produtos} vendas={vendas} />;
      case 'estoque':
        return (
          <Inventory 
            produtos={produtos} 
            onAdicionar={adicionarProduto} 
            onAtualizar={atualizarProduto} 
            onExcluir={excluirProduto} 
            podeEditar={usuarioLogado?.cargo === 'gerente' || usuarioLogado?.cargo === 'estoquista'} 
          />
        );
      case 'caixa':
        return (
          <PDV
            produtos={produtos}
            clientes={clientes}
            onAdicionarCliente={adicionarCliente}
            onAtualizarCliente={atualizarCliente}
            onExcluirCliente={excluirCliente}
            onConfirmarVenda={registrarVenda}
          />
        );
      case 'clientes':
        return (
          <ClientsCRM
            clientes={clientes}
            vendas={vendas}
            onAdicionarCliente={adicionarCliente}
            onAtualizarCliente={atualizarCliente}
            onExcluirCliente={excluirCliente}
            onReceberPagamento={receberPagamentoCliente}
            podeEditar={usuarioLogado?.cargo === 'gerente' || usuarioLogado?.cargo === 'vendedor'}
          />
        );
      case 'financeiro':
        return (
          <FinanceFlow
            transacoes={transacoes}
            clientes={clientes}
            onAdicionarTransacao={adicionarTransacao}
            onAtualizarTransacao={atualizarTransacao}
            onExcluirTransacao={excluirTransacao}
            podeEditar={podeEditar}
          />
        );
      case 'tarefas':
        return (
          <TasksKanban
            tarefas={tarefas}
            membros={membros.filter(m => m.status === 'ativo')}
            onAdicionarTarefa={adicionarTarefa}
            onAtualizarTarefa={atualizarTarefa}
            onExcluirTarefa={excluirTarefa}
            podeEditar={usuarioLogado?.cargo === 'gerente' || usuarioLogado?.cargo === 'vendedor' || usuarioLogado?.cargo === 'estoquista'}
          />
        );
      case 'relatorios':
        return (
          <Reports 
            vendas={vendas} 
            produtos={produtos} 
            auditoria={auditoria}
            onExcluirVenda={excluirVenda} 
            podeEditar={podeEditar}
          />
        );
      case 'equipe':
        return (
          <TeamManagement
            membros={membros}
            usuarioLogado={usuarioLogado!}
            permissoes={permissoes}
            onAdicionarMembro={adicionarMembro}
            onAtualizarMembro={atualizarMembro}
            onExcluirMembro={excluirMembro}
            onAtualizarPermissoes={atualizarPermissoes}
            podeEditar={podeEditar}
          />
        );
      default:
        return <Dashboard produtos={produtos} vendas={vendas} />;
    }
  };

  // --- SEÇÃO 1: USUÁRIO DESLOGADO (TELA DE LOGIN PREMIUM) ---
  if (!usuarioLogado) {
    return (
      <div className="min-h-screen bg-bone flex flex-col items-center justify-center p-4 relative overflow-hidden font-sans">
        
        {/* Glow de fundo luxuoso em tom de verde sage suave */}
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-reseda/10 blur-[100px] rounded-full pointer-events-none" />

        <div className="w-full max-w-sm z-10 space-y-6">
          {/* Logo / Header */}
          <div className="text-center space-y-2 select-none">
            <h1 className="text-4xl text-ebony font-serif tracking-tight mt-2 font-medium">Elegância Premium</h1>
            <p className="text-reseda text-xs uppercase tracking-widest font-bold">Controle de Estoque</p>
          </div>

          {/* Form Card */}
          <motion.div 
            initial={{ opacity: 0, y: 15 }}
            animate={{ opacity: 1, y: 0 }}
            className="bg-white border border-dun/50 rounded-2xl p-8 shadow-xl relative text-left"
          >
            <form onSubmit={lidarComLogin} className="space-y-4 text-xs font-semibold">
              
              {/* Usuário Input */}
              <div className="space-y-1.5">
                <label className="block text-reseda uppercase tracking-wider">Usuário do Colaborador</label>
                <div className="relative">
                  <User className="absolute left-3.5 top-3.5 text-reseda/60" size={15} />
                  <input
                    type="text"
                    required
                    value={loginUsuario}
                    onChange={(e) => setLoginUsuario(e.target.value)}
                    placeholder="Digite seu usuario..."
                    className="w-full bg-bone/20 hover:bg-bone/35 border border-dun/40 text-ebony pl-10 pr-4 py-3 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-ebony transition placeholder:text-reseda/40"
                  />
                </div>
              </div>

              {/* Senha Input */}
              <div className="space-y-1.5">
                <label className="block text-reseda uppercase tracking-wider">Senha Secreta</label>
                <div className="relative">
                  <Lock className="absolute left-3.5 top-3.5 text-reseda/60" size={15} />
                  <input
                    type="password"
                    required
                    value={loginSenha}
                    onChange={(e) => setLoginSenha(e.target.value)}
                    placeholder="Senha padrão..."
                    className="w-full bg-bone/20 hover:bg-bone/35 border border-dun/40 text-ebony pl-10 pr-4 py-3 rounded-xl text-xs focus:outline-none focus:ring-1 focus:ring-ebony transition placeholder:text-reseda/40"
                  />
                </div>
              </div>

              {/* Erro de login se houver */}
              {erroLogin && (
                <motion.div 
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  className="bg-rose-50 border border-rose-200 text-rose-750 p-3 rounded-xl text-[11px] font-bold text-center mt-1"
                >
                  {erroLogin}
                </motion.div>
              )}

              {/* Submit */}
              <motion.button
                whileHover={{ scale: 1.01 }}
                whileTap={{ scale: 0.99 }}
                type="submit"
                className="w-full bg-ebony hover:bg-[#8f9779] text-white font-bold py-3.5 rounded-xl text-xs tracking-widest uppercase transition shadow-md shadow-ebony/10 mt-6 cursor-pointer"
              >
                Ingressar no Caixa
              </motion.button>
            </form>
          </motion.div>
        </div>
      </div>
    );
  }

  // --- SEÇÃO 2: USUÁRIO LOGADO (SISTEMA ADMINISTRATIVO DESIGN PREMIUM) ---
  return (
    <div className="min-h-screen bg-stone-55 flex flex-col md:flex-row font-sans">
      
      {/* 2.1 Sidebar Desktop (Esquerda) */}
      <aside className="hidden md:flex flex-col w-64 bg-ebony text-bone shrink-0 justify-between self-stretch border-r border-[#3a412e]">
        <div className="space-y-6 py-8 px-5">
          
          {/* Logo Brand */}
          <div className="text-left space-y-1 border-b border-reseda/20 pb-5 px-1 select-none">
            <h2 className="text-xl font-serif text-white tracking-tight">Elegância Premium</h2>
            <div className="flex items-center gap-1.5 text-[10px] text-sage font-bold uppercase tracking-wider">
              <span className="w-1.5 h-1.5 rounded-full bg-sage animate-ping" />
              <span>Unidade Física 01</span>
            </div>
          </div>

          {/* Dados do Operador */}
          <div className="bg-reseda/20 border border-reseda/30 p-4 rounded-xl text-left space-y-1">
            <span className="text-[10px] font-bold text-sage uppercase tracking-widest block">Operador Ativo</span>
            <span className="text-xs font-bold text-white block truncate">{usuarioLogado.nome}</span>
            <span className="inline-flex text-[9px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-sage/20 text-bone border border-sage/30">
              {usuarioLogado.cargo}
            </span>
          </div>

          {/* Navegação Dinâmica conforme cargos e privilégios */}
          <nav className="space-y-1 text-xs font-semibold uppercase tracking-wider">
            {tabsDisponiveis.map(tab => (
              <button
                key={tab.id}
                onClick={() => setMenuAtivo(tab.id)}
                className={`w-full flex items-center gap-3 px-3.5 py-3 rounded-xl text-left transition duration-150 cursor-pointer ${
                  menuAtivo === tab.id 
                    ? 'bg-reseda text-white font-bold shadow-xs' 
                    : 'text-dun hover:text-white hover:bg-reseda/20'
                }`}
              >
                {tab.icone}
                <span>{tab.nome}</span>
              </button>
            ))}
          </nav>
        </div>

        {/* Botão de Logout Rodapé */}
        <div className="p-5 border-t border-reseda/10">
          <button
            onClick={lidarComLogout}
            className="w-full flex items-center justify-center gap-2 bg-reseda/10 hover:bg-rose-950/20 hover:border-rose-900 border border-reseda/30 text-dun hover:text-rose-200 font-bold py-3 rounded-xl text-[10px] tracking-wider uppercase transition cursor-pointer"
          >
            <LogOut size={13} />
            <span>Sair do Caixa</span>
          </button>
        </div>
      </aside>

      {/* 2.2 Topbar Mobile */}
      <header className="md:hidden bg-ebony text-white p-4 flex items-center justify-between border-b border-reseda/30 select-none">
        <div className="text-left">
          <h2 className="font-serif text-lg">Elegância Premium</h2>
          <p className="text-[9px] text-sage tracking-wider font-semibold uppercase">{usuarioLogado.cargo}</p>
        </div>

        <button
          onClick={() => setMobileSidebarAberta(!mobileSidebarAberta)}
          className="p-1 px-2.5 bg-reseda/40 rounded-lg text-slate-200 border border-reseda/30"
        >
          {mobileSidebarAberta ? <X size={20} /> : <Menu size={20} />}
        </button>
      </header>

      {/* Menu Gaveta Mobile */}
      <AnimatePresence>
        {mobileSidebarAberta && (
          <motion.div
            initial={{ opacity: 0, height: 0 }}
            animate={{ opacity: 1, height: "auto" }}
            exit={{ opacity: 0, height: 0 }}
            className="md:hidden bg-ebony text-white border-b border-reseda/30 flex flex-col p-5 space-y-4 text-left"
          >
            <div className="text-xs text-dun pb-2 border-b border-reseda/20">
              Operador: <span className="font-bold text-white">{usuarioLogado.nome}</span>
            </div>

            <nav className="flex flex-col gap-1 text-xs font-bold uppercase tracking-wider">
              {tabsDisponiveis.map(tab => (
                <button
                  key={tab.id}
                  onClick={() => {
                    setMenuAtivo(tab.id);
                    setMobileSidebarAberta(false);
                  }}
                  className={`w-full flex items-center gap-3 px-4 py-3 rounded-lg text-left transition ${
                    menuAtivo === tab.id 
                      ? 'bg-reseda text-white font-semibold' 
                      : 'text-dun hover:text-white'
                  }`}
                >
                  {tab.icone}
                  <span>{tab.nome}</span>
                </button>
              ))}
            </nav>

            <button
               onClick={() => {
                 setMobileSidebarAberta(false);
                 lidarComLogout();
               }}
               className="w-full flex items-center justify-center gap-2 bg-reseda/20 border border-reseda/30 text-dun hover:text-white py-2.5 rounded-lg text-xs font-bold uppercase transition"
            >
               <LogOut size={13} />
               <span>Sair do Caixa</span>
            </button>
          </motion.div>
        )}
      </AnimatePresence>

      {/* 2.3 Área de Conteúdo Principal */}
      <main className="flex-1 p-6 md:p-8 max-w-7xl mx-auto w-full transition-all duration-300 overflow-x-hidden">
        <AnimatePresence mode="wait">
          <motion.div
            key={menuAtivo}
            initial={{ opacity: 0, y: 12 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -12 }}
            transition={{ duration: 0.15 }}
          >
            {renderizarTelaAtiva()}
          </motion.div>
        </AnimatePresence>
      </main>

    </div>
  );
}
