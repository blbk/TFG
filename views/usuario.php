<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : views/usuario.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción   : Ficha de usuario (datos ITSM). Se accede pulsando
 *                 sobre el login del "Último usuario" en la sección
 *                 "Datos del equipo" del detalle de un CI.
 *
 * Variables recibidas desde UsuarioController::showUsuario():
 *   $login    — login solicitado (string, siempre presente)
 *   $usuario  — array [login, nomape, tlf_movil, foto] o null si no existe
 *   $rutaFoto — ruta relativa a la imagen de perfil (con fallback)
 *   $error    — string|null
 * ========================================================= */

$pageTitle = $usuario
    ? ($usuario['nomape'] ?: $usuario['login']) . ' — Ficha de usuario'
    : 'Ficha de usuario';

require VIEWS_PATH . '/partials/header.php';

// Ruta de la imagen genérica, usada como fallback si la foto específica
// del usuario no existe físicamente en el repositorio de imágenes.
$rutaFotoGenerica = 'public/img/usuarios/0.jpg';
?>

<div class="detail-page usuario-page">

    <!-- Migas de pan (breadcrumb): volver a la pantalla anterior (detalle del CI) -->
    <nav class="breadcrumb">
        <a href="javascript:history.back()"><i class="fas fa-arrow-left"></i> Volver</a>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <a href="index.php?action=search"><i class="fas fa-search"></i> Búsqueda</a>
        <span class="bc-sep"><i class="fas fa-chevron-right"></i></span>
        <span>Ficha de usuario</span>
    </nav>

    <?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin:24px 32px;">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($error) ?>
        <?php if (!empty($login)): ?>
        <span class="alert-login-hint">(login solicitado: <strong><?= htmlspecialchars($login) ?></strong>)</span>
        <?php endif; ?>
    </div>

    <?php elseif ($usuario): ?>

    <!-- ══════════════════════════════════════════════════════════
         CABECERA DEL USUARIO: foto + nombre + login
    ══════════════════════════════════════════════════════════ -->
    <div class="detail-header usuario-header">

        <!-- Foto de perfil. Si {foto}.jpg no existe físicamente,
             onerror carga la imagen genérica 0.jpg como fallback. -->
        <div class="usuario-foto-wrap">
            <img src="<?= htmlspecialchars($rutaFoto) ?>"
                 alt="Foto de <?= htmlspecialchars($usuario['nomape'] ?: $usuario['login']) ?>"
                 class="usuario-foto"
                 onerror="this.onerror=null; this.src='<?= htmlspecialchars($rutaFotoGenerica) ?>';">
        </div>

        <div class="detail-header-info">
            <div class="detail-header-clase">
                <span class="badge-clase badge-clase--lg">
                    <i class="fas fa-id-badge"></i> Usuario
                </span>
                <span class="detail-id">
                    <i class="fas fa-key"></i> <?= htmlspecialchars($usuario['login']) ?>
                </span>
            </div>
            <h1 class="detail-title">
                <?= htmlspecialchars($usuario['nomape'] ?: '(Sin nombre registrado)') ?>
            </h1>
            <?php if (!empty($usuario['tlf_movil'])): ?>
            <div class="detail-subtitle">
                <i class="fas fa-phone"></i>
                <a href="tel:<?= htmlspecialchars($usuario['tlf_movil']) ?>" class="usuario-tlf-link">
                    <?= htmlspecialchars($usuario['tlf_movil']) ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="detail-header-actions">
            <a href="javascript:history.back()" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         SECCIONES: datos de contacto + bloques pendientes
    ══════════════════════════════════════════════════════════ -->
    <div class="detail-sections">

        <!-- Datos de contacto -->
        <div class="detail-card">
            <div class="detail-card-header">
                <i class="fas fa-address-card"></i> Datos de contacto
            </div>
            <div class="detail-card-body">
                <table class="detail-table">
                    <tr>
                        <th><i class="fas fa-key"></i> Login</th>
                        <td class="mono"><?= htmlspecialchars($usuario['login']) ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-id-badge"></i> Nombre y apellidos</th>
                        <td><?= htmlspecialchars($usuario['nomape'] ?: '—') ?></td>
                    </tr>
                    <tr>
                        <th><i class="fas fa-phone"></i> Teléfono de contacto</th>
                        <td>
                            <?php if (!empty($usuario['tlf_movil'])): ?>
                            <a href="tel:<?= htmlspecialchars($usuario['tlf_movil']) ?>" class="usuario-tlf-link">
                                <?= htmlspecialchars($usuario['tlf_movil']) ?>
                            </a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- ──────────────────────────────────────────────────────
             BLOQUE PENDIENTE: Licencias de software
             Reservado para una futura integración con el catálogo
             de licencias de la organización.
        ────────────────────────────────────────────────────── -->
        <div class="detail-card detail-card--pendiente">
            <div class="detail-card-header">
                <i class="fas fa-file-contract"></i> Licencias de software asignadas
            </div>
            <div class="detail-card-body">
                <div class="pendiente-placeholder">
                    <i class="fas fa-tools"></i>
                    <p>Pendiente de integración con el catálogo de licencias de software.</p>
                    <p class="pendiente-hint">
                        Aquí se mostrarán las licencias de software asignadas a
                        <?= htmlspecialchars($login) ?>: aplicaciones, versiones,
                        fechas de vencimiento y estado de cada licencia.
                    </p>
                </div>
            </div>
        </div>

        <!-- ──────────────────────────────────────────────────────
             BLOQUE: Equipos donde el usuario ha hecho login
             Datos obtenidos de la tabla pc (campo login).
        ────────────────────────────────────────────────────── -->
        <div class="detail-card detail-card--full">
            <div class="detail-card-header">
                <i class="fas fa-desktop"></i> Equipos con login registrado
                <?php if (!empty($equipos)): ?>
                <span class="header-total">Total: <?= count($equipos) ?></span>
                <?php endif; ?>
            </div>
            <div class="detail-card-body">
                <?php if (empty($equipos)): ?>
                <div class="pendiente-placeholder">
                    <i class="fas fa-desktop" style="color:var(--gris-3);font-size:2rem;margin-bottom:8px;"></i>
                    <p>No se encontraron equipos con el login <strong><?= htmlspecialchars($login) ?></strong> registrado como último usuario.</p>
                </div>
                <?php else: ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Clase</th>
                            <th>Marca / Modelo</th>
                            <th>Nombre local</th>
                            <th>Hostname</th>
                            <th>IP</th>
                            <th>Sistema operativo</th>
                            <th>Último login</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipos as $eq): ?>
                        <tr>
                            <td class="ci-id">#<?= $eq['id_ci'] ?></td>
                            <td><span class="badge-clase"><?= htmlspecialchars($eq['clase']) ?></span></td>
                            <td><?= htmlspecialchars(trim(($eq['marca'] ?? '') . ' ' . ($eq['modelo'] ?? ''))) ?></td>
                            <td><?= htmlspecialchars($eq['nombre_local'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($eq['hostname'] ?? '—') ?></td>
                            <td class="mono"><?= htmlspecialchars($eq['direccion_ip'] ?? '—') ?></td>
                            <td><?= htmlspecialchars(trim(($eq['sistema_operativo'] ?? '') . ' ' . ($eq['version_so'] ?? ''))) ?: '—' ?></td>
                            <td><?= $eq['fecha_login'] ? date('d/m/Y', strtotime($eq['fecha_login'])) : '—' ?></td>
                            <td>
                                <a href="index.php?action=detail&id=<?= $eq['id_ci'] ?>"
                                   class="btn-detail">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- ──────────────────────────────────────────────────────
             BLOQUE PENDIENTE: Grupos de Directorio Activo
             Reservado para una futura integración con el DA.
             Por ahora se muestra vacío con un mensaje informativo.
        ────────────────────────────────────────────────────── -->
        <div class="detail-card detail-card--pendiente">
            <div class="detail-card-header">
                <i class="fas fa-users-cog"></i> Grupos de Directorio Activo
            </div>
            <div class="detail-card-body">
                <div class="pendiente-placeholder">
                    <i class="fas fa-tools"></i>
                    <p>Pendiente de integración con el Directorio Activo.</p>
                    <p class="pendiente-hint">
                        Aquí se mostrarán los grupos de seguridad y distribución
                        a los que pertenece <?= htmlspecialchars($usuario['login']) ?>.
                    </p>
                </div>
            </div>
        </div>

        <!-- ──────────────────────────────────────────────────────
             BLOQUE PENDIENTE: Incidencias / Tickets ITSM
             Reservado para una futura integración con el sistema
             de gestión de incidencias.
        ────────────────────────────────────────────────────── -->
        <div class="detail-card detail-card--pendiente detail-card--full">
            <div class="detail-card-header">
                <i class="fas fa-ticket-alt"></i> Incidencias
            </div>
            <div class="detail-card-body">
                <div class="pendiente-placeholder">
                    <i class="fas fa-tools"></i>
                    <p>Pendiente de integración con el sistema de gestión de incidencias (ITSM).</p>
                    <p class="pendiente-hint">
                        Aquí se mostrará el histórico de tickets abiertos, en curso
                        y resueltos asociados a <?= htmlspecialchars($usuario['login']) ?>.
                    </p>
                </div>
            </div>
        </div>

    </div><!-- /.detail-sections -->

    <?php endif; ?>
</div><!-- /.usuario-page -->

<?php require VIEWS_PATH . '/partials/footer.php'; ?>
