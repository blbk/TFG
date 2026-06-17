/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : valida_login.js
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción:
 * Valida el formulario de inicio de sesión, asegurando que
 * los campos de usuario y contraseña cumplan con los
 * requisitos mínimos antes de enviar la información al
 * servidor para su autenticación.
 * ========================================================= 
 */
 
document
    .getElementById('loginForm')
    .addEventListener('submit', async function (event) {

    // Evita recargar la página
    event.preventDefault();

    // Obtener valores
    const usuario = document.getElementById('usuario').value.trim();
    const password = document.getElementById('password').value.trim();

    const mensaje = document.getElementById('mensaje');

    // Limpiar mensaje
    mensaje.textContent = '';

    // ===== VALIDACIONES =====
    // Se verifica que haya usuario y contraseña, y que la contraseña tenga al menos 4 caracteres
    // Si alguna validación falla, se muestra un mensaje indicando el error y sigue en el formulario.

    if (usuario === '') {
        mensaje.textContent = 'El usuario es obligatorio';
        mensaje.style.color = 'red';
        return;
    }

    if (password === '') {
        mensaje.textContent = 'La contraseña es obligatoria';
        mensaje.style.color = 'red';
        return;
    }

    if (password.length < 4) {
        mensaje.textContent = 'La contraseña debe tener al menos 4 caracteres';
        mensaje.style.color = 'red';
        return;
    }

    // ===== ENVÍO AL SERVIDOR =====

    try {

        const respuesta = await fetch('./login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body:
                `usuario=${encodeURIComponent(usuario)}&password=${encodeURIComponent(password)}`
        });

        const resultado = await respuesta.json();

        if (resultado.exito) {
            mensaje.textContent = 'Acceso autorizado';
            mensaje.style.color = 'green';

            // Redirección opcional
            // window.location.href = 'dashboard.php';

        } else {
            mensaje.textContent = resultado.mensaje;
            mensaje.style.color = 'red';
        }

    } catch (error) {
        mensaje.textContent = 'Error de conexión con el servidor';
        mensaje.style.color = 'red';

        console.error(error);
    }

});