-- MySQL dump 10.13  Distrib 8.3.0, for Linux (x86_64)
--
-- Host: localhost    Database: scpper
-- ------------------------------------------------------
-- Server version	8.3.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `authors`
--

DROP TABLE IF EXISTS `authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `authors` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `PageId` int NOT NULL,
  `UserId` int NOT NULL,
  `RoleId` int unsigned NOT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `PageId_RoleId_UserId_UNIQUE` (`PageId`,`UserId`,`RoleId`),
  KEY `FK_authors_PageId_idx` (`PageId`),
  KEY `FK_authors_UserId_idx` (`UserId`),
  KEY `FK_authors_RoleId_idx` (`RoleId`),
  CONSTRAINT `FK_authors_PageId` FOREIGN KEY (`PageId`) REFERENCES `page_status` (`PageId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_authors_RoleId` FOREIGN KEY (`RoleId`) REFERENCES `dict_authorship` (`CodeId`) ON UPDATE CASCADE,
  CONSTRAINT `FK_authors_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3685288 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `WikidotId` int NOT NULL,
  `Name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `SiteId` int NOT NULL,
  `Ignored` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `WikidotId_INDEX` (`WikidotId`),
  KEY `FK_Categories_SiteId_idx` (`SiteId`),
  CONSTRAINT `FK_Categories_SiteId` FOREIGN KEY (`SiteId`) REFERENCES `sites` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=405831 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dict_authorship`
--

DROP TABLE IF EXISTS `dict_authorship`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dict_authorship` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `CodeId` int unsigned NOT NULL,
  `Name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `CodeId_UNIQUE` (`CodeId`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dict_page_kind`
--

DROP TABLE IF EXISTS `dict_page_kind`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dict_page_kind` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `KindId` int NOT NULL,
  `Description` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `Unique_KindId` (`KindId`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dict_status`
--

DROP TABLE IF EXISTS `dict_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dict_status` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `StatusId` int NOT NULL,
  `Name` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `StatusId_UNIQUE` (`StatusId`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fans`
--

DROP TABLE IF EXISTS `fans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fans` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `SiteId` int NOT NULL,
  `UserId` int NOT NULL,
  `AuthorId` int NOT NULL,
  `Positive` int DEFAULT '0',
  `Negative` int DEFAULT '0',
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `SiteId_UserId_AuthorId_UNIQUE` (`SiteId`,`UserId`,`AuthorId`),
  KEY `FK_UserId_idx` (`UserId`),
  KEY `FK_AuthorId_idx` (`AuthorId`),
  CONSTRAINT `FK_fans_AuthorId` FOREIGN KEY (`AuthorId`) REFERENCES `users` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_fans_SiteId` FOREIGN KEY (`SiteId`) REFERENCES `sites` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_fans_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2529694309 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `membership`
--

DROP TABLE IF EXISTS `membership`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `membership` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `SiteId` int NOT NULL,
  `UserId` int NOT NULL,
  `JoinDate` datetime DEFAULT NULL,
  `SummaryRating` int DEFAULT NULL,
  `AdjustedRating` double DEFAULT NULL,
  `AdjustedWeight` double DEFAULT NULL,
  `CleanRating` int DEFAULT NULL,
  `Aborted` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `SiteId_UserId_UNIQUE` (`SiteId`,`UserId`),
  KEY `SiteId_INDEX` (`SiteId`),
  KEY `UserId_INDEX` (`UserId`),
  CONSTRAINT `FK_site_users_SiteId` FOREIGN KEY (`SiteId`) REFERENCES `sites` (`WikidotId`),
  CONSTRAINT `FK_site_users_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`WikidotId`)
) ENGINE=InnoDB AUTO_INCREMENT=442726 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `page_reports`
--

