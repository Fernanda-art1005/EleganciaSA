<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Usuario {
    public static function findById(int $id): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
        return $user ? $user : null;
    }

    public static function findByEmail(string $email): ?array {
        $db = Database::getConnection();
        $clean = trim($email);
        
        // Se contiver palavras adicionais de cargos, limpa para pegar apenas o nome
        // Exemplo: "patricia gerente" -> "patricia", "carlos estoquista" -> "carlos"
        $nameParts = preg_split('/\s+/', $clean);
        $firstName = $nameParts[0] ?? '';
        
        // Tenta primeiro a busca exata pelo que o usuário digitou
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE LOWER(email) = LOWER(:email) OR LOWER(nome) = LOWER(:nome)");
        $stmt->execute([
            ':email' => $clean,
            ':nome' => $clean
        ]);
        $user = $stmt->fetch();
        if ($user) {
            return $user;
        }

        // Se falhou, mas temos um primeiro nome, tenta buscar por ele de forma case-insensitive
        if (!empty($firstName)) {
            $stmt = $db->prepare("SELECT * FROM usuarios WHERE LOWER(nome) = LOWER(:firstName) OR LOWER(email) LIKE LOWER(:firstMail)");
            $stmt->execute([
                ':firstName' => $firstName,
                ':firstMail' => $firstName . '%'
            ]);
            $user = $stmt->fetch();
            if ($user) {
                return $user;
            }
        }
        
        return null;
    }

    public static function create(array $data): bool {
        $db = Database::getConnection();
        $sql = "INSERT INTO usuarios (nome, email, senha_hash, nivel_acesso, status, perm_dashboard, perm_caixa, perm_estoque, perm_financeiro, perm_crm, perm_kanban, perm_relatorios, perm_equipe) 
                VALUES (:nome, :email, :senha_hash, :nivel_acesso, :status, :perm_dashboard, :perm_caixa, :perm_estoque, :perm_financeiro, :perm_crm, :perm_kanban, :perm_relatorios, :perm_equipe)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':nome' => $data['nome'],
            ':email' => $data['email'],
            ':senha_hash' => password_hash($data['senha'], PASSWORD_BCRYPT, ['cost' => 12]),
            ':nivel_acesso' => $data['nivel_acesso'] ?? 'CAIXA',
            ':status' => $data['status'] ?? 'ATIVO',
            ':perm_dashboard' => $data['perm_dashboard'] ?? 1,
            ':perm_caixa' => $data['perm_caixa'] ?? 1,
            ':perm_estoque' => $data['perm_estoque'] ?? 1,
            ':perm_financeiro' => $data['perm_financeiro'] ?? ($data['nivel_acesso'] === 'ADMIN' ? 1 : 0),
            ':perm_crm' => $data['perm_crm'] ?? 1,
            ':perm_kanban' => $data['perm_kanban'] ?? 1,
            ':perm_relatorios' => $data['perm_relatorios'] ?? ($data['nivel_acesso'] === 'ADMIN' ? 1 : 0),
            ':perm_equipe' => $data['perm_equipe'] ?? ($data['nivel_acesso'] === 'ADMIN' ? 1 : 0),
        ]);
    }

    public static function updatePermissions(int $id, array $perms): bool {
        $db = Database::getConnection();
        $sql = "UPDATE usuarios SET 
                    nivel_acesso = :nivel_acesso,
                    status = :status,
                    perm_dashboard = :perm_dashboard, 
                    perm_caixa = :perm_caixa, 
                    perm_estoque = :perm_estoque, 
                    perm_financeiro = :perm_financeiro, 
                    perm_crm = :perm_crm, 
                    perm_kanban = :perm_kanban, 
                    perm_relatorios = :perm_relatorios, 
                    perm_equipe = :perm_equipe 
                WHERE id_usuario = :id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            ':nivel_acesso' => $perms['nivel_acesso'],
            ':status' => $perms['status'] ?? 'ATIVO',
            ':perm_dashboard' => $perms['perm_dashboard'] ?? 0,
            ':perm_caixa' => $perms['perm_caixa'] ?? 0,
            ':perm_estoque' => $perms['perm_estoque'] ?? 0,
            ':perm_financeiro' => $perms['perm_financeiro'] ?? 0,
            ':perm_crm' => $perms['perm_crm'] ?? 0,
            ':perm_kanban' => $perms['perm_kanban'] ?? 0,
            ':perm_relatorios' => $perms['perm_relatorios'] ?? 0,
            ':perm_equipe' => $perms['perm_equipe'] ?? 0,
            ':id' => $id
        ]);
    }

    public static function delete(int $id): bool {
        $db = Database::getConnection();
        // Garante que não removemos o último administrador ativo (regra RN-AC-005)
        $user = self::findById($id);
        if ($user && $user['nivel_acesso'] === 'ADMIN') {
            $stmt = $db->query("SELECT COUNT(*) as count FROM usuarios WHERE nivel_acesso = 'ADMIN' AND status = 'ATIVO'");
            $res = $stmt->fetch();
            if ($res && $res['count'] <= 1) {
                return false; // Bloqueia exclusão
            }
        }
        
        // Remove referências em tarefas do Kanban
        $stmtT = $db->prepare("UPDATE kanban_tarefas SET id_responsavel = NULL WHERE id_responsavel = :id");
        $stmtT->execute([':id' => $id]);
        
        // Remove referências de vendas e faturamentos associados ao usuário para permitir exclusão física
        $stmtVendas = $db->prepare("SELECT id_venda FROM vendas WHERE id_usuario = :id");
        $stmtVendas->execute([':id' => $id]);
        $vendas = $stmtVendas->fetchAll();
        foreach ($vendas as $v) {
            $stmtI = $db->prepare("DELETE FROM itens_venda WHERE id_venda = :id_venda");
            $stmtI->execute([':id_venda' => $v['id_venda']]);
            $stmtTr = $db->prepare("DELETE FROM transacoes WHERE id_venda = :id_venda");
            $stmtTr->execute([':id_venda' => $v['id_venda']]);
            $stmtV = $db->prepare("DELETE FROM vendas WHERE id_venda = :id_venda");
            $stmtV->execute([':id_venda' => $v['id_venda']]);
        }

        $stmt = $db->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
        return $stmt->execute([':id' => $id]);
    }

    public static function getAll(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT u.*, 
                                   COALESCE(SUM(v.total), 0) as total_vendas,
                                   COALESCE(SUM(v.total) * 0.05, 0) as comissao_acumulada
                            FROM usuarios u
                            LEFT JOIN vendas v ON u.id_usuario = v.id_usuario
                            GROUP BY u.id_usuario
                            ORDER BY u.nome ASC");
        return $stmt->fetchAll();
    }

    // Convites de Equipe
    public static function createInvite(string $email, string $perfil): string {
        $db = Database::getConnection();
        $token = bin2hex(random_bytes(16));
        $stmt = $db->prepare("INSERT INTO convites_equipe (email, perfil, token, status) VALUES (:email, :perfil, :token, 'PENDENTE')");
        $stmt->execute([
            ':email' => $email,
            ':perfil' => $perfil,
            ':token' => $token
        ]);
        return $token;
    }

    public static function getInvites(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM convites_equipe ORDER BY data_criacao DESC");
        return $stmt->fetchAll();
    }

    public static function getInviteByToken(string $token): ?array {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM convites_equipe WHERE token = :token AND status = 'PENDENTE'");
        $stmt->execute([':token' => $token]);
        $invite = $stmt->fetch();
        return $invite ? $invite : null;
    }

    public static function acceptInvite(string $token): bool {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE convites_equipe SET status = 'ACEITO' WHERE token = :token");
        return $stmt->execute([':token' => $token]);
    }
}
