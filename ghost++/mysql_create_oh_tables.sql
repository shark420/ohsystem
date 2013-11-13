-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 07, 2013 at 11:35 PM
-- Server version: 5.5.34
-- PHP Version: 5.3.10-1ubuntu3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `ohsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `oh_adminlog`
--

CREATE TABLE IF NOT EXISTS `oh_adminlog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `gameid` int(11) NOT NULL,
  `log_time` datetime NOT NULL,
  `log_admin` varchar(30) NOT NULL,
  `log_data` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `time` (`log_time`),
  KEY `gameid` (`gameid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4319 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_bans`
--

CREATE TABLE IF NOT EXISTS `oh_bans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `server` varchar(100) NOT NULL,
  `name` varchar(15) NOT NULL,
  `ip` varchar(16) NOT NULL,
  `ip_part` varchar(10) NOT NULL,
  `country` varchar(4) NOT NULL,
  `date` datetime NOT NULL,
  `gamename` varchar(31) NOT NULL,
  `admin` varchar(15) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `gamecount` int(11) NOT NULL,
  `expiredate` datetime NOT NULL,
  `warn` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `date` (`date`),
  KEY `expire` (`expiredate`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=68773 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_ban_appeals`
--

CREATE TABLE IF NOT EXISTS `oh_ban_appeals` (
  `player_id` int(11) NOT NULL,
  `player_name` varchar(25) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_ip` varchar(20) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `game_url` varchar(200) NOT NULL,
  `replay_url` varchar(255) NOT NULL,
  `added` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `resolved` varchar(30) NOT NULL,
  `resolved_text` varchar(255) NOT NULL,
  KEY `player_id` (`player_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `oh_ban_reports`
--

CREATE TABLE IF NOT EXISTS `oh_ban_reports` (
  `player_id` int(11) NOT NULL,
  `player_name` varchar(25) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_ip` varchar(20) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `game_url` varchar(200) NOT NULL,
  `replay_url` varchar(255) NOT NULL,
  `added` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  KEY `player_id` (`player_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `oh_bot_configuration`
--

CREATE TABLE IF NOT EXISTS `oh_bot_configuration` (
  `cfg_botid` tinyint(4) NOT NULL,
  `cfg_name` varchar(150) NOT NULL,
  `cfg_description` varchar(255) NOT NULL,
  `cfg_value` varchar(100) NOT NULL,
  KEY `cfg_name` (`cfg_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `oh_commands`
--

CREATE TABLE IF NOT EXISTS `oh_commands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) DEFAULT NULL,
  `command` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1266 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_comments`
--

CREATE TABLE IF NOT EXISTS `oh_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `page` varchar(16) CHARACTER SET latin1 NOT NULL,
  `post_id` int(11) NOT NULL,
  `text` text CHARACTER SET latin1 NOT NULL,
  `date` int(11) NOT NULL,
  `user_ip` varchar(16) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=26 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_custom_fields`
--

CREATE TABLE IF NOT EXISTS `oh_custom_fields` (
  `field_id` int(11) NOT NULL,
  `field_name` varchar(64) COLLATE utf8_bin NOT NULL,
  `field_value` longtext COLLATE utf8_bin NOT NULL,
  KEY `field_id` (`field_id`,`field_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `oh_dotagames`
--

CREATE TABLE IF NOT EXISTS `oh_dotagames` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `gameid` int(11) NOT NULL,
  `winner` int(11) NOT NULL,
  `min` int(11) NOT NULL,
  `sec` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `gameid` (`gameid`),
  KEY `winner` (`winner`),
  KEY `min` (`min`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=37428 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_dotaplayers`
--

CREATE TABLE IF NOT EXISTS `oh_dotaplayers` (
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
  `item1` char(4) CHARACTER SET latin1 NOT NULL,
  `item2` char(4) CHARACTER SET latin1 NOT NULL,
  `item3` char(4) CHARACTER SET latin1 NOT NULL,
  `item4` char(4) CHARACTER SET latin1 NOT NULL,
  `item5` char(4) CHARACTER SET latin1 NOT NULL,
  `item6` char(4) CHARACTER SET latin1 NOT NULL,
  `hero` char(4) CHARACTER SET latin1 NOT NULL,
  `newcolour` int(11) NOT NULL,
  `towerkills` int(11) NOT NULL,
  `raxkills` int(11) NOT NULL,
  `courierkills` int(11) NOT NULL,
  `level` tinyint(4) NOT NULL,
  `apm` int(11) NOT NULL,
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=372828 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_downloads`
--

CREATE TABLE IF NOT EXISTS `oh_downloads` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=69270 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_gamelist`
--

CREATE TABLE IF NOT EXISTS `oh_gamelist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) DEFAULT NULL,
  `gamename` varchar(128) DEFAULT NULL,
  `ownername` varchar(32) DEFAULT NULL,
  `creatorname` varchar(32) DEFAULT NULL,
  `map` varchar(100) DEFAULT NULL,
  `slotstaken` int(11) DEFAULT NULL,
  `slotstotal` int(11) DEFAULT NULL,
  `usernames` varchar(512) DEFAULT NULL,
  `totalgames` int(11) DEFAULT NULL,
  `totalplayers` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MEMORY  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_gameplayers`
--

CREATE TABLE IF NOT EXISTS `oh_gameplayers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `gameid` int(11) NOT NULL,
  `name` varchar(15) CHARACTER SET latin1 NOT NULL,
  `ip` varchar(15) CHARACTER SET latin1 NOT NULL,
  `spoofed` int(11) NOT NULL,
  `reserved` int(11) NOT NULL,
  `loadingtime` int(11) NOT NULL,
  `left` int(11) NOT NULL,
  `leftreason` varchar(100) CHARACTER SET latin1 NOT NULL,
  `team` int(11) NOT NULL,
  `colour` int(11) NOT NULL,
  `spoofedrealm` varchar(100) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `gameid` (`gameid`),
  KEY `colour` (`colour`),
  KEY `name` (`name`),
  KEY `name_2` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=374210 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_games`
--

CREATE TABLE IF NOT EXISTS `oh_games` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `server` varchar(100) CHARACTER SET latin1 NOT NULL,
  `map` varchar(100) CHARACTER SET latin1 NOT NULL,
  `datetime` datetime NOT NULL,
  `gamename` varchar(31) CHARACTER SET latin1 NOT NULL,
  `ownername` varchar(15) CHARACTER SET latin1 NOT NULL,
  `duration` int(11) NOT NULL,
  `gamestate` int(11) NOT NULL,
  `creatorname` varchar(15) CHARACTER SET latin1 NOT NULL,
  `creatorserver` varchar(20) CHARACTER SET latin1 NOT NULL,
  `gametype` tinyint(2) NOT NULL,
  `stats` tinyint(1) NOT NULL,
  `views` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `datetime` (`datetime`),
  KEY `map` (`map`),
  KEY `duration` (`duration`),
  KEY `gamestate` (`gamestate`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=37520 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_game_info`
--

CREATE TABLE IF NOT EXISTS `oh_game_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `gameid` int(11) NOT NULL,
  `server` varchar(100) NOT NULL,
  `map` varchar(100) NOT NULL,
  `datetime` datetime NOT NULL,
  `gamename` varchar(31) NOT NULL,
  `ownername` varchar(30) NOT NULL,
  `duration` int(11) NOT NULL,
  `gamestate` int(11) NOT NULL,
  `creatorname` varchar(30) NOT NULL,
  `creatorserver` varchar(100) NOT NULL,
  `gametype` tinyint(2) NOT NULL,
  `winner` tinyint(1) NOT NULL,
  `min` int(11) NOT NULL,
  `sec` int(11) NOT NULL,
  `stats` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=37519 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_game_log`
--

CREATE TABLE IF NOT EXISTS `oh_game_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `botid` int(11) NOT NULL,
  `gameid` int(11) NOT NULL,
  `log_time` datetime NOT NULL,
  `log_data` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `time` (`log_time`),
  KEY `gameid` (`gameid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_game_offenses`
--

CREATE TABLE IF NOT EXISTS `oh_game_offenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `player_name` varchar(30) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `offence_time` datetime NOT NULL,
  `offence_expire` datetime NOT NULL,
  `pp` tinyint(2) NOT NULL,
  `admin` varchar(30) NOT NULL,
  `banned` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`player_id`),
  KEY `time` (`offence_time`),
  KEY `expires` (`offence_expire`),
  KEY `pp` (`pp`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=50266 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_game_status`
--

CREATE TABLE IF NOT EXISTS `oh_game_status` (
  `botid` smallint(6) NOT NULL,
  `gameid` smallint(6) NOT NULL,
  `gametime` datetime NOT NULL,
  `gamename` varchar(30) NOT NULL,
  `gamestatus` tinyint(3) NOT NULL,
  `gametype` tinyint(1) NOT NULL DEFAULT '0',
  KEY `botid` (`botid`),
  KEY `gameid` (`gameid`),
  KEY `status` (`gamestatus`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `oh_geoip`
--

CREATE TABLE IF NOT EXISTS `oh_geoip` (
  `ip_start` varchar(16) NOT NULL,
  `ip_end` varchar(16) NOT NULL,
  `ip_start_int` int(11) NOT NULL,
  `ip_end_int` int(11) NOT NULL,
  `code` varchar(4) NOT NULL,
  `country` varchar(30) NOT NULL,
  KEY `ip_start` (`ip_start`,`ip_end`),
  KEY `code` (`code`),
  KEY `ip_integer` (`ip_start_int`,`ip_end_int`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `oh_heroes`
--

CREATE TABLE IF NOT EXISTS `oh_heroes` (
  `heroid` varchar(4) NOT NULL,
  `original` varchar(4) NOT NULL,
  `description` varchar(32) NOT NULL,
  `playcount` int(11) NOT NULL,
  `wins` int(11) NOT NULL,
  `summary` varchar(900) NOT NULL,
  `stats` varchar(300) NOT NULL,
  `skills` varchar(300) NOT NULL,
  `type` tinyint(4) NOT NULL,
  `voteup` int(11) NOT NULL DEFAULT '0',
  `votedown` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`heroid`),
  KEY `description` (`description`),
  KEY `original` (`original`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `oh_hero_guides`
--

CREATE TABLE IF NOT EXISTS `oh_hero_guides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hid` varchar(6) COLLATE utf8_bin NOT NULL,
  `title` varchar(255) COLLATE utf8_bin NOT NULL,
  `link` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `hid` (`hid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=142 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_items`
--

CREATE TABLE IF NOT EXISTS `oh_items` (
  `itemid` varchar(4) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `playcount` int(11) NOT NULL,
  `code` smallint(10) NOT NULL,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `shortname` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `item_info` mediumtext CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `price` smallint(6) NOT NULL,
  `type` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `icon` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`itemid`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `oh_lobby_game_logs`
--

CREATE TABLE IF NOT EXISTS `oh_lobby_game_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `gameid` int(11) NOT NULL,
  `botid` tinyint(4) NOT NULL,
  `gametype` tinyint(4) NOT NULL,
  `lobbylog` longtext COLLATE utf8_bin NOT NULL,
  `gamelog` longtext COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `gameid` (`gameid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=8013 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_news`
--

CREATE TABLE IF NOT EXISTS `oh_news` (
  `news_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `news_title` varchar(255) CHARACTER SET latin1 NOT NULL,
  `news_content` mediumtext CHARACTER SET latin1 NOT NULL,
  `author` int(11) NOT NULL,
  `comments` int(11) NOT NULL,
  `news_date` int(11) NOT NULL,
  `news_updated` int(11) NOT NULL,
  `views` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `allow_comments` tinyint(4) NOT NULL,
  PRIMARY KEY (`news_id`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=7 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_pm`
--

CREATE TABLE IF NOT EXISTS `oh_pm` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `m_from` varchar(30) NOT NULL,
  `m_to` varchar(30) NOT NULL,
  `m_time` datetime NOT NULL,
  `m_read` tinyint(2) NOT NULL,
  `m_message` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `from` (`m_from`),
  KEY `to` (`m_to`),
  KEY `status` (`m_read`),
  KEY `m_from` (`m_from`),
  KEY `m_to` (`m_to`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=201 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_stats`
--

CREATE TABLE IF NOT EXISTS `oh_stats` (
  `botid` int(11) NOT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `player` varchar(30) CHARACTER SET latin1 NOT NULL,
  `player_lower` varchar(30) CHARACTER SET latin1 NOT NULL,
  `last_seen` datetime NOT NULL,
  `user_level` tinyint(4) NOT NULL,
  `forced_gproxy` tinyint(1) NOT NULL,
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
  `penalty` tinyint(4) NOT NULL,
  `warn_expire` datetime NOT NULL,
  `warn` int(11) NOT NULL,
  `realm` varchar(100) CHARACTER SET latin1 NOT NULL,
  `reserved` int(11) NOT NULL,
  `leaver` int(11) NOT NULL,
  `ip` varchar(16) CHARACTER SET latin1 NOT NULL,
  `streak` int(11) NOT NULL,
  `maxstreak` int(11) NOT NULL,
  `losingstreak` int(11) NOT NULL,
  `maxlosingstreak` int(11) NOT NULL,
  `zerodeaths` int(11) NOT NULL,
  `points` int(11) NOT NULL,
  `points_bet` tinyint(11) NOT NULL,
  PRIMARY KEY (`id`),
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=32672 ;

-- --------------------------------------------------------

--
-- Table structure for table `oh_users`
--

CREATE TABLE IF NOT EXISTS `oh_users` (
  `user_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(60) NOT NULL,
  `bnet_username` varchar(30) NOT NULL,
  `user_bnet` tinyint(1) NOT NULL,
  `user_password` varchar(100) NOT NULL,
  `password_hash` varchar(65) NOT NULL,
  `user_ppwd` varchar(20) NOT NULL,
  `user_email` varchar(60) NOT NULL,
  `user_joined` int(11) NOT NULL DEFAULT '0',
  `user_level` tinyint(1) NOT NULL,
  `admin_realm` varchar(64) NOT NULL,
  `expire_date` datetime NOT NULL,
  `user_last_login` int(11) NOT NULL DEFAULT '0',
  `user_points_time` int(11) NOT NULL,
  `user_ip` varchar(40) NOT NULL,
  `user_avatar` varchar(255) NOT NULL,
  `user_location` varchar(100) NOT NULL,
  `user_realm` varchar(50) NOT NULL,
  `user_website` varchar(255) NOT NULL,
  `user_gender` tinyint(4) NOT NULL,
  `user_lang` varchar(30) NOT NULL,
  `user_clan` varchar(30) NOT NULL,
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=423 ;

-- --------------------------------------------------------

--
-- Table structure for table `online_users`
--

CREATE TABLE IF NOT EXISTS `online_users` (
  `user_id` int(11) NOT NULL,
  `timedate` int(11) NOT NULL,
  `user_ip` varchar(20) COLLATE utf8_bin NOT NULL,
  `user_agent` varchar(100) COLLATE utf8_bin NOT NULL,
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `w3mmdplayers`
--

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `w3mmdvars`
--

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
