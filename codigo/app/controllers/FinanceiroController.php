<?php
namespace App\Controllers;

use App\Models\Transacao;
use App\Models\Auditoria;

class FinanceiroController {
    private string $base_path;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->base_path = BASE_PATH ?? '';

        $this->auth();
        $this->perm();
    }

    private function auth(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    private function perm(): void {
        if (empty($_SESSION['perms']['financeiro'])) {
            $this->redirect('/?erro=' . urlencode('Acesso negado ao Financeiro.'));
        }
    }

    /* =========================
       LISTAGEM + KPIs
    ========================== */
    public function index(): void {
        $filters = [
            'tipo' => $_GET['tipo'] ?? '',
            'status' => $_GET['status'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? ''
        ];

        $this->render('financeiro/index', [
            'transacoes' => Transacao::getAll($filters),
            'saldoTotal' => Transacao::getNetBalance(),
            'totalVendas' => Transacao::getTotalSalesAmount(),
            'creditoPendente' => Transacao::getPendingCreditsAmount(),
            'filters' => $filters
        ]);
    }

    /* =========================
       CREATE / UPDATE
    ========================== */
    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/financeiro');
        }

        $id = (int)($_POST['id_transacao'] ?? 0) ?: null;

        $descricao = trim($_POST['descricao'] ?? '');
        $tipo = strtoupper($_POST['tipo'] ?? '');
        $valor = $this->toFloat($_POST['valor'] ?? 0);
        $status = strtoupper($_POST['status'] ?? '');

        if ($descricao === '') {
            $this->redirect('/financeiro?erro=' . urlencode('Descrição obrigatória'));
        }

        if (!in_array($tipo, ['RECEITA', 'DESPESA'])) {
            $this->redirect('/financeiro?erro=' . urlencode('Tipo inválido'));
        }

        if ($valor <= 0) {
            $this->redirect('/financeiro?erro=' . urlencode('Valor inválido'));
        }

        // regra consistente
        $status = $tipo === 'DESPESA' ? 'SAIDA' : ($status ?: 'CONCLUIDO');

        $data = [
            'descricao' => $descricao,
            'tipo' => $tipo,
            'origem' => 'MANUAL',
            'valor' => $valor,
            'status' => $status
        ];

        $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";

        /* CREATE */
        if ($id === null) {
            if (!Transacao::create($data)) {
                $this->redirect('/financeiro?erro=erro-create');
            }

            Auditoria::log(
                'FIN_CREATE',
                "Criou transação: $descricao",
                $operador,
                (string)$valor,
                'SUCESSO'
            );

            $this->redirect('/financeiro?sucesso=ok');
        }

        /* UPDATE */
        $old = Transacao::findById($id);

        if (!$old) {
            $this->redirect('/financeiro?erro=nao-encontrado');
        }

        // regra de proteção de venda
        if ($old['origem'] === 'VENDA' && $_SESSION['nivel_acesso'] !== 'ADMIN') {
            $this->redirect('/financeiro?erro=sem-permissao');
        }

        if (!Transacao::update($id, $data)) {
            $this->redirect('/financeiro?erro=erro-update');
        }

        $log = "Editou transação #$id: $descricao";

        if ((float)$old['valor'] !== $valor) {
            $log .= " | {$old['valor']} → $valor";
        }

        Auditoria::log(
            'FIN_UPDATE',
            $log,
            $operador,
            (string)$valor,
            'SUCESSO'
        );

        $this->redirect('/financeiro?sucesso=atualizado');
    }

    /* =========================
       DELETE (CORRIGIDO)
    ========================== */
    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/financeiro');
        }

        $id = (int)($_POST['id_transacao'] ?? 0);

        if ($id <= 0) {
            $this->redirect('/financeiro?erro=id-invalido');
        }

        $trans = Transacao::findById($id);

        if (!$trans) {
            $this->redirect('/financeiro?erro=nao-encontrado');
        }

        $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";

        if ($trans['origem'] === 'VENDA') {
            Auditoria::log(
                'FIN_DELETE_BLOCK',
                "Tentativa bloqueada de excluir venda #$id",
                $operador,
                (string)$trans['valor'],
                'BLOQUEADO'
            );

            $this->redirect('/financeiro?erro=bloqueado');
        }

        if (!Transacao::delete($id)) {
            $this->redirect('/financeiro?erro=erro-delete');
        }

        Auditoria::log(
            'FIN_DELETE',
            "Removeu transação #$id",
            $operador,
            (string)$trans['valor'],
            'SUCESSO'
        );

        $this->redirect('/financeiro?sucesso=excluido');
    }

    /* =========================
       HELPERS
    ========================== */
    private function toFloat($v): float {
        return (float)str_replace(',', '.', $v);
    }

    private function redirect(string $url): void {
        header("Location: {$this->base_path}{$url}");
        exit();
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        require dirname(__DIR__) . '/views/layout/header.php';
        require dirname(__DIR__) . '/views/' . $view . '.php';
        require dirname(__DIR__) . '/views/layout/footer.php';
    }
}