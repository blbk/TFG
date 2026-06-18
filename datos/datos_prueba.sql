/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : datos_prueba.sql
 * Descripción   : Datos de prueba para la aplicación
 *                 EJECUTAR DESPUÉS de DDL_CMDB.sql y DDL_ERP.sql
 * =========================================================
 *
 * IMPORTANTE: Las contraseñas están en bcrypt.
 * Generar hashes propios ejecutando: php crear_hash.php
 * ========================================================= */

USE cmdb;

-- ---- Perfiles de acceso ----
INSERT IGNORE INTO perfil (nombre, prioridad, descripcion) VALUES
    ('Administrador', 10, 'Acceso total a todas las secciones'),
    ('Técnico',       20, 'Acceso a CI y búsquedas, sin configuración'),
    ('Consulta',      30, 'Solo lectura, sin acceso a datos sensibles');

-- ---- Grupos locales ----
INSERT IGNORE INTO grupo_local (nombre, descripcion, fecha_creacion, id_perfil) VALUES
    ('Admins',   'Grupo de administradores',   CURDATE(), 1),
    ('Tecnicos', 'Grupo de técnicos de campo',  CURDATE(), 2),
    ('Consulta', 'Grupo de solo lectura',       CURDATE(), 3);

-- ---- Usuarios de prueba ----
-- Contraseñas: admin123 / tecnico123 / consulta123
-- NOTA: Sustituir los hashes por los generados con crear_hash.php

-- admin / admin123
INSERT IGNORE INTO usuario_local (login, pwd_hash, nombre, apellidos, id_oficina, observaciones, id_grupo_local)
VALUES ('admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Administrador', 'Sistema', 1, 'Usuario admin por defecto', 1);

-- tecnico / tecnico123
INSERT IGNORE INTO usuario_local (login, pwd_hash, nombre, apellidos, id_oficina, observaciones, id_grupo_local)
VALUES ('tecnico',
    '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LkCPCoa61I6',
    'Técnico', 'Prueba', 1, 'Usuario técnico de prueba', 2);

-- ---- Clases de CI ----
INSERT IGNORE INTO clase_ci (nombre) VALUES
    ('Ordenador'),
    ('Impresora'),
    ('Monitor'),
    ('Switch'),
    ('Router'),
    ('Servidor'),
    ('Laptop'),
    ('Teléfono IP'),
    ('NAS'),
    ('Punto de Acceso WiFi');

-- ---- Clases de relación ----
INSERT IGNORE INTO clase_relacion (nombre) VALUES
    ('Conectado a'),
    ('Depende de'),
    ('Imprime en'),
    ('Pertenece a'),
    ('Visualiza en');

-- ---- Redes de ejemplo ----
INSERT IGNORE INTO red (gateway, cidr, vlan, descripcion) VALUES
    ('192.168.1.1',  '192.168.1.0/24',  10, 'Red oficina Madrid - Administración'),
    ('192.168.2.1',  '192.168.2.0/24',  20, 'Red oficina Madrid - Técnicos'),
    ('10.10.1.1',    '10.10.1.0/24',    30, 'Red servidores'),
    ('172.16.0.1',   '172.16.0.0/24',  100, 'Red DMZ');

-- ---- CI de ejemplo ----

-- PC 1
INSERT IGNORE INTO ci (id_ci, id_clase, marca, modelo, numero_serie, fecha) VALUES
    (1, 1, 'HP', 'ProBook 450 G8', 'SN-HP-001', '2022-03-15');
INSERT IGNORE INTO pc (id_ci, nombre_local, sistema_operativo, version_so, arquitectura,
    disco_total, disco_libre, memoria, dominio, login, fecha_login, fecha_antivirus,
    fecha_boot, antivirus, estado_antivirus, version_chrome, version_edge) VALUES
    (1, 'PC-MAD-001', 'Windows 11 Pro', '22H2', 'x64',
     512.0, 180.5, 16.00, 'DOMINIO.LOCAL', 'jmoyano',
     '2024-12-10', '2024-12-15', '2024-12-10',
     'Windows Defender', 'Activo', '120.0.6099.71', '120.0.2210.61');
INSERT IGNORE INTO red_ci (id_ci, direccion_ip, direccion_mac, hostname, gateway) VALUES
    (1, '192.168.1.101', 'AA:BB:CC:DD:01:01', 'PC-MAD-001', '192.168.1.1');

