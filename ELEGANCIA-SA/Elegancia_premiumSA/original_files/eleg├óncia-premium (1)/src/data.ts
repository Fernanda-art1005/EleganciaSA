import { Produto, Cliente, Venda, Transacao, Tarefa, MembroEquipe, RegistroAuditoria, PermissoesCargo } from './types';

export const PRODUTOS_INICIAIS: Produto[] = [
  {
    id: 1,
    nome: "Camisa Social Slim Premium",
    preco: 159.90,
    estoque: 42,
    imagemUrl: "https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80",
    categoria: "Camisas",
    tamanho: "M"
  },
  {
    id: 2,
    nome: "Calça Alfaiataria Italiana",
    preco: 199.90,
    estoque: 28,
    imagemUrl: "https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=600&q=80",
    categoria: "Calças",
    tamanho: "42"
  },
  {
    id: 3,
    nome: "Vestido de Festa Ateliê Rose",
    preco: 450.00,
    estoque: 15,
    imagemUrl: "https://images.unsplash.com/photo-1566174053879-31528523f8ae?auto=format&fit=crop&w=600&q=80",
    categoria: "Vestidos",
    tamanho: "P"
  },
  {
    id: 4,
    nome: "Blazer Executivo Linho",
    preco: 299.90,
    estoque: 18,
    imagemUrl: "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?auto=format&fit=crop&w=600&q=80",
    categoria: "Casacos",
    tamanho: "G"
  },
  {
    id: 5,
    nome: "Sapato Oxford Masculino Couro",
    preco: 320.00,
    estoque: 8,
    imagemUrl: "https://images.unsplash.com/photo-1533867617858-e7b97e060509?auto=format&fit=crop&w=600&q=80",
    categoria: "Calçados",
    tamanho: "41"
  },
  {
    id: 6,
    nome: "Cinto de Couro Nobre Sela",
    preco: 89.90,
    estoque: 35,
    imagemUrl: "https://images.unsplash.com/photo-1624222247344-550fb8ecf7db?auto=format&fit=crop&w=600&q=80",
    categoria: "Acessórios",
    tamanho: "Único"
  },
  {
    id: 7,
    nome: "Saia Midi Plissada Coral",
    preco: 179.90,
    estoque: 22,
    imagemUrl: "https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?auto=format&fit=crop&w=600&q=80",
    categoria: "Saias",
    tamanho: "M"
  }
];

export const CLIENTES_INICIAIS: Cliente[] = [
  { 
    id: 1, 
    nome: "Consumidor Final", 
    email: "consumidor@elegancia.com", 
    telefone: "", 
    cpf: "",
    limiteCredito: 0,
    saldoDevedor: 0,
    status: 'ativo'
  },
  { 
    id: 2, 
    nome: "Alice Monteverde", 
    email: "alice.monte@gmail.com", 
    telefone: "11988887777", 
    cpf: "12345678901",
    limiteCredito: 1200.00,
    saldoDevedor: 359.80,
    status: 'ativo'
  },
  { 
    id: 3, 
    nome: "Roberto Guimarães", 
    email: "roberto.gui@hotmail.com", 
    telefone: "21977776666", 
    cpf: "98765432109",
    limiteCredito: 800.00,
    saldoDevedor: 0,
    status: 'ativo'
  },
  { 
    id: 4, 
    nome: "Eduarda Castanho", 
    email: "eduarda.cas@yahoo.com.br", 
    telefone: "31966665555", 
    cpf: "45678912344",
    limiteCredito: 1500.00,
    saldoDevedor: 450.00,
    status: 'inadimplente'
  }
];

export const VENDAS_INICIAIS: Venda[] = [
  {
    id: 1,
    clienteNome: "Alice Monteverde",
    total: 359.80,
    desconto: 40.00,
    imposto: 17.99,
    dataCriacao: "2026-06-02T10:15:00Z",
    formaPagamento: 'credito_loja',
    status: 'pendente',
    itens: [
      { idProduto: 1, nomeProduto: "Camisa Social Slim Premium", quantidade: 1, precoUnitario: 159.90 },
      { idProduto: 2, nomeProduto: "Calça Alfaiataria Italiana", quantidade: 1, precoUnitario: 199.90 }
    ]
  },
  {
    id: 2,
    clienteNome: "Roberto Guimarães",
    total: 320.00,
    desconto: 0.00,
    imposto: 16.00,
    dataCriacao: "2026-06-02T14:30:00Z",
    formaPagamento: 'pix',
    status: 'concluido',
    itens: [
      { idProduto: 5, nomeProduto: "Sapato Oxford Masculino Couro", quantidade: 1, precoUnitario: 320.00 }
    ]
  },
  {
    id: 3,
    clienteNome: "Consumidor Final",
    total: 539.90,
    desconto: 50.00,
    imposto: 29.50,
    dataCriacao: "2026-06-01T11:05:00Z",
    formaPagamento: 'cartao',
    status: 'concluido',
    itens: [
      { idProduto: 4, nomeProduto: "Blazer Executivo Linho", quantidade: 1, precoUnitario: 299.90 },
      { idProduto: 1, nomeProduto: "Camisa Social Slim Premium", quantidade: 1, precoUnitario: 159.90 },
      { idProduto: 6, nomeProduto: "Cinto de Couro Nobre Sela", quantidade: 1, precoUnitario: 89.90 }
    ]
  },
  {
    id: 4,
    clienteNome: "Eduarda Castanho",
    total: 450.00,
    desconto: 0.00,
    imposto: 22.50,
    dataCriacao: "2026-06-01T16:45:00Z",
    formaPagamento: 'credito_loja',
    status: 'pendente',
    itens: [
      { idProduto: 3, nomeProduto: "Vestido de Festa Ateliê Rose", quantidade: 1, precoUnitario: 450.00 }
    ]
  }
];

