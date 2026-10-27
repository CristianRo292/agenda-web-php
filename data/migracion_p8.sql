-- 1. Respaldo de seguridad
CREATE TABLE eventos_respaldo_p8 AS SELECT * FROM eventos;

ALTER TABLE eventos DROP COLUMN categoria;

-- 2. Crear tabla de usuarios
CREATE TABLE usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(80)  NOT NULL,
  correo     VARCHAR(120) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  creado_en  DATETIME     DEFAULT CURRENT_TIMESTAMP
);

-- 3. Agregar columna usuario_id a eventos (permite nulos temporalmente)
ALTER TABLE eventos ADD COLUMN usuario_id INT NULL AFTER id;

-- 4. Asignar eventos existentes a tu usuario. 
-- ¡IMPORTANTE! Cambia el '1' por el ID real de tu usuario recién creado.
UPDATE eventos SET usuario_id = 1 WHERE id > 0;

-- 5. Hacer la columna obligatoria y agregar la llave foránea con CASCADE
ALTER TABLE eventos
  MODIFY usuario_id INT NOT NULL,
  ADD CONSTRAINT fk_eventos_usuario
      FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
      ON DELETE CASCADE;