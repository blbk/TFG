<?php
/**
 * Script auxiliar para generar hashes de contraseñas
 * Ejecutar UNA SOLA VEZ desde consola: php crear_hash.php
 * ELIMINAR después de generar los datos.
 */

$usuarios = [
    ['admin',    'admin123',    'Administrador', 'Sistema'],
    ['jmoyano',  'cmdb2025',    'Javier',        'Moyano'],
    ['soporte',  'soporte123',  'Usuario',       'Soporte'],
];

echo "-- SQL para insertar usuarios de prueba\n";
echo "-- Ejecutar DESPUÉS de insertar perfiles y grupos\n\n";

foreach ($usuarios as $u) {
    $hash = password_hash($u[1], PASSWORD_BCRYPT);
    echo "-- Usuario: {$u[0]} | Contraseña: {$u[1]}\n";
    echo "INSERT IGNORE INTO usuario_local (login, pwd_hash, nombre, apellidos, id_oficina, observaciones, id_grupo_local)\n";
    echo "VALUES ('{$u[0]}', '$hash', '{$u[2]}', '{$u[3]}', 1, 'Usuario de prueba TFG', 1);\n\n";
}