-- PC 2
INSERT IGNORE INTO ci (id_ci, id_clase, marca, modelo, numero_serie, fecha) VALUES
    (2, 1, 'Dell', 'Latitude 5530', 'SN-DELL-001', '2023-01-20');
INSERT IGNORE INTO pc (id_ci, nombre_local, sistema_operativo, version_so, arquitectura,
    disco_total, disco_libre, memoria, dominio, login, fecha_login, fecha_antivirus,
    fecha_boot, antivirus, estado_antivirus, version_chrome, version_edge) VALUES
    (2, 'PC-MAD-002', 'Windows 10 Pro', '21H2', 'x64',
     256.0, 90.2, 8.00, 'DOMINIO.LOCAL', 'aperez',
     '2024-12-09', '2024-12-01', '2024-12-09',
     'Symantec Endpoint', 'Activo', '119.0.6045.160', NULL);
INSERT IGNORE INTO red_ci (id_ci, direccion_ip, direccion_mac, hostname, gateway) VALUES
    (2, '192.168.1.102', 'AA:BB:CC:DD:01:02', 'PC-MAD-002', '192.168.1.1');

-- PC 3 — sin red (portátil WiFi)
INSERT IGNORE INTO ci (id_ci, id_clase, marca, modelo, numero_serie, fecha) VALUES
    (3, 7, 'Apple', 'MacBook Pro 14"', 'SN-APPLE-001', '2023-06-10');
INSERT IGNORE INTO pc (id_ci, nombre_local, sistema_operativo, version_so, arquitectura,
    disco_total, disco_libre, memoria, dominio, login, fecha_login, fecha_antivirus,
    fecha_boot, antivirus, estado_antivirus, version_chrome, version_edge) VALUES
    (3, 'MAC-MAD-001', 'macOS Sonoma', '14.2.1', 'ARM64',
     1000.0, 650.0, 32.00, NULL, 'cgomez',
     '2024-12-11', NULL, '2024-12-11',
     NULL, NULL, '120.0.6099.71', NULL);

-- Impresora 1
INSERT IGNORE INTO ci (id_ci, id_clase, marca, modelo, numero_serie, fecha) VALUES
    (4, 2, 'HP', 'LaserJet Pro M428fdw', 'SN-IMP-001', '2021-09-01');
INSERT IGNORE INTO impresora (id_ci, nombre_local, driver) VALUES
    (4, 'IMP-PLANTA1', 'HP Universal Print Driver v7.0.1');
INSERT IGNORE INTO red_ci (id_ci, direccion_ip, direccion_mac, hostname, gateway) VALUES
    (4, '192.168.1.200', 'AA:BB:CC:DD:02:01', 'IMP-PLANTA1', '192.168.1.1');

-- Impresora 2
INSERT IGNORE INTO ci (id_ci, id_clase, marca, modelo, numero_serie, fecha) VALUES
    (5, 2, 'Kyocera', 'ECOSYS M3645idn', 'SN-IMP-002', '2022-04-12');
INSERT IGNORE INTO impresora (id_ci, nombre_local, driver) VALUES
    (5, 'IMP-PLANTA2', 'Kyocera KX Driver v8.1');
INSERT IGNORE INTO red_ci (id_ci, direccion_ip, direccion_mac, hostname, gateway) VALUES
    (5, '192.168.2.200', 'AA:BB:CC:DD:02:02', 'IMP-PLANTA2', '192.168.2.1');

-- Monitor 1
INSERT IGNORE INTO ci (id_ci, id_clase, marca, modelo, numero_serie, fecha) VALUES
    (6, 3, 'LG', '27BK850Y-B', 'SN-MON-001', '2022-03-15');
INSERT IGNORE INTO monitor (id_ci, pulgadas) VALUES (6, 27.0);

-- Monitor 2
INSERT IGNORE INTO ci (id_ci, id_clase, marca, modelo, numero_serie, fecha) VALUES
    (7, 3, 'Dell', 'P2422H', 'SN-MON-002', '2022-03-15');
INSERT IGNORE INTO monitor (id_ci, pulgadas) VALUES (7, 24.0);

-- Switch
INSERT IGNORE INTO ci (id_ci, id_clase, marca, modelo, numero_serie, fecha) VALUES
    (8, 4, 'Cisco', 'Catalyst 2960-X', 'SN-SW-001', '2020-11-01');
INSERT IGNORE INTO red_ci (id_ci, direccion_ip, direccion_mac, hostname, gateway) VALUES
    (8, '192.168.1.2', 'AA:BB:CC:DD:FF:01', 'SW-CORE-01', '192.168.1.1');

