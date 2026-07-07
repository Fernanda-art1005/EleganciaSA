<?php
namespace App\Controllers;

use App\Models\Tarefa;
use App\Models\Usuario;
use App\Models\Auditoria;
use Exception;

class KanbanController {
    private string $base_path;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->base_path = BASE_PATH ?? '';

        if (empty($_SESSION['user_id'])) {
            header("Location: {$this->base_path}/login");
            exit;
        }

        if (empty($_SESSION['perms']['kanban'])) {
            header("Location: {$this->base_path}/?erro=Acesso negado");
            exit;
        }
    }

    public function index(): void {
        $colunas = Tarefa::getColumns();
        $responsaveis = Usuario::getAll();

        $quadro = [];
        foreach ($colunas as $coluna) {
            $quadro[$coluna['id_coluna']] = [
                'id_coluna' => $coluna['id_coluna'],
                'titulo' => $coluna['titulo'],
                'tarefas' => Tarefa::getTasksByColumn($coluna['id_coluna'])
            ];
        }

        $this->render('kanban/index', compact('colunas', 'quadro', 'responsaveis'));
    }

    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$this->base_path}/kanban");
            exit;
        }

        try {
            $id = filter_input(INPUT_POST, 'id_tarefa', FILTER_VALIDATE_INT);
            $titulo = trim($_POST['titulo'] ?? '');
            $descricao = trim($_POST['descricao'] ?? '');
            $data_vencimento = $_POST['data_vencimento'] ?? null;
            $id_responsavel = filter_input(INPUT_POST, 'id_responsavel', FILTER_VALIDATE_INT);
            $prioridade = strtoupper($_POST['prioridade'] ?? 'MEDIA');
            $id_coluna = filter_input(INPUT_POST, 'id_coluna', FILTER_VALIDATE_INT);

            if (!$titulo || !$id_coluna) {
                throw new Exception("Título e coluna são obrigatórios.");
            }

            $data = compact(
                'titulo',
                'descricao',
                'data_vencimento',
                'id_responsavel',
                'prioridade',
                'id_coluna'
            );

            $op = $_SESSION['user_name'];

            if (!$id) {
                Tarefa::create($data);
                Auditoria::log('KANBAN_ADD', "Criou tarefa {$titulo}", $op, 'N/A', 'SUCESSO');
            } else {
                Tarefa::update($id, $data);
                Auditoria::log('KANBAN_EDIT', "Editou tarefa #$id", $op, 'N/A', 'SUCESSO');
            }

            header("Location: {$this->base_path}/kanban?sucesso=OK");
        } catch (Exception $e) {
            header("Location: {$this->base_path}/kanban?erro=" . urlencode($e->getMessage()));
        }
        exit;
    }

    public function delete(): void {
        $id = filter_input(INPUT_POST, 'id_tarefa', FILTER_VALIDATE_INT);
        if (!$id) exit;

        $tarefa = Tarefa::findById($id);
        if ($tarefa) {
            Tarefa::delete($id);
            Auditoria::log('KANBAN_DEL', "Removeu tarefa #$id", $_SESSION['user_name'], 'N/A', 'SUCESSO');
        }

        header("Location: {$this->base_path}/kanban");
        exit;
    }

    public function move(): void {
        header('Content-Type: application/json');

        $id = filter_input(INPUT_POST, 'id_tarefa', FILTER_VALIDATE_INT);
        $col = filter_input(INPUT_POST, 'id_coluna', FILTER_VALIDATE_INT);

        if (!$id || !$col) {
            echo json_encode(['sucesso' => false]);
            exit;
        }

        $ok = Tarefa::moveTask($id, $col);

        echo json_encode(['sucesso' => $ok]);
        exit;
    }

    public function saveColumn(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

        $id = filter_input(INPUT_POST, 'id_coluna', FILTER_VALIDATE_INT);
        $titulo = trim($_POST['titulo'] ?? '');

        if (!$titulo) {
            header("Location: {$this->base_path}/kanban?erro=Nome obrigatório");
            exit;
        }

        if (!$id) {
            Tarefa::createColumn($titulo);
        } else {
            Tarefa::renameColumn($id, $titulo);
        }

        header("Location: {$this->base_path}/kanban");
        exit;
    }

    public function deleteColumn(): void {
        $id = filter_input(INPUT_POST, 'id_coluna', FILTER_VALIDATE_INT);
        if (!$id) exit;

        Tarefa::deleteColumn($id);

        header("Location: {$this->base_path}/kanban");
        exit;
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        require dirname(__DIR__) . "/views/layout/header.php";
        require dirname(__DIR__) . "/views/$view.php";
        require dirname(__DIR__) . "/views/layout/footer.php";
    }
}