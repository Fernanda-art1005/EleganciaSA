<?php
namespace App\Controllers;

use App\Models\Cliente;
use App\Models\Transacao;
use App\Models\Auditoria;
use Exception;

class CRMController {
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

        if (!$_SESSION['perms']['crm']) {
            header('Location: ' . $this->base_path . '/?erro=' . urlencode('Acesso negado ao módulo de Clientes (CRM).'));
            exit();
        }
    }

    public function index(): void {
        $q = trim($_GET['q'] ?? '');
        if (empty($q)) {
            $clientes = Cliente::getAll();
        } else {
            $clientes = Cliente::search($q);
        }

        // Se houver um cliente específico selecionado para visualizar histórico de compras
        $clienteHistorico = null;
        $compras = [];
        $id_historico = !empty($_GET['historico_id']) ? (int)$_GET['historico_id'] : null;
        if ($id_historico) {
            $clienteHistorico = Cliente::findById($id_historico);
            if ($clienteHistorico) {
                $compras = Cliente::getPurchaseHistory($id_historico);
            }
        }

        $this->render('crm/index', [
            'clientes' => $clientes,
            'q' => $q,
            'clienteHistorico' => $clienteHistorico,
            'compras' => $compras
        ]);
    }

    // Salva ou edita um cliente (RF-CL-003 / RF-CL-005)
    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->base_path . '/crm');
            exit();
        }

        $id = !empty($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : null;
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefone = trim($_POST['telefone'] ?? '');
        $limite_credito = (float)str_replace(',', '.', $_POST['limite_credito'] ?? '0');
        $saldo_devedor = (float)str_replace(',', '.', $_POST['saldo_devedor'] ?? '0');

        $erro = '';
        $sucesso = '';

        if (empty($nome)) {
            $erro = 'O nome do cliente é obrigatório.';
        } elseif ($limite_credito < 0 || $saldo_devedor < 0) {
            $erro = 'Os valores de limites e saldos não podem ser negativos.';
        } else {
            // Regra RN-CR-007: Apenas administradores podem modificar o limite de crédito
            if ($id !== null && $_SESSION['nivel_acesso'] !== 'ADMIN') {
                $oldClient = Cliente::findById($id);
                if ($oldClient && (float)$oldClient['limite_credito'] !== $limite_credito) {
                    $erro = 'Apenas o perfil Administrador pode alterar o limite de crédito de clientes.';
                }
            }

            if (empty($erro)) {
                $data = [
                    'nome' => $nome,
                    'email' => $email,
                    'telefone' => $telefone,
                    'limite_credito' => $limite_credito,
                    'saldo_devedor' => $saldo_devedor
                ];

                $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";

                if ($id === null) {
                    // Novo Cliente (RF-CL-003)
                    $created = Cliente::create($data);
                    if ($created) {
                        $sucesso = "Cliente '" . $nome . "' cadastrado com sucesso!";
                        Auditoria::log('CADASTRO_CLIENTE', "Cadastrou o cliente: $nome | Limite: R$ " . number_format($limite_credito, 2, ',', '.'), $operador, 'N/A', 'SUCESSO');
                    } else {
                        $erro = 'Falha ao cadastrar o cliente.';
                    }
                } else {
                    // Edição (RF-CL-005)
                    $oldClient = Cliente::findById($id);
                    
                    // Confirmação adicional se o limite for reduzido abaixo do saldo devedor atual (Regra RN-CR-007)
                    if ($limite_credito < $saldo_devedor) {
                        // Avisa ou apenas prossegue registrando no log
                    }

                    $updated = Cliente::update($id, $data);
                    if ($updated) {
                        $sucesso = "Cliente '" . $nome . "' atualizado com sucesso!";
                        $changes = "Editou o cliente ID #$id: $nome";
                        if ($oldClient) {
                            if ((float)$oldClient['limite_credito'] !== $limite_credito) {
                                $changes .= " | Limite: R$ " . number_format($oldClient['limite_credito'], 2, ',', '.') . " -> R$ " . number_format($limite_credito, 2, ',', '.');
                            }
                            if ((float)$oldClient['saldo_devedor'] !== $saldo_devedor) {
                                $changes .= " | Saldo Devedor: R$ " . number_format($oldClient['saldo_devedor'], 2, ',', '.') . " -> R$ " . number_format($saldo_devedor, 2, ',', '.');
                            }
                        }
                        Auditoria::log('EDICAO_CLIENTE', $changes, $operador, 'N/A', 'SUCESSO');
                    } else {
                        $erro = 'Falha ao atualizar o cliente.';
                    }
                }
            }
        }

        if (!empty($erro)) {
            header('Location: ' . $this->base_path . '/crm?erro=' . urlencode($erro));
        } else {
            header('Location: ' . $this->base_path . '/crm?sucesso=' . urlencode($sucesso));
        }
        exit();
    }

    // Processa recebimento de pagamento de crédito (RF-CL-007 / RF-CL-008)
    public function receive(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->base_path . '/crm');
            exit();
        }

        $id = !empty($_POST['id_cliente_pagamento']) ? (int)$_POST['id_cliente_pagamento'] : null;
        $valor = (float)str_replace(',', '.', $_POST['valor_pagamento'] ?? '0');

        if ($id === null || $valor <= 0) {
            header('Location: ' . $this->base_path . '/crm?erro=' . urlencode('Valor de pagamento ou cliente inválido.'));
            exit();
        }

        $cliente = Cliente::findById($id);
        if (!$cliente) {
            header('Location: ' . $this->base_path . '/crm?erro=' . urlencode('Cliente não encontrado.'));
            exit();
        }

        if ($cliente['saldo_devedor'] <= 0) {
            header('Location: ' . $this->base_path . '/crm?erro=' . urlencode('O cliente selecionado não possui saldo devedor em aberto.'));
            exit();
        }

        $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
        
        // Quita o saldo devedor do cliente
        $success = Cliente::receivePayment($id, $valor);
        if ($success) {
            $valorAbatido = min($cliente['saldo_devedor'], $valor);

            // Regra RN-FI-003: Quando recebe pagamento, muda o status das transações pendentes de crédito para 'CONCLUIDO'
            $db = \App\Config\Database::getConnection();
            
            // Busca se existem transações de receitas pendentes para o cliente
            // Vamos procurar as vendas do cliente que estejam gerando transações pendentes e liquidá-las
            $stmtVendas = $db->prepare("SELECT id_venda FROM vendas WHERE id_cliente = :id");
            $stmtVendas->execute([':id' => $id]);
            $vendas = $stmtVendas->fetchAll();
            foreach ($vendas as $venda) {
                Transacao::clearPendingSaleTransaction($venda['id_venda']);
            }

            // Registra uma transação de receita manual correspondente ao pagamento
            Transacao::create([
                'descricao' => "Recebimento de Crédito - Cliente: " . $cliente['nome'],
                'tipo' => 'RECEITA',
                'origem' => 'MANUAL',
                'valor' => $valorAbatido,
                'status' => 'CONCLUIDO'
            ]);

            $sucesso = "Pagamento de R$ " . number_format($valorAbatido, 2, ',', '.') . " processado com sucesso para '" . $cliente['nome'] . "'.";
            Auditoria::log('PAGAMENTO_CRM', "Recebeu pagamento de crédito do cliente ID #$id (" . $cliente['nome'] . ") no valor de R$ " . number_format($valorAbatido, 2, ',', '.'), $operador, 'R$ ' . number_format($valorAbatido, 2, '.', ''), 'SUCESSO');
            
            header('Location: ' . $this->base_path . '/crm?sucesso=' . urlencode($sucesso));
        } else {
            header('Location: ' . $this->base_path . '/crm?erro=' . urlencode('Falha ao registrar o pagamento de crédito.'));
        }
        exit();
    }

    // Exclui um cliente (RF-CL-006)
    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Location: ' . $this->base_path . '/crm');
            exit();
        }

        $id = !empty($_REQUEST['id_cliente']) ? (int)$_REQUEST['id_cliente'] : null;

        if ($id === null) {
            header('Location: ' . $this->base_path . '/crm?erro=' . urlencode('ID do cliente inválido.'));
            exit();
        }

        $cliente = Cliente::findById($id);
        if (!$cliente) {
            header('Location: ' . $this->base_path . '/crm?erro=' . urlencode('Cliente não encontrado no sistema.'));
            exit();
        }

        $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";

        $deleted = Cliente::delete($id);
        if ($deleted) {
            $sucesso = "Cliente '" . $cliente['nome'] . "' removido com sucesso.";
            Auditoria::log('EXCLUSAO_CLIENTE', "Excluiu o cliente ID #$id: " . $cliente['nome'], $operador, 'N/A', 'SUCESSO');
            header('Location: ' . $this->base_path . '/crm?sucesso=' . urlencode($sucesso));
        } else {
            header('Location: ' . $this->base_path . '/crm?erro=' . urlencode('Falha ao excluir o cliente do banco de dados.'));
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
