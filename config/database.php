<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : config/database.php
 * Descripción   : Configuración de conexión a las BBDD
 *                 *** EDITAR con tus credenciales ***
 * ========================================================= */

// --- Base de datos CMDB ---
define('DB_CMDB_HOST',    'localhost');
define('DB_CMDB_PORT',    '3306');
define('DB_CMDB_NAME',    'cmdb');
define('DB_CMDB_USER',    'root');
define('DB_CMDB_PASS',    'root');
define('DB_CMDB_CHARSET', 'utf8mb4');

// --- Base de datos ERP ---
define('DB_ERP_HOST',    'localhost');
define('DB_ERP_PORT',    '3306');
define('DB_ERP_NAME',    'erp');
define('DB_ERP_USER',    'root');
define('DB_ERP_PASS',    'root');
define('DB_ERP_CHARSET', 'utf8mb4');

// --- Sesión ---
define('SESSION_LIFETIME', 3600);
define('SESSION_NAME',     'CMDB_SESSION');

// --- App ---
define('APP_NAME',    'CMDB');
define('APP_VERSION', '1.0.0');
