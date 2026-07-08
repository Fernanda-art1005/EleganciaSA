<?php
namespace App\Controllers;

use App\Models\Produto;
use App\Models\Cliente;
use App\Models\Venda;
use App\Models\Transacao;
use App\Models\Auditoria;

class DashboardController {
    private string $base_path;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->base_path = defined('BASE_PATH') ? BASE_PATH : '';

        // Proteção de Sessão e Controle de Acesso (RBAC) (Regras RNF-006, RNF-007, RN-AC-002/003)
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->base_path . '/login');
            exit();
        }

        if (!$_SESSION['perms']['dashboard']) {
            // Se não tem acesso ao dashboard, redireciona pelo perfil
            if ($_SESSION['nivel_acesso'] === 'CAIXA') {
                header('Location: ' . $this->base_path . '/pdv');
            } elseif ($_SESSION['nivel_acesso'] === 'ESTOQUE') {
                header('Location: ' . $this->base_path . '/estoque');
            } else {
                header('Location: ' . $this->base_path . '/login');
            }
            exit();
        }
    }

    public function index(): void {
        // 1. KPIs
        $receitaTotal = Transacao::getNetBalance(); // Total balance (concluded revenues - despesas)
        
        $db = \App\Config\Database::getConnection();
        
        // Contagem de transações de vendas (RF-DA-002)
        $stmtSalesCount = $db->query("SELECT COUNT(*) as count FROM vendas");
        $resCount = $stmtSalesCount->fetch();
        $totalVendas = $resCount ? (int)$resCount['count'] : 0;

        // Produtos ativos (quantidade > 0) (RF-DA-003)
        $stmtActiveProducts = $db->query("SELECT COUNT(*) as count FROM produtos WHERE quantidade > 0");
        $resActive = $stmtActiveProducts->fetch();
        $produtosAtivos = $resActive ? (int)$resActive['count'] : 0;

        // Crédito pendente em aberto de todos os clientes (RF-DA-004 / RF-FI-003)
        $stmtPendingCredit = $db->query("SELECT SUM(saldo_devedor) as total FROM clientes WHERE saldo_devedor > 0");
        $resPending = $stmtPendingCredit->fetch();
        $creditoPendente = $resPending && $resPending['total'] ? (float)$resPending['total'] : 0.00;

        // 2. Gráfico de Receita Mensal (Janeiro - Dezembro do ano atual) (RF-DA-005)
        $anoAtual = date('Y');
        $receitaMensal = array_fill(1, 12, 0.00);

        // Query agrupando as receitas por mês no ano atual (compatível com MySQL e SQLite)
        $dbType = \App\Config\Database::getDbType();
        if ($dbType === 'sqlite') {
            $stmtChart = $db->prepare("SELECT CAST(strftime('%m', data_transacao) AS INTEGER) as mes, SUM(valor) as total 
                                       FROM transacoes 
                                       WHERE tipo = 'RECEITA' AND status = 'CONCLUIDO' AND strftime('%Y', data_transacao) = :ano
                                       GROUP BY mes");
        } else {
            $stmtChart = $db->prepare("SELECT MONTH(data_transacao) as mes, SUM(valor) as total 
                                       FROM transacoes 
                                       WHERE tipo = 'RECEITA' AND status = 'CONCLUIDO' AND YEAR(data_transacao) = :ano
                                       GROUP BY mes");
        }
        
        $stmtChart->execute([':ano' => $anoAtual]);
        $rows = $stmtChart->fetchAll();
        foreach ($rows as $row) {
            $mes = (int)$row['mes'];
            $receitaMensal[$mes] = (float)$row['total'];
        }

        // 3. Alertas de estoque baixo (RF-DA-006)
        $alertasEstoque = Produto::getLowStock();

        // 4. Clientes com créditos devedores/vencidos (RF-DA-008)
        $stmtOverdue = $db->query("SELECT * FROM clientes WHERE saldo_devedor > 0 ORDER BY saldo_devedor DESC");
        $clientesDevedores = $stmtOverdue->fetchAll();

        // Renderiza a view com os dados
        $this->render('dashboard/index', [
            'receitaTotal' => $receitaTotal,
            'totalVendas' => $totalVendas,
            'produtosAtivos' => $produtosAtivos,
            'creditoPendente' => $creditoPendente,
            'receitaMensal' => $receitaMensal,
            'alertasEstoque' => $alertasEstoque,
            'clientesDevedores' => $clientesDevedores,
            'anoAtual' => $anoAtual
        ]);
    }

    // Retorna os KPIs em JSON para requisições assíncronas (se houver atualização em segundo plano per RNF-001/RNF-003)
    public function apiKpis(): void {
        header('Content-Type: application/json');
        
        $db = \App\Config\Database::getConnection();
        
        // Receita Total
        $receitaTotal = Transacao::getNetBalance();

        // Ativos Alocados (Ex: total de vendas realizadas)
        $stmtSales = $db->query("SELECT COUNT(*) as count FROM vendas");
        $resSales = $stmtSales->fetch();
        $ativosAlocados = $resSales ? (int)$resSales['count'] : 0;

        // Atrasos críticos (Ex: produtos em nível de alerta de estoque mínimo)
        $stmtAlert = $db->query("SELECT COUNT(*) as count FROM produtos WHERE quantidade <= estoque_minimo");
        $resAlert = $stmtAlert->fetch();
        $ativosAtrasados = $resAlert ? (int)$resAlert['count'] : 0;

        // Porcentagem de disponibilidade do estoque (Disponíveis / Total cadastrado)
        $stmtStock = $db->query("SELECT SUM(quantidade) as total, SUM(CASE WHEN quantidade > estoque_minimo THEN quantidade ELSE 0 END) as disponivel FROM produtos");
        $resStock = $stmtStock->fetch();
        $totalEstoque = $resStock && $resStock['total'] ? (int)$resStock['total'] : 1;
        $dispEstoque = $resStock && $resStock['disponivel'] ? (int)$resStock['disponivel'] : 0;
        $taxaDisponibilidade = round(($dispEstoque / $totalEstoque) * 100, 1);

        echo json_encode([
            'taxa_disponibilidade' => $taxaDisponibilidade,
            'ativos_alocados' => $ativosAlocados,
            'ativos_atrasados' => $ativosAtrasados,
            'receita_total' => $receitaTotal
        ]);
        exit();
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        require dirname(__DIR__) . '/views/layout/header.php';
        require dirname(__DIR__) . '/views/' . $view . '.php';
        require dirname(__DIR__) . '/views/layout/footer.php';
    }
}
