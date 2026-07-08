<?php
namespace App\Controllers;

use App\Models\Usuario;
use App\Models\Auditoria;
use Exception;

class EquipeController {
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

        // Regra RN-AC-001: Somente o perfil 'administrador' pode acessar o módulo de Equipe
        if ($_SESSION['nivel_acesso'] !== 'ADMIN' || !$_SESSION['perms']['equipe']) {
            header('Location: ' . $this->base_path . '/?erro=' . urlencode('Acesso negado. Apenas administradores podem gerenciar a equipe e permissões.'));
            exit();
        }
    }

    public function index(): void {
        $membros = Usuario::getAll();
        $convites = Usuario::getInvites();

        $this->render('equipe/index', [
            'membros' => $membros,
            'convites' => $convites
        ]);
    }

    // Salva perfil e permissões de membros existentes (RF-EQ-005)
    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->base_path . '/equipe');
            exit();
        }

        $id = !empty($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : null;
        $nivel_acesso = strtoupper($_POST['nivel_acesso'] ?? '');

        $erro = '';
        $sucesso = '';

        if ($id === null) {
            $erro = 'ID do usuário ausente.';
        } elseif (!in_array($nivel_acesso, ['ADMIN', 'CAIXA', 'ESTOQUE'])) {
            $erro = 'Perfil selecionado inválido.';
        } else {
            // Regra RN-AC-004: Nenhum usuário pode alterar seu próprio perfil ou permissões
            if ($id === (int)$_SESSION['user_id']) {
                $erro = 'Por motivos de segurança, você não pode alterar o seu próprio perfil ou permissões.';
            } else {
                $user = Usuario::findById($id);
                if (!$user) {
                    $erro = 'Usuário não localizado.';
                } else {
                    // Prepara array de permissões customizadas
                    $perms = [
                        'nivel_acesso' => $nivel_acesso,
                        'status' => $_POST['status'] ?? 'ATIVO',
                        'perm_dashboard' => isset($_POST['perm_dashboard']) ? 1 : 0,
                        'perm_caixa' => isset($_POST['perm_caixa']) ? 1 : 0,
                        'perm_estoque' => isset($_POST['perm_estoque']) ? 1 : 0,
                        'perm_financeiro' => isset($_POST['perm_financeiro']) ? 1 : 0,
                        'perm_crm' => isset($_POST['perm_crm']) ? 1 : 0,
                        'perm_kanban' => isset($_POST['perm_kanban']) ? 1 : 0,
                        'perm_relatorios' => isset($_POST['perm_relatorios']) ? 1 : 0,
                        'perm_equipe' => isset($_POST['perm_equipe']) ? 1 : 0,
                    ];

                    $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
                    $updated = Usuario::updatePermissions($id, $perms);

                    if ($updated) {
                        $sucesso = "Perfil e permissões de '" . $user['nome'] . "' atualizados com sucesso!";
                        Auditoria::log('EQUIPE_PERM_EDIT', "Alterou o perfil/permissões de " . $user['nome'] . " para " . $nivel_acesso, $operador, 'N/A', 'SUCESSO');
                    } else {
                        $erro = 'Falha ao atualizar permissões do usuário.';
                    }
                }
            }
        }

        if (!empty($erro)) {
            header('Location: ' . $this->base_path . '/equipe?erro=' . urlencode($erro));
        } else {
            header('Location: ' . $this->base_path . '/equipe?sucesso=' . urlencode($sucesso));
        }
        exit();
    }

    // Cria um convite para novo membro via e-mail (RF-EQ-001 / RF-EQ-002)
    public function invite(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $this->base_path . '/equipe');
            exit();
        }

        $email = trim($_POST['email'] ?? '');
        $perfil = strtoupper($_POST['perfil'] ?? 'CAIXA');

        $erro = '';
        $sucesso = '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Por favor, insira um endereço de e-mail válido.';
        } elseif (!in_array($perfil, ['ADMIN', 'CAIXA', 'ESTOQUE'])) {
            $erro = 'Perfil de convite inválido.';
        } else {
            $existing = Usuario::findByEmail($email);
            if ($existing) {
                $erro = 'Este e-mail já pertence a um usuário ativo no sistema.';
            } else {
                $token = Usuario::createInvite($email, $perfil);
                $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
                
                // Gera o link de onboarding simulando envio de SMTP (conforme requisito 5.1 e RF-EQ-001)
                $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $linkConvite = $proto . '://' . $host . $this->base_path . '/register?token=' . $token;

                $sucesso = "Convite criado com sucesso para o e-mail " . $email . "! Link de onboarding gerado: " . $linkConvite;
                
                Auditoria::log('EQUIPE_INVITE', "Enviou convite de equipe para $email com perfil $perfil", $operador, 'N/A', 'SUCESSO');
                
                header('Location: ' . $this->base_path . '/equipe?sucesso=' . urlencode($sucesso) . '&invite_link=' . urlencode($linkConvite));
                exit();
            }
        }

        header('Location: ' . $this->base_path . '/equipe?erro=' . urlencode($erro));
        exit();
    }

    // Remove um membro da equipe (RF-EQ-004)
    public function delete(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Location: ' . $this->base_path . '/equipe');
            exit();
        }

        $id = !empty($_REQUEST['id_usuario']) ? (int)$_REQUEST['id_usuario'] : null;

        if ($id === null) {
            header('Location: ' . $this->base_path . '/equipe?erro=' . urlencode('ID de usuário inválido.'));
            exit();
        }

        // Regra RN-AC-004: Auto-exclusão proibida
        if ($id === (int)$_SESSION['user_id']) {
            header('Location: ' . $this->base_path . '/equipe?erro=' . urlencode('Você não pode excluir a sua própria conta de usuário.'));
            exit();
        }

        $user = Usuario::findById($id);
        if (!$user) {
            header('Location: ' . $this->base_path . '/equipe?erro=' . urlencode('Usuário não localizado no sistema.'));
            exit();
        }

        $operador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
        $deleted = Usuario::delete($id);

        if ($deleted) {
            $sucesso = "Membro '" . $user['nome'] . "' removido da equipe com sucesso.";
            Auditoria::log('EQUIPE_MEMBRO_DEL', "Excluiu o membro de equipe ID #$id: " . $user['nome'], $operador, 'N/A', 'SUCESSO');
            header('Location: ' . $this->base_path . '/equipe?sucesso=' . urlencode($sucesso));
        } else {
            // Regra RN-AC-005: Garante pelo menos um administrador ativo
            $erro = "Não foi possível remover o usuário '" . $user['nome'] . "'. O sistema deve possuir sempre pelo menos um Administrador ativo.";
            Auditoria::log('EQUIPE_MEMBRO_DEL_ERR', "Tentativa malsucedida de exclusão do administrador ID #$id por proteção de segurança.", $operador, 'N/A', 'BLOQUEADO');
            header('Location: ' . $this->base_path . '/equipe?erro=' . urlencode($erro));
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
