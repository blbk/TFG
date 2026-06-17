<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : views/oficina.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción   : Vista de ficha completa de una oficina.
 *                 Muestra cuatro secciones:
 *                   1. Datos de la organización (nombre, unidad)
 *                   2. Ubicación (dirección, ciudad, CP, país + mapa)
 *                   3. Conectividad (redes / VLANs de la sede)
 *                   4. Datos técnicos (activos CMDB vs ERP)
 *
 * Variables recibidas desde OficinaController::showOficina():
 *   $oficina     — array con datos de la oficina (ERP)
 *   $redes       — array de redes asociadas (CMDB)
 *   $activosCmdb — array [clase, total] de CI en CMDB
 *   $totalCmdb   — int total de CI en CMDB
 *   $activosErp  — array [clase, total] de activos en ERP
 *   $totalErp    — int total de activos en ERP
 *   $coordenadas — array [lat, lng, zoom] para Leaflet
 *   $error       — string|null mensaje de error si lo hay
 * ========================================================= */

/* Título de la pestaña del navegador */
$pageTitle = $oficina
    ? 'Oficina #' . $oficina['id_oficina'] . ' — ' . $oficina['nombre_oficina']
    : 'Ficha de oficina';

require VIEWS_PATH . '/partials/header.php';

/*
 * Función auxiliar local: devuelve un icono según el nombre de la clase.
 * Permite reutilizar el mismo criterio visual que en search.php y detail.php.
 */
function iconoClaseOf(string $clase): string {
    $c = strtolower($clase);
    if (str_contains($c, 'pc') || str_contains($c, 'ordenador')) return '<i class="fas fa-desktop"></i>';
    if (str_contains($c, 'laptop'))    return '<i class="fas fa-laptop"></i>';
    if (str_contains($c, 'impresora')) return '<i class="fas fa-print"></i>';
    if (str_contains($c, 'monitor'))   return '<i class="fas fa-tv"></i>';
    if (str_contains($c, 'switch'))    return '<i class="fas fa-network-wired"></i>';
    if (str_contains($c, 'router'))    return '<i class="fas fa-wifi"></i>';
    if (str_contains($c, 'servidor') || str_contains($c, 'server')) return '<i class="fas fa-server"></i>';
    if (str_contains($c, 'nas'))       return '<i class="fas fa-hdd"></i>';
    return '<i class="fas fa-cube"></i>';
}
?>

