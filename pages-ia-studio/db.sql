-- ============================================================================
-- Base de Datos: AgendaWeb
-- Motor: MySQL 8+ / MariaDB (UTF8MB4)
-- Descripción: Esquema relacional para gestión de eventos personales
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `agendaweb`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `agendaweb`;

-- ----------------------------------------------------------------------------
-- Tabla: usuarios
-- Almacena las cuentas de usuario con contraseñas cifradas (password_hash)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `correo` VARCHAR(150) NOT NULL,
  `clave_hash` VARCHAR(255) NOT NULL,
  `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_correo` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: categoria (en singular según especificación)
-- Catálogo maestro de categorías de eventos
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categoria` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categoria_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Tabla: eventos
-- Eventos personales vinculados al usuario propietario y a una categoría
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `eventos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `titulo` VARCHAR(150) NOT NULL,
  `fecha` DATE NOT NULL,
  `hora` TIME NOT NULL,
  `categoria_id` INT UNSIGNED NOT NULL,
  `descripcion` TEXT NULL,
  `creado_en` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_eventos_usuario_fecha` (`usuario_id`, `fecha`, `hora`),
  KEY `idx_eventos_categoria` (`categoria_id`),
  CONSTRAINT `fk_eventos_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_eventos_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categoria` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Datos iniciales de categorías
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO `categoria` (`nombre`) VALUES
  ('Trabajo'),
  ('Personal'),
  ('Importante'),
  ('Salud'),
  ('Estudio');
