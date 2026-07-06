/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : public/js/login.js
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 * Descripción   : Validación y UX del formulario de login
 * ========================================================= */

document.addEventListener('DOMContentLoaded', () => {

    // mostrar/ocultar contraseña
    const togglePwd = document.getElementById('togglePwd');
    const pwdInput  = document.getElementById('password');
    const eyeIcon   = document.getElementById('eyeIcon');

    if (togglePwd && pwdInput) {
        togglePwd.addEventListener('click', () => {
            const shown = pwdInput.type === 'text';
            pwdInput.type = shown ? 'password' : 'text';
            eyeIcon.className = shown ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
    }

    // Validación del formulario antes de enviar
    const form = document.getElementById('loginForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            const usuario  = document.getElementById('usuario').value.trim();
            const password = document.getElementById('password').value;

            if (!usuario || !password) {
                e.preventDefault();
                mostrarError('Por favor, rellena todos los campos.');
                return;
            }

            // Deshabilitar botón para evitar doble envío
            const btn = form.querySelector('.btn-submit');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando…';
            }
        });
    }

    // Autoenfoque en el campo usuario si está vacío
    const usuarioInput = document.getElementById('usuario');
    if (usuarioInput && !usuarioInput.value) {
        usuarioInput.focus();
    } else if (usuarioInput && usuarioInput.value) {
        document.getElementById('password')?.focus();
    }

    function mostrarError(msg) {
        // Eliminar alerta previa si la hay
        const prevAlert = document.querySelector('.alert-js');
        if (prevAlert) prevAlert.remove();

        const alert = document.createElement('div');
        alert.className = 'alert alert-error alert-js';
        alert.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${msg}`;
        form.prepend(alert);

        // Vibración en inputs
        form.querySelectorAll('.form-control').forEach(el => {
            el.style.borderColor = 'var(--rojo-error)';
            setTimeout(() => el.style.borderColor = '', 2000);
        });
    }

    // Enlace "Olvidé mi contraseña"
    const forgotLink = document.getElementById('forgotLink');
    if (forgotLink) {
        forgotLink.addEventListener('click', (e) => {
            e.preventDefault();
            alert('Para recuperar tu contraseña, contacta con el administrador de sistemas.');
        });
    }
});
