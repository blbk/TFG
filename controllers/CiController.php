<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : controllers/CiController.php
 * ========================================================= */

require_once BASE_PATH . '/models/CiModel.php';

class CiController {
    private CiModel $model;

    public function __construct() {
        $this->model = new CiModel();
    }

    public function showSearch(): void {
        $resultado    = null;   // null = no buscado aún → muestra estadísticas
        $termino      = '';
        $filtros      = [];
        $modoAvanzado = false;
        $error        = null;
        $clases       = $this->model->getClases();
        $estadisticas = $this->model->estadisticas();
        require VIEWS_PATH . '/search.php';
    }

    public function doSearch(): void {
        $termino      = trim($_REQUEST['q'] ?? '');
        $pagina       = max(1, (int)($_GET['pagina'] ?? 1));
        $resultado    = null;       // array paginado (filas, total, paginas…)
        $filtros      = [];
        $modoAvanzado = false;
        $error        = null;
        $clases       = $this->model->getClases();
        $estadisticas = $this->model->estadisticas();

        if ($termino !== '') {
            try {
                $resultado = $this->model->buscarSimple($termino, $pagina);
            } catch (PDOException $e) {
                $error = 'Error al realizar la búsqueda.';
                if (APP_ENV === 'development') $error .= ' [' . $e->getMessage() . ']';
            }
        }
        require VIEWS_PATH . '/search.php';
    }

    public function doAdvancedSearch(): void {

        // ── Nombres de los campos de cada grupo ───────────────────────────
        $camposCmdb = ['id_ci','clase','marca','modelo','numero_serie',
                       'hostname','ip','login','nombre_local','fecha_desde','fecha_hasta'];
        $camposErp  = ['id_oficina','nombre_oficina','direccion',
                       'cp','ciudad','unidad_organica'];

        // ── Recoger todos los filtros del formulario ──────────────────────
        $filtrosCmdb = array_filter(array_combine(
            $camposCmdb,
            array_map(fn($k) => trim($_POST[$k] ?? ''), $camposCmdb)
        ), fn($v) => $v !== '');

        $filtrosErp = array_filter(array_combine(
            $camposErp,
            array_map(fn($k) => trim($_POST[$k] ?? ''), $camposErp)
        ), fn($v) => $v !== '');

        $hayFiltrosCmdb = !empty($filtrosCmdb);
        $hayFiltrosErp  = !empty($filtrosErp);

        // ── Regla de enrutamiento ─────────────────────────────────────────
        /*
         * Caso A — Solo filtros ERP, sin ningún filtro CMDB:
         *   El usuario quiere ver OFICINAS. Redirigimos a buscar_oficinas
         *   pasando los filtros ERP como parámetros GET para mantener la
         *   URL compartible y aprovechar la paginación ya implementada.
         *
         * Caso B — Solo filtros CMDB, o mezcla CMDB+ERP:
         *   El usuario quiere ver CIs (con o sin cruce a ERP).
         *   Se ejecuta la búsqueda de CI como hasta ahora.
         *
         * Caso C — Sin ningún filtro:
         *   Volvemos al formulario de búsqueda vacío.
         */
        if ($hayFiltrosErp && !$hayFiltrosCmdb) {
            // ── Caso A: redirigir a búsqueda de oficinas ─────────────────
            $params = array_merge(
                ['action' => 'buscar_oficinas', 'pagina' => 1],
                $filtrosErp
            );
            header('Location: index.php?' . http_build_query($params));
            exit;
        }

        if (!$hayFiltrosCmdb && !$hayFiltrosErp) {
            // ── Caso C: sin filtros, volver al buscador ───────────────────
            header('Location: index.php?action=search');
            exit;
        }

        // ── Caso B: buscar CIs (con posible cruce ERP) ────────────────────
        $filtros      = array_merge($filtrosCmdb, $filtrosErp);
        $pagina       = max(1, (int)($_POST['pagina'] ?? 1));
        $termino      = '';
        $resultado    = null;
        $modoAvanzado = true;
        $error        = null;
        $clases       = $this->model->getClases();
        $estadisticas = $this->model->estadisticas();

        try {
            $resultado = $this->model->buscarAvanzado($filtros, $pagina);
        } catch (PDOException $e) {
            $error = 'Error en la búsqueda avanzada.';
            if (APP_ENV === 'development') $error .= ' [' . $e->getMessage() . ']';
        }
        require VIEWS_PATH . '/search.php';
    }

