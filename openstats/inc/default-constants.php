<?php
/*********************************************
<!-- 
*   	DOTA OPENSTATS
*   
*	Developers: Ivan.
*	Contact: ivan.anta@gmail.com - Ivan
*
*	
*	Please see http://openstats.iz.rs
*	and post your webpage there, so I know who's using it.
*
*	Files downloaded from http://openstats.iz.rs
*
*	Copyright (C) 2010  Ivan
*
*
*	This file is part of DOTA OPENSTATS.
*
* 
*	 DOTA OPENSTATS is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    DOTA OPEN STATS is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with DOTA OPEN STATS.  If not, see <http://www.gnu.org/licenses/>
*
-->
**********************************************/
if (!isset($website) ) {header('HTTP/1.1 404 Not Found'); die; }

define('OS_VERSION', '4.0.0.0');
define('OS_HOME',      $website); 
define('OS_HOME_TITLE',    $HomeTitle);
define('OS_TIMEZONE',      $TimeZone);

define('OSDB_ADMINS',   'admins'); //do not change
define('OSDB_BANS',     'bans'); //do not change
define('OSDB_APPEALS',  'ban_appeals');
define('OSDB_REPORTS',  'ban_reports');
define('OSDB_COMMENTS', 'comments');
define('OSDB_CUSTOM_FIELDS',  'custom_fields');
define('OSDB_DG',       'dotagames'); //do not change
define('OSDB_DP',       'dotaplayers'); //do not change
define('OSDB_DL',       'downloads'); //do not change
define('OSDB_GP',       'gameplayers'); //do not change
define('OSDB_GAMES',    'games'); //do not change
define('OSDB_GAMELIST', 'gamelist'); //table for gamelist patch
define('OSDB_HEROES',   'heroes');
define('OSDB_GUIDES',   'hero_guides');
define('OSDB_ITEMS',    'items');
define('OSDB_NEWS',     'news');
define('OSDB_NOTES',    'notes');
define('OSDB_PERMISSIONS', 'permissions');
define('OSDB_SAFELIST',    'safelist'); //do not change
define('OSDB_SCORES',      'scores');
define('OSDB_STATS',       'stats');
define('OSDB_USERS',       'users');
//define('OSDB_VISITORS',    'visitors');
define('OSDB_W3PL',        'w3mmdplayers'); //do not change
define('OSDB_W3VARS',      'w3mmdvars'); //do not change


//DATABASE
define('OSDB_SERVER',      $server); 
define('OSDB_USERNAME',    $username); 
define('OSDB_PASSWORD',    $password); 
define('OSDB_DATABASE',    $database); 

define('OS_THEMES_DIR',              $DefaultStyle);
define('OS_THEME_PATH',              OS_HOME."themes/".OS_THEMES_DIR."/");
define('OS_CURRENT_THEME_PATH',     "themes/".OS_THEMES_DIR."/"); 
define('OS_PLUGINS_DIR',            'plugins/');
define('OS_PAGE_PATH',               "inc/pages/"); 
define('OS_LANGUAGE',                $default_language);
define('OS_DATE_FORMAT',             $DateFormat); 
define('OS_MIN_GAME_DURATION',       $MinDuration);

//
define('OS_TOP_ENABLED',       $TopPage);
define('OS_HEROES_ENABLED',    $HeroesPage);
//
?>