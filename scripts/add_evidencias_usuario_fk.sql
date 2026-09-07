-- =============================================================================
-- Relación 1:N — usuario (1) → evidencias (N)
-- Un usuario puede crear muchas evidencias.
-- Base de datos: mydb
-- =============================================================================

USE `mydb`;

-- -----------------------------------------------------------------------------
-- 1. Columna foránea en evidencias
-- -----------------------------------------------------------------------------
ALTER TABLE `evidencias`
  ADD COLUMN `idUsuario` INT NULL
  COMMENT 'Usuario que creó la evidencia'
  AFTER `Estado`;

-- -----------------------------------------------------------------------------
-- 2. Rellenar registros existentes (asignar al primer administrador, idRol = 2)
--    Si no hay admin, se usa el usuario con idUsuario más bajo.
--    Ajusta el UPDATE manualmente si necesitas otro criterio.
-- -----------------------------------------------------------------------------
SET @id_usuario_creador = (
  SELECT `idUsuario`
  FROM `usuario`
  WHERE `idRol` = 2
  ORDER BY `idUsuario` ASC
  LIMIT 1
);

SET @id_usuario_creador = IFNULL(
  @id_usuario_creador,
  (SELECT MIN(`idUsuario`) FROM `usuario`)
);

UPDATE `evidencias`
SET `idUsuario` = @id_usuario_creador
WHERE `idUsuario` IS NULL;

-- -----------------------------------------------------------------------------
-- 3. Obligatorio y clave foránea
-- -----------------------------------------------------------------------------
ALTER TABLE `evidencias`
  MODIFY COLUMN `idUsuario` INT NOT NULL;

ALTER TABLE `evidencias`
  ADD INDEX `fk_evidencias_usuario_idx` (`idUsuario` ASC),
  ADD CONSTRAINT `fk_evidencias_usuario`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `usuario` (`idUsuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION;

-- =============================================================================
-- Verificación (opcional)
-- =============================================================================
-- SELECT e.idevidencias, e.Titular, e.idUsuario, u.PrimerNombre, u.PrimerApellido
-- FROM evidencias e
-- INNER JOIN usuario u ON u.idUsuario = e.idUsuario;

-- =============================================================================
-- Revertir (solo si necesitas deshacer)
-- =============================================================================
-- ALTER TABLE `evidencias` DROP FOREIGN KEY `fk_evidencias_usuario`;
-- ALTER TABLE `evidencias` DROP INDEX `fk_evidencias_usuario_idx`;
-- ALTER TABLE `evidencias` DROP COLUMN `idUsuario`;
