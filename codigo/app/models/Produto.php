<?php
namespace App\Models;

use App\Config\Database;

class Produto {

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM produtos WHERE id_produto = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function getAll(): array {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM produtos ORDER BY nome ASC")->fetchAll();
    }

    public static function create(array $data): bool {
        if ($data['preco'] < 0.01) {
            throw new \Exception("Preço inválido.");
        }

        $db = Database::getConnection();

        $sql = "INSERT INTO produtos 
                (nome, categoria, preco_custo, preco, quantidade, estoque_minimo)
                VALUES (:nome, :categoria, :preco_custo, :preco, :quantidade, :estoque_minimo)";

        return $db->prepare($sql)->execute([
            ':nome' => $data['nome'],
            ':categoria' => $data['categoria'] ?? null,
            ':preco_custo' => $data['preco_custo'] ?? 0,
            ':preco' => $data['preco'],
            ':quantidade' => $data['quantidade'],
            ':estoque_minimo' => $data['estoque_minimo']
        ]);
    }

    public static function update(int $id, array $data): bool {
        if ($data['preco'] < 0.01) {
            throw new \Exception("Preço inválido.");
        }

        $db = Database::getConnection();

        $sql = "UPDATE produtos SET 
                nome = :nome,
                categoria = :categoria,
                preco_custo = :preco_custo,
                preco = :preco,
                quantidade = :quantidade,
                estoque_minimo = :estoque_minimo
                WHERE id_produto = :id";

        return $db->prepare($sql)->execute([
            ':nome' => $data['nome'],
            ':categoria' => $data['categoria'] ?? null,
            ':preco_custo' => $data['preco_custo'] ?? 0,
            ':preco' => $data['preco'],
            ':quantidade' => $data['quantidade'],
            ':estoque_minimo' => $data['estoque_minimo'],
            ':id' => $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT COUNT(*) as total FROM itens_venda WHERE id_produto = :id");
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();

        if ($res && $res['total'] > 0) {
            throw new \Exception("Produto com histórico de vendas não pode ser excluído.");
        }

        return $db->prepare("DELETE FROM produtos WHERE id_produto = :id")
                   ->execute([':id' => $id]);
    }
}