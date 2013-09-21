SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

CREATE SCHEMA IF NOT EXISTS `strimsowe_boty` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

USE `strimsowe_boty`;

-- -----------------------------------------------------
-- Table `strimsowe_boty`.`listings`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `listings` (
  `listing_id` INT NOT NULL AUTO_INCREMENT ,
  `strim` VARCHAR(100) NOT NULL ,
  `url` TEXT NOT NULL ,
  `url_md5` VARCHAR(32) NULL ,
  `title` VARCHAR(150) NOT NULL ,
  `extras` TEXT NULL DEFAULT NULL ,
  `already_listed` TINYINT NULL DEFAULT 0 ,
  `added` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ,
  PRIMARY KEY (`listing_id`) ,
  UNIQUE INDEX `unique_strimurl` (`strim` ASC, `url_md5` ASC) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `strimsowe_boty`.`strimy`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `strimy` (
  `strim` VARCHAR(100) NOT NULL ,
  `last_update` DATETIME NULL DEFAULT NULL ,
  PRIMARY KEY (`strim`) )
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