DROP TABLE IF EXISTS `page_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_reports` (
  `Id` bigint NOT NULL AUTO_INCREMENT,
  `PageId` int NOT NULL,
  `Reporter` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `StatusId` int NOT NULL,
  `OriginalId` int DEFAULT NULL,
  `KindId` int NOT NULL,
  `Contributors` varchar(10000) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `ReportState` tinyint(1) DEFAULT '0',
  `Date` datetime DEFAULT NULL,
  PRIMARY KEY (`Id`),
  KEY `FK_page_reports_pageid_idx` (`PageId`),
  KEY `FK_page_reports_originalid_idx` (`OriginalId`),
  KEY `FK_page_reports_statusid_idx` (`StatusId`),
  KEY `FK_page_reports_kindid_idx` (`KindId`),
  CONSTRAINT `FK_page_reports_kindid` FOREIGN KEY (`KindId`) REFERENCES `dict_page_kind` (`KindId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_page_reports_originalid` FOREIGN KEY (`OriginalId`) REFERENCES `pages` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_page_reports_pageid` FOREIGN KEY (`PageId`) REFERENCES `pages` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_page_reports_statusid` FOREIGN KEY (`StatusId`) REFERENCES `dict_status` (`StatusId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4834 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `page_reports_BEFORE_INSERT` BEFORE INSERT ON `page_reports` FOR EACH ROW BEGIN
  IF @DISABLE_TRIGGERS IS NULL THEN
    SET NEW.Date = Now();
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;

--
-- Table structure for table `page_requests`
--

DROP TABLE IF EXISTS `page_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_requests` (
  `__Id` bigint NOT NULL AUTO_INCREMENT,
  `PageId` int NOT NULL,
  `Host` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `Count` int DEFAULT '1',
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `PageId_Host_Unique` (`PageId`,`Host`),
  KEY `FK_page_requests_PageId_idx` (`PageId`),
  CONSTRAINT `FK_page_requests_PageId` FOREIGN KEY (`PageId`) REFERENCES `pages` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=230962 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `page_status`
--

DROP TABLE IF EXISTS `page_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_status` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `PageId` int NOT NULL,
  `StatusId` int DEFAULT '1',
  `OriginalId` int DEFAULT NULL,
  `Fixed` tinyint(1) DEFAULT NULL,
  `KindId` int DEFAULT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `PageId_UNIQUE` (`PageId`),
  KEY `OriginalId_INDEX` (`OriginalId`),
  KEY `FK_page_status_StatusId_idx` (`StatusId`),
  KEY `FK_page_status_kindid_idx` (`KindId`),
  KEY `KindID_INDEX` (`KindId`),
  CONSTRAINT `FK_page_status_kindid` FOREIGN KEY (`KindId`) REFERENCES `dict_page_kind` (`KindId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_page_status_originalid` FOREIGN KEY (`OriginalId`) REFERENCES `pages` (`WikidotId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `page_status_ibfk_1` FOREIGN KEY (`PageId`) REFERENCES `pages` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=197731 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `page_status_BEFORE_UPDATE` BEFORE UPDATE ON `page_status` FOR EACH ROW BEGIN
  DECLARE Orig INT;
  DECLARE msg varchar(1000);
  IF @DISABLE_TRIGGERS IS NULL THEN
	  IF NEW.StatusId = 1 THEN
	    SET NEW.OriginalId = NULL;
	  END IF;  
	  SET Orig = New.OriginalId;
	  WHILE Orig IS NOT NULL AND Orig <> New.PageId do
		SET Orig = (SELECT OriginalID FROM page_status WHERE PageId = Orig);
	  END WHILE;
	  IF Orig = New.PageId THEN
		SET msg = CONCAT('Cannot SET parent page ', New.OriginalId, ' for page ', New.PageId, '. Loop detected.');
		 SIGNAL SQLSTATE '45000'
		  SET MESSAGE_TEXT = msg;
	  END IF;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;

--
-- Table structure for table `page_summary`
--

DROP TABLE IF EXISTS `page_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `page_summary` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `PageId` int NOT NULL,
  `Rating` int DEFAULT NULL,
  `CleanRating` int DEFAULT NULL,
  `Revisions` int DEFAULT NULL,
  `ContributorRating` int DEFAULT NULL,
  `AdjustedRating` int DEFAULT NULL,
  `WilsonScore` double DEFAULT NULL,
  `MonthRating` int DEFAULT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `PageId_UNIQUE` (`PageId`),
  KEY `CleanRating_INDEX` (`CleanRating`),
  CONSTRAINT `FK_PageId` FOREIGN KEY (`PageId`) REFERENCES `pages` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=234724586 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `SiteId` int NOT NULL,
  `WikidotId` int NOT NULL,
  `Title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Name` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `CategoryId` int DEFAULT NULL,
  `Source` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `AltTitle` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `Deleted` tinyint(1) NOT NULL DEFAULT '0',
  `LastUpdate` datetime DEFAULT NULL,
  `HideSource` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `WikidotId_UNIQUE` (`WikidotId`),
  KEY `FK_SiteId_INDEX` (`SiteId`),
  KEY `FK_Pages_CategoryId_idx` (`CategoryId`),
  KEY `Name_INDEX` (`Name`(191)),
  FULLTEXT KEY `Fulltext_INDEX` (`Title`,`AltTitle`,`Name`),
  CONSTRAINT `FK_Pages_CategoryId` FOREIGN KEY (`CategoryId`) REFERENCES `categories` (`WikidotId`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_Pages_SiteId` FOREIGN KEY (`SiteId`) REFERENCES `sites` (`WikidotId`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=236762 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `pages_BEFORE_UPDATE` BEFORE UPDATE ON `pages` FOR EACH ROW BEGIN
  IF (NEW.Deleted = 1) AND (OLD.Deleted = 0) THEN
	SET NEW.LastUpdate = (SELECT LastUpdate FROM sites WHERE WikidotId = Old.SiteId);
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;

--
-- Table structure for table `revisions`
--

DROP TABLE IF EXISTS `revisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `revisions` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `WikidotId` int NOT NULL,
  `PageId` int NOT NULL,
  `RevisionIndex` int unsigned NOT NULL,
  `UserId` int NOT NULL,
  `DateTime` datetime NOT NULL,
  `Comments` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `WikidotId_UNIQUE` (`WikidotId`),
  KEY `PageId_INDEX` (`RevisionIndex`),
  KEY `FK_revisions_PageId_idx` (`PageId`),
  KEY `FK_revisions_UserId_idx` (`UserId`),
  KEY `RevisionIndex_INDEX` (`RevisionIndex`),
  KEY `DateTime_INDEX` (`DateTime`),
  CONSTRAINT `FK_revisions_PageId` FOREIGN KEY (`PageId`) REFERENCES `pages` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_revisions_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`WikidotId`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2313390 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `revisions_AFTER_INSERT` AFTER INSERT ON `revisions` FOR EACH ROW BEGIN
	DECLARE OriginalId, SiteId INT;
    DECLARE PageName NVARCHAR(256);
    IF @DISABLE_TRIGGERS IS NULL THEN
		IF New.RevisionIndex = 0 THEN
            SET PageName = (SELECT p.Name FROM pages p WHERE p.WikidotId = New.PageId);
            SET SiteId = (SELECT p.SiteId FROM pages p WHERE p.WikidotId = New.PageId);
			SET OriginalId = (SELECT p.WikidotId FROM pages p 
			  INNER JOIN revisions r ON p.WikidotId = r.PageId AND r.RevisionIndex = 0
              LEFT JOIN page_status ps ON p.WikidotId = ps.PageId
 			  WHERE p.Name = PageName AND (ps.StatusId = 1 or p.WikidotId = New.PageId) AND p.Deleted = 0 AND p.SiteId <> SiteId
              ORDER BY r.DateTime ASC LIMIT 1);
			  
			IF (OriginalId IS NOT NULL) AND (OriginalId <> New.PageId) THEN
			  INSERT INTO page_status (PageId, StatusId, OriginalId) VALUES (New.PageId, 2, OriginalId);
			ELSE
			  INSERT INTO page_status (PageId, StatusId) VALUES (New.PageId, 1);
			END IF;        
            
			INSERT INTO authors (PageId, UserId, RoleId)
			  SELECT New.PageId, New.UserId, CASE WHEN ps.StatusId = 1 THEN 1 ELSE 3 END
			  FROM page_status ps WHERE ps.PageId = New.PageId;
		END IF;
		
		
		
	END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;

--
-- Table structure for table `scpper_users`
--

DROP TABLE IF EXISTS `scpper_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scpper_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user` varchar(45) NOT NULL,
  `email` varchar(80) NOT NULL,
  `password` varchar(256) NOT NULL,
  `groupId` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_UNIQUE` (`user`),
  UNIQUE KEY `email_UNIQUE` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `site_stats`
--

DROP TABLE IF EXISTS `site_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_stats` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `SiteId` int NOT NULL,
  `Members` int DEFAULT NULL,
  `ActiveMembers` int DEFAULT NULL,
  `Contributors` int DEFAULT NULL,
  `Authors` int DEFAULT NULL,
  `Pages` int DEFAULT NULL,
  `Originals` int DEFAULT NULL,
  `Translations` int DEFAULT NULL,
  `Votes` int DEFAULT NULL,
  `Positive` int DEFAULT NULL,
  `Negative` int DEFAULT NULL,
  `Revisions` int DEFAULT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `SiteId_UNIQUE` (`SiteId`),
  CONSTRAINT `FK_SiteStats_SiteId` FOREIGN KEY (`SiteId`) REFERENCES `sites` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sites` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `WikidotId` int NOT NULL,
  `EnglishName` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NativeName` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ShortName` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `WikidotName` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Domain` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `DefaultLanguage` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN',
  `LastUpdate` datetime DEFAULT NULL,
  `HideVotes` tinyint(1) DEFAULT '0',
  `Protocol` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'http',
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `WikidotId_UNIQUE` (`WikidotId`),
  UNIQUE KEY `ShortName_UNIQUE` (`ShortName`),
  UNIQUE KEY `Domain_UNIQUE` (`Domain`),
  UNIQUE KEY `WikidotName_UNIQUE` (`WikidotName`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `PageId` int NOT NULL,
  `Tag` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `PageId_Tag_UNIQUE` (`PageId`,`Tag`),
  KEY `PageId_INDEX` (`PageId`),
  CONSTRAINT `FK_tags_PageId` FOREIGN KEY (`PageId`) REFERENCES `pages` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=77709938 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `tags_AFTER_INSERT` AFTER INSERT ON `tags` FOR EACH ROW BEGIN
	UPDATE page_status SET KindId = NULL WHERE PageId = New.PageId AND (Fixed IS NULL OR Fixed <> 1);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `tags_AFTER_DELETE` AFTER DELETE ON `tags` FOR EACH ROW BEGIN
	UPDATE page_status SET KindId = NULL WHERE PageId = Old.PageId AND (Fixed IS NULL OR Fixed <> 1);
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;

--
-- Table structure for table `user_activity`
--

DROP TABLE IF EXISTS `user_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_activity` (
  `__Id` int unsigned NOT NULL AUTO_INCREMENT,
  `UserId` int NOT NULL,
  `SiteId` int NOT NULL,
  `Votes` int DEFAULT NULL,
  `Revisions` int DEFAULT NULL,
  `Pages` int DEFAULT NULL,
  `LastActivity` datetime DEFAULT NULL,
  `TotalRating` int DEFAULT NULL,
  `VotesSumm` int DEFAULT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `__Id_UNIQUE` (`__Id`),
  UNIQUE KEY `UserId_SiteId_UNIQUE` (`UserId`,`SiteId`),
  KEY `UserId_INDEX` (`UserId`),
  KEY `SiteId_INDEX` (`SiteId`),
  KEY `LastActivity_INDEX` (`LastActivity`),
  CONSTRAINT `FK_SiteId` FOREIGN KEY (`SiteId`) REFERENCES `sites` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1184219773 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `WikidotName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `RegistrationDate` date DEFAULT NULL,
  `WikidotId` int NOT NULL,
  `Deleted` tinyint(1) DEFAULT NULL,
  `DisplayName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `Id` (`__Id`),
  UNIQUE KEY `WikidotId_UNIQUE` (`WikidotId`)
) ENGINE=InnoDB AUTO_INCREMENT=193913 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary view structure for view `view_all_pages`
--

DROP TABLE IF EXISTS `view_all_pages`;
/*!50001 DROP VIEW IF EXISTS `view_all_pages`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_all_pages` AS SELECT 
 1 AS `__Id`,
 1 AS `SiteId`,
 1 AS `PageId`,
 1 AS `CategoryId`,
 1 AS `SiteName`,
 1 AS `Site`,
 1 AS `PageName`,
 1 AS `Title`,
 1 AS `Source`,
 1 AS `CreationDate`,
 1 AS `Rating`,
 1 AS `CleanRating`,
 1 AS `ContributorRating`,
 1 AS `AdjustedRating`,
 1 AS `Revisions`,
 1 AS `StatusId`,
 1 AS `KindId`,
 1 AS `Status`,
 1 AS `Kind`,
 1 AS `OriginalId`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_authors`
--

DROP TABLE IF EXISTS `view_authors`;
/*!50001 DROP VIEW IF EXISTS `view_authors`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_authors` AS SELECT 
 1 AS `SiteId`,
 1 AS `Site`,
 1 AS `SiteName`,
 1 AS `PageId`,
 1 AS `PageName`,
 1 AS `PageTitle`,
 1 AS `PageDeleted`,
 1 AS `CategoryName`,
 1 AS `StatusId`,
 1 AS `KindId`,
 1 AS `Status`,
 1 AS `Rating`,
 1 AS `Role`,
 1 AS `RoleId`,
 1 AS `UserName`,
 1 AS `UserDisplayName`,
 1 AS `UserDeleted`,
 1 AS `UserId`,
 1 AS `Rated`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_categories`
--

DROP TABLE IF EXISTS `view_categories`;
/*!50001 DROP VIEW IF EXISTS `view_categories`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_categories` AS SELECT 
 1 AS `__Id`,
 1 AS `SiteId`,
 1 AS `CategoryId`,
 1 AS `Name`,
 1 AS `Ignored`,
 1 AS `SiteName`,
 1 AS `Site`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_fans`
--

DROP TABLE IF EXISTS `view_fans`;
/*!50001 DROP VIEW IF EXISTS `view_fans`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_fans` AS SELECT 
 1 AS `SiteId`,
 1 AS `UserId`,
 1 AS `UserName`,
 1 AS `UserDisplayName`,
 1 AS `UserDeleted`,
 1 AS `AuthorId`,
 1 AS `AuthorName`,
 1 AS `AuthorDisplayName`,
 1 AS `AuthorDeleted`,
 1 AS `Positive`,
 1 AS `Negative`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_membership`
--

DROP TABLE IF EXISTS `view_membership`;
/*!50001 DROP VIEW IF EXISTS `view_membership`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_membership` AS SELECT 
 1 AS `__Id`,
 1 AS `SiteId`,
 1 AS `SiteNativeName`,
 1 AS `Site`,
 1 AS `SiteEnglishName`,
 1 AS `UserId`,
 1 AS `UserName`,
 1 AS `DisplayName`,
 1 AS `JoinDate`,
 1 AS `LastActivity`,
 1 AS `Votes`,
 1 AS `Revisions`,
 1 AS `Pages`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_page_reports`
--

DROP TABLE IF EXISTS `view_page_reports`;
/*!50001 DROP VIEW IF EXISTS `view_page_reports`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_page_reports` AS SELECT 
 1 AS `Id`,
 1 AS `PageId`,
 1 AS `Reporter`,
 1 AS `StatusId`,
 1 AS `OriginalId`,
 1 AS `KindId`,
 1 AS `Contributors`,
 1 AS `Date`,
 1 AS `ReportState`,
 1 AS `SiteId`,
 1 AS `SiteName`,
 1 AS `PageName`,
 1 AS `Title`,
 1 AS `OldStatusId`,
 1 AS `OldStatus`,
 1 AS `OldKindId`,
 1 AS `OldKind`,
 1 AS `OldOriginalId`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_page_requests`
--

DROP TABLE IF EXISTS `view_page_requests`;
/*!50001 DROP VIEW IF EXISTS `view_page_requests`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_page_requests` AS SELECT 
 1 AS `Site`,
 1 AS `PageName`,
 1 AS `Title`,
 1 AS `Host`,
 1 AS `Count`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_pages`
--

DROP TABLE IF EXISTS `view_pages`;
/*!50001 DROP VIEW IF EXISTS `view_pages`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_pages` AS SELECT 
 1 AS `__Id`,
 1 AS `SiteId`,
 1 AS `PageId`,
 1 AS `CategoryId`,
 1 AS `SiteName`,
 1 AS `Site`,
 1 AS `PageName`,
 1 AS `Title`,
 1 AS `AltTitle`,
 1 AS `Source`,
 1 AS `HideSource`,
 1 AS `Deleted`,
 1 AS `LastUpdate`,
 1 AS `CreationDate`,
 1 AS `Rating`,
 1 AS `CleanRating`,
 1 AS `ContributorRating`,
 1 AS `AdjustedRating`,
 1 AS `WilsonScore`,
 1 AS `MonthRating`,
 1 AS `Revisions`,
 1 AS `StatusId`,
 1 AS `KindId`,
 1 AS `Status`,
 1 AS `Kind`,
 1 AS `OriginalId`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_pages_all`
--

DROP TABLE IF EXISTS `view_pages_all`;
/*!50001 DROP VIEW IF EXISTS `view_pages_all`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_pages_all` AS SELECT 
 1 AS `__Id`,
 1 AS `SiteId`,
 1 AS `PageId`,
 1 AS `CategoryId`,
 1 AS `SiteName`,
 1 AS `Site`,
 1 AS `PageName`,
 1 AS `Title`,
 1 AS `AltTitle`,
 1 AS `Source`,
 1 AS `HideSource`,
 1 AS `Deleted`,
 1 AS `LastUpdate`,
 1 AS `CreationDate`,
 1 AS `Rating`,
 1 AS `CleanRating`,
 1 AS `ContributorRating`,
 1 AS `AdjustedRating`,
 1 AS `WilsonScore`,
 1 AS `MonthRating`,
 1 AS `Revisions`,
 1 AS `StatusId`,
 1 AS `KindId`,
 1 AS `Status`,
 1 AS `Kind`,
 1 AS `OriginalId`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_pages_deleted`
--

DROP TABLE IF EXISTS `view_pages_deleted`;
/*!50001 DROP VIEW IF EXISTS `view_pages_deleted`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_pages_deleted` AS SELECT 
 1 AS `__Id`,
 1 AS `SiteId`,
 1 AS `PageId`,
 1 AS `CategoryId`,
 1 AS `SiteName`,
 1 AS `Site`,
 1 AS `PageName`,
 1 AS `Title`,
 1 AS `AltTitle`,
 1 AS `Source`,
 1 AS `HideSource`,
 1 AS `Deleted`,
 1 AS `LastUpdate`,
 1 AS `CreationDate`,
 1 AS `Rating`,
 1 AS `CleanRating`,
 1 AS `ContributorRating`,
 1 AS `AdjustedRating`,
 1 AS `WilsonScore`,
 1 AS `MonthRating`,
 1 AS `Revisions`,
 1 AS `StatusId`,
 1 AS `KindId`,
 1 AS `Status`,
 1 AS `Kind`,
 1 AS `OriginalId`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_revisions`
--

DROP TABLE IF EXISTS `view_revisions`;
/*!50001 DROP VIEW IF EXISTS `view_revisions`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_revisions` AS SELECT 
 1 AS `__Id`,
 1 AS `RevisionId`,
 1 AS `PageId`,
 1 AS `RevisionIndex`,
 1 AS `PageName`,
 1 AS `PageTitle`,
 1 AS `Deleted`,
 1 AS `SiteId`,
 1 AS `SiteName`,
 1 AS `Site`,
 1 AS `UserId`,
 1 AS `UserWikidotName`,
 1 AS `UserDisplayName`,
 1 AS `UserDeleted`,
 1 AS `DateTime`,
 1 AS `Comments`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_revisions_all`
--

DROP TABLE IF EXISTS `view_revisions_all`;
/*!50001 DROP VIEW IF EXISTS `view_revisions_all`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_revisions_all` AS SELECT 
 1 AS `__Id`,
 1 AS `RevisionId`,
 1 AS `PageId`,
 1 AS `RevisionIndex`,
 1 AS `PageName`,
 1 AS `PageTitle`,
 1 AS `Deleted`,
 1 AS `SiteId`,
 1 AS `SiteName`,
 1 AS `Site`,
 1 AS `UserId`,
 1 AS `UserWikidotName`,
 1 AS `UserDisplayName`,
 1 AS `UserDeleted`,
 1 AS `DateTime`,
 1 AS `Comments`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_sites`
--

DROP TABLE IF EXISTS `view_sites`;
/*!50001 DROP VIEW IF EXISTS `view_sites`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_sites` AS SELECT 
 1 AS `SiteId`,
 1 AS `EnglishName`,
 1 AS `NativeName`,
 1 AS `ShortName`,
 1 AS `WikidotName`,
 1 AS `Domain`,
 1 AS `DefaultLanguage`,
 1 AS `LastUpdate`,
 1 AS `HideVotes`,
 1 AS `Protocol`,
 1 AS `Members`,
 1 AS `ActiveMembers`,
 1 AS `Contributors`,
 1 AS `Authors`,
 1 AS `Pages`,
 1 AS `Originals`,
 1 AS `Translations`,
 1 AS `Votes`,
 1 AS `Positive`,
 1 AS `Negative`,
 1 AS `Revisions`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_tags`
--

DROP TABLE IF EXISTS `view_tags`;
/*!50001 DROP VIEW IF EXISTS `view_tags`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_tags` AS SELECT 
 1 AS `__Id`,
 1 AS `SiteId`,
 1 AS `SiteName`,
 1 AS `PageId`,
 1 AS `PageName`,
 1 AS `Tag`,
 1 AS `Deleted`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_user_activity`
--

DROP TABLE IF EXISTS `view_user_activity`;
/*!50001 DROP VIEW IF EXISTS `view_user_activity`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_user_activity` AS SELECT 
 1 AS `UserId`,
 1 AS `UserDisplayName`,
 1 AS `UserName`,
 1 AS `UserDeleted`,
 1 AS `SiteId`,
 1 AS `Votes`,
 1 AS `VotesSumm`,
 1 AS `Revisions`,
 1 AS `Pages`,
 1 AS `TotalRating`,
 1 AS `LastActivity`,
 1 AS `SiteEnglishName`,
 1 AS `SiteName`,
 1 AS `Site`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_users`
--

DROP TABLE IF EXISTS `view_users`;
/*!50001 DROP VIEW IF EXISTS `view_users`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_users` AS SELECT 
 1 AS `__Id`,
 1 AS `UserId`,
 1 AS `WikidotName`,
 1 AS `DisplayName`,
 1 AS `RegistrationDate`,
 1 AS `Deleted`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_vote_history_all`
--

DROP TABLE IF EXISTS `view_vote_history_all`;
/*!50001 DROP VIEW IF EXISTS `view_vote_history_all`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_vote_history_all` AS SELECT 
 1 AS `__Id`,
 1 AS `SiteId`,
 1 AS `SiteName`,
 1 AS `Site`,
 1 AS `PageId`,
 1 AS `PageName`,
 1 AS `PageTitle`,
 1 AS `Deleted`,
 1 AS `UserId`,
 1 AS `UserName`,
 1 AS `UserDisplayName`,
 1 AS `UserDeleted`,
 1 AS `Value`,
 1 AS `DateTime`,
 1 AS `DeltaFromPrev`,
 1 AS `FromMember`,
 1 AS `FromContributor`,
 1 AS `FromActive`,
 1 AS `IsPositive`,
 1 AS `IsNegative`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_votes`
--

DROP TABLE IF EXISTS `view_votes`;
/*!50001 DROP VIEW IF EXISTS `view_votes`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_votes` AS SELECT 
 1 AS `__Id`,
 1 AS `SiteId`,
 1 AS `SiteName`,
 1 AS `Site`,
 1 AS `PageId`,
 1 AS `PageName`,
 1 AS `PageTitle`,
 1 AS `Deleted`,
 1 AS `UserId`,
 1 AS `UserName`,
 1 AS `UserDisplayName`,
 1 AS `UserDeleted`,
 1 AS `Value`,
 1 AS `DateTime`,
 1 AS `DeltaFromPrev`,
 1 AS `FromMember`,
 1 AS `FromContributor`,
 1 AS `FromActive`,
 1 AS `IsPositive`,
 1 AS `IsNegative`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `view_votes_all`
--

DROP TABLE IF EXISTS `view_votes_all`;
/*!50001 DROP VIEW IF EXISTS `view_votes_all`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `view_votes_all` AS SELECT 
 1 AS `__Id`,
 1 AS `SiteId`,
 1 AS `SiteName`,
 1 AS `Site`,
 1 AS `PageId`,
 1 AS `PageName`,
 1 AS `PageTitle`,
 1 AS `Deleted`,
 1 AS `UserId`,
 1 AS `UserName`,
 1 AS `UserDisplayName`,
 1 AS `UserDeleted`,
 1 AS `Value`,
 1 AS `DateTime`,
 1 AS `DeltaFromPrev`,
 1 AS `FromMember`,
 1 AS `FromContributor`,
 1 AS `FromActive`,
 1 AS `IsPositive`,
 1 AS `IsNegative`*/;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `vote_history`
--

DROP TABLE IF EXISTS `vote_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vote_history` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `PageId` int NOT NULL,
  `UserId` int NOT NULL,
  `Value` tinyint(1) DEFAULT NULL,
  `DateTime` datetime DEFAULT NULL,
  `DeltaFromPrev` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `Page_User_UNIQUE` (`PageId`,`UserId`,`DateTime`),
  KEY `PageId_INDEX` (`PageId`),
  KEY `UserId_INDEX` (`UserId`),
  KEY `Time_INDEX` (`DateTime`),
  CONSTRAINT `FK_vote_history_PageId` FOREIGN KEY (`PageId`) REFERENCES `pages` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_vote_history_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`WikidotId`)
) ENGINE=InnoDB AUTO_INCREMENT=35890 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `votes`
--

DROP TABLE IF EXISTS `votes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `votes` (
  `__Id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `PageId` int NOT NULL,
  `UserId` int NOT NULL,
  `Value` tinyint(1) DEFAULT NULL,
  `DateTime` datetime DEFAULT NULL,
  `DeltaFromPrev` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`__Id`),
  UNIQUE KEY `Page_User_UNIQUE` (`PageId`,`UserId`),
  KEY `PageId_INDEX` (`PageId`),
  KEY `UserId_INDEX` (`UserId`),
  KEY `Time_INDEX` (`DateTime`),
  CONSTRAINT `FK_Votes_PageId` FOREIGN KEY (`PageId`) REFERENCES `pages` (`WikidotId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_Votes_UserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`WikidotId`)
) ENGINE=InnoDB AUTO_INCREMENT=1383473192 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `votes_BEFORE_INSERT` BEFORE INSERT ON `votes` FOR EACH ROW BEGIN
  IF @DISABLE_TRIGGERS IS NULL THEN
    SET NEW.DateTime = Now();
    SET New.DeltaFromPrev = New.Value;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `votes_BEFORE_UPDATE` BEFORE UPDATE ON `votes` FOR EACH ROW BEGIN
  IF @DISABLE_TRIGGERS IS NULL THEN
    IF OLD.Value <> NEW.Value THEN
      INSERT INTO vote_history (`PageId`, `UserId`, `Value`, `DateTime`, `DeltaFromPrev`) VALUES (OLD.PageId, OLD.UserId, Old.Value, Old.DateTime, Old.DeltaFromPrev);
      SET New.DateTime = Now();
      SET New.DeltaFromPrev = New.Value - Old.Value;
    END IF;
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;

--
-- Dumping events for database 'scpper'
--

--
-- Dumping routines for database 'scpper'
--
/*!50003 DROP FUNCTION IF EXISTS `CI_LOWER_BOUND` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE FUNCTION `CI_LOWER_BOUND`(Positive INT, Negative INT) RETURNS float
BEGIN
RETURN ((Positive + 1.9208) / (Positive + Negative) -
                   1.96 * SQRT((Positive * Negative) / (Positive + Negative) + 0.9604) /
                          (Positive + Negative)) / (1 + 3.8416 / (Positive + Negative));
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ADD_SITE` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE PROCEDURE `ADD_SITE`(in ASiteId int, in ACategoryId int, in AEnglishName varchar(256), in ANativeName varchar(256), in AShortName varchar(10), in AWikidotName varchar(256), in ADomain varchar(256), in ADefaultLanguage varchar(10))
BEGIN
 INSERT INTO sites (WikidotId, EnglishName, NativeName, ShortName, WikidotName, Domain, DefaultLanguage) VALUES (ASiteId, AEnglishName, ANativeName, AShortName, AWikidotName, ADomain, ADefaultLanguage);
 INSERT INTO site_stats (SiteId) VALUES (ASiteId);
 INSERT INTO categories (WikidotId, Name, SiteId) VALUES (ACategoryId, '_default', ASiteId); 
 CALL UPDATE_PAGE_SUMMARY(ASiteId);
 CALL UPDATE_USER_ACTIVITY(ASiteId);
 CALL UPDATE_PAGE_SUMMARY(ASiteId);
 CALL UPDATE_USER_ACTIVITY(ASiteId); 
 CALL UPDATE_SITE_STATS(ASiteId);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `debug_msg` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `debug_msg`(enabled INTEGER, msg VARCHAR(255))
BEGIN
  IF enabled THEN BEGIN
    select concat("** ", msg) AS '** DEBUG:';
  END; END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `FILL_PAGE_KINDS_DE` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `FILL_PAGE_KINDS_DE`()
BEGIN
	UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 2 WHERE p.SiteId = 1269857 AND KindId IS NULL AND t.Tag = 'geschichte';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 3 WHERE p.SiteId = 1269857 AND KindId IS NULL AND t.Tag = 'witz';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 10 WHERE p.SiteId = 1269857 AND KindId IS NULL AND t.Tag = 'autor';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 4 WHERE p.SiteId = 1269857 AND KindId IS NULL AND t.Tag = 'artwork';    
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 5 WHERE p.SiteId = 1269857 AND KindId IS NULL AND t.Tag = 'goi-sichtweise';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 8 WHERE p.SiteId = 1269857 AND KindId IS NULL AND t.Tag = 'abhandlung';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 1 WHERE p.SiteId = 1269857 AND KindId IS NULL AND t.Tag = 'scp';
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `FILL_PAGE_KINDS_EN` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `FILL_PAGE_KINDS_EN`()
BEGIN
	UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 2 WHERE p.SiteId = 66711 AND KindId IS NULL AND t.Tag = 'tale';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 3 WHERE p.SiteId = 66711 AND KindId IS NULL AND t.Tag = 'joke';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 10 WHERE p.SiteId = 66711 AND KindId IS NULL AND t.Tag = 'author';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 4 WHERE p.SiteId = 66711 AND KindId IS NULL AND t.Tag = 'artwork';    
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 5 WHERE p.SiteId = 66711 AND KindId IS NULL AND t.Tag = 'goi-format';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 8 WHERE p.SiteId = 66711 AND KindId IS NULL AND t.Tag = 'essay';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 6 WHERE p.SiteId = 66711 AND KindId IS NULL AND t.Tag IN ('supplement', 'archived', 'explained');
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 1 WHERE p.SiteId = 66711 AND KindId IS NULL AND t.Tag = 'scp';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 9 WHERE p.SiteId = 66711 AND KindId IS NULL AND t.Tag = 'audio';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 7 WHERE p.SiteId = 66711 AND KindId IS NULL AND t.Tag <> 'in-deletion';
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `FILL_PAGE_KINDS_FR` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `FILL_PAGE_KINDS_FR`()
BEGIN
	UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 2 WHERE p.SiteId = 464696 AND KindId IS NULL AND t.Tag = 'conte';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 3 WHERE p.SiteId = 464696 AND KindId IS NULL AND t.Tag = 'humour';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 10 WHERE p.SiteId = 464696 AND KindId IS NULL AND t.Tag = 'auteur';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 4 WHERE p.SiteId = 464696 AND KindId IS NULL AND t.Tag = 'fanart';    
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 5 WHERE p.SiteId = 464696 AND KindId IS NULL AND t.Tag = 'format-gdi';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 8 WHERE p.SiteId = 464696 AND KindId IS NULL AND t.Tag = 'essai';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 1 WHERE p.SiteId = 464696 AND KindId IS NULL AND t.Tag = 'scp';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 9 WHERE p.SiteId = 464696 AND KindId IS NULL AND t.Tag = 'audio';
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `FILL_PAGE_KINDS_KO` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `FILL_PAGE_KINDS_KO`()
BEGIN
	UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 2 WHERE p.SiteId = 486864 AND KindId IS NULL AND t.Tag = '이야기';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 3 WHERE p.SiteId = 486864 AND KindId IS NULL AND t.Tag = '농담';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 10 WHERE p.SiteId = 486864 AND KindId IS NULL AND t.Tag = '작가';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 4 WHERE p.SiteId = 486864 AND KindId IS NULL AND t.Tag = '아트워크';    
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 5 WHERE p.SiteId = 486864 AND KindId IS NULL AND t.Tag = '요주의단체-서식';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 8 WHERE p.SiteId = 486864 AND KindId IS NULL AND t.Tag = '에세이';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 1 WHERE p.SiteId = 486864 AND KindId IS NULL AND t.Tag = 'scp';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 9 WHERE p.SiteId = 486864 AND KindId IS NULL AND t.Tag = '음성첨부';
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `FILL_PAGE_KINDS_RU` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE PROCEDURE `FILL_PAGE_KINDS_RU`()
BEGIN
	UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN page_status o ON ps.OriginalId = o.PageId SET ps.KindId = o.KindId WHERE p.SiteId = 169125 AND o.KindId IS NOT NULL;
	UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 2 WHERE p.SiteId = 169125 AND KindId IS NULL AND t.Tag = 'рассказ';
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 3 WHERE p.SiteId = 169125 AND KindId IS NULL AND t.Tag = 'шуточный';    
    UPDATE page_status ps INNER JOIN pages p ON ps.PageId = p.WikidotId INNER JOIN tags t ON ps.PageId = t.PageId SET KindId = 1 WHERE p.SiteId = 169125 AND KindId IS NULL AND t.Tag = 'объект';
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `FIX_MISSING_MEMBERSHIP` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `FIX_MISSING_MEMBERSHIP`(in ASiteId INT)
BEGIN
  INSERT INTO membership (SiteId, UserId, JoinDate)
    SELECT s.WikidotId, ua.UserId, ua.LastActivity
    FROM sites s
    INNER JOIN user_activity ua on s.WikidotId = ua.SiteId
    WHERE s.WikidotId = ASiteId AND ua.LastActivity >= s.LastUpdate
  ON DUPLICATE KEY UPDATE Aborted = 0;
  CALL SET_LATEST_POSSIBLE_JOIN_DATES(ASiteId);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `FIX_NON_TRANSLATIONS_EN` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE PROCEDURE `FIX_NON_TRANSLATIONS_EN`()
BEGIN
DROP TABLE IF EXISTS tmp ;
CREATE TABLE tmp (PageId int);

INSERT INTO tmp(
SELECT p.PageId FROM view_pages p 
LEFT JOIN tags t ON p.PageId = t.PageId AND t.Tag = 'translation'
WHERE p.Site='en' AND p.Status = 'Translation' and t.Tag IS NULL);

UPDATE page_status ps SET StatusId = 1, OriginalId = NULL WHERE PageId IN (SELECT PageId FROM tmp);
UPDATE authors a SET RoleId = 1 WHERE RoleId = 3 AND PageId IN (SELECT PageId FROM tmp);

DROP TABLE IF EXISTS tmp ;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `FIX_PAGE_STATUS` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `FIX_PAGE_STATUS`(in PageId int)
BEGIN
  DECLARE OriginalId, SiteId int;
  DECLARE PageName NVARCHAR(256);
  SET PageName = (SELECT p.Name FROM pages p WHERE p.WikidotId = PageId);
  SET SiteId = (SELECT p.SiteId FROM pages p WHERE p.WikidotId = PageId);
  SET OriginalId = (
    SELECT p.WikidotId FROM pages p 
    INNER JOIN revisions r ON p.WikidotId = r.PageId AND r.RevisionIndex = 0
    LEFT JOIN page_status ps ON p.WikidotId = ps.PageId
    WHERE p.Name = PageName AND (ps.StatusId = 1 or p.WikidotId = PageId) AND p.Deleted = 0 ORDER BY r.DateTime ASC LIMIT 1);
  call debug_msg(@enabled, CONCAT('PageId = ', PageId));
  call debug_msg(@enabled, CONCAT('PageName = ', PageName));
  call debug_msg(@enabled, CONCAT('SiteId = ', SiteId));
  call debug_msg(@enabled, CONCAT('OriginalId = ', OriginalId));
			  
  IF (OriginalId IS NOT NULL) AND (OriginalId <> PageId) THEN
    UPDATE page_status p SET StatusId = 2, p.OriginalId = OriginalId WHERE p.PageId = PageId;
    UPDATE authors a SET RoleId = 3 WHERE a.PageId = PageId AND a.RoleId = 1;
  ELSE
    UPDATE page_status p SET StatusId = 1, p.OriginalId = NULL WHERE p.PageId = PageId;
    UPDATE authors a SET RoleId = 1 WHERE a.PageId = PageId AND a.RoleId = 3;
  END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `FIX_SITE_STATUS` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE PROCEDURE `FIX_SITE_STATUS`(in SiteId int)
BEGIN
  DECLARE Finished INT DEFAULT 0;
  DECLARE PageId INT;
  DECLARE SitePages CURSOR FOR 
	SELECT WikidotId FROM pages p WHERE p.SiteId = SiteId;  
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET Finished = 1;
  
  OPEN SitePages;
  
  all_pages: LOOP
    FETCH SitePages INTO PageId;
    IF Finished = 1 THEN
	  LEAVE all_pages;
	END IF;
    CALL FIX_PAGE_STATUS(PageId);
  END LOOP all_pages;
  
  CLOSE SitePages;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `FIX_VOTE_DATES` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE PROCEDURE `FIX_VOTE_DATES`(in SiteId int, in PageName nvarchar(256))
BEGIN
UPDATE votes v 
INNER JOIN pages p ON v.PageId = p.WikidotId
INNER JOIN revisions r ON r.PageId = p.WikidotId AND r.RevisionIndex = 0
INNER JOIN users u ON v.UserId = u.WikidotId
LEFT JOIN  membership m ON u.WikidotId = m.UserId AND m.SiteId = p.SiteId
SET v.DateTime = GREATEST(COALESCE(m.JoinDate, r.DateTime), r.DateTime)
WHERE p.Name = PageName AND p.SiteId = SiteId;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `SET_CONTRIBUTORS` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE PROCEDURE `SET_CONTRIBUTORS`(APageId int, ARoleId int, UserIds varchar(1000))
proc_label:BEGIN
 DECLARE AUserId int;
 DECLARE i, Count, Pos int;
 DECLARE UserStr varchar(100);
 IF EXISTS(SELECT NULL FROM page_status WHERE PageId = APageId AND Fixed = 1) THEN
	LEAVE proc_label;
 END IF;
 DELETE FROM authors WHERE PageId = APageId AND RoleId = ARoleId;
 SET Count = 1;
 SET Pos = LOCATE(',', UserIds);
 WHILE Pos>0 DO
   SET Count = Count+1;
   SET Pos = LOCATE(',', UserIds, Pos+1);
 END WHILE; 
 SET i = 1;
 WHILE i <= Count DO	
	SET UserStr = trim(replace(substring(substring_index(UserIds, ',', i), length(substring_index(UserIds, ',', i - 1)) + 1), ',', ''));
    IF UserStr REGEXP '[[:digit:]]+' THEN
		SET AUserId = CAST(UserStr as UNSIGNED);
		IF AUserId > 0 THEN
			INSERT INTO authors (PageId, RoleId, UserId) VALUES (APageId, ARoleId, AUserId);
		END IF;
	END IF;
    SET i = i + 1;
 END WHILE;  
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `SET_LATEST_POSSIBLE_JOIN_DATES` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `SET_LATEST_POSSIBLE_JOIN_DATES`(in ASiteId int)
BEGIN
	UPDATE membership m
	INNER JOIN (SELECT m.UserId, m.SiteId, least(m.JoinDate, COALESCE(min(b.DateTime), now())) as JD 
		FROM membership m 
		INNER JOIN (    
			SELECT m.UserId, m.SiteId, v.DateTime
			FROM membership m
			INNER JOIN votes v ON m.UserId = v.UserId 
			INNER JOIN pages p ON v.PageId = p.WikidotId AND m.SiteId = p.SiteId
			WHERE m.SiteId = ASiteId
			UNION ALL
			SELECT m.UserId, m.SiteId, r.DateTime
			FROM membership m
			INNER JOIN revisions r ON m.UserId = r.UserId
			INNER JOIN pages p2 ON r.PageId = p2.WikidotId AND m.SiteId = p2.SiteId
			WHERE m.SiteId = ASiteId) b ON m.UserId = b.UserId AND m.SiteId = b.SiteId
		GROUP BY m.UserId, m.SiteId) a ON m.UserId = a.UserId AND m.SiteId = a.SiteId
	SET JoinDate = a.JD
	WHERE m.SiteId = ASiteId;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `UPDATE_FANS` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `UPDATE_FANS`(in UpdateSiteId int)
BEGIN
  DELETE FROM fans WHERE SiteId = UpdateSiteId;

  INSERT INTO fans (SiteId, UserId, AuthorId, Positive, Negative) (
    SELECT v.SiteId, v.UserId, a.UserId, Sum(v.IsPositive), Sum(v.IsNegative) 
    FROM view_votes v     
    INNER JOIN view_authors a on v.PageId = a.PageId
    WHERE a.SiteId = UpdateSiteId
    GROUP BY v.SiteId, v.UserId, a.UserId
  );  
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `UPDATE_PAGE_SUMMARY` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `UPDATE_PAGE_SUMMARY`(in UpdateSiteId INT)
BEGIN
  DELETE FROM page_summary WHERE PageId IN (SELECT WikidotId FROM pages WHERE SiteId = UpdateSiteId);
  INSERT INTO page_summary (PageId, Rating, CleanRating, ContributorRating, AdjustedRating, WilsonScore, MonthRating, Revisions)
    SELECT 
	  p.WikidotId as PageId,
      (
		SELECT SUM(Value) FROM view_votes_all 
        WHERE PageId = p.WikidotId
	  ) as Rating,
      (
        SELECT SUM(Value) FROM view_votes_all v 
        INNER JOIN membership m ON v.SiteId = m.SiteId AND v.UserId = m.UserId 
        WHERE v.PageId = p.WikidotId AND m.Aborted = 0
	  ) as CleanRating,
      (
        SELECT SUM(Value) FROM view_votes_all v 
        INNER JOIN membership m ON v.SiteId = m.SiteId AND v.UserId = m.UserId 
        INNER JOIN user_activity ua ON v.SiteId = ua.SiteId AND v.UserId = ua.UserId 
        WHERE v.PageId = p.WikidotId AND ua.Pages>0 AND m.Aborted = 0
	  ) as ContributorRating,
      (
        SELECT SUM(Value) FROM view_votes_all v 
        INNER JOIN membership m ON v.SiteId = m.SiteId AND v.UserId = m.UserId 
        INNER JOIN user_activity ua ON v.SiteId = ua.SiteId AND v.UserId = ua.UserId 
        WHERE v.PageId = p.WikidotId AND m.Aborted = 0 AND ua.LastActivity IS NOT NULL AND ua.LastActivity >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
	  ) as AdjustedRating,
      CI_LOWER_BOUND(
        (SELECT SUM(Value) FROM view_votes_all v 
        INNER JOIN membership m ON v.SiteId = m.SiteId AND v.UserId = m.UserId 
        WHERE v.PageId = p.WikidotId AND m.Aborted = 0 AND v.Value > 0),
        (SELECT COUNT(Value) FROM view_votes_all v 
        INNER JOIN membership m ON v.SiteId = m.SiteId AND v.UserId = m.UserId 
        WHERE v.PageId = p.WikidotId AND m.Aborted = 0 AND v.Value < 0)
      ) as WilsonScore,
      (
        SELECT SUM(Value) FROM view_votes_all v 
        INNER JOIN membership m ON v.SiteId = m.SiteId AND v.UserId = m.UserId 
        INNER JOIN revisions r ON v.PageId = r.PageId AND r.RevisionIndex = 0
        WHERE v.PageId = p.WikidotId AND m.Aborted = 0 AND datediff(v.DateTime, r.DateTime) <= 30
      ) as MonthRating,
      (SELECT COUNT(*) FROM revisions WHERE PageId = p.WikidotId) as Revisions
	FROM pages p
    WHERE p.SiteId = UpdateSiteId;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `UPDATE_SITE_STATS` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `UPDATE_SITE_STATS`(in ASiteId INT)
BEGIN
	UPDATE site_stats SET
		Members = (SELECT COUNT(*) FROM view_membership WHERE SiteId = ASiteId),
        ActiveMembers = (SELECT COUNT(*) FROM view_membership WHERE SiteId = ASiteId AND LastActivity > DATE_SUB(now(), INTERVAL 6 MONTH)),
        Contributors = (SELECT COUNT(DISTINCT r.UserId) FROM view_revisions r INNER JOIN view_membership m ON r.SiteId = m.SiteId AND r.UserId = m.UserId WHERE r.SiteId = ASiteId),
        Authors = (SELECT COUNT(DISTINCT a.UserId) FROM view_authors a INNER JOIN view_membership m ON a.SiteId = m.SiteId AND a.UserId = m.UserId WHERE a.SiteId = ASiteId AND a.PageDeleted <> 1),
        Pages = (SELECT COUNT(*) FROM view_pages WHERE SiteId = ASiteId),
        Originals = (SELECT COUNT(*) FROM view_pages WHERE SiteId = ASiteId AND StatusId = 1),
        Translations = (SELECT COUNT(*) FROM view_pages WHERE SiteId = ASiteId AND StatusId = 2),
        Votes = (SELECT COUNT(*) FROM view_votes WHERE SiteId = ASiteId),
        Positive = (SELECT COUNT(*) FROM view_votes WHERE SiteId = ASiteId AND Value > 0),
        Negative = (SELECT COUNT(*) FROM view_votes WHERE SiteId = ASiteId AND Value < 0),
        Revisions = (SELECT COUNT(*) FROM view_revisions WHERE SiteId = ASiteId)
	WHERE SiteId = ASiteId;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;
/*!50003 DROP PROCEDURE IF EXISTS `UPDATE_USER_ACTIVITY` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE PROCEDURE `UPDATE_USER_ACTIVITY`(IN UpdateSiteId INT)
BEGIN
    INSERT INTO user_activity (SiteId, UserId, Votes, VotesSumm, Revisions, Pages, TotalRating) 
    	SELECT 
			UpdateSiteId AS `SiteId`,
			`u`.`WikidotId` AS `UserId`,
			0,
            0,
			0,
			0,
            0
		FROM users u
        ON DUPLICATE KEY UPDATE Votes=0, VotesSumm=0, Revisions=0, Pages=0, TotalRating=0;

	UPDATE user_activity ua
		INNER JOIN (
			SELECT u.WikidotId as UserId,
			COUNT(*) as Votes,
            Sum(v.Value) as VotesSumm            
			FROM users u
			INNER JOIN votes v ON u.WikidotId = v.UserId
            INNER JOIN pages p ON v.PageId = p.WikidotId
			WHERE p.SiteId = UpdateSiteId AND not (p.Deleted = 1)
			GROUP BY u.WikidotId
            ) as vv
		ON ua.UserId = vv.UserId
		SET ua.Votes = vv.Votes, ua.VotesSumm = vv.VotesSumm
        WHERE ua.SiteId = UpdateSiteId;

	UPDATE user_activity ua
		INNER JOIN (
			SELECT u.WikidotId as UserId,
			COUNT(*) as Revs
			FROM users u
			INNER JOIN revisions r ON u.WikidotId = r.UserId
            INNER JOIN pages p ON r.PageId = p.WikidotId
			WHERE p.SiteId = UpdateSiteId AND not (p.Deleted = 1) AND r.RevisionIndex > 0
			GROUP BY u.WikidotId) as vv
		ON ua.UserId = vv.UserId
		SET ua.Revisions = vv.Revs
        WHERE ua.SiteId = UpdateSiteId;

	UPDATE user_activity ua
		INNER JOIN (
			SELECT va.UserId as UserId,
			COUNT(*) as Pages,
            Sum(Rating) as Rating
			FROM view_authors va
			WHERE va.SiteId = UpdateSiteId AND not (va.PageDeleted = 1) AND va.Rated = 1
			GROUP BY va.UserId) as vv
		ON ua.UserId = vv.UserId
		SET ua.Pages = vv.Pages, ua.TotalRating = vv.Rating
        WHERE ua.SiteId = UpdateSiteId;

	DROP TABLE IF EXISTS tmp;

	CREATE TEMPORARY TABLE tmp (
		UserId INT UNIQUE,
        LA datetime
    );
    
    INSERT INTO tmp SELECT UserId, MAX(DateTime) FROM votes v INNER JOIN pages p ON v.PageId = p.WikidotId WHERE `p`.`SiteId` = UpdateSiteId GROUP BY UserId;

	UPDATE user_activity SET LastActivity = (
		SELECT GREATEST(COALESCE(user_activity.LastActivity, FROM_UNIXTIME(0)), LA) FROM tmp WHERE `tmp`.`UserId` = user_activity.UserId)
	WHERE SiteId = UpdateSiteId;

	DELETE FROM tmp;
    
    INSERT INTO tmp SELECT UserId, MAX(DateTime) FROM view_revisions_all r WHERE `r`.`SiteId` = UpdateSiteId GROUP BY UserId;
    
    UPDATE user_activity
    INNER JOIN tmp t ON user_activity.UserId = t.UserId
    SET user_activity.LastActivity = GREATEST(COALESCE(user_activity.LastActivity, FROM_UNIXTIME(0)), COALESCE(t.LA, FROM_UNIXTIME(0)))
	WHERE SiteId = UpdateSiteId;    	
    
    CALL UPDATE_FANS(UpdateSiteId);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `WITH_EMULATOR` */;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = '' */ ;
DELIMITER ;;
CREATE PROCEDURE `WITH_EMULATOR`(
recursive_table varchar(100), 
initial_SELECT varchar(65530), 
recursive_SELECT varchar(65530), 
final_SELECT varchar(65530), 
max_recursion int unsigned, 
create_table_options varchar(65530) 


)
BEGIN
  declare new_rows int unsigned;
  declare show_progress int default 0; 
  declare recursive_table_next varchar(120);
  declare recursive_table_union varchar(120);
  declare recursive_table_tmp varchar(120);
  set recursive_table_next  = concat(recursive_table, "_next");
  set recursive_table_union = concat(recursive_table, "_union");
  set recursive_table_tmp   = concat(recursive_table, "_tmp");
  
  
  SET @str = 
    CONCAT("DROP TABLE IF EXISTS ", recursive_table);
  PREPARE stmt FROM @str;
  EXECUTE stmt;
  SET @str = 
    CONCAT("CREATE TEMPORARY TABLE ", recursive_table, " ",
    create_table_options, " AS ", initial_SELECT);
  PREPARE stmt FROM @str;
  EXECUTE stmt;
  SET @str = 
    CONCAT("DROP TABLE IF EXISTS ", recursive_table_union);
  PREPARE stmt FROM @str;
  EXECUTE stmt;  
  SET @str = 
    CONCAT("CREATE TEMPORARY TABLE ", recursive_table_union, " LIKE ", recursive_table);
  PREPARE stmt FROM @str;
  EXECUTE stmt;
  SET @str = 
    CONCAT("DROP TABLE IF EXISTS ", recursive_table_next);
  PREPARE stmt FROM @str;
  EXECUTE stmt;    
  SET @str = 
    CONCAT("CREATE TEMPORARY TABLE ", recursive_table_next, " LIKE ", recursive_table);
  PREPARE stmt FROM @str;
  EXECUTE stmt;
  if max_recursion = 0 then
    set max_recursion = 100; 
  end if;
  recursion: repeat
    
    SET @str =
      CONCAT("INSERT INTO ", recursive_table_union, " SELECT * FROM ", recursive_table);
    PREPARE stmt FROM @str;
    EXECUTE stmt;
    
    set max_recursion = max_recursion - 1;
    if not max_recursion then
      if show_progress then
        select concat("max recursion exceeded");
      end if;
      leave recursion;
    end if;
    
    SET @str =
      CONCAT("INSERT INTO ", recursive_table_next, " ", recursive_SELECT);
    PREPARE stmt FROM @str;
    EXECUTE stmt;
    
    select row_count() into new_rows;
    if show_progress then
      select concat(new_rows, " new rows found");
    end if;
    if not new_rows then
      leave recursion;
    end if;
    
    
    
    SET @str =
      CONCAT("ALTER TABLE ", recursive_table, " RENAME ", recursive_table_tmp);
    PREPARE stmt FROM @str;
    EXECUTE stmt;
    
    SET @str =
      CONCAT("ALTER TABLE ", recursive_table_next, " RENAME ", recursive_table);
    PREPARE stmt FROM @str;
    EXECUTE stmt;
    SET @str =
      CONCAT("ALTER TABLE ", recursive_table_tmp, " RENAME ", recursive_table_next);
    PREPARE stmt FROM @str;
    EXECUTE stmt;
    
    SET @str =
      CONCAT("TRUNCATE TABLE ", recursive_table_next);
    PREPARE stmt FROM @str;
    EXECUTE stmt;
  until 0 end repeat;
  
  SET @str =
    CONCAT("DROP TEMPORARY TABLE ", recursive_table_next, ", ", recursive_table);
  PREPARE stmt FROM @str;
  EXECUTE stmt;
  
  SET @str =
    CONCAT("ALTER TABLE ", recursive_table_union, " RENAME ", recursive_table);
  PREPARE stmt FROM @str;
  EXECUTE stmt;
  
  SET @str = final_SELECT;
  PREPARE stmt FROM @str;
  EXECUTE stmt;
  
  SET @str =
    CONCAT("DROP TEMPORARY TABLE ", recursive_table);
  PREPARE stmt FROM @str;
  EXECUTE stmt;
  
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
ALTER DATABASE `scpper` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;

--
-- Final view structure for view `view_all_pages`
--

/*!50001 DROP VIEW IF EXISTS `view_all_pages`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_all_pages` AS select `p`.`__Id` AS `__Id`,`p`.`SiteId` AS `SiteId`,`p`.`WikidotId` AS `PageId`,`p`.`CategoryId` AS `CategoryId`,`s`.`WikidotName` AS `SiteName`,`s`.`ShortName` AS `Site`,`p`.`Name` AS `PageName`,`p`.`Title` AS `Title`,`p`.`Source` AS `Source`,`r`.`DateTime` AS `CreationDate`,`ps`.`Rating` AS `Rating`,`ps`.`CleanRating` AS `CleanRating`,`ps`.`ContributorRating` AS `ContributorRating`,`ps`.`AdjustedRating` AS `AdjustedRating`,`ps`.`Revisions` AS `Revisions`,`pst`.`StatusId` AS `StatusId`,`pst`.`KindId` AS `KindId`,`ds`.`Name` AS `Status`,`dpk`.`Description` AS `Kind`,`pst`.`OriginalId` AS `OriginalId` from ((((((`pages` `p` join `sites` `s` on((`p`.`SiteId` = `s`.`WikidotId`))) join `revisions` `r` on(((`r`.`PageId` = `p`.`WikidotId`) and (`r`.`RevisionIndex` = 0)))) left join `page_summary` `ps` on((`p`.`WikidotId` = `ps`.`PageId`))) join `page_status` `pst` on((`p`.`WikidotId` = `pst`.`PageId`))) join `dict_status` `ds` on((`pst`.`StatusId` = `ds`.`StatusId`))) left join `dict_page_kind` `dpk` on((`pst`.`KindId` = `dpk`.`KindId`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_authors`
--

/*!50001 DROP VIEW IF EXISTS `view_authors`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_authors` AS select `s`.`WikidotId` AS `SiteId`,`s`.`ShortName` AS `Site`,`s`.`WikidotName` AS `SiteName`,`a`.`PageId` AS `PageId`,`p`.`Name` AS `PageName`,`p`.`Title` AS `PageTitle`,`p`.`Deleted` AS `PageDeleted`,`c`.`Name` AS `CategoryName`,`ps`.`StatusId` AS `StatusId`,`ps`.`KindId` AS `KindId`,`ds`.`Name` AS `Status`,`psum`.`CleanRating` AS `Rating`,`da`.`Name` AS `Role`,`a`.`RoleId` AS `RoleId`,`u`.`WikidotName` AS `UserName`,`u`.`DisplayName` AS `UserDisplayName`,`u`.`Deleted` AS `UserDeleted`,`a`.`UserId` AS `UserId`,(case when (((`ps`.`KindId` is null) or (`ps`.`KindId` <> 7)) and (`p`.`Deleted` <> 1) and (`c`.`Name` = '_default')) then 1 else 0 end) AS `Rated` from ((((((((`authors` `a` join `pages` `p` on((`a`.`PageId` = `p`.`WikidotId`))) join `categories` `c` on((`p`.`CategoryId` = `c`.`WikidotId`))) join `page_status` `ps` on((`a`.`PageId` = `ps`.`PageId`))) join `dict_status` `ds` on((`ps`.`StatusId` = `ds`.`StatusId`))) join `sites` `s` on((`p`.`SiteId` = `s`.`WikidotId`))) join `dict_authorship` `da` on((`a`.`RoleId` = `da`.`CodeId`))) join `users` `u` on((`a`.`UserId` = `u`.`WikidotId`))) join `page_summary` `psum` on((`a`.`PageId` = `psum`.`PageId`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_categories`
--

/*!50001 DROP VIEW IF EXISTS `view_categories`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_categories` AS select `c`.`__Id` AS `__Id`,`c`.`SiteId` AS `SiteId`,`c`.`WikidotId` AS `CategoryId`,`c`.`Name` AS `Name`,`c`.`Ignored` AS `Ignored`,`s`.`WikidotName` AS `SiteName`,`s`.`ShortName` AS `Site` from (`categories` `c` join `sites` `s` on((`c`.`SiteId` = `s`.`WikidotId`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_fans`
--

/*!50001 DROP VIEW IF EXISTS `view_fans`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_fans` AS select `f`.`SiteId` AS `SiteId`,`f`.`UserId` AS `UserId`,`u`.`WikidotName` AS `UserName`,`u`.`DisplayName` AS `UserDisplayName`,`u`.`Deleted` AS `UserDeleted`,`f`.`AuthorId` AS `AuthorId`,`a`.`WikidotName` AS `AuthorName`,`a`.`DisplayName` AS `AuthorDisplayName`,`a`.`Deleted` AS `AuthorDeleted`,`f`.`Positive` AS `Positive`,`f`.`Negative` AS `Negative` from ((`fans` `f` join `users` `a` on((`f`.`AuthorId` = `a`.`WikidotId`))) join `users` `u` on((`f`.`UserId` = `u`.`WikidotId`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_membership`
--

/*!50001 DROP VIEW IF EXISTS `view_membership`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_membership` AS select `m`.`__Id` AS `__Id`,`m`.`SiteId` AS `SiteId`,`s`.`NativeName` AS `SiteNativeName`,`s`.`WikidotName` AS `Site`,`s`.`EnglishName` AS `SiteEnglishName`,`m`.`UserId` AS `UserId`,`u`.`WikidotName` AS `UserName`,`u`.`DisplayName` AS `DisplayName`,`m`.`JoinDate` AS `JoinDate`,`ua`.`LastActivity` AS `LastActivity`,`ua`.`Votes` AS `Votes`,`ua`.`Revisions` AS `Revisions`,`ua`.`Pages` AS `Pages` from (((`membership` `m` join `sites` `s` on((`m`.`SiteId` = `s`.`WikidotId`))) join `users` `u` on((`m`.`UserId` = `u`.`WikidotId`))) join `user_activity` `ua` on(((`m`.`SiteId` = `ua`.`SiteId`) and (`m`.`UserId` = `ua`.`UserId`)))) where (`m`.`Aborted` = 0) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_page_reports`
--

/*!50001 DROP VIEW IF EXISTS `view_page_reports`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_page_reports` AS select `pr`.`Id` AS `Id`,`pr`.`PageId` AS `PageId`,`pr`.`Reporter` AS `Reporter`,`pr`.`StatusId` AS `StatusId`,`pr`.`OriginalId` AS `OriginalId`,`pr`.`KindId` AS `KindId`,`pr`.`Contributors` AS `Contributors`,`pr`.`Date` AS `Date`,`pr`.`ReportState` AS `ReportState`,`vp`.`SiteId` AS `SiteId`,`vp`.`SiteName` AS `SiteName`,`vp`.`PageName` AS `PageName`,`vp`.`Title` AS `Title`,`vp`.`StatusId` AS `OldStatusId`,`vp`.`Status` AS `OldStatus`,`vp`.`KindId` AS `OldKindId`,`vp`.`Kind` AS `OldKind`,`vp`.`OriginalId` AS `OldOriginalId` from (`page_reports` `pr` join `view_pages_all` `vp` on((`pr`.`PageId` = `vp`.`PageId`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_page_requests`
--

/*!50001 DROP VIEW IF EXISTS `view_page_requests`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_page_requests` AS select `p`.`Site` AS `Site`,`p`.`PageName` AS `PageName`,`p`.`Title` AS `Title`,`r`.`Host` AS `Host`,`r`.`Count` AS `Count` from (`page_requests` `r` join `view_pages_all` `p` on((`r`.`PageId` = `p`.`PageId`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_pages`
--

/*!50001 DROP VIEW IF EXISTS `view_pages`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_pages` AS select `view_pages_all`.`__Id` AS `__Id`,`view_pages_all`.`SiteId` AS `SiteId`,`view_pages_all`.`PageId` AS `PageId`,`view_pages_all`.`CategoryId` AS `CategoryId`,`view_pages_all`.`SiteName` AS `SiteName`,`view_pages_all`.`Site` AS `Site`,`view_pages_all`.`PageName` AS `PageName`,`view_pages_all`.`Title` AS `Title`,`view_pages_all`.`AltTitle` AS `AltTitle`,`view_pages_all`.`Source` AS `Source`,`view_pages_all`.`HideSource` AS `HideSource`,`view_pages_all`.`Deleted` AS `Deleted`,`view_pages_all`.`LastUpdate` AS `LastUpdate`,`view_pages_all`.`CreationDate` AS `CreationDate`,`view_pages_all`.`Rating` AS `Rating`,`view_pages_all`.`CleanRating` AS `CleanRating`,`view_pages_all`.`ContributorRating` AS `ContributorRating`,`view_pages_all`.`AdjustedRating` AS `AdjustedRating`,`view_pages_all`.`WilsonScore` AS `WilsonScore`,`view_pages_all`.`MonthRating` AS `MonthRating`,`view_pages_all`.`Revisions` AS `Revisions`,`view_pages_all`.`StatusId` AS `StatusId`,`view_pages_all`.`KindId` AS `KindId`,`view_pages_all`.`Status` AS `Status`,`view_pages_all`.`Kind` AS `Kind`,`view_pages_all`.`OriginalId` AS `OriginalId` from `view_pages_all` where (`view_pages_all`.`Deleted` <> 1) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_pages_all`
--

/*!50001 DROP VIEW IF EXISTS `view_pages_all`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_pages_all` AS select `p`.`__Id` AS `__Id`,`p`.`SiteId` AS `SiteId`,`p`.`WikidotId` AS `PageId`,`p`.`CategoryId` AS `CategoryId`,`s`.`WikidotName` AS `SiteName`,`s`.`ShortName` AS `Site`,`p`.`Name` AS `PageName`,`p`.`Title` AS `Title`,`p`.`AltTitle` AS `AltTitle`,`p`.`Source` AS `Source`,`p`.`HideSource` AS `HideSource`,`p`.`Deleted` AS `Deleted`,`p`.`LastUpdate` AS `LastUpdate`,`r`.`DateTime` AS `CreationDate`,`ps`.`Rating` AS `Rating`,`ps`.`CleanRating` AS `CleanRating`,`ps`.`ContributorRating` AS `ContributorRating`,`ps`.`AdjustedRating` AS `AdjustedRating`,`ps`.`WilsonScore` AS `WilsonScore`,`ps`.`MonthRating` AS `MonthRating`,`ps`.`Revisions` AS `Revisions`,`pst`.`StatusId` AS `StatusId`,`pst`.`KindId` AS `KindId`,`ds`.`Name` AS `Status`,`dpk`.`Description` AS `Kind`,`pst`.`OriginalId` AS `OriginalId` from ((((((`pages` `p` join `sites` `s` on((`p`.`SiteId` = `s`.`WikidotId`))) join `revisions` `r` on(((`r`.`PageId` = `p`.`WikidotId`) and (`r`.`RevisionIndex` = 0)))) left join `page_summary` `ps` on((`p`.`WikidotId` = `ps`.`PageId`))) join `page_status` `pst` on((`p`.`WikidotId` = `pst`.`PageId`))) straight_join `dict_status` `ds` on((`pst`.`StatusId` = `ds`.`StatusId`))) left join `dict_page_kind` `dpk` on((`pst`.`KindId` = `dpk`.`KindId`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_pages_deleted`
--

/*!50001 DROP VIEW IF EXISTS `view_pages_deleted`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_pages_deleted` AS select `view_pages_all`.`__Id` AS `__Id`,`view_pages_all`.`SiteId` AS `SiteId`,`view_pages_all`.`PageId` AS `PageId`,`view_pages_all`.`CategoryId` AS `CategoryId`,`view_pages_all`.`SiteName` AS `SiteName`,`view_pages_all`.`Site` AS `Site`,`view_pages_all`.`PageName` AS `PageName`,`view_pages_all`.`Title` AS `Title`,`view_pages_all`.`AltTitle` AS `AltTitle`,`view_pages_all`.`Source` AS `Source`,`view_pages_all`.`HideSource` AS `HideSource`,`view_pages_all`.`Deleted` AS `Deleted`,`view_pages_all`.`LastUpdate` AS `LastUpdate`,`view_pages_all`.`CreationDate` AS `CreationDate`,`view_pages_all`.`Rating` AS `Rating`,`view_pages_all`.`CleanRating` AS `CleanRating`,`view_pages_all`.`ContributorRating` AS `ContributorRating`,`view_pages_all`.`AdjustedRating` AS `AdjustedRating`,`view_pages_all`.`WilsonScore` AS `WilsonScore`,`view_pages_all`.`MonthRating` AS `MonthRating`,`view_pages_all`.`Revisions` AS `Revisions`,`view_pages_all`.`StatusId` AS `StatusId`,`view_pages_all`.`KindId` AS `KindId`,`view_pages_all`.`Status` AS `Status`,`view_pages_all`.`Kind` AS `Kind`,`view_pages_all`.`OriginalId` AS `OriginalId` from `view_pages_all` where (`view_pages_all`.`Deleted` = 1) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_revisions`
--

/*!50001 DROP VIEW IF EXISTS `view_revisions`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_revisions` AS select `view_revisions_all`.`__Id` AS `__Id`,`view_revisions_all`.`RevisionId` AS `RevisionId`,`view_revisions_all`.`PageId` AS `PageId`,`view_revisions_all`.`RevisionIndex` AS `RevisionIndex`,`view_revisions_all`.`PageName` AS `PageName`,`view_revisions_all`.`PageTitle` AS `PageTitle`,`view_revisions_all`.`Deleted` AS `Deleted`,`view_revisions_all`.`SiteId` AS `SiteId`,`view_revisions_all`.`SiteName` AS `SiteName`,`view_revisions_all`.`Site` AS `Site`,`view_revisions_all`.`UserId` AS `UserId`,`view_revisions_all`.`UserWikidotName` AS `UserWikidotName`,`view_revisions_all`.`UserDisplayName` AS `UserDisplayName`,`view_revisions_all`.`UserDeleted` AS `UserDeleted`,`view_revisions_all`.`DateTime` AS `DateTime`,`view_revisions_all`.`Comments` AS `Comments` from `view_revisions_all` where (`view_revisions_all`.`Deleted` <> 1) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_revisions_all`
--

/*!50001 DROP VIEW IF EXISTS `view_revisions_all`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_revisions_all` AS select `r`.`__Id` AS `__Id`,`r`.`WikidotId` AS `RevisionId`,`r`.`PageId` AS `PageId`,`r`.`RevisionIndex` AS `RevisionIndex`,`p`.`Name` AS `PageName`,`p`.`Title` AS `PageTitle`,`p`.`Deleted` AS `Deleted`,`s`.`WikidotId` AS `SiteId`,`s`.`WikidotName` AS `SiteName`,`s`.`ShortName` AS `Site`,`r`.`UserId` AS `UserId`,`u`.`WikidotName` AS `UserWikidotName`,`u`.`DisplayName` AS `UserDisplayName`,`u`.`Deleted` AS `UserDeleted`,`r`.`DateTime` AS `DateTime`,`r`.`Comments` AS `Comments` from (((`revisions` `r` join `pages` `p` on((`r`.`PageId` = `p`.`WikidotId`))) join `sites` `s` on((`p`.`SiteId` = `s`.`WikidotId`))) join `users` `u` on((`r`.`UserId` = `u`.`WikidotId`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_sites`
--

/*!50001 DROP VIEW IF EXISTS `view_sites`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_sites` AS select `s`.`WikidotId` AS `SiteId`,`s`.`EnglishName` AS `EnglishName`,`s`.`NativeName` AS `NativeName`,`s`.`ShortName` AS `ShortName`,`s`.`WikidotName` AS `WikidotName`,`s`.`Domain` AS `Domain`,`s`.`DefaultLanguage` AS `DefaultLanguage`,`s`.`LastUpdate` AS `LastUpdate`,`s`.`HideVotes` AS `HideVotes`,`s`.`Protocol` AS `Protocol`,`ss`.`Members` AS `Members`,`ss`.`ActiveMembers` AS `ActiveMembers`,`ss`.`Contributors` AS `Contributors`,`ss`.`Authors` AS `Authors`,`ss`.`Pages` AS `Pages`,`ss`.`Originals` AS `Originals`,`ss`.`Translations` AS `Translations`,`ss`.`Votes` AS `Votes`,`ss`.`Positive` AS `Positive`,`ss`.`Negative` AS `Negative`,`ss`.`Revisions` AS `Revisions` from (`sites` `s` join `site_stats` `ss` on((`s`.`WikidotId` = `ss`.`SiteId`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_tags`
--

/*!50001 DROP VIEW IF EXISTS `view_tags`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_tags` AS select `t`.`__Id` AS `__Id`,`p`.`SiteId` AS `SiteId`,`s`.`WikidotName` AS `SiteName`,`t`.`PageId` AS `PageId`,`p`.`Name` AS `PageName`,`t`.`Tag` AS `Tag`,`p`.`Deleted` AS `Deleted` from ((`tags` `t` join `pages` `p` on((`t`.`PageId` = `p`.`WikidotId`))) join `sites` `s` on((`p`.`SiteId` = `s`.`WikidotId`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_user_activity`
--

/*!50001 DROP VIEW IF EXISTS `view_user_activity`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_user_activity` AS select `u`.`WikidotId` AS `UserId`,`u`.`DisplayName` AS `UserDisplayName`,`u`.`WikidotName` AS `UserName`,`u`.`Deleted` AS `UserDeleted`,`ua`.`SiteId` AS `SiteId`,`ua`.`Votes` AS `Votes`,`ua`.`VotesSumm` AS `VotesSumm`,`ua`.`Revisions` AS `Revisions`,`ua`.`Pages` AS `Pages`,`ua`.`TotalRating` AS `TotalRating`,`ua`.`LastActivity` AS `LastActivity`,`s`.`EnglishName` AS `SiteEnglishName`,`s`.`NativeName` AS `SiteName`,`s`.`WikidotName` AS `Site` from ((`users` `u` join `user_activity` `ua` on((`u`.`WikidotId` = `ua`.`UserId`))) join `sites` `s` on((`ua`.`SiteId` = `s`.`WikidotId`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_users`
--

/*!50001 DROP VIEW IF EXISTS `view_users`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_users` AS select `u`.`__Id` AS `__Id`,`u`.`WikidotId` AS `UserId`,`u`.`WikidotName` AS `WikidotName`,`u`.`DisplayName` AS `DisplayName`,`u`.`RegistrationDate` AS `RegistrationDate`,`u`.`Deleted` AS `Deleted` from `users` `u` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_vote_history_all`
--

/*!50001 DROP VIEW IF EXISTS `view_vote_history_all`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_vote_history_all` AS select `vh`.`__Id` AS `__Id`,`p`.`SiteId` AS `SiteId`,`s`.`WikidotName` AS `SiteName`,`s`.`ShortName` AS `Site`,`vh`.`PageId` AS `PageId`,`p`.`Name` AS `PageName`,`p`.`Title` AS `PageTitle`,`p`.`Deleted` AS `Deleted`,`vh`.`UserId` AS `UserId`,`u`.`WikidotName` AS `UserName`,`u`.`DisplayName` AS `UserDisplayName`,`u`.`Deleted` AS `UserDeleted`,`vh`.`Value` AS `Value`,`vh`.`DateTime` AS `DateTime`,`vh`.`DeltaFromPrev` AS `DeltaFromPrev`,((`m`.`JoinDate` is not null) and (`m`.`Aborted` = 0)) AS `FromMember`,((`m`.`JoinDate` is not null) and (`m`.`Aborted` = 0) and (`ua`.`Pages` is not null) and (`ua`.`Pages` > 0)) AS `FromContributor`,((`m`.`JoinDate` is not null) and (`m`.`Aborted` = 0) and (`ua`.`LastActivity` is not null) and ((now() - interval 6 month) < `ua`.`LastActivity`)) AS `FromActive`,(`vh`.`Value` > 0) AS `IsPositive`,(`vh`.`Value` < 0) AS `IsNegative` from (((((`vote_history` `vh` straight_join `pages` `p` on((`vh`.`PageId` = `p`.`WikidotId`))) straight_join `sites` `s` on((`p`.`SiteId` = `s`.`WikidotId`))) straight_join `users` `u` on((`vh`.`UserId` = `u`.`WikidotId`))) left join `membership` `m` on(((`vh`.`UserId` = `m`.`UserId`) and (`p`.`SiteId` = `m`.`SiteId`)))) left join `user_activity` `ua` on(((`vh`.`UserId` = `ua`.`UserId`) and (`p`.`SiteId` = `ua`.`SiteId`)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_votes`
--

/*!50001 DROP VIEW IF EXISTS `view_votes`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_votes` AS select `view_votes_all`.`__Id` AS `__Id`,`view_votes_all`.`SiteId` AS `SiteId`,`view_votes_all`.`SiteName` AS `SiteName`,`view_votes_all`.`Site` AS `Site`,`view_votes_all`.`PageId` AS `PageId`,`view_votes_all`.`PageName` AS `PageName`,`view_votes_all`.`PageTitle` AS `PageTitle`,`view_votes_all`.`Deleted` AS `Deleted`,`view_votes_all`.`UserId` AS `UserId`,`view_votes_all`.`UserName` AS `UserName`,`view_votes_all`.`UserDisplayName` AS `UserDisplayName`,`view_votes_all`.`UserDeleted` AS `UserDeleted`,`view_votes_all`.`Value` AS `Value`,`view_votes_all`.`DateTime` AS `DateTime`,`view_votes_all`.`DeltaFromPrev` AS `DeltaFromPrev`,`view_votes_all`.`FromMember` AS `FromMember`,`view_votes_all`.`FromContributor` AS `FromContributor`,`view_votes_all`.`FromActive` AS `FromActive`,`view_votes_all`.`IsPositive` AS `IsPositive`,`view_votes_all`.`IsNegative` AS `IsNegative` from `view_votes_all` where (`view_votes_all`.`Deleted` <> 1) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `view_votes_all`
--

/*!50001 DROP VIEW IF EXISTS `view_votes_all`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb3 */;
/*!50001 SET character_set_results     = utf8mb3 */;
/*!50001 SET collation_connection      = utf8mb3_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 SQL SECURITY DEFINER */
/*!50001 VIEW `view_votes_all` AS select `v`.`__Id` AS `__Id`,`p`.`SiteId` AS `SiteId`,`s`.`WikidotName` AS `SiteName`,`s`.`ShortName` AS `Site`,`v`.`PageId` AS `PageId`,`p`.`Name` AS `PageName`,`p`.`Title` AS `PageTitle`,`p`.`Deleted` AS `Deleted`,`v`.`UserId` AS `UserId`,`u`.`WikidotName` AS `UserName`,`u`.`DisplayName` AS `UserDisplayName`,`u`.`Deleted` AS `UserDeleted`,`v`.`Value` AS `Value`,`v`.`DateTime` AS `DateTime`,`v`.`DeltaFromPrev` AS `DeltaFromPrev`,((`m`.`JoinDate` is not null) and (`m`.`Aborted` = 0)) AS `FromMember`,((`m`.`JoinDate` is not null) and (`m`.`Aborted` = 0) and (`ua`.`Pages` is not null) and (`ua`.`Pages` > 0)) AS `FromContributor`,((`m`.`JoinDate` is not null) and (`m`.`Aborted` = 0) and (`ua`.`LastActivity` is not null) and ((now() - interval 6 month) < `ua`.`LastActivity`)) AS `FromActive`,(`v`.`Value` > 0) AS `IsPositive`,(`v`.`Value` < 0) AS `IsNegative` from (((((`votes` `v` straight_join `pages` `p` on((`v`.`PageId` = `p`.`WikidotId`))) straight_join `sites` `s` on((`p`.`SiteId` = `s`.`WikidotId`))) straight_join `users` `u` on((`v`.`UserId` = `u`.`WikidotId`))) left join `membership` `m` on(((`v`.`UserId` = `m`.`UserId`) and (`p`.`SiteId` = `m`.`SiteId`)))) left join `user_activity` `ua` on(((`v`.`UserId` = `ua`.`UserId`) and (`p`.`SiteId` = `ua`.`SiteId`)))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-03-29 20:11:35
