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

        $this->base_path = BASE_PATH ?? '';

        if (empty($_SESSION['user_id'])) {
            header("Location: {$this->base_path}/login");
            exit;
        }

        if (empty($_SESSION['perms']['caixa'])) {
            header("Location: {$this->base_path}/?erro=Acesso negado ao Caixa");
            exit;
        }
    }

    public function index(): void {
        $produtos = Produto::getAll();
        $clientes = Cliente::getAll();

        $this->render('pdv/index', [
            'produtos' => $produtos,
            'clientes' => $clientes,
            'aliquotaImposto' => 0.05
        ]);
    }

    public function checkout(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['sucesso' => false, 'erro' => 'Método inválido']);
            exit;
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!is_array($input)) {
                $input = $_POST;
            }

            if (empty($input['items']) || !is_array($input['items'])) {
                throw new Exception("Carrinho vazio ou inválido.");
            }

            $cliente = isset($input['id_cliente'])
                ? (int)$input['id_cliente']
                : null;

            $forma = strtoupper(trim($input['forma_pagamento'] ?? ''));

            $formasValidas = ['DEBITO', 'CREDITO_LOJA', 'PIX', 'CARTAO'];

            if (!in_array($forma, $formasValidas, true)) {
                throw new Exception("Forma de pagamento inválida.");
            }

            $subtotal = 0.0;
            $items = [];

            foreach ($input['items'] as $item) {
                $id = (int)($item['id_produto'] ?? 0);
                $qtd = (int)($item['quantidade'] ?? 0);

                if ($id <= 0 || $qtd <= 0) continue;

                $produto = Produto::findById($id);

                if (!$produto) {
                    throw new Exception("Produto $id não encontrado.");
                }

                $subtotal += (float)$produto['preco'] * $qtd;

                $items[] = [
                    'id_produto' => $id,
                    'quantidade' => $qtd,
                    'preco_unitario' => (float)$produto['preco']
                ];
            }

            if (!$items) {
                throw new Exception("Nenhum item válido no pedido.");
            }

            $imposto = $subtotal * 0.05;
            $total = $subtotal + $imposto;

            $idVenda = Venda::createSale([
                'id_cliente' => $cliente,
                'id_usuario' => $_SESSION['user_id'],
                'forma_pagamento' => $forma,
                'subtotal' => $subtotal,
                'imposto' => $imposto,
                'total' => $total,
                'items' => $items
            ]);

            echo json_encode([
                'sucesso' => true,
                'mensagem' => 'Venda realizada com sucesso',
                'id_venda' => $idVenda
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode([
                'sucesso' => false,
                'erro' => $e->getMessage()
            ]);
            exit;
        }
    }

    public function searchProducts(): void {
        header('Content-Type: application/json');

        $q = trim($_GET['q'] ?? '');

        $produtos = (strlen($q) < 3)
            ? Produto::getAll()
            : Produto::search($q);

        echo json_encode($produtos);
        exit;
    }

    public function searchClients(): void {
        header('Content-Type: application/json');

        $q = trim($_GET['q'] ?? '');

        $clientes = empty($q)
            ? Cliente::getAll()
            : Cliente::search($q);

        echo json_encode($clientes);
        exit;
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        require dirname(__DIR__) . "/views/layout/header.php";
        require dirname(__DIR__) . "/views/$view.php";
        require dirname(__DIR__) . "/views/layout/footer.php";
    }
}