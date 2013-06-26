<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

    //REMOVE FROM LIST
	if ( isset( $_GET["compare"]) AND isset($_GET["remove"]) AND is_numeric($_GET["remove"]) AND isset($_SESSION["compare_list"]) ) {
	   
	   $CompareList = explode(",", $_SESSION["compare_list"]);
	   $Remove = safeEscape( (int) $_GET["remove"] );
	   $_SESSION["compare_list"] = "";
	   foreach ( $CompareList as $List ) {
	   
	    if ( $List!= $Remove) if ($List>=1) $_SESSION["compare_list"].= safeEscape( (int) $List).",";
	   
	   }
	   
	   
	   if ( isset($_GET["page"]) AND is_numeric($_GET["page"]) ) $page = '&page='.safeEscape( (int) $_GET["page"] ); else $page = "";
	   header("location: ".OS_HOME."?top&compare".$page); die;
	   
	}
	
	//Clear list
	if (  (isset($_POST["clear_compare_list"]) AND isset($_SESSION["compare_list"])) OR isset($_GET["clear_list"]) ) unset($_SESSION["compare_list"]); 
	
	if (isset($_POST["compare_players"])) { header("location: ".OS_HOME."?compare_players"); die; }
	
	if ( isset( $_POST["compare"] ) ) {
	   
	   if (isset($_POST["compare_list_add"])) {
		if ( !isset($_SESSION["compare_list"]) ) $_SESSION["compare_list"] = "";
		
	    foreach( $_POST["compare"] as $c) {
		
		if ( !in_array($c, explode(",", $_SESSION["compare_list"]) ) )
	    if ($c>=1 ) $_SESSION["compare_list"].= safeEscape( (int) $c).",";
	    }
		
	    //$_SESSION["compare_list"] = substr($_SESSION["compare_list"], 0, strlen($_SESSION["compare_list"])-1 );
		
		//Limit - players per list
		$CheckCompareList = explode(",", $_SESSION["compare_list"]);
		
		  if ( count($CheckCompareList)>$MaxPlayersToCompare ) {
		    $count = 0;
			$_SESSION["compare_list"] = "";
			foreach($CheckCompareList as $pl) {
			$count++;
			if ( $count<=$MaxPlayersToCompare ) $_SESSION["compare_list"].= safeEscape( (int) $pl).",";
			}
		  }
		  
	   }
	   
	   if ( isset($_GET["page"]) AND is_numeric($_GET["page"]) ) $page = '&page='.safeEscape( (int) $_GET["page"] ); else $page = "";
	   
	   header("location: ".OS_HOME."?top&compare".$page); die;
	   //COMPARING PLAYERS
 	}
	
	//if (  isset($_SESSION["compare_list"])) echo( $_SESSION["compare_list"]);
	
	if ( (isset($_GET["compare"]) OR isset($_GET["compare_players"]) ) AND isset($_SESSION["compare_list"]) AND !empty($_SESSION["compare_list"]) ) {
	   
	   $CompareIDArray = explode(",", $_SESSION["compare_list"]);
	   $sqlCompare = "SELECT * FROM ".OSDB_STATS." WHERE id>=1 AND (";
	   
	   foreach ( $CompareIDArray as $PlayerID ) {
	   if (!empty($PlayerID) AND is_numeric($PlayerID) ) $sqlCompare.="id = ".(int)$PlayerID." OR ";
	   }
	   
	   $IDs = substr($_SESSION["compare_list"], 0, strlen($_SESSION["compare_list"])-1 )." ";
	   $ORD = "ORDER BY FIELD(id,".safeEscape($IDs).")";
	   
	   $sqlCompare = substr($sqlCompare, 0, strlen($sqlCompare)-3 ).") ".$ORD."";
	   
	   $sth = $db->prepare( $sqlCompare );
	   
	   
	   $resultCompare = $sth->execute();
	   $c=0;
	   $ComparePlayersData = array();
	   $temp_ck      = 0; //creeps
	   $temp_games   = 0; //games
	   $temp_wins    = 0; //wins %
	   $temp_stay    = 0; //stay ratio
	   $temp_apg     = 0; //assists per game
	   $temp_cd      = 0; //denies
	   $temp_kd      = 0; //KD Ratio
	   $temp_ne      = 0; //Neutrals
	   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
       $ComparePlayersData[$c]["id"]        = (int)($row["id"]);
	   $ComparePlayersData[$c]["player"]  = ($row["player"]);

	   $ComparePlayersData[$c]["score"]  = number_format($row["score"],0);
	   $ComparePlayersData[$c]["games"]  = number_format($row["games"],0);
	   $ComparePlayersData[$c]["wins"]  = number_format($row["wins"],0);
	   $ComparePlayersData[$c]["losses"]  = number_format($row["losses"],0);
	   $ComparePlayersData[$c]["draw"]  = number_format($row["draw"],0);
	   $ComparePlayersData[$c]["kills"]  = number_format($row["kills"],0);
	   $ComparePlayersData[$c]["deaths"]  = number_format($row["deaths"],0);
	   $ComparePlayersData[$c]["assists"]  = number_format($row["assists"],0);
	   $ComparePlayersData[$c]["creeps"]  = number_format($row["creeps"],0);
	   $ComparePlayersData[$c]["denies"]  = number_format($row["denies"],0);
	   $ComparePlayersData[$c]["neutrals"]  = number_format($row["neutrals"],0);
	   $ComparePlayersData[$c]["towers"]  = ($row["towers"]);
	   $ComparePlayersData[$c]["rax"]  = ($row["rax"]);
	   $ComparePlayersData[$c]["banned"]  = ($row["banned"]);
	   $ComparePlayersData[$c]["warn_expire"]  = ($row["warn_expire"]);
	   $ComparePlayersData[$c]["warn"]  = ($row["warn"]);
	   $ComparePlayersData[$c]["admin"]  = ($row["admin"]);
	   $ComparePlayersData[$c]["safelist"]  = ($row["safelist"]);
	   $ComparePlayersData[$c]["ip"]  = ($row["ip"]);
	   $ComparePlayersData[$c]["leaver"]  = ($row["leaver"]);
	   

	   
	   	if ($row["games"] >0 )
	    $ComparePlayersData[$c]["stayratio"] = ROUND($row["games"]/($row["games"]+$row["leaver"]), 3)*100;
	    else $ComparePlayersData[$c]["stayratio"] = 0;
	
	    if ($row["wins"] >0 )
	    $ComparePlayersData[$c]["winslosses"] = ROUND($row["wins"]/($row["wins"]+$row["losses"]), 3)*100;
	    else $ComparePlayersData[$c]["winslosses"] = 0;
		
	   	if ($row["kills"] >0 )
	    $ComparePlayersData[$c]["kpg"] = ROUND($row["kills"]/$row["games"],2); 
	    else $ComparePlayersData[$c]["kpg"] = 0;
		
	   	if ($row["assists"] >0 )
	    $ComparePlayersData[$c]["apg"] = ROUND($row["assists"]/$row["games"],2); 
	    else $ComparePlayersData[$c]["apg"] = 0;
		
		if ($row["games"]>=1 AND $row["creeps"]>=1)
	     $ComparePlayersData[$c]["ckpg"] = ROUND($row["creeps"]/$row["games"],2);
         else $ComparePlayersData[$c]["ckpg"] = 0;		 
		 
		if ($row["games"]>=1 AND $row["neutrals"]>=1)
	     $ComparePlayersData[$c]["ne"] = ROUND($row["neutrals"]/$row["games"],2); 
		 else $ComparePlayersData[$c]["ne"] = 0;	
		 
	    if ($row["games"] >0 AND $row["denies"]>0 )
	    $ComparePlayersData[$c]["cd"] = ROUND($row["denies"]/$row["games"],2); 
	    else $ComparePlayersData[$c]["cd"] = 0;
		
		//KD Ratio
		if ($row["deaths"]>=1) $ComparePlayersData[$c]["kd"]  = ROUND($row["kills"] / $row["deaths"],2);
        else $ComparePlayersData[$c]["kd"] = $row["kills"];
		 
		 
		//MOST GAMES
		if ( !isset($MostGames) )  {
		$MostGames = ($row["player"]); 
		$temp_games = ( ($ComparePlayersData[$c]["games"]) ); 
		$PlayerGames = ( ($ComparePlayersData[$c]["games"]) ); 
		}

	    if ( ( ($ComparePlayersData[$c]["games"])) > $temp_games ) {
	    $MostGames = ($row["player"]); $PlayerGames = ($ComparePlayersData[$c]["games"]); 
		$temp_games= ($ComparePlayersData[$c]["games"]);
	    }
		
		//MOST WINS
		if ( !isset($MostWins) )  {
		$MostWins = ($row["player"]); 
		$temp_wins = ( ($ComparePlayersData[$c]["winslosses"]) ); 
		$PlayerWins = ( ($ComparePlayersData[$c]["winslosses"]) ); 
		}

	    if ( ( ($ComparePlayersData[$c]["winslosses"])) > $temp_wins ) {
	    $MostWins = ($row["player"]); $PlayerWins = OS_Number($ComparePlayersData[$c]["winslosses"]); 
		$temp_wins= ($ComparePlayersData[$c]["winslosses"]);
	    }
		
		//STAY RATIO
		if ( !isset($MostStay) )  {
		$MostStay = ($row["player"]); 
		$temp_stay = ( ($ComparePlayersData[$c]["stayratio"]) ); 
		$PlayerStay = ( ($ComparePlayersData[$c]["stayratio"]) ); 
		$PlayerStayGames = $row["games"];
		}
        //Highest stay ratio + games played 

	    if ( ( ($ComparePlayersData[$c]["stayratio"])) > $temp_stay ) {
	      $MostStay = ($row["player"]); $PlayerStay = ($ComparePlayersData[$c]["stayratio"]); 
		  $temp_stay= ($ComparePlayersData[$c]["stayratio"]);
		  $PlayerStayGames = $row["games"];
	    }
		//Same stay ratio, but more games
		if ( $temp_stay == ( ($ComparePlayersData[$c]["stayratio"])) ) {
		  if ( $row["games"]>$PlayerStayGames) {
          $PlayerStayGames = $row["games"];
		  $temp_stay= ($ComparePlayersData[$c]["stayratio"]);
		  $MostStay = ($row["player"]); $PlayerStay = ($ComparePlayersData[$c]["stayratio"]); 
		  }
		}
		
		//BEST KD Ratio
		//MOST WINS
		if ( !isset($MostKD) )  {
		$MostKD = ($row["player"]); 
		$temp_kd = ( ($ComparePlayersData[$c]["kd"]) ); 
		$PlayerKD = ( ($ComparePlayersData[$c]["kd"]) ); 
		}

	    if ( ( ($ComparePlayersData[$c]["kd"])) > $temp_kd ) {
	    $MostKD = ($row["player"]); $PlayerKD = ($ComparePlayersData[$c]["kd"]); 
		$temp_kd= ($ComparePlayersData[$c]["kd"]);
	    }

		//MOST KILLS PER GAME
		if ( !isset($MostKPG) )  {
		$MostKPG = ($row["player"]); 
		$temp_kpg = ( ($ComparePlayersData[$c]["kpg"]) ); 
		$PlayerKPG = ( ($ComparePlayersData[$c]["kpg"]) ); 
		}

	    if ( ( ($ComparePlayersData[$c]["kpg"])) > $temp_kpg ) {
	    $MostKPG = ($row["player"]); $PlayerKPG = ($ComparePlayersData[$c]["kpg"]); 
		$temp_kpg= ($ComparePlayersData[$c]["kpg"]);
	    }
		
		//MOST ASSISTS PER GAME
		if ( !isset($MostAPG) )  {
		$MostAPG = ($row["player"]); 
		$temp_apg = ( ($ComparePlayersData[$c]["apg"]) ); 
		$PlayerAPG = ( ($ComparePlayersData[$c]["apg"]) ); 
		}

	    if ( ( ($ComparePlayersData[$c]["apg"])) > $temp_apg ) {
	    $MostAPG = ($row["player"]); $PlayerAPG = ($ComparePlayersData[$c]["apg"]); 
		$temp_apg= ($ComparePlayersData[$c]["apg"]);
	    }
		
		//MOST CREEP KILLS
		if ( !isset($MostCK) )  {
		
		$MostCK = ($row["player"]); 
		$temp_ck = ( ($ComparePlayersData[$c]["ckpg"]) ); 
		$PlayerCK = ( ($ComparePlayersData[$c]["ckpg"]) ); 
		}

	    if ( ( ($ComparePlayersData[$c]["ckpg"])) > $temp_ck ) {
	    $MostCK = ($row["player"]); $PlayerCK = ($ComparePlayersData[$c]["ckpg"]); 
		$temp_ck= ($ComparePlayersData[$c]["ckpg"]);
	    }
		
		//MOST CREEP DENIES
		if ( !isset($MostCD) )  {
		
		$MostCD = ($row["player"]); 
		$temp_cd = ( ($ComparePlayersData[$c]["cd"]) ); 
		$PlayerCD = ( ($ComparePlayersData[$c]["cd"]) ); 
		}

	    if ( ( ($ComparePlayersData[$c]["cd"])) > $temp_cd ) {
	    $MostCD = ($row["player"]); $PlayerCD = ($ComparePlayersData[$c]["cd"]); 
		$temp_cd= ($ComparePlayersData[$c]["cd"]);
	    }
		
		//MOST NEUTRALS
		if ( !isset($MostNE) )  {
		
		$MostNE = ($row["player"]); 
		$temp_ne = ( ($ComparePlayersData[$c]["ne"]) ); 
		$PlayerNE = ( ($ComparePlayersData[$c]["ne"]) ); 
		}

	    if ( ( ($ComparePlayersData[$c]["ne"])) > $temp_ne ) {
	    $MostNE = ($row["player"]); $PlayerNE = ($ComparePlayersData[$c]["ne"]); 
		$temp_ne= ($ComparePlayersData[$c]["ne"]);
	    }
		
		
		$ComparePlayersData[$c]["points"] = 0; //set overall points to 0
		$c++;
	   }
	    
		$c=0;
	    foreach ($ComparePlayersData as $Player) {
		 if ( strtolower($Player["player"]) ==  strtolower($MostGames) ) $ComparePlayersData[$c]["points"]+=1;
		 if ( strtolower($Player["player"]) ==  strtolower($MostWins) )  $ComparePlayersData[$c]["points"]+=1;
		 if ( strtolower($Player["player"]) ==  strtolower($MostStay) )  $ComparePlayersData[$c]["points"]+=1;
		 if ( strtolower($Player["player"]) ==  strtolower($MostKD) )    $ComparePlayersData[$c]["points"]+=1;
		 if ( strtolower($Player["player"]) ==  strtolower($MostKPG) )   $ComparePlayersData[$c]["points"]+=1;
		 if ( strtolower($Player["player"]) ==  strtolower($MostAPG) )   $ComparePlayersData[$c]["points"]+=1;
		 if ( strtolower($Player["player"]) ==  strtolower($MostCK) )    $ComparePlayersData[$c]["points"]+=1;
		 if ( strtolower($Player["player"]) ==  strtolower($MostCD) )    $ComparePlayersData[$c]["points"]+=1;
		 if ( strtolower($Player["player"]) ==  strtolower($MostNE) )    $ComparePlayersData[$c]["points"]+=1;
		 $c++;
		}
	   
	   if ( isset($_GET["compare_players"]) ) {
	    AddEvent("os_head","OS_GoogleChart");
		function OS_GoogleChart() {
?>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<?php
		}
	   }
	}
	
	if ( isset($_GET["compare_players"]) AND !isset($_GET["empty"]) AND empty($_SESSION["compare_list"]) ) {
	header("location: ".OS_HOME."?compare_players&empty");
	die;
	}
?>