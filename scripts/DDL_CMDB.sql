/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : DDL_CMDB.SQL
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción	 : DDL para la creación de tablas de la CMDB
 * 					Tablas para controlar los CI
 * 					Tablas para controlar los accesos
 * =========================================================*/

/*============================================================================
  SCRIPT DDL: MODELO DE BASE DE DATOS PARA INVENTARIO PATRIMONIAL GLOBAL (MySQL)
  ============================================================================ */

use cmdb;
/* ----------------------------------------------------------------------------
-- 1. ELIMINACIÓN DE TABLAS EXISTENTES (Orden inverso para evitar errores de FK)
-- ----------------------------------------------------------------------------*/
DROP TABLE IF EXISTS perfil_veta_modulo CASCADE;
DROP TABLE IF EXISTS perfil_veta_seccion CASCADE;
DROP TABLE IF EXISTS seccion_fuente CASCADE;
DROP TABLE IF EXISTS fuente CASCADE;
DROP TABLE IF EXISTS seccion CASCADE;
DROP TABLE IF EXISTS modulo CASCADE;
DROP TABLE IF EXISTS usuario_local CASCADE;
DROP TABLE IF EXISTS grupo_local CASCADE;
DROP TABLE IF EXISTS grupo_da CASCADE;
DROP TABLE IF EXISTS perfil CASCADE;
DROP TABLE IF EXISTS red_oficina CASCADE;
DROP TABLE IF EXISTS configuracion_red CASCADE;
DROP TABLE IF EXISTS red_ci CASCADE;
DROP TABLE IF EXISTS relacion_ci CASCADE;
DROP TABLE IF EXISTS clase_relacion cascade;
DROP TABLE IF EXISTS monitor CASCADE;
DROP TABLE IF EXISTS impresora CASCADE;
DROP TABLE IF EXISTS pc CASCADE;
DROP TABLE IF EXISTS ci CASCADE;
DROP TABLE IF EXISTS clase_ci CASCADE;
DROP TABLE IF EXISTS red CASCADE;
DROP TABLE IF EXISTS usuario_itsm CASCADE;
DROP VIEW IF EXISTS v_impresora;
DROP VIEW IF EXISTS v_impresora_local;
DROP VIEW IF EXISTS v_ci_clase;
DROP VIEW IF EXISTS v_ci_oficina;
DROP VIEW IF EXISTS v_monitor_oficina;
DROP VIEW IF EXISTS v_red_oficina;
DROP VIEW IF EXISTS v_pc;
DROP VIEW IF EXISTS v_usuario_perfil;

/*-- ----------------------------------------------------------------------------
-- 2. CREACIÓN DE TABLAS DE CI
-- ----------------------------------------------------------------------------*/

-- Tabla: Clases de CI
-- Todos los CI que vamos a considerar en la aplicación (redes, activos, etc.)
CREATE TABLE clase_ci (
    id_clase INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(30) NOT NULL, 
    UNIQUE (nombre)
);

-- Tabla: ci (elementos de congifuración)
-- Todos los elementos de la base de datos con valor para el modelo son CI según ITIL
-- Las clases de CI deberían coincidir con las del ERP
CREATE TABLE ci (
    id_ci INT PRIMARY KEY AUTO_INCREMENT,
    id_clase INT(11) NOT NULL,
    marca VARCHAR(100) NULL,
    modelo VARCHAR(100) NULL,
    numero_serie VARCHAR(30) null,
    fecha date,
    CONSTRAINT fk_clase_ci FOREIGN KEY (id_clase) REFERENCES clase_ci(id_clase)
);


-- Tabla: ordenares personales
CREATE TABLE pc (
    id_ci INT PRIMARY KEY,
    nombre_local VARCHAR(50) NULL,
    sistema_operativo VARCHAR(100),
    version_so VARCHAR(100),
    arquitectura VARCHAR(10),
    disco_total float(5,1),
    disco_libre float(5,1), 
    memoria float(5,2),
    dominio VARCHAR(15),
    login VARCHAR(20),
    fecha_login date NULL,
    fecha_antivirus date NULL,
    fecha_boot date NULL,
    antivirus VARCHAR(50),
    estado_antivirus VARCHAR(50),
    version_chrome VARCHAR(30),
    version_edge VARCHAR(30),
    CONSTRAINT fk_pc_ci FOREIGN KEY (id_ci) REFERENCES ci(id_ci)
);

