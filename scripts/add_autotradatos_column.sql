-- =============================================================================
-- Añadir columna AutoTraDatos (booleano: 0 = false, 1 = true)
-- Base de datos: mydb (ajusta USE si tu instancia usa otro nombre)
-- Tabla: usuario
-- =============================================================================

USE `mydb`;

-- Comprueba que la columna no exista antes de ejecutar (opcional)
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = 'mydb' AND TABLE_NAME = 'usuario' AND COLUMN_NAME = 'AutoTraDatos';

ALTER TABLE `usuario`
  ADD COLUMN `AutoTraDatos` TINYINT(1) NOT NULL DEFAULT 0
  COMMENT 'Traspaso automático de datos: 0=false, 1=true'
  AFTER `Certificado`;

-- Valores explícitos (opcional):
-- UPDATE `usuario` SET `AutoTraDatos` = 1 WHERE `idUsuario` = 1;
-- UPDATE `usuario` SET `AutoTraDatos` = 0 WHERE `idUsuario` = 2;

-- =============================================================================
-- Revertir cambio (solo si necesitas deshacer):
-- ALTER TABLE `usuario` DROP COLUMN `AutoTraDatos`;
-- =============================================================================
