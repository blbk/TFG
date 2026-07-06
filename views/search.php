<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : views/search.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción   : Página de búsqueda de elementos de configuración (CI). 
 * Permite búsqueda simple y avanzada.
 *
 * Variables recibidas desde UsuarioController::showUsuario():
 *   $login    — login solicitado (string, siempre presente)
 *   $usuario  — array [login, nomape, tlf_movil, foto] o null si no existe
 *   $rutaFoto — ruta relativa a la imagen de perfil (con fallback)
 *   $error    — string|null
 * ========================================================= */

$pageTitle    = 'Búsqueda de CI';
$modoAvanzado = $modoAvanzado ?? false;
$modoOficina  = $modoOficina  ?? false;   // true cuando viene de la ficha de oficina
require VIEWS_PATH . '/partials/header.php';

function iconoClase(string $clase): string {
    $c = strtolower($clase);
    if (str_contains($c, 'pc') || str_contains($c, 'ordenador'))  return '<i class="fas fa-desktop"></i>';
    if (str_contains($c, 'laptop'))    return '<i class="fas fa-laptop"></i>';
    if (str_contains($c, 'impresora')) return '<i class="fas fa-print"></i>';
    if (str_contains($c, 'monitor'))   return '<i class="fas fa-tv"></i>';
    if (str_contains($c, 'switch'))    return '<i class="fas fa-network-wired"></i>';
    if (str_contains($c, 'router'))    return '<i class="fas fa-wifi"></i>';
    if (str_contains($c, 'servidor') || str_contains($c, 'server')) return '<i class="fas fa-server"></i>';
    if (str_contains($c, 'nas'))       return '<i class="fas fa-hdd"></i>';
    if (str_contains($c, 'tel') || str_contains($c, 'ip')) return '<i class="fas fa-phone"></i>';
    return '<i class="fas fa-cube"></i>';
}

// ¿Hay algún campo del ERP activo en los filtros actuales?
$filtrosErp = ['id_oficina','nombre_oficina','direccion','cp','ciudad','unidad_organica'];
$tieneErp   = !empty(array_intersect_key($filtros ?? [], array_flip($filtrosErp)));
?>

