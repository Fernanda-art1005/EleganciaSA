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
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
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

        $erro = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';

            if ($email && $senha) {
                $user = Usuario::findByEmail($email);

                if ($user && password_verify($senha, $user['senha_hash'])) {

                    if ($user['status'] === 'INATIVO') {
                        $erro = 'Conta inativa.';
                    } else {
                        $_SESSION = [
                            'user_id' => $user['id_usuario'],
                            'user_name' => $user['nome'],
                            'user_email' => $user['email'],
                            'nivel_acesso' => $user['nivel_acesso'],
                            'perms' => [
                                'dashboard' => $user['perm_dashboard'],
                                'caixa' => $user['perm_caixa'],
                                'estoque' => $user['perm_estoque'],
                                'financeiro' => $user['perm_financeiro'],
                                'crm' => $user['perm_crm'],
                                'kanban' => $user['perm_kanban'],
                                'relatorios' => $user['perm_relatorios'],
                                'equipe' => $user['perm_equipe'],
                            ]
                        ];

                        Auditoria::log('LOGIN', 'Login realizado', $user['nome'], 'N/A', 'SUCESSO');

                        $this->redirectByProfile($user['nivel_acesso']);
                        return;
                    }
                } else {
                    $erro = 'E-mail ou senha incorretos';
                    Auditoria::log('LOGIN_FAIL', 'Falha de login', $email, 'N/A', 'FALHA');
                }
            } else {
                $erro = 'Preencha todos os campos';
            }
        }

        require dirname(__DIR__) . '/views/auth/login.php';
    }

    public function logout(): void {
        if (isset($_SESSION['user_id'])) {
            Auditoria::log('LOGOUT', 'Logout realizado', $_SESSION['user_name'], 'N/A', 'SUCESSO');
        }

        session_destroy();

        header('Location: ' . $this->base_path . '/login');
        exit();
    }

    private function redirectByProfile(string $profile): void {
        $routes = [
            'CAIXA' => '/pdv',
            'ESTOQUE' => '/estoque',
        ];

        $route = $routes[$profile] ?? '/';
        header('Location: ' . $this->base_path . $route);
        exit();
    }
}