CREATE TABLE IF NOT EXISTS `movies` (
 `ID` int(11) NOT NULL AUTO_INCREMENT,
 `MOVIE_DB_ID` int(11) NOT NULL,
 `TITLE` varchar(200) NOT NULL,
 `FILENAME` varchar(100) NOT NULL,
 `OVERVIEW` varchar(2000) DEFAULT NULL,
 `RELEASE_DATE` date NOT NULL,
 `GENRES` varchar(500) NOT NULL,
 `COUNTRIES` varchar(50) NOT NULL,
 `ACTORS` varchar(500) NOT NULL,
 `DIRECTOR` varchar(50) NOT NULL,
 `INFO` varchar(100) NOT NULL,
 `ORIGINAL_TITLE` varchar(200) NOT NULL,
 `COLLECTION_ID` int(11) DEFAULT NULL,
 `ADDED_DATE` date NOT NULL,
 `TITLE_SORT` varchar(200) DEFAULT NULL,
 `CATEGORY` varchar(100) NOT NULL, 
 PRIMARY KEY (`ID`),
 UNIQUE KEY `Filename_Category` (`FILENAME`,`CATEGORY`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8