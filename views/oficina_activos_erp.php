<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : views/oficina_activos_erp.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción   : Vista de listado paginado de los activos del ERP
 *                 de una oficina, opcionalmente filtrados por clase
 *                 de activo. Se accede desde oficina.php pulsando "Ver"
 *                 en la sección "Activos registrados — ERP".
 *
 * Variables recibidas desde OficinaController::showActivosErp():
 *   $oficina   — array con datos de la oficina (ERP), o null si error
 *   $resultado — array con claves:
 *                  filas, total, pagina, porPagina, paginas,
 *                  id_oficina, id_clase, nombre_clase
 *                Es null si hubo un error antes de ejecutar la búsqueda.
 *   $error     — string|null
 *
 * Cada fila de $resultado['filas'] incluye:
 *   codigo_inventario, clase, marca, modelo, numero_serie,
 *   fecha_compra, garantia, id_ci_cmdb (int|null)
 *
 * Paginación: 20 registros por página (porPagina), con enlaces GET
 * que mantienen id_oficina, id_clase y pagina en la URL.
 * ========================================================= */

$pageTitle = $oficina
    ? ($resultado['nombre_clase'] ?? 'Activos') . ' — ' . $oficina['nombre_oficina']
    : 'Activos ERP';

require VIEWS_PATH . '/partials/header.php';

/*
 * Función auxiliar: construye la URL de una página concreta del
 * listado, manteniendo id_oficina e id_clase en la query string.
 */
function urlPaginaActivosErp(int $idOficina, int $idClase, int $pagina): string {
    return 'index.php?' . http_build_query([
        'action'     => 'activos_erp_oficina',
        'id_oficina' => $idOficina,
        'id_clase'   => $idClase,
        'pagina'     => $pagina,
    ]);
}

/*
 * Icono según la clase del activo (mismo criterio visual que en
 * search.php y oficina.php, redefinido aquí con guarda function_exists
 * porque esta vista puede cargarse de forma independiente).
 */
if (!function_exists('iconoClaseErp')) {
    function iconoClaseErp(string $clase): string {
        $c = strtolower($clase);
        if (str_contains($c, 'pc') || str_contains($c, 'ordenador')) return '<i class="fas fa-desktop"></i>';
        if (str_contains($c, 'laptop') || str_contains($c, 'portátil') || str_contains($c, 'portatil')) return '<i class="fas fa-laptop"></i>';
        if (str_contains($c, 'tablet'))    return '<i class="fas fa-tablet-alt"></i>';
        if (str_contains($c, 'impresora')) return '<i class="fas fa-print"></i>';
        if (str_contains($c, 'monitor'))   return '<i class="fas fa-tv"></i>';
        if (str_contains($c, 'switch'))    return '<i class="fas fa-network-wired"></i>';
        if (str_contains($c, 'router'))    return '<i class="fas fa-wifi"></i>';
        if (str_contains($c, 'servidor') || str_contains($c, 'server')) return '<i class="fas fa-server"></i>';
        if (str_contains($c, 'nas'))       return '<i class="fas fa-hdd"></i>';
        if (str_contains($c, 'tel') || str_contains($c, 'móvil') || str_contains($c, 'movil')) return '<i class="fas fa-mobile-alt"></i>';
        return '<i class="fas fa-box"></i>';
    }
}
?>

