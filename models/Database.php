<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : models/Database.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción   : Se crean las instancias de las BBDD usando el Patrón Singleton 
 *                 PDO — gestiona conexiones CMDB y ERP
 * ========================================================= */

class Database {
    private static ?PDO $instanceCmdb = null;
    private static ?PDO $instanceErp  = null;

    private function __construct() {}
    private function __clone() {}

    public static function getCmdb(): PDO {
        if (self::$instanceCmdb === null) {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_CMDB_HOST, DB_CMDB_PORT, DB_CMDB_NAME, DB_CMDB_CHARSET);
            self::$instanceCmdb = new PDO($dsn, DB_CMDB_USER, DB_CMDB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instanceCmdb;
    }

    public static function getErp(): PDO {
        if (self::$instanceErp === null) {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_ERP_HOST, DB_ERP_PORT, DB_ERP_NAME, DB_ERP_CHARSET);
            self::$instanceErp = new PDO($dsn, DB_ERP_USER, DB_ERP_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instanceErp;
    }
}
