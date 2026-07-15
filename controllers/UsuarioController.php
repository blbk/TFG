<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : controllers/UsuarioController.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción   : Controlador de la ficha de usuario (datos ITSM).
 *                 Se accede pulsando sobre el login del "Último usuario"
 *                 en la sección "Datos del equipo" del detalle de un CI.
 *
 * Rutas (desde index.php):
 *   GET action=usuario&login=XXX
 * ========================================================= */

require_once BASE_PATH . '/models/UsuarioItsmModel.php';

class UsuarioController {

    /** Instancia del modelo de usuario ITSM */
    private UsuarioItsmModel $model;

    public function __construct() {
        $this->model = new UsuarioItsmModel();
    }

    /* ------------------------------------------------------------------
     * showUsuario()
     * Muestra la ficha de un usuario a partir de su login.
     *
     * Parámetro GET:
     *   login — obligatorio, login del usuario (clave de usuario_itsm)
     *
     * Variables que se pasan a la vista:
     *   $login     — el login solicitado (siempre, aunque no exista)
     *   $usuario   — array de datos (login, nomape, tlf_movil, foto)
     *                o null si no se encontró
     *   $rutaFoto  — ruta a la imagen de perfil (con fallback a 0.jpg
     *                gestionado en la propia vista vía onerror)
     *   $error     — string|null
     * ------------------------------------------------------------------ */
    public function showUsuario(): void {
        $login = trim($_GET['login'] ?? '');

        $usuario  = null;
        $rutaFoto = $this->model->getRutaFotoGenerica();
        $error    = null;

        if ($login === '') {
            // Sin login: no hay nada que mostrar, volver a la búsqueda
            header('Location: index.php?action=search');
            exit;
        }

        try {
            $usuario = $this->model->findByLogin($login);

            if ($usuario) {
                $rutaFoto = $this->model->getRutaFoto((int)$usuario['foto']);
                $equipos  = $this->model->getEquipos($login);
                // $licencias  = $this->model->getLicencias($login); Desarrollo futuro
            } else {
                $error = "El usuario '$login' no existe en el sistema ITSM.";
            }
        } catch (PDOException $e) {
            $error = 'Error al obtener los datos del usuario.';
            if (APP_ENV === 'desarrollo') {
                $error .= ' [' . $e->getMessage() . ']';
            }
        }

        require VIEWS_PATH . '/usuario.php';
    }
}