-- Router
INSERT IGNORE INTO ci (id_ci, id_clase, marca, modelo, numero_serie, fecha) VALUES
    (9, 5, 'Cisco', 'ISR 4331', 'SN-RT-001', '2020-11-01');
INSERT IGNORE INTO red_ci (id_ci, direccion_ip, direccion_mac, hostname, gateway) VALUES
    (9, '192.168.1.1', 'AA:BB:CC:DD:FF:FF', 'GW-PRINCIPAL', '192.168.1.1');

-- Servidor
INSERT IGNORE INTO ci (id_ci, id_clase, marca, modelo, numero_serie, fecha) VALUES
    (10, 6, 'HP', 'ProLiant DL380 Gen10', 'SN-SRV-001', '2020-06-01');
INSERT IGNORE INTO red_ci (id_ci, direccion_ip, direccion_mac, hostname, gateway) VALUES
    (10, '10.10.1.10', 'AA:BB:CC:00:00:01', 'SRV-AD-01', '10.10.1.1');

-- ---- Relaciones entre CI ----

-- PC1 imprime en Impresora 1
INSERT IGNORE INTO relacion_ci (id_ci_origen, id_ci_destino, id_relacion) VALUES (1, 4, 3);
-- PC2 imprime en Impresora 1
INSERT IGNORE INTO relacion_ci (id_ci_origen, id_ci_destino, id_relacion) VALUES (2, 4, 3);
-- PC1 visualiza en Monitor 1
INSERT IGNORE INTO relacion_ci (id_ci_origen, id_ci_destino, id_relacion) VALUES (1, 6, 5);
-- PC2 visualiza en Monitor 2
INSERT IGNORE INTO relacion_ci (id_ci_origen, id_ci_destino, id_relacion) VALUES (2, 7, 5);
-- PCs conectados al Switch
INSERT IGNORE INTO relacion_ci (id_ci_origen, id_ci_destino, id_relacion) VALUES (1, 8, 1);
INSERT IGNORE INTO relacion_ci (id_ci_origen, id_ci_destino, id_relacion) VALUES (2, 8, 1);
-- Switch conectado al Router
INSERT IGNORE INTO relacion_ci (id_ci_origen, id_ci_destino, id_relacion) VALUES (8, 9, 1);
-- Servidor depende del Switch
INSERT IGNORE INTO relacion_ci (id_ci_origen, id_ci_destino, id_relacion) VALUES (10, 8, 2);

-- ---- Módulos y Secciones ----
INSERT IGNORE INTO modulo (nombre, descripcion) VALUES
    ('Inventario', 'Gestión del inventario de CI'),
    ('Red',        'Gestión de redes e IPs'),
    ('Accesos',    'Gestión de usuarios y perfiles');

INSERT IGNORE INTO seccion (nombre, descripcion, id_modulo) VALUES
    ('CI',       'Elementos de configuración',       1),
    ('Relaciones','Relaciones entre CI',              1),
    ('Redes',    'Configuración de redes',            2),
    ('Usuarios', 'Gestión de usuarios locales',       3),
    ('Perfiles', 'Perfiles y permisos',               3);

-- ----------------------------------------------------------------------------
-- TABLA: usuario_itsm
-- Datos de contacto de los usuarios finales (empleados), usados en la
-- ficha de usuario accesible desde "Datos del equipo" → "Último usuario".
--
--   login     — clave primaria, coincide con pc.login
--   nomape    — nombre y apellidos completos
--   tlf_movil — teléfono de contacto en el trabajo
--   foto      — índice de la foto en public/img/usuarios/{foto}.jpg
--                (0 = imagen genérica)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuario_itsm (
    login     VARCHAR(15) PRIMARY KEY,
    nomape    VARCHAR(100),
    tlf_movil VARCHAR(15),
    foto      INT DEFAULT 0 NOT NULL
);

-- Usuarios de prueba — corresponden a los logins usados en la tabla pc
INSERT IGNORE INTO usuario_itsm (login, nomape, tlf_movil, foto) VALUES
    ('jmoyano', 'Javier Moyano Vizcaíno',  '+34 600 111 222', 1),
    ('aperez',  'Antonio Pérez Carrasco',  '+34 600 333 444', 2),
    ('cgomez',  'Carmen Gómez Ruiz',       '+34 600 555 666', 3);

SELECT 'Datos de prueba cargados correctamente' AS resultado;
