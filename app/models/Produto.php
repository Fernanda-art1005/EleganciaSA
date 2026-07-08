<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Produto {
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM produtos WHERE id_produto = :id");
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch();
        return $product ? $product : null;
    }

    public static function getAll(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM produtos WHERE ativo = 1 ORDER BY nome ASC");
        return $stmt->fetchAll();
    }

    public static function getLowStock(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM produtos WHERE quantidade <= estoque_minimo AND ativo = 1 ORDER BY quantidade ASC");
        return $stmt->fetchAll();
    }

    public static function create(array $data): bool {
        $db = Database::getConnection();
        // Preço mínimo deve ser maior ou igual a R$ 0,01 (regra RN-ES-005)
        if ($data['preco'] < 0.01) {
            return false;
        }
        $sql = "INSERT INTO produtos (nome, descricao, categoria, preco_custo, preco, quantidade, estoque_minimo, imagem_url, ativo) 
                VALUES (:nome, :descricao, :categoria, :preco_custo, :preco, :quantidade, :estoque_minimo, :imagem_url, 1)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?? null,
            ':categoria' => $data['categoria'] ?? null,
            ':preco_custo' => $data['preco_custo'] ?? 0.00,
            ':preco' => $data['preco'],
            ':quantidade' => $data['quantidade'],
            ':estoque_minimo' => $data['estoque_minimo'],
            ':imagem_url' => $data['imagem_url'] ?? null
        ]);
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();
        if ($data['preco'] < 0.01) {
            return false;
        }
        $sql = "UPDATE produtos SET nome = :nome, descricao = :descricao, categoria = :categoria, preco_custo = :preco_custo, preco = :preco, quantidade = :quantidade, estoque_minimo = :estoque_minimo, imagem_url = :imagem_url WHERE id_produto = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao'] ?? null,
            ':categoria' => $data['categoria'] ?? null,
            ':preco_custo' => $data['preco_custo'] ?? 0.00,
            ':preco' => $data['preco'],
            ':quantidade' => $data['quantidade'],
            ':estoque_minimo' => $data['estoque_minimo'],
            ':imagem_url' => $data['imagem_url'] ?? null,
            ':id' => $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        
        // Exclui itens de venda associados para garantir deleção física limpa
        $stmtItems = $db->prepare("DELETE FROM itens_venda WHERE id_produto = :id");
        $stmtItems->execute([':id' => $id]);
        
        // Exclui o produto do banco de dados fisicamente
        $stmt = $db->prepare("DELETE FROM produtos WHERE id_produto = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function search(string $query): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM produtos WHERE (nome LIKE :query OR categoria LIKE :query) AND ativo = 1 ORDER BY nome ASC");
        $stmt->execute([':query' => "%$query%"]);
        return $stmt->fetchAll();
    }
}
