<?php
namespace App\Models;

use App\Config\Database;
use Exception;
use PDO;

class Tarefa {
    // CRUD de Funis (Colunas)
    public static function getColumns(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM kanban_colunas ORDER BY ordem ASC");
        return $stmt->fetchAll();
    }

    public static function createColumn(string $titulo): bool {
        $db = Database::getConnection();
        // Acha a última ordem
        $stmtOrdem = $db->query("SELECT MAX(ordem) as max_ordem FROM kanban_colunas");
        $res = $stmtOrdem->fetch();
        $ordem = ($res ? (int)$res['max_ordem'] : 0) + 1;

        $stmt = $db->prepare("INSERT INTO kanban_colunas (titulo, ordem) VALUES (:titulo, :ordem)");
        return $stmt->execute([
            ':titulo' => $titulo,
            ':ordem' => $ordem
        ]);
    }

    public static function renameColumn(int $id, string $titulo): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE kanban_colunas SET titulo = :titulo WHERE id_coluna = :id");
        return $stmt->execute([
            ':titulo' => $titulo,
            ':id' => $id
        ]);
    }

    public static function deleteColumn(int $id): bool {
        $db = Database::getConnection();
        // Regra RN-TA-002: Exclusão de funil com tarefas
        $stmtCheck = $db->prepare("SELECT COUNT(*) as count FROM kanban_tarefas WHERE id_coluna = :id");
        $stmtCheck->execute([':id' => $id]);
        $res = $stmtCheck->fetch();
        if ($res && $res['count'] > 0) {
            throw new Exception("Não é possível excluir um funil que ainda contém tarefas (" . $res['count'] . " afetadas). Mova as tarefas antes de excluir.");
        }

        $stmt = $db->prepare("DELETE FROM kanban_colunas WHERE id_coluna = :id");
        return $stmt->execute([':id' => $id]);
    }


    // CRUD de Tarefas
    public static function all(): array {
        return self::getTasks();
    }

    public static function getTasks(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT t.*, u.nome as responsavel_nome 
                            FROM kanban_tarefas t 
                            LEFT JOIN usuarios u ON t.id_responsavel = u.id_usuario 
                            ORDER BY t.data_vencimento ASC");
        return $stmt->fetchAll();
    }

    public static function getTasksByColumn(int $id_coluna): array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT t.*, u.nome as responsavel_nome 
                              FROM kanban_tarefas t 
                              LEFT JOIN usuarios u ON t.id_responsavel = u.id_usuario 
                              WHERE t.id_coluna = :id_coluna 
                              ORDER BY t.data_vencimento ASC");
        $stmt->execute([':id_coluna' => $id_coluna]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT t.*, u.nome as responsavel_nome FROM kanban_tarefas t LEFT JOIN usuarios u ON t.id_responsavel = u.id_usuario WHERE t.id_tarefa = :id");
        $stmt->execute([':id' => $id]);
        $task = $stmt->fetch();
        return $task ? $task : null;
    }

    public static function create(array $data): bool {
        $db = Database::getConnection();

        // Regra RN-TA-001: Responsável é obrigatório
        if (empty($data['id_responsavel'])) {
            throw new Exception("Toda tarefa deve possuir obrigatoriamente um responsável atribuído.");
        }

        if (empty($data['data_vencimento'])) {
            throw new Exception("A data de vencimento da tarefa é obrigatória.");
        }

        // Regra RN-TA-003: Data de vencimento válida (não anterior a hoje)
        $today = date('Y-m-d');
        if ($data['data_vencimento'] < $today) {
            throw new Exception("A data de vencimento de uma tarefa não pode ser anterior à data de hoje.");
        }

        // Regra RN-TA-004: Prioridade obrigatória
        $prioridadesValidas = ['BAIXA', 'MEDIA', 'ALTA', 'URGENTE'];
        $prioridade = strtoupper($data['prioridade'] ?? 'MEDIA');
        if (!in_array($prioridade, $prioridadesValidas)) {
            $prioridade = 'MEDIA';
        }

        $sql = "INSERT INTO kanban_tarefas (titulo, descricao, data_vencimento, id_responsavel, prioridade, id_coluna) 
                VALUES (:titulo, :descricao, :data_vencimento, :id_responsavel, :prioridade, :id_coluna)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'] ?? null,
            ':data_vencimento' => $data['data_vencimento'],
            ':id_responsavel' => (int)$data['id_responsavel'],
            ':prioridade' => $prioridade,
            ':id_coluna' => (int)$data['id_coluna']
        ]);
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getConnection();

        if (empty($data['id_responsavel'])) {
            throw new Exception("Toda tarefa deve possuir obrigatoriamente um responsável atribuído.");
        }

        if (empty($data['data_vencimento'])) {
            throw new Exception("A data de vencimento da tarefa é obrigatória.");
        }

        $existingTask = self::findById($id);
        if ($existingTask && $data['data_vencimento'] !== $existingTask['data_vencimento']) {
            $today = date('Y-m-d');
            if ($data['data_vencimento'] < $today) {
                throw new Exception("A data de vencimento de uma tarefa não pode ser anterior à data de hoje.");
            }
        }

        $prioridade = strtoupper($data['prioridade'] ?? 'MEDIA');

        $sql = "UPDATE kanban_tarefas SET 
                    titulo = :titulo, 
                    descricao = :descricao, 
                    data_vencimento = :data_vencimento, 
                    id_responsavel = :id_responsavel, 
                    prioridade = :prioridade, 
                    id_coluna = :id_coluna 
                WHERE id_tarefa = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'] ?? null,
            ':data_vencimento' => $data['data_vencimento'],
            ':id_responsavel' => (int)$data['id_responsavel'],
            ':prioridade' => $prioridade,
            ':id_coluna' => (int)$data['id_coluna'],
            ':id' => $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM kanban_tarefas WHERE id_tarefa = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Mover tarefa entre funis - Regra RF-TA-005
    public static function moveTask(int $id_tarefa, int $id_coluna): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE kanban_tarefas SET id_coluna = :id_coluna WHERE id_tarefa = :id_tarefa");
        return $stmt->execute([
            ':id_coluna' => $id_coluna,
            ':id_tarefa' => $id_tarefa
        ]);
    }
}
