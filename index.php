<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : index.php  (Front Controller - raíz del proyecto)
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 * 
 * Descripción   : es el front controller del proyecto: 
 *                 todas las peticiones pasan por él vía "?"
 *                 Inicia la sesión y comprueba su caducidad 
 *                 Prptege rutas que requieren sesión iniciada y redirige a login si no hay sesión.
 *                 Carga el controlador adecuado según la acción solicitada.
 * ========================================================= */


// BASE_PATH = carpeta raíz del proyecto (donde está este fichero)
define('BASE_PATH', __DIR__);

require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/database.php';

// Iniciar sesión
session_name(SESSION_NAME);
session_start();

// Verificar expiración de sesión
if (!empty($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_LIFETIME) {
    session_destroy();
    header('Location: index.php?action=login&expired=1');
    exit;
}

// ---- Router ----
$action = $_GET['action'] ?? 'login';

/* Rutas protegidas: Son rutas que requieren sesión iniciada. 
    si la acción solicitada está en la lista y $_SESSION['usuario'] está vacío, redirige a index.php?action=login en vez de ejecutar el controlador.
*/
$rutasProtegidas = ['search', 'advanced_search', 'detail', 'api_search', 'oficina', 'buscar_oficinas', 'activos_oficina', 'activos_erp_oficina', 'usuario'];
if (in_array($action, $rutasProtegidas) && empty($_SESSION['usuario'])) {
    header('Location: index.php?action=login');
    exit;
}

// Controlador prinicipal: según la acción solicitada, se carga el controlador correspondiente y se ejecuta el método adecuado.
switch ($action) {

    case 'login':
        require_once BASE_PATH . '/controllers/AuthController.php';
        $ctrl = new AuthController();
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $ctrl->doLogin() : $ctrl->showLogin();
        break;

    case 'logout':
        require_once BASE_PATH . '/controllers/AuthController.php';
        (new AuthController())->doLogout();
        break;

    case 'search':
        require_once BASE_PATH . '/controllers/CiController.php';
        $ctrl = new CiController();
        ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET['q']))
            ? $ctrl->doSearch()
            : $ctrl->showSearch();
        break;

    case 'advanced_search':
        require_once BASE_PATH . '/controllers/CiController.php';
        (new CiController())->doAdvancedSearch();
        break;

    case 'detail':
        require_once BASE_PATH . '/controllers/CiController.php';
        (new CiController())->showDetail();
        break;

    case 'api_search':
        require_once BASE_PATH . '/controllers/CiController.php';
        (new CiController())->apiSearch();
        break;

    // --- Lista de CI de una oficina (desde ficha de oficina) ---
    case 'activos_oficina':
        require_once BASE_PATH . '/controllers/CiController.php';
        (new CiController())->showActivosOficina();
        break;

    // --- Lista de activos ERP de una oficina (desde ficha de oficina) ---
    case 'activos_erp_oficina':
        require_once BASE_PATH . '/controllers/OficinaController.php';
        (new OficinaController())->showActivosErp();
        break;

    // --- Búsqueda de oficinas (formulario + resultados paginados) ---
    case 'buscar_oficinas':
        require_once BASE_PATH . '/controllers/OficinaController.php';
        $ctrl = new OficinaController();
        /*
         * Si hay algún filtro en la URL se ejecuta la búsqueda;
         * si no, se muestra el formulario vacío.
         */
        $hayFiltros = !empty(array_filter([
            $_GET['id_oficina']      ?? '',
            $_GET['nombre_oficina']  ?? '',
            $_GET['direccion']       ?? '',
            $_GET['cp']              ?? '',
            $_GET['ciudad']          ?? '',
            $_GET['unidad_organica'] ?? '',
            $_GET['cod_pais']        ?? '',
        ]));
        $hayFiltros ? $ctrl->doBusqueda() : $ctrl->showBusqueda();
        break;

    // --- Ficha completa de una oficina ---
    case 'oficina':
        require_once BASE_PATH . '/controllers/OficinaController.php';
        (new OficinaController())->showOficina();
        break;

    // --- Ficha de usuario ITSM (desde "Último usuario" en detalle de CI) ---
    case 'usuario':
        require_once BASE_PATH . '/controllers/UsuarioController.php';
        (new UsuarioController())->showUsuario();
        break;

    default:
        header('Location: index.php?action=login');
        exit;
}
