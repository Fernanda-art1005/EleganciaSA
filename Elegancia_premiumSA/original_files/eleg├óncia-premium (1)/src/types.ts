export interface Usuario {
  id: number;
  usuario: string;
  nome: string;
  cargo: 'gerente' | 'vendedor' | 'estoquista';
}

export interface Produto {
  id: number;
  nome: string;
  preco: number;
  estoque: number;
  imagemUrl: string; // Imagens de roupas no estoque
  categoria?: string;
  tamanho?: string;
}

export interface Cliente {
  id: number;
  nome: string;
  email: string;
  telefone: string;
  cpf: string;
  limiteCredito: number;
  saldoDevedor: number;
  status: 'ativo' | 'inadimplente' | 'bloqueado';
}

export interface ItemCarrinho {
  id: number;
  nome: string;
  qtd: number;
  preco: number;
  total: number;
  imagemUrl: string;
}

export interface Venda {
  id: number;
  clienteNome: string;
  total: number;
  desconto: number;
  imposto: number; // For RF-CX-004 compliance
  dataCriacao: string;
  formaPagamento: 'debito' | 'credito_loja' | 'pix' | 'cartao';
  status: 'pendente' | 'concluido';
  itens: {
    idProduto: number;
    nomeProduto: string;
    quantidade: number;
    precoUnitario: number;
  }[];
}

export interface Transacao {
  id: number;
  nome: string;
  data: string;
  tipo: 'receita_venda' | 'receita_manual' | 'despesa';
  valor: number;
  status: 'pendente' | 'concluido' | 'saida';
  vendaId?: number;
}

export interface Tarefa {
  id: number;
  titulo: string;
  descricao: string;
  dataVencimento: string;
  responsavel: string;
  prioridade: 'baixa' | 'media' | 'alta' | 'urgente';
  funil: string; // Coluna/funil onde se encontra
}

export interface MembroEquipe {
  id: number;
  nome: string;
  email: string;
  cargo: 'gerente' | 'vendedor' | 'estoquista';
  status: 'ativo' | 'convite_pendente';
}

export interface RegistroAuditoria {
  id: number;
  data: string;
  tipoAcao: string;
  descricao: string;
  usuario: string; // e.g. "Maria Silva (Gerente)" ou "Sistema"
  valor: number | 'N/A';
  status: string;
}

export interface PermissoesCargo {
  dashboard: boolean;
  estoque: boolean;
  caixa: boolean;
  financeiro: boolean;
  relatorios: boolean;
  tarefas: boolean;
  clientes: boolean;
  equipe: boolean;
}