export const TRANSCOES_INICIAIS: Transacao[] = [
  {
    id: 1,
    nome: "Venda PDV - Alice Monteverde",
    data: "2026-06-02T10:15:00Z",
    tipo: 'receita_venda',
    valor: 359.80,
    status: 'pendente',
    vendaId: 1
  },
  {
    id: 2,
    nome: "Venda PDV - Roberto Guimarães",
    data: "2026-06-02T14:30:00Z",
    tipo: 'receita_venda',
    valor: 320.00,
    status: 'concluido',
    vendaId: 2
  },
  {
    id: 3,
    nome: "Venda PDV - Consumidor Final",
    data: "2026-06-01T11:05:00Z",
    tipo: 'receita_venda',
    valor: 539.90,
    status: 'concluido',
    vendaId: 3
  },
  {
    id: 4,
    nome: "Venda PDV - Eduarda Castanho",
    data: "2026-06-01T16:45:00Z",
    tipo: 'receita_venda',
    valor: 450.00,
    status: 'pendente',
    vendaId: 4
  },
  {
    id: 5,
    nome: "Aluguel da Loja Mensal",
    data: "2026-06-01T08:00:00Z",
    tipo: 'despesa',
    valor: 2500.00,
    status: 'saida'
  },
  {
    id: 6,
    nome: "Serviço de Marketing Digital",
    data: "2026-06-03T17:20:00Z",
    tipo: 'despesa',
    valor: 450.00,
    status: 'saida'
  },
  {
    id: 7,
    nome: "Reembolso Divulgação Estúdio",
    data: "2026-06-03T11:00:00Z",
    tipo: 'receita_manual',
    valor: 150.00,
    status: 'concluido'
  }
];

export const TAREFAS_INICIAIS: Tarefa[] = [
  {
    id: 1,
    titulo: "Contagem Geral de Inverno",
    descricao: "Fazer o balanço físico de casacos e blazers da coleção inverno.",
    dataVencimento: "2026-06-15",
    responsavel: "Patricia Lima (Estoque)",
    prioridade: "alta",
    funil: "A Fazer"
  },
  {
    id: 2,
    titulo: "Arrumar vitrine principal",
    descricao: "Substituir os manequins do hall de entrada com a nova coleção de sapatos e vestidos decorados.",
    dataVencimento: "2026-06-12",
    responsavel: "Carlos Souza (Vendedor)",
    prioridade: "media",
    funil: "Em Progresso"
  },
  {
    id: 3,
    titulo: "Treinamento PIX e Prazos",
    descricao: "Capacitar os novos atendentes sobre a modalidade de crédito da loja.",
    dataVencimento: "2026-06-05",
    responsavel: "Maria Silva (Gerente)",
    prioridade: "urgente",
    funil: "Concluído"
  }
];

export const MEMBROS_INICIAIS: MembroEquipe[] = [
  { id: 1, nome: "Maria Silva", email: "maria.gerente@elegancia.com", cargo: "gerente", status: "ativo" },
  { id: 2, nome: "Carlos Souza", email: "carlos.vendedor@elegancia.com", cargo: "vendedor", status: "ativo" },
  { id: 3, nome: "Patricia Lima", email: "patricia.estoque@elegancia.com", cargo: "estoquista", status: "ativo" },
  { id: 4, nome: "Juliana Mendes", email: "juliana.caixa@elegancia.com", cargo: "vendedor", status: "convite_pendente" }
];

export const AUDITORIA_INICIAL: RegistroAuditoria[] = [
  {
    id: 1,
    data: "2026-06-01T11:05:00Z",
    tipoAcao: "Venda concluída",
    descricao: "Venda de 3 peças por R$ 539.90 para Consumidor Final no dinheiro/cartão.",
    usuario: "Carlos Souza (Vendedor)",
    valor: 539.90,
    status: "concluído"
  },
  {
    id: 2,
    data: "2026-06-02T10:15:00Z",
    tipoAcao: "Abertura de crédito de loja",
    descricao: "Venda por crédito de 2 peças no valor de R$ 359.80 para Alice Monteverde.",
    usuario: "Carlos Souza (Vendedor)",
    valor: 359.80,
    status: "concluído"
  },
  {
    id: 3,
    data: "2026-06-03T10:00:00Z",
    tipoAcao: "Criação de produto",
    descricao: "Nova peça registrada: 'Saia Midi Plissada Coral' no valor de R$ 179.90.",
    usuario: "Maria Silva (Gerente)",
    valor: "N/A",
    status: "N/A"
  }
];

export const PERMISSOES_PADRAO: Record<'gerente' | 'vendedor' | 'estoquista', PermissoesCargo> = {
  gerente: {
    dashboard: true,
    estoque: true,
    caixa: true,
    financeiro: true,
    relatorios: true,
    tarefas: true,
    clientes: true,
    equipe: true
  },
  vendedor: {
    dashboard: true,
    estoque: false,
    caixa: true,
    financeiro: false,
    relatorios: false,
    tarefas: true,
    clientes: true,
    equipe: false
  },
  estoquista: {
    dashboard: true,
    estoque: true,
    caixa: false,
    financeiro: false,
    relatorios: false,
    tarefas: true,
    clientes: false,
    equipe: false
  }
};
