-- phpMyAdmin SQL Dump
-- version 4.0.5
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Авг 20 2013 г., 10:59
-- Версия сервера: 5.6.13
-- Версия PHP: 5.5.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `ldap_squid`
--
CREATE DATABASE IF NOT EXISTS `ldap_squid`;
USE `ldap_squid`;
-- --------------------------------------------------------

--
-- Структура таблицы `patterns`
--

CREATE TABLE IF NOT EXISTS `patterns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `traffic` int(11) NOT NULL DEFAULT '0',
  `access` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;


-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `name` text NOT NULL,
  `ip` int(10) unsigned DEFAULT NULL,
  `trafficForDay` float NOT NULL DEFAULT '0',
  `pattern_id` int(11) NOT NULL,
  `lastUpdate` double NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `pattern_id` (`pattern_id`),
  KEY `login` (`login`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`pattern_id`) REFERENCES `patterns` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `usersTraffic`
--

CREATE TABLE IF NOT EXISTS `usersTraffic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(50) NOT NULL,
  `site` text,
  `bytes` int(11) NOT NULL,
  `dateTime` double NOT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`),
  KEY `dateTime` (`dateTime`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

--
-- Структура таблицы `denySites`
--
CREATE TABLE IF NOT EXISTS `denySites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;


--
-- Дамп данных таблицы `patterns`
--
INSERT IGNORE INTO `patterns` (`id`, `name`, `traffic`, `access`) VALUES
  (1, 'default', 0, 0);

GRANT USAGE ON *.* TO 'ldap_squid'@'localhost' IDENTIFIED BY PASSWORD '*AA1420F182E88B9E5F874F6FBE7459291E8F4601';
GRANT ALL PRIVILEGES ON `ldap\_squid`.* TO 'ldap_squid'@'localhost' WITH GRANT OPTION;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(45) NOT NULL ,
  `addUsers` TINYINT NOT NULL ,
  `editUsers` TINYINT NOT NULL ,
  `deleteUsers` TINYINT NOT NULL ,
  `createPatterns` TINYINT NOT NULL ,
  `editPatterns` TINYINT NOT NULL ,
  `deletePatterns` TINYINT NOT NULL ,
  `addDenySites` TINYINT NOT NULL ,
  `deleteDenySites` TINYINT NOT NULL ,
  `createAdmins` TINYINT NOT NULL ,
  `editAdmins` TINYINT NOT NULL ,
  `deleteAdmins` TINYINT NOT NULL ,
  `createPermissions` TINYINT NOT NULL ,
  `editPermissions` TINYINT NOT NULL ,
  `deletePermissions` TINYINT NOT NULL ,
  PRIMARY KEY (`id`) );

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `login` VARCHAR(45) NOT NULL ,
  `password` VARCHAR(45) NOT NULL ,
  `permission_id` INT NOT NULL ,
  PRIMARY KEY (`id`) ,
  INDEX `permission_id_idx` (`permission_id` ASC) ,
  CONSTRAINT `permission_id`
  FOREIGN KEY (`permission_id` )
  REFERENCES `ldap_squid`.`permissions` (`id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);