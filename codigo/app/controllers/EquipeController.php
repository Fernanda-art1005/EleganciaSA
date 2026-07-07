<?php
namespace App\Controllers;

use App\Models\Produto;
use App\Models\Auditoria;

class EstoqueController {
    private string $base_path;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $this->base_path = BASE_PATH ?? '';

        $this->auth();
        $this->perm();
    }

    private function auth(): void {
        if (!isset($_SESSION['user_id'])) {
            header("Location: {$this->base_path}/login");
            exit();
        }
    }

    private function perm(): void {
        if (empty($_SESSION['perms']['estoque'])) {
            header("Location: {$this->base_path}/?erro=sem-permissao");
            exit();
        }
    }

    public function index(): void {
        $q = trim($_GET['q'] ?? '');
        $produtos = $q ? Produto::search($q) : Produto::getAll();

        $this->render('estoque/index', compact('produtos','q'));
    }

    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect();

        $id = !empty($_POST['id_produto']) ? (int)$_POST['id_produto'] : null;

        $data = [
            'nome' => trim($_POST['nome'] ?? ''),
            'categoria' => trim($_POST['categoria'] ?? '') ?: null,
            'preco_custo' => (float)str_replace(',', '.', $_POST['preco_custo'] ?? 0),
            'preco' => (float)str_replace(',', '.', $_POST['preco'] ?? 0),
            'quantidade' => (int)($_POST['quantidade'] ?? 0),
            'estoque_minimo' => (int)($_POST['estoque_minimo'] ?? 0),
        ];

        if ($data['nome'] === '') {
            $this->redirect('erro=Nome obrigatório');
        }

        if ($data['preco'] <= $data['preco_custo']) {
            $this->redirect('erro=Preço inválido');
        }

        if ($id === null) {
            Produto::create($data);
            Auditoria::log('PRODUTO_CREATE', $data['nome'], $_SESSION['user_name'], 'N/A', 'SUCESSO');
        } else {
            Produto::update($id, $data);
            Auditoria::log('PRODUTO_UPDATE', $data['nome'], $_SESSION['user_name'], 'N/A', 'SUCESSO');
        }

        $this->redirect('sucesso=ok');
    }

    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') $this->redirect();

        $id = (int)($_POST['id_produto'] ?? 0);
        if (!$id) $this->redirect('erro=id-invalido');

        $produto = Produto::findById($id);
        if (!$produto) $this->redirect('erro=nao-encontrado');

        Produto::delete($id);

        Auditoria::log('PRODUTO_DELETE', $produto['nome'], $_SESSION['user_name'], 'N/A', 'SUCESSO');

        $this->redirect('sucesso=excluido');
    }

    private function redirect(string $params = ''): void {
        header("Location: {$this->base_path}/estoque?$params");
        exit();
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        require dirname(__DIR__)."/views/layout/header.php";
        require dirname(__DIR__)."/views/$view.php";
        require dirname(__DIR__)."/views/layout/footer.php";
    }
}