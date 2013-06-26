<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

      $s = safeEscape( $_GET["search"]);
	  $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_STATS." WHERE (player) LIKE ? LIMIT 1");
	  
	  $sth->bindValue(1, "%".strtolower($s)."%", PDO::PARAM_STR);
	  $result = $sth->execute();
	  $r = $sth->fetch(PDO::FETCH_NUM);
	  $numrows = $r[0];
	  $result_per_page = $TopPlayersPerPage;
	  $draw_pagination = 0;
	  include('inc/pagination.php');
	  $draw_pagination = 1;
	  
	  
	  $sth = $db->prepare("SELECT * FROM ".OSDB_STATS." WHERE (player) LIKE ? 
	  ORDER BY score DESC
	  LIMIT $offset, $rowsperpage");
	  
	  $sth->bindValue(1, "%".strtolower($s)."%", PDO::PARAM_STR);
	  $result = $sth->execute();
	  
	$c=0;
    $SearchData = array();
	if ( file_exists("inc/geoip/geoip.inc") ) {
	include("inc/geoip/geoip.inc");
	$GeoIPDatabase = geoip_open("inc/geoip/GeoIP.dat", GEOIP_STANDARD);
	$GeoIP = 1;
	}
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	if ( isset($GeoIP) AND $GeoIP == 1) {
	$SearchData[$c]["letter"]   = geoip_country_code_by_addr($GeoIPDatabase, $row["ip"]);
	$SearchData[$c]["country"]  = geoip_country_name_by_addr($GeoIPDatabase, $row["ip"]);
	}
	if ($GeoIP == 1 AND empty($SearchData[$c]["letter"]) ) { 
	$SearchData[$c]["letter"] = "blank";
	$SearchData[$c]["country"]  = "Reserved";
	}
	$SearchData[$c]["id"]        = (int)($row["id"]);
	$SearchData[$c]["player"]  = ($row["player"]);
	$SearchData[$c]["score"]  = number_format($row["score"],0);
	$SearchData[$c]["games"]  = number_format($row["games"],0);
	$SearchData[$c]["wins"]  = number_format($row["wins"],0);
	$SearchData[$c]["losses"]  = number_format($row["losses"],0);
	$SearchData[$c]["draw"]  = number_format($row["draw"],0);
	$SearchData[$c]["kills"]  = number_format($row["kills"],0);
	$SearchData[$c]["deaths"]  = number_format($row["deaths"],0);
	$SearchData[$c]["assists"]  = number_format($row["assists"],0);
	$SearchData[$c]["creeps"]  = number_format($row["creeps"],0);
	$SearchData[$c]["denies"]  = number_format($row["denies"],0);
	$SearchData[$c]["neutrals"]  = number_format($row["neutrals"],0);
	$SearchData[$c]["towers"]  = ($row["towers"]);
	$SearchData[$c]["rax"]  = ($row["rax"]);
	$SearchData[$c]["banned"]  = ($row["banned"]);
	$SearchData[$c]["ip"]  = ($row["ip"]);
	
	$c++;
	}
	if ( isset($GeoIP) AND $GeoIP == 1) geoip_close($GeoIPDatabase);
	
?>