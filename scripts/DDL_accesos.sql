/*============================================================================
-- SCRIPT DDL: MODELO DE BASE DE DATOS PARA INVENTARIO PATRIMONIAL GLOBAL (MySQL)
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. ELIMINACIÓN DE TABLAS EXISTENTES (Orden inverso para evitar errores de FK)
-- ----------------------------------------------------------------------------*/
DROP TABLE IF EXISTS detalle_computo CASCADE;
DROP TABLE IF EXISTS activos CASCADE;
DROP TABLE IF EXISTS modelo CASCADE;
DROP TABLE IF EXISTS marca CASCADE;
DROP TABLE IF EXISTS clase_activo CASCADE;
DROP TABLE IF EXISTS oficina CASCADE;
DROP TABLE IF EXISTS ciudad CASCADE;
DROP TABLE IF EXISTS pais CASCADE;

/*-- ----------------------------------------------------------------------------
-- 2. CREACIÓN DE TABLAS DE UBICACIONES
-- ----------------------------------------------------------------------------*/

-- Tabla: Paises
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
 -- Concatenación País + Ciudad + Secuencial (no automático, ya que cada oficina comienza desde 001 en cada ciudad)
CREATE TABLE oficina (
	id_oficina INT PRIMARY KEY AUTO_INCREMENT,
    id_ciudad INT NOT NULL,
    cod_oficina CHAR(3) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(150) NOT NULL,
    cp CHAR(5) NOT NULL,
    CONSTRAINT fk_oficina_ciudad FOREIGN KEY (id_ciudad) REFERENCES ciudad (id_ciudad) ON DELETE RESTRICT
);

/*  CONSTRAINT fk_oficina_ciudad FOREIGN KEY (id_ciudad) REFERENCES ciudad (id_ciudad) ON DELETE RESTRICT 
ALTER TABLE oficina
ADD CONSTRAINT fk_oficina_ciudad
FOREIGN KEY (id_ciudad) REFERENCES ciudad (id);*/

/*-- ----------------------------------------------------------------------------
-- 3. CREACIÓN DE TABLAS MAESTRAS (Módulo de Catálogo)
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
    CONSTRAINT fk_modelo_clase FOREIGN KEY (id_clase) REFERENCES clase_activo (id_clase) ON DELETE RESTRICT
);

-- Tabla: Activos
-- Rango entre 100000 y 800000, solo para la carga inicial del dato
-- Garantía en años (entre 2 y 3 años, pero puede no conocerse
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
-- 5. CREACIÓN DE ÍNDICES PARA OPTIMIZACIÓN (Garantiza consultas en milisegundos)
-- ----------------------------------------------------------------------------*/

CREATE INDEX idx_modelo_marca_clase ON modelo(id_marca, id_clase);
CREATE INDEX idx_oficina_ciudad ON oficina(id_ciudad);
CREATE INDEX idx_activo_oficina ON activo(id_oficina);
CREATE INDEX idx_activo_modelo ON activo(id_modelo);