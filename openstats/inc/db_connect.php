<?PHP

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

    if (strstr($_SERVER['REQUEST_URI'], basename(__FILE__) ) )
    {header('HTTP/1.1 404 Not Found'); die; }
   
   if (isset($_debug) AND $_debug == 1)
   {
   ini_set ("display_errors", "1");
   error_reporting(E_ALL);
   } 
     else 
     {
	 ini_set ("display_errors", "0");
      error_reporting(NULL);
	 }

  if(isset($DBDriver) AND $DBDriver == "mysql" ) {
  $db = new database($server, $username, $password, $database);
  $db->connect(database);
  $sth = $db->query("SET NAMES 'utf8'");
  } else {
   $db = new db("mysql:host=".OSDB_SERVER.";dbname=".OSDB_DATABASE."", OSDB_USERNAME, OSDB_PASSWORD);
   $sth = $db->prepare("SET NAMES 'utf8'");
   $result = $sth->execute();
  }
  

  
  ?>