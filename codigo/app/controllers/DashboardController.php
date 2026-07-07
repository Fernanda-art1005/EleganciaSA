<?php
namespace App\Controllers;

use App\Models\Produto;
use App\Models\Transacao;

class DashboardController {
    private string $base_path;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->base_path = defined('BASE_PATH') ? BASE_PATH : '';

        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        if (empty($_SESSION['perms']['dashboard'])) {
            $perfil = $_SESSION['nivel_acesso'] ?? null;

            match ($perfil) {
                'CAIXA' => $this->redirect('/pdv'),
                'ESTOQUE' => $this->redirect('/estoque'),
                default => $this->redirect('/login')
            };
        }
    }

    public function index(): void {
        $db = \App\Config\Database::getConnection();

        $receitaTotal = Transacao::getNetBalance();

        $totalVendas = $this->count($db, "vendas");

        $produtosAtivos = $this->countWhere($db, "produtos", "quantidade > 0");

        $creditoPendente = $this->sum($db, "clientes", "saldo_devedor > 0", "saldo_devedor");

        $ano = date('Y');
        $receitaMensal = $this->getReceitaMensal($db, $ano);

        $alertasEstoque = Produto::getLowStock();

        $clientesDevedores = $this->fetchAll($db,
            "SELECT * FROM clientes WHERE saldo_devedor > 0 ORDER BY saldo_devedor DESC"
        );

        $this->render('dashboard/index', [
            'receitaTotal' => $receitaTotal,
            'totalVendas' => $totalVendas,
            'produtosAtivos' => $produtosAtivos,
            'creditoPendente' => $creditoPendente,
            'receitaMensal' => $receitaMensal,
            'alertasEstoque' => $alertasEstoque,
            'clientesDevedores' => $clientesDevedores,
            'anoAtual' => $ano
        ]);
    }

    public function apiKpis(): void {
        header('Content-Type: application/json');

        $db = \App\Config\Database::getConnection();

        $receitaTotal = Transacao::getNetBalance();

        $ativosAlocados = $this->count($db, "vendas");

        $ativosAtrasados = $this->countWhere($db, "produtos", "quantidade <= estoque_minimo");

        $res = $db->query("
            SELECT 
                SUM(quantidade) as total,
                SUM(CASE WHEN quantidade > estoque_minimo THEN quantidade ELSE 0 END) as disponivel
            FROM produtos
        ")->fetch();

        $total = (int)($res['total'] ?? 1);
        $disp = (int)($res['disponivel'] ?? 0);

        echo json_encode([
            'taxa_disponibilidade' => $total > 0 ? round(($disp / $total) * 100, 1) : 0,
            'ativos_alocados' => $ativosAlocados,
            'ativos_atrasados' => $ativosAtrasados,
            'receita_total' => $receitaTotal
        ]);

        exit();
    }

    // ---------------- HELPERS ----------------

    private function count($db, string $table): int {
        return (int)$db->query("SELECT COUNT(*) as c FROM {$table}")
            ->fetch()['c'];
    }

    private function countWhere($db, string $table, string $where): int {
        return (int)$db->query("SELECT COUNT(*) as c FROM {$table} WHERE {$where}")
            ->fetch()['c'];
    }

    private function sum($db, string $table, string $where, string $field): float {
        return (float)$db->query("SELECT SUM({$field}) as s FROM {$table} WHERE {$where}")
            ->fetch()['s'];
    }

    private function fetchAll($db, string $sql): array {
        return $db->query($sql)->fetchAll();
    }

    private function getReceitaMensal($db, int $ano): array {
        $receita = array_fill(1, 12, 0.0);

        $stmt = $db->prepare("
            SELECT MONTH(data_transacao) mes, SUM(valor) total
            FROM transacoes
            WHERE tipo='RECEITA'
            AND status='CONCLUIDO'
            AND YEAR(data_transacao)=:ano
            GROUP BY mes
        ");

        $stmt->execute([':ano' => $ano]);

        foreach ($stmt->fetchAll() as $row) {
            $receita[(int)$row['mes']] = (float)$row['total'];
        }

        return $receita;
    }

    private function redirect(string $path): void {
        header("Location: {$this->base_path}{$path}");
        exit();
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        require dirname(__DIR__) . "/views/layout/header.php";
        require dirname(__DIR__) . "/views/{$view}.php";
        require dirname(__DIR__) . "/views/layout/footer.php";
    }
}