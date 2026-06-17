<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : views/oficinas_busqueda.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción   : Vista de búsqueda paginada de oficinas.
 *                 Incluye:
 *                   - Formulario de búsqueda avanzada (GET, URL compartible)
 *                   - Tabla de resultados paginada (20 por página)
 *                   - Filtro de texto libre sobre los resultados en cliente
 *                   - Paginador numérico con prev/next
 *
 * Variables recibidas desde OficinaController:
 *   $filtros    — array de filtros activos (puede estar vacío)
 *   $resultado  — array con claves: filas, total, pagina, porPagina, paginas
 *                 Es null si no se ha ejecutado ninguna búsqueda todavía
 *   $paises     — array [cod_pais, nombre] para el selector
 *   $error      — string|null
 * ========================================================= */

$pageTitle = 'Búsqueda de oficinas';
require VIEWS_PATH . '/partials/header.php';

/*
 * Función auxiliar para construir la URL de una página concreta
 * manteniendo todos los filtros activos en la query string.
 * Se usa en el paginador para generar los enlaces de navegación.
 */
function urlPagina(array $filtros, int $pagina): string {
    $params = array_merge($filtros, ['action' => 'buscar_oficinas', 'pagina' => $pagina]);
    return 'index.php?' . http_build_query($params);
}
?>

