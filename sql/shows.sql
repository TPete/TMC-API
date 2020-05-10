CREATE TABLE IF NOT EXISTS `shows` (
 `ID` int(11) NOT NULL AUTO_INCREMENT,
 `CATEGORY` varchar(100) NOT NULL,
 `TITLE` varchar(100) NOT NULL,
 `FOLDER` varchar(100) NOT NULL,
 `TVDB_ID` int(11) DEFAULT NULL,
 `ORDERING_SCHEME` varchar(20) NOT NULL,
 `LANG` varchar(10) DEFAULT 'de' NOT NULL,
 PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8