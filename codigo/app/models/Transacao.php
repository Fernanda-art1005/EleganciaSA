<?php
namespace App\Models;

use App\Config\Database;

class Transacao {

    public static function getAll(): array {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM transacoes ORDER BY data_transacao DESC")->fetchAll();
    }

    public static function create(array $data): bool {
        $db = Database::getConnection();

        $sql = "INSERT INTO transacoes 
                (descricao, tipo, origem, id_venda, valor, status)
                VALUES (:descricao, :tipo, :origem, :id_venda, :valor, :status)";

        return $db->prepare($sql)->execute([
            ':descricao' => $data['descricao'],
            ':tipo' => $data['tipo'],
            ':origem' => $data['origem'] ?? 'MANUAL',
            ':id_venda' => $data['id_venda'] ?? null,
            ':valor' => $data['valor'],
            ':status' => $data['status'] ?? 'CONCLUIDO'
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();

        $trans = $db->prepare("SELECT origem FROM transacoes WHERE id_transacao = :id");
        $trans->execute([':id' => $id]);
        $t = $trans->fetch();

        if ($t && $t['origem'] === 'VENDA') {
            throw new \Exception("Transações de venda não podem ser excluídas.");
        }

        return $db->prepare("DELETE FROM transacoes WHERE id_transacao = :id")
                   ->execute([':id' => $id]);
    }
}