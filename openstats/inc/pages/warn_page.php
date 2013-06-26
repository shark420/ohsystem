<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

    $HomeTitle = $lang["warn"] ;
	$HomeDesc = $lang["warn"];
	$HomeKeywords = strtolower( os_strip_quotes($lang["warn"])).','.$HomeKeywords;
   
     if ( isset($_GET["search_bans"]) AND strlen($_GET["search_bans"])>=2  ) {
	    $search_bans = safeEscape( $_GET["search_bans"]);
		$sql = "AND (name) LIKE ? ";
	 } else $sql = "";
   
     $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_BANS." WHERE id>=1 AND warn = 1 $sql LIMIT 1");
	 
	 if ( !empty($sql) ) $sth->bindValue(1, "%".strtolower($search_bans)."%", PDO::PARAM_STR);
	 
	 $result = $sth->execute();
	 $r = $sth->fetch(PDO::FETCH_NUM);
	 
	 $numrows = $r[0];
	 $result_per_page = $TopPlayersPerPage;
	 $draw_pagination = 0;
	 include('inc/pagination.php');
	 $draw_pagination = 1;
	  
	 $sth = $db->prepare("SELECT * FROM ".OSDB_BANS." WHERE id>=1 $sql AND warn = 1 LIMIT $offset, $rowsperpage");
	 if ( !empty($sql) ) $sth->bindValue(1, "%".strtolower($search_bans)."%", PDO::PARAM_STR);
	 
	 $result = $sth->execute();
	 $c=0;
    $BansData = array();
	if ( file_exists("inc/geoip/geoip.inc") ) {
	include("inc/geoip/geoip.inc");
	$GeoIPDatabase = geoip_open("inc/geoip/GeoIP.dat", GEOIP_STANDARD);
	$GeoIP = 1;
	}
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	if ( isset($GeoIP) AND $GeoIP == 1)
	$BansData[$c]["letter"]   = geoip_country_code_by_addr($GeoIPDatabase, $row["ip"]);
	if ($GeoIP == 1 AND empty($BansData[$c]["letter"]) ) $BansData[$c]["letter"] = "blank";
	$BansData[$c]["id"]        = (int)($row["id"]);
	$BansData[$c]["server"]  = ($row["server"]);
	$BansData[$c]["name"]  = ($row["name"]);
	$BansData[$c]["ip"]  = ($row["ip"]);
	$BansData[$c]["date"]  = date($DateFormat, strtotime($row["date"]));
	$BansData[$c]["gamename"]  = ($row["gamename"]);
	$BansData[$c]["admin"]  = ($row["admin"]);
	$BansData[$c]["reason"]  = substr($row["reason"],0, 40);
	$BansData[$c]["reason_full"]  = ($row["reason"]);
	//$BansData[$c]["expiredate"]  = ($row["expiredate"]);
	//$BansData[$c]["warn"]  = ($row["warn"]);
	$c++;
	}
	if ( isset($GeoIP) AND $GeoIP == 1) geoip_close($GeoIPDatabase);
?>