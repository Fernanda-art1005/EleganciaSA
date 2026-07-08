<?php
namespace App\Controllers;

use App\Models\Produto;
use App\Models\Cliente;
use App\Models\Venda;
use App\Models\Auditoria;
use Exception;

class PDVController {
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

        if (!$_SESSION['perms']['caixa']) {
            header('Location: ' . $this->base_path . '/?erro=' . urlencode('Acesso negado ao Caixa.'));
            exit();
        }
    }

    public function index(): void {
        // Busca produtos disponíveis em estoque (quantidade > 0) para o PDV (RF-CX-001)
        $produtos = Produto::getAll();
        $clientes = Cliente::getAll();
        $vendas = Venda::getAll();

        // Alíquota de imposto padrão (Regra RN-FI-005, e.g., 5%)
        $aliquotaImposto = 0.05;

        $this->render('pdv/index', [
            'produtos' => $produtos,
            'clientes' => $clientes,
            'vendas' => $vendas,
            'aliquotaImposto' => $aliquotaImposto
        ]);
    }

    // Exclui/cancela uma venda faturada (Estorna estoque e deleta transações)
    public function delete(): void {
        $id = !empty($_REQUEST['id_venda']) ? (int)$_REQUEST['id_venda'] : null;

        if ($id === null) {
            header('Location: ' . $this->base_path . '/pdv?erro=' . urlencode('Venda inválida ou ID ausente.'));
            exit();
        }

        try {
            $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
            Venda::deleteSale($id, $operador);
            header('Location: ' . $this->base_path . '/pdv?sucesso=' . urlencode("Venda PDV #$id cancelada com sucesso! O estoque foi devolvido e a transação financeira estornada."));
        } catch (Exception $e) {
            header('Location: ' . $this->base_path . '/pdv?erro=' . urlencode('Falha ao cancelar a venda: ' . $e->getMessage()));
        }
        exit();
    }

    // Processa a conclusão de uma venda (RF-CX-010)
    public function checkout(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['sucesso' => false, 'erro' => 'Método de requisição inválido.']);
            exit();
        }

        try {
            // Recebe dados enviados como JSON
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);

            if (!$data) {
                // Tenta formulário padrão caso não seja JSON
                $data = $_POST;
            }

            if (empty($data['items'])) {
                throw new Exception("Nenhum item adicionado ao pedido.");
            }

            $id_cliente = !empty($data['id_cliente']) ? (int)$data['id_cliente'] : null;
            $forma_pagamento = strtoupper($data['forma_pagamento'] ?? ''); // DEBITO, CREDITO_LOJA, PIX, CARTAO
            
            // Valida formas de pagamento
            $formasValidas = ['DEBITO', 'CREDITO_LOJA', 'PIX', 'CARTAO'];
            if (!in_array($forma_pagamento, $formasValidas)) {
                throw new Exception("Forma de pagamento inválida ou não selecionada.");
            }

            // Calcula subtotal, imposto e total (Regra RF-CX-004 e RN-FI-005)
            $subtotal = 0.00;
            $itemsProcessados = [];
            foreach ($data['items'] as $item) {
                $id_produto = (int)$item['id_produto'];
                $quantidade = (int)$item['quantidade'];

                if ($quantidade <= 0) {
                    continue;
                }

                $produto = Produto::findById($id_produto);
                if (!$produto) {
                    throw new Exception("Produto ID $id_produto não encontrado.");
                }

                $subtotal += $produto['preco'] * $quantidade;
                $itemsProcessados[] = [
                    'id_produto' => $id_produto,
                    'quantidade' => $quantidade,
                    'preco_unitario' => (float)$produto['preco']
                ];
            }

            if (empty($itemsProcessados)) {
                throw new Exception("Pedido não possui itens válidos.");
            }

            $aliquotaImposto = 0.05; // 5%
            $imposto = $subtotal * $aliquotaImposto;
            $total = $subtotal + $imposto;

            // Cria a venda no banco utilizando transação ACID
            $id_venda = Venda::createSale([
                'id_cliente' => $id_cliente,
                'id_usuario' => $_SESSION['user_id'],
                'forma_pagamento' => $forma_pagamento,
                'subtotal' => $subtotal,
                'imposto' => $imposto,
                'total' => $total,
                'items' => $itemsProcessados
            ]);

            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Venda realizada com sucesso!',
                'id_venda' => $id_venda
            ]);
            exit();

        } catch (Exception $e) {
            echo json_encode([
                'sucesso' => false,
                'erro' => $e->getMessage()
            ]);
            exit();
        }
    }

    // API JSON de produtos para busca dinâmica em tempo real (RF-CX-002)
    public function searchProducts(): void {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        
        // Se a busca tem menos que 3 caracteres, retorna vazio para otimizar conforme RNF-003
        if (strlen($q) < 3) {
            $produtos = Produto::getAll();
        } else {
            $produtos = Produto::search($q);
        }

        echo json_encode($produtos);
        exit();
    }

    // API JSON de clientes para busca dinâmica em tempo real (RF-CL-002)
    public function searchClients(): void {
        header('Content-Type: application/json');
        $q = trim($_GET['q'] ?? '');
        
        if (empty($q)) {
            $clientes = Cliente::getAll();
        } else {
            $clientes = Cliente::search($q);
        }

        echo json_encode($clientes);
        exit();
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        require dirname(__DIR__) . '/views/layout/header.php';
        require dirname(__DIR__) . '/views/' . $view . '.php';
        require dirname(__DIR__) . '/views/layout/footer.php';
    }
}
