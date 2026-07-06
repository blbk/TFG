<?php
/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : views/login.php
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción   : Pantalla de Login para el sistema de gestión CMDB. 
 *                 Solicita usuario y contraseña.
 *
 * Variables recibidas desde UsuarioController::showUsuario():
 *   $login    — login solicitado (string, siempre presente)
 *   $usuario  — array [login, nomape, tlf_movil, foto] o null si no existe
 *   $rutaFoto — ruta relativa a la imagen de perfil (con fallback)
 *   $error    — string|null
 * ========================================================= */

$expired = !empty($_GET['expired']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMDB — Inicio de Sesión</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/estilos_login.css">
</head>
<body>

<div class="login-container">

    <div class="panel-izq">
        <div class="logo-container">
            <img src="public/img/logo_cmdb.svg" alt="Logo CMDB" class="logo-unir">
        </div>
        <h1>CMDB</h1>
        <p>Gestión de Configuración y Activos</p>
        <div class="txt-tfg">Trabajo de Fin de Grado</div>
    </div>

    <div class="panel-dch">
        <h2>Bienvenido <span>| Acceso</span></h2>

        <?php if ($expired): ?>
        <div class="alert alert-warning">
            <i class="fas fa-clock"></i> La sesión ha expirado. Por favor, vuelve a iniciar sesión.
        </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="index.php?action=login" novalidate>

            <div class="form-group">
                <i class="far fa-user"></i>
                <input type="text" id="usuario" name="usuario" class="form-control"
                       placeholder="Nombre de usuario"
                       value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                       required autocomplete="username">
            </div>

            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" class="form-control"
                       placeholder="Contraseña" required autocomplete="current-password">
                <button type="button" class="toggle-pwd" id="togglePwd" title="Mostrar/ocultar">
                    <i class="fas fa-eye" id="eyeIcon"></i>
                </button>
            </div>

            <div class="form-options">
                <label class="checkbox-container">
                    <input type="checkbox" id="rememberMe" name="remember">
                    Recuérdame
                </label>
                <a href="#" class="enlace-azul" id="forgotLink">Olvidé mi contraseña</a>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-sign-in-alt"></i> INICIAR SESIÓN
            </button>

            <div class="signup-text">
                ¿No tienes cuenta? <a href="#" class="enlace-azul">Solicitud de acceso</a>
            </div>

        </form>
    </div>

</div>

<footer>
    <p>Javier Moyano Vizcaíno.</p>
    <p>
        <a href="https://www.unir.net/" target="_blank">
            Grado en Ingeniería Informática &mdash;
            Universidad Internacional de La Rioja &mdash;
            Escuela Superior de Ingeniería y Tecnología
        </a>
    </p>
</footer>

<script src="public/js/login.js"></script>
</body>
</html>
