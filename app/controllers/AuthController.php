<?php
namespace App\Controllers;

use App\Models\Usuario;
use App\Models\Auditoria;

class AuthController {
    private string $base_path;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'None'
            ]);
            session_start();
        }
        $this->base_path = defined('BASE_PATH') ? BASE_PATH : '';
    }

    public function login(): void {
        if (isset($_SESSION['user_id'])) {
            $this->redirectByProfile($_SESSION['nivel_acesso']);
            return;
        }

        $colaboradores = [];
        try {
            $colaboradores = Usuario::getAll();
        } catch (\Exception $e) {
            // Ignora se o banco não estiver pronto ou em migração
        }

       $erro = '';
       if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nome = trim($_POST['nome'] ?? '');
          $senha = $_POST['senha'] ?? '';

    if (!empty($nome) && !empty($senha)) {
        $user = Usuario::findByEmail($nome);
                if ($user && password_verify($senha, $user['senha_hash'])) {
                    if ($user['status'] === 'INATIVO') {
                        $erro = 'Sua conta está desativada ou aguardando aprovação de um Administrador.';
                    } else {
                        // Define sessão segura (JWT-like sessions per RNF-006)
                        $_SESSION['user_id'] = $user['id_usuario'];
                        $_SESSION['user_name'] = $user['nome'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
                        $_SESSION['last_activity'] = time();
                        
                        // Permissões granulares
                        $_SESSION['perms'] = [
                            'dashboard' => $user['perm_dashboard'],
                            'caixa' => $user['perm_caixa'],
                            'estoque' => $user['perm_estoque'],
                            'financeiro' => $user['perm_financeiro'],
                            'crm' => $user['perm_crm'],
                            'kanban' => $user['perm_kanban'],
                            'relatorios' => $user['perm_relatorios'],
                            'equipe' => $user['perm_equipe'],
                        ];

                        // Log da Auditoria (Regra RN-AU-001)
                        $identificador = $user['nome'] . " (" . $user['nivel_acesso'] . ")";
                        Auditoria::log('LOGIN', 'Usuário efetuou login com sucesso no sistema.', $identificador, 'N/A', 'SUCESSO');

                        $this->redirectByProfile($user['nivel_acesso']);
                        return;
                    }
                } else {
                    $erro = 'Nome ou senha incorretos';
                    Auditoria::log('LOGIN', "Tentativa de login malsucedida para o usuário: $nome.", 'Sistema', 'N/A', 'FALHA');
                }
            } else {
                $erro = 'Por favor, preencha todos os campos.';
            }
        }

        require dirname(__DIR__) . '/views/auth/login.php';
    }

    public function register(): void {
        $token = $_GET['token'] ?? '';
        $invite = null;
        if (!empty($token)) {
            $invite = Usuario::getInviteByToken($token);
        }

        $erro = '';
        $sucesso = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';
            $confirmar_senha = $_POST['confirmar_senha'] ?? '';
            $perfil = $_POST['nivel_acesso'] ?? 'CAIXA';

            if (!empty($nome) && !empty($email) && !empty($senha)) {
                if ($senha !== $confirmar_senha) {
                    $erro = 'As senhas não coincidem.';
                } else {
                    $existing = Usuario::findByEmail($email);
                    if ($existing) {
                        $erro = 'Este e-mail já está sendo utilizado por outro usuário.';
                    } else {
                        // Se aceitando convite, marca token como aceito e ativo imediatamente.
                        // Caso contrário, fica INATIVO (pendente de permissão/aprovação de entrada de administrador).
                        $status = 'INATIVO';
                        if ($invite && $invite['email'] === $email) {
                            $perfil = $invite['perfil'];
                            $status = 'ATIVO';
                        }

                        // Se for o primeiríssimo usuário do sistema, ativa imediatamente como ADMIN!
                        try {
                            $dbCount = \App\Config\Database::getConnection();
                            $userCount = (int)$dbCount->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
                            if ($userCount === 0) {
                                $perfil = 'ADMIN';
                                $status = 'ATIVO';
                            }
                        } catch (\Exception $e) {
                            // Ignora se der erro de consulta
                        }

                        $created = Usuario::create([
                            'nome' => $nome,
                            'email' => $email,
                            'senha' => $senha,
                            'nivel_acesso' => $perfil,
                            'status' => $status
                        ]);

                        if ($created) {
                            if ($invite) {
                                Usuario::acceptInvite($token);
                                $sucesso = 'Conta criada com sucesso! Faça login.';
                            } elseif ($status === 'ATIVO') {
                                $sucesso = 'Conta de Administrador criada com sucesso! Faça login para começar.';
                            } else {
                                $sucesso = 'Solicitação de cadastro enviada com sucesso! Aguarde a aprovação de um Administrador para acessar o sistema.';
                            }
                            
                            Auditoria::log('CADASTRO', "Novo usuário cadastrado (Status: $status): $nome ($perfil).", 'Sistema', 'N/A', 'SUCESSO');
                            header('Location: ' . $this->base_path . '/login?sucesso=' . urlencode($sucesso));
                            exit();
                        } else {
                            $erro = 'Falha ao registrar conta.';
                        }
                    }
                }
            } else {
                $erro = 'Por favor, preencha todos os campos obrigatórios.';
            }
        }

        require dirname(__DIR__) . '/views/auth/register.php';
    }

    public function logout(): void {
        if (isset($_SESSION['user_id'])) {
            $identificador = $_SESSION['user_name'] . " (" . $_SESSION['nivel_acesso'] . ")";
            Auditoria::log('LOGOUT', 'Usuário deslogou do sistema de forma segura.', $identificador, 'N/A', 'SUCESSO');
        }
        
        session_unset();
        session_destroy();
        
        header('Location: ' . $this->base_path . '/login');
        exit();
    }

    private function redirectByProfile(string $profile): void {
        // Redirecionamento por perfil (Regra RF-AU-004)
        if ($profile === 'CAIXA') {
            header('Location: ' . $this->base_path . '/pdv');
        } else if ($profile === 'ESTOQUE') {
            header('Location: ' . $this->base_path . '/estoque');
        } else {
            header('Location: ' . $this->base_path . '/');
        }
        exit();
    }
}