<!-- Hoja de estilos de Leaflet (solo para esta vista) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<div class="detail-page oficina-page">

    <!-- ── Breadcrumb de navegación ─────────────────────────────── -->
    <nav class="breadcrumb">
        <a href="index.php?action=search"><i class="fas fa-search"></i> Búsqueda</a>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <span>Ficha de oficina</span>
    </nav>

    <!-- ── Mensaje de error ──────────────────────────────────────── -->
    <?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin:24px 32px;">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($error) ?>
    </div>

    <?php elseif ($oficina): ?>

    <!-- ══════════════════════════════════════════════════════════
         CABECERA DE LA OFICINA
    ══════════════════════════════════════════════════════════ -->
    <div class="detail-header">
        <!-- Icono representativo de la oficina -->
        <div class="detail-header-icon">
            <i class="fas fa-building"></i>
        </div>

        <div class="detail-header-info">
            <div class="detail-header-clase">
                <span class="badge-clase badge-clase--lg">Oficina</span>
                <span class="detail-id">
                    ID: <?= $oficina['id_oficina'] ?>
                    &nbsp;·&nbsp;
                    Cód: <?= htmlspecialchars($oficina['cod_oficina']) ?>
                </span>
            </div>
            <h1 class="detail-title">
                <?= htmlspecialchars($oficina['nombre_oficina']) ?>
            </h1>
            <div class="detail-subtitle">
                <i class="fas fa-sitemap"></i>
                <?= htmlspecialchars($oficina['unidad_organica']) ?>
                &nbsp;·&nbsp;
                <i class="fas fa-map-marker-alt"></i>
                <?= htmlspecialchars($oficina['ciudad']) ?>, <?= htmlspecialchars($oficina['pais']) ?>
            </div>
        </div>

        <!-- Botones de acción en la cabecera -->
        <div class="detail-header-actions">
            <a href="javascript:history.back()" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button onclick="window.print()" class="btn-secondary">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         GRID DE SECCIONES
         Se usa el mismo sistema de detail-sections que detail.php
         para mantener coherencia visual en toda la aplicación.
    ══════════════════════════════════════════════════════════ -->
    <div class="detail-sections">

        <!-- ──────────────────────────────────────────────────────
             SECCIÓN 1: DATOS DE LA ORGANIZACIÓN
             Fuente: base de datos ERP
        ────────────────────────────────────────────────────── -->
        <div class="detail-card">
            <div class="detail-card-header">
                <i class="fas fa-sitemap"></i> Datos de la organización
            </div>
            <div class="detail-card-body">
                <table class="detail-table">
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID oficina</th>
                        <td>
                            <strong>#<?= $oficina['id_oficina'] ?></strong>
                        </td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-key"></i> Código</th>
                        <td><?= htmlspecialchars($oficina['cod_oficina']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-building"></i> Nombre de la oficina</th>
                        <td><?= htmlspecialchars($oficina['nombre_oficina']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-sitemap"></i> Unidad orgánica</th>
                        <td><?= htmlspecialchars($oficina['unidad_organica']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ──────────────────────────────────────────────────────
             SECCIÓN 2: UBICACIÓN
             Fuente: base de datos ERP (tablas oficina, ciudad, pais)
             Incluye mapa Leaflet con OpenStreetMap (sin API key)
        ────────────────────────────────────────────────────── -->
        <div class="detail-card">
            <div class="detail-card-header">
                <i class="fas fa-map-marker-alt"></i> Ubicación
            </div>
            <div class="detail-card-body">
                <table class="detail-table">
                    <tr>
                        <th><i class="fas fa-road"></i> Dirección</th>
                        <td><?= htmlspecialchars($oficina['direccion']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-city"></i> Ciudad</th>
                        <td><?= htmlspecialchars($oficina['ciudad']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-mail-bulk"></i> Código postal</th>
                        <td><?= htmlspecialchars($oficina['cp']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-globe"></i> País</th>
                        <td>
                            <?= htmlspecialchars($oficina['pais']) ?>
                            <span class="cod-pais">(<?= htmlspecialchars($oficina['cod_pais']) ?>)</span>
                        </td>
                    </tr>
                </table>

                <?php if ($coordenadas): ?>
                <!--
                    Mapa Leaflet centrado en la ciudad de la oficina.
                    Los datos de coordenadas se pasan vía data-* attributes
                    para separar PHP de JavaScript (ver script al final).
                    Se usa OpenStreetMap como tile provider (libre y gratuito).
                -->
                <div id="mapaOficina" class="mapa-oficina"
                     data-lat="<?= $coordenadas['lat'] ?>"
                     data-lng="<?= $coordenadas['lng'] ?>"
                     data-zoom="<?= $coordenadas['zoom'] ?>"
                     data-label="<?= htmlspecialchars($oficina['nombre_oficina'] . "\n" . $oficina['direccion'] . "\n" . $oficina['ciudad']) ?>">
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ──────────────────────────────────────────────────────
             SECCIÓN 3: CONECTIVIDAD
             Fuente: CMDB — tablas red_oficina + red
             Muestra todas las redes (VLANs) asignadas a la sede.
        ────────────────────────────────────────────────────── -->
        <div class="detail-card detail-card--full">
            <div class="detail-card-header">
                <i class="fas fa-network-wired"></i> Conectividad — redes de la sede
            </div>
            <div class="detail-card-body">
                <?php if (empty($redes)): ?>
                <p class="detail-empty">
                    <i class="fas fa-info-circle"></i>
                    No hay redes registradas para esta oficina en la CMDB.
                </p>
                <?php else: ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Gateway</th>
                            <th>Red / CIDR</th>
                            <th>VLAN</th>
                            <th>Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($redes as $red): ?>
                        <tr>
                            <!-- Gateway es la IP del router de esa red -->
                            <td class="mono"><?= htmlspecialchars($red['gateway']) ?></td>
                            <!-- CIDR: notación IP/máscara, p.ej. 192.168.1.0/24 -->
                            <td class="mono"><?= htmlspecialchars($red['cidr']) ?></td>
                            <!-- VLAN: identificador de red virtual (0-4094) -->
                            <td>
                                <span class="badge-vlan-num">
                                    <?= htmlspecialchars($red['vlan'] ?? '—') ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($red['descripcion'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- ──────────────────────────────────────────────────────
             SECCIÓN 4: DATOS TÉCNICOS
             Combina información de CMDB y ERP para dar una visión
             completa del inventario de la sede.

             4a. Activos conectados en la CMDB (por clase de CI)
                 → Se cuentan los CI cuya IP pertenece a una red
                   asignada a esta oficina vía red_oficina.

             4b. Activos registrados en el ERP (por clase de activo)
                 → Se cuentan los activos cuyo id_oficina coincide.

             La diferencia entre ambos totales puede indicar activos
             sin configuración de red o activos dados de baja en ERP
             pero aún activos en red (o viceversa).
        ────────────────────────────────────────────────────── -->
        <div class="detail-card">
            <div class="detail-card-header">
                <i class="fas fa-database"></i>
                Activos conectados — CMDB
                <span class="header-total">
                    <a href="index.php?action=activos_oficina&id_oficina=<?= $oficina['id_oficina'] ?>"
                       class="header-total-link" title="Ver todos los activos de esta oficina">
                        Total: <?= $totalCmdb ?>
                    </a>
                </span>
            </div>
            <div class="detail-card-body">
                <?php if (empty($activosCmdb)): ?>
                <p class="detail-empty">
                    <i class="fas fa-info-circle"></i>
                    No se encontraron CI asignados a esta oficina en la CMDB.
                </p>
                <?php else: ?>
                <table class="detail-table activos-tabla">
                    <thead>
                        <tr>
                            <th>Clase</th>
                            <th>Cantidad</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($activosCmdb as $act):
                        /*
                         * Buscamos el id_clase para pasarlo en la URL.
                         * Lo obtenemos de la tabla clase_ci en memoria
                         * para evitar una consulta por fila.
                         */
                        $urlActivos = 'index.php?action=activos_oficina'
                            . '&id_oficina=' . $oficina['id_oficina']
                            . '&clase='      . urlencode($act['clase']);
                    ?>
                    <tr class="activo-row"
                        onclick="window.location='<?= $urlActivos ?>'"
                        title="Ver <?= htmlspecialchars($act['clase']) ?> de esta oficina"
                        style="cursor:pointer;">
                        <td>
                            <?= iconoClaseOf($act['clase']) ?>
                            <?= htmlspecialchars($act['clase']) ?>
                        </td>
                        <td>
                            <div class="stat-bar-wrap">
                                <div class="stat-bar"
                                     style="width:<?= $totalCmdb > 0 ? round($act['total']/$totalCmdb*100) : 0 ?>%">
                                </div>
                                <span class="stat-bar-num"><?= $act['total'] ?></span>
                            </div>
                        </td>
                        <td>
                            <a href="<?= $urlActivos ?>" class="btn-detail"
                               onclick="event.stopPropagation()">
                                <i class="fas fa-list"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="detail-card">
            <div class="detail-card-header">
                <i class="fas fa-boxes"></i>
                Activos registrados — ERP
                <span class="header-total">
                    <a href="index.php?action=activos_erp_oficina&id_oficina=<?= $oficina['id_oficina'] ?>"
                       class="header-total-link" title="Ver todos los activos ERP de esta oficina">
                        Total: <?= $totalErp ?>
                    </a>
                </span>
            </div>
            <div class="detail-card-body">
                <?php if (empty($activosErp)): ?>
                <p class="detail-empty">
                    <i class="fas fa-info-circle"></i>
                    No se encontraron activos registrados para esta oficina en el ERP.
                </p>
                <?php else: ?>
                <table class="detail-table activos-tabla">
                    <thead>
                        <tr>
                            <th>Clase</th>
                            <th>Cantidad</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($activosErp as $act):
                        $urlActivosErp = 'index.php?action=activos_erp_oficina'
                            . '&id_oficina=' . $oficina['id_oficina']
                            . '&id_clase='   . ($act['id_clase'] ?? 0);
                    ?>
                    <tr class="activo-row"
                        onclick="window.location='<?= $urlActivosErp ?>'"
                        title="Ver <?= htmlspecialchars($act['clase']) ?> de esta oficina en el ERP"
                        style="cursor:pointer;">
                        <td>
                            <?= iconoClaseOf($act['clase']) ?>
                            <?= htmlspecialchars($act['clase']) ?>
                        </td>
                        <td>
                            <!-- Barra visual proporcional al total de activos ERP -->
                            <div class="stat-bar-wrap">
                                <div class="stat-bar stat-bar--erp"
                                     style="width:<?= $totalErp > 0 ? round($act['total']/$totalErp*100) : 0 ?>%">
                                </div>
                                <span class="stat-bar-num"><?= $act['total'] ?></span>
                            </div>
                        </td>
                        <td>
                            <a href="<?= $urlActivosErp ?>" class="btn-detail"
                               onclick="event.stopPropagation()">
                                <i class="fas fa-list"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comparativa CMDB vs ERP como tarjeta resumen -->
        <div class="detail-card detail-card--full">
            <div class="detail-card-header">
                <i class="fas fa-balance-scale"></i> Comparativa inventario CMDB vs ERP
            </div>
            <div class="detail-card-body" style="padding:20px 24px;">
                <div class="comparativa-grid">
                    <!-- Total CMDB -->
                    <div class="comp-card comp-card--cmdb">
                        <div class="comp-icon"><i class="fas fa-database"></i></div>
                        <div class="comp-num"><?= $totalCmdb ?></div>
                        <div class="comp-label">CI en CMDB<br><small>(con configuración de red)</small></div>
                    </div>

                    <!-- Diferencia -->
                    <?php
                    /*
                     * La diferencia entre ERP y CMDB puede significar:
                     *  > 0 : hay activos en ERP sin IP asignada (p.ej. monitores sin red)
                     *  < 0 : hay CIs en red que no están en el inventario del ERP
                     *  = 0 : inventarios perfectamente alineados
                     */
                    $diff = $totalErp - $totalCmdb;
                    $diffClass = $diff === 0 ? 'comp-card--ok'
                               : ($diff > 0  ? 'comp-card--warn' : 'comp-card--alert');
                    $diffIcon  = $diff === 0 ? 'fa-check-circle'
                               : ($diff > 0  ? 'fa-arrow-up' : 'fa-arrow-down');
                    ?>
                    <div class="comp-card <?= $diffClass ?>">
                        <div class="comp-icon"><i class="fas <?= $diffIcon ?>"></i></div>
                        <div class="comp-num"><?= $diff >= 0 ? '+' . $diff : $diff ?></div>
                        <div class="comp-label">Diferencia<br>
                            <small>
                            <?php if ($diff === 0): ?>
                                Inventarios alineados
                            <?php elseif ($diff > 0): ?>
                                Activos ERP sin IP en CMDB
                            <?php else: ?>
                                CI en CMDB sin registro en ERP
                            <?php endif; ?>
                            </small>
                        </div>
                    </div>

                    <!-- Total ERP -->
                    <div class="comp-card comp-card--erp">
                        <div class="comp-icon"><i class="fas fa-boxes"></i></div>
                        <div class="comp-num"><?= $totalErp ?></div>
                        <div class="comp-label">Activos en ERP<br><small>(inventario patrimonial)</small></div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.detail-sections -->

    <?php endif; // $oficina ?>
</div><!-- /.oficina-page -->

<?php
/*
 * Script de Leaflet: solo se carga si hay coordenadas disponibles.
 * Se carga al final del body para no bloquear la renderización de la página.
 */
if (!empty($coordenadas)):
?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/*
 * Inicialización del mapa Leaflet para la ficha de oficina.
 * Usa OpenStreetMap como capa base (libre, sin API key).
 * Las coordenadas provienen del modelo OficinaModel::getCoordenadasCiudad().
 */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('mapaOficina');
    if (!el) return;

    const lat   = parseFloat(el.dataset.lat);
    const lng   = parseFloat(el.dataset.lng);
    const zoom  = parseInt(el.dataset.zoom, 10);
    const label = el.dataset.label;

    /* Crear el mapa desactivando el zoom con rueda del ratón
       para evitar conflictos al hacer scroll en la página */
    const map = L.map('mapaOficina', {
        zoomControl:      true,
        scrollWheelZoom:  false
    }).setView([lat, lng], zoom);

    /* Capa de teselas de OpenStreetMap */
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom:     19,
        attribution: '© <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
    }).addTo(map);

    /* Marcador en las coordenadas con popup del nombre y dirección */
    L.marker([lat, lng])
     .addTo(map)
     .bindPopup(`<strong>${label.replace(/\n/g, '<br>')}</strong>`)
     .openPopup();
});
</script>
<?php endif; ?>

<?php require VIEWS_PATH . '/partials/footer.php'; ?>
