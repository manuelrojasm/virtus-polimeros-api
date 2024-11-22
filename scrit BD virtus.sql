-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8 ;
-- -----------------------------------------------------
-- Schema virtusplasticos
-- -----------------------------------------------------
USE `mydb` ;

-- -----------------------------------------------------
-- Table `mydb`.`Contacto`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`Contacto` (
  `idContacto` INT NOT NULL AUTO_INCREMENT,
  `Nombre` VARCHAR(100) NULL,
  `Correo` VARCHAR(100) NULL,
  `Mensaje` VARCHAR(250) NULL,
  `FechaCreación` DATETIME NOT NULL,
  `Estado` TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (`idContacto`),
  UNIQUE INDEX `idContacto_UNIQUE` (`idContacto` ASC) VISIBLE)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`Rol`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`Rol` (
  `idRol` INT NOT NULL AUTO_INCREMENT,
  `Rol` VARCHAR(45) NULL,
  `FechaCreacion` DATETIME NULL,
  `Estado` TINYINT NULL,
  PRIMARY KEY (`idRol`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`Usuario`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`Usuario` (
  `idUsuario` INT NOT NULL AUTO_INCREMENT,
  `idRol` INT NOT NULL,
  `PrimerNombre` VARCHAR(45) NOT NULL,
  `SegundoNombre` VARCHAR(45) NULL,
  `PrimerApellido` VARCHAR(45) NOT NULL,
  `SegundoApellido` VARCHAR(45) NULL,
  `Celular` VARCHAR(45) NULL,
  `Edad` INT NULL,
  `Genero` VARCHAR(45) NULL,
  `Escolaridad` VARCHAR(45) NULL,
  `Ubicacion` VARCHAR(45) NULL,
  `Correo` VARCHAR(100) NOT NULL,
  `Contraseña` VARCHAR(250) NOT NULL,
  `Certificado` TINYINT NULL,
  `FechaCreacion` DATETIME NULL,
  `Estado` TINYINT NULL,
  PRIMARY KEY (`idUsuario`),
  INDEX `idRol_idx` (`idRol` ASC) VISIBLE,
  CONSTRAINT `idRol`
    FOREIGN KEY (`idRol`)
    REFERENCES `mydb`.`Rol` (`idRol`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`Publicacion`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`Publicacion` (
  `idPublicacion` INT NOT NULL AUTO_INCREMENT,
  `idUsuario` INT NOT NULL,
  `Titulo` VARCHAR(100) NULL,
  `Contenido` VARCHAR(250) NULL,
  `FechaCreacion` DATETIME NULL,
  `FechaActualizacion` DATETIME NULL,
  `Estado` TINYINT NULL,
  PRIMARY KEY (`idPublicacion`),
  INDEX `idUsuario_idx` (`idUsuario` ASC) VISIBLE,
  CONSTRAINT `idUsuario`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `mydb`.`Usuario` (`idUsuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`Comentario`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`Comentario` (
  `idComentario` INT NOT NULL AUTO_INCREMENT,
  `idPublicacion` INT NULL,
  `idUsuario` INT NULL,
  `Contenido` VARCHAR(150) NULL,
  `FechaCreacion` DATETIME NULL,
  `FechaActualizacion` DATETIME NULL,
  `Estado` TINYINT NULL,
  PRIMARY KEY (`idComentario`),
  INDEX `idPublicacion_idx` (`idPublicacion` ASC) VISIBLE,
  INDEX `idUsuario_idx` (`idUsuario` ASC) VISIBLE,
  CONSTRAINT `idPublicacion`
    FOREIGN KEY (`idPublicacion`)
    REFERENCES `mydb`.`Publicacion` (`idPublicacion`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `idUsuario`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `mydb`.`Usuario` (`idUsuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`Pregunta`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`Pregunta` (
  `idPregunta` INT NOT NULL AUTO_INCREMENT,
  `Pregunta` VARCHAR(45) NULL,
  `Descripcion` VARCHAR(45) NULL,
  `T‬ipo` VARCHAR(45) NULL,
  `FechaCreacion` DATETIME NULL,
  `FechaActualizacion` DATETIME NULL,
  `Estado` TINYINT NULL,
  PRIMARY KEY (`idPregunta`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`OpcionRespuesta`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`OpcionRespuesta` (
  `idOpcionRespuesta` INT NOT NULL,
  `idPregunta` INT NULL,
  `Respuesta` VARCHAR(45) NULL,
  `Correcta` VARCHAR(45) NULL,
  `FechaCreacion` DATETIME NULL,
  `Estado` TINYINT NULL,
  PRIMARY KEY (`idOpcionRespuesta`),
  INDEX `idPregunta_idx` (`idPregunta` ASC) VISIBLE,
  CONSTRAINT `idPregunta`
    FOREIGN KEY (`idPregunta`)
    REFERENCES `mydb`.`Pregunta` (`idPregunta`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`RespuestaUsuarios`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`RespuestaUsuarios` (
  `idRespuestaUsuarios` INT NOT NULL,
  `idPregunta` INT NULL,
  `idUsuario` INT NULL,
  `idOpcionRespuesta` INT NOT NULL,
  `RespuestaTexto` VARCHAR(45) NULL,
  `FechaCreacion` DATETIME NULL,
  PRIMARY KEY (`idRespuestaUsuarios`),
  INDEX `idPregunta_idx` (`idPregunta` ASC) VISIBLE,
  INDEX `idUsuario_idx` (`idUsuario` ASC) VISIBLE,
  INDEX `fk_RespuestaUsuarios_OpcionRespuesta1_idx` (`idOpcionRespuesta` ASC) VISIBLE,
  CONSTRAINT `idPregunta`
    FOREIGN KEY (`idPregunta`)
    REFERENCES `mydb`.`Pregunta` (`idPregunta`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `idUsuario`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `mydb`.`Usuario` (`idUsuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `idOpcionRespuesta`
    FOREIGN KEY (`idOpcionRespuesta`)
    REFERENCES `mydb`.`OpcionRespuesta` (`idOpcionRespuesta`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`CalificacionUsuario`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`CalificacionUsuario` (
  `idCalificacionUsuario` INT NOT NULL,
  `idUsuario` INT NOT NULL,
  `Puntaje` VARCHAR(45) NULL,
  `Aprobo` TINYINT NULL,
  `FechaCreacion` DATETIME NULL,
  `Estado` TINYINT NULL,
  PRIMARY KEY (`idCalificacionUsuario`),
  INDEX `fk_CalificacionUsuario_Usuario1_idx` (`idUsuario` ASC) VISIBLE,
  CONSTRAINT `fk_CalificacionUsuario_Usuario1`
    FOREIGN KEY (`idUsuario`)
    REFERENCES `mydb`.`Usuario` (`idUsuario`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
