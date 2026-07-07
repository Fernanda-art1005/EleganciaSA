<?php
namespace App\Controllers;

use App\Models\Produto;
use App\Models\Auditoria;

class EstoqueController {
    private string $base_path;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->base_path = BASE_PATH ?? '';

        $this->auth();
        $this->checkPermission();
    }

    private function auth(): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
    }

    private function checkPermission(): void {
        if (empty($_SESSION['perms']['estoque'])) {
            $this->redirect('/?erro=' . urlencode('Acesso negado ao Controle de Estoque.'));
        }
    }

    /* =========================
       LISTAGEM
    ========================== */
    public function index(): void {
        $q = trim($_GET['q'] ?? '');
        $produtos = $q !== '' ? Produto::search($q) : Produto::getAll();

        $this->render('estoque/index', compact('produtos', 'q'));
    }

    /* =========================
       CREATE / UPDATE
    ========================== */
    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/estoque');
        }

        $id = !empty($_POST['id_produto']) ? (int)$_POST['id_produto'] : null;

        $nome = trim($_POST['nome'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '') ?: null;

        $preco_custo = $this->toFloat($_POST['preco_custo'] ?? 0);
        $preco = $this->toFloat($_POST['preco'] ?? 0);

        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $estoque_minimo = (int)($_POST['estoque_minimo'] ?? 0);

        /* VALIDAÇÕES */
        if ($nome === '') {
            $this->redirect('/estoque?erro=' . urlencode('Nome obrigatório'));
        }

        if ($preco <= 0 || $preco_custo <= 0) {
            $this->redirect('/estoque?erro=' . urlencode('Preços inválidos'));
        }

        if ($preco <= $preco_custo) {
            $this->redirect('/estoque?erro=' . urlencode('Preço de venda deve ser maior que custo'));
        }

        if ($quantidade < 0 || $estoque_minimo < 0) {
            $this->redirect('/estoque?erro=' . urlencode('Estoque não pode ser negativo'));
        }

        $data = [
            'nome' => $nome,
            'categoria' => $categoria,
            'preco_custo' => $preco_custo,
            'preco' => $preco,
            'quantidade' => $quantidade,
            'estoque_minimo' => $estoque_minimo
        ];

        $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";

        if ($id === null) {
            $ok = Produto::create($data);

            if (!$ok) {
                $this->redirect('/estoque?erro=' . urlencode('Erro ao cadastrar produto'));
            }

            Auditoria::log(
                'PRODUTO_CREATE',
                "Criou produto: $nome",
                $operador,
                (string)$preco,
                'SUCESSO'
            );

            $this->redirect('/estoque?sucesso=criado');
        }

        $old = Produto::findById($id);
        $ok = Produto::update($id, $data);

        if (!$ok) {
            $this->redirect('/estoque?erro=' . urlencode('Erro ao atualizar produto'));
        }

        $log = "Editou produto ID #$id: $nome";

        if ($old) {
            if ((int)$old['quantidade'] !== $quantidade) {
                $log .= " | qtd {$old['quantidade']} → $quantidade";
            }

            if ((float)$old['preco'] !== $preco) {
                $log .= " | preço {$old['preco']} → $preco";
            }
        }

        Auditoria::log(
            'PRODUTO_UPDATE',
            $log,
            $operador,
            (string)$preco,
            'SUCESSO'
        );

        $this->redirect('/estoque?sucesso=atualizado');
    }

    /* =========================
       DELETE (CORRIGIDO - APENAS POST)
    ========================== */
    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/estoque');
        }

        $id = (int)($_POST['id_produto'] ?? 0);

        if ($id <= 0) {
            $this->redirect('/estoque?erro=' . urlencode('ID inválido'));
        }

        $produto = Produto::findById($id);

        if (!$produto) {
            $this->redirect('/estoque?erro=' . urlencode('Produto não encontrado'));
        }

        $ok = Produto::delete($id);

        $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";

        if ($ok) {
            Auditoria::log(
                'PRODUTO_DELETE',
                "Removeu produto: {$produto['nome']}",
                $operador,
                (string)$produto['preco'],
                'SUCESSO'
            );

            $this->redirect('/estoque?sucesso=excluido');
        }

        Auditoria::log(
            'PRODUTO_DELETE_BLOCK',
            "Falha ao excluir produto ID $id",
            $operador,
            'N/A',
            'BLOQUEADO'
        );

        $this->redirect('/estoque?erro=erro-exclusao');
    }

    /* =========================
       HELPERS
    ========================== */
    private function toFloat($value): float {
        return (float)str_replace(',', '.', $value);
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