<div class="search-page oficina-activos-erp-page">

    <!-- ── Cabecera con breadcrumb contextual ───────────────────── -->
    <section class="search-hero">
        <div class="search-hero-content">

            <?php if ($oficina): ?>
            <nav class="breadcrumb breadcrumb--hero">
                <a href="index.php?action=search"><i class="fas fa-search"></i> Búsqueda</a>
                <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
                <a href="index.php?action=oficina&id=<?= $oficina['id_oficina'] ?>">
                    <i class="fas fa-building"></i>
                    <?= htmlspecialchars($oficina['nombre_oficina']) ?>
                </a>
                <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
                <span><?= htmlspecialchars($resultado['nombre_clase'] ?? 'Activos') ?></span>
            </nav>
            <h1 class="search-title">
                <i class="fas fa-boxes"></i>
                <?= htmlspecialchars($resultado['nombre_clase'] ?? 'Activos') ?>
                <span class="search-title-sub">
                    en <?= htmlspecialchars($oficina['nombre_oficina']) ?>
                    <span class="search-title-fuente">— Inventario ERP</span>
                </span>
            </h1>
            <?php else: ?>
            <h1 class="search-title">
                <i class="fas fa-boxes"></i> Activos del ERP
            </h1>
            <?php endif; ?>

        </div>
    </section>

    <!-- ── Resultados ─────────────────────────────────────────────── -->
    <section class="search-results-area">

        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($resultado !== null): ?>

        <?php if ($resultado['total'] === 0): ?>
        <div class="empty-state">
            <i class="fas fa-box-open empty-icon"></i>
            <h3>Sin activos</h3>
            <p>No se encontraron activos de esta clase para la oficina en el ERP.</p>
        </div>

        <?php else:
            $filas   = $resultado['filas'];
            $pag     = $resultado['pagina'];
            $paginas = $resultado['paginas'];
            $total   = $resultado['total'];
        ?>

        <!-- Cabecera de resultados -->
        <div class="results-header">
            <span class="results-count">
                <strong><?= number_format($total) ?></strong>
                activo<?= $total !== 1 ? 's' : '' ?> registrado<?= $total !== 1 ? 's' : '' ?> en el ERP
                &nbsp;·&nbsp; Página <strong><?= $pag ?></strong> de <strong><?= $paginas ?></strong>
            </span>
        </div>

        <!-- Tabla de resultados -->
        <div class="results-table-wrapper">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>Código inventario</th>
                        <th>Clase</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Nº Serie</th>
                        <th>Fecha compra</th>
                        <th>Garantía</th>
                        <th>CMDB</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $act): ?>
                    <tr>
                        <td class="ci-id">#<?= htmlspecialchars($act['codigo_inventario']) ?></td>
                        <td>
                            <span class="badge-clase">
                                <?= iconoClaseErp($act['clase']) ?>
                                <?= htmlspecialchars($act['clase']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($act['marca'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($act['modelo'] ?? '—') ?></td>
                        <td class="ci-serie"><?= htmlspecialchars($act['numero_serie'] ?? '—') ?></td>
                        <td><?= $act['fecha_compra'] ? date('d/m/Y', strtotime($act['fecha_compra'])) : '—' ?></td>
                        <td>
                            <?= !is_null($act['garantia'])
                                ? $act['garantia'] . ' año' . ($act['garantia'] != 1 ? 's' : '')
                                : '—' ?>
                        </td>
                        <td>
                            <?php
                            /*
                             * Cruce con la CMDB: si el activo tiene un CI
                             * con el mismo número de serie, enlazar a su
                             * ficha de detalle. En caso contrario, mostrar
                             * un aviso — coherente con las verificaciones
                             * de consistencia de la ficha de CI.
                             */
                            if (!empty($act['id_ci_cmdb'])): ?>
                            <a href="index.php?action=detail&id=<?= $act['id_ci_cmdb'] ?>"
                               class="btn-detail" title="Ver ficha del CI en la CMDB">
                                <i class="fas fa-eye"></i> Ver CI
                            </a>
                            <?php else: ?>
                            <span class="ci-sin-oficina" title="No existe ningún CI en la CMDB con este número de serie">
                                <i class="fas fa-times-circle"></i> No está en CMDB
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ══════════════════════════════════════════════════════
             PAGINADOR (enlaces GET, igual que oficinas_busqueda.php)
        ══════════════════════════════════════════════════════ -->
        <?php if ($paginas > 1): ?>
        <nav class="paginador" aria-label="Navegación por páginas">

            <?php if ($pag > 1): ?>
            <a href="<?= urlPaginaActivosErp($resultado['id_oficina'], $resultado['id_clase'], $pag - 1) ?>"
               class="pag-btn pag-prev" title="Página anterior">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
            <?php else: ?>
            <span class="pag-btn pag-prev pag-disabled">
                <i class="fas fa-chevron-left"></i> Anterior
            </span>
            <?php endif; ?>

            <?php
            /* Ventana de páginas ±2 con elipsis — mismo criterio que el resto de paginadores */
            $ventana = 2;
            $mostrar = [];
            for ($i = 1; $i <= $paginas; $i++) {
                if ($i === 1 || $i === $paginas || abs($i - $pag) <= $ventana) {
                    $mostrar[] = $i;
                }
            }
            $mostrar  = array_unique($mostrar);
            sort($mostrar);

            $anterior = null;
            foreach ($mostrar as $num):
                if ($anterior !== null && $num - $anterior > 1):
            ?>
            <span class="pag-ellipsis">…</span>
            <?php
                endif;
                $anterior = $num;
            ?>
            <?php if ($num === $pag): ?>
            <span class="pag-btn pag-current"><?= $num ?></span>
            <?php else: ?>
            <a href="<?= urlPaginaActivosErp($resultado['id_oficina'], $resultado['id_clase'], $num) ?>"
               class="pag-btn"><?= $num ?></a>
            <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($pag < $paginas): ?>
            <a href="<?= urlPaginaActivosErp($resultado['id_oficina'], $resultado['id_clase'], $pag + 1) ?>"
               class="pag-btn pag-next" title="Página siguiente">
                Siguiente <i class="fas fa-chevron-right"></i>
            </a>
            <?php else: ?>
            <span class="pag-btn pag-next pag-disabled">
                Siguiente <i class="fas fa-chevron-right"></i>
            </span>
            <?php endif; ?>

        </nav>
        <?php endif; // paginador ?>

        <?php endif; // total === 0 ?>

        <?php endif; // resultado !== null ?>

        <!-- Volver a la ficha de oficina -->
        <?php if ($oficina): ?>
        <div class="oficina-activos-volver">
            <a href="index.php?action=oficina&id=<?= $oficina['id_oficina'] ?>" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a la ficha de oficina
            </a>
        </div>
        <?php endif; ?>

    </section>
</div>

<?php require VIEWS_PATH . '/partials/footer.php'; ?>
