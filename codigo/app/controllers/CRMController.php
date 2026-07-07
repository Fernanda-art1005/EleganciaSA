<?php
namespace App\Controllers;

use App\Models\Cliente;
use App\Models\Transacao;
use App\Models\Auditoria;
use App\Config\Database;
use Exception;

class CRMController {
    private string $base_path;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->base_path = defined('BASE_PATH') ? BASE_PATH : '';

        if (empty($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        if (empty($_SESSION['perms']['crm'])) {
            $this->redirect('/?erro=' . urlencode('Acesso negado ao módulo CRM.'));
        }
    }

    public function index(): void {
        $q = trim($_GET['q'] ?? '');

        $clientes = empty($q)
            ? Cliente::getAll()
            : Cliente::search($q);

        $clienteHistorico = null;
        $compras = [];

        $id = isset($_GET['historico_id']) ? (int)$_GET['historico_id'] : null;

        if ($id) {
            $clienteHistorico = Cliente::findById($id);
            if ($clienteHistorico) {
                $compras = Cliente::getPurchaseHistory($id);
            }
        }

        $this->render('crm/index', compact('clientes','q','clienteHistorico','compras'));
    }

    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/crm');
        }

        $id = !empty($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : null;

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');

        $limite = (float) str_replace(',', '.', $_POST['limite_credito'] ?? 0);
        $saldo  = (float) str_replace(',', '.', $_POST['saldo_devedor'] ?? 0);

        if ($nome === '') {
            $this->redirect('/crm?erro=' . urlencode('Nome obrigatório.'));
        }

        if ($limite < 0 || $saldo < 0) {
            $this->redirect('/crm?erro=' . urlencode('Valores inválidos.'));
        }

        // proteção limite crédito
        if ($id && $_SESSION['nivel_acesso'] !== 'ADMIN') {
            $old = Cliente::findById($id);
            if ($old && (float)$old['limite_credito'] !== $limite) {
                $this->redirect('/crm?erro=' . urlencode('Somente ADMIN pode alterar limite.'));
            }
        }

        $data = [
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
            'limite_credito' => $limite,
            'saldo_devedor' => $saldo
        ];

        $op = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";

        if (!$id) {
            Cliente::create($data);

            Auditoria::log(
                'CLIENTE_CREATE',
                "Criou cliente {$nome}",
                $op,
                'N/A',
                'SUCESSO'
            );

            $this->redirect('/crm?sucesso=' . urlencode('Cliente criado.'));
        }

        $old = Cliente::findById($id);

        Cliente::update($id, $data);

        Auditoria::log(
            'CLIENTE_UPDATE',
            "Atualizou cliente #{$id}",
            $op,
            'N/A',
            'SUCESSO'
        );

        $this->redirect('/crm?sucesso=' . urlencode('Cliente atualizado.'));
    }

    public function receive(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/crm');
        }

        $id = (int)($_POST['id_cliente_pagamento'] ?? 0);
        $valor = (float) str_replace(',', '.', $_POST['valor_pagamento'] ?? 0);

        if ($id <= 0 || $valor <= 0) {
            $this->redirect('/crm?erro=' . urlencode('Dados inválidos.'));
        }

        $cliente = Cliente::findById($id);

        if (!$cliente) {
            $this->redirect('/crm?erro=' . urlencode('Cliente não encontrado.'));
        }

        if ((float)$cliente['saldo_devedor'] <= 0) {
            $this->redirect('/crm?erro=' . urlencode('Sem saldo devedor.'));
        }

        Cliente::receivePayment($id, $valor);

        Transacao::create([
            'descricao' => "Recebimento crédito {$cliente['nome']}",
            'tipo' => 'RECEITA',
            'origem' => 'MANUAL',
            'valor' => $valor,
            'status' => 'CONCLUIDO'
        ]);

        $this->redirect('/crm?sucesso=' . urlencode('Pagamento processado.'));
    }

    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/crm');
        }

        $id = (int)($_POST['id_cliente'] ?? 0);

        if (!$id) {
            $this->redirect('/crm?erro=' . urlencode('ID inválido.'));
        }

        $cliente = Cliente::findById($id);

        if (!$cliente) {
            $this->redirect('/crm?erro=' . urlencode('Cliente não existe.'));
        }

        if ((float)$cliente['saldo_devedor'] > 0) {
            $this->redirect('/crm?erro=' . urlencode('Cliente possui débito.'));
        }

        Cliente::delete($id);

        $this->redirect('/crm?sucesso=' . urlencode('Cliente removido.'));
    }

    private function redirect(string $path): void {
        header('Location: ' . $this->base_path . $path);
        exit();
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        require dirname(__DIR__) . "/views/layout/header.php";
        require dirname(__DIR__) . "/views/{$view}.php";
        require dirname(__DIR__) . "/views/layout/footer.php";
    }
}