<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : models/OficinaModel.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción   : Modelo para la consulta de datos de una oficina.
 *                 Combina información de dos bases de datos:
 *                   - ERP  : datos organizativos y de ubicación
 *                   - CMDB : datos de red y activos inventariados
 *
 * Dependencias  : models/Database.php (Singleton PDO)
 * ========================================================= */

require_once BASE_PATH . '/models/Database.php';

class OficinaModel {

    /** Conexión PDO a la base de datos CMDB */
    private PDO $dbCmdb;

    /** Conexión PDO a la base de datos ERP */
    private PDO $dbErp;

    public function __construct() {
        $this->dbCmdb = Database::getCmdb();
        $this->dbErp  = Database::getErp();
    }

    /* ------------------------------------------------------------------
     * findById()
     * Devuelve los datos completos de una oficina a partir de su ID.
     * Fuente: base de datos ERP.
     * ------------------------------------------------------------------ */
    public function findById(int $idOficina): ?array {
        $sql = "SELECT
                    o.id_oficina,
                    o.cod_oficina,
                    o.nombre        AS nombre_oficina,
                    o.direccion,
                    o.cp,
                    c.nombre        AS ciudad,
                    p.nombre        AS pais,
                    p.cod_pais,
                    uo.nombre       AS unidad_organica
                FROM oficina o
                    INNER JOIN ciudad           c  ON c.id_ciudad    = o.id_ciudad
                    INNER JOIN pais             p  ON p.cod_pais      = c.cod_pais
                    INNER JOIN unidad_organica  uo ON uo.id_uorganica = o.id_uorganica
                WHERE o.id_oficina = :id";

        $stmt = $this->dbErp->prepare($sql);
        $stmt->execute([':id' => $idOficina]);
        $oficina = $stmt->fetch();
        return $oficina ?: null;
    }

    /* ------------------------------------------------------------------
     * findByIdCi()
     * Localiza la oficina asociada a un CI concreto.
     *
     * Estrategia (por orden de prioridad):
     *
     *   1. ERP por número de serie (fuente canónica):
     *        cmdb.ci.numero_serie → erp.activo.numero_serie → erp.activo.id_oficina
     *      Es la fuente más fiable porque refleja el inventario patrimonial
     *      independientemente de si el CI tiene red asignada o no.
     *
     *   2. Fallback por red (si el CI no tiene serie o no está en el ERP):
     *        cmdb.red_ci.gateway → cmdb.red_oficina.id_oficina
     *      Útil para CI sin número de serie o no registrados en el ERP.
     *
     * El método devuelve también qué fuente se usó en la clave 'fuente':
     *   'erp'  — se encontró por número de serie en el ERP
     *   'red'  — se encontró por gateway en red_oficina (fallback)
     *   null   — no se encontró por ninguna vía
     * ------------------------------------------------------------------ */
    public function findByIdCi(int $idCi): ?array {

        // ── Paso 1: obtener el número de serie del CI ─────────────────
        $stmtSerie = $this->dbCmdb->prepare(
            "SELECT numero_serie FROM ci WHERE id_ci = :id LIMIT 1"
        );
        $stmtSerie->execute([':id' => $idCi]);
        $rowSerie = $stmtSerie->fetch();
        $numeroSerie = $rowSerie['numero_serie'] ?? null;

        // ── Paso 2: buscar en el ERP por número de serie ──────────────
        if (!empty($numeroSerie)) {
            $stmtErp = $this->dbErp->prepare(
                "SELECT id_oficina FROM activo WHERE numero_serie = :ns LIMIT 1"
            );
            $stmtErp->execute([':ns' => $numeroSerie]);
            $rowErp = $stmtErp->fetch();

            if ($rowErp && !empty($rowErp['id_oficina'])) {
                $oficina = $this->findById((int)$rowErp['id_oficina']);
                if ($oficina) {
                    $oficina['fuente'] = 'erp'; // fuente canónica: ERP
                    return $oficina;
                }
            }
        }

        // ── Paso 3: fallback — deducir oficina por red ────────────────
        // Solo se usa si el CI no tiene serie o no está en el ERP.
        //
        // Dos vías posibles:
        //   a) Red propia:        ci → red_ci.gateway → red_oficina.id_oficina
        //   b) v_monitor_oficina: para CI sin red propia (p.ej. monitores),
        //      a través del PC al que están conectados.
        $stmtGw = $this->dbCmdb->prepare(
            "SELECT gateway FROM red_ci WHERE id_ci = :id LIMIT 1"
        );
        $stmtGw->execute([':id' => $idCi]);
        $rowGw = $stmtGw->fetch();

        $idOficinaRed = null;

        if ($rowGw && !empty($rowGw['gateway'])) {
            $stmtRo = $this->dbCmdb->prepare(
                "SELECT id_oficina FROM red_oficina WHERE gateway = :gw LIMIT 1"
            );
            $stmtRo->execute([':gw' => $rowGw['gateway']]);
            $rowRo = $stmtRo->fetch();

            if ($rowRo && !empty($rowRo['id_oficina'])) {
                $idOficinaRed = (int)$rowRo['id_oficina'];
            }
        }

        if ($idOficinaRed === null) {
            // Fallback: CI sin red propia (p.ej. monitor conectado a un PC)
            $stmtVmo = $this->dbCmdb->prepare(
                "SELECT id_oficina FROM v_monitor_oficina WHERE id_ci = :id LIMIT 1"
            );
            $stmtVmo->execute([':id' => $idCi]);
            $rowVmo = $stmtVmo->fetch();

            if ($rowVmo && !empty($rowVmo['id_oficina'])) {
                $idOficinaRed = (int)$rowVmo['id_oficina'];
            }
        }

        if ($idOficinaRed === null) return null;

        $oficina = $this->findById($idOficinaRed);
        if ($oficina) {
            $oficina['fuente'] = 'red'; // fallback: deducido por red
        }
        return $oficina;
    }

