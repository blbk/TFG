<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : config/database.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 * Descripción   : Parámetros de Configuración de conexión a las BBDD
 * NOTA          : Este fichero contiene información sensible (usuario y contraseña de BBDD),
 *                 por lo que no debe subirse a repositorios públicos.
 * ========================================================= */

// --- Base de datos CMDB ---
define('DB_CMDB_HOST', 'localhost');
define('DB_CMDB_PORT', '3306');
define('DB_CMDB_NAME', 'cmdb');
define('DB_CMDB_USER', 'consulta');
define('DB_CMDB_PASS', '[CodaX4UV5E');
define('DB_CMDB_CHARSET', 'utf8mb4');

// --- Base de datos ERP ---
define('DB_ERP_HOST', 'localhost');
define('DB_ERP_PORT', '3306');
define('DB_ERP_NAME', 'erp');
define('DB_ERP_USER', 'consulta');
define('DB_ERP_PASS', '[CodaX4UV5E');
define('DB_ERP_CHARSET', 'utf8mb4');


