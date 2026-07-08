<?php
$host = "127.0.0.1";
$dbname = "elegancia_premium";
$user = "root";
$pass = "";

try {
    $db = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Limpa tabelas antes de semear
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    $db->exec("TRUNCATE TABLE convites_equipe;");
    $db->exec("TRUNCATE TABLE kanban_tarefas;");
    $db->exec("TRUNCATE TABLE kanban_colunas;");
    $db->exec("TRUNCATE TABLE logs_auditoria;");
    $db->exec("TRUNCATE TABLE transacoes;");
    $db->exec("TRUNCATE TABLE itens_venda;");
    $db->exec("TRUNCATE TABLE vendas;");
    $db->exec("TRUNCATE TABLE clientes;");
    $db->exec("TRUNCATE TABLE produtos;");
    $db->exec("TRUNCATE TABLE usuarios;");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // Semeia usuários
    $hash = password_hash('123', PASSWORD_BCRYPT, ['cost' => 12]);
    $db->exec("INSERT INTO usuarios (id_usuario, nome, email, senha_hash, nivel_acesso, status, perm_dashboard, perm_caixa, perm_estoque, perm_financeiro, perm_crm, perm_kanban, perm_relatorios, perm_equipe) VALUES 
        (1, 'Administrador', 'admin@elegancia.com', '$hash', 'ADMIN', 'ATIVO', 1, 1, 1, 1, 1, 1, 1, 1),
        (2, 'Patricia', 'patricia@elegancia.com', '$hash', 'ADMIN', 'ATIVO', 1, 1, 1, 1, 1, 1, 1, 1),
        (3, 'Carlos', 'carlos@elegancia.com', '$hash', 'ESTOQUE', 'ATIVO', 1, 0, 1, 0, 1, 1, 0, 0),
        (4, 'Maria', 'maria@elegancia.com', '$hash', 'CAIXA', 'ATIVO', 1, 1, 0, 1, 1, 1, 1, 0)");

    // Semeia produtos
    $db->exec("INSERT INTO produtos (id_produto, nome, preco, quantidade, estoque_minimo) VALUES 
        (1, 'Vestido Longo Floral', 189.90, 15, 5),
        (2, 'Blusa Seda Elegance', 120.00, 8, 10),
        (3, 'Calça Alfaiataria', 159.90, 25, 8),
        (4, 'Bolsa Couro Classic', 249.90, 4, 5),
        (5, 'Blazer Modern Fit', 299.00, 12, 4)");

    // Semeia colunas Kanban
    $db->exec("INSERT INTO kanban_colunas (id_coluna, titulo, ordem) VALUES 
        (1, 'A Fazer', 1),
        (2, 'Em Andamento', 2),
        (3, 'Concluído', 3)");

    // Semeia clientes CRM
    $db->exec("INSERT INTO clientes (id_cliente, nome, email, telefone, limite_credito, saldo_devedor) VALUES 
        (1, 'Maria Silva', 'maria@email.com', '(11) 98765-4321', 1000.00, 150.00),
        (2, 'Ana Oliveira', 'ana@email.com', '(11) 97654-3210', 1500.00, 0.00),
        (3, 'Carla Souza', 'carla@email.com', '(11) 96543-2109', 800.00, 520.00)");

    // Semeia tarefas Kanban
    $db->exec("INSERT INTO kanban_tarefas (id_tarefa, titulo, descricao, data_vencimento, id_responsavel, prioridade, id_coluna) VALUES 
        (1, 'Reposição de Vestidos', 'Solicitar novos tamanhos do vestido longo floral.', '2026-07-15', 1, 'ALTA', 1),
        (2, 'Ajustar Alíquota Imposto', 'Alíquota de imposto padrão precisa ser revisada no sistema.', '2026-07-10', 1, 'MEDIA', 2),
        (3, 'Balancete Trimestral', 'Fechar relatório financeiro do segundo trimestre.', '2026-07-20', 1, 'URGENTE', 1)");

    // Semeia logs de auditoria
    $db->exec("INSERT INTO logs_auditoria (data_hora, tipo_acao, descricao, usuario, valor, status) VALUES 
        (NOW(), 'SISTEMA', 'Sistema inicializado e dados de demonstração semeados.', 'Sistema', 'N/A', 'SUCESSO')");

    // Semeia transações financeiras para simular receita e despesa
    $db->exec("INSERT INTO transacoes (descricao, tipo, origem, valor, data_transacao, status) VALUES 
        ('Venda de Vestido Longo Floral', 'RECEITA', 'VENDA', 189.90, NOW(), 'CONCLUIDO'),
        ('Venda de Calça Alfaiataria', 'RECEITA', 'VENDA', 159.90, NOW(), 'CONCLUIDO'),
        ('Pagamento de Fornecedor Tecidos', 'DESPESA', 'MANUAL', 450.00, NOW(), 'SAIDA'),
        ('Compra de Material de Escritório', 'DESPESA', 'MANUAL', 85.00, NOW(), 'SAIDA')");

    echo "Seed completed successfully!\n";
} catch (Exception $e) {
    echo "Seed failed: " . $e->getMessage() . "\n";
}