<div class="search-page">

    <!-- Hero -->
    <section class="search-hero">
        <div class="search-hero-content">

            <?php if ($modoOficina): ?>
            <!-- Modo activos de oficina: breadcrumb y título contextual -->
            <nav class="breadcrumb breadcrumb--hero">
                <a href="index.php?action=search"><i class="fas fa-search"></i> Búsqueda</a>
                <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
                <a href="index.php?action=oficina&id=<?= $resultado['id_oficina'] ?? 0 ?>">
                    <i class="fas fa-building"></i>
                    <?= htmlspecialchars($nombreOficina ?? 'Oficina') ?>
                </a>
                <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
                <span><?= htmlspecialchars($resultado['nombre_clase'] ?? 'Activos') ?></span>
            </nav>
            <h1 class="search-title">
                <i class="fas fa-database"></i>
                <?= htmlspecialchars($resultado['nombre_clase'] ?? 'Activos') ?>
                <span class="search-title-sub">en <?= htmlspecialchars($nombreOficina ?? '') ?></span>
            </h1>
            <?php else: ?>
            <h1 class="search-title">
                <i class="fas fa-search-plus"></i>
                Buscar elementos de configuración
            </h1>
            <p class="search-subtitle">Localiza cualquier CI por nombre, IP, modelo, número de serie, login, ID, oficina&hellip;</p>
            <?php endif; ?>

            <?php if (!$modoOficina): ?>
            <form id="searchForm" method="GET" action="index.php" class="search-bar-form">
                <input type="hidden" name="action" value="search">
                <div class="search-bar-wrapper">
                    <i class="fas fa-search search-bar-icon"></i>
                    <input type="text" id="searchInput" name="q" class="search-bar-input"
                           placeholder="Busca por nombre, IP, modelo, serie, hostname, login, ID del CI&hellip;"
                           value="<?= htmlspecialchars($termino ?? '') ?>"
                           autocomplete="off" spellcheck="false">
                    <?php if (!empty($termino)): ?>
                    <button type="button" id="clearSearch" class="search-clear-btn" title="Limpiar">
                        <i class="fas fa-times"></i>
                    </button>
                    <?php endif; ?>
                    <button type="submit" class="search-bar-btn">Buscar</button>
                </div>
                <div id="searchSuggestions" class="search-suggestions" hidden></div>
            </form>

            <button type="button" id="toggleAdvanced"
                    class="btn-advanced-toggle <?= $modoAvanzado ? 'active' : '' ?>">
                <i class="fas fa-sliders-h"></i>
                Búsqueda avanzada
                <i class="fas fa-chevron-down chevron-icon"></i>
            </button>
            <?php else: ?>
            <!-- En modo oficina: botón de vuelta a la ficha -->
            <a href="index.php?action=oficina&id=<?= $resultado['id_oficina'] ?? 0 ?>"
               class="btn-secondary" style="margin-top:14px; display:inline-flex;">
                <i class="fas fa-arrow-left"></i> Volver a la ficha de oficina
            </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- ══════════════════════════════════════════
         PANEL DE BÚSQUEDA AVANZADA
    ══════════════════════════════════════════ -->
    <section id="advancedPanel" class="advanced-panel <?= $modoAvanzado ? 'open' : '' ?>">
        <form method="POST" action="index.php?action=advanced_search" id="advancedForm">

            <!-- Grupo CMDB -->
            <div class="adv-group-label">
                <i class="fas fa-database"></i> Datos del CI (CMDB)
            </div>
            <div class="advanced-grid">

                <!-- Identificador único del CI -->
                <div class="adv-field">
                    <label for="adv_id_ci"><i class="fas fa-hashtag"></i> ID del CI</label>
                    <input type="number" id="adv_id_ci" name="id_ci" class="adv-input"
                           placeholder="276483…" min="1"
                           value="<?= htmlspecialchars($filtros['id_ci'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="adv_clase"><i class="fas fa-tag"></i> Clase de CI</label>
                    <select id="adv_clase" name="clase" class="adv-input">
                        <option value="">— Todas —</option>
                        <?php foreach ($clases as $cl): ?>
                        <option value="<?= $cl['id_clase'] ?>"
                            <?= isset($filtros['clase']) && $filtros['clase'] == $cl['id_clase'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cl['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="adv-field">
                    <label for="adv_marca"><i class="fas fa-industry"></i> Marca</label>
                    <input type="text" id="adv_marca" name="marca" class="adv-input"
                           placeholder="HP, Dell, Cisco…"
                           value="<?= htmlspecialchars($filtros['marca'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="adv_modelo"><i class="fas fa-box"></i> Modelo</label>
                    <input type="text" id="adv_modelo" name="modelo" class="adv-input"
                           placeholder="ProBook, Latitude…"
                           value="<?= htmlspecialchars($filtros['modelo'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="adv_ns"><i class="fas fa-barcode"></i> Número de serie</label>
                    <input type="text" id="adv_ns" name="numero_serie" class="adv-input"
                           placeholder="SN12345…"
                           value="<?= htmlspecialchars($filtros['numero_serie'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="adv_hostname"><i class="fas fa-server"></i> Hostname</label>
                    <input type="text" id="adv_hostname" name="hostname" class="adv-input"
                           placeholder="PC1234…"
                           value="<?= htmlspecialchars($filtros['hostname'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="adv_ip"><i class="fas fa-network-wired"></i> Dirección IP</label>
                    <input type="text" id="adv_ip" name="ip" class="adv-input"
                           placeholder="10.88.1…"
                           value="<?= htmlspecialchars($filtros['ip'] ?? '') ?>">
                </div>

                <!-- Login del último usuario que inició sesión (tabla pc) -->
                <div class="adv-field">
                    <label for="adv_login"><i class="fas fa-user"></i> Login de usuario</label>
                    <input type="text" id="adv_login" name="login" class="adv-input"
                           placeholder="ABC1234…"
                           value="<?= htmlspecialchars($filtros['login'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="adv_nl"><i class="fas fa-desktop"></i> Nombre local</label>
                    <input type="text" id="adv_nl" name="nombre_local" class="adv-input"
                           placeholder="PC1234…"
                           value="<?= htmlspecialchars($filtros['nombre_local'] ?? '') ?>">
                </div>

                <div class="adv-field adv-field--date">
                    <label><i class="fas fa-calendar-alt"></i> Fecha de actividad</label>
                    <div class="date-range">
                        <input type="date" name="fecha_desde" class="adv-input"
                               value="<?= htmlspecialchars($filtros['fecha_desde'] ?? '') ?>" title="Desde">
                        <span class="date-sep">—</span>
                        <input type="date" name="fecha_hasta" class="adv-input"
                               value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? '') ?>" title="Hasta">
                    </div>
                </div>

            </div>

            <!-- Separador -->
            <div class="adv-separator"></div>

            <!-- Grupo ERP -->
            <div class="adv-group-label adv-group-label--erp">
                <i class="fas fa-building"></i> Datos de la oficina (ERP)
            </div>
            <div class="advanced-grid">

                <div class="adv-field">
                    <label for="adv_id_of"><i class="fas fa-hashtag"></i> ID de oficina</label>
                    <input type="number" id="adv_id_of" name="id_oficina" class="adv-input"
                           placeholder="1, 2, 3…" min="1"
                           value="<?= htmlspecialchars($filtros['id_oficina'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="adv_nom_of"><i class="fas fa-building"></i> Nombre de oficina</label>
                    <input type="text" id="adv_nom_of" name="nombre_oficina" class="adv-input"
                           placeholder="Sede Madrid…"
                           value="<?= htmlspecialchars($filtros['nombre_oficina'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="adv_dir"><i class="fas fa-map-marker-alt"></i> Dirección</label>
                    <input type="text" id="adv_dir" name="direccion" class="adv-input"
                           placeholder="Calle Gran Vía…"
                           value="<?= htmlspecialchars($filtros['direccion'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="adv_cp"><i class="fas fa-mail-bulk"></i> Código postal</label>
                    <input type="text" id="adv_cp" name="cp" class="adv-input"
                           placeholder="28001…"
                           value="<?= htmlspecialchars($filtros['cp'] ?? '') ?>">
                </div>

                <div class="adv-field">
                    <label for="adv_uorg"><i class="fas fa-sitemap"></i> Unidad orgánica</label>
                    <input type="text" id="adv_uorg" name="unidad_organica" class="adv-input"
                           placeholder="Dirección TI…"
                           value="<?= htmlspecialchars($filtros['unidad_organica'] ?? '') ?>">
                </div>

                <!-- Ciudad de la oficina (join con ERP) -->
                <div class="adv-field">
                    <label for="adv_ciudad"><i class="fas fa-city"></i> Ciudad</label>
                    <input type="text" id="adv_ciudad" name="ciudad" class="adv-input"
                           placeholder="Madrid, Barcelona…"
                           value="<?= htmlspecialchars($filtros['ciudad'] ?? '') ?>">
                </div>

            </div>

            <div class="advanced-actions">
                <!--
                    El texto del botón cambia dinámicamente según qué campos
                    están rellenos: si solo hay filtros ERP, avisa al usuario
                    de que verá oficinas. El JS de search.js gestiona esto.
                -->
                <button type="submit" class="btn-primary" id="btnBuscarAvanzado">
                    <i class="fas fa-search"></i>
                    <span id="btnBuscarTexto">Buscar con filtros</span>
                </button>
                <button type="button" id="clearAdvanced" class="btn-secondary">
                    <i class="fas fa-eraser"></i> Limpiar filtros
                </button>
                <!-- Aviso contextual: aparece cuando solo hay filtros ERP -->
                <span id="avisoOficinas" class="adv-aviso-oficinas" hidden>
                    <i class="fas fa-info-circle"></i>
                    Solo se han rellenado filtros de oficina — el resultado será un listado de oficinas
                </span>
            </div>
        </form>
    </section>

     <!-- ══════════════════════════════════════════
         LOADER — visible mientras el servidor procesa la búsqueda.
         Se muestra al enviar cualquiera de los dos formularios y
         desaparece en cuanto el DOM termina de cargar los resultados.
    ══════════════════════════════════════════ -->
    <div id="searchLoader" class="search-loader" hidden aria-label="Buscando…">
        <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200' class="loader-svg" aria-hidden="true">
            <g fill='#0098CD' stroke='#0098CD' stroke-width='15'>
                <circle r='15' cx='50'  cy='150'>
                    <animateTransform attributeName='transform' type='translate' calcMode='spline'
                        dur='2' values='0 0;0 -100' keySplines='.5 0 .5 1' repeatCount='indefinite'/>
                </circle>
                <circle r='15' cx='50'  cy='50'>
                    <animateTransform attributeName='transform' type='translate' calcMode='spline'
                        dur='2' values='0 0;100 0' keySplines='.5 0 .5 1' repeatCount='indefinite'/>
                </circle>
                <circle r='15' cx='150' cy='50'>
                    <animateTransform attributeName='transform' type='translate' calcMode='spline'
                        dur='2' values='0 0;0 100' keySplines='.5 0 .5 1' repeatCount='indefinite'/>
                </circle>
                <circle r='15' cx='150' cy='150'>
                    <animateTransform attributeName='transform' type='translate' calcMode='spline'
                        dur='2' values='0 0;-100 0' keySplines='.5 0 .5 1' repeatCount='indefinite'/>
                </circle>
            </g>
        </svg>
        <p class="loader-texto">Buscando…</p>
    </div>

    <!-- ══════════════════════════════════════════
         RESULTADOS
    ══════════════════════════════════════════ -->
    <section class="search-results-area">

        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if ($resultado === null): ?>
        <!-- Estado inicial: estadísticas -->
        <div class="dashboard-stats">
            <h2 class="stats-title">Resumen del inventario</h2>
            <div class="stats-grid">
                <div class="stat-card stat-card--total">
                    <div class="stat-icon"><i class="fas fa-cubes"></i></div>
                    <div class="stat-info">
                        <span class="stat-num"><?= number_format((int)$estadisticas['total_ci'],0,'','.') ?></span>
                        <span class="stat-label">Total CI</span>
                    </div>
                </div>
                <?php foreach (array_slice($estadisticas['por_clase'], 0, 5) as $cl): ?>
                <div class="stat-card">
                    <div class="stat-icon"><?= iconoClase($cl['nombre']) ?></div>
                    <div class="stat-info">
                        <span class="stat-num"><?= number_format($cl['total'],0,'','.') ?></span>
                        <span class="stat-label"><?= htmlspecialchars($cl['nombre']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php elseif ($resultado['total'] === 0): ?>
        <div class="empty-state">
            <i class="fas fa-search empty-icon"></i>
            <h3>Sin resultados</h3>
            <p>No se encontraron CI que coincidan con la búsqueda.<br>
               Prueba con otros términos o usa la búsqueda avanzada.</p>
        </div>

        <?php else:
            /*
             * Alias cortos para no repetir $resultado[…] en toda la vista.
             * $filas   — array de CI de la página actual
             * $pag     — número de página actual
             * $paginas — total de páginas
             * $total   — total de coincidencias
             */
            $filas   = $resultado['filas'];
            $pag     = $resultado['pagina'];
            $paginas = $resultado['paginas'];
            $total   = $resultado['total'];
        ?>

        <!-- Cabecera de resultados -->
        <div class="results-header">
            <span class="results-count">
                <strong><?= number_format($total) ?></strong>
                resultado<?= $total !== 1 ? 's' : '' ?>
                <?php if (!empty($termino)): ?>
                para <em>"<?= htmlspecialchars($termino) ?>"</em>
                <?php endif; ?>
                &nbsp;·&nbsp; Página <strong><?= $pag ?></strong> de <strong><?= $paginas ?></strong>
            </span>
            <div class="results-view-toggle">
                <button class="view-btn active" id="viewTable" title="Vista tabla"><i class="fas fa-list"></i></button>
                <button class="view-btn" id="viewCards" title="Vista tarjetas"><i class="fas fa-th-large"></i></button>
            </div>
        </div>

        <!-- Vista tabla -->
        <div id="tableView" class="results-table-wrapper">
            <table class="results-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Clase</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Nombre / Hostname</th>
                        <th>IP</th>
                        <th>Nº Serie</th>
                        <?php if ($tieneErp): ?>
                        <th><i class="fas fa-building"></i> Oficina</th>
                        <th>Ciudad</th>
                        <th>Unidad orgánica</th>
                        <?php else: ?>
                        <th>Fecha</th>
                        <?php endif; ?>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $ci): ?>
                    <tr class="result-row" data-id="<?= $ci['id_ci'] ?>">
                        <td class="ci-id">#<?= $ci['id_ci'] ?></td>
                        <td><span class="badge-clase"><?= htmlspecialchars($ci['clase']) ?></span></td>
                        <td><?= htmlspecialchars($ci['marca'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($ci['modelo'] ?? '—') ?></td>
                        <td>
                            <?= htmlspecialchars($ci['nombre_local'] ?: ($ci['hostname'] ?: '—')) ?>
                            <?php if (!empty($ci['login_usuario'])): ?>
                            <a href="index.php?action=usuario&login=<?= urlencode($ci['login_usuario']) ?>"
                               class="login-subtitle" title="Ver ficha del usuario"
                               onclick="event.stopPropagation()">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($ci['login_usuario']) ?>
                            </a>
                            <?php endif; ?>
                        </td>
                        <td class="ci-ip"><?= htmlspecialchars($ci['direccion_ip'] ?? '—') ?></td>
                        <td class="ci-serie"><?= htmlspecialchars($ci['numero_serie'] ?? '—') ?></td>
                        <?php if ($tieneErp): ?>
                        <td>
                            <?php if (!empty($ci['oficina'])): ?>
                            <span class="badge-oficina" title="<?= htmlspecialchars($ci['oficina_dir'] ?? '') ?> — CP <?= htmlspecialchars($ci['oficina_cp'] ?? '') ?>">
                                <i class="fas fa-building"></i>
                                #<?= $ci['id_oficina'] ?> — <?= htmlspecialchars($ci['oficina']) ?>
                            </span>
                            <?php else: ?>
                            <span class="ci-sin-oficina">Sin oficina asignada</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($ci['ciudad'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($ci['unidad_organica'] ?? '—') ?></td>
                        <?php else: ?>
                        <td><?= $ci['fecha'] ? date('d/m/Y', strtotime($ci['fecha'])) : '—' ?></td>
                        <?php endif; ?>
                        <td>
                            <a href="index.php?action=detail&id=<?= $ci['id_ci'] ?>" class="btn-detail">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Vista tarjetas -->
        <div id="cardsView" class="results-cards" hidden>
            <?php foreach ($filas as $ci): ?>
            <div class="ci-card" data-id="<?= $ci['id_ci'] ?>"
                 onclick="window.location='index.php?action=detail&id=<?= $ci['id_ci'] ?>'"
                 style="cursor:pointer;">
                <div class="ci-card-header">
                    <span class="ci-card-icon"><?= iconoClase($ci['clase']) ?></span>
                    <span class="badge-clase"><?= htmlspecialchars($ci['clase']) ?></span>
                    <span class="ci-card-id">#<?= $ci['id_ci'] ?></span>
                </div>
                <div class="ci-card-body">
                    <div class="ci-card-name">
                        <?= htmlspecialchars(trim(($ci['marca'] ?? '') . ' ' . ($ci['modelo'] ?? ''))) ?>
                    </div>
                    <?php if ($ci['nombre_local'] || $ci['hostname']): ?>
                    <div class="ci-card-detail">
                        <i class="fas fa-tag"></i>
                        <?= htmlspecialchars($ci['nombre_local'] ?: $ci['hostname']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($ci['direccion_ip']): ?>
                    <div class="ci-card-detail">
                        <i class="fas fa-network-wired"></i> <?= htmlspecialchars($ci['direccion_ip']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($ci['oficina'])): ?>
                    <div class="ci-card-detail">
                        <i class="fas fa-building"></i>
                        <?= htmlspecialchars($ci['oficina']) ?>
                        <?php if (!empty($ci['unidad_organica'])): ?>
                        <span class="ci-card-uorg">(<?= htmlspecialchars($ci['unidad_organica']) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($ci['numero_serie']): ?>
                    <div class="ci-card-detail">
                        <i class="fas fa-barcode"></i> <?= htmlspecialchars($ci['numero_serie']) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($ci['login_usuario'])): ?>
                    <div class="ci-card-detail">
                        <i class="fas fa-user"></i>
                        <a href="index.php?action=usuario&login=<?= urlencode($ci['login_usuario']) ?>"
                           class="link-usuario" title="Ver ficha del usuario"
                           onclick="event.stopPropagation()">
                            <?= htmlspecialchars($ci['login_usuario']) ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="ci-card-footer">
                    <span><i class="fas fa-arrow-right"></i> Ver detalle</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ══════════════════════════════════════════════════════
             PAGINADOR
             Para búsqueda simple: enlaces GET con ?q=…&pagina=N
             Para búsqueda avanzada: formulario POST con página oculta
        ══════════════════════════════════════════════════════ -->
        <?php if ($paginas > 1): ?>
        <nav class="paginador" aria-label="Navegación por páginas">

            <?php
            /*
             * Genera la URL o el botón de una página concreta según el
             * tipo de búsqueda activo:
             *   - Simple   → enlace GET con q= y pagina=
             *   - Avanzada → formulario POST con pagina oculta
             */
            $ventana = 2; // páginas a cada lado de la actual

            $mostrar  = [];
            for ($i = 1; $i <= $paginas; $i++) {
                if ($i === 1 || $i === $paginas || abs($i - $pag) <= $ventana) {
                    $mostrar[] = $i;
                }
            }
            $mostrar  = array_unique($mostrar);
            sort($mostrar);

            /* Función local para construir el botón/enlace de cada página */
            $btnPagina = function(int $num, bool $activa) use ($termino, $modoAvanzado, $filtros, $modoOficina, $resultado): string {
                $cls = $activa ? 'pag-btn pag-current' : 'pag-btn';
                if ($activa) return "<span class=\"$cls\">$num</span>";

                if ($modoOficina) {
                    /* Modo activos de oficina: GET con id_oficina + id_clase */
                    $url = 'index.php?' . http_build_query([
                        'action'     => 'activos_oficina',
                        'id_oficina' => $resultado['id_oficina'] ?? 0,
                        'id_clase'   => $resultado['id_clase']   ?? 0,
                        'pagina'     => $num,
                    ]);
                    return "<a href=\"$url\" class=\"$cls\">$num</a>";
                }

                if (!$modoAvanzado) {
                    /* Búsqueda simple: enlace GET */
                    $url = 'index.php?' . http_build_query([
                        'action' => 'search', 'q' => $termino, 'pagina' => $num
                    ]);
                    return "<a href=\"$url\" class=\"$cls\">$num</a>";
                } else {
                    /* Búsqueda avanzada: botón que envía el formulario con pagina=N */
                    return "<button type=\"button\" class=\"$cls\"
                                    onclick=\"cambiarPaginaAvanzada($num)\">$num</button>";
                }
            };

            /* Botón Anterior */
            $pagAnterior = $pag - 1;
            if ($pag > 1) {
                if ($modoOficina) {
                    $urlAnterior = 'index.php?' . http_build_query([
                        'action'     => 'activos_oficina',
                        'id_oficina' => $resultado['id_oficina'] ?? 0,
                        'id_clase'   => $resultado['id_clase']   ?? 0,
                        'pagina'     => $pagAnterior,
                    ]);
                    echo "<a href=\"$urlAnterior\" class=\"pag-btn pag-prev\">
                              <i class=\"fas fa-chevron-left\"></i> Anterior
                          </a>";
                } elseif (!$modoAvanzado) {
                    $urlAnterior = 'index.php?' . http_build_query([
                        'action' => 'search', 'q' => $termino, 'pagina' => $pagAnterior
                    ]);
                    echo "<a href=\"$urlAnterior\" class=\"pag-btn pag-prev\">
                              <i class=\"fas fa-chevron-left\"></i> Anterior
                          </a>";
                } else {
                    echo "<button type=\"button\" class=\"pag-btn pag-prev\"
                                  onclick=\"cambiarPaginaAvanzada($pagAnterior)\">
                              <i class=\"fas fa-chevron-left\"></i> Anterior
                          </button>";
                }
            } else {
                echo '<span class="pag-btn pag-prev pag-disabled">
                          <i class="fas fa-chevron-left"></i> Anterior
                      </span>';
            }

            /* Páginas numeradas con elipsis */
            $anterior = null;
            foreach ($mostrar as $num) {
                if ($anterior !== null && $num - $anterior > 1) {
                    echo '<span class="pag-ellipsis">…</span>';
                }
                echo $btnPagina($num, $num === $pag);
                $anterior = $num;
            }

            /* Botón Siguiente */
            $pagSiguiente = $pag + 1;
            if ($pag < $paginas) {
                if ($modoOficina) {
                    $urlSiguiente = 'index.php?' . http_build_query([
                        'action'     => 'activos_oficina',
                        'id_oficina' => $resultado['id_oficina'] ?? 0,
                        'id_clase'   => $resultado['id_clase']   ?? 0,
                        'pagina'     => $pagSiguiente,
                    ]);
                    echo "<a href=\"$urlSiguiente\" class=\"pag-btn pag-next\">
                              Siguiente <i class=\"fas fa-chevron-right\"></i>
                          </a>";
                } elseif (!$modoAvanzado) {
                    $urlSiguiente = 'index.php?' . http_build_query([
                        'action' => 'search', 'q' => $termino, 'pagina' => $pagSiguiente
                    ]);
                    echo "<a href=\"$urlSiguiente\" class=\"pag-btn pag-next\">
                              Siguiente <i class=\"fas fa-chevron-right\"></i>
                          </a>";
                } else {
                    echo "<button type=\"button\" class=\"pag-btn pag-next\"
                                  onclick=\"cambiarPaginaAvanzada($pagSiguiente)\">
                              Siguiente <i class=\"fas fa-chevron-right\"></i>
                          </button>";
                }
            } else {
                echo '<span class="pag-btn pag-next pag-disabled">
                          Siguiente <i class="fas fa-chevron-right"></i>
                      </span>';
            }
            ?>

        </nav>

        <?php if ($modoAvanzado): ?>
        <!--
            Campo oculto dentro del formulario avanzado para la paginación POST.
            cambiarPaginaAvanzada() actualiza este campo y reenvía el formulario.
        -->
        <script>
        function cambiarPaginaAvanzada(pagina) {
            const form = document.getElementById('advancedForm');
            if (!form) return;
            /* Actualizar o crear el campo pagina oculto */
            let input = form.querySelector('input[name="pagina"]');
            if (!input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'pagina';
                form.appendChild(input);
            }
            input.value = pagina;
            form.submit();
        }
        </script>
        <?php endif; ?>

        <?php endif; // paginador ?>

        <?php endif; // resultados ?>

    </section>
</div>

<script src="public/js/search.js"></script>
<?php require VIEWS_PATH . '/partials/footer.php'; ?>
