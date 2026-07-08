<?php
namespace App\Models;

use App\Config\Database;
use Exception;
use PDO;

class Venda {
    public static function createSale(array $saleData): int {
        $db = Database::getConnection();
        
        try {
            $db->beginTransaction();

            $id_cliente = !empty($saleData['id_cliente']) ? (int)$saleData['id_cliente'] : null;
            $id_usuario = (int)$saleData['id_usuario'];
            $forma_pagamento = $saleData['forma_pagamento']; // DEBITO, CREDITO_LOJA, PIX, CARTAO
            $subtotal = (float)$saleData['subtotal'];
            $imposto = (float)$saleData['imposto'];
            $total = (float)$saleData['total'];
            $items = $saleData['items']; // Array of ['id_produto' => X, 'quantidade' => Y, 'preco_unitario' => Z]

            // 1. Se for Crédito de Loja, valida o cliente
            if ($forma_pagamento === 'CREDITO_LOJA') {
                if ($id_cliente === null) {
                    throw new Exception("Cliente não selecionado para a modalidade Crédito Loja.");
                }

                // Busca dados de crédito do cliente
                $stmtCl = $db->prepare("SELECT limite_credito, saldo_devedor FROM clientes WHERE id_cliente = :id");
                $stmtCl->execute([':id' => $id_cliente]);
                $client = $stmtCl->fetch();
                if (!$client) {
                    throw new Exception("Cliente inválido.");
                }

                $creditoDisponivel = $client['limite_credito'] - $client['saldo_devedor'];
                
                // Regra RN-CR-002: Bloqueia se o valor total exceder o crédito disponível
                if ($total > $creditoDisponivel) {
                    throw new Exception("Limite de crédito excedido. Disponível: R$ " . number_format($creditoDisponivel, 2, ',', '.') . ", Compra: R$ " . number_format($total, 2, ',', '.'));
                }
            }

            // 2. Insere a venda principal
            $stmtVenda = $db->prepare("INSERT INTO vendas (id_cliente, id_usuario, data_venda, subtotal, imposto, total, forma_pagamento, status) 
                                       VALUES (:id_cliente, :id_usuario, :data_venda, :subtotal, :imposto, :total, :forma_pagamento, :status)");
            
            $statusVenda = 'CONCLUIDA'; // Crédito loja inicia pendente? Requisito diz: "vendas concluídas por crédito geram transação pendente". Venda em si pode ser concluída.
            
            // Para SQLite e MySQL unificados, pegamos data atual
            $now = date('Y-m-d H:i:s');
            $stmtVenda->execute([
                ':id_cliente' => $id_cliente,
                ':id_usuario' => $id_usuario,
                ':data_venda' => $now,
                ':subtotal' => $subtotal,
                ':imposto' => $imposto,
                ':total' => $total,
                ':forma_pagamento' => $forma_pagamento,
                ':status' => $statusVenda
            ]);

            $id_venda = (int)$db->lastInsertId();

            // 3. Processa cada item da venda
            foreach ($items as $item) {
                $id_produto = (int)$item['id_produto'];
                $quantidade = (int)$item['quantidade'];
                $preco_unitario = (float)$item['preco_unitario'];

                // Verifica estoque do produto (Regra RN-ES-002 - Bloqueia venda sem estoque suficiente)
                $stmtProd = $db->prepare("SELECT nome, quantidade FROM produtos WHERE id_produto = :id");
                $stmtProd->execute([':id' => $id_produto]);
                $product = $stmtProd->fetch();
                if (!$product) {
                    throw new Exception("Produto ID $id_produto não encontrado.");
                }

                if ($product['quantidade'] < $quantidade) {
                    throw new Exception("Estoque insuficiente para '" . $product['nome'] . "'. Solicitado: $quantidade, Disponível: " . $product['quantidade']);
                }

                // Insere item da venda
                $stmtItem = $db->prepare("INSERT INTO itens_venda (id_venda, id_produto, quantidade, preco_unitario) 
                                           VALUES (:id_venda, :id_produto, :quantidade, :preco_unitario)");
                $stmtItem->execute([
                    ':id_venda' => $id_venda,
                    ':id_produto' => $id_produto,
                    ':quantidade' => $quantidade,
                    ':preco_unitario' => $preco_unitario
                ]);

                // Decrementa o estoque (Regra RN-ES-001)
                $stmtDecStock = $db->prepare("UPDATE produtos SET quantidade = quantidade - :qtd WHERE id_produto = :id");
                $stmtDecStock->execute([
                    ':qtd' => $quantidade,
                    ':id' => $id_produto
                ]);
            }

            // 4. Se for Crédito de Loja, adiciona ao saldo devedor do cliente (Regra RN-CR-003)
            if ($forma_pagamento === 'CREDITO_LOJA') {
                $stmtIncDebt = $db->prepare("UPDATE clientes SET saldo_devedor = saldo_devedor + :total WHERE id_cliente = :id");
                $stmtIncDebt->execute([
                    ':total' => $total,
                    ':id' => $id_cliente
                ]);
            }

            // 5. Gera automaticamente a transação financeira (Regra RN-FI-002)
            $tipoTransacao = 'RECEITA';
            $origemTransacao = 'VENDA';
            // Regra RN-FI-003: Vendas por crédito iniciam com status 'PENDENTE', outras são 'CONCLUIDO'
            $statusTransacao = ($forma_pagamento === 'CREDITO_LOJA') ? 'PENDENTE' : 'CONCLUIDO';
            
            $descricaoTransacao = "Venda PDV #" . $id_venda . ($id_cliente ? " - Cliente ID " . $id_cliente : "");

            $stmtTrans = $db->prepare("INSERT INTO transacoes (descricao, tipo, origem, id_venda, valor, data_transacao, status) 
                                        VALUES (:desc, :tipo, :origem, :id_venda, :valor, :data, :status)");
            $stmtTrans->execute([
                ':desc' => $descricaoTransacao,
                ':tipo' => $tipoTransacao,
                ':origem' => $origemTransacao,
                ':id_venda' => $id_venda,
                ':valor' => $total,
                ':data' => $now,
                ':status' => $statusTransacao
            ]);

            // 6. Registra no log imutável de auditoria (Regra RN-AU-001)
            $stmtUser = $db->prepare("SELECT nome, nivel_acesso FROM usuarios WHERE id_usuario = :id");
            $stmtUser->execute([':id' => $id_usuario]);
            $userData = $stmtUser->fetch();
            $usuarioIdentificador = $userData ? $userData['nome'] . " (" . $userData['nivel_acesso'] . ")" : "Sistema";

            $descLog = "Nova venda PDV realizada. ID: #" . $id_venda . " | Forma de Pagamento: " . $forma_pagamento . " | Total: R$ " . number_format($total, 2, ',', '.');
            
            $stmtLog = $db->prepare("INSERT INTO logs_auditoria (data_hora, tipo_acao, descricao, usuario, valor, status) 
                                      VALUES (:data_hora, :tipo, :desc, :usuario, :valor, 'SUCESSO')");
            $stmtLog->execute([
                ':data_hora' => $now,
                ':tipo' => 'VENDA',
                ':desc' => $descLog,
                ':usuario' => $usuarioIdentificador,
                ':valor' => 'R$ ' . number_format($total, 2, '.', '')
            ]);

            $db->commit();
            return $id_venda;

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function getAll(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT v.*, c.nome as cliente_nome, u.nome as usuario_nome 
                            FROM vendas v 
                            LEFT JOIN clientes c ON v.id_cliente = c.id_cliente 
                            JOIN usuarios u ON v.id_usuario = u.id_usuario 
                            ORDER BY v.data_venda DESC");
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT v.*, c.nome as cliente_nome, u.nome as usuario_nome 
                              FROM vendas v 
                              LEFT JOIN clientes c ON v.id_cliente = c.id_cliente 
                              JOIN usuarios u ON v.id_usuario = u.id_usuario 
                              WHERE v.id_venda = :id");
        $stmt->execute([':id' => $id]);
        $sale = $stmt->fetch();
        return $sale ? $sale : null;
    }

    public static function getSaleItems(int $id_venda): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT iv.*, p.nome as produto_nome 
                              FROM itens_venda iv 
                              JOIN produtos p ON iv.id_produto = p.id_produto 
                              WHERE iv.id_venda = :id_venda");
        $stmt->execute([':id_venda' => $id_venda]);
        return $stmt->fetchAll();
    }

    // Exclui/estorna uma venda realizada (Controlador de PDV, RF-CX-011)
    public static function deleteSale(int $id, string $operador = 'Sistema'): bool {
        $db = Database::getConnection();
        
        try {
            $db->beginTransaction();
            
            $sale = self::findById($id);
            if (!$sale) {
                throw new Exception("Venda ID $id não encontrada.");
            }
            
            $items = self::getSaleItems($id);
            
            // 1. Se era Crédito de Loja, reduz o saldo devedor do cliente
            if ($sale['forma_pagamento'] === 'CREDITO_LOJA' && !empty($sale['id_cliente'])) {
                $stmtCl = $db->prepare("SELECT saldo_devedor FROM clientes WHERE id_cliente = :id");
                $stmtCl->execute([':id' => $sale['id_cliente']]);
                $clData = $stmtCl->fetch();
                $currentDebt = $clData ? (float)$clData['saldo_devedor'] : 0.00;
                $newDebt = max(0.00, $currentDebt - (float)$sale['total']);
                
                $stmtDecDebt = $db->prepare("UPDATE clientes SET saldo_devedor = :new_debt WHERE id_cliente = :id");
                $stmtDecDebt->execute([
                    ':new_debt' => $newDebt,
                    ':id' => $sale['id_cliente']
                ]);
            }
            
            // 2. Devolve os itens faturados de volta para o estoque
            foreach ($items as $item) {
                $stmtIncStock = $db->prepare("UPDATE produtos SET quantidade = quantidade + :qtd WHERE id_produto = :id");
                $stmtIncStock->execute([
                    ':qtd' => $item['quantidade'],
                    ':id' => $item['id_produto']
                ]);
            }
            
            // 3. Exclui itens da venda
            $stmtDelItems = $db->prepare("DELETE FROM itens_venda WHERE id_venda = :id");
            $stmtDelItems->execute([':id' => $id]);
            
            // 4. Exclui transações financeiras geradas por essa venda
            $stmtDelTrans = $db->prepare("DELETE FROM transacoes WHERE id_venda = :id");
            $stmtDelTrans->execute([':id' => $id]);
            
            // 5. Exclui a venda em si
            $stmtDelSale = $db->prepare("DELETE FROM vendas WHERE id_venda = :id");
            $stmtDelSale->execute([':id' => $id]);
            
            // 6. Registra no log de auditoria
            $now = date('Y-m-d H:i:s');
            $descLog = "Venda PDV ID #$id CANCELADA/REMOVIDA pelo operador. Estoque devolvido e valores estornados. Total estorno: R$ " . number_format($sale['total'], 2, ',', '.');
            $stmtLog = $db->prepare("INSERT INTO logs_auditoria (data_hora, tipo_acao, descricao, usuario, valor, status) 
                                      VALUES (:data_hora, 'EXCLUSAO_VENDA', :desc, :usuario, :valor, 'SUCESSO')");
            $stmtLog->execute([
                ':data_hora' => $now,
                ':desc' => $descLog,
                ':usuario' => $operador,
                ':valor' => '-R$ ' . number_format($sale['total'], 2, '.', '')
            ]);
            
            $db->commit();
            return true;
            
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
