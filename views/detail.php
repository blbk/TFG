<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : views/detail.php
 * Descripción   : Vista de detalle completo de un CI.
 *                 Incluye datos generales, red, extensión según
 *                 clase (PC/impresora/monitor), relaciones y,
 *                 si está disponible, datos de la oficina del ERP.
 * ========================================================= */

$pageTitle = $ci
    ? 'CI #' . $ci['id_ci'] . ' — ' . trim(($ci['marca'] ?? '') . ' ' . ($ci['modelo'] ?? ''))
    : 'Detalle CI';
require VIEWS_PATH . '/partials/header.php';

/*
 * Función helper: devuelve un icono FontAwesome según la clase del CI.
 * Se define con function_exists() para que sea seguro si la función
 * ya fue declarada en otra vista de la misma petición.
 */
if (!function_exists('iconoClase')) {
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
        return '<i class="fas fa-cube"></i>';
    }
}

/*
 * Carga de datos de oficina asociada al CI.
 * Se hace aquí en la vista (a través del modelo) para mantener el
 * controlador CI simple, ya que la oficina es un bloque opcional
 * y depende de que el CI tenga red configurada.
 *
 * Se captura la excepción de forma silenciosa: si la BD ERP no está
 * disponible, la vista simplemente no mostrará la sección de oficina.
 */
$oficina       = null;
$redOficina    = [];
$coordenadas   = null;
$consistencia  = null;   // resultado de verificarConsistenciaOficina()

if ($ci) {
    try {
        require_once BASE_PATH . '/models/OficinaModel.php';
        $oficModel    = new OficinaModel();
        $oficina      = $oficModel->findByIdCi((int)$ci['id_ci']);
        if ($oficina) {
            $redOficina   = $oficModel->getRedes((int)$oficina['id_oficina']);
            $coordenadas  = $oficModel->getCoordenadasCiudad($oficina['ciudad'] ?? '');
        }
        /*
         * La verificación de consistencia se lanza siempre que el CI
         * tenga número de serie, independientemente de si encontramos
         * la oficina por red. Puede darse el caso de que el CI esté
         * en el ERP pero en una oficina diferente a la de su red.
         */
        $consistencia = $oficModel->verificarConsistenciaOficina((int)$ci['id_ci']);
    } catch (PDOException $e) {
        $oficina      = null;
        $consistencia = null;
    }
}
?>

