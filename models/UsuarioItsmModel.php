<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : models/UsuarioItsmModel.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción   : Modelo para la ficha de usuario (datos ITSM).
 *                 Consulta la tabla usuario_itsm de la CMDB, que
 *                 almacena los datos de contacto del usuario y un
 *                 índice de foto que apunta al repositorio de imágenes
 *                 public/img/usuarios/{foto}.jpg.
 *
 * Estructura de la tabla (creada por el usuario):
 *   CREATE TABLE usuario_itsm (
 *       login     VARCHAR(15) PRIMARY KEY,
 *       nomape    VARCHAR(100),
 *       tlf_movil VARCHAR(15),
 *       foto      INT DEFAULT 0 NOT NULL
 *   );
 * ========================================================= */

require_once BASE_PATH . '/models/Database.php';

class UsuarioItsmModel {

    /** Conexión PDO a la base de datos CMDB (donde vive usuario_itsm) */
    private PDO $db;

    public function __construct() {
        $this->db = Database::getCmdb();
    }

    /* ------------------------------------------------------------------
     * findByLogin()
     * Devuelve los datos de contacto de un usuario a partir de su login.
     *
     * Parámetro : $login — login del usuario (clave primaria de usuario_itsm)
     * Retorna   : array con login, nomape, tlf_movil, foto — o null si
     *             no existe ningún registro con ese login.
     * ------------------------------------------------------------------ */
    public function findByLogin(string $login): ?array {
        $stmt = $this->db->prepare(
            "SELECT login, nomape, tlf_movil, foto
             FROM usuario_itsm
             WHERE login = :login
             LIMIT 1"
        );
        $stmt->execute([':login' => $login]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /* ------------------------------------------------------------------
     * getRutaFoto()
     * Devuelve la ruta relativa (desde la raíz del proyecto) a la imagen
     * de perfil del usuario, a partir del valor numérico de la columna
     * 'foto'. El valor 0 corresponde a la imagen genérica (0.jpg).
     *
     * No se valida la existencia física del fichero aquí: la vista
     * incluye un fallback en el atributo onerror del <img> que recurre
     * a 0.jpg si la imagen específica no existe en el repositorio.
     *
     * Parámetro : $foto — índice numérico de la foto (0, 1, 2, 3…)
     * Retorna   : string — ruta relativa, p.ej. "public/img/usuarios/2.jpg"
     * ------------------------------------------------------------------ */
    public function getRutaFoto(int $foto): string {
        $indice = $foto >= 0 ? $foto : 0;
        return 'public/img/usuarios/' . $indice . '.jpg';
    }

    /* ------------------------------------------------------------------
     * getRutaFotoGenerica()
     * Ruta a la imagen genérica (0.jpg), usada como fallback cuando la
     * foto específica del usuario no existe en el repositorio.
     * ------------------------------------------------------------------ */
    public function getRutaFotoGenerica(): string {
        return 'public/img/usuarios/0.jpg';
    }
}
