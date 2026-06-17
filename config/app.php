<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : config/app.php
 * NOTA: BASE_PATH ya definido en index.php, no se redefine.
 * ========================================================= */

define('APP_ENV', 'development');

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

date_default_timezone_set('Europe/Madrid');

define('VIEWS_PATH',       BASE_PATH . '/views');
define('CONTROLLERS_PATH', BASE_PATH . '/controllers');
define('MODELS_PATH',      BASE_PATH . '/models');