    /* ------------------------------------------------------------------
     * verificarConsistenciaOficina()
     * Comprueba si la oficina que se deduce de la IP del CI (vía gateway
     * en la CMDB) coincide con la oficina que el ERP tiene asignada al
     * activo con el mismo número de serie.
     *
     * Fuentes de datos:
     *   - Oficina por red (CMDB):
     *       ci → red_ci.gateway → red_oficina.id_oficina
     *   - Oficina por inventario (ERP):
     *       activo.numero_serie = ci.numero_serie → activo.id_oficina
     *
     * Retorna un array con:
     *   'estado'          => 'ok' | 'error' | 'sin_datos'
     *   'id_red'          => int|null   id_oficina deducido de la red
     *   'id_erp'          => int|null   id_oficina en el ERP
     *   'nombre_red'      => string|null nombre de la oficina por red
     *   'nombre_erp'      => string|null nombre de la oficina en ERP
     *   'mensaje'         => string      explicación del resultado
     *
     * Estado 'sin_datos': el CI no tiene red asignada, o no hay activo
     *   en el ERP con ese número de serie — no se puede verificar.
     * Estado 'ok'    : ambas fuentes apuntan a la misma oficina.
     * Estado 'error' : las fuentes apuntan a oficinas distintas.
     * ------------------------------------------------------------------ */
    public function verificarConsistenciaOficina(int $idCi): array {
        $sinDatos = fn(string $msg) => [
            'estado'      => 'sin_datos',
            'id_red'      => null,
            'id_erp'      => null,
            'nombre_red'  => null,
            'nombre_erp'  => null,
            'mensaje'     => $msg,
        ];

        // ── Paso 1: obtener el número de serie del CI ─────────────────
        $stmtSerie = $this->dbCmdb->prepare(
            "SELECT numero_serie FROM ci WHERE id_ci = :id LIMIT 1"
        );
        $stmtSerie->execute([':id' => $idCi]);
        $rowSerie = $stmtSerie->fetch();

        if (!$rowSerie || empty($rowSerie['numero_serie'])) {
            return $sinDatos('El CI no tiene número de serie: no se puede localizar en el ERP.');
        }
        $numeroSerie = $rowSerie['numero_serie'];

        // ── Paso 2: id_oficina deducido de la red (CMDB) ──────────────
        // Dos vías posibles:
        //   a) Red propia:        ci → red_ci.gateway → red_oficina.id_oficina
        //      (PCs, impresoras, switches, routers… con IP propia)
        //   b) v_monitor_oficina: para CI sin red propia (p.ej. monitores),
        //      la vista resuelve la oficina a través del PC al que están
        //      conectados (relacion_ci → PC → red_ci → red → red_oficina)
        $stmtGw = $this->dbCmdb->prepare(
            "SELECT gateway FROM red_ci WHERE id_ci = :id LIMIT 1"
        );
        $stmtGw->execute([':id' => $idCi]);
        $rowGw = $stmtGw->fetch();

        $idOficinaRed = null;

        if ($rowGw && !empty($rowGw['gateway'])) {
            // Vía a) — el CI tiene IP/red propia
            $stmtRo = $this->dbCmdb->prepare(
                "SELECT id_oficina FROM red_oficina WHERE gateway = :gw LIMIT 1"
            );
            $stmtRo->execute([':gw' => $rowGw['gateway']]);
            $rowRo = $stmtRo->fetch();

            if ($rowRo && !empty($rowRo['id_oficina'])) {
                $idOficinaRed = (int)$rowRo['id_oficina'];
            }
        }

        if ($idOficinaRed === null) {
            // Vía b) — fallback: CI sin red propia (p.ej. monitor),
            // deducir la oficina a través de v_monitor_oficina
            $stmtVmo = $this->dbCmdb->prepare(
                "SELECT id_oficina FROM v_monitor_oficina WHERE id_ci = :id LIMIT 1"
            );
            $stmtVmo->execute([':id' => $idCi]);
            $rowVmo = $stmtVmo->fetch();

            if ($rowVmo && !empty($rowVmo['id_oficina'])) {
                $idOficinaRed = (int)$rowVmo['id_oficina'];
            }
        }

        if ($idOficinaRed === null) {
            return $sinDatos(
                'El CI no tiene IP/red propia ni está conectado a un PC con red ' .
                'asignada: no se puede deducir la oficina por red.'
            );
        }

        // ── Paso 3: id_oficina en el ERP (por número de serie) ────────
        $stmtErp = $this->dbErp->prepare(
            "SELECT id_oficina FROM activo WHERE numero_serie = :ns LIMIT 1"
        );
        $stmtErp->execute([':ns' => $numeroSerie]);
        $rowErp = $stmtErp->fetch();

        if (!$rowErp || empty($rowErp['id_oficina'])) {
            return $sinDatos(
                "No se encontró el activo con serie «$numeroSerie» en el ERP: " .
                "no se puede verificar la consistencia."
            );
        }
        $idOficinaErp = (int)$rowErp['id_oficina'];

        // ── Paso 4: obtener nombres de ambas oficinas para el mensaje ─
        $nombreRed = null;
        $nombreErp = null;

        $stmtNom = $this->dbErp->prepare(
            "SELECT nombre FROM oficina WHERE id_oficina = :id LIMIT 1"
        );

        $stmtNom->execute([':id' => $idOficinaRed]);
        $rowNom = $stmtNom->fetch();
        $nombreRed = $rowNom ? $rowNom['nombre'] : "Oficina #$idOficinaRed";

        $stmtNom->execute([':id' => $idOficinaErp]);
        $rowNom = $stmtNom->fetch();
        $nombreErp = $rowNom ? $rowNom['nombre'] : "Oficina #$idOficinaErp";

        // ── Paso 5: comparar y devolver el resultado ──────────────────
        if ($idOficinaRed === $idOficinaErp) {
            return [
                'estado'     => 'ok',
                'id_red'     => $idOficinaRed,
                'id_erp'     => $idOficinaErp,
                'nombre_red' => $nombreRed,
                'nombre_erp' => $nombreErp,
                'mensaje'    => "Consistente: la red y el ERP apuntan a la misma oficina ($nombreRed).",
            ];
        }

        return [
            'estado'     => 'error',
            'id_red'     => $idOficinaRed,
            'id_erp'     => $idOficinaErp,
            'nombre_red' => $nombreRed,
            'nombre_erp' => $nombreErp,
            'mensaje'    => "Inconsistencia: el CI reporta en '$nombreRed' (#$idOficinaRed) " .
                            "pero el ERP registra '$nombreErp' (#$idOficinaErp).",
        ];
    }

