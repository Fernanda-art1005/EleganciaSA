<?php
namespace App\Controllers;

use App\Models\Task;
use App\Models\Tarefa;
use App\Models\Usuario;
use App\Models\Auditoria;
use Exception;

class TaskController {
    private string $base_path;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->base_path = defined('BASE_PATH') ? BASE_PATH : '';

        // Validação de Sessão
        if (!isset($_SESSION['user_id'])) {
            if ($this->isAjax()) {
                http_response_code(401);
                echo json_encode(['erro' => 'Não autorizado. Faça login novamente.']);
                exit();
            }
            header('Location: ' . $this->base_path . '/login');
            exit();
        }

        // Validação de Permissão
        if (!$_SESSION['perms']['kanban']) {
            if ($this->isAjax()) {
                http_response_code(403);
                echo json_encode(['erro' => 'Acesso negado ao quadro de Tarefas (Kanban).']);
                exit();
            }
            header('Location: ' . $this->base_path . '/?erro=' . urlencode('Acesso negado ao quadro de Tarefas (Kanban).'));
            exit();
        }
    }

    // LISTAR TAREFAS - index()
    public function index(): void {
        $colunas = Tarefa::getColumns();
        $responsaveis = Usuario::getAll();
        $tasks = Task::all();

        // Organiza as tarefas por coluna
        $quadro = [];
        foreach ($colunas as $coluna) {
            $quadro[$coluna['id_coluna']] = [
                'id_coluna' => $coluna['id_coluna'],
                'titulo' => $coluna['titulo'],
                'tarefas' => []
            ];
        }

        foreach ($tasks as $task) {
            if (isset($quadro[$task['id_coluna']])) {
                $quadro[$task['id_coluna']]['tarefas'][] = $task;
            }
        }

        $this->render('kanban/index', [
            'colunas' => $colunas,
            'quadro' => $quadro,
            'responsaveis' => $responsaveis
        ]);
    }

    // FORM CRIAR - create()
    public function create(): void {
        // Redireciona para o index para que o modal de criação seja aberto lá
        header('Location: ' . $this->base_path . '/kanban?action=create');
        exit();
    }

    // SALVAR TAREFA - store()
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->base_path . '/kanban');
            exit();
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $data_vencimento = $_POST['data_vencimento'] ?? '';
        $id_responsavel = !empty($_POST['id_responsavel']) ? (int)$_POST['id_responsavel'] : null;
        $prioridade = strtoupper($_POST['prioridade'] ?? 'MEDIA');
        $id_coluna = !empty($_POST['id_coluna']) ? (int)$_POST['id_coluna'] : null;

        try {
            if (empty($titulo)) {
                throw new Exception("O título da tarefa é obrigatório.");
            }
            if ($id_coluna === null) {
                throw new Exception("A coluna de destino é obrigatória.");
            }

            $data = [
                'titulo' => $titulo,
                'descricao' => $descricao,
                'data_vencimento' => $data_vencimento,
                'id_responsavel' => $id_responsavel,
                'prioridade' => $prioridade,
                'id_coluna' => $id_coluna
            ];

            $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
            Task::create($data);

            $sucesso = "Tarefa '" . $titulo . "' criada com sucesso!";
            Auditoria::log('KANBAN_TAREFA_ADD', "Criou a tarefa: $titulo | Vence em: $data_vencimento | Prioridade: $prioridade", $operador, 'N/A', 'SUCESSO');
            
            header('Location: ' . $this->base_path . '/kanban?sucesso=' . urlencode($sucesso));
        } catch (Exception $e) {
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode($e->getMessage()));
        }
        exit();
    }

    // FORM EDITAR / CONSULTAR - edit()
    public function edit($id = null): void {
        if ($id === null) {
            $id = !empty($_REQUEST['id']) ? (int)$_REQUEST['id'] : null;
        }
        if ($id === null) {
            $id = !empty($_REQUEST['id_tarefa']) ? (int)$_REQUEST['id_tarefa'] : null;
        }

        if ($id === null) {
            if ($this->isAjax()) {
                http_response_code(400);
                echo json_encode(['erro' => 'ID de tarefa inválido ou ausente.']);
                exit();
            }
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode('ID de tarefa inválido ou ausente.'));
            exit();
        }

        $task = Task::findById($id);

        if (!$task) {
            if ($this->isAjax()) {
                http_response_code(404);
                echo json_encode(['erro' => 'Tarefa não encontrada.']);
                exit();
            }
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode('Tarefa não encontrada.'));
            exit();
        }

        if ($this->isAjax()) {
            echo json_encode($task);
            exit();
        }

        // Se não for AJAX, renderiza a página do Kanban passando parâmetros para abrir o modal de edição
        header('Location: ' . $this->base_path . '/kanban?action=edit&id=' . $id);
        exit();
    }

    // ATUALIZAR TAREFA - update()
    public function update($id = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->base_path . '/kanban');
            exit();
        }

        if ($id === null) {
            $id = !empty($_POST['id_tarefa']) ? (int)$_POST['id_tarefa'] : null;
        }
        if ($id === null) {
            $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        }

        $titulo = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $data_vencimento = $_POST['data_vencimento'] ?? '';
        $id_responsavel = !empty($_POST['id_responsavel']) ? (int)$_POST['id_responsavel'] : null;
        $prioridade = strtoupper($_POST['prioridade'] ?? 'MEDIA');
        $id_coluna = !empty($_POST['id_coluna']) ? (int)$_POST['id_coluna'] : null;

        try {
            if ($id === null) {
                throw new Exception("ID da tarefa inválido ou ausente.");
            }
            if (empty($titulo)) {
                throw new Exception("O título da tarefa é obrigatório.");
            }
            if ($id_coluna === null) {
                throw new Exception("A coluna de destino é obrigatória.");
            }

            $data = [
                'titulo' => $titulo,
                'descricao' => $descricao,
                'data_vencimento' => $data_vencimento,
                'id_responsavel' => $id_responsavel,
                'prioridade' => $prioridade,
                'id_coluna' => $id_coluna
            ];

            $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
            Task::update($id, $data);

            $sucesso = "Tarefa '" . $titulo . "' atualizada com sucesso!";
            Auditoria::log('KANBAN_TAREFA_EDIT', "Editou a tarefa ID #$id: $titulo | Vence em: $data_vencimento | Prioridade: $prioridade", $operador, 'N/A', 'SUCESSO');
            
            header('Location: ' . $this->base_path . '/kanban?sucesso=' . urlencode($sucesso));
        } catch (Exception $e) {
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode($e->getMessage()));
        }
        exit();
    }

    // EXCLUIR TAREFA - destroy() / delete()
    public function destroy($id = null): void {
        if ($id === null) {
            $id = !empty($_REQUEST['id']) ? (int)$_REQUEST['id'] : null;
        }
        if ($id === null) {
            $id = !empty($_REQUEST['id_tarefa']) ? (int)$_REQUEST['id_tarefa'] : null;
        }

        if ($id === null) {
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode('ID de tarefa inválido ou ausente.'));
            exit();
        }

        $task = Task::findById($id);
        if (!$task) {
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode('Tarefa não localizada no sistema.'));
            exit();
        }

        $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
        $deleted = Task::delete($id);

        if ($deleted) {
            $sucesso = "Tarefa '" . $task['titulo'] . "' removida do quadro.";
            Auditoria::log('KANBAN_TAREFA_DEL', "Excluiu a tarefa ID #$id: " . $task['titulo'], $operador, 'N/A', 'SUCESSO');
            header('Location: ' . $this->base_path . '/kanban?sucesso=' . urlencode($sucesso));
        } else {
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode('Falha ao excluir a tarefa.'));
        }
        exit();
    }

    public function delete($id = null): void {
        $this->destroy($id);
    }

    // CONCLUIR TAREFA - complete()
    public function complete($id = null): void {
        if ($id === null) {
            $id = !empty($_REQUEST['id']) ? (int)$_REQUEST['id'] : null;
        }
        if ($id === null) {
            $id = !empty($_REQUEST['id_tarefa']) ? (int)$_REQUEST['id_tarefa'] : null;
        }

        if ($id === null) {
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode('ID de tarefa inválido ou ausente.'));
            exit();
        }

        $task = Task::findById($id);
        if (!$task) {
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode('Tarefa não localizada no sistema.'));
            exit();
        }

        $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
        $completed = Task::complete($id);

        if ($completed) {
            $sucesso = "Tarefa '" . $task['titulo'] . "' concluída com sucesso!";
            Auditoria::log('KANBAN_TAREFA_DONE', "Concluiu a tarefa ID #$id: " . $task['titulo'], $operador, 'N/A', 'SUCESSO');
            header('Location: ' . $this->base_path . '/kanban?sucesso=' . urlencode($sucesso));
        } else {
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode('Falha ao concluir a tarefa.'));
        }
        exit();
    }

    // Move tarefa entre funis/colunas (RF-TA-005)
    public function move(): void {
        header('Content-Type: application/json');

        $id_tarefa = !empty($_GET['id_tarefa']) ? (int)$_GET['id_tarefa'] : null;
        $id_coluna = !empty($_GET['id_coluna']) ? (int)$_GET['id_coluna'] : null;

        if ($id_tarefa === null || $id_coluna === null) {
            echo json_encode(['sucesso' => false, 'erro' => 'Parâmetros de movimentação ausentes.']);
            exit();
        }

        $tarefa = Task::findById($id_tarefa);
        if (!$tarefa) {
            echo json_encode(['sucesso' => false, 'erro' => 'Tarefa não localizada.']);
            exit();
        }

        $success = Tarefa::moveTask($id_tarefa, $id_coluna);
        if ($success) {
            $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
            Auditoria::log('KANBAN_TAREFA_MOVE', "Moveu a tarefa ID #$id_tarefa ('" . $tarefa['titulo'] . "') para a coluna ID $id_coluna.", $operador, 'N/A', 'SUCESSO');
            echo json_encode(['sucesso' => true, 'mensagem' => 'Tarefa movida com sucesso!']);
        } else {
            echo json_encode(['sucesso' => false, 'erro' => 'Falha ao salvar a movimentação no banco de dados.']);
        }
        exit();
    }

    // Salva ou cria nova coluna (RF-TA-006)
    public function saveColumn(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->base_path . '/kanban');
            exit();
        }

        $id = !empty($_POST['id_coluna']) ? (int)$_POST['id_coluna'] : null;
        $titulo = trim($_POST['titulo'] ?? '');

        $erro = '';
        $sucesso = '';

        if (empty($titulo)) {
            $erro = 'O nome do funil/coluna é obrigatório.';
        } else {
            $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
            if ($id === null) {
                // Criação
                $created = Tarefa::createColumn($titulo);
                if ($created) {
                    $sucesso = "Coluna '" . $titulo . "' criada com sucesso!";
                    Auditoria::log('KANBAN_COLUNA_ADD', "Criou a coluna/funil: $titulo", $operador, 'N/A', 'SUCESSO');
                } else {
                    $erro = 'Falha ao criar a coluna.';
                }
            } else {
                // Renomeação
                $updated = Tarefa::renameColumn($id, $titulo);
                if ($updated) {
                    $sucesso = "Coluna renomeada para '" . $titulo . "'!";
                    Auditoria::log('KANBAN_COLUNA_EDIT', "Renomeou a coluna ID #$id para: $titulo", $operador, 'N/A', 'SUCESSO');
                } else {
                    $erro = 'Falha ao renomear a coluna.';
                }
            }
        }

        if (!empty($erro)) {
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode($erro));
        } else {
            header('Location: ' . $this->base_path . '/kanban?sucesso=' . urlencode($sucesso));
        }
        exit();
    }

    // Exclui coluna (RF-TA-006)
    public function deleteColumn(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Location: ' . $this->base_path . '/kanban');
            exit();
        }

        $id = !empty($_REQUEST['id_coluna']) ? (int)$_REQUEST['id_coluna'] : null;

        if ($id === null) {
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode('ID de coluna inválido.'));
            exit();
        }

        $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";

        try {
            Tarefa::deleteColumn($id);
            $sucesso = "Coluna removida com sucesso!";
            Auditoria::log('KANBAN_COLUNA_DEL', "Excluiu a coluna ID #$id", $operador, 'N/A', 'SUCESSO');
            header('Location: ' . $this->base_path . '/kanban?sucesso=' . urlencode($sucesso));
        } catch (Exception $e) {
            // Regra RN-TA-002: Exclusão bloqueada se contiver tarefas
            $erro = $e->getMessage();
            Auditoria::log('KANBAN_COLUNA_DEL_ERR', "Tentativa malsucedida de exclusão da coluna ID #$id por conter tarefas.", $operador, 'N/A', 'BLOQUEADO');
            header('Location: ' . $this->base_path . '/kanban?erro=' . urlencode($erro));
        }
        exit();
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        require dirname(__DIR__) . '/views/layout/header.php';
        require dirname(__DIR__) . '/views/' . $view . '.php';
        require dirname(__DIR__) . '/views/layout/footer.php';
    }

    private function isAjax(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
               (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);
    }
}
