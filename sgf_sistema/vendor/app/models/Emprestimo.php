<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Emprestimo {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function listarAtivos(): array {
        $sql = "SELECT e.*, f.nome AS ferramenta_nome, u.nome AS usuario_nome
                FROM emprestimos e
                JOIN ferramentas f ON e.id_ferramenta = f.id_ferramenta
                JOIN usuarios    u ON e.id_usuario    = u.id_usuario
                WHERE e.status_emprestimo IN ('ATIVO','ATRASADO')
                ORDER BY e.data_hora_saida DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function registrarDevolucao(int $idEmprestimo): bool {
        $sql = "UPDATE emprestimos
                SET data_hora_devolucao = NOW(), status_emprestimo = 'DEVOLVIDO'
                WHERE id_emprestimo = :id AND status_emprestimo != 'DEVOLVIDO'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idEmprestimo]);
        return $stmt->rowCount() > 0;
    }

    /** Rotina de atualização de status para ATRASADO — executada por cron ou chamada periódica */
    public function atualizarAtrasados(): int {
        $sql = "UPDATE emprestimos
                SET status_emprestimo = 'ATRASADO'
                WHERE status_emprestimo = 'ATIVO' AND NOW() > data_hora_previsao";
        $stmt = $this->db->query($sql);
        return $stmt->rowCount();
    }
}
