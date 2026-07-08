-- --------------------------------------------------------
-- Banco de Dados: elegancia_premium
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS elegancia_premium CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE elegancia_premium;

-- 1. TABELA DE USUÁRIOS (Controle RBAC)
CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario INT AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  nivel_acesso ENUM('ADMIN', 'CAIXA', 'ESTOQUE') NOT NULL DEFAULT 'CAIXA',
  status ENUM('ATIVO', 'INATIVO', 'CONVIDADO') NOT NULL DEFAULT 'ATIVO',
  perm_dashboard TINYINT(1) NOT NULL DEFAULT 1,
  perm_caixa TINYINT(1) NOT NULL DEFAULT 1,
  perm_estoque TINYINT(1) NOT NULL DEFAULT 1,
  perm_financeiro TINYINT(1) NOT NULL DEFAULT 1,
  perm_crm TINYINT(1) NOT NULL DEFAULT 1,
  perm_kanban TINYINT(1) NOT NULL DEFAULT 1,
  perm_relatorios TINYINT(1) NOT NULL DEFAULT 1,
  perm_equipe TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT PK_usuarios PRIMARY KEY (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABELA DE PRODUTOS (Catálogo de Estoque)
CREATE TABLE IF NOT EXISTS produtos (
  id_produto INT AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  descricao TEXT NULL,
  categoria VARCHAR(100) NULL,
  preco_custo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  preco DECIMAL(10,2) NOT NULL,
  quantidade INT NOT NULL DEFAULT 0,
  estoque_minimo INT NOT NULL DEFAULT 0,
  imagem_url VARCHAR(255) NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT PK_produtos PRIMARY KEY (id_produto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABELA DE CLIENTES (CRM)
CREATE TABLE IF NOT EXISTS clientes (
  id_cliente INT AUTO_INCREMENT,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(100) NULL,
  telefone VARCHAR(20) NULL,
  limite_credito DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  saldo_devedor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT PK_clientes PRIMARY KEY (id_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABELA DE VENDAS
CREATE TABLE IF NOT EXISTS vendas (
  id_venda INT AUTO_INCREMENT,
  id_cliente INT NULL,
  id_usuario INT NOT NULL,
  data_venda DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  subtotal DECIMAL(10,2) NOT NULL,
  imposto DECIMAL(10,2) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  forma_pagamento ENUM('DEBITO', 'CREDITO_LOJA', 'PIX', 'CARTAO') NOT NULL,
  status ENUM('PENDENTE', 'CONCLUIDA') NOT NULL DEFAULT 'CONCLUIDA',
  CONSTRAINT PK_vendas PRIMARY KEY (id_venda),
  CONSTRAINT FK_vendas_cliente FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE SET NULL,
  CONSTRAINT FK_vendas_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABELA DE ITENS DA VENDA
CREATE TABLE IF NOT EXISTS itens_venda (
  id_item INT AUTO_INCREMENT,
  id_venda INT NOT NULL,
  id_produto INT NOT NULL,
  quantidade INT NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL,
  CONSTRAINT PK_itens_venda PRIMARY KEY (id_item),
  CONSTRAINT FK_itens_venda_venda FOREIGN KEY (id_venda) REFERENCES vendas(id_venda) ON DELETE CASCADE,
  CONSTRAINT FK_itens_venda_produto FOREIGN KEY (id_produto) REFERENCES produtos(id_produto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABELA DE TRANSAÇÕES FINANCEIRAS (Fluxo de Caixa)
CREATE TABLE IF NOT EXISTS transacoes (
  id_transacao INT AUTO_INCREMENT,
  descricao VARCHAR(255) NOT NULL,
  tipo ENUM('RECEITA', 'DESPESA') NOT NULL,
  origem ENUM('VENDA', 'MANUAL') NOT NULL DEFAULT 'MANUAL',
  id_venda INT NULL,
  valor DECIMAL(10,2) NOT NULL,
  data_transacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('PENDENTE', 'CONCLUIDO', 'SAIDA') NOT NULL DEFAULT 'CONCLUIDO',
  CONSTRAINT PK_transacoes PRIMARY KEY (id_transacao),
  CONSTRAINT FK_transacoes_venda FOREIGN KEY (id_venda) REFERENCES vendas(id_venda) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABELA DE LOGS DE AUDITORIA (Imutável)
CREATE TABLE IF NOT EXISTS logs_auditoria (
  id_log INT AUTO_INCREMENT,
  data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  tipo_acao VARCHAR(50) NOT NULL,
  descricao TEXT NOT NULL,
  usuario VARCHAR(150) NOT NULL, -- "Nome (Perfil)"
  valor VARCHAR(50) NOT NULL DEFAULT 'N/A', -- Valor monetário ou 'N/A'
  status VARCHAR(50) NOT NULL DEFAULT 'SUCESSO',
  CONSTRAINT PK_logs_auditoria PRIMARY KEY (id_log)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TABELA DE COLUNAS DO KANBAN
CREATE TABLE IF NOT EXISTS kanban_colunas (
  id_coluna INT AUTO_INCREMENT,
  titulo VARCHAR(50) NOT NULL,
  ordem INT NOT NULL DEFAULT 0,
  CONSTRAINT PK_kanban_colunas PRIMARY KEY (id_coluna)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. TABELA DE TAREFAS DO KANBAN
CREATE TABLE IF NOT EXISTS kanban_tarefas (
  id_tarefa INT AUTO_INCREMENT,
  titulo VARCHAR(100) NOT NULL,
  descricao TEXT NULL,
  data_vencimento DATE NOT NULL,
  id_responsavel INT NOT NULL,
  prioridade ENUM('BAIXA', 'MEDIA', 'ALTA', 'URGENTE') NOT NULL DEFAULT 'MEDIA',
  id_coluna INT NOT NULL,
  concluida TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT PK_kanban_tarefas PRIMARY KEY (id_tarefa),
  CONSTRAINT FK_kanban_tarefas_responsavel FOREIGN KEY (id_responsavel) REFERENCES usuarios(id_usuario),
  CONSTRAINT FK_kanban_tarefas_coluna FOREIGN KEY (id_coluna) REFERENCES kanban_colunas(id_coluna) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. TABELA DE CONVITES DE EQUIPE
CREATE TABLE IF NOT EXISTS convites_equipe (
  id_convite INT AUTO_INCREMENT,
  email VARCHAR(100) NOT NULL UNIQUE,
  perfil ENUM('ADMIN', 'CAIXA', 'ESTOQUE') NOT NULL DEFAULT 'CAIXA',
  token VARCHAR(100) NOT NULL UNIQUE,
  status ENUM('PENDENTE', 'ACEITO') NOT NULL DEFAULT 'PENDENTE',
  data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT PK_convites_equipe PRIMARY KEY (id_convite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices de Otimização
CREATE INDEX idx_produtos_nome ON produtos(nome);
CREATE INDEX idx_clientes_nome ON clientes(nome);
CREATE INDEX idx_transacoes_data ON transacoes(data_transacao);
CREATE INDEX idx_logs_data ON logs_auditoria(data_hora);
