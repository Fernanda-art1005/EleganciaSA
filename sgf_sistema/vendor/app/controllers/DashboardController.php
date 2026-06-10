<?php
namespace App\Controllers;

use App\Config\Database;
use PDO;

class DashboardController {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function getKpis(): array {
        $totalStmt = $this->db->query("SELECT COUNT(*) AS total FROM ferramentas WHERE status_atual != 'BAIXADA'");
        $total = (int) $totalStmt->fetch()['total'];

        $dispStmt = $this->db->query("SELECT COUNT(*) AS disp FROM ferramentas WHERE status_atual = 'DISPONIVEL'");
        $disp = (int) $dispStmt->fetch()['disp'];

        $alocStmt = $this->db->query("SELECT COUNT(*) AS aloc FROM ferramentas WHERE status_atual = 'EMPRESTADA'");
        $aloc = (int) $alocStmt->fetch()['aloc'];

        $atrasStmt = $this->db->query("SELECT COUNT(*) AS atras FROM emprestimos WHERE status_emprestimo = 'ATRASADO'");
        $atras = (int) $atrasStmt->fetch()['atras'];

        $taxa = $total > 0 ? round(($disp / $total) * 100, 1) : 0;

        return [
            'taxa_disponibilidade' => $taxa,
            'ativos_alocados'      => $aloc,
            'ativos_atrasados'     => $atras,
        ];
    }
}
