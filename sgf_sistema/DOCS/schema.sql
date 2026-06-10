-- =============================================
-- SGF — Script de Modelagem do Banco de Dados
-- SGBD: MySQL 8.x | Charset: utf8mb4
-- =============================================

CREATE DATABASE IF NOT EXISTS sgf_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sgf_db;

-- 1. TABELA DE USUÁRIOS (Controle RBAC)
CREATE TABLE IF NOT EXISTS usuarios (
    id_usuario   INT AUTO_INCREMENT,
    matricula    VARCHAR(20)  NOT NULL UNIQUE,
    nome         VARCHAR(100) NOT NULL,
    email        VARCHAR(100) NOT NULL UNIQUE,
    senha_hash   VARCHAR(255) NOT NULL,
    nivel_acesso ENUM('ADMIN','FERRAMENTEIRO','OPERADOR') NOT NULL,
    CONSTRAINT PK_usuarios PRIMARY KEY (id_usuario)
);

-- 2. TABELA DE FERRAMENTAS (Catálogo Patrimonial)
CREATE TABLE IF NOT EXISTS ferramentas (
    id_ferramenta   INT AUTO_INCREMENT,
    codigo_tag      VARCHAR(50)  NOT NULL UNIQUE,
    nome            VARCHAR(100) NOT NULL,
    categoria       VARCHAR(50)  NOT NULL,
    localizacao     VARCHAR(50)  NOT NULL,
    status_atual    ENUM('DISPONIVEL','EMPRESTADA','MANUTENCAO','BAIXADA') DEFAULT 'DISPONIVEL',
    vida_util_ciclos INT         NOT NULL,
    ciclos_atuais   INT          DEFAULT 0,
    CONSTRAINT PK_ferramentas PRIMARY KEY (id_ferramenta)
);

-- 3. TABELA DE EMPRÉSTIMOS (Movimentação e Precisão Temporal)
CREATE TABLE IF NOT EXISTS emprestimos (
    id_emprestimo      INT AUTO_INCREMENT,
    id_ferramenta      INT  NOT NULL,
    id_usuario         INT  NOT NULL,
    data_hora_saida    DATETIME NOT NULL,
    data_hora_previsao DATETIME NOT NULL,
    data_hora_devolucao DATETIME NULL,
    status_emprestimo  ENUM('ATIVO','DEVOLVIDO','ATRASADO') DEFAULT 'ATIVO',
    observacoes        TEXT,
    CONSTRAINT PK_emprestimos  PRIMARY KEY (id_emprestimo),
    CONSTRAINT FK_emp_ferramenta FOREIGN KEY (id_ferramenta) REFERENCES ferramentas(id_ferramenta),
    CONSTRAINT FK_emp_usuario    FOREIGN KEY (id_usuario)    REFERENCES usuarios(id_usuario)
);

-- Índices de otimização para alta performance do Dashboard
CREATE INDEX idx_ferramentas_status  ON ferramentas(status_atual);
CREATE INDEX idx_emprestimos_status  ON emprestimos(status_emprestimo);
