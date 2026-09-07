-- =============================================================================
-- Relación 1:N — usuario (1) → noticias_eventos (N)
-- Un usuario puede crear muchas noticias/eventos.
-- Base de datos: mydb
-- =============================================================================

USE `mydb`;

-- -----------------------------------------------------------------------------
-- 1. Columna foránea en noticias_eventos
-- -----------------------------------------------------------------------------
ALTER TABLE `noticias_eventos`
  ADD COLUMN `idUsuario` INT NULL
  COMMENT 'Usuario que creó la noticia o evento'
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

UPDATE `noticias_eventos`
SET `idUsuario` = @id_usuario_creador
WHERE `idUsuario` IS NULL;

-- -----------------------------------------------------------------------------
-- 3. Obligatorio y clave foránea
-- -----------------------------------------------------------------------------
ALTER TABLE `noticias_eventos`
  MODIFY COLUMN `idUsuario` INT NOT NULL;

ALTER TABLE `noticias_eventos`
  ADD INDEX `fk_noticias_eventos_usuario_idx` (`idUsuario` ASC),
  ADD CONSTRAINT `fk_noticias_eventos_usuario`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `usuario` (`idUsuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION;

-- =============================================================================
-- Verificación (opcional)
-- =============================================================================
-- SELECT ne.Id, ne.Titulo, ne.idUsuario, u.PrimerNombre, u.PrimerApellido
-- FROM noticias_eventos ne
-- INNER JOIN usuario u ON u.idUsuario = ne.idUsuario;

-- =============================================================================
-- Revertir (solo si necesitas deshacer)
-- =============================================================================
-- ALTER TABLE `noticias_eventos` DROP FOREIGN KEY `fk_noticias_eventos_usuario`;
-- ALTER TABLE `noticias_eventos` DROP INDEX `fk_noticias_eventos_usuario_idx`;
-- ALTER TABLE `noticias_eventos` DROP COLUMN `idUsuario`;
