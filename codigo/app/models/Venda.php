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

            // ---------------- VALIDAÇÃO BASE ----------------
            if (empty($saleData['items']) || !is_array($saleData['items'])) {
                throw new Exception("Nenhum item válido informado na venda.");
            }

            $id_cliente = !empty($saleData['id_cliente']) ? (int)$saleData['id_cliente'] : null;
            $id_usuario = (int)$saleData['id_usuario'];
            $forma_pagamento = $saleData['forma_pagamento'];
            $subtotal = (float)$saleData['subtotal'];
            $imposto = (float)$saleData['imposto'];
            $total = (float)$saleData['total'];
            $items = $saleData['items'];

            $now = date('Y-m-d H:i:s');

            // ---------------- VALIDA CRÉDITO ----------------
            if ($forma_pagamento === 'CREDITO_LOJA') {

                if (!$id_cliente) {
                    throw new Exception("Cliente obrigatório para crédito loja.");
                }

                $stmtCl = $db->prepare("
                    SELECT limite_credito, saldo_devedor 
                    FROM clientes 
                    WHERE id_cliente = :id
                ");
                $stmtCl->execute([':id' => $id_cliente]);
                $client = $stmtCl->fetch();

                if (!$client) {
                    throw new Exception("Cliente inválido.");
                }

                $creditoDisponivel = $client['limite_credito'] - $client['saldo_devedor'];

                if ($total > $creditoDisponivel) {
                    throw new Exception("Limite de crédito excedido.");
                }
            }

            // ---------------- INSERE VENDA ----------------
            $statusVenda = ($forma_pagamento === 'CREDITO_LOJA')
                ? 'CONCLUIDA'
                : 'CONCLUIDA';

            $stmtVenda = $db->prepare("
                INSERT INTO vendas 
                (id_cliente, id_usuario, data_venda, subtotal, imposto, total, forma_pagamento, status)
                VALUES
                (:id_cliente, :id_usuario, :data_venda, :subtotal, :imposto, :total, :forma_pagamento, :status)
            ");

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

            // ---------------- ITENS + ESTOQUE ----------------
            foreach ($items as $item) {

                $id_produto = (int)$item['id_produto'];
                $quantidade = (int)$item['quantidade'];
                $preco_unitario = (float)$item['preco_unitario'];

                if ($quantidade <= 0) {
                    continue;
                }

                $stmtProd = $db->prepare("
                    SELECT nome, quantidade 
                    FROM produtos 
                    WHERE id_produto = :id
                ");
                $stmtProd->execute([':id' => $id_produto]);
                $product = $stmtProd->fetch();

                if (!$product) {
                    throw new Exception("Produto não encontrado.");
                }

                if ($product['quantidade'] < $quantidade) {
                    throw new Exception("Estoque insuficiente.");
                }

                // item
                $stmtItem = $db->prepare("
                    INSERT INTO itens_venda 
                    (id_venda, id_produto, quantidade, preco_unitario)
                    VALUES
                    (:id_venda, :id_produto, :quantidade, :preco_unitario)
                ");

                $stmtItem->execute([
                    ':id_venda' => $id_venda,
                    ':id_produto' => $id_produto,
                    ':quantidade' => $quantidade,
                    ':preco_unitario' => $preco_unitario
                ]);

                // estoque
                $db->prepare("
                    UPDATE produtos 
                    SET quantidade = quantidade - :qtd 
                    WHERE id_produto = :id
                ")->execute([
                    ':qtd' => $quantidade,
                    ':id' => $id_produto
                ]);
            }

            // ---------------- CRÉDITO CLIENTE ----------------
            if ($forma_pagamento === 'CREDITO_LOJA') {
                $db->prepare("
                    UPDATE clientes 
                    SET saldo_devedor = saldo_devedor + :total 
                    WHERE id_cliente = :id
                ")->execute([
                    ':total' => $total,
                    ':id' => $id_cliente
                ]);
            }

            // ---------------- TRANSAÇÃO FINANCEIRA ----------------
            $statusTransacao = ($forma_pagamento === 'CREDITO_LOJA')
                ? 'PENDENTE'
                : 'CONCLUIDO';

            $stmtTrans = $db->prepare("
                INSERT INTO transacoes
                (descricao, tipo, origem, id_venda, valor, data_transacao, status)
                VALUES
                (:desc, 'RECEITA', 'VENDA', :id_venda, :valor, :data, :status)
            ");

            $stmtTrans->execute([
                ':desc' => "Venda #$id_venda",
                ':id_venda' => $id_venda,
                ':valor' => $total,
                ':data' => $now,
                ':status' => $statusTransacao
            ]);

            // ---------------- AUDITORIA ----------------
            $stmtLog = $db->prepare("
                INSERT INTO logs_auditoria 
                (data_hora, tipo_acao, descricao, usuario, valor, status)
                VALUES
                (:data, 'VENDA', :desc, 'SISTEMA', :valor, 'SUCESSO')
            ");

            $stmtLog->execute([
                ':data' => $now,
                ':desc' => "Venda #$id_venda registrada",
                ':valor' => $total
            ]);

            $db->commit();
            return $id_venda;

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}