<div class="search-page oficinas-page">

    <!-- ── Cabecera de la sección ────────────────────────────────── -->
    <section class="search-hero">
        <div class="search-hero-content">
            <h1 class="search-title">
                <i class="fas fa-building"></i>
                Búsqueda de oficinas
            </h1>
            <p class="search-subtitle">
                Localiza cualquier sede por ciudad, CP, unidad orgánica&hellip;
            </p>
        </div>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         FORMULARIO DE BÚSQUEDA
         Método GET para que la URL sea compartible y el botón
         "atrás" del navegador funcione correctamente.
    ══════════════════════════════════════════════════════════ -->
    <section class="advanced-panel open" style="padding:24px 32px;">
        <form method="GET" action="index.php" id="formOficinas">
            <input type="hidden" name="action" value="buscar_oficinas">
            <input type="hidden" name="pagina" value="1"><!-- resetea a p.1 en cada búsqueda -->

            <div class="adv-group-label adv-group-label--erp">
                <i class="fas fa-building"></i> Filtros de búsqueda (ERP)
            </div>

            <div class="advanced-grid">

                <div class="adv-field">
                    <label for="f_id"><i class="fas fa-hashtag"></i> ID de oficina</label>
                    <input type="number" id="f_id" name="id_oficina" class="adv-input"
                           placeholder="1, 2, 3…" min="1"
                           value="<?= htmlspecialchars($filtros['id_oficina'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="f_nom"><i class="fas fa-building"></i> Nombre de oficina</label>
                    <input type="text" id="f_nom" name="nombre_oficina" class="adv-input"
                           placeholder="Sede Madrid…"
                           value="<?= htmlspecialchars($filtros['nombre_oficina'] ?? '') ?>">
                </div>

                <!-- NUEVO: filtro por ciudad -->
                <div class="adv-field">
                    <label for="f_ciudad"><i class="fas fa-city"></i> Ciudad</label>
                    <input type="text" id="f_ciudad" name="ciudad" class="adv-input"
                           placeholder="Madrid, Barcelona…"
                           value="<?= htmlspecialchars($filtros['ciudad'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="f_cp"><i class="fas fa-mail-bulk"></i> Código postal</label>
                    <input type="text" id="f_cp" name="cp" class="adv-input"
                           placeholder="28001…"
                           value="<?= htmlspecialchars($filtros['cp'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="f_dir"><i class="fas fa-map-marker-alt"></i> Dirección</label>
                    <input type="text" id="f_dir" name="direccion" class="adv-input"
                           placeholder="Gran Vía…"
                           value="<?= htmlspecialchars($filtros['direccion'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="f_uorg"><i class="fas fa-sitemap"></i> Unidad orgánica</label>
                    <input type="text" id="f_uorg" name="unidad_organica" class="adv-input"
                           placeholder="Dirección TI…"
                           value="<?= htmlspecialchars($filtros['unidad_organica'] ?? '') ?>">
                </div>

                <!-- Selector de país (datos del ERP) -->
                <div class="adv-field">
                    <label for="f_pais"><i class="fas fa-globe"></i> País</label>
                    <select id="f_pais" name="cod_pais" class="adv-input">
                        <option value="">— Todos los países —</option>
                        <?php foreach ($paises as $p): ?>
                        <option value="<?= htmlspecialchars($p['cod_pais']) ?>"
                            <?= isset($filtros['cod_pais']) && $filtros['cod_pais'] === $p['cod_pais'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nombre']) ?>
                            (<?= htmlspecialchars($p['cod_pais']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="advanced-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-search"></i> Buscar oficinas
                </button>
                <a href="index.php?action=buscar_oficinas" class="btn-secondary">
                    <i class="fas fa-eraser"></i> Limpiar filtros
                </a>
                <a href="index.php?action=search" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a CI
                </a>
            </div>
        </form>
    </section>

    <!-- ══════════════════════════════════════════════════════════
         ÁREA DE RESULTADOS
    ══════════════════════════════════════════════════════════ -->
    <section class="search-results-area">

        <!-- Mensaje de error -->
        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Estado inicial: todavía no se ha buscado -->
        <?php if ($resultado === null && empty($error)): ?>
        <div class="empty-state">
            <i class="fas fa-building empty-icon"></i>
            <h3>Introduce los filtros y pulsa Buscar</h3>
            <p>Puedes combinar varios filtros para afinar los resultados.</p>
        </div>

        <!-- Sin resultados -->
        <?php elseif (!empty($resultado) && $resultado['total'] === 0): ?>
        <div class="empty-state">
            <i class="fas fa-search empty-icon"></i>
            <h3>Sin resultados</h3>
            <p>No se encontraron oficinas con los filtros indicados.<br>
               Prueba a ampliar la búsqueda.</p>
        </div>

        <!-- Con resultados -->
        <?php elseif (!empty($resultado) && $resultado['total'] > 0): ?>

        <!-- Barra de info + filtro en cliente -->
        <div class="results-header" style="flex-wrap:wrap; gap:10px;">
            <span class="results-count">
                <strong><?= $resultado['total'] ?></strong>
                oficina<?= $resultado['total'] !== 1 ? 's' : '' ?> encontrada<?= $resultado['total'] !== 1 ? 's' : '' ?>.
                Página <strong><?= $resultado['pagina'] ?></strong>
                de <strong><?= $resultado['paginas'] ?></strong>
                (<?= $resultado['porPagina'] ?> por página)
            </span>

            <!--
                Filtro de texto libre sobre los resultados visibles.
                Actúa en cliente (JS) sobre las filas de la tabla actual
                sin recargar la página. Solo filtra la página visible,
                no todas las páginas.
            -->
            <div class="filtro-rapido-wrap">
                <i class="fas fa-filter"></i>
                <input type="text"
                       id="filtroRapido"
                       class="filtro-rapido-input"
                       placeholder="Filtrar estos resultados…"
                       autocomplete="off">
                <span id="filtroCount" class="filtro-count"></span>
            </div>
        </div>

        <!-- Tabla de resultados -->
        <div class="results-table-wrapper">
            <table class="results-table" id="tablaOficinas">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Nombre de la oficina</th>
                        <th>Unidad orgánica</th>
                        <th>Ciudad</th>
                        <th>CP</th>
                        <th>Dirección</th>
                        <th>País</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tablaOficinasBody">
                    <?php foreach ($resultado['filas'] as $of): ?>
                    <tr class="result-row ofic-row"
                        data-texto="<?= htmlspecialchars(strtolower(
                            $of['nombre_oficina'] . ' ' .
                            $of['unidad_organica'] . ' ' .
                            $of['ciudad'] . ' ' .
                            $of['cp'] . ' ' .
                            $of['direccion'] . ' ' .
                            $of['pais']
                        )) ?>">
                        <td class="ci-id">#<?= $of['id_oficina'] ?></td>
                        <td><?= htmlspecialchars($of['cod_oficina']) ?></td>
                        <td><strong><?= htmlspecialchars($of['nombre_oficina']) ?></strong></td>
                        <td><?= htmlspecialchars($of['unidad_organica']) ?></td>
                        <td><?= htmlspecialchars($of['ciudad']) ?></td>
                        <td class="mono"><?= htmlspecialchars($of['cp']) ?></td>
                        <td><?= htmlspecialchars($of['direccion']) ?></td>
                        <td>
                            <?= htmlspecialchars($of['pais']) ?>
                            <span class="cod-pais">(<?= htmlspecialchars($of['cod_pais']) ?>)</span>
                        </td>
                        <td>
                            <a href="index.php?action=oficina&id=<?= $of['id_oficina'] ?>"
                               class="btn-detail">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ══════════════════════════════════════════════════════
             PAGINADOR
             Muestra prev, hasta 7 páginas numeradas y next.
             Las páginas se construyen con urlPagina() para mantener
             todos los filtros activos en cada enlace.
        ══════════════════════════════════════════════════════ -->
        <?php if ($resultado['paginas'] > 1): ?>
        <nav class="paginador" aria-label="Paginación de resultados">

            <!-- Botón anterior -->
            <?php if ($resultado['pagina'] > 1): ?>
            <a href="<?= urlPagina($filtros, $resultado['pagina'] - 1) ?>"
               class="pag-btn pag-prev" title="Página anterior">
                <i class="fas fa-chevron-left"></i> Anterior
            </a>
            <?php else: ?>
            <span class="pag-btn pag-prev pag-disabled">
                <i class="fas fa-chevron-left"></i> Anterior
            </span>
            <?php endif; ?>

            <!-- Páginas numeradas (máximo 7 visibles con elipsis) -->
            <?php
            /*
             * Algoritmo de ventana de páginas:
             * Siempre muestra la primera, la última y un rango de ±3
             * alrededor de la página actual. Entre saltos se añade "…".
             */
            $paginaActual = $resultado['pagina'];
            $totalPaginas = $resultado['paginas'];
            $ventana      = 2; // páginas a cada lado de la actual

            $mostrar = [];
            for ($i = 1; $i <= $totalPaginas; $i++) {
                if (
                    $i === 1 ||
                    $i === $totalPaginas ||
                    abs($i - $paginaActual) <= $ventana
                ) {
                    $mostrar[] = $i;
                }
            }
            $mostrar = array_unique($mostrar);
            sort($mostrar);

            $anterior = null;
            foreach ($mostrar as $num):
                /* Insertar elipsis si hay un salto entre páginas */
                if ($anterior !== null && $num - $anterior > 1):
            ?>
            <span class="pag-ellipsis">…</span>
            <?php
                endif;
                $anterior = $num;
            ?>
            <?php if ($num === $paginaActual): ?>
            <span class="pag-btn pag-current"><?= $num ?></span>
            <?php else: ?>
            <a href="<?= urlPagina($filtros, $num) ?>" class="pag-btn"><?= $num ?></a>
            <?php endif; ?>
            <?php endforeach; ?>

            <!-- Botón siguiente -->
            <?php if ($resultado['pagina'] < $resultado['paginas']): ?>
            <a href="<?= urlPagina($filtros, $resultado['pagina'] + 1) ?>"
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

        <?php endif; // resultados ?>

    </section>
</div>

<!--
    Script para el filtro rápido en cliente.
    Actúa sobre las filas visibles en la página actual sin recargar.
    Usa el atributo data-texto (en minúsculas) para comparar.
-->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const input  = document.getElementById('filtroRapido');
    const count  = document.getElementById('filtroCount');
    const tbody  = document.getElementById('tablaOficinasBody');

    if (!input || !tbody) return;

    input.addEventListener('input', () => {
        const q    = input.value.trim().toLowerCase();
        const filas = tbody.querySelectorAll('.ofic-row');
        let visibles = 0;

        filas.forEach(fila => {
            /* Comparar contra el texto precalculado en data-texto */
            const coincide = !q || fila.dataset.texto.includes(q);
            fila.style.display = coincide ? '' : 'none';
            if (coincide) visibles++;
        });

        /* Mostrar cuántas filas son visibles tras el filtro */
        if (q) {
            count.textContent = `${visibles} de ${filas.length}`;
            count.style.display = 'inline';
        } else {
            count.style.display = 'none';
        }
    });

    /* Limpiar el filtro al cambiar de página (la URL cambia) */
    window.addEventListener('beforeunload', () => {
        if (input) input.value = '';
    });
});
</script>

<?php require VIEWS_PATH . '/partials/footer.php'; ?>
