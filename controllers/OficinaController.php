<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : controllers/OficinaController.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción   : Controlador para las vistas de oficina.
 *                 Gestiona dos acciones:
 *                   - showBusqueda / doBusqueda : búsqueda paginada
 *                   - showOficina              : ficha completa
 *
 * Rutas (desde index.php):
 *   GET  action=buscar_oficinas          — formulario vacío
 *   GET  action=buscar_oficinas&ciudad=X — resultados paginados
 *   GET  action=oficina&id=N             — ficha de oficina
 * ========================================================= */

require_once BASE_PATH . '/models/OficinaModel.php';

class OficinaController {

    /** Instancia del modelo de oficina */
    private OficinaModel $model;

    public function __construct() {
        $this->model = new OficinaModel();
    }

    /* ------------------------------------------------------------------
     * showBusqueda()
     * Muestra el formulario de búsqueda de oficinas vacío (GET sin
     * parámetros de búsqueda). Precarga la lista de países.
     * ------------------------------------------------------------------ */
    public function showBusqueda(): void {
        $filtros   = [];
        $resultado = null;
        $paises    = [];
        $error     = null;

        try {
            $paises = $this->model->getPaises();
        } catch (PDOException $e) {
            $error = 'Error al cargar los datos del formulario.';
            if (APP_ENV === 'development') $error .= ' [' . $e->getMessage() . ']';
        }

        require VIEWS_PATH . '/oficinas_busqueda.php';
    }

    /* ------------------------------------------------------------------
     * doBusqueda()
     * Recoge los filtros del formulario (GET), ejecuta la búsqueda
     * paginada en el modelo y carga la vista con los resultados.
     *
     * Se usa GET (no POST) para que la URL sea "compartible":
     * el usuario puede copiar la URL y compartir los resultados.
     * ------------------------------------------------------------------ */
    public function doBusqueda(): void {
        /* Recoger y limpiar filtros — todos opcionales */
        $filtros = array_filter([
            'id_oficina'      => trim($_GET['id_oficina']      ?? ''),
            'nombre_oficina'  => trim($_GET['nombre_oficina']  ?? ''),
            'direccion'       => trim($_GET['direccion']       ?? ''),
            'cp'              => trim($_GET['cp']              ?? ''),
            'ciudad'          => trim($_GET['ciudad']          ?? ''),
            'unidad_organica' => trim($_GET['unidad_organica'] ?? ''),
            'cod_pais'        => trim($_GET['cod_pais']        ?? ''),
        ], fn($v) => $v !== '');

        /* Página actual validada como entero positivo */
        $pagina    = max(1, (int)($_GET['pagina'] ?? 1));
        $porPagina = 20;

        $resultado = null;
        $paises    = [];
        $error     = null;

        try {
            $paises    = $this->model->getPaises();
            $resultado = $this->model->buscar($filtros, $pagina, $porPagina);
        } catch (PDOException $e) {
            $error = 'Error al realizar la búsqueda de oficinas.';
            if (APP_ENV === 'development') $error .= ' [' . $e->getMessage() . ']';
        }

        require VIEWS_PATH . '/oficinas_busqueda.php';
    }

    /* ------------------------------------------------------------------
     * showOficina()
     * Carga todos los datos de una oficina concreta (ficha completa)
     * y los pasa a la vista oficina.php.
     * ------------------------------------------------------------------ */
    public function showOficina(): void {
        $id    = (int)($_GET['id'] ?? 0);
        $error = null;

        $oficina     = null;
        $redes       = [];
        $activosCmdb = [];
        $totalCmdb   = 0;
        $activosErp  = [];
        $totalErp    = 0;
        $coordenadas = ['lat' => 40.4168, 'lng' => -3.7038, 'zoom' => 6];

        if ($id <= 0) {
            header('Location: index.php?action=buscar_oficinas');
            exit;
        }

        try {
            $oficina = $this->model->findById($id);

            if (!$oficina) {
                $error = "No se encontró la oficina con ID $id.";
            } else {
                $redes       = $this->model->getRedes($id);
                $activosCmdb = $this->model->getActivosCmdb($id);
                $totalCmdb   = $this->model->getTotalCiCmdb($id);
                $activosErp  = $this->model->getActivosErp($id);
                $totalErp    = $this->model->getTotalActivosErp($id);
                $coordenadas = $this->model->getCoordenadasCiudad($oficina['ciudad'] ?? '');
                $coordenadas = $this->model->getCoordenadasOficina($id);
            }
        } catch (PDOException $e) {
            $error = 'Error al obtener los datos de la oficina.';
            if (APP_ENV === 'development') $error .= ' [' . $e->getMessage() . ']';
        }

        require VIEWS_PATH . '/oficina.php';
    }

    /* ------------------------------------------------------------------
     * showActivosErp()
     * Lista paginada (20 en 20) de los activos del ERP de una oficina,
     * con filtro opcional por clase de activo. Se accede desde la ficha
     * de oficina pulsando "Ver" en la sección "Activos registrados — ERP".
     *
     * Parámetros GET:
     *   id_oficina — ID de la oficina (obligatorio)
     *   id_clase   — ID de clase_activo (0 = todas, opcional)
     *   pagina     — número de página (defecto 1)
     * ------------------------------------------------------------------ */
    public function showActivosErp(): void {
        $idOficina = (int)($_GET['id_oficina'] ?? 0);
        $idClase   = (int)($_GET['id_clase']   ?? 0);
        $pagina    = max(1, (int)($_GET['pagina'] ?? 1));

        if ($idOficina <= 0) {
            header('Location: index.php?action=buscar_oficinas');
            exit;
        }

        $resultado = null;
        $oficina   = null;
        $error     = null;

        try {
            $oficina = $this->model->findById($idOficina);

            if (!$oficina) {
                $error = "No se encontró la oficina con ID $idOficina.";
            } else {
                $resultado = $this->model->buscarActivosErp($idOficina, $idClase, $pagina);
            }
        } catch (PDOException $e) {
            $error = 'Error al obtener los activos del ERP de la oficina.';
            if (APP_ENV === 'development') $error .= ' [' . $e->getMessage() . ']';
        }

        require VIEWS_PATH . '/oficina_activos_erp.php';
    }
}