<div class="detail-page">

    <nav class="breadcrumb">
        <a href="index.php?action=search"><i class="fas fa-search"></i> Búsqueda</a>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <span>Detalle CI</span>
    </nav>

    <?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin:24px 32px;">
        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php elseif ($ci): ?>

    <div class="detail-header">
        <div class="detail-header-icon"><?= iconoClase($ci['clase']) ?></div>
        <div class="detail-header-info">
            <div class="detail-header-clase">
                <span class="badge-clase badge-clase--lg"><?= htmlspecialchars($ci['clase']) ?></span>
                <span class="detail-id">ID: <?= $ci['id_ci'] ?></span>
            </div>
            <h1 class="detail-title">
                <?= htmlspecialchars(trim(($ci['marca'] ?? '') . ' ' . ($ci['modelo'] ?? ''))) ?>
            </h1>
            <?php
            $nombreLocal = $ci['detalle_pc']['nombre_local']
                        ?? $ci['detalle_impresora']['nombre_local']
                        ?? $ci['red']['hostname']
                        ?? null;
            if ($nombreLocal): ?>
            <div class="detail-subtitle"><?= htmlspecialchars($nombreLocal) ?></div>
            <?php endif; ?>
        </div>
        <div class="detail-header-actions">
            <a href="index.php?action=search" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
            <button onclick="window.print()" class="btn-secondary">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>

    <div class="detail-sections">

        <!-- Datos generales -->
        <div class="detail-card">
            <div class="detail-card-header"><i class="fas fa-info-circle"></i> Datos generales</div>
            <div class="detail-card-body">
                <table class="detail-table">
                    <tr><th><i class="fas fa-hashtag"></i> ID del CI</th><td><?= $ci['id_ci'] ?></td></tr>
                    <tr><th><i class="fas fa-tag"></i> Clase</th>
                        <td><span class="badge-clase"><?= htmlspecialchars($ci['clase']) ?></span></td></tr>
                    <tr><th><i class="fas fa-industry"></i> Marca</th>
                        <td><?= htmlspecialchars($ci['marca'] ?? '—') ?></td></tr>
                    <tr><th><i class="fas fa-box"></i> Modelo</th>
                        <td><?= htmlspecialchars($ci['modelo'] ?? '—') ?></td></tr>
                    <tr><th><i class="fas fa-barcode"></i> Número de serie</th>
                        <td><?= htmlspecialchars($ci['numero_serie'] ?? '—') ?></td></tr>
                    <tr><th><i class="fas fa-calendar-alt"></i> Fecha de alta</th>
                        <td><?= $ci['fecha'] ? date('d/m/Y', strtotime($ci['fecha'])) : '—' ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Red -->
        <?php if (!empty($ci['red'])): $red = $ci['red']; ?>
        <div class="detail-card">
            <div class="detail-card-header"><i class="fas fa-network-wired"></i> Configuración de red</div>
            <div class="detail-card-body">
                <table class="detail-table">
                    <tr><th><i class="fas fa-globe"></i> Dirección IP</th>
                        <td class="mono"><?= htmlspecialchars($red['direccion_ip'] ?? '—') ?></td></tr>
                    <tr><th><i class="fas fa-ethernet"></i> Dirección MAC</th>
                        <td class="mono"><?= htmlspecialchars($red['direccion_mac'] ?? '—') ?></td></tr>
                    <tr><th><i class="fas fa-server"></i> Hostname</th>
                        <td><?= htmlspecialchars($red['hostname'] ?? '—') ?></td></tr>
                    <tr><th><i class="fas fa-route"></i> Gateway</th>
                        <td class="mono"><?= htmlspecialchars($red['gateway'] ?? '—') ?></td></tr>
                    <?php if (!empty($red['info_red'])): $r = $red['info_red']; ?>
                    <tr><th><i class="fas fa-sitemap"></i> CIDR</th>
                        <td class="mono"><?= htmlspecialchars($r['cidr'] ?? '—') ?></td></tr>
                    <tr><th><i class="fas fa-layer-group"></i> VLAN</th>
                        <td><?= htmlspecialchars($r['vlan'] ?? '—') ?></td></tr>
                    <?php if (!empty($r['descripcion'])): ?>
                    <tr><th><i class="fas fa-comment"></i> Descripción red</th>
                        <td><?= htmlspecialchars($r['descripcion']) ?></td></tr>
                    <?php endif; ?>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- PC -->
        <?php if (!empty($ci['detalle_pc'])): $pc = $ci['detalle_pc'];

        /* ============================================================
           CÁLCULO DE VERIFICACIONES
           Se calculan aquí, antes del HTML, para mantener la vista limpia.
           Cada verificación produce un array con:
             'ok'      bool|null  — true=ok, false=error, null=sin datos
             'icono'   string     — HTML del badge (tick/aspa)
             'detalle' string     — texto adicional en el tooltip
        ============================================================ */

        /*
         * Helper local: genera el HTML del badge de verificación.
         *   $ok    = true  → tick verde
         *   $ok    = false → aspa roja
         *   $ok    = null  → sin badge (sin datos suficientes)
         *   $titulo        → texto del atributo title (tooltip)
         */
        $badge = function(?bool $ok, string $titulo): string {
            if ($ok === null) return '';
            if ($ok) {
                return "<span class=\"verif verif--ok\" title=\"$titulo\">"
                     . "<i class=\"fas fa-check-circle\"></i></span>";
            }
            return "<span class=\"verif verif--error\" title=\"$titulo\">"
                 . "<i class=\"fas fa-times-circle\"></i></span>";
        };

        /* ── 1. Disco libre < 10% del total ─────────────────────────
         * Sólo verificamos si tenemos ambos valores y el total > 0.
         * Porcentaje libre = (libre / total) * 100
         */
        $discoOk     = null;
        $discoTitulo = '';
        $discoPct    = null;
        if (!is_null($pc['disco_total']) && (float)$pc['disco_total'] > 0) {
            $discoPct    = ((float)$pc['disco_libre'] / (float)$pc['disco_total']) * 100;
            $discoOk     = $discoPct >= 10;
            $pctFmt      = number_format($discoPct, 1);
            $discoTitulo = $discoOk
                ? "Espacio libre: {$pctFmt}% — dentro del umbral (≥ 10%)"
                : "Espacio libre: {$pctFmt}% — CRÍTICO: queda menos del 10% del disco";
        }

        /* ── 2. Actualización de antivirus hace más de un mes ────────
         * Comparamos fecha_antivirus con la fecha actual.
         * «Hace más de un mes» = más de 30 días de diferencia.
         */
        $avOk     = null;
        $avTitulo = '';
        if (!empty($pc['fecha_antivirus'])) {
            $diasAv   = (int)floor(
                (time() - strtotime($pc['fecha_antivirus'])) / 86400
            );
            $avOk     = $diasAv <= 30;
            $avTitulo = $avOk
                ? "Actualizado hace {$diasAv} días — dentro del plazo (≤ 30 días)"
                : "Actualizado hace {$diasAv} días — DESACTUALIZADO: más de 30 días sin actualizar";
        }

        /* ── 3. Chrome < 136.0 ───────────────────────────────────────
         * Extraemos el número mayor de versión (antes del primer punto).
         * Chrome usa versiones del tipo 136.0.7103.49
         * Umbral mínimo: 136
         */
        $chromeOk     = null;
        $chromeTitulo = '';
        if (!empty($pc['version_chrome'])) {
            $chromeMayor  = (int)explode('.', $pc['version_chrome'])[0];
            $chromeOk     = $chromeMayor >= 136;
            $chromeTitulo = $chromeOk
                ? "Chrome {$pc['version_chrome']} — versión actualizada (≥ 136.0)"
                : "Chrome {$pc['version_chrome']} — DESACTUALIZADO: se requiere ≥ 136.0";
        }

        /* ── 4. Edge < 135.0 ─────────────────────────────────────────
         * Misma lógica que Chrome. Edge usa el mismo esquema de versiones.
         * Umbral mínimo: 135
         */
        $edgeOk     = null;
        $edgeTitulo = '';
        if (!empty($pc['version_edge'])) {
            $edgeMayor  = (int)explode('.', $pc['version_edge'])[0];
            $edgeOk     = $edgeMayor >= 135;
            $edgeTitulo = $edgeOk
                ? "Edge {$pc['version_edge']} — versión actualizada (≥ 135.0)"
                : "Edge {$pc['version_edge']} — DESACTUALIZADO: se requiere ≥ 135.0";
        }

        ?>
        <div class="detail-card">
            <div class="detail-card-header"><i class="fas fa-desktop"></i> Datos del equipo</div>
            <div class="detail-card-body">
                <table class="detail-table">

                    <tr><th><i class="fas fa-tag"></i> Nombre local</th>
                        <td><?= htmlspecialchars($pc['nombre_local'] ?? '—') ?></td></tr>

                    <tr><th><i class="fas fa-windows"></i> Sistema operativo</th>
                        <td><?= htmlspecialchars($pc['sistema_operativo'] ?? '—') ?></td></tr>

                    <tr><th><i class="fas fa-code-branch"></i> Versión SO</th>
                        <td><?= htmlspecialchars($pc['version_so'] ?? '—') ?></td></tr>

                    <tr><th><i class="fas fa-microchip"></i> Arquitectura</th>
                        <td><?= htmlspecialchars($pc['arquitectura'] ?? '—') ?></td></tr>

                    <tr><th><i class="fas fa-memory"></i> RAM</th>
                        <td><?= !is_null($pc['memoria']) ? number_format($pc['memoria'], 2).' GB' : '—' ?></td></tr>

                    <!-- Disco: badge rojo si libre < 10% del total -->
                    <tr <?= $discoOk === false ? 'class="fila-alerta"' : '' ?>>
                        <th><i class="fas fa-hdd"></i> Disco total / libre</th>
                        <td>
                            <?php if (!is_null($pc['disco_total'])): ?>
                                <?= $pc['disco_total'] ?> GB /
                                <?= $pc['disco_libre'] ?> GB libres
                                <?php if ($discoPct !== null): ?>
                                <span class="disco-pct <?= $discoOk ? 'disco-pct--ok' : 'disco-pct--error' ?>">
                                    (<?= number_format($discoPct, 1) ?>%)
                                </span>
                                <?php endif; ?>
                                <?= $badge($discoOk, $discoTitulo) ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>

                    <tr><th><i class="fas fa-building"></i> Dominio</th>
                        <td><?= htmlspecialchars($pc['dominio'] ?? '—') ?></td></tr>

                    <tr><th><i class="fas fa-user"></i> Último usuario</th>
                        <td>
                            <?php if (!empty($pc['login'])): ?>
                            <a href="index.php?action=usuario&login=<?= urlencode($pc['login']) ?>"
                               class="link-usuario"
                               title="Ver ficha del usuario">
                                <i class="fas fa-id-card"></i>
                                <?= htmlspecialchars($pc['login']) ?>
                            </a>
                            <?php else: echo '—'; endif; ?>
                        </td></tr>

                    <tr><th><i class="fas fa-calendar-check"></i> Fecha último login</th>
                        <td><?= $pc['fecha_login'] ? date('d/m/Y', strtotime($pc['fecha_login'])) : '—' ?></td></tr>

                    <tr><th><i class="fas fa-power-off"></i> Último arranque</th>
                        <td><?= $pc['fecha_boot'] ? date('d/m/Y', strtotime($pc['fecha_boot'])) : '—' ?></td></tr>

                    <tr><th><i class="fas fa-shield-alt"></i> Antivirus</th>
                        <td>
                            <?php if ($pc['antivirus']): ?>
                                <?= htmlspecialchars($pc['antivirus']) ?>
                                <?php
                                $e   = strtolower($pc['estado_antivirus'] ?? '');
                                $cls = (str_contains($e, 'activo') || str_contains($e, 'ok'))
                                    ? 'badge--ok' : 'badge--warn';
                                ?>
                                <span class="badge-estado <?= $cls ?>">
                                    <?= htmlspecialchars($pc['estado_antivirus'] ?? '') ?>
                                </span>
                            <?php else: echo '—'; endif; ?>
                        </td>
                    </tr>

                    <!-- Actualización antivirus: badge rojo si > 30 días -->
                    <tr <?= $avOk === false ? 'class="fila-alerta"' : '' ?>>
                        <th><i class="fas fa-calendar-times"></i> Act. antivirus</th>
                        <td>
                            <?= $pc['fecha_antivirus'] ? date('d/m/Y', strtotime($pc['fecha_antivirus'])) : '—' ?>
                            <?= $badge($avOk, $avTitulo) ?>
                        </td>
                    </tr>

                    <!-- Chrome: badge rojo si versión mayor < 136 -->
                    <?php if (!empty($pc['version_chrome'])): ?>
                    <tr <?= $chromeOk === false ? 'class="fila-alerta"' : '' ?>>
                        <th><i class="fab fa-chrome"></i> Chrome</th>
                        <td>
                            <?= htmlspecialchars($pc['version_chrome']) ?>
                            <?= $badge($chromeOk, $chromeTitulo) ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <!-- Edge: badge rojo si versión mayor < 135 -->
                    <?php if (!empty($pc['version_edge'])): ?>
                    <tr <?= $edgeOk === false ? 'class="fila-alerta"' : '' ?>>
                        <th><i class="fab fa-edge"></i> Edge</th>
                        <td>
                            <?= htmlspecialchars($pc['version_edge']) ?>
                            <?= $badge($edgeOk, $edgeTitulo) ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Impresora -->
        <?php if (!empty($ci['detalle_impresora'])): $imp = $ci['detalle_impresora']; ?>
        <div class="detail-card">
            <div class="detail-card-header"><i class="fas fa-print"></i> Datos de la impresora</div>
            <div class="detail-card-body">
                <table class="detail-table">
                    <tr><th><i class="fas fa-tag"></i> Nombre local</th>
                        <td><?= htmlspecialchars($imp['nombre_local'] ?? '—') ?></td></tr>
                    <tr><th><i class="fas fa-cogs"></i> Driver</th>
                        <td><?= htmlspecialchars($imp['driver'] ?? '—') ?></td></tr>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Monitor -->
        <?php if (!empty($ci['detalle_monitor'])): $mon = $ci['detalle_monitor']; ?>
        <div class="detail-card">
            <div class="detail-card-header"><i class="fas fa-tv"></i> Datos del monitor</div>
            <div class="detail-card-body">
                <table class="detail-table">
                    <tr><th><i class="fas fa-expand-arrows-alt"></i> Tamaño</th>
                        <td><?= $mon['pulgadas'] ? $mon['pulgadas'].'"' : '—' ?></td></tr>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Relaciones -->
        <?php if (!empty($ci['relaciones'])): ?>
        <div class="detail-card detail-card--full">
            <div class="detail-card-header"><i class="fas fa-project-diagram"></i> Relaciones con otros CI</div>
            <div class="detail-card-body">
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Relación</th><th>CI Origen</th><th>Clase</th>
                            <th>CI Destino</th><th>Clase</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ci['relaciones'] as $rel): ?>
                        <tr>
                            <td><span class="badge-relacion"><?= htmlspecialchars($rel['tipo_relacion']) ?></span></td>
                            <td><strong>#<?= $rel['id_ci_origen'] ?></strong>
                                — <?= htmlspecialchars($rel['marca_origen'].' '.$rel['modelo_origen']) ?></td>
                            <td><span class="badge-clase"><?= htmlspecialchars($rel['clase_origen']) ?></span></td>
                            <td><strong>#<?= $rel['id_ci_destino'] ?></strong>
                                — <?= htmlspecialchars($rel['marca_destino'].' '.$rel['modelo_destino']) ?></td>
                            <td><span class="badge-clase"><?= htmlspecialchars($rel['clase_destino']) ?></span></td>
                            <td>
                                <?php $rid = ($rel['id_ci_origen'] == $ci['id_ci'])
                                    ? $rel['id_ci_destino'] : $rel['id_ci_origen']; ?>
                                <a href="index.php?action=detail&id=<?= $rid ?>" class="btn-detail">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; // relaciones ?>

        <!-- ══════════════════════════════════════════════════════
             SECCIÓN: OFICINA ASOCIADA AL CI
             Se muestra si el CI tiene red asignada y esa red
             pertenece a una oficina registrada en el ERP.
             El enlace "Ver ficha completa" lleva a oficina.php.
        ══════════════════════════════════════════════════════ -->
        <?php if (!empty($oficina)): ?>
        <div class="detail-card detail-card--full detail-card--oficina">
            <div class="detail-card-header">
                <i class="fas fa-building"></i> Oficina donde está ubicado el CI
                <a href="index.php?action=oficina&id=<?= $oficina['id_oficina'] ?>"
                   class="btn-detail btn-detail--header" title="Ver ficha completa de la oficina">
                    <i class="fas fa-external-link-alt"></i> Ver ficha completa
                </a>
            </div>
            <div class="detail-card-body detail-oficina-body">

                <!-- Mini tabla con datos clave de la oficina -->
                <table class="detail-table detail-table--oficina">
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID oficina</th>
                        <td>
                            <strong>#<?= $oficina['id_oficina'] ?></strong>
                            (<?= htmlspecialchars($oficina['cod_oficina']) ?>)
                            <?php
                            /* Indicador de la fuente del dato de ubicación */
                            if (($oficina['fuente'] ?? '') === 'erp'): ?>
                            <span class="badge-fuente badge-fuente--erp"
                                  title="Oficina obtenida del ERP por número de serie del activo">
                                <i class="fas fa-database"></i> ERP
                            </span>
                            <?php elseif (($oficina['fuente'] ?? '') === 'red'): ?>
                            <span class="badge-fuente badge-fuente--red"
                                  title="Oficina deducida por el gateway de red (fallback: el CI no está en el ERP)">
                                <i class="fas fa-network-wired"></i> Red
                            </span>
                            <?php endif; ?>
                            <?php
                            /*
                             * Señal de consistencia entre la oficina deducida
                             * por la red (CMDB) y la registrada en el ERP.
                             *
                             *  ✅ ok        — ambas fuentes coinciden
                             *  ❌ error     — las fuentes apuntan a oficinas distintas
                             *  (sin señal)  — sin_datos: no hay suficiente info para comparar
                             */
                            if ($consistencia && $consistencia['estado'] !== 'sin_datos'):
                                if ($consistencia['estado'] === 'ok'): ?>
                            <span class="badge-consistencia badge-consistencia--ok"
                                  title="<?= htmlspecialchars($consistencia['mensaje']) ?>">
                                <i class="fas fa-check-circle"></i> Consistente con ERP
                            </span>
                            <?php   else: // error ?>
                            <span class="badge-consistencia badge-consistencia--error"
                                  title="<?= htmlspecialchars($consistencia['mensaje']) ?>">
                                <i class="fas fa-times-circle"></i> Discrepancia con ERP
                            </span>
                            <?php   endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-building"></i> Nombre</th>
                        <td><?= htmlspecialchars($oficina['nombre_oficina']) ?></td>
                    </tr>
                    <?php
                    /*
                     * Si hay discrepancia, mostrar fila extra con la oficina
                     * que el ERP tiene registrada para este CI (por número de serie),
                     * con enlace directo a su ficha para facilitar la corrección.
                     */
                    if ($consistencia && $consistencia['estado'] === 'error'): ?>
                    <tr class="fila-discrepancia">
                        <th><i class="fas fa-exclamation-triangle"></i> Oficina en ERP</th>
                        <td>
                            <a href="index.php?action=oficina&id=<?= $consistencia['id_erp'] ?>"
                               class="btn-detail btn-detail--warn">
                                <i class="fas fa-building"></i>
                                #<?= $consistencia['id_erp'] ?> —
                                <?= htmlspecialchars($consistencia['nombre_erp']) ?>
                            </a>
                            <span class="discrepancia-hint">
                                La red apunta a
                                <strong>#<?= $consistencia['id_red'] ?></strong>
                                pero el ERP registra
                                <strong>#<?= $consistencia['id_erp'] ?></strong>
                            </span>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th><i class="fas fa-sitemap"></i> Unidad orgánica</th>
                        <td><?= htmlspecialchars($oficina['unidad_organica']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-map-marker-alt"></i> Dirección</th>
                        <td><?= htmlspecialchars($oficina['direccion']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-city"></i> Ciudad</th>
                        <td><?= htmlspecialchars($oficina['ciudad']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-mail-bulk"></i> CP</th>
                        <td><?= htmlspecialchars($oficina['cp']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-globe"></i> País</th>
                        <td><?= htmlspecialchars($oficina['pais']) ?>
                            <span class="cod-pais">(<?= htmlspecialchars($oficina['cod_pais']) ?>)</span>
                        </td>
                    </tr>
                    <?php if (!empty($redOficina)): ?>
                    <tr>
                        <th><i class="fas fa-network-wired"></i> Redes</th>
                        <td>
                            <?php foreach ($redOficina as $red): ?>
                            <span class="badge-vlan" title="<?= htmlspecialchars($red['descripcion'] ?? '') ?>">
                                VLAN <?= $red['vlan'] ?> — <?= htmlspecialchars($red['cidr']) ?>
                            </span>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>

                <!-- Mini mapa Leaflet centrado en la ciudad de la oficina -->
                <?php if ($coordenadas): ?>
                <div id="mapaOficinaDetalle" class="mapa-mini"
                     data-lat="<?= $coordenadas['lat'] ?>"
                     data-lng="<?= $coordenadas['lng'] ?>"
                     data-zoom="<?= $coordenadas['zoom'] ?>"
                     data-label="<?= htmlspecialchars($oficina['nombre_oficina'] . ' — ' . $oficina['direccion']) ?>">
                </div>
                <?php endif; ?>

            </div><!-- /.detail-oficina-body -->
        </div>
        <?php endif; // oficina ?>

    </div><!-- /.detail-sections -->
    <?php endif; // ci ?>
</div><!-- /.detail-page -->

<?php
/*
 * Carga condicional de Leaflet: solo si hay datos de oficina con
 * coordenadas. Se carga al final del body para no bloquear el render.
 */
if (!empty($oficina) && !empty($coordenadas)):
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/*
 * Inicializa el mini mapa en la sección de oficina del detalle del CI.
 * Lee las coordenadas desde los atributos data-* del contenedor para
 * mantener la lógica de PHP separada del JavaScript.
 */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('mapaOficinaDetalle');
    if (!el) return;

    const lat   = parseFloat(el.dataset.lat);
    const lng   = parseFloat(el.dataset.lng);
    const zoom  = parseInt(el.dataset.zoom, 10);
    const label = el.dataset.label;

    // Crear mapa con tiles de OpenStreetMap (sin API key, software libre)
    const map = L.map('mapaOficinaDetalle', { zoomControl: true, scrollWheelZoom: false })
                 .setView([lat, lng], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    // Marcador con el nombre y dirección de la oficina
    L.marker([lat, lng])
     .addTo(map)
     .bindPopup(`<strong>${label}</strong>`)
     .openPopup();
});
</script>
<?php endif; ?>

<?php require VIEWS_PATH . '/partials/footer.php'; ?>
