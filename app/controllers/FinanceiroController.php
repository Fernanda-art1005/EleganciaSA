<?php
namespace App\Controllers;

use App\Models\Transacao;
use App\Models\Auditoria;
use Exception;

class FinanceiroController {
    private string $base_path;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->base_path = defined('BASE_PATH') ? BASE_PATH : '';

        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $this->base_path . '/login');
            exit();
        }

        if (!$_SESSION['perms']['financeiro']) {
            header('Location: ' . $this->base_path . '/?erro=' . urlencode('Acesso negado ao módulo Financeiro.'));
            exit();
        }
    }

    public function index(): void {
        // Filtros (RF-FI-006)
        $filters = [
            'tipo' => $_GET['tipo'] ?? '',
            'status' => $_GET['status'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? ''
        ];

        $transacoes = Transacao::getAll($filters);

        // Métricas financeiras (Regras RN-FI-001 / RF-FI-002 / RF-FI-003)
        $saldoTotal = Transacao::getNetBalance();
        $totalVendas = Transacao::getTotalSalesAmount();
        $creditoPendente = Transacao::getPendingCreditsAmount();

        $this->render('financeiro/index', [
            'transacoes' => $transacoes,
            'saldoTotal' => $saldoTotal,
            'totalVendas' => $totalVendas,
            'creditoPendente' => $creditoPendente,
            'filters' => $filters
        ]);
    }

    // Cria ou edita uma transação financeira manual (RF-FI-004 / RF-FI-005)
    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->base_path . '/financeiro');
            exit();
        }

        $id = !empty($_POST['id_transacao']) ? (int)$_POST['id_transacao'] : null;
        $descricao = trim($_POST['descricao'] ?? '');
        $tipo = strtoupper($_POST['tipo'] ?? ''); // RECEITA, DESPESA
        $valor = (float)str_replace(',', '.', $_POST['valor'] ?? '0');
        $status = strtoupper($_POST['status'] ?? ''); // PENDENTE, CONCLUIDO, SAIDA

        $erro = '';
        $sucesso = '';

        if (empty($descricao)) {
            $erro = 'A descrição da transação é obrigatória.';
        } elseif (!in_array($tipo, ['RECEITA', 'DESPESA'])) {
            $erro = 'Selecione um tipo válido de transação.';
        } elseif ($valor <= 0) {
            $erro = 'O valor da transação deve ser positivo.';
        } else {
            // Se for despesa, força o status correto
            if ($tipo === 'DESPESA') {
                $status = 'SAIDA';
            } elseif (empty($status)) {
                $status = 'CONCLUIDO';
            }

            $data = [
                'descricao' => $descricao,
                'tipo' => $tipo,
                'origem' => 'MANUAL',
                'valor' => $valor,
                'status' => $status
            ];

            $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";

            if ($id === null) {
                // Inserção manual (RF-FI-004 / RF-FI-005)
                $created = Transacao::create($data);
                if ($created) {
                    $sucesso = "Transação '" . $descricao . "' registrada com sucesso!";
                    Auditoria::log('FINANCEIRO_REC', "Registrou transação manual ($tipo): $descricao | Valor: R$ " . number_format($valor, 2, ',', '.'), $operador, 'R$ ' . number_format($valor, 2, '.', ''), 'SUCESSO');
                } else {
                    $erro = 'Falha ao registrar a transação no financeiro.';
                }
            } else {
                // Edição de transação existente (RF-FI-007)
                $oldTrans = Transacao::findById($id);

                if ($oldTrans) {
                    // Regra RN-FI-004: Edições de valores originados por vendas concluídas exigem autorização do admin
                    if ($oldTrans['origem'] === 'VENDA' && $_SESSION['nivel_acesso'] !== 'ADMIN') {
                        $erro = 'Permissão negada. Alterações de valores em vendas já concluídas só podem ser efetuadas pelo perfil Administrador.';
                    } else {
                        $updated = Transacao::update($id, $data);
                        if ($updated) {
                            $sucesso = "Transação atualizada com sucesso!";
                            Auditoria::log('FINANCEIRO_EDIT', "Editou a transação ID #$id: $descricao | Novo valor: R$ " . number_format($valor, 2, ',', '.'), $operador, 'R$ ' . number_format($valor, 2, '.', ''), 'SUCESSO');
                        } else {
                            $erro = 'Falha ao salvar alterações da transação.';
                        }
                    }
                } else {
                    $erro = 'Transação não encontrada.';
                }
            }
        }

        if (!empty($erro)) {
            header('Location: ' . $this->base_path . '/financeiro?erro=' . urlencode($erro));
        } else {
            header('Location: ' . $this->base_path . '/financeiro?sucesso=' . urlencode($sucesso));
        }
        exit();
    }

    // Exclui uma transação manual (RF-FI-008)
    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Location: ' . $this->base_path . '/financeiro');
            exit();
        }

        $id = !empty($_REQUEST['id_transacao']) ? (int)$_REQUEST['id_transacao'] : null;

        if ($id === null) {
            header('Location: ' . $this->base_path . '/financeiro?erro=' . urlencode('Transação inválida ou ID ausente.'));
            exit();
        }

        $trans = Transacao::findById($id);
        if (!$trans) {
            header('Location: ' . $this->base_path . '/financeiro?erro=' . urlencode('Lançamento não localizado no financeiro.'));
            exit();
        }

        $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";

        $deleted = Transacao::delete($id);
        if ($deleted) {
            $sucesso = "Lançamento '" . $trans['descricao'] . "' excluído com sucesso do fluxo de caixa.";
            Auditoria::log('FINANCEIRO_DEL', "Excluiu lançamento ID #$id: " . $trans['descricao'], $operador, 'R$ ' . number_format($trans['valor'], 2, '.', ''), 'SUCESSO');
            header('Location: ' . $this->base_path . '/financeiro?sucesso=' . urlencode($sucesso));
        } else {
            header('Location: ' . $this->base_path . '/financeiro?erro=' . urlencode('Falha ao excluir o lançamento do banco de dados.'));
        }
        exit();
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        require dirname(__DIR__) . '/views/layout/header.php';
        require dirname(__DIR__) . '/views/' . $view . '.php';
        require dirname(__DIR__) . '/views/layout/footer.php';
    }
}
