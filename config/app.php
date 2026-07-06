<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : config/app.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 * 
 * Descripción   : Configuración de la aplicación (entorno, rutas, errores).
 * NOTA          : BASE_PATH ya definido en index.php, no se redefine.
 * ========================================================= */

define('APP_ENV', 'desarrollo');

/* Configuración de la visualización de errores según el entorno:
 * - Desarrollo: Muestra los errores en pantalla para facilitar la depuración.
 * - Producción: Oculta los errores al usuario final por seguridad.
 */
if (APP_ENV === 'desarrollo') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

date_default_timezone_set('Europe/Madrid');

// Rutas para controlar los directorios principales (MVC) */
define('VIEWS_PATH',       BASE_PATH . '/views');
define('CONTROLLERS_PATH', BASE_PATH . '/controllers');
define('MODELS_PATH',      BASE_PATH . '/models');

// Valores para control de la Sesión
define('SESSION_LIFETIME', 3600);
define('SESSION_NAME', 'CMDB_SESSION');

// Valores de la aplicación para un futuro uso (p.ej., en el pie de página)
define('APP_NAME', 'CMDB');
define('APP_VERSION', '1.0.0');