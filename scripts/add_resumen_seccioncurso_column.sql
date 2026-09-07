-- =============================================================================
-- Añadir columna Resumen (texto: resumen generado por IA a partir del PDF)
-- Base de datos: mydb (ajusta USE si tu instancia usa otro nombre)
-- Tabla: seccioncurso
-- =============================================================================

USE `mydb`;

-- Comprueba que la columna no exista antes de ejecutar (opcional)
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_SCHEMA = 'mydb' AND TABLE_NAME = 'seccioncurso' AND COLUMN_NAME = 'Resumen';

ALTER TABLE `seccioncurso`
  ADD COLUMN `Resumen` TEXT NULL
  COMMENT 'Resumen del PDF generado por IA (Claude), usado para sugerir preguntas'
  AFTER `RutaArchivo`;

-- =============================================================================
-- Revertir cambio (solo si necesitas deshacer):
-- ALTER TABLE `seccioncurso` DROP COLUMN `Resumen`;
-- =============================================================================
