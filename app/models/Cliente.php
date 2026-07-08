<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Cliente {
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
        $stmt->execute([':id' => $id]);
        $client = $stmt->fetch();
        if ($client) {
            $client['credito_disponivel'] = max(0.00, $client['limite_credito'] - $client['saldo_devedor']);
        }
        return $client ? $client : null;
    }

    public static function getAll(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM clientes ORDER BY nome ASC");
        $clients = $stmt->fetchAll();
        foreach ($clients as &$client) {
            $client['credito_disponivel'] = max(0.00, $client['limite_credito'] - $client['saldo_devedor']);
        }
        return $clients;
    }

    public static function create(array $data): bool {
        $db = Database::getConnection();
        $sql = "INSERT INTO clientes (nome, email, telefone, limite_credito, saldo_devedor) VALUES (:nome, :email, :telefone, :limite_credito, :saldo_devedor)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':nome' => $data['nome'],
            ':email' => $data['email'] ?? null,
            ':telefone' => $data['telefone'] ?? null,
            ':limite_credito' => $data['limite_credito'] ?? 0.00,
            ':saldo_devedor' => $data['saldo_devedor'] ?? 0.00
        ]);
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();
        $sql = "UPDATE clientes SET nome = :nome, email = :email, telefone = :telefone, limite_credito = :limite_credito, saldo_devedor = :saldo_devedor WHERE id_cliente = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':nome' => $data['nome'],
            ':email' => $data['email'] ?? null,
            ':telefone' => $data['telefone'] ?? null,
            ':limite_credito' => $data['limite_credito'],
            ':saldo_devedor' => $data['saldo_devedor'],
            ':id' => $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        // Set id_cliente = NULL on vendas to keep sales history without breaking constraint
        $stmtUpdate = $db->prepare("UPDATE vendas SET id_cliente = NULL WHERE id_cliente = :id");
        $stmtUpdate->execute([':id' => $id]);

        $stmt = $db->prepare("DELETE FROM clientes WHERE id_cliente = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function search(string $query): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM clientes WHERE nome LIKE :query ORDER BY nome ASC");
        $stmt->execute([':query' => "%$query%"]);
        $clients = $stmt->fetchAll();
        foreach ($clients as &$client) {
            $client['credito_disponivel'] = max(0.00, $client['limite_credito'] - $client['saldo_devedor']);
        }
        return $clients;
    }

    // Receber Pagamento de Crédito (Abate saldo devedor) - Regra RN-CR-004
    public static function receivePayment(int $id, float $value): bool {
        $db = Database::getConnection();
        $client = self::findById($id);
        if (!$client) {
            return false;
        }

        $newDebt = max(0.00, $client['saldo_devedor'] - $value);

        $stmt = $db->prepare("UPDATE clientes SET saldo_devedor = :new_debt WHERE id_cliente = :id");
        return $stmt->execute([
            ':new_debt' => $newDebt,
            ':id' => $id
        ]);
    }

    // Histórico de Compras - Regra RF-CL-009
    public static function getPurchaseHistory(int $id): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM vendas WHERE id_cliente = :id ORDER BY data_venda DESC");
        $stmt->execute([':id' => $id]);
        return $stmt->fetchAll();
    }
}
