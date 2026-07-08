<?php
namespace App\Controllers;

use App\Models\Produto;
use App\Models\Auditoria;
use Exception;

class EstoqueController {
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

        if (!$_SESSION['perms']['estoque']) {
            header('Location: ' . $this->base_path . '/?erro=' . urlencode('Acesso negado ao Controle de Estoque.'));
            exit();
        }
    }

    public function index(): void {
        $q = trim($_GET['q'] ?? '');
        if (empty($q)) {
            $produtos = Produto::getAll();
        } else {
            $produtos = Produto::search($q);
        }
        $this->render('estoque/index', [
            'produtos' => $produtos,
            'q' => $q
        ]);
    }

    // Salva ou atualiza um produto (RF-ES-002 / RF-ES-003)
    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->base_path . '/estoque');
            exit();
        }

        $id = !empty($_POST['id_produto']) ? (int)$_POST['id_produto'] : null;
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $preco_custo = (float)str_replace(',', '.', $_POST['preco_custo'] ?? '0');
        $preco = (float)str_replace(',', '.', $_POST['preco'] ?? '0');
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $estoque_minimo = (int)($_POST['estoque_minimo'] ?? 0);
        $imagem_url = trim($_POST['imagem_url'] ?? '');

        $erro = '';
        $sucesso = '';

        if (empty($nome)) {
            $erro = 'O nome do produto é obrigatório.';
        } elseif ($preco < 0.01) {
            $erro = 'O preço de venda do produto não pode ser inferior a R$ 0,01.'; // Regra RN-ES-005
        } elseif ($preco <= $preco_custo) {
            $erro = 'O preço de venda deve ser estritamente maior do que o preço de custo (RN-ES-002).';
        } elseif ($quantidade < 0 || $estoque_minimo < 0) {
            $erro = 'As quantidades do estoque não podem ser negativas.';
        } else {
            $data = [
                'nome' => $nome,
                'descricao' => !empty($descricao) ? $descricao : null,
                'categoria' => !empty($categoria) ? $categoria : null,
                'preco_custo' => $preco_custo,
                'preco' => $preco,
                'quantidade' => $quantidade,
                'estoque_minimo' => $estoque_minimo,
                'imagem_url' => !empty($imagem_url) ? $imagem_url : null
            ];

            $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";

            if ($id === null) {
                // Cadastro (RF-ES-002)
                $created = Produto::create($data);
                if ($created) {
                    $sucesso = "Produto '" . $nome . "' cadastrado com sucesso!";
                    Auditoria::log('CADASTRO_PRODUTO', "Cadastrou o produto: $nome | Quantidade Inicial: $quantidade | Preço: R$ " . number_format($preco, 2, ',', '.'), $operador, 'R$ ' . number_format($preco, 2, '.', ''), 'SUCESSO');
                } else {
                    $erro = 'Falha ao cadastrar o produto no banco de dados.';
                }
            } else {
                // Edição (RF-ES-003)
                $oldProduct = Produto::findById($id);
                $updated = Produto::update($id, $data);
                if ($updated) {
                    $sucesso = "Produto '" . $nome . "' atualizado com sucesso!";
                    
                    // Compara e registra no log de auditoria
                    $descChanges = "Atualizou o produto ID #$id: " . $nome;
                    if ($oldProduct) {
                        if ((int)$oldProduct['quantidade'] !== $quantidade) {
                            $descChanges .= " | Quantidade: " . $oldProduct['quantidade'] . " -> " . $quantidade;
                        }
                        if ((float)$oldProduct['preco'] !== $preco) {
                            $descChanges .= " | Preço: R$ " . number_format($oldProduct['preco'], 2, ',', '.') . " -> R$ " . number_format($preco, 2, ',', '.');
                        }
                    }
                    Auditoria::log('EDICAO_PRODUTO', $descChanges, $operador, 'R$ ' . number_format($preco, 2, '.', ''), 'SUCESSO');
                } else {
                    $erro = 'Falha ao atualizar o produto no banco de dados.';
                }
            }
        }

        if (!empty($erro)) {
            header('Location: ' . $this->base_path . '/estoque?erro=' . urlencode($erro));
        } else {
            header('Location: ' . $this->base_path . '/estoque?sucesso=' . urlencode($sucesso));
        }
        exit();
    }

    // Exclui um produto (RF-ES-004)
    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Location: ' . $this->base_path . '/estoque');
            exit();
        }

        $id = !empty($_REQUEST['id_produto']) ? (int)$_REQUEST['id_produto'] : null;

        if ($id === null) {
            header('Location: ' . $this->base_path . '/estoque?erro=' . urlencode('Produto inválido ou ID ausente.'));
            exit();
        }

        $produto = Produto::findById($id);
        if (!$produto) {
            header('Location: ' . $this->base_path . '/estoque?erro=' . urlencode('Produto não localizado no sistema.'));
            exit();
        }

        $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
        $deleted = Produto::delete($id);

        if ($deleted) {
            $sucesso = "Produto '" . $produto['nome'] . "' removido com sucesso.";
            Auditoria::log('EXCLUSAO_PRODUTO', "Excluiu o produto ID #$id: " . $produto['nome'], $operador, 'R$ ' . number_format($produto['preco'], 2, '.', ''), 'SUCESSO');
            header('Location: ' . $this->base_path . '/estoque?sucesso=' . urlencode($sucesso));
        } else {
            header('Location: ' . $this->base_path . '/estoque?erro=' . urlencode("Falha ao excluir o produto."));
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
