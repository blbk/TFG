<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : models/UsuarioModel.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción   : Modelo para manejar datos del usuario que
 *                  se autentica en la aplicación 
 *                  (login, contraseña, perfil).
 * Métodos:
 *   - findByLogin($login)
 *   - autenticar($login, $password)
 * Dependencias:
 *   - models/Database.php
 * ========================================================= */

require_once BASE_PATH . '/models/Database.php';

class UsuarioModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getCmdb();
    }


    /* findByLogin($login): ?array
        Devuelve un array con los datos del usuario
        a partir de su login, o null si no existe. 
     */
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

   /* autenticar($login, $password): ?array
        Comprueba si el login y la contraseña son correctos. 
        Devuelve un array con los datos del usuario (sin la contraseña) si son correctos, 
        o null si no lo son.
    */
    public function autenticar(string $login, string $password): ?array {
        $usuario = $this->findByLogin($login);
        if ($usuario && password_verify($password, $usuario['pwd_hash'])) {
            unset($usuario['pwd_hash']);
            return $usuario;
        }
        return null;
    }
}
