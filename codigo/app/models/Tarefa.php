<?php
namespace App\Models;

use App\Config\Database;

class Tarefa {

    public static function getColumns(): array {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM kanban_colunas ORDER BY ordem ASC")->fetchAll();
    }

    public static function getTasks(): array {
        $db = Database::getConnection();
        return $db->query("
            SELECT t.*, u.nome as responsavel_nome
            FROM kanban_tarefas t
            JOIN usuarios u ON t.id_responsavel = u.id_usuario
        ")->fetchAll();
    }

    public static function create(array $data): bool {
        if (empty($data['id_responsavel'])) {
            throw new \Exception("Responsável obrigatório.");
        }

        $db = Database::getConnection();

        $sql = "INSERT INTO kanban_tarefas 
                (titulo, descricao, data_vencimento, id_responsavel, prioridade, id_coluna)
                VALUES (:titulo, :descricao, :data_vencimento, :id_responsavel, :prioridade, :id_coluna)";

        return $db->prepare($sql)->execute([
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'] ?? null,
            ':data_vencimento' => $data['data_vencimento'],
            ':id_responsavel' => $data['id_responsavel'],
            ':prioridade' => $data['prioridade'] ?? 'MEDIA',
            ':id_coluna' => $data['id_coluna']
        ]);
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();

        $sql = "UPDATE kanban_tarefas SET 
                titulo = :titulo,
                descricao = :descricao,
                data_vencimento = :data_vencimento,
                id_responsavel = :id_responsavel,
                prioridade = :prioridade,
                id_coluna = :id_coluna
                WHERE id_tarefa = :id";

        return $db->prepare($sql)->execute([
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'] ?? null,
            ':data_vencimento' => $data['data_vencimento'],
            ':id_responsavel' => $data['id_responsavel'],
            ':prioridade' => $data['prioridade'] ?? 'MEDIA',
            ':id_coluna' => $data['id_coluna'],
            ':id' => $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        return $db->prepare("DELETE FROM kanban_tarefas WHERE id_tarefa = :id")
                  ->execute([':id' => $id]);
    }
}