-- Tabla: impresoras
CREATE TABLE impresora (
    id_ci INT PRIMARY KEY,
    nombre_local VARCHAR(50) NULL,
    driver VARCHAR(100),
	CONSTRAINT fk_impresora_ci FOREIGN KEY (id_ci) REFERENCES ci(id_ci)
);


-- Tabla: monitores
CREATE TABLE monitor (
    id_ci INT PRIMARY KEY,
    pulgadas DECIMAL(4,1),
	CONSTRAINT fk_monitor_ci FOREIGN KEY (id_ci) REFERENCES ci(id_ci)
);


-- Tabla: Clases de relación
-- Establece las clases de relaciones entre CI (imprimir, visualizar, conectar...)
CREATE TABLE clase_relacion (
    id_relacion tinyint unsigned PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(30) NOT NULL, 
    UNIQUE (nombre)
);

-- Tabla: Relaciones entre los CI
-- Relaciona los CI
CREATE TABLE relacion_ci (
    id_ci_origen INT(11),
    id_ci_destino INT(11),
   	id_relacion tinyint UNSIGNED,
    CONSTRAINT fk_relacion_ci_origen FOREIGN KEY (id_ci_origen) REFERENCES ci(id_ci),
	CONSTRAINT fk_relacion_ci_destino FOREIGN KEY (id_ci_destino) REFERENCES ci(id_ci),
	CONSTRAINT fk_relacion_ci_clase_relacion FOREIGN KEY (id_relacion) REFERENCES clase_relacion(id_relacion)
    );

-- Tabla: redes
-- Importa la información del IPAM. Se graba el dato de cada red de la organización
-- El gateway es redundante (se puede calcular), pero permite simplificar las consultas.
-- Además, puede darse el caso de algún gateway que no siga la norma.
create table red (
	gateway VARCHAR(15) PRIMARY KEY,
	cidr VARCHAR(20), 	-- 	ip varchar(15)/mascara (0..31)
	vlan int,			-- 	(0..4094)
	descripcion VARCHAR(100),
	unique (cidr)
);

-- Tabla: configuración de red del CI
-- Los datos de red se implementan como una COMPOSICIÓN, ya que es una característica opcional.
CREATE TABLE red_ci (
    id_ci INT PRIMARY KEY,
    direccion_ip VARCHAR(15),
    direccion_mac VARCHAR(17),
    hostname VARCHAR(15) NULL,
    gateway VARCHAR(15) NULL,
	CONSTRAINT fk_red_ci FOREIGN KEY (id_ci) REFERENCES ci(id_ci),
	CONSTRAINT fk_red_ci_gateway FOREIGN KEY (gateway) REFERENCES red(gateway)
);


-- Tabla: Relación entre oficinas y las redes que tienen
-- Recoge la información del IPAM. Se graba el dato del gateway para simplificar las consultas.
-- El codigo de la oficina es del ERP (otra base de datos) no puede relacionarse directamente.
-- clave primaria compuesta de gateway y oficina.
-- El gateway está ligado a la tabla de redes
-- La uso para modelar los casos de gateways que den servicio a varias sedes próximas
-- No aplico "ON DELETE CASCADE"
create table red_oficina (
	gateway VARCHAR(15),
	id_oficina INT(11),
	primary key (gateway, id_oficina),
	CONSTRAINT fk_gateway FOREIGN KEY (gateway) REFERENCES red(gateway)  
);

/*-- ----------------------------------------------------------------------------
-- 3. CREACIÓN DE TABLAS DE ACCESOS
-- ----------------------------------------------------------------------------*/

-- Tabla: perfiles
-- Permite asignar a los grupos diferente visibilidad de datos
-- Prioridad: Cuanto más baja, más prioridad (Asignar de 10 en 10)
CREATE TABLE perfil (
	id_perfil INT PRIMARY KEY auto_increment,
	nombre VARCHAR(20) UNIQUE,
	prioridad tinyint unsigned,
	descripcion VARCHAR(100)
);

-- Tabla: grupos locales
-- Permite crear grupos de acceso propios de la aplicación
CREATE TABLE grupo_local (
	id_grupo_local int PRIMARY KEY AUTO_INCREMENT,
	nombre VARCHAR(30),
	descripcion VARCHAR(100),
	fecha_creacion date,
	id_perfil INT(11),
	CONSTRAINT fk_grupo_local_perfil FOREIGN KEY (id_perfil) REFERENCES perfil(id_perfil)
);

