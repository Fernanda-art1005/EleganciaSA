<?php
namespace App\Models;

use App\Config\Database;
use Exception;
use PDO;

class Auditoria {
    public static function log(string $tipo_acao, string $descricao, string $usuario, string $valor = 'N/A', string $status = 'SUCESSO'): bool {
        $db = Database::getConnection();
        $sql = "INSERT INTO logs_auditoria (data_hora, tipo_acao, descricao, usuario, valor, status) 
                VALUES (:data_hora, :tipo, :desc, :usuario, :valor, :status)";
        
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':data_hora' => $now,
            ':tipo' => strtoupper($tipo_acao),
            ':desc' => $descricao,
            ':usuario' => $usuario,
            ':valor' => $valor,
            ':status' => strtoupper($status)
        ]);
    }

    public static function getAll(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM logs_auditoria ORDER BY data_hora DESC");
        return $stmt->fetchAll();
    }

    // Regras RN-AU-003 e RNF-009: Os logs de auditoria são IMUTÁVEIS de forma absoluta.
    // Não existem funções de exclusão (delete) ou atualização (update) aqui.
    public static function update(): void {
        throw new Exception("Operação Proibida: Os registros de auditoria são absolutamente imutáveis (Erro 403).");
    }

    public static function delete(): void {
        throw new Exception("Operação Proibida: Os registros de auditoria são absolutamente imutáveis (Erro 403).");
    }
}
