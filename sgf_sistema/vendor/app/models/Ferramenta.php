<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Ferramenta {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function listar(array $filtros = []): array {
        $sql = "SELECT * FROM ferramentas WHERE 1=1";
        $params = [];

        if (!empty($filtros['status'])) {
            $sql .= " AND status_atual = :status";
            $params[':status'] = $filtros['status'];
        }
        if (!empty($filtros['categoria'])) {
            $sql .= " AND categoria = :categoria";
            $params[':categoria'] = $filtros['categoria'];
        }
        if (!empty($filtros['termo'])) {
            $sql .= " AND nome LIKE :termo";
            $params[':termo'] = '%' . $filtros['termo'] . '%';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function buscarPorId(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM ferramentas WHERE id_ferramenta = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function cadastrar(array $dados): bool {
        $sql = "INSERT INTO ferramentas (codigo_tag, nome, categoria, localizacao, vida_util_ciclos)
                VALUES (:codigo_tag, :nome, :categoria, :localizacao, :vida_util_ciclos)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':codigo_tag'       => $dados['codigo_tag'],
            ':nome'             => $dados['nome'],
            ':categoria'        => $dados['categoria'],
            ':localizacao'      => $dados['localizacao'],
            ':vida_util_ciclos' => $dados['vida_util_ciclos'],
        ]);
    }

    /** Baixa lógica — nunca exclui fisicamente o registro */
    public function baixarLogico(int $id, string $justificativa): bool {
        $sql = "UPDATE ferramentas SET status_atual = 'BAIXADA' WHERE id_ferramenta = :id";
        $stmt = $this->db->prepare($sql);
        // justificativa pode ser gravada em tabela de log em versões futuras
        return $stmt->execute([':id' => $id]);
    }
}
