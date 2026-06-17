<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : models/UsuarioModel.php
 * ========================================================= */

require_once BASE_PATH . '/models/Database.php';

class UsuarioModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getCmdb();
    }

    public function findByLogin(string $login): ?array {
        $sql  = "SELECT login, pwd_hash, nombre, apellidos, oficina, grupo, perfil
                 FROM v_usuario_perfil
                 WHERE login = :login
                 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':login' => $login]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function autenticar(string $login, string $password): ?array {
        $usuario = $this->findByLogin($login);
        if ($usuario && password_verify($password, $usuario['pwd_hash'])) {
            unset($usuario['pwd_hash']);
            return $usuario;
        }
        return null;
    }
}
