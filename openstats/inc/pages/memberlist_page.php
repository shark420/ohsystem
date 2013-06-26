<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

    if ( !isset($search_members) ) $search_members=$lang["search_members"];

     if ( isset($_GET["search_members"]) AND strlen($_GET["search_members"])>=2  ) {
	    $search_members = safeEscape( trim($_GET["search_members"]));
		$sql = "AND (user_name) LIKE ? ";
		$start = "%";
	 } else $sql = "";
	 
	 if ( isset($_GET["search_members"]) AND strlen($_GET["search_members"])==1  ) {
	 	$search_members = safeEscape( trim($_GET["search_members"]));
		$sql = "AND (user_name) LIKE ? ";
		$start = "";
	 }

     $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_USERS." WHERE confirm= '' $sql  LIMIT 1");
	 if ( !empty($sql) ) $sth->bindValue(1, $start.strtolower($search_members)."%", PDO::PARAM_STR);
	 $result = $sth->execute();
	 $r = $sth->fetch(PDO::FETCH_NUM);
	 $numrows = $r[0];
	 $result_per_page = $HeroesPerPage;
	 $draw_pagination = 0;
	 $total_comments  = $numrows;
	 include('inc/pagination.php');
	 $draw_pagination = 1;
	 
	 $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE confirm= '' $sql  ORDER BY user_id DESC LIMIT $offset, $rowsperpage");
	 if ( !empty($sql) ) $sth->bindValue(1, $start.strtolower($search_members)."%", PDO::PARAM_STR);
	 $result = $sth->execute();
	 $c=0;
     $MembersData = array();
	 
	 if ( file_exists("inc/geoip/geoip.inc") ) {
	 include("inc/geoip/geoip.inc");
	 $GeoIPDatabase = geoip_open("inc/geoip/GeoIP.dat", GEOIP_STANDARD);
	 $GeoIP = 1;
	 }
	 
	 $HomeTitle = ($lang["members"]);
	 
	 while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	 $MembersData[$c]["id"]        = (int)($row["user_id"]);
	 $MembersData[$c]["user_name"] = ($row["user_name"]);
	 $MembersData[$c]["user_email"] = ($row["user_email"]);
	 $MembersData[$c]["user_joined"] = ($row["user_joined"]);
	 $MembersData[$c]["user_level"] = ($row["user_level"]);
	 $MembersData[$c]["user_ip"] = ($row["user_ip"]);
	 $MembersData[$c]["user_avatar"] = ($row["user_avatar"]);
	 $MembersData[$c]["user_location"] = ($row["user_location"]);
	 $MembersData[$c]["user_realm"] = ($row["user_realm"]);
	 $MembersData[$c]["user_website"] = ($row["user_website"]);
	 $MembersData[$c]["user_gender"] = ($row["user_gender"]);
	 $MembersData[$c]["user_fbid"] = ($row["user_fbid"]);
	 $MembersData[$c]["can_comment"] = ($row["can_comment"]);
	 $MembersData[$c]["letter"]   = geoip_country_code_by_addr($GeoIPDatabase, $row["user_ip"]);
     $MembersData[$c]["country"]  = geoip_country_name_by_addr($GeoIPDatabase, $row["user_ip"]);
	 if ($GeoIP == 1 AND empty($MembersData[$c]["letter"]) ) $MembersData[$c]["letter"] = "blank";
	 if ( isset($ShowMembersCountry) AND $ShowMembersCountry == 0 ) $MembersData[$c]["letter"] = "";
	 $c++;
	 }
	 if ( isset($GeoIP) AND $GeoIP == 1) geoip_close($GeoIPDatabase);
?>