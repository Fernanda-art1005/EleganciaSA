<?php
namespace App\Models;

use App\Config\Database;

class Usuario {

    public static function getAll(): array {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM usuarios ORDER BY nome ASC")->fetchAll();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();

        $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE nivel_acesso='ADMIN' AND status='ATIVO'");
        $res = $stmt->fetch();

        $user = self::findById($id);

        if ($user && $user['nivel_acesso'] === 'ADMIN' && $res['total'] <= 1) {
            throw new \Exception("Não pode excluir o último administrador.");
        }

        return $db->prepare("DELETE FROM usuarios WHERE id_usuario = :id")
                   ->execute([':id' => $id]);
    }
}