<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $conexao = null;
    private static string $db_type = 'mysql';

    public static function getConnection(): PDO {
        if (self::$conexao === null) {
            // Configurações padrão para o ambiente MySQL do XAMPP
            $host = "127.0.0.1";
            $dbname = "elegancia_premium";
            $user = "root";
            $pass = "";

            try {
                // Tenta conectar no MySQL (XAMPP local)
                self::$conexao = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT => 2
                    ]
                );
                self::$db_type = 'mysql';

                // Migrações auto-executáveis (self-healing)
                try {
                    self::$conexao->exec("ALTER TABLE produtos ADD COLUMN imagem_url VARCHAR(255) NULL");
                } catch (\Exception $e) {
                    // Ignora se coluna já existir
                }
                try {
                    self::$conexao->exec("ALTER TABLE produtos ADD COLUMN ativo TINYINT NOT NULL DEFAULT 1");
                } catch (\Exception $e) {
                    // Ignora se coluna já existir
                }
                try {
                    self::$conexao->exec("ALTER TABLE produtos ADD COLUMN descricao TEXT NULL");
                } catch (\Exception $e) {
                    // Ignora se coluna já existir
                }
                try {
                    self::$conexao->exec("ALTER TABLE kanban_tarefas ADD COLUMN concluida TINYINT(1) NOT NULL DEFAULT 0");
                } catch (\Exception $e) {
                    // Ignora se coluna já existir
                }
            } catch (PDOException $e) {
                // FALLBACK PARA SQLITE SE MYSQL FALHAR OU ESTIVER INDISPONÍVEL
                try {
                    $sqlitePath = __DIR__ . '/database.sqlite';
                    self::$conexao = new PDO("sqlite:$sqlitePath");
                    self::$conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$conexao->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$conexao->exec("PRAGMA foreign_keys = ON;");
                    self::$db_type = 'sqlite';

                    // Inicializa as tabelas se não existirem
                    self::initializeSQLiteSchema(self::$conexao);
                } catch (PDOException $sqliteEx) {
                    throw new PDOException("Erro ao conectar ao banco de dados (MySQL recusado, SQLite falhou): " . $sqliteEx->getMessage());
                }
            }
        }
        return self::$conexao;
    }

    public static function getDbType(): string {
        return self::$db_type;
    }

    private static function initializeSQLiteSchema(PDO $db): void {
        // 1. Tabela de Usuários
        $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
            id_usuario INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            senha_hash VARCHAR(255) NOT NULL,
            nivel_acesso VARCHAR(50) NOT NULL DEFAULT 'CAIXA',
            status VARCHAR(50) NOT NULL DEFAULT 'ATIVO',
            perm_dashboard TINYINT NOT NULL DEFAULT 1,
            perm_caixa TINYINT NOT NULL DEFAULT 1,
            perm_estoque TINYINT NOT NULL DEFAULT 1,
            perm_financeiro TINYINT NOT NULL DEFAULT 1,
            perm_crm TINYINT NOT NULL DEFAULT 1,
            perm_kanban TINYINT NOT NULL DEFAULT 1,
            perm_relatorios TINYINT NOT NULL DEFAULT 1,
            perm_equipe TINYINT NOT NULL DEFAULT 0
        )");

        // 2. Tabela de Produtos
        $db->exec("CREATE TABLE IF NOT EXISTS produtos (
            id_produto INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(100) NOT NULL,
            descricao TEXT NULL,
            categoria VARCHAR(100) NULL,
            preco_custo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            preco DECIMAL(10,2) NOT NULL,
            quantidade INT NOT NULL DEFAULT 0,
            estoque_minimo INT NOT NULL DEFAULT 0,
            imagem_url VARCHAR(255) NULL,
            ativo TINYINT NOT NULL DEFAULT 1
        )");

        // Garante que a coluna 'descricao' exista em instalações antigas
        try {
            $db->exec("ALTER TABLE produtos ADD COLUMN descricao TEXT NULL");
        } catch (\PDOException $e) {
            // Ignora se a coluna já existir
        }

        // 3. Tabela de Clientes
        $db->exec("CREATE TABLE IF NOT EXISTS clientes (
            id_cliente INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(100) NOT NULL,
            email VARCHAR(100) NULL,
            telefone VARCHAR(20) NULL,
            limite_credito DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            saldo_devedor DECIMAL(10,2) NOT NULL DEFAULT 0.00
        )");

        // 4. Tabela de Vendas
        $db->exec("CREATE TABLE IF NOT EXISTS vendas (
            id_venda INTEGER PRIMARY KEY AUTOINCREMENT,
            id_cliente INT NULL,
            id_usuario INT NOT NULL,
            data_venda DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            subtotal DECIMAL(10,2) NOT NULL,
            imposto DECIMAL(10,2) NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            forma_pagamento VARCHAR(50) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'CONCLUIDA',
            FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE SET NULL,
            FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
        )");

        // 5. Tabela de Itens da Venda
        $db->exec("CREATE TABLE IF NOT EXISTS itens_venda (
            id_item INTEGER PRIMARY KEY AUTOINCREMENT,
            id_venda INT NOT NULL,
            id_produto INT NOT NULL,
            quantidade INT NOT NULL,
            preco_unitario DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (id_venda) REFERENCES vendas(id_venda) ON DELETE CASCADE,
            FOREIGN KEY (id_produto) REFERENCES produtos(id_produto)
        )");

        // 6. Tabela de Transações Financeiras
        $db->exec("CREATE TABLE IF NOT EXISTS transacoes (
            id_transacao INTEGER PRIMARY KEY AUTOINCREMENT,
            descricao VARCHAR(255) NOT NULL,
            tipo VARCHAR(50) NOT NULL,
            origem VARCHAR(50) NOT NULL DEFAULT 'MANUAL',
            id_venda INT NULL,
            valor DECIMAL(10,2) NOT NULL,
            data_transacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            status VARCHAR(50) NOT NULL DEFAULT 'CONCLUIDO',
            FOREIGN KEY (id_venda) REFERENCES vendas(id_venda) ON DELETE CASCADE
        )");

        // 7. Tabela de Logs de Auditoria
        $db->exec("CREATE TABLE IF NOT EXISTS logs_auditoria (
            id_log INTEGER PRIMARY KEY AUTOINCREMENT,
            data_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tipo_acao VARCHAR(50) NOT NULL,
            descricao TEXT NOT NULL,
            usuario VARCHAR(150) NOT NULL,
            valor VARCHAR(50) NOT NULL DEFAULT 'N/A',
            status VARCHAR(50) NOT NULL DEFAULT 'SUCESSO'
        )");

        // 8. Tabela de Colunas do Kanban
        $db->exec("CREATE TABLE IF NOT EXISTS kanban_colunas (
            id_coluna INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo VARCHAR(50) NOT NULL,
            ordem INT NOT NULL DEFAULT 0
        )");

        // 9. Tabela de Tarefas do Kanban
        $db->exec("CREATE TABLE IF NOT EXISTS kanban_tarefas (
            id_tarefa INTEGER PRIMARY KEY AUTOINCREMENT,
            titulo VARCHAR(100) NOT NULL,
            descricao TEXT NULL,
            data_vencimento DATE NOT NULL,
            id_responsavel INT NOT NULL,
            prioridade VARCHAR(50) NOT NULL DEFAULT 'MEDIA',
            id_coluna INT NOT NULL,
            concluida INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (id_responsavel) REFERENCES usuarios(id_usuario),
            FOREIGN KEY (id_coluna) REFERENCES kanban_colunas(id_coluna) ON DELETE CASCADE
        )");

        // Garante que a coluna 'concluida' exista em instalações antigas
        try {
            $db->exec("ALTER TABLE kanban_tarefas ADD COLUMN concluida INTEGER NOT NULL DEFAULT 0");
        } catch (\PDOException $e) {
            // Ignora se a coluna já existir
        }

        // 10. Tabela de Convites de Equipe
        $db->exec("CREATE TABLE IF NOT EXISTS convites_equipe (
            id_convite INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(100) NOT NULL UNIQUE,
            perfil VARCHAR(50) NOT NULL DEFAULT 'CAIXA',
            token VARCHAR(100) NOT NULL UNIQUE,
            status VARCHAR(50) NOT NULL DEFAULT 'PENDENTE',
            data_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");

        // Seeder inicial para Kanban colunas se estiver vazio
        $count = $db->query("SELECT COUNT(*) FROM kanban_colunas")->fetchColumn();
        if ($count == 0) {
            $db->exec("INSERT INTO kanban_colunas (titulo, ordem) VALUES ('A Fazer', 0)");
            $db->exec("INSERT INTO kanban_colunas (titulo, ordem) VALUES ('Em Progresso', 1)");
            $db->exec("INSERT INTO kanban_colunas (titulo, ordem) VALUES ('Concluído', 2)");
        }

        // Seeder inicial para Funcionários (Patricia, Maria, Carlos) com seus devidos cargos
        $staff = [
            [
                'nome' => 'Patricia',
                'email' => 'patricia@eleganciapremium.com',
                'nivel_acesso' => 'ADMIN',
                'perms' => [
                    'perm_dashboard' => 1, 'perm_caixa' => 1, 'perm_estoque' => 1, 'perm_financeiro' => 1,
                    'perm_crm' => 1, 'perm_kanban' => 1, 'perm_relatorios' => 1, 'perm_equipe' => 1
                ]
            ],
            [
                'nome' => 'Carlos',
                'email' => 'carlos@eleganciapremium.com',
                'nivel_acesso' => 'ESTOQUE',
                'perms' => [
                    'perm_dashboard' => 1, 'perm_caixa' => 0, 'perm_estoque' => 1, 'perm_financeiro' => 0,
                    'perm_crm' => 0, 'perm_kanban' => 1, 'perm_relatorios' => 0, 'perm_equipe' => 0
                ]
            ],
            [
                'nome' => 'Maria',
                'email' => 'maria@eleganciapremium.com',
                'nivel_acesso' => 'CAIXA',
                'perms' => [
                    'perm_dashboard' => 1, 'perm_caixa' => 1, 'perm_estoque' => 0, 'perm_financeiro' => 0,
                    'perm_crm' => 1, 'perm_kanban' => 1, 'perm_relatorios' => 0, 'perm_equipe' => 0
                ]
            ]
        ];

        foreach ($staff as $member) {
            $stmt = $db->prepare("SELECT id_usuario FROM usuarios WHERE LOWER(nome) = LOWER(:nome) OR LOWER(email) = LOWER(:email)");
            $stmt->execute([':nome' => $member['nome'], ':email' => $member['email']]);
            $existing = $stmt->fetch();
            $hash = password_hash('123', PASSWORD_BCRYPT, ['cost' => 12]);
            if (!$existing) {
                $stmtInsert = $db->prepare("INSERT INTO usuarios (nome, email, senha_hash, nivel_acesso, status, perm_dashboard, perm_caixa, perm_estoque, perm_financeiro, perm_crm, perm_kanban, perm_relatorios, perm_equipe) 
                        VALUES (:nome, :email, :senha_hash, :nivel_acesso, 'ATIVO', :perm_dashboard, :perm_caixa, :perm_estoque, :perm_financeiro, :perm_crm, :perm_kanban, :perm_relatorios, :perm_equipe)");
                $params = [
                    ':nome' => $member['nome'],
                    ':email' => $member['email'],
                    ':senha_hash' => $hash,
                    ':nivel_acesso' => $member['nivel_acesso']
                ];
                foreach ($member['perms'] as $key => $val) {
                    $params[':' . $key] = $val;
                }
                $stmtInsert->execute($params);
            } else {
                // Atualiza o nível de acesso, permissões e garante que a senha seja 123
                $stmtUpdate = $db->prepare("UPDATE usuarios SET nivel_acesso = :nivel_acesso, senha_hash = :senha_hash, 
                    perm_dashboard = :perm_dashboard, perm_caixa = :perm_caixa, perm_estoque = :perm_estoque, 
                    perm_financeiro = :perm_financeiro, perm_crm = :perm_crm, perm_kanban = :perm_kanban, 
                    perm_relatorios = :perm_relatorios, perm_equipe = :perm_equipe 
                    WHERE id_usuario = :id");
                $params = [
                    ':nivel_acesso' => $member['nivel_acesso'],
                    ':senha_hash' => $hash,
                    ':id' => $existing['id_usuario']
                ];
                foreach ($member['perms'] as $key => $val) {
                    $params[':' . $key] = $val;
                }
                $stmtUpdate->execute($params);
            }
        }
    }
}