    public function showDetail(): void {
        $id    = (int)($_GET['id'] ?? 0);
        $ci    = null;
        $error = null;

        if ($id <= 0) {
            header('Location: index.php?action=search');
            exit;
        }
        try {
            $ci = $this->model->detalle($id);
        } catch (PDOException $e) {
            $error = 'Error al obtener el detalle del CI.';
            if (APP_ENV === 'development') $error .= ' [' . $e->getMessage() . ']';
        }
        if (!$ci && !$error) {
            $error = "No se encontró el CI con ID $id.";
        }
        require VIEWS_PATH . '/detail.php';
    }

    /* ------------------------------------------------------------------
     * showActivosOficina()
     * Lista paginada de CI pertenecientes a una oficina, con filtro
     * opcional por clase. Se accede desde la ficha de oficina pulsando
     * en una fila de la sección "Activos conectados — CMDB".
     *
     * Parámetros GET:
     *   id_oficina — ID de la oficina (obligatorio)
     *   id_clase   — ID de la clase de CI (0 = todas, opcional)
     *   pagina     — número de página (defecto 1)
     * ------------------------------------------------------------------ */
    public function showActivosOficina(): void {
        $idOficina  = (int)($_GET['id_oficina'] ?? 0);
        $idClase    = (int)($_GET['id_clase']   ?? 0);
        $claseNombre = trim($_GET['clase'] ?? '');  // nombre de clase desde la ficha de oficina
        $pagina     = max(1, (int)($_GET['pagina'] ?? 1));

        if ($idOficina <= 0) {
            header('Location: index.php?action=buscar_oficinas');
            exit;
        }

        $resultado    = null;
        $error        = null;
        $clases       = [];
        $estadisticas = $this->model->estadisticas();

        /* Nombre de la oficina para el breadcrumb y el título */
        $nombreOficina = null;
        try {
            require_once BASE_PATH . '/models/OficinaModel.php';
            $oficModel     = new OficinaModel();
            $datosOficina  = $oficModel->findById($idOficina);
            $nombreOficina = $datosOficina['nombre_oficina'] ?? "Oficina #$idOficina";
        } catch (PDOException $e) {
            $nombreOficina = "Oficina #$idOficina";
        }

        try {
            $clases = $this->model->getClases();

            /*
             * Resolución del filtro de clase:
             * Puede llegar como id_clase (int) desde el paginador,
             * o como nombre de clase (string) desde la ficha de oficina.
             * En ambos casos normalizamos a $idClase (int, 0 = todas).
             */
            if ($idClase === 0 && $claseNombre !== '') {
                foreach ($clases as $cl) {
                    if (strtolower($cl['nombre']) === strtolower($claseNombre)) {
                        $idClase = (int)$cl['id_clase'];
                        break;
                    }
                }
            }

            $resultado = $this->model->buscarPorOficinaYClase(
                $idOficina, $idClase, $pagina
            );
        } catch (PDOException $e) {
            $error = 'Error al obtener los activos de la oficina.';
            if (APP_ENV === 'development') $error .= ' [' . $e->getMessage() . ']';
        }

        $termino      = '';
        $modoAvanzado = false;
        $filtros      = [];
        $modoOficina  = true;   // flag para adaptar título y breadcrumb en search.php
        require VIEWS_PATH . '/search.php';
    }

    public function apiSearch(): void {
        header('Content-Type: application/json; charset=utf-8');
        $termino = trim($_GET['q'] ?? '');
        if (strlen($termino) < 2) {
            echo json_encode(['resultados' => []]);
            exit;
        }
        try {
            // Para el AJAX solo necesitamos la primera página con pocas filas
            $resultado  = $this->model->buscarSimple($termino, 1, 10);
            echo json_encode(['resultados' => $resultado['filas']]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error en la búsqueda']);
        }
        exit;
    }
}
