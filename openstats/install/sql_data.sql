SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `name` varchar(15) NOT NULL,
  `server` varchar(100) NOT NULL,
  `access` bigint(20) NOT NULL DEFAULT '4294963199',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `bans`;
CREATE TABLE IF NOT EXISTS `bans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `server` varchar(100) NOT NULL,
  `name` varchar(15) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `date` datetime NOT NULL,
  `gamename` varchar(31) NOT NULL,
  `admin` varchar(15) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `expiredate` varchar(31) NOT NULL,
  `warn` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date` (`date`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `ban_appeals`;
CREATE TABLE IF NOT EXISTS `ban_appeals` (
  `player_id` int(11) NOT NULL,
  `player_name` varchar(25) CHARACTER SET latin1 NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_ip` varchar(20) CHARACTER SET latin1 NOT NULL,
  `reason` varchar(255) CHARACTER SET latin1 NOT NULL,
  `game_url` varchar(200) CHARACTER SET latin1 NOT NULL,
  `replay_url` varchar(255) CHARACTER SET latin1 NOT NULL,
  `added` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  KEY `player_id` (`player_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `ban_reports`;
CREATE TABLE IF NOT EXISTS `ban_reports` (
  `player_id` int(11) NOT NULL,
  `player_name` varchar(25) CHARACTER SET latin1 NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_ip` varchar(20) CHARACTER SET latin1 NOT NULL,
  `reason` varchar(255) CHARACTER SET latin1 NOT NULL,
  `game_url` varchar(200) CHARACTER SET latin1 NOT NULL,
  `replay_url` varchar(255) CHARACTER SET latin1 NOT NULL,
  `added` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  KEY `player_id` (`player_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `page` varchar(16) CHARACTER SET latin1 NOT NULL,
  `post_id` int(11) NOT NULL,
  `text` text COLLATE utf8_bin NOT NULL,
  `date` int(11) NOT NULL,
  `user_ip` varchar(16) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

DROP TABLE IF EXISTS `custom_fields`;
CREATE TABLE IF NOT EXISTS `custom_fields` (
  `field_id` int(11) NOT NULL,
  `field_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `field_value` longtext COLLATE utf8_bin NOT NULL,
  KEY `field_id` (`field_id`,`field_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `dotagames`;
CREATE TABLE IF NOT EXISTS `dotagames` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `gameid` int(11) NOT NULL,
  `winner` int(11) NOT NULL,
  `min` int(11) NOT NULL,
  `sec` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `gameid` (`gameid`),
  KEY `winner` (`winner`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `dotaplayers`;
CREATE TABLE IF NOT EXISTS `dotaplayers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `gameid` int(11) NOT NULL,
  `colour` int(11) NOT NULL,
  `kills` int(11) NOT NULL,
  `deaths` int(11) NOT NULL,
  `creepkills` int(11) NOT NULL,
  `creepdenies` int(11) NOT NULL,
  `assists` int(11) NOT NULL,
  `gold` int(11) NOT NULL,
  `neutralkills` int(11) NOT NULL,
  `item1` char(4) NOT NULL,
  `item2` char(4) NOT NULL,
  `item3` char(4) NOT NULL,
  `item4` char(4) NOT NULL,
  `item5` char(4) NOT NULL,
  `item6` char(4) NOT NULL,
  `hero` char(4) NOT NULL,
  `newcolour` int(11) NOT NULL,
  `towerkills` int(11) NOT NULL,
  `raxkills` int(11) NOT NULL,
  `courierkills` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `gameid` (`gameid`,`colour`),
  KEY `colour` (`colour`),
  KEY `newcolour` (`newcolour`),
  KEY `hero` (`hero`),
  KEY `item1` (`item1`),
  KEY `item2` (`item2`),
  KEY `item3` (`item3`),
  KEY `item4` (`item4`),
  KEY `item5` (`item5`),
  KEY `item6` (`item6`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `downloads`;
CREATE TABLE IF NOT EXISTS `downloads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `map` varchar(100) NOT NULL,
  `mapsize` int(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `name` varchar(15) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `spoofed` int(11) NOT NULL,
  `spoofedrealm` varchar(100) NOT NULL,
  `downloadtime` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `gameplayers`;
CREATE TABLE IF NOT EXISTS `gameplayers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `gameid` int(11) NOT NULL,
  `name` varchar(15) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `spoofed` int(11) NOT NULL,
  `reserved` int(11) NOT NULL,
  `loadingtime` int(11) NOT NULL,
  `left` int(11) NOT NULL,
  `leftreason` varchar(100) NOT NULL,
  `team` int(11) NOT NULL,
  `colour` int(11) NOT NULL,
  `spoofedrealm` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `gameid` (`gameid`),
  KEY `colour` (`colour`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `games`;
CREATE TABLE IF NOT EXISTS `games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `server` varchar(100) NOT NULL,
  `map` varchar(100) NOT NULL,
  `datetime` datetime NOT NULL,
  `gamename` varchar(31) NOT NULL,
  `ownername` varchar(15) NOT NULL,
  `duration` int(11) NOT NULL,
  `gamestate` int(11) NOT NULL,
  `creatorname` varchar(15) NOT NULL,
  `creatorserver` varchar(100) NOT NULL,
  `stats` tinyint(4) NOT NULL,
  `views` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `datetime` (`datetime`),
  KEY `map` (`map`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `heroes`;
CREATE TABLE IF NOT EXISTS `heroes` (
  `heroid` varchar(4) CHARACTER SET latin1 NOT NULL,
  `voteup` int(11) NOT NULL DEFAULT '0',
  `votedown` int(11) NOT NULL DEFAULT '0',
  `original` varchar(4) CHARACTER SET latin1 NOT NULL,
  `description` varchar(32) CHARACTER SET latin1 NOT NULL,
  `summary` varchar(900) CHARACTER SET latin1 NOT NULL,
  `stats` varchar(300) CHARACTER SET latin1 NOT NULL,
  `skills` varchar(300) CHARACTER SET latin1 NOT NULL,
  `type` tinyint(4) NOT NULL,
  PRIMARY KEY (`heroid`),
  KEY `description` (`description`),
  KEY `original` (`original`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `hero_guides`;
CREATE TABLE IF NOT EXISTS `hero_guides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hid` varchar(6) CHARACTER SET latin1 NOT NULL,
  `title` varchar(255) CHARACTER SET latin1 NOT NULL,
  `link` varchar(255) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `hid` (`hid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `items`;
CREATE TABLE IF NOT EXISTS `items` (
  `itemid` varchar(4) CHARACTER SET latin1 NOT NULL,
  `code` smallint(10) NOT NULL,
  `name` varchar(50) CHARACTER SET latin1 NOT NULL,
  `shortname` varchar(50) CHARACTER SET latin1 NOT NULL,
  `item_info` mediumtext CHARACTER SET latin1 NOT NULL,
  `price` smallint(6) NOT NULL,
  `type` varchar(10) CHARACTER SET latin1 NOT NULL,
  `icon` varchar(50) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`itemid`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `news`;
CREATE TABLE IF NOT EXISTS `news` (
  `news_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `news_title` varchar(90) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `news_content` mediumtext CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `author` int(11) NOT NULL,
  `comments` int(11) NOT NULL,
  `news_date` int(11) NOT NULL,
  `news_updated` int(11) NOT NULL,
  `views` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `allow_comments` tinyint(4) NOT NULL,
  PRIMARY KEY (`news_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `notes`;
CREATE TABLE IF NOT EXISTS `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server` varchar(100) COLLATE utf8_bin NOT NULL,
  `name` varchar(15) COLLATE utf8_bin NOT NULL,
  `note` varchar(250) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;


DROP TABLE IF EXISTS `safelist`;
CREATE TABLE IF NOT EXISTS `safelist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server` varchar(100) NOT NULL,
  `name` varchar(15) NOT NULL,
  `voucher` varchar(15) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `scores`;
CREATE TABLE IF NOT EXISTS `scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(25) NOT NULL,
  `name` varchar(15) NOT NULL,
  `server` varchar(100) NOT NULL,
  `score` double NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `score` (`score`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `stats`;
CREATE TABLE IF NOT EXISTS `stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player` varchar(30) CHARACTER SET latin1 NOT NULL,
  `player_lower` varchar(30) CHARACTER SET latin1 NOT NULL,
  `score` int(11) NOT NULL,
  `games` int(11) NOT NULL,
  `wins` int(11) NOT NULL,
  `losses` int(11) NOT NULL,
  `draw` int(11) NOT NULL,
  `kills` int(11) NOT NULL,
  `deaths` int(11) NOT NULL,
  `assists` int(11) NOT NULL,
  `creeps` int(11) NOT NULL,
  `denies` int(11) NOT NULL,
  `neutrals` int(11) NOT NULL,
  `towers` int(11) NOT NULL,
  `rax` int(11) NOT NULL,
  `banned` tinyint(1) NOT NULL,
  `warn_expire` datetime NOT NULL,
  `warn` int(11) NOT NULL,
  `admin` tinyint(4) NOT NULL,
  `safelist` tinyint(4) NOT NULL,
  `realm` varchar(100) CHARACTER SET latin1 NOT NULL,
  `reserved` int(11) NOT NULL,
  `leaver` int(11) NOT NULL,
  `ip` varchar(16) CHARACTER SET latin1 NOT NULL,
  `streak` int(11) NOT NULL,
  `maxstreak` int(11) NOT NULL,
  `losingstreak` int(11) NOT NULL,
  `maxlosingstreak` int(11) NOT NULL,
  `zerodeaths` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `score` (`score`),
  KEY `games` (`games`),
  KEY `wins` (`wins`),
  KEY `losses` (`losses`),
  KEY `draw` (`draw`),
  KEY `kills` (`kills`),
  KEY `deaths` (`deaths`),
  KEY `assists` (`assists`),
  KEY `ck` (`creeps`),
  KEY `cd` (`denies`),
  KEY `player` (`player`),
  KEY `player_lower` (`player_lower`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(60) NOT NULL,
  `user_password` varchar(60) NOT NULL,
  `password_hash` varchar(65) NOT NULL,
  `user_email` varchar(60) NOT NULL,
  `user_joined` int(11) NOT NULL DEFAULT '0',
  `user_level` tinyint(1) NOT NULL,
  `user_last_login` int(11) NOT NULL DEFAULT '0',
  `user_ip` varchar(40) NOT NULL,
  `user_avatar` varchar(255) NOT NULL,
  `user_location` varchar(100) NOT NULL,
  `user_realm` varchar(255) NOT NULL,
  `user_website` varchar(255) NOT NULL,
  `user_gender` tinyint(4) NOT NULL,
  `user_lang` char(15) NOT NULL,
  `user_fbid` varchar(30) NOT NULL,
  `phpbb_id` int(11) NOT NULL,
  `smf_id` int(11) NOT NULL,
  `can_comment` tinyint(4) NOT NULL DEFAULT '1',
  `code` varchar(15) NOT NULL,
  `confirm` varchar(65) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_name` (`user_name`),
  KEY `last_login` (`user_last_login`),
  KEY `joined` (`user_joined`),
  KEY `confirm` (`confirm`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `w3mmdplayers`;
CREATE TABLE IF NOT EXISTS `w3mmdplayers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `category` varchar(25) NOT NULL,
  `gameid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `name` varchar(15) NOT NULL,
  `flag` varchar(32) NOT NULL,
  `leaver` int(11) NOT NULL,
  `practicing` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `w3mmdvars`;
CREATE TABLE IF NOT EXISTS `w3mmdvars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `gameid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `varname` varchar(25) NOT NULL,
  `value_int` int(11) DEFAULT NULL,
  `value_real` double DEFAULT NULL,
  `value_string` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;