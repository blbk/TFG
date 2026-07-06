<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : models/CiModel.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 * 
 * Descripción   : Modelo para manejar la búsqueda y detalle de CI
 *                  (Configuration Item) en la CMDB, incluyendo búsquedas
 *                simples, avanzadas y filtradas por oficina.
 * Métodos       : buscarSimple(), buscarAvanzado(), detalle(), getClases(), estadisticas(),
 *                 buscarPorOficinaYClase()
 * ========================================================= */

require_once BASE_PATH . '/models/Database.php';

class CiModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getCmdb();
    }

    /* ------------------------------------------------------------------
     * buscarSimple()
     * Búsqueda por término libre con paginación.
     * Busca en marca, modelo, serie, clase, nombre local, hostname, IP,
     * login del último usuario (tabla pc) e identificador del CI (id_ci).
     *
     * Parámetros:
     *   $termino   — cadena a buscar (se aplica LIKE %termino%)
     *   $pagina    — número de página base 1
     *   $porPagina — registros por página (defecto 20)
     *
     * Retorna array con claves:
     *   filas, total, pagina, porPagina, paginas
     * ------------------------------------------------------------------ */
    public function buscarSimple(string $termino, int $pagina = 1, int $porPagina = 20): array {
        $like = '%' . $termino . '%';

        /* SQL base de la búsqueda — se reutiliza para COUNT y SELECT */
        $sqlBase = "FROM ci c
                    INNER JOIN clase_ci cl  ON c.id_clase   = cl.id_clase
                    LEFT  JOIN pc           ON pc.id_ci     = c.id_ci
                    LEFT  JOIN impresora imp ON imp.id_ci   = c.id_ci
                    LEFT  JOIN red_ci rc    ON rc.id_ci     = c.id_ci
                WHERE  c.marca            LIKE :t1
                    OR c.modelo           LIKE :t2
                    OR c.numero_serie     LIKE :t3
                    OR cl.nombre          LIKE :t4
                    OR pc.nombre_local    LIKE :t5
                    OR imp.nombre_local   LIKE :t6
                    OR rc.hostname        LIKE :t7
                    OR rc.direccion_ip    LIKE :t8
                    OR pc.login           LIKE :t9
                    OR CAST(c.id_ci AS CHAR) LIKE :t10";

        /* Contar total de coincidencias sin LIMIT */
        // Se usa COUNT(DISTINCT c.id_ci) porque un CI puede coincidir en varias columnas
        // El bucle FOR se usa para enlazar los 10 parámetros :t1 … :t10 con la búsqueda $like
        $stmtTotal = $this->db->prepare("SELECT COUNT(DISTINCT c.id_ci) $sqlBase");
        for ($i = 1; $i <= 10; $i++) $stmtTotal->bindValue(":t$i", $like);
        $stmtTotal->execute();
        $total = (int)$stmtTotal->fetchColumn();

        /* Calcular offset 
        (cuántas filas de la BD "saltar" antes de mostrar los resultados de la página actual.)
        */
        $offset = max(0, $pagina - 1) * $porPagina;

        /* Consulta paginada */
        $stmt = $this->db->prepare(
            "SELECT DISTINCT
                c.id_ci,
                cl.nombre AS clase,
                c.marca,
                c.modelo,
                c.numero_serie,
                COALESCE(pc.nombre_local, imp.nombre_local) AS nombre_local,
                rc.direccion_ip,
                rc.hostname,
                pc.login AS login_usuario,
                c.fecha
            $sqlBase
            ORDER BY cl.nombre, c.marca, c.modelo
            LIMIT :limit OFFSET :offset"
        );
        for ($i = 1; $i <= 10; $i++) $stmt->bindValue(":t$i", $like);
        $stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
        $stmt->execute();

        return [
            'filas'     => $stmt->fetchAll(),
            'total'     => $total,
            'pagina'    => $pagina,
            'porPagina' => $porPagina,
            'paginas'   => (int)ceil($total / max(1, $porPagina)),
        ];
    }

    /* ------------------------------------------------------------------
     * buscarAvanzado()
     * Búsqueda con filtros opcionales CMDB + ERP, con paginación.
     *
     * Parámetros:
     *   $f         — array de filtros activos
     *   $pagina    — número de página base 1
     *   $porPagina — registros por página (20 por defecto)
     *
     * Retorna array con claves:
     *   filas, total, pagina, porPagina, paginas
     * ------------------------------------------------------------------ */
    public function buscarAvanzado(array $f, int $pagina = 1, int $porPagina = 20): array {
        $where  = [];
        $params = [];

        // ── Filtros CMDB ──────────────────────────────────────────────
        if (!empty($f['id_ci']))        { $where[] = 'c.id_ci = :id_ci';            $params[':id_ci']    = (int)$f['id_ci']; }
        if (!empty($f['clase']))        { $where[] = 'c.id_clase = :clase';         $params[':clase']    = $f['clase']; }
        if (!empty($f['marca']))        { $where[] = 'c.marca LIKE :marca';         $params[':marca']    = '%'.$f['marca'].'%'; }
        if (!empty($f['modelo']))       { $where[] = 'c.modelo LIKE :modelo';       $params[':modelo']   = '%'.$f['modelo'].'%'; }
        if (!empty($f['numero_serie'])) { $where[] = 'c.numero_serie LIKE :ns';     $params[':ns']       = '%'.$f['numero_serie'].'%'; }
        if (!empty($f['hostname']))     { $where[] = 'rc.hostname LIKE :hostname';  $params[':hostname'] = '%'.$f['hostname'].'%'; }
        if (!empty($f['ip']))           { $where[] = 'rc.direccion_ip LIKE :ip';    $params[':ip']       = '%'.$f['ip'].'%'; }
        if (!empty($f['login']))        { $where[] = 'pc.login LIKE :login';        $params[':login']    = '%'.$f['login'].'%'; }
        if (!empty($f['nombre_local'])) {
            $where[] = '(pc.nombre_local LIKE :nl OR imp.nombre_local LIKE :nl2)';
            $params[':nl']  = '%'.$f['nombre_local'].'%';
            $params[':nl2'] = '%'.$f['nombre_local'].'%';
        }
        if (!empty($f['fecha_desde']))  { $where[] = 'c.fecha >= :fd'; $params[':fd'] = $f['fecha_desde']; }
        if (!empty($f['fecha_hasta']))  { $where[] = 'c.fecha <= :fh'; $params[':fh'] = $f['fecha_hasta']; }

        // ── Filtros ERP (oficina) ─────────────────────────────────────
        // Cadena: cmdb.red_ci → cmdb.red_oficina → erp.oficina → erp.ciudad/unidad_organica
        $needsErp = !empty($f['id_oficina'])      || !empty($f['nombre_oficina']) ||
                    !empty($f['direccion'])       || !empty($f['cp'])             ||
                    !empty($f['unidad_organica']) || !empty($f['ciudad']);

        if (!empty($f['id_oficina']))      { $where[] = 'eo.id_oficina = :id_of';    $params[':id_of']   = (int)$f['id_oficina']; }
        if (!empty($f['nombre_oficina']))  { $where[] = 'eo.nombre LIKE :nom_of';    $params[':nom_of']  = '%'.$f['nombre_oficina'].'%'; }
        if (!empty($f['direccion']))       { $where[] = 'eo.direccion LIKE :dir';    $params[':dir']     = '%'.$f['direccion'].'%'; }
        if (!empty($f['cp']))              { $where[] = 'eo.cp LIKE :cp';            $params[':cp']      = '%'.$f['cp'].'%'; }
        if (!empty($f['unidad_organica'])) { $where[] = 'euo.nombre LIKE :uorg';    $params[':uorg']    = '%'.$f['unidad_organica'].'%'; }
        if (!empty($f['ciudad']))          { $where[] = 'eciu.nombre LIKE :ciudad';  $params[':ciudad']  = '%'.$f['ciudad'].'%'; }

        // Se construye la cláusula WHERE combinando los filtros activos con AND. Si no hay filtros, no se añade WHERE.
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // JOINs ERP: solo se añaden si hay algún filtro ERP activo
        //
        // La oficina de un CI se resuelve de dos formas posibles:
        //   1. Por red propia:    ci → red_ci.gateway → red_oficina.id_oficina
        //      (válido para PC, impresoras, switches, routers… que tienen IP propia)
        //   2. Por v_monitor_oficina: para CI sin red propia (p.ej. monitores),
        //      la vista resuelve la oficina a través del PC al que están
        //      conectados (relacion_ci → PC → red_ci → red → red_oficina).
        //
        // COALESCE() usa la primera fuente disponible; si un CI tiene ambas
        // (caso raro) prevalece la red propia.
        $joinErp = $needsErp ? "
            LEFT  JOIN red_oficina         ro   ON ro.gateway       = rc.gateway
            LEFT  JOIN v_monitor_oficina   vmo  ON vmo.id_ci         = c.id_ci
            LEFT  JOIN erp.oficina         eo   ON eo.id_oficina    = COALESCE(ro.id_oficina, vmo.id_oficina)
            LEFT  JOIN erp.ciudad          eciu ON eciu.id_ciudad   = eo.id_ciudad
            LEFT  JOIN erp.unidad_organica euo  ON euo.id_uorganica = eo.id_uorganica" : '';

        // Campos ERP en el SELECT: solo cuando hay filtros ERP
        $selectErp = $needsErp ? ",
            eo.id_oficina,
            eo.nombre      AS oficina,
            eo.direccion   AS oficina_dir,
            eo.cp          AS oficina_cp,
            eciu.nombre    AS ciudad,
            euo.nombre     AS unidad_organica" : '';

        /* FROM + JOINs + WHERE reutilizables para COUNT y SELECT */
        $sqlFrom = "FROM ci c
                    INNER JOIN clase_ci cl   ON c.id_clase  = cl.id_clase
                    LEFT  JOIN pc            ON pc.id_ci    = c.id_ci
                    LEFT  JOIN impresora imp ON imp.id_ci   = c.id_ci
                    LEFT  JOIN red_ci rc     ON rc.id_ci    = c.id_ci
                    $joinErp
                $whereClause";

        /* Contar total */
        $stmtTotal = $this->db->prepare("SELECT COUNT(DISTINCT c.id_ci) $sqlFrom");
        $stmtTotal->execute($params);
        $total = (int)$stmtTotal->fetchColumn();

        $offset = max(0, $pagina - 1) * $porPagina;

        /* Consulta paginada */
        $sql = "SELECT DISTINCT
                    c.id_ci,
                    cl.nombre AS clase,
                    c.marca,
                    c.modelo,
                    c.numero_serie,
                    COALESCE(pc.nombre_local, imp.nombre_local) AS nombre_local,
                    rc.direccion_ip,
                    rc.hostname,
                    pc.login AS login_usuario,
                    c.fecha
                    $selectErp
                $sqlFrom
                ORDER BY cl.nombre, c.marca, c.modelo
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
        $stmt->execute();

        return [
            'filas'     => $stmt->fetchAll(),
            'total'     => $total,
            'pagina'    => $pagina,
            'porPagina' => $porPagina,
            'paginas'   => (int)ceil($total / max(1, $porPagina)),
        ];
    }

    /* ------------------------------------------------------------------ 
     * Detalle completo de un CI para mostrar en la ficha de CI.
     * parámetros:
     *   $id — identificador interno del CI
     *    
     * Devuelve un array con todos los datos del CI, incluyendo:
     *  - Datos básicos técnicos (ci + clase_ci)
     *  - Datos de red (red_ci)
     *  - Datos de la red (red) si tiene IP propia
     *  - Datos extendidos según clase (pc, impresora, monitor)
     *  - Relaciones con otros CI (relacion_ci)
     *  - null si no existe el CI. 
    */
    public function detalle(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT c.*, cl.nombre AS clase
             FROM ci c INNER JOIN clase_ci cl ON c.id_clase = cl.id_clase
             WHERE c.id_ci = :id");
        $stmt->execute([':id' => $id]);
        $ci = $stmt->fetch();
        if (!$ci) return null;

        // Red
        $stmt = $this->db->prepare("SELECT * FROM red_ci WHERE id_ci = :id");
        $stmt->execute([':id' => $id]);
        $ci['red'] = $stmt->fetch() ?: null;

        // Info de la red (VLAN, CIDR)
        if (!empty($ci['red']['gateway'])) {
            $stmt = $this->db->prepare("SELECT * FROM red WHERE gateway = :gw");
            $stmt->execute([':gw' => $ci['red']['gateway']]);
            $ci['red']['info_red'] = $stmt->fetch() ?: null;
        }

        // Extensión según clase
        $clase = strtolower($ci['clase']);
        if (str_contains($clase, 'pc') || str_contains($clase, 'ordenador') || str_contains($clase, 'laptop')) {
            $stmt = $this->db->prepare("SELECT * FROM pc WHERE id_ci = :id");
            $stmt->execute([':id' => $id]);
            $ci['detalle_pc'] = $stmt->fetch() ?: null;
        } elseif (str_contains($clase, 'impresora')) {
            $stmt = $this->db->prepare("SELECT * FROM impresora WHERE id_ci = :id");
            $stmt->execute([':id' => $id]);
            $ci['detalle_impresora'] = $stmt->fetch() ?: null;
        } elseif (str_contains($clase, 'monitor')) {
            $stmt = $this->db->prepare("SELECT * FROM monitor WHERE id_ci = :id");
            $stmt->execute([':id' => $id]);
            $ci['detalle_monitor'] = $stmt->fetch() ?: null;
        }

        // Relaciones
        $stmt = $this->db->prepare(
            "SELECT r.id_ci_origen, r.id_ci_destino,
                    cr.nombre AS tipo_relacion,
                    co.marca AS marca_origen, co.modelo AS modelo_origen, clo.nombre AS clase_origen,
                    cd.marca AS marca_destino, cd.modelo AS modelo_destino, cld.nombre AS clase_destino
             FROM relacion_ci r
                INNER JOIN clase_relacion cr ON r.id_relacion    = cr.id_relacion
                INNER JOIN ci co             ON co.id_ci         = r.id_ci_origen
                INNER JOIN clase_ci clo      ON clo.id_clase     = co.id_clase
                INNER JOIN ci cd             ON cd.id_ci         = r.id_ci_destino
                INNER JOIN clase_ci cld      ON cld.id_clase     = cd.id_clase
             WHERE r.id_ci_origen = :id OR r.id_ci_destino = :id2");
        $stmt->execute([':id' => $id, ':id2' => $id]);
        $ci['relaciones'] = $stmt->fetchAll();

        return $ci;
    }

    /* ------------------------------------------------------------------ 
     * Lista de clases para el selector de búsqueda avanzada 
     * parámetros: ninguno
     * Retorna array de arrays con claves: id_clase, nombre
    */
    public function getClases(): array {
        return $this->db->query("SELECT id_clase, nombre FROM clase_ci ORDER BY nombre")->fetchAll();
    }

    /* ------------------------------------------------------------------
     * Estadísticas rápidas para el cuadro de mando 
     * Retorna array con claves:
     *   total_ci — total de CI en la CMDB
     *   por_clase — array de arrays con claves: nombre, total 
    */
    public function estadisticas(): array {
        $stats = [];
        $stats['total_ci']  = $this->db->query("SELECT COUNT(*) FROM ci")->fetchColumn();
        $stats['por_clase'] = $this->db->query(
            "SELECT cl.nombre, COUNT(*) AS total
             FROM ci c INNER JOIN clase_ci cl ON c.id_clase = cl.id_clase
             GROUP BY cl.nombre ORDER BY total DESC")->fetchAll();
        return $stats;
    }

    /* ------------------------------------------------------------------
     * buscarPorOficinaYClase()
     * Devuelve los CI de una oficina concreta, opcionalmente filtrados
     * por clase. Se usa desde la ficha de oficina cuando el usuario pulsa
     * en una fila de la sección "Activos conectados — CMDB".
     *
     * La oficina de un CI se resuelve de dos formas (ver buscarAvanzado
     * para más detalle):
     *   1. Por red propia:        ci → red_ci → red_oficina
     *   2. Por v_monitor_oficina:  para CI sin red propia (monitores),
     *      a través del PC al que están conectados.
     *
     * Si $idClase es 0 se devuelven todas las clases de la oficina.
     *
     * Parámetros:
     *   $idOficina — ID de la oficina (erp.oficina)
     *   $idClase   — ID de la clase de CI (0 = todas)
     *   $pagina    — número de página base 1
     *   $porPagina — registros por página (defecto 20)
     *
     * Retorna array con claves: filas, total, pagina, porPagina, paginas,
     *   más los metadatos de contexto: nombre_oficina, nombre_clase
     * ------------------------------------------------------------------ */
    public function buscarPorOficinaYClase(
        int $idOficina,
        int $idClase   = 0,
        int $pagina    = 1,
        int $porPagina = 20
    ): array {
        $where  = ['COALESCE(ro.id_oficina, vmo.id_oficina) = :id_oficina'];
        $params = [':id_oficina' => $idOficina];

        if ($idClase > 0) {
            $where[]         = 'c.id_clase = :id_clase';
            $params[':id_clase'] = $idClase;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        /* SQL base reutilizable para COUNT y SELECT */
        $sqlFrom = "FROM ci c
                    INNER JOIN clase_ci    cl  ON cl.id_clase  = c.id_clase
                    LEFT  JOIN pc              ON pc.id_ci     = c.id_ci
                    LEFT  JOIN impresora   imp ON imp.id_ci    = c.id_ci
                    LEFT  JOIN red_ci      rc  ON rc.id_ci     = c.id_ci
                    LEFT  JOIN red_oficina ro  ON ro.gateway   = rc.gateway
                    LEFT  JOIN v_monitor_oficina vmo ON vmo.id_ci = c.id_ci
                    $whereClause";

        /* Total para paginación */
        $stmtTotal = $this->db->prepare("SELECT COUNT(DISTINCT c.id_ci) $sqlFrom");
        $stmtTotal->execute($params);
        $total = (int)$stmtTotal->fetchColumn();

        $offset = max(0, $pagina - 1) * $porPagina;

        /* Consulta paginada */
        $sql = "SELECT DISTINCT
                    c.id_ci,
                    cl.nombre        AS clase,
                    c.marca,
                    c.modelo,
                    c.numero_serie,
                    COALESCE(pc.nombre_local, imp.nombre_local) AS nombre_local,
                    rc.direccion_ip,
                    rc.hostname,
                    pc.login         AS login_usuario,
                    c.fecha
                $sqlFrom
                ORDER BY cl.nombre, c.marca, c.modelo
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
        $stmt->execute();

        /* Nombre de la clase para el título de la vista */
        $nombreClase = 'Todos los activos';
        if ($idClase > 0) {
            $stmtCl = $this->db->prepare(
                "SELECT nombre FROM clase_ci WHERE id_clase = :id LIMIT 1"
            );
            $stmtCl->execute([':id' => $idClase]);
            $rowCl = $stmtCl->fetch();
            $nombreClase = $rowCl ? $rowCl['nombre'] : $nombreClase;
        }

        return [
            'filas'         => $stmt->fetchAll(),
            'total'         => $total,
            'pagina'        => $pagina,
            'porPagina'     => $porPagina,
            'paginas'       => (int)ceil($total / max(1, $porPagina)),
            'id_oficina'    => $idOficina,
            'id_clase'      => $idClase,
            'nombre_clase'  => $nombreClase,
        ];
    }
}