-- Tabla: grupos de directorio activo
-- grupos de directorio activo autorizados a acceder a la aplicación
-- Common Name (CN) es el nombre del grupo de DA que luego se verificará
-- fecha_alta: Cuando se gestiona el acceso para este grupo
CREATE TABLE grupo_da (
	common_name VARCHAR(20),
	fecha_alta date,
	id_perfil INT(11),
	CONSTRAINT fk_grupo_da_perfil FOREIGN KEY (id_perfil) REFERENCES perfil(id_perfil)
);

-- Tabla: usuarios locales
-- Permite asignar una cuenta a usuarios para acceder a la aplicación
-- Se limita el login a 15 caracteres
-- La contraseña se graba con su valor Hash por seguridad
CREATE TABLE usuario_local (
	login VARCHAR(15) primary key,
	pwd_hash VARCHAR(255),
	apellidos VARCHAR(50),
	nombre VARCHAR(50),
	id_oficina INT(11),
	observaciones VARCHAR(100),
	id_grupo_local INT(11),
	CONSTRAINT fk_usuario_grupo_local FOREIGN KEY (id_grupo_local) REFERENCES grupo_local(id_grupo_local) ON DELETE cascade   
);

-- Tabla: módulos
-- Para controlar la visibilidad de los módulos por perfiles
CREATE TABLE modulo (
	id_modulo int PRIMARY KEY AUTO_INCREMENT,
	nombre VARCHAR(20) unique,
	descripcion VARCHAR(100)
);

-- Tabla: secciones
-- Para controlar la visibilidad de las secciones por perfiles
CREATE TABLE seccion (
	id_seccion int PRIMARY KEY AUTO_INCREMENT,
	nombre varchar(20) unique,
	descripcion varchar(100),
	id_modulo INT(11),
	CONSTRAINT fk_seccion_modulo FOREIGN KEY (id_modulo) REFERENCES modulo(id_modulo) ON DELETE cascade 
);

-- Tabla: fuentes
-- Para conocer la fuente del dato
CREATE TABLE fuente (
	id_fuente int PRIMARY KEY AUTO_INCREMENT,
	nombre varchar(20) unique,
	descripcion varchar(100),
	id_seccion INT(11)
);

-- Tabla: secciones fuentes
-- Relaciona secciones y las fuentes que informan en cada una (N:N)
CREATE TABLE seccion_fuente (
	id_seccion INT(11),
	id_fuente INT(11),
primary key (id_seccion, id_fuente),
CONSTRAINT fk_rel_seccion FOREIGN KEY (id_seccion) REFERENCES seccion(id_seccion) ON DELETE cascade,
CONSTRAINT fk_rel_fuente FOREIGN KEY (id_fuente) REFERENCES fuente(id_fuente) ON DELETE cascade 
);

-- Tabla: secciones que veta un perfil
-- Lista negra de secciones que el perfil no permite ver
CREATE TABLE perfil_veta_seccion (
	id_perfil INT(11),
	id_seccion INT(11),
	primary key (id_perfil, id_seccion),
	CONSTRAINT fk_veta_seccion_perfil FOREIGN KEY (id_perfil) REFERENCES perfil(id_perfil),
	CONSTRAINT fk_veta_seccion FOREIGN KEY (id_seccion) REFERENCES seccion(id_seccion) ON DELETE cascade 
);

-- Tabla: módulos que veta un perfil
-- Lista negra de módulos que el perfil no permite ver
CREATE TABLE perfil_veta_modulo (
	id_perfil INT(11),
	id_modulo INT(11),
	primary key (id_perfil, id_modulo),
	CONSTRAINT fk_veta_modulo_perfil FOREIGN KEY (id_perfil) REFERENCES perfil(id_perfil),
	CONSTRAINT fk_veta_modulo FOREIGN KEY (id_modulo) REFERENCES modulo(id_modulo) ON DELETE cascade 
);


/*-- ----------------------------------------------------------------------------
-- 3. CREACIÓN DE TABLAS AUXILIARES o TEMPORALES hasta integración
-- ----------------------------------------------------------------------------*/

-- Tabla: usuarios ITSM
-- Tabla auxiliar para emular conexión con el sistema ITSM para conseguir el teléfono del usuario
-- El software ITSM debería proporcionar más información (mínimo: Incidencias abiertas por el usuario)
-- No se puede relacionar por ser un sistema externo (Iría por API)
CREATE TABLE usuario_itsm (
	login VARCHAR(15) primary key,
	nomape VARCHAR(100),
	tlf_movil VARCHAR(15),
	foto INT default 0 not NULL;
);

