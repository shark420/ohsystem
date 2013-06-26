<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

   	 //SET META INFORMATION AND PAGE NAME
	 $HomeTitle = $lang["top"];
	 $HomeDesc = $lang["top"];
	 //$HomeKeywords = strtolower($row["gamename"]).','.$HomeKeywords;
   
   $orderby = "`score` DESC";
   
   if ( isset($_GET["sort"]) ) {
     if ( $_GET["sort"] == "score") $orderby = "`score` DESC";
	 if ( $_GET["sort"] == "player_name") $orderby = "(`player`) ASC";
	 if ( $_GET["sort"] == "games") $orderby = "(`games`) DESC";
	 if ( $_GET["sort"] == "wins") $orderby = "(`wins`) DESC";
	 if ( $_GET["sort"] == "losses") $orderby = "(`losses`) DESC";
	 if ( $_GET["sort"] == "draw") $orderby = "(`draw`) DESC";
	 if ( $_GET["sort"] == "kills") $orderby = "(`kills`) DESC";
	 if ( $_GET["sort"] == "deaths") $orderby = "(`deaths`) DESC";
	 if ( $_GET["sort"] == "assists") $orderby = "(`assists`) DESC";
	 if ( $_GET["sort"] == "ck") $orderby = "(`creeps`) DESC";
	 if ( $_GET["sort"] == "cd") $orderby = "(`denies`) DESC";
	 if ( $_GET["sort"] == "nk") $orderby = "(`neutrals`) DESC";
	 if ( $_GET["sort"] == "leaves") $orderby = "(`leaver`) DESC";
	 if ( $_GET["sort"] == "streak") $orderby = "(`maxstreak`) DESC";
   }
   
   if ( isset($_GET["L"]) AND strlen($_GET["L"]) == 1 ) {
     $sql = " AND player LIKE ('".strtolower($_GET["L"])."%') ";
   } else $sql = "";
   
  $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_STATS." WHERE id>=1 $sql LIMIT 1");
  $result = $sth->execute();
  
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  
  if ( $numrows<=0) { 
  require_once(OS_PLUGINS_DIR.'index.php');
  os_init();
  header('location: '.OS_HOME.'?404'); die; 
  }
  
  $result_per_page = $TopPlayersPerPage;
  $draw_pagination = 0;
  include('inc/pagination.php');
  $draw_pagination = 1;
  
  $sth = $db->prepare("SELECT * FROM ".OSDB_STATS." WHERE id>=1 $sql ORDER BY $orderby LIMIT $offset, $rowsperpage");
  $result = $sth->execute();

  if ( isset($_GET["page"]) AND is_numeric($_GET["page"]) AND $sth->rowCount()>=1 ) {
     $HomeTitle.=" | Page ".(int) $_GET["page"];
  }
   
   	$c=0;
    $TopData = array();
	$counter = 0;
	
	if ( isset( $_GET["page"]) AND is_numeric($_GET["page"]) ) {
	  $counter = (($_GET["page"]-1) * $TopPlayersPerPage) ;
	}
	
	if ( file_exists("inc/geoip/geoip.inc") ) {
	include("inc/geoip/geoip.inc");
	$GeoIPDatabase = geoip_open("inc/geoip/GeoIP.dat", GEOIP_STANDARD);
	$GeoIP = 1;
	}
	
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	$TopData[$c]["letter"]   = geoip_country_code_by_addr($GeoIPDatabase, $row["ip"]);
	$TopData[$c]["country"]  = geoip_country_name_by_addr($GeoIPDatabase, $row["ip"]);
	if ($GeoIP == 1 AND empty($TopData[$c]["letter"]) ) {
	$TopData[$c]["letter"] = "blank";
	$TopData[$c]["country"] = "Reserved";
	}
	
	$counter++;
	
	$TopData[$c]["counter"]        = $counter;
	$TopData[$c]["id"]        = (int)($row["id"]);
	$TopData[$c]["player"]  = ($row["player"]);

	$TopData[$c]["score"]  = number_format($row["score"],0);
	$TopData[$c]["games"]  = number_format($row["games"],0);
	$TopData[$c]["wins"]  = number_format($row["wins"],0);
	$TopData[$c]["losses"]  = number_format($row["losses"],0);
	$TopData[$c]["draw"]  = number_format($row["draw"],0);
	$TopData[$c]["kills"]  = number_format($row["kills"],0);
	$TopData[$c]["deaths"]  = number_format($row["deaths"],0);
	$TopData[$c]["assists"]  = number_format($row["assists"],0);
	$TopData[$c]["creeps"]  = number_format($row["creeps"],0);
	$TopData[$c]["denies"]  = number_format($row["denies"],0);
	$TopData[$c]["neutrals"]  = number_format($row["neutrals"],0);
	$TopData[$c]["towers"]  = ($row["towers"]);
	$TopData[$c]["rax"]  = ($row["rax"]);
	$TopData[$c]["banned"]  = ($row["banned"]);
	$TopData[$c]["warn_expire"]  = ($row["warn_expire"]);
	$TopData[$c]["warn"]  = ($row["warn"]);
	$TopData[$c]["admin"]  = ($row["admin"]);
	$TopData[$c]["safelist"]  = ($row["safelist"]);
	$TopData[$c]["ip"]  = ($row["ip"]);
	$TopData[$c]["leaver"]  = ($row["leaver"]);
	$TopData[$c]["streak"]  = ($row["streak"]);
	$TopData[$c]["maxstreak"]  = ($row["maxstreak"]);
	$TopData[$c]["losingstreak"]  = ($row["losingstreak"]);
	$TopData[$c]["maxlosingstreak"]  = ($row["maxlosingstreak"]);
	$TopData[$c]["zerodeaths"]  = ($row["zerodeaths"]);
	
	if ($row["games"] >0 )
	$TopData[$c]["stayratio"] = ROUND($row["games"]/($row["games"]+$row["leaver"]), 3)*100;
	else $TopData[$c]["stayratio"] = 0;
	
	if ($row["wins"] >0 )
	$TopData[$c]["winslosses"] = ROUND($row["wins"]/($row["wins"]+$row["losses"]), 3)*100;
	else $TopData[$c]["winslosses"] = 0;
		
	//Highlight - sort
	if ( (isset($_GET["sort"]) AND $_GET["sort"] == "score") OR !isset($_GET["sort"]) ) 
	$TopData[$c]["score"] = "<span class='highlight_top'>".$TopData[$c]["score"]."</span>";
	
	if ( isset($_GET["sort"]) AND $_GET["sort"] == "games") 
	$TopData[$c]["games"] = "<span class='highlight_top'>".$TopData[$c]["games"]."</span>";
	
	if ( isset($_GET["sort"]) AND $_GET["sort"] == "wins") 
	$TopData[$c]["wins"] = "<span class='highlight_top'>".$TopData[$c]["wins"]."</span>";
	
	if ( isset($_GET["sort"]) AND $_GET["sort"] == "losses") 
	$TopData[$c]["losses"] = "<span class='highlight_top'>".$TopData[$c]["losses"]."</span>";
	
	if ( isset($_GET["sort"]) AND $_GET["sort"] == "losses") 
	$TopData[$c]["losses"] = "<span class='highlight_top'>".$TopData[$c]["losses"]."</span>";
	
	if ( isset($_GET["sort"]) AND $_GET["sort"] == "draw") 
	$TopData[$c]["draw"] = "<span class='highlight_top'>".$TopData[$c]["draw"]."</span>";
	
	if ( isset($_GET["sort"]) AND $_GET["sort"] == "kills") 
	$TopData[$c]["kills"] = "<span class='highlight_top'>".$TopData[$c]["kills"]."</span>";
	
	if ( isset($_GET["sort"]) AND $_GET["sort"] == "deaths") 
	$TopData[$c]["deaths"] = "<span class='highlight_top'>".$TopData[$c]["deaths"]."</span>";
	
	if ( isset($_GET["sort"]) AND $_GET["sort"] == "assists") 
	$TopData[$c]["assists"] = "<span class='highlight_top'>".$TopData[$c]["assists"]."</span>";
	
	if ( isset($_GET["sort"]) AND $_GET["sort"] == "ck") 
	$TopData[$c]["creeps"] = "<span class='highlight_top'>".$TopData[$c]["creeps"]."</span>";
	
	if ( isset($_GET["sort"]) AND $_GET["sort"] == "cd") 
	$TopData[$c]["denies"] = "<span class='highlight_top'>".$TopData[$c]["denies"]."</span>";
	
	if ( isset($_GET["sort"]) AND $_GET["sort"] == "nk") 
	$TopData[$c]["neutrals"] = "<span class='highlight_top'>".$TopData[$c]["neutrals"]."</span>";
	
	if ( isset($_GET["sort"]) AND $_GET["sort"] == "streak") 
	$TopData[$c]["maxstreak"] = "<span class='highlight_top'>".$TopData[$c]["maxstreak"]."</span>";
	
	$c++;
	}	
	if ( isset($GeoIP) AND $GeoIP == 1) geoip_close($GeoIPDatabase);

?>