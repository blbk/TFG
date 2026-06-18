/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : ETL_ERP.SQL
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción	 : Rellena los datos de las tablas del ERP de forma manual.
 * 				   Datos mínimos para alimentar posteriormente la BD del ERP
 * 				   El resto de tablas se rellenará importando datos de un fichero CSV.
 * Tablas		 : pais
 * 				   clase_activo
 * 				   unidad_organica
 * =========================================================
 */

use erp;

-- Tabla: pais
-- Valores para la tabla de países
START TRANSACTION;
	-- 1. Vaciar la tabla
	DELETE FROM pais;
	
	-- 2. Insertar los nuevos datos
	INSERT INTO pais VALUES
	('01','España'),
	('02','México'),
	('03','Colombia'),
	('04','Ecuador'),
	('05','Perú');
commit;

-- Tabla: clase_activo
-- Valores para la tabla de clases de activos
START TRANSACTION;
	-- 1. Vaciar la tabla
	DELETE FROM marca;
	
	-- 2. Insertar los nuevos datos
	INSERT INTO clase_activo (nombre) VALUES
	('PC'),
	('PC-Portátil'),
	('Tablet-PC'),
	('monitor'),
	('impresora'),
	('móvil'),
	('switch'),
	('router'),
	('Docking'),
	('AP'),
	('Teléfonos-IP');
commit;

-- Tabla: unidad_organica
-- Valores para la tabla de estructura de la organización
START TRANSACTION;
	-- 1. Vaciar la tabla
	DELETE FROM marca;
	
	-- 2. Insertar los nuevos datos
	INSERT INTO unidad_organica (nombre) VALUES
	('Departamento Jurídico Legal'),
	('Gabinete de Comunicación y Relaciones Públicas'),
	('Departamento de Sostenibilidad'),
	('Dirección de Planificación Estratégica y Desarrollo'),
	('Dirección General de Recursos Humanos'),
	('Dirección de Tecnologías de la Información'),
	('Departamento Financiero'),
	('Área de Ciberseguridad'),
	('Oficina de Atención al Público'),
	('Unidad de Compras y Logística'),
	('Departamento de Responsabilidad Social Corporativa'),
	('Dirección de Infraestucturas');
commit;

-- Tabla: marca
-- Valores para la tabla de marcas de activos
/*
START TRANSACTION;
	-- 1. Vaciar la tabla
	DELETE FROM marca;

	-- 2. Insertar los nuevos datos
	INSERT INTO marca (nombre) VALUES	
	('HP'),
	('Fujitsu'),
	('Lenovo'),
	('Acer'),
	('ASUS'),
	('Dell'),
	('Apple'),
	('Samsung'),
	('Microsoft'),
	('Huawei'),
	('Xiaomi'),
	('LG'),
	('BenQ'),
	('Philips'),
	('Epson'),
	('Brother'),
	('Canon'),
	('Kyocera'),
	('Xerox'),
	('Cisco'),
	('Aruba'),
	('Juniper'),
	('D-Link'),
	('TP-Link'),
	('Ubiquiti'),
	('Fortinet'),
	('Mikrotik'),
	('Polycom');
commit;
*/