-- Tabla: usuarios Directorio Activo
-- Esta tabla no existiría, se preguntaría directamente a DA.
-- Tabla auxiliar para emular conexión con el Directorio Activo para proporcionar información de la cuenta
-- No se puede relacionar por ser un sistema externo (Iría por integración de PHP con DA)
-- El DA debería aportar más información (grupos de DA, contendeores, datos organizativos...)
/*create table usuario_da (
	login VARCHAR(15) PRIMARY KEY,
	nombre VARCHAR(50),
	fecha_login date,
	fecha_caducidad_pwd date,
	
);*/
/*-- ----------------------------------------------------------------------------
-- 4. CREACIÓN DE ÍNDICES PARA OPTIMIZACIÓN 
-- ----------------------------------------------------------------------------*/

CREATE INDEX idx_pc_nombre ON pc(nombre_local);
CREATE INDEX idx_impresora_nombre ON impresora(nombre_local);
CREATE INDEX idx_ci_numero_serie ON ci(numero_serie);

CREATE INDEX idx_red_cidr ON red(cidr);
CREATE INDEX idx_red_ci_hostname ON red_ci(hostname);
CREATE INDEX idx_red_ci_ip ON red_ci(direccion_ip);

/*-- ----------------------------------------------------------------------------
-- 5. VISTAS para ver los accesos: Usuarios autorizados y sus perfiles
-- ----------------------------------------------------------------------------*/
CREATE VIEW v_usuario_perfil AS
SELECT login, u.nombre, u.apellidos, pwd_hash, id_oficina oficina, g.nombre grupo, p.nombre perfil
FROM usuario_local as u
	INNER JOIN grupo_local as g on u.id_grupo_local = g.id_grupo_local
	INNER JOIN perfil p on p.id_perfil = g.id_perfil;

/*-- ----------------------------------------------------------------------------
-- 6. VISTAS para consultar los detalles de los CI
-- ----------------------------------------------------------------------------*/
CREATE VIEW v_impresora AS
SELECT 
    c.id_ci,
	c.marca,
	c.modelo,
	c.numero_serie,
	rc.direccion_ip,
	rc.direccion_mac,
	rc.hostname,
	rc.gateway
FROM ci AS c
	JOIN red_ci rc ON c.id_ci = rc.id_ci
WHERE id_clase=5;

CREATE VIEW v_impresora_local AS
SELECT 
    c.id_ci,
	c.marca,
	c.modelo,
	c.numero_serie,
	rc.direccion_ip,
	rc.direccion_mac,
	rc.hostname,
	rc.gateway
FROM ci AS c
	LEFT JOIN red_ci rc ON c.id_ci = rc.id_ci
WHERE id_clase=5;

-- Todos los CI y su clase
CREATE VIEW v_ci_clase AS
SELECT 
    c.id_ci,
	c.marca,
	c.modelo,
	c.numero_serie,
	cl.nombre clase
FROM ci AS c
	INNER JOIN clase_ci cl ON c.id_clase = cl.id_clase;
	
-- Todas las redes de una oficina
CREATE VIEW v_red_oficina AS
SELECT id_oficina, o.gateway, cidr, vlan, descripcion
FROM red_oficina o
	JOIN red ON o.gateway = red.gateway 
WHERE id_oficina = 1
order by vlan;

-- Todos los CI en las redes de una oficina
CREATE VIEW v_ci_oficina AS
SELECT 
	cl.nombre clase,
    ci.id_ci,
    rc.hostname,
	ci.marca,
	ci.modelo,
	ci.numero_serie,
	rc.direccion_ip,
	rc.direccion_mac,
	rc.gateway,
	ro.id_oficina
FROM red_ci AS rc
	INNER JOIN ci ON ci.id_ci = rc.id_ci
	INNER JOIN clase_ci cl ON ci.id_clase = cl.id_clase
	INNER JOIN red ON rc.gateway = red.gateway
	INNER JOIN red_oficina ro ON red.gateway = ro.gateway
ORDER BY ci.id_clase;

-- Todos los monitores conectados a PC de una oficina
CREATE VIEW v_monitor_oficina AS
select 
	ci.id_ci, 
	ci.id_clase, 
	ci.marca, 
	ci.modelo, 
	ci.numero_serie, 
	ci.fecha, 
	id_oficina 
from ci 
	join relacion_ci rc on ci.id_ci = rc.id_ci_destino 
	join ci ci2 on rc.id_ci_origen  = ci2.id_ci 
	join red_ci rc2 on ci2.id_ci = rc2.id_ci 
	join red on	red.gateway = rc2.gateway 
	join red_oficina ro on red.gateway = ro.gateway 
where ci.id_clase=4; -- 96.336