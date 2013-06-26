<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

  //GAMES
  if ( isset($_GET["games"]) OR isset($_GET["u"]) ) {
  
  	$HomeTitle = ($lang["game_archive"]);
	$HomeDesc = os_strip_quotes($lang["game_archive"]);
	$HomeKeywords = strtolower( os_strip_quotes($lang["game_archive"])).','.$HomeKeywords;
	
	//Get date of first game
	$sth = $db->prepare("SELECT * FROM ".OSDB_GAMES." WHERE id>=1 AND (map) LIKE ('%dota%') 
	ORDER BY datetime ASC LIMIT 1");
	
	$result = $sth->execute();

	if ( $sth->rowCount()>=1 ) {
	  $row = $sth->fetch(PDO::FETCH_ASSOC);
	  $StartYear = date("Y", strtotime($row["datetime"]));
	  $StartMonth = date("m", strtotime($row["datetime"]));
	} else  {
	$StartYear = date("Y", time() );
	$StartMonth = date("m", time() );
	}
  
	  
  if ( (isset($_GET["uid"]) AND is_numeric($_GET["uid"])) OR isset($_GET["u"])  ) {
     
	 $sqlFilter = "";
	 
	 if ( isset($_GET["u"]) ) $id = safeEscape( (int) $_GET["u"] );
	 else
	 $id = safeEscape( (int) $_GET["uid"] );
	 
	 if ( isset($_GET["h"]) ) {
	   $hero = safeEscape(strtoupper($_GET["h"]) );
	   $sqlFilter = "AND dp.hero = '".$hero."' ";
	   
     $sql = "SELECT s.*, g.id, g.map, g.gamename, g.datetime, g.ownername, g.duration,  g.creatorname, dg.winner, 
	 g.gamestate  AS type, s.player, dp.kills, dp.deaths, dp.creepkills, dp.creepdenies, dp.assists, dp.hero, dp.neutralkills, dp.newcolour, gp.`left`
	 FROM ".OSDB_STATS." as s 
	 LEFT JOIN ".OSDB_GP." as gp ON (gp.name) = (s.player)
	 LEFT JOIN ".OSDB_GAMES." as g ON g.id = gp.gameid
	 LEFT JOIN ".OSDB_DG." as dg ON g.id = dg.gameid 
	 LEFT JOIN ".OSDB_DP." as dp ON dp.gameid = dg.gameid AND gp.colour = dp.colour
	 WHERE s.id = :id AND (g.map) LIKE ('%dota%') AND g.duration>='".$MinDuration."' ".$sqlFilter."
	 ";
	 $sth = $db->prepare($sql);
	 
	 $sth->bindValue(':id', $id, PDO::PARAM_INT); 
	 //if (!empty($sqlFilter) ) $sth->bindValue(':hero', $hero, PDO::PARAM_STR); 
	 
	 $result = $sth->execute();
	 
	$numrows = $sth->rowCount(); 
    $result_per_page = $GamesPerPage;
	 } else {
	 
	 $sql = "SELECT COUNT(*) 
	 FROM ".OSDB_STATS." as s 
	 LEFT JOIN ".OSDB_GP." as gp ON (gp.name) = (s.player)
	 LEFT JOIN ".OSDB_GAMES." as g ON g.id = gp.gameid
	 LEFT JOIN ".OSDB_DG." as dg ON g.id = dg.gameid 
	 WHERE s.id = :id 
	 AND (g.map) LIKE ('%dota%') 
	 AND g.duration>='".$MinDuration."' 
	 LIMIT 1";
	 

     $sth = $db->prepare($sql);
	 $sth->bindValue(':id', $id, PDO::PARAM_INT); 
	 $result = $sth->execute();
  
     $r = $sth->fetch(PDO::FETCH_NUM);
     $numrows = $r[0]; 
     $result_per_page = $GamesPerPage;
  }
  
  if ( $numrows<=0) {
  require_once(OS_PLUGINS_DIR.'index.php');
  os_init();
  header('location: '.OS_HOME.'?404'); }
  
  $draw_pagination = 0;
  include('inc/pagination.php');
  $draw_pagination = 1;
  
	$sqlFilter.="ORDER BY g.datetime DESC";
	
	$sql = getUserGames ($id, $MinDuration, $offset, $rowsperpage, $sqlFilter );
	 
  }
  else   
  {
  //FILTER
  $filter  = "";
    	unset($sth);
  if ( isset($_GET["m"]) AND is_numeric($_GET["m"]) AND $_GET["m"]<=12 AND $_GET["m"]>=1 ) {
  $m = safeEscape( (int) $_GET["m"] );
  $filter.= "AND MONTH(datetime) = '".(int)$m."'";
  }
  
  if ( isset($_GET["y"]) AND is_numeric($_GET["y"]) AND $_GET["y"]<=date("Y") AND $_GET["y"]>=1998 ) {
  $y = safeEscape( (int) $_GET["y"] );
  $filter.= "AND YEAR(datetime) = '".(int)$y."'";
  }
  
  $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_GAMES." 
  WHERE (map) LIKE ('%dota%') AND duration>='".$MinDuration."' ".$filter." LIMIT 1");
  
  $result = $sth->execute();	  
  
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  $result_per_page = $GamesPerPage;
  $draw_pagination = 0;
  include('inc/pagination.php');
  $draw_pagination = 1;
	  
   $sql = getAllGames($MinDuration, $offset, $rowsperpage, $filter, "datetime DESC" );
	  
    }
    $sth = $db->prepare( $sql  );
	$result = $sth->execute();	  
	$c=0;
    $GamesData = array();
	
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	
	$GamesData[$c]["id"]        = (int)($row["id"]);
	$GetMap = convEnt2(substr($row["map"], strripos($row["map"], '\\')+1));
	$Map = explode(".w", $GetMap );
	$GamesData[$c]["map"]  = $Map[0];
	//$GamesData[$c]["map"]  = convEnt2(substr($row["map"], strripos($row["map"], '\\')+1));
	//$GamesData[$c]["map"] = reset( explode(".w", $GamesData[$c]["map"] ) );
	//$GamesData[$c]["map"] = substr($GamesData[$c]["map"],0,20);
	$GamesData[$c]["datetime"]  = ($row["datetime"]);
	$GamesData[$c]["gamename"]  = ($row["gamename"]);
	$GamesData[$c]["ownername"]  = ($row["ownername"]);
	$GamesData[$c]["duration"]  = ($row["duration"]);
	$GamesData[$c]["creatorname"]  = ($row["creatorname"]);
	
	if ( isset($_GET["h"]) AND file_exists("img/heroes/".$_GET["h"].".gif") )
	$GamesData[$c]["hero_history"]  = $_GET["h"].""; else $GamesData[$c]["hero_history"] = "";
	
	$GamesData[$c]["winner"]  = ($row["winner"]);
	$GamesData[$c]["type"]  = OS_GetGameState($row["type"], $lang["gamestate_priv"] , $lang["gamestate_pub"]);
	
	$GamesData[$c]["gamestate"]  = ($row["type"]);
	if (isset($row["server"]) ) $GamesData[$c]["server"]  = ($row["server"]);
	
	if ( isset($row["newcolour"]) ) {
	$GamesData[$c]["newcolour"]  = ($row["newcolour"]);
	
	if ( $row["newcolour"] <=5  AND $row["winner"] == 1 )  $GamesData[$c]["win"]  = 1; else 
	if ( $row["newcolour"] >5   AND $row["winner"] == 1 )  $GamesData[$c]["win"]  = 2; else 
	if ( $row["newcolour"] >5   AND $row["winner"] == 2 )  $GamesData[$c]["win"]  = 1; else 
	if ( $row["newcolour"] <=5  AND $row["winner"] == 2 )  $GamesData[$c]["win"]  = 2; 
	} else $GamesData[$c]["newcolour"]  = 0;
	if ( $row["winner"] == 0 ) $GamesData[$c]["win"] = 0;
	
	if ( (isset($_GET["uid"]) AND is_numeric($_GET["uid"])) OR isset($_GET["u"])  )
	if ($row["newcolour"] >5) $GamesData[$c]["newcolour"]-=1; //fix bug with colour (slot 6-7)

	if ( isset( $row["left"] ) ) {
	$GamesData[$c]["left"] = $row["left"]; 
	
	if ( $row["left"] <= ( $row["duration"] - $LeftTimePenalty) AND $row["winner"]!=0 ) $GamesData[$c]["leaver"] = 1; 
	else $GamesData[$c]["leaver"] = 0;
	} 
	   else {
	   $GamesData[$c]["leaver"] = 0;
	   $GamesData[$c]["left"] = ""; 
	   }

	//echo $GamesData[$c]["leaver"];
	
	//REPLAY
	 $duration = secondsToTime($row["duration"]);
     $replayDate =  strtotime($row["datetime"]);  //3*3600 = +3 HOURS,   +0 minutes.
     $replayDate = date("Y-m-d H:i",$replayDate);
     $gametimenew = substr(str_ireplace(":","-",date("Y-m-d H:i",strtotime($replayDate))),0,16);
	 $gid =  (int)($row["id"]);
	 $gamename = $GamesData[$c]["gamename"];
	 include('inc/get_replay.php');
	 
	 if ( file_exists($replayloc) ) $GamesData[$c]["replay"] = $replayloc; else $GamesData[$c]["replay"] = "";
	 //END REPLAY
	
	if (isset($row["hero"]) )         
	{
	$GamesData[$c]["hero"]  = strtoupper($row["hero"]);   
    if ( empty($row["hero"])  ) $GamesData[$c]["hero"] = "blank";
	
	if ( !file_exists("img/heroes/".$GamesData[$c]["hero"].".gif") ) $GamesData[$c]["hero"]  = "blank";
	
	}
	else $GamesData[$c]["hero"]  = "blank";
	if (isset($row["kills"]) )        $GamesData[$c]["kills"]  = ($row["kills"]);   else $GamesData[$c]["kills"]  = "0";
	if (isset($row["deaths"]) )       $GamesData[$c]["deaths"]  = ($row["deaths"]); else $GamesData[$c]["deaths"]  = "0";
	if (isset($row["creepkills"]) )   $GamesData[$c]["creepkills"]  = ($row["creepkills"]);   else $GamesData[$c]["creepkills"]  = "0";
	if (isset($row["creepdenies"]) )  $GamesData[$c]["creepdenies"]  = ($row["creepdenies"]); else $GamesData[$c]["creepdenies"]  = "0";
	if (isset($row["assists"]) )      $GamesData[$c]["assists"]  = ($row["assists"]);       else $GamesData[$c]["assists"]  = "0";
	if (isset($row["neutralkills"]) ) $GamesData[$c]["neutrals"]  = ($row["neutralkills"]); else $GamesData[$c]["neutrals"]  = "0";
	
	if (isset($row["player"]) ) $GamesData[$c]["player"]  = ($row["player"]);
	$c++;
	}
	
	if ( $sth->rowCount()<=0) {
	header("location:".OS_HOME."?404");
	die;
    }	
	//$db->free($result);	
  }
  
  // ----- > END GAMES
?>