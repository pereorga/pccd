-- MariaDB dump 10.19  Distrib 10.5.22-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: pccd
-- ------------------------------------------------------
-- Server version	10.5.22-MariaDB-1:10.5.22+maria~ubu2004

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `00_EDITORIA`
--

DROP TABLE IF EXISTS `00_EDITORIA`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `00_EDITORIA` (
  `CODI` varchar(3) DEFAULT NULL,
  `NOM` varchar(300) DEFAULT NULL,
  `DATA_ENTR` datetime DEFAULT NULL,
  `ADREÇA` varchar(300) DEFAULT NULL,
  `MUNICIPI` varchar(300) DEFAULT NULL,
  `CODI_POST` varchar(5) DEFAULT NULL,
  `TELEFON` varchar(10) DEFAULT NULL,
  `FAX` varchar(10) DEFAULT NULL,
  `EMAIL` varchar(300) DEFAULT NULL,
  `INTERNET` varchar(300) DEFAULT NULL,
  `CONTACTE` varchar(300) DEFAULT NULL,
  `DARRER_CAT` datetime DEFAULT NULL,
  `OBSERVACIO` varchar(300) DEFAULT NULL,
  KEY `CODI` (`CODI`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `00_EQUIVALENTS`
--

DROP TABLE IF EXISTS `00_EQUIVALENTS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `00_EQUIVALENTS` (
  `CODI` varchar(300) NOT NULL,
  `IDIOMA` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `00_FONTS`
--

DROP TABLE IF EXISTS `00_FONTS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `00_FONTS` (
  `Comptador` int(11) NOT NULL AUTO_INCREMENT,
  `Identificador` varchar(300) DEFAULT NULL,
  `CODI_RML` varchar(300) DEFAULT NULL,
  `Autor` varchar(300) DEFAULT NULL,
  `Any` varchar(10) DEFAULT NULL,
  `Títol` varchar(300) DEFAULT NULL,
  `ISBN` varchar(50) DEFAULT NULL,
  `Codi_edit` varchar(3) DEFAULT NULL,
  `Editorial` varchar(300) DEFAULT NULL,
  `Municipi` varchar(300) DEFAULT NULL,
  `Edició` varchar(25) DEFAULT NULL,
  `Any_edició` int(11) DEFAULT NULL,
  `Collecció` varchar(300) DEFAULT NULL,
  `Núm_collecció` varchar(300) DEFAULT NULL,
  `Pàgines` int(11) DEFAULT NULL,
  `Idioma` varchar(300) DEFAULT NULL,
  `Varietat_dialectal` varchar(300) DEFAULT NULL,
  `Registres` int(11) DEFAULT NULL,
  `Preu` float DEFAULT NULL,
  `Data_compra` date DEFAULT NULL,
  `Lloc_compra` varchar(300) DEFAULT NULL,
  `Imatge` varchar(300) DEFAULT NULL,
  `URL` varchar(300) DEFAULT NULL,
  `Observacions` text DEFAULT NULL,
  `WIDTH` int(11) NOT NULL DEFAULT 0,
  `HEIGHT` int(11) NOT NULL DEFAULT 0,
  UNIQUE KEY `Comptador` (`Comptador`),
  KEY `Identificador` (`Identificador`)
) ENGINE=InnoDB AUTO_INCREMENT=525 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `00_IMATGES`
--

DROP TABLE IF EXISTS `00_IMATGES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `00_IMATGES` (
  `Comptador` int(11) NOT NULL AUTO_INCREMENT,
  `Identificador` varchar(300) DEFAULT NULL,
  `TIPUS` varchar(1) DEFAULT NULL,
  `MODISME` varchar(300) DEFAULT NULL,
  `PAREMIOTIPUS` varchar(300) DEFAULT NULL,
  `IDIOMA` varchar(300) DEFAULT NULL,
  `EQUIVALENT` varchar(300) DEFAULT NULL,
  `LLOC` varchar(300) DEFAULT NULL,
  `DESCRIPCIO` varchar(300) DEFAULT NULL,
  `AUTOR` varchar(300) DEFAULT NULL,
  `ANY` float DEFAULT NULL,
  `EDITORIAL` varchar(3) DEFAULT NULL,
  `DIARI` varchar(300) DEFAULT NULL,
  `ARTICLE` varchar(200) DEFAULT NULL,
  `PAGINA` varchar(10) DEFAULT NULL,
  `URL_ENLLAÇ` varchar(300) DEFAULT NULL,
  `TIPUS_IMATGE` varchar(300) DEFAULT NULL,
  `URL_IMATGE` varchar(300) DEFAULT NULL,
  `OBSERVACIONS` varchar(300) DEFAULT NULL,
  `DATA` date DEFAULT NULL,
  `WIDTH` int(11) NOT NULL DEFAULT 0,
  `HEIGHT` int(11) NOT NULL DEFAULT 0,
  UNIQUE KEY `Comptador` (`Comptador`),
  KEY `PAREMIOTIPUS` (`PAREMIOTIPUS`)
) ENGINE=InnoDB AUTO_INCREMENT=4953 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `00_OBRESVPR`
--

DROP TABLE IF EXISTS `00_OBRESVPR`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `00_OBRESVPR` (
  `Comptador` int(11) NOT NULL,
  `Identificador` varchar(300) DEFAULT NULL,
  `Autor` varchar(300) DEFAULT NULL,
  `Any` varchar(10) DEFAULT NULL,
  `Títol` varchar(300) DEFAULT NULL,
  `ISBN` varchar(50) DEFAULT NULL,
  `Codi_edit` varchar(3) DEFAULT NULL,
  `Editorial` varchar(300) DEFAULT NULL,
  `Municipi` varchar(300) DEFAULT NULL,
  `Edició` varchar(25) DEFAULT NULL,
  `Any_edició` int(11) DEFAULT NULL,
  `Collecció` varchar(300) DEFAULT NULL,
  `Núm_collecció` varchar(300) DEFAULT NULL,
  `Pàgines` int(11) DEFAULT NULL,
  `Idioma` varchar(300) DEFAULT NULL,
  `Preu` float DEFAULT NULL,
  `Imatge` varchar(300) DEFAULT NULL,
  `URL` varchar(300) DEFAULT NULL,
  `WIDTH` int(11) NOT NULL DEFAULT 0,
  `HEIGHT` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `00_PAREMIOTIPUS`
--

DROP TABLE IF EXISTS `00_PAREMIOTIPUS`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `00_PAREMIOTIPUS` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `TIPUS` varchar(1) DEFAULT NULL,
  `MODISME` varchar(300) DEFAULT NULL,
  `PAREMIOTIPUS` varchar(300) DEFAULT NULL,
  `SINONIM` varchar(300) DEFAULT NULL,
  `IDIOMA` varchar(300) DEFAULT NULL,
  `EQUIVALENT` varchar(300) DEFAULT NULL,
  `LLOC` varchar(300) DEFAULT NULL,
  `AUTORIA` varchar(300) DEFAULT NULL,
  `FONT` varchar(300) DEFAULT NULL,
  `EXPLICACIO` varchar(300) DEFAULT NULL,
  `EXPLICACIO2` varchar(300) DEFAULT NULL,
  `EXEMPLES` varchar(300) DEFAULT NULL,
  `AUTOR` varchar(300) DEFAULT NULL,
  `ANY` float DEFAULT NULL,
  `EDITORIAL` varchar(3) DEFAULT NULL,
  `ID_FONT` varchar(300) DEFAULT NULL,
  `DIARI` varchar(300) DEFAULT NULL,
  `ARTICLE` varchar(200) DEFAULT NULL,
  `PAGINA` varchar(10) DEFAULT NULL,
  `NUM_ORDRE` varchar(300) DEFAULT NULL,
  `DATA` date DEFAULT NULL,
  `PAREMIOTIPUS_LC_WA` varchar(300) DEFAULT NULL,
  `MODISME_LC_WA` varchar(300) DEFAULT NULL,
  `SINONIM_LC_WA` varchar(300) DEFAULT NULL,
  `EQUIVALENT_LC_WA` varchar(300) DEFAULT NULL,
  `ACCEPCIO` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `Id` (`Id`),
  KEY `PAREMIOTIPUS` (`PAREMIOTIPUS`),
  KEY `MODISME` (`MODISME`),
  KEY `ID_FONT` (`ID_FONT`),
  FULLTEXT KEY `PAREMIOTIPUS_LC_WA` (`PAREMIOTIPUS_LC_WA`),
  FULLTEXT KEY `PAREMIOTIPUS_LC_WA_2` (`PAREMIOTIPUS_LC_WA`,`MODISME_LC_WA`),
  FULLTEXT KEY `PAREMIOTIPUS_LC_WA_3` (`PAREMIOTIPUS_LC_WA`,`SINONIM_LC_WA`),
  FULLTEXT KEY `PAREMIOTIPUS_LC_WA_4` (`PAREMIOTIPUS_LC_WA`,`EQUIVALENT_LC_WA`),
  FULLTEXT KEY `PAREMIOTIPUS_LC_WA_5` (`PAREMIOTIPUS_LC_WA`,`MODISME_LC_WA`,`SINONIM_LC_WA`),
  FULLTEXT KEY `PAREMIOTIPUS_LC_WA_6` (`PAREMIOTIPUS_LC_WA`,`MODISME_LC_WA`,`EQUIVALENT_LC_WA`),
  FULLTEXT KEY `PAREMIOTIPUS_LC_WA_7` (`PAREMIOTIPUS_LC_WA`,`SINONIM_LC_WA`,`EQUIVALENT_LC_WA`),
  FULLTEXT KEY `PAREMIOTIPUS_LC_WA_8` (`PAREMIOTIPUS_LC_WA`,`MODISME_LC_WA`,`SINONIM_LC_WA`,`EQUIVALENT_LC_WA`)
) ENGINE=InnoDB AUTO_INCREMENT=770537 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `common_paremiotipus`
--

DROP TABLE IF EXISTS `common_paremiotipus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `common_paremiotipus` (
  `Paremiotipus` varchar(300) DEFAULT NULL,
  `Compt` int(11) DEFAULT NULL,
  KEY `Compt` (`Compt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `commonvoice`
--

DROP TABLE IF EXISTS `commonvoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `commonvoice` (
  `paremiotipus` varchar(300) NOT NULL,
  `file` varchar(200) NOT NULL,
  PRIMARY KEY (`paremiotipus`,`file`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `paremiotipus_display`
--

DROP TABLE IF EXISTS `paremiotipus_display`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paremiotipus_display` (
  `Paremiotipus` varchar(300) NOT NULL,
  `Display` varchar(300) DEFAULT NULL,
  PRIMARY KEY (`Paremiotipus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pccd_is_installed`
--

DROP TABLE IF EXISTS `pccd_is_installed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pccd_is_installed` (
  `id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
