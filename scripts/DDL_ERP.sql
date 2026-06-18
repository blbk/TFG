/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : DDL_ERP.SQL
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 *
 * Descripción	 : DDL para la creación de tablas del ERP
 * Tablas		 : pais
 * 				   clase_activo
 * =========================================================
 */
/*============================================================================
-- SCRIPT DDL: MODELO DE BASE DE DATOS PARA INVENTARIO PATRIMONIAL GLOBAL (MySQL)
-- ============================================================================ */

use erp;
/* ----------------------------------------------------------------------------
-- 1. ELIMINACIÓN DE TABLAS EXISTENTES (Orden inverso para evitar errores de FK)
-- ----------------------------------------------------------------------------*/
DROP TABLE IF EXISTS activo CASCADE;
DROP TABLE IF EXISTS modelo CASCADE;
DROP TABLE IF EXISTS marca CASCADE;
DROP TABLE IF EXISTS clase_activo CASCADE;
DROP TABLE IF EXISTS oficina CASCADE;
DROP TABLE IF EXISTS ciudad CASCADE;
DROP TABLE IF EXISTS pais CASCADE;
DROP TABLE IF EXISTS unidad_organica CASCADE;
DROP view IF EXISTS v_oficina;
DROP view IF EXISTS v_activo;
/*-- ----------------------------------------------------------------------------
-- 2. CREACIÓN DE TABLAS DE UBICACIONES
-- ----------------------------------------------------------------------------*/

-- Tabla: Unidades orgánicas
-- Permite vincular una unidad de negocio (unidad orgánica) de la empresa con una oficina.
-- No es estrictamente necesaria, pero es normal que las empresas vinculen sus sedes a sus estructuras.
CREATE TABLE unidad_organica (
    id_uorganica INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    unique (nombre)
);

-- Tabla: Países
CREATE TABLE pais (
    cod_pais CHAR(2) PRIMARY KEY, 
    nombre VARCHAR(50) NOT NULL,
    UNIQUE (nombre)
);

-- Tabla: Ciudades
CREATE TABLE ciudad (
    id_ciudad INT PRIMARY KEY AUTO_INCREMENT,
    cod_pais CHAR(2) NOT NULL,
    cod_ciudad CHAR(2) NOT NULL,
    nombre VARCHAR(50) NOT NULL,
    UNIQUE (cod_pais, cod_ciudad),
    CONSTRAINT fk_ciudad_pais FOREIGN KEY (cod_pais) REFERENCES pais (cod_pais) ON update cascade ON DELETE RESTRICT
);

-- Tabla: Oficinas o Sedes
 -- Concatenación País + Ciudad + Secuencial (no automático, ya que cada oficina comienza desde 001 en cada ciudad).
 -- El código postal (CP) debe ser varchar(6) porque hay países con 6 dígitos de CP (i.e. Colombia).
CREATE TABLE oficina (
	id_oficina INT PRIMARY KEY AUTO_INCREMENT,
    id_ciudad INT NOT NULL,
    id_uorganica INT NOT NULL,
    cod_oficina CHAR(3) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(150) NOT NULL,
    cp VARCHAR(6) NOT NULL,
    lat DECIMAL(9,7) DEFAULT 0,
    lng DECIMAL(9,7) DEFAULT 0,
    zoom TINYINT DEFAULT 0,
    geo_estado VARCHAR(20) DEFAULT NULL,
    CONSTRAINT fk_oficina_ciudad FOREIGN KEY (id_ciudad) REFERENCES ciudad (id_ciudad) ON DELETE restrict,
    CONSTRAINT fk_oficina_uorganica FOREIGN KEY (id_uorganica) REFERENCES unidad_organica (id_uorganica) ON DELETE RESTRICT
);


/*-- ----------------------------------------------------------------------------
-- 3. CREACIÓN DE TABLAS DE ACTIVOS
-- ----------------------------------------------------------------------------*/

-- Tabla: Clases de Activos
-- 'PC', 'Móvil', 'Router', etc.
CREATE TABLE clase_activo (
    id_clase INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(30) NOT NULL, 
    UNIQUE (nombre)
);

-- Tabla: Marcas
-- 'HP', 'Apple', 'Cisco', etc.
CREATE TABLE marca (
    id_marca INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    UNIQUE (nombre)
);

-- Tabla: Modelos
CREATE TABLE modelo (
    id_modelo INT PRIMARY KEY AUTO_INCREMENT,
    id_marca INT NOT NULL,
    id_clase INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    CONSTRAINT fk_modelo_marca FOREIGN KEY (id_marca) REFERENCES marca (id_marca) ON DELETE RESTRICT,
    CONSTRAINT fk_modelo_clase FOREIGN KEY (id_clase) REFERENCES clase_activo (id_clase) ON update cascade ON DELETE RESTRICT
);

-- Tabla: Activos
-- Rango a partir de 100000, solo para la carga inicial del dato
-- Garantía en años
CREATE TABLE activo (
    codigo_inventario VARCHAR(6) PRIMARY KEY NOT NULL,
    id_modelo INT NOT NULL,
    id_oficina INT NOT NULL,
    fecha_compra DATE NOT NULL,
    garantia TINYINT unsigned NULL,  
    numero_serie VARCHAR(30) NULL,
    CONSTRAINT chk_codigo_inventario CHECK (codigo_inventario BETWEEN 100000 AND 800000),
    CONSTRAINT fk_activo_modelo FOREIGN KEY (id_modelo) REFERENCES modelo (id_modelo) ON DELETE RESTRICT,
    CONSTRAINT fk_activo_oficina FOREIGN KEY (id_oficina) REFERENCES oficina (id_oficina) ON DELETE RESTRICT
);

/*-- ----------------------------------------------------------------------------
-- 4. CREACIÓN DE ÍNDICES PARA OPTIMIZACIÓN 
-- ----------------------------------------------------------------------------*/

CREATE INDEX idx_modelo_marca_clase ON modelo(id_marca, id_clase);
CREATE INDEX idx_oficina_ciudad ON oficina(id_ciudad);
CREATE INDEX idx_ciudad_nombre ON ciudad(nombre);
CREATE INDEX idx_activo_oficina ON activo(id_oficina);
CREATE INDEX idx_activo_modelo ON activo(id_modelo);

/*-- ----------------------------------------------------------------------------
-- 5. VISTA para ver las oficinas, dirección, ciudad y país
-- ----------------------------------------------------------------------------*/
CREATE VIEW v_oficina AS
select
	o.id_oficina as id,
    o.cod_oficina as codigo,
    o.nombre as oficina,
    o.direccion,
    c.nombre AS ciudad,
    p.nombre AS pais,
    lat,
    lng,
    zoom
FROM oficina o, ciudad c, pais p
where o.id_ciudad = c.id_ciudad 
and p.cod_pais = c.cod_pais

/*-- ----------------------------------------------------------------------------
-- 6. VISTA para consultar los detalles de los ACTIVOS
-- ----------------------------------------------------------------------------*/
CREATE VIEW v_activo AS
SELECT 
    a.codigo_inventario, 
    c.nombre AS clase, 
    mc.nombre AS marca, 
    md.nombre AS modelo, 
    a.numero_serie,
    a.fecha_compra,
    a.garantia,
    o.id_oficina,
    o.cod_oficina,
    o.nombre as oficina,
    o.direccion, 
    d.nombre AS ciudad, 
    p.nombre AS pais,
    p.cod_pais
FROM activo a
	INNER JOIN modelo md ON a.id_modelo = md.id_modelo
	INNER JOIN marca mc ON md.id_marca = mc.id_marca
	INNER JOIN clase_activo c ON md.id_clase = c.id_clase
	INNER JOIN oficina o ON a.id_oficina = o.id_oficina
	INNER JOIN ciudad d ON o.id_ciudad = d.id_ciudad
	INNER JOIN pais p ON d.cod_pais = p.cod_pais;


