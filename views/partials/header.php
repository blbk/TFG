<?php $usuario_h = $_SESSION['usuario'] ?? null; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'CMDB') ?> — CMDB</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/app.css">
</head>
<body>
<nav class="navbar">
    <div class="navbar-brand">
        <!-- <span class="navbar-logo"><i class="fas fa-network-wired"></i></span> -->
         <img src="public/img/cubo_q.svg" alt="Logo CMDB" class="logo-header">
        <span class="navbar-title">CMDB</span>
        <span class="navbar-sub">Gestión de Configuración</span>
    </div>
    <?php if ($usuario_h): ?>
    <div class="navbar-user">
        <!-- Enlace rápido a búsqueda de oficinas -->
        <a href="index.php?action=buscar_oficinas" class="navbar-link">
            <i class="fas fa-building"></i>
            <span>Oficinas</span>
        </a>
        <div class="user-info">
            <span class="user-name">
                <i class="fas fa-user-circle"></i>
                <?= htmlspecialchars($usuario_h['nombre'] . ' ' . $usuario_h['apellidos']) ?>
            </span>
            <span class="user-badge"><?= htmlspecialchars($usuario_h ['perfil'] ?? 'Usuario') ?></span>
        </div>
        <a href="index.php?action=logout" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Salir
        </a>
    </div>
    <?php endif; ?>
</nav>
<main class="main-content">