    /* ------------------------------------------------------------------
     * buscar()
     * Busca oficinas en el ERP aplicando filtros opcionales.
     * Soporta paginación mediante LIMIT/OFFSET.
     *
     * Filtros soportados (todos opcionales, combinados con AND):
     *   id_oficina, nombre_oficina, direccion, cp, ciudad,
     *   unidad_organica, cod_pais
     *
     * Parámetros:
     *   $filtros   — array asociativo con los filtros activos
     *   $pagina    — número de página base 1
     *   $porPagina — registros por página (defecto 20)
     *
     * Retorna array con claves:
     *   filas, total, pagina, porPagina, paginas
     * ------------------------------------------------------------------ */
    public function buscar(array $filtros, int $pagina = 1, int $porPagina = 20): array {
        [$where, $params] = $this->construirWhere($filtros);
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        /* Total de coincidencias para calcular páginas */
        $sqlTotal = "SELECT COUNT(*)
                     FROM oficina o
                         INNER JOIN ciudad          c  ON c.id_ciudad    = o.id_ciudad
                         INNER JOIN pais            p  ON p.cod_pais      = c.cod_pais
                         INNER JOIN unidad_organica uo ON uo.id_uorganica = o.id_uorganica
                     $whereClause";

        $stmtTotal = $this->dbErp->prepare($sqlTotal);
        $stmtTotal->execute($params);
        $total = (int)$stmtTotal->fetchColumn();

        $offset = max(0, ($pagina - 1)) * $porPagina;

        /* Consulta paginada con datos completos */
        $sql = "SELECT
                    o.id_oficina,
                    o.cod_oficina,
                    o.nombre        AS nombre_oficina,
                    o.direccion,
                    o.cp,
                    c.nombre        AS ciudad,
                    c.id_ciudad,
                    p.nombre        AS pais,
                    p.cod_pais,
                    uo.nombre       AS unidad_organica
                FROM oficina o
                    INNER JOIN ciudad          c  ON c.id_ciudad    = o.id_ciudad
                    INNER JOIN pais            p  ON p.cod_pais      = c.cod_pais
                    INNER JOIN unidad_organica uo ON uo.id_uorganica = o.id_uorganica
                $whereClause
                ORDER BY p.nombre, c.nombre, o.nombre
                LIMIT :limit OFFSET :offset";

        $stmt = $this->dbErp->prepare($sql);
        /*
         * LIMIT y OFFSET se vinculan como INT porque PDO los trataría
         * como string si se pasaran en el array execute(), lo que
         * causaría un error de tipo en MySQL con strict_mode activo.
         */
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
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
     * construirWhere()
     * Centraliza la construcción de la cláusula WHERE y los parámetros
     * PDO a partir del array de filtros. Se usa en buscar() para
     * mantener la lógica en un solo lugar.
     *
     * Retorna: [ array $condiciones, array $params ]
     * ------------------------------------------------------------------ */
    private function construirWhere(array $filtros): array {
        $where  = [];
        $params = [];

        if (!empty($filtros['id_oficina'])) {
            $where[]               = 'o.id_oficina = :id_oficina';
            $params[':id_oficina'] = (int)$filtros['id_oficina'];
        }
        if (!empty($filtros['nombre_oficina'])) {
            $where[]                   = 'o.nombre LIKE :nombre_oficina';
            $params[':nombre_oficina'] = '%' . $filtros['nombre_oficina'] . '%';
        }
        if (!empty($filtros['direccion'])) {
            $where[]              = 'o.direccion LIKE :direccion';
            $params[':direccion'] = '%' . $filtros['direccion'] . '%';
        }
        if (!empty($filtros['cp'])) {
            $where[]      = 'o.cp LIKE :cp';
            $params[':cp'] = '%' . $filtros['cp'] . '%';
        }
        if (!empty($filtros['ciudad'])) {
            /* ciudad viene de la tabla ciudad, no de oficina */
            $where[]          = 'c.nombre LIKE :ciudad';
            $params[':ciudad'] = '%' . $filtros['ciudad'] . '%';
        }
        if (!empty($filtros['unidad_organica'])) {
            $where[]                    = 'uo.nombre LIKE :unidad_organica';
            $params[':unidad_organica'] = '%' . $filtros['unidad_organica'] . '%';
        }
        if (!empty($filtros['cod_pais'])) {
            $where[]            = 'p.cod_pais = :cod_pais';
            $params[':cod_pais'] = $filtros['cod_pais'];
        }

        return [$where, $params];
    }

    /* ------------------------------------------------------------------
     * getRedes()
     * Devuelve todas las redes (VLANs) asignadas a una oficina.
     * Fuente: CMDB — tablas red_oficina + red.
     * ------------------------------------------------------------------ */
    public function getRedes(int $idOficina): array {
        $sql = "SELECT r.gateway, r.cidr, r.vlan, r.descripcion
                FROM red_oficina ro
                    INNER JOIN red r ON r.gateway = ro.gateway
                WHERE ro.id_oficina = :id
                ORDER BY r.vlan ASC";

        $stmt = $this->dbCmdb->prepare($sql);
        $stmt->execute([':id' => $idOficina]);
        return $stmt->fetchAll();
    }

    /* ------------------------------------------------------------------
     * getActivosCmdb()
     * Cuenta los CI en la CMDB ubicados en la oficina, agrupados
     * por clase. La ubicación se deduce de dos formas:
     *   1. Por red propia:       ci → red_ci → red_oficina
     *   2. Por v_monitor_oficina: para CI sin red propia (p.ej. monitores),
     *      a través del PC al que están conectados.
     * COALESCE() prioriza la red propia si existiera ambas.
     * ------------------------------------------------------------------ */
    public function getActivosCmdb(int $idOficina): array {
        $sql = "SELECT cl.id_clase, cl.nombre AS clase, COUNT(*) AS total
                FROM ci c
                    INNER JOIN clase_ci    cl  ON cl.id_clase = c.id_clase
                    LEFT  JOIN red_ci      rc  ON rc.id_ci    = c.id_ci
                    LEFT  JOIN red_oficina ro  ON ro.gateway  = rc.gateway
                    LEFT  JOIN v_monitor_oficina vmo ON vmo.id_ci = c.id_ci
                WHERE COALESCE(ro.id_oficina, vmo.id_oficina) = :id
                GROUP BY cl.id_clase, cl.nombre
                ORDER BY total DESC";

        $stmt = $this->dbCmdb->prepare($sql);
        $stmt->execute([':id' => $idOficina]);
        return $stmt->fetchAll();
    }

    /* ------------------------------------------------------------------
     * getTotalCiCmdb()
     * Total de CI en CMDB para la oficina (independiente de clase).
     * Misma resolución de oficina que getActivosCmdb(): red propia o,
     * en su defecto, v_monitor_oficina (monitores vía su PC).
     * ------------------------------------------------------------------ */
    public function getTotalCiCmdb(int $idOficina): int {
        $sql = "SELECT COUNT(*)
                FROM ci c
                    LEFT JOIN red_ci      rc  ON rc.id_ci   = c.id_ci
                    LEFT JOIN red_oficina ro  ON ro.gateway = rc.gateway
                    LEFT JOIN v_monitor_oficina vmo ON vmo.id_ci = c.id_ci
                WHERE COALESCE(ro.id_oficina, vmo.id_oficina) = :id";

        $stmt = $this->dbCmdb->prepare($sql);
        $stmt->execute([':id' => $idOficina]);
        return (int)$stmt->fetchColumn();
    }

    /* ------------------------------------------------------------------
     * getActivosErp()
     * Cuenta los activos del ERP para la oficina, agrupados por clase.
     * ------------------------------------------------------------------ */
    public function getActivosErp(int $idOficina): array {
        $sql = "SELECT ca.id_clase, ca.nombre AS clase, COUNT(*) AS total
                FROM activo a
                    INNER JOIN modelo       mo ON mo.id_modelo = a.id_modelo
                    INNER JOIN clase_activo ca ON ca.id_clase  = mo.id_clase
                WHERE a.id_oficina = :id
                GROUP BY ca.id_clase, ca.nombre
                ORDER BY total DESC";

        $stmt = $this->dbErp->prepare($sql);
        $stmt->execute([':id' => $idOficina]);
        return $stmt->fetchAll();
    }

    /* ------------------------------------------------------------------
     * getTotalActivosErp()
     * Total de activos en ERP para la oficina.
     * ------------------------------------------------------------------ */
    public function getTotalActivosErp(int $idOficina): int {
        $stmt = $this->dbErp->prepare(
            "SELECT COUNT(*) FROM activo WHERE id_oficina = :id"
        );
        $stmt->execute([':id' => $idOficina]);
        return (int)$stmt->fetchColumn();
    }

    /* ------------------------------------------------------------------
     * buscarActivosErp()
     * Lista paginada de los activos del ERP de una oficina, opcionalmente
     * filtrados por clase de activo. Se usa desde la ficha de oficina
     * cuando el usuario pulsa "Ver" en la sección "Activos registrados — ERP".
     *
     * Si $idClase es 0 se devuelven todos los activos de la oficina.
     *
     * Además de los datos propios del ERP (código de inventario, marca,
     * modelo, número de serie, fecha de compra, garantía), cada fila
     * incluye 'id_ci_cmdb': el id_ci de la CMDB cuyo numero_serie coincide
     * con el del activo, o null si ese activo no está registrado como CI.
     * Esto permite enlazar directamente a la ficha del CI cuando exista,
     * en línea con las verificaciones de consistencia ya implementadas.
     *
     * Parámetros:
     *   $idOficina — ID de la oficina (erp.oficina)
     *   $idClase   — ID de clase_activo (0 = todas)
     *   $pagina    — número de página base 1
     *   $porPagina — registros por página (defecto 20)
     *
     * Retorna array con claves: filas, total, pagina, porPagina, paginas,
     *   más los metadatos de contexto: id_oficina, id_clase, nombre_clase
     * ------------------------------------------------------------------ */
    public function buscarActivosErp(
        int $idOficina,
        int $idClase   = 0,
        int $pagina    = 1,
        int $porPagina = 20
    ): array {
        $where  = ['a.id_oficina = :id_oficina'];
        $params = [':id_oficina' => $idOficina];

        if ($idClase > 0) {
            $where[]              = 'ca.id_clase = :id_clase';
            $params[':id_clase']  = $idClase;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        /* SQL base reutilizable para COUNT y SELECT */
        $sqlFrom = "FROM activo a
                    INNER JOIN modelo       mo ON mo.id_modelo = a.id_modelo
                    INNER JOIN marca        ma ON ma.id_marca  = mo.id_marca
                    INNER JOIN clase_activo ca ON ca.id_clase  = mo.id_clase
                    $whereClause";

        /* Total para la paginación */
        $stmtTotal = $this->dbErp->prepare("SELECT COUNT(*) $sqlFrom");
        $stmtTotal->execute($params);
        $total = (int)$stmtTotal->fetchColumn();

        $offset = max(0, $pagina - 1) * $porPagina;

        /* Consulta paginada */
        $sql = "SELECT
                    a.codigo_inventario,
                    ca.nombre   AS clase,
                    ma.nombre   AS marca,
                    mo.nombre   AS modelo,
                    a.numero_serie,
                    a.fecha_compra,
                    a.garantia
                $sqlFrom
                ORDER BY ca.nombre, ma.nombre, mo.nombre, a.codigo_inventario
                LIMIT :limit OFFSET :offset";

        $stmt = $this->dbErp->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $porPagina, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,    PDO::PARAM_INT);
        $stmt->execute();
        $filas = $stmt->fetchAll();

        /*
         * Cruce con la CMDB: para cada activo, comprobar si existe un CI
         * con el mismo número de serie y obtener su id_ci. Se hace en una
         * sola consulta con IN(...) para no lanzar una query por fila.
         */
        $series = array_filter(array_column($filas, 'numero_serie'));
        $mapaCi = [];
        if (!empty($series)) {
            $placeholders = implode(',', array_fill(0, count($series), '?'));
            $stmtCi = $this->dbCmdb->prepare(
                "SELECT id_ci, numero_serie FROM ci WHERE numero_serie IN ($placeholders)"
            );
            $stmtCi->execute(array_values($series));
            foreach ($stmtCi->fetchAll() as $rowCi) {
                $mapaCi[$rowCi['numero_serie']] = (int)$rowCi['id_ci'];
            }
        }
        foreach ($filas as &$fila) {
            $fila['id_ci_cmdb'] = $mapaCi[$fila['numero_serie']] ?? null;
        }
        unset($fila);

        /* Nombre de la clase para el título de la vista */
        $nombreClase = 'Todos los activos';
        if ($idClase > 0) {
            $stmtCl = $this->dbErp->prepare(
                "SELECT nombre FROM clase_activo WHERE id_clase = :id LIMIT 1"
            );
            $stmtCl->execute([':id' => $idClase]);
            $rowCl = $stmtCl->fetch();
            $nombreClase = $rowCl ? $rowCl['nombre'] : $nombreClase;
        }

        return [
            'filas'        => $filas,
            'total'        => $total,
            'pagina'       => $pagina,
            'porPagina'    => $porPagina,
            'paginas'      => (int)ceil($total / max(1, $porPagina)),
            'id_oficina'   => $idOficina,
            'id_clase'     => $idClase,
            'nombre_clase' => $nombreClase,
        ];
    }

    /* ------------------------------------------------------------------
     * getPaises()
     * Lista de países disponibles en el ERP para el selector del
     * formulario de búsqueda avanzada de oficinas.
     * ------------------------------------------------------------------ */
    public function getPaises(): array {
        return $this->dbErp
            ->query("SELECT cod_pais, nombre FROM pais ORDER BY nombre")
            ->fetchAll();
    }

    /* ------------------------------------------------------------------
     * getCoordenadasCiudad()
     * Coordenadas aproximadas de una ciudad para el mapa Leaflet.
     * Si la ciudad no está en la tabla devuelve Madrid por defecto (fallback.
     * En producción se podría sustituir por la API (P. ej. Nominatim).
     * Esta función ya no se usa en la vista de oficina, 
     * que ya tiene las coordenadas de las oficinas en la tabla oficina, 
     * pero se mantiene por posibles oficinas futuras no geolocalizadas.
     * ------------------------------------------------------------------ */
    public function getCoordenadasCiudad(string $ciudad): array {
        $coordenadas = [
            'madrid'       => ['lat' => 40.4168,  'lng' => -3.7038,  'zoom' => 13],
            'barcelona'    => ['lat' => 41.3851,  'lng' =>  2.1734,  'zoom' => 13],
            'valencia'     => ['lat' => 39.4699,  'lng' => -0.3763,  'zoom' => 13],
            'sevilla'      => ['lat' => 37.3891,  'lng' => -5.9845,  'zoom' => 13],
            'zaragoza'     => ['lat' => 41.6488,  'lng' => -0.8891,  'zoom' => 13],
            'málaga'       => ['lat' => 36.7213,  'lng' => -4.4213,  'zoom' => 13],
            'malaga'       => ['lat' => 36.7213,  'lng' => -4.4213,  'zoom' => 13],
            'bilbao'       => ['lat' => 43.2630,  'lng' => -2.9350,  'zoom' => 13],
            'alicante'     => ['lat' => 38.3452,  'lng' => -0.4815,  'zoom' => 13],
            'córdoba'      => ['lat' => 37.8882,  'lng' => -4.7794,  'zoom' => 13],
            'cordoba'      => ['lat' => 37.8882,  'lng' => -4.7794,  'zoom' => 13],
            'valladolid'   => ['lat' => 41.6523,  'lng' => -4.7245,  'zoom' => 13],
            'vigo'         => ['lat' => 42.2314,  'lng' => -8.7124,  'zoom' => 13],
            'gijón'        => ['lat' => 43.5322,  'lng' => -5.6611,  'zoom' => 13],
            'gijon'        => ['lat' => 43.5322,  'lng' => -5.6611,  'zoom' => 13],
            'granada'      => ['lat' => 37.1773,  'lng' => -3.5986,  'zoom' => 13],
            'murcia'       => ['lat' => 37.9922,  'lng' => -1.1307,  'zoom' => 13],
            'palma'        => ['lat' => 39.5696,  'lng' =>  2.6502,  'zoom' => 13],
            'las palmas'   => ['lat' => 28.1235,  'lng' => -15.4363, 'zoom' => 13],
            'santander'    => ['lat' => 43.4623,  'lng' => -3.8099,  'zoom' => 13],
            'pamplona'     => ['lat' => 42.8125,  'lng' => -1.6458,  'zoom' => 13],
            'donostia'     => ['lat' => 43.3183,  'lng' => -1.9812,  'zoom' => 13],
            'san sebastián'=> ['lat' => 43.3183,  'lng' => -1.9812,  'zoom' => 13],
            'burgos'       => ['lat' => 42.3440,  'lng' => -3.6970,  'zoom' => 13],
            'salamanca'    => ['lat' => 40.9701,  'lng' => -5.6635,  'zoom' => 13],
            'toledo'       => ['lat' => 39.8628,  'lng' => -4.0273,  'zoom' => 13],
            'badajoz'      => ['lat' => 38.8794,  'lng' => -6.9706,  'zoom' => 13],
            'logroño'      => ['lat' => 42.4650,  'lng' => -2.4456,  'zoom' => 13],
            'albacete'     => ['lat' => 38.9943,  'lng' => -1.8585,  'zoom' => 13],
            'huelva'       => ['lat' => 37.2614,  'lng' => -6.9447,  'zoom' => 13],
            'cádiz'        => ['lat' => 36.5270,  'lng' => -6.2886,  'zoom' => 13],
            'cadiz'        => ['lat' => 36.5270,  'lng' => -6.2886,  'zoom' => 13],
            'leon'         => ['lat' => 42.5987,  'lng' => -5.5671,  'zoom' => 13],
            'oviedo'       => ['lat' => 43.3614,  'lng' => -5.8494,  'zoom' => 13],
            'a coruña'     => ['lat' => 43.3713,  'lng' => -8.3960,  'zoom' => 13],
        ];

        $clave = mb_strtolower(trim($ciudad), 'UTF-8');

        if (isset($coordenadas[$clave])) return $coordenadas[$clave];

        foreach ($coordenadas as $key => $coords) {
            if (str_contains($clave, $key) || str_contains($key, $clave)) {
                return $coords;
            }
        }

        return ['lat' => 40.4168, 'lng' => -3.7038, 'zoom' => 6]; // Fallback: Madrid
    }

     /* ------------------------------------------------------------------
     * getCoordenadasOficina()
     * Consulta las coordenadas aproximadas de una oficina para el mapa Leaflet.
     * Si la oficina no está en la tabla devuelve Madrid como fallback.
     * ------------------------------------------------------------------ */
    public function getCoordenadasOficina(int $idOficina): array {
        $stmt = $this->dbErp->prepare("SELECT lat, lng, zoom FROM oficina WHERE id_oficina = :idOficina");
        $stmt->execute(['idOficina' => $idOficina]);
        $resultado = $stmt->fetch();

        // Si no se encuentra la oficina o no tiene coordenadas, usar valores por defecto
        if (!$resultado || empty($resultado['lat']) || empty($resultado['lng'])) {
            return ['lat' => 40.4168, 'lng' => -3.7038, 'zoom' => 6]; // Fallback: Madrid
        }

        return [
            'lat' => $resultado['lat'],
            'lng' => $resultado['lng'],
            'zoom' => $resultado['zoom'] ?? 6 // Si zoom es null, usar 6 por defecto
        ];
    }
}
