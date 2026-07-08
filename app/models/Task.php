<?php
namespace App\Models;

use App\Config\Database;
use Exception;
use PDO;

class Task {
    // Busca todas as tarefas
    public static function all(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT t.*, u.nome as responsavel_nome 
                            FROM kanban_tarefas t 
                            LEFT JOIN usuarios u ON t.id_responsavel = u.id_usuario 
                            ORDER BY t.data_vencimento ASC");
        return $stmt->fetchAll();
    }

    // Busca uma tarefa específica por ID
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT t.*, u.nome as responsavel_nome 
                              FROM kanban_tarefas t 
                              LEFT JOIN usuarios u ON t.id_responsavel = u.id_usuario 
                              WHERE t.id_tarefa = :id");
        $stmt->execute([':id' => $id]);
        $task = $stmt->fetch();
        return $task ? $task : null;
    }

    // Insere uma nova tarefa
    public static function create(array $data): bool {
        $db = Database::getConnection();

        if (empty($data['id_responsavel'])) {
            throw new Exception("Toda tarefa deve possuir obrigatoriamente um responsável atribuído.");
        }

        if (empty($data['data_vencimento'])) {
            throw new Exception("A data de vencimento da tarefa é obrigatória.");
        }

        $today = date('Y-m-d');
        if ($data['data_vencimento'] < $today) {
            throw new Exception("A data de vencimento de uma tarefa não pode ser anterior à data de hoje.");
        }

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

    // Atualiza uma tarefa existente
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

        $prioridadesValidas = ['BAIXA', 'MEDIA', 'ALTA', 'URGENTE'];
        $prioridade = strtoupper($data['prioridade'] ?? 'MEDIA');
        if (!in_array($prioridade, $prioridadesValidas)) {
            $prioridade = 'MEDIA';
        }

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

    // Exclui uma tarefa
    public static function delete(int $id): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM kanban_tarefas WHERE id_tarefa = :id");
        return $stmt->execute([':id' => $id]);
    }

    // Conclui uma tarefa (Marca como concluída e move para a coluna de Concluídos)
    public static function complete(int $id): bool {
        $db = Database::getConnection();
        
        // Localiza a coluna de concluídos
        $stmtCol = $db->query("SELECT id_coluna FROM kanban_colunas WHERE LOWER(titulo) LIKE '%conclu%' LIMIT 1");
        $col = $stmtCol->fetch();
        
        if ($col) {
            $stmt = $db->prepare("UPDATE kanban_tarefas SET concluida = 1, id_coluna = :id_coluna WHERE id_tarefa = :id");
            return $stmt->execute([
                ':id_coluna' => $col['id_coluna'],
                ':id' => $id
            ]);
        } else {
            $stmt = $db->prepare("UPDATE kanban_tarefas SET concluida = 1 WHERE id_tarefa = :id");
            return $stmt->execute([':id' => $id]);
        }
    }
}
