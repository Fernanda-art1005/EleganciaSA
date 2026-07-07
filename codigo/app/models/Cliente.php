<?php
namespace App\Models;

use App\Config\Database;

class Cliente {

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM clientes WHERE id_cliente = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function getAll(): array {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll();
    }

    public static function create(array $data): bool {
        $db = Database::getConnection();

        $sql = "INSERT INTO clientes 
                (nome, email, telefone, limite_credito, saldo_devedor)
                VALUES (:nome, :email, :telefone, :limite, :saldo)";

        return $db->prepare($sql)->execute([
            ':nome' => $data['nome'],
            ':email' => $data['email'] ?? null,
            ':telefone' => $data['telefone'] ?? null,
            ':limite' => $data['limite_credito'] ?? 0,
            ':saldo' => $data['saldo_devedor'] ?? 0
        ]);
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();

        $sql = "UPDATE clientes SET 
                nome = :nome,
                email = :email,
                telefone = :telefone,
                limite_credito = :limite,
                saldo_devedor = :saldo
                WHERE id_cliente = :id";

        return $db->prepare($sql)->execute([
            ':nome' => $data['nome'],
            ':email' => $data['email'] ?? null,
            ':telefone' => $data['telefone'] ?? null,
            ':limite' => $data['limite_credito'],
            ':saldo' => $data['saldo_devedor'],
            ':id' => $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();

        $db->prepare("UPDATE vendas SET id_cliente = NULL WHERE id_cliente = :id")
           ->execute([':id' => $id]);

        return $db->prepare("DELETE FROM clientes WHERE id_cliente = :id")
                   ->execute([':id' => $id]);
    }
}