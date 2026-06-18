/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : ETL_CMDB.SQL
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción	 : Rellena los datos de las tablas de la CMDB de forma manual.
 * 				   Datos mínimos para alimentar posteriormente la BD de la CMDB
 * 				   Los datos masivos se rellenarán importando datos de un fichero CSV.
 * Tablas		 : perfil
 * 				   grupo_local
 * 				   usuario_local
 * 				   modulo
 * 				   clase_ci
 * 				   clase_relacion
 * =========================================================
 */

use cmdb;

/*============================================================================
 * Tablas relacionadas con gestión de accesos
 *============================================================================
*/

-- Borrado de datos de tablas que se importan en CSV
-- DELETE FROM ci;
DELETE FROM clase_ci;
DELETE FROM clase_relacion;
DELETE FROM relacion_ci;
DELETE FROM red_oficina;
DELETE FROM red;
DELETE FROM red_ci ;


-- Tabla: perfiles de acceso
-- Valores para los perfiles creados
START TRANSACTION;
-- 1. Vaciar la tabla
	DELETE FROM perfil;
-- 2. Insertar los nuevos datos
INSERT INTO perfil VALUES
	(1, 'administrador', 10, 'Visibilidad de todos los datos, más algún módulo técnico específico'),
	(2, 'privilegiado', 20, 'Visibilidad de todos los datos, más algunos datos restringidos como los de altos cargos'),
	(3, 'sectorial', 30, 'Visibilidad limitada a su área, país u oficina'),
	(4, 'técnico', 40, 'Permite ver todos los datos almacenados sin ningún tipo de restricción'),
	(5, 'básico', 50, 'Visibilidad limitada: no permite ver datos de usuarios');
commit;

-- Tabla: grupos locales
-- Valores para los grupos por defecto
-- Son válidos para pruebas, pero el acceso debería realizarse mediante sistema federado
START TRANSACTION;
-- 1. Vaciar la tabla
	DELETE FROM grupo_local;
-- 2. Insertar los nuevos datos
INSERT INTO grupo_local VALUES
	(1, 'grp_local_administradores', 'Grupo local para administradores con visibilidad total y módulos de administración del sistema', '2626-06-06', 1),
	(2, 'grp_local_privilegiado', 'Grupo local para usuarios con acceso a información restringida (altos cargos)', '2626-06-06', 2),
	(3, 'grp_local_administrativos', 'Grupo local para administrativos con visibilidad sectorial', '2626-06-06', 3),
	(4, 'grp_local_técnico', 'Grupo local para técnicos con visibilidad total', '2626-06-06', 4),
	(5, 'grp_local_básico', 'Grupo local para usuarios que no pueden ver datos privados', '2626-06-06', 5);
commit;

-- Tabla: usuarios locales
-- Valores para usuarios de pruebas
-- Son válidos para pruebas, pero el acceso debería realizarse mediante sistema federado
START TRANSACTION;
-- 1. Vaciar la tabla
	DELETE FROM usuario_local;
-- 2. Insertar los nuevos datos
INSERT INTO usuario_local (login, pwd_hash , apellidos, nombre, id_oficina, observaciones, id_grupo_local) VALUES
	('caperucita', '$2y$10$Eoz.y7Cg8Gub9Yx99nSBeuV69i6F8Q69Yq9666tZ5hK6tE29y666.', 'Rojas', 'Caperucita', 101, 'Cuidado con el lobo en los desplazamientos', 1),
	('pinocho', '$2y$10$Eoz.y7Cg8Gub9Yx99nSBeuV69i6F8Q69Yq9666tZ5hK6tE29y666.', 'Pino Carpintero', 'Pinocho', 102, 'Controlar rigurosamente sus declaraciones', 1),
	('cenicienta', '$2y$10$Eoz.y7Cg8Gub9Yx99nSBeuV69i6F8Q69Yq9666tZ5hK6tE29y666.', 'del Castillo', 'Cenicienta', 101, 'Salida obligatoria antes de las 00:00', 2),
	('blancanieves', '$2y$10$Eoz.y7Cg8Gub9Yx99nSBeuV69i6F8Q69Yq9666tZ5hK6tE29y666.', 'del Bosque', 'Blancanieves', 102, 'Líder de equipo de siete subalternos', 2),
	('aladino', '$2y$10$Eoz.y7Cg8Gub9Yx99nSBeuV69i6F8Q69Yq9666tZ5hK6tE29y666.', 'Arenas Palacio', 'Aladino', 103, 'Especialista en logística y transporte aéreo',3),
	('peterpan', '$2y$10$Eoz.y7Cg8Gub9Yx99nSBeuV69i6F8Q69Yq9666tZ5hK6tE29y666.', 'Niño Pluma', 'Peter', 104, 'Se niega a firmar contratos a largo plazo', 4),
	('rapunzel', '$2y$10$Eoz.y7Cg8Gub9Yx99nSBeuV69i6F8Q69Yq9666tZ5hK6tE29y666.', 'de La Torre', 'Rapunzel', 103, 'Requiere oficina con excelente conectividad', 3),
	('pulgarcito', '$2y$10$Eoz.y7Cg8Gub9Yx99nSBeuV69i6F8Q69Yq9666tZ5hK6tE29y666.','Pequeño Chico', 'Pulgarcito', 104, 'Técnico de Soporte', 4),
	('simbad', '$2y$10$Eoz.y7Cg8Gub9Yx99nSBeuV69i6F8Q69Yq9666tZ5hK6tE29y666.', 'Marino', 'Simbad', 105, 'Alta disponibilidad para viajes internacionales', 5),
	('bella', '$2y$10$Eoz.y7Cg8Gub9Yx99nSBeuV69i6F8Q69Yq9666tZ5hK6tE29y666.', 'Alba de la Rosa', 'Bella', 101, 'Responsable de la gestión de la biblioteca', 5);
commit;

-- Tabla: Módulos
-- Un módulo define un contenido temático o conceptual completo. Por ejemplo: PTD, sedes, impresoras
START TRANSACTION;
-- 1. Vaciar la tabla
	DELETE FROM modulo;
-- 2. Insertar los nuevos datos
INSERT into modulo values 
	(1, 'Módulo-PTD', 'Presenta información del PC, usuario, monitor, impresoras'),
	(2, 'Módulo-Oficina', 'Presenta información organizacional de la oficina, redes y datos técnicos cuantitativos'),
	(3, 'Módulo-Tareas', 'Presenta información del resultado de las tareas ETL'),
	(4, 'Módulo-Impresora', 'Presenta información de la conectividad completa de las impresoras');
commit;	

-- Tabla: clase_CI
-- Valores para la tabla de clases de CI
START TRANSACTION;
-- 1. Vaciar la tabla
	DELETE FROM clase_ci;
-- 2. Insertar los nuevos datos
	INSERT INTO clase_ci (nombre) VALUES
	('PC'),
	('PC-Portátil'),
	('tablet-PC'),
	('monitor'),
	('impresora'),
	('móvil'),
	('switch'),
	('router'),
	('docking'),
	('AP'),
	('teléfono-IP'),
	('usuario');
commit;


-- Tabla: clase_relacion
-- Valores para las posibles relaciones entre CI
START TRANSACTION;
-- 1. Vaciar la tabla
	DELETE FROM clase_relacion;
-- 2. Insertar los nuevos datos
	INSERT INTO clase_relacion VALUES
	(1,'visualizar'),
	(2,'imprimir'),
	(3,'conectar'),
	(4,'licencias_soft'),
	(5,'licencias_usr');
commit;


