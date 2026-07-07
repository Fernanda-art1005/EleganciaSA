<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;
use Exception;

class EmprestimoController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function registrarSaida(int $idFerramenta, int $idUsuario, int $prazoHoras): bool {
        try {
            $this->db->beginTransaction();

            // 1. Inserir o registro de empréstimo
            $sql = "INSERT INTO emprestimos (id_ferramenta, id_usuario, data_hora_saida, data_hora_previsao, status_emprestimo)
                    VALUES (:id_ferramenta, :id_usuario, NOW(), DATE_ADD(NOW(), INTERVAL :prazo HOUR), 'ATIVO')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_ferramenta' => $idFerramenta,
                ':id_usuario' => $idUsuario,
                ':prazo' => $prazoHoras
            ]);

            // 2. Atualizar o status da ferramenta
            $sqlAtivo = "UPDATE ferramentas SET status_atual = 'EMPRESTADA', ciclos_atuais = ciclos_atuais + 1
                         WHERE id_ferramenta = :id_ferramenta AND status_atual = 'DISPONIVEL'";
            $stmtAtivo = $this->db->prepare($sqlAtivo);
            $stmtAtivo->execute([':id_ferramenta' => $idFerramenta]);

            if ($stmtAtivo->rowCount() === 0) {
                throw new Exception("Ativo indisponível para alocação de empréstimo.");
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Falha na transação de empréstimo: " . $e->getMessage());
            return false;
        }
    }
}
