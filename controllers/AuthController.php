<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : controllers/AuthController.php
 * ========================================================= */

require_once BASE_PATH . '/models/UsuarioModel.php';

class AuthController {
    private UsuarioModel $model;

    public function __construct() {
        $this->model = new UsuarioModel();
    }

    public function showLogin(): void {
        if (!empty($_SESSION['usuario'])) {
            header('Location: index.php?action=search');
            exit;
        }
        require VIEWS_PATH . '/login.php';
    }

    public function doLogin(): void {
        $login    = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';
        $error    = '';

        if (empty($login) || empty($password)) {
            $error = 'Por favor, introduce usuario y contraseña.';
        } else {
            try {
                $usuario = $this->model->autenticar($login, $password);
                if ($usuario) {
                    session_regenerate_id(true);
                    $_SESSION['usuario']    = $usuario;
                    $_SESSION['login_time'] = time();
                    header('Location: index.php?action=search');
                    exit;
                } else {
                    $error = 'Usuario o contraseña incorrectos.';
                }
            } catch (PDOException $e) {
                $error = 'Error de conexión con la base de datos.';
                if (APP_ENV === 'development') {
                    $error .= ' [' . $e->getMessage() . ']';
                }
            }
        }
        require VIEWS_PATH . '/login.php';
    }

    public function doLogout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: index.php?action=login');
        exit;
    }
}
