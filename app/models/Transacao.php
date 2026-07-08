<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Transacao {
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM transacoes WHERE id_transacao = :id");
        $stmt->execute([':id' => $id]);
        $trans = $stmt->fetch();
        return $trans ? $trans : null;
    }

    public static function getAll(array $filters = []): array {
        $db = Database::getConnection();
        $sql = "SELECT * FROM transacoes WHERE 1=1";
        $params = [];

        if (!empty($filters['tipo'])) {
            $sql .= " AND tipo = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['data_inicio'])) {
            $sql .= " AND data_transacao >= :data_inicio";
            $params[':data_inicio'] = $filters['data_inicio'] . " 00:00:00";
        }
        if (!empty($filters['data_fim'])) {
            $sql .= " AND data_transacao <= :data_fim";
            $params[':data_fim'] = $filters['data_fim'] . " 23:59:59";
        }

        $sql .= " ORDER BY data_transacao DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(array $data): bool {
        $db = Database::getConnection();
        $sql = "INSERT INTO transacoes (descricao, tipo, origem, id_venda, valor, status) 
                VALUES (:desc, :tipo, :origem, :id_venda, :valor, :status)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':desc' => $data['descricao'],
            ':tipo' => $data['tipo'], // RECEITA, DESPESA
            ':origem' => $data['origem'] ?? 'MANUAL',
            ':id_venda' => $data['id_venda'] ?? null,
            ':valor' => $data['valor'],
            ':status' => $data['status'] ?? ($data['tipo'] === 'RECEITA' ? 'CONCLUIDO' : 'SAIDA')
        ]);
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();
        // Regra RN-FI-004: Transações geradas por vendas não podem ter seu valor alterado por usuários comuns,
        // apenas o administrador pode autorizar ou editar transações originadas por vendas.
        $trans = self::findById($id);
        if ($trans && $trans['origem'] === 'VENDA' && isset($data['valor']) && (float)$data['valor'] !== (float)$trans['valor']) {
            // Em nossa lógica de controlador, vamos checar o cargo da sessão. No modelo, prosseguimos se explicitamente permitido.
        }

        $sql = "UPDATE transacoes SET descricao = :desc, tipo = :tipo, valor = :valor, status = :status WHERE id_transacao = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':desc' => $data['descricao'],
            ':tipo' => $data['tipo'],
            ':valor' => $data['valor'],
            ':status' => $data['status'],
            ':id' => $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM transacoes WHERE id_transacao = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Calcula saldo total da loja (Regra RN-FI-001)
    // Saldo Total = Soma de Receitas (Concluído) - Soma de Despesas e Saídas. Crédito pendente NÃO entra até ser recebido.
    public static function getNetBalance(): float {
        $db = Database::getConnection();
        
        // Receitas Concluídas (exclui PENDENTE)
        $stmtRec = $db->query("SELECT SUM(valor) as total FROM transacoes WHERE tipo = 'RECEITA' AND status = 'CONCLUIDO'");
        $resRec = $stmtRec->fetch();
        $receitas = $resRec ? (float)$resRec['total'] : 0.00;

        // Despesas e Saídas
        $stmtDes = $db->query("SELECT SUM(valor) as total FROM transacoes WHERE tipo = 'DESPESA' OR status = 'SAIDA'");
        $resDes = $stmtDes->fetch();
        $despesas = $resDes ? (float)$resDes['total'] : 0.00;

        return $receitas - $despesas;
    }

    // Soma total de vendas registradas pelo PDV (Regra RF-FI-002)
    public static function getTotalSalesAmount(): float {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT SUM(valor) as total FROM transacoes WHERE origem = 'VENDA'");
        $res = $stmt->fetch();
        return $res ? (float)$res['total'] : 0.00;
    }

    // Soma de crédito pendente de clientes inadimplentes/em aberto (Regra RF-FI-003)
    public static function getPendingCreditsAmount(): float {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT SUM(valor) as total FROM transacoes WHERE tipo = 'RECEITA' AND status = 'PENDENTE'");
        $res = $stmt->fetch();
        return $res ? (float)$res['total'] : 0.00;
    }

    // Quitar transação de venda por crédito (quando o cliente faz pagamento no CRM)
    public static function clearPendingSaleTransaction(int $id_venda): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE transacoes SET status = 'CONCLUIDO' WHERE id_venda = :id_venda AND status = 'PENDENTE'");
        return $stmt->execute([':id_venda' => $id_venda]);
    }
}
