<?PHP
   //if (!defined('IN_OS')) exit;
   
   if (isset($_GET["replayLoc"]) ) 
   {$replayloc = "../".EscapeStr($_GET["replayLoc"]); }

   if(file_exists("$replayloc"))
   {
   $drawTable = "replay_header";
   include("themes/".$DefaultStyle."/game_log.php");

	require('inc/replay_parser/chat.php');

	$replay = new replay($replayloc);
	if (!isset($error)) {
	
	///////////////////     COLORS            ////////////
	
			$firstBlood = true;
		$i = 1;
		foreach ($replay->teams as $team=>$players) {
			if ($team != 12) {	
				foreach ($players as $player) {          
					// remember there's no color in tournament replays from battle.net website
					if ($player['color']) {
						//echo('<span class="'.$player['color'].'">'.$player['color'].'</span>');
						// since version 2.0 of the parser there's no players array so
						// we have to gather colors and names earlier as it will be harder later ;)
						$colors[$player['player_id']] = $player['color'];
						$names[$player['player_id']] = $player['name'];
					}
				}
				$i++;
			}
		}
		for($i = 0; $i <= 14; $i++)
		{
			switch($i) {
			
			case 0:
				$slotname[$i] = 'The Sentinel';
				$slotcolor[$i] = 'sentinel';
				break;
			case 1:
				for($n = 0; $n < 12; $n++)
				{
					if(isset($colors[$n]))
					{
						if($colors[$n] == 'blue')
						{
							$playerID = $n;
						}
					}
				}				
				$slotname[$i] = $names[$playerID];
				$slotcolor[$i] = $colors[$playerID];
				break;
			case 2:
				for($n = 0; $n < 12; $n++)
				{
					if(isset($colors[$n]))
					{
						if($colors[$n] == 'teal')
						{
							$playerID = $n;
						}
					}
				}				
				$slotname[$i] = $names[$playerID];
				$slotcolor[$i] = $colors[$playerID];
				break;
			case 3:
				for($n = 0; $n < 12; $n++)
				{
					if(isset($colors[$n]))
					{
						if($colors[$n] == 'purple')
						{
							$playerID = $n;
						}
					}
				}				
				$slotname[$i] = $names[$playerID];
				$slotcolor[$i] = $colors[$playerID];
				break;
			case 4:
				for($n = 0; $n < 12; $n++)
				{
					if(isset($colors[$n]))
					{
						if($colors[$n] == 'yellow')
						{
							$playerID = $n;
						}
					}
				}				
				$slotname[$i] = $names[$playerID];
				$slotcolor[$i] = $colors[$playerID];
				break;
			case 5:
				for($n = 0; $n < 12; $n++)
				{
					if(isset($colors[$n]))
					{
						if($colors[$n] == 'orange')
						{
							$playerID = $n;
						}
					}
				}				
				$slotname[$i] = $names[$playerID];
				$slotcolor[$i] = $colors[$playerID];
				break;
			case 6:
				$slotname[$i] = 'The Scourge';
				$slotcolor[$i] = 'scourge';
				break;
			case 7:
				for($n = 0; $n < 12; $n++)
				{
					if(isset($colors[$n]))
					{
						if($colors[$n] == 'pink')
						{
							$playerID = $n;
						}
					}
				}				
				$slotname[$i] = $names[$playerID];
				$slotcolor[$i] = $colors[$playerID];
				break;
			case 8:
				for($n = 0; $n < 12; $n++)
				{
					if(isset($colors[$n]))
					{
						if($colors[$n] == 'gray')
						{
							$playerID = $n;
						}
					}
				}				
				$slotname[$i] = $names[$playerID];
				$slotcolor[$i] = $colors[$playerID];
				break;
			case 9:
				for($n = 0; $n < 12; $n++)
				{
					if(isset($colors[$n]))
					{
						if($colors[$n] == 'light-blue')
						{
							$playerID = $n;
						}
					}
				}				
				$slotname[$i] = $names[$playerID];
				$slotcolor[$i] = $colors[$playerID];
				break;
			case 10:
				for($n = 0; $n < 12; $n++)
				{
					if(isset($colors[$n]))
					{
						if($colors[$n] == 'dark-green')
						{
							$playerID = $n;
						}
					}
				}				
				$slotname[$i] = $names[$playerID];
				$slotcolor[$i] = $colors[$playerID];
				break;
			case 11:
				for($n = 0; $n < 12; $n++)
				{
					if(isset($colors[$n]))
					{
						if($colors[$n] == 'brown')
						{
							$playerID = $n;
						}
					}
				}				
				$slotname[$i] = $names[$playerID];
				$slotcolor[$i] = $colors[$playerID];
				break;
			case 12:		
				$slotname[$i] = 'Neutral Creeps';
				$slotcolor[$i] = 'system';
				break;	
			case 13:		
				$slotname[$i] = 'The Sentinel';
				$slotcolor[$i] = 'sentinel';
				break;
			case 14:
				$slotname[$i] = 'The Scourge';
				$slotcolor[$i] = 'scourge';
				break;
			}
		}
		$colors[''] = 'system';
		$names[''] = 'System';
		
         //////////////       COLORS              /////////////
		 	$str = "<div class='hid' align='center'>
   <table class='tableA'><tr><td align='center'><b>Game Log:</b></td></tr>
   <tr>
   <td colspan='13'>
   <table width='80%'><tr>
   <th><div align='right'>Time</div></th>
   <th style='width:100px;padding-right:4px;'><div align='right'>Player &nbsp;</div></th>
   <th></th></tr>";
			
		if ($replay->chat) {
			foreach ($replay->chat as $content) {
				$time = $content['time'];
				$mode = $content['mode'];
				$playerID = $content['player_id'];
				$playerName = $names[$playerID];
				$playerColor = $colors[$playerID];
				$text = convEnt2($content['text']);
				$ply = "<span class='GameSystem'><i>(System)</i></span>";
			
				
	if($mode == 'All' || getTeam($playerColor) == 1) 
	{$ply = "<a href='".$website."?u=$playerName'><span class='$playerColor'>$playerName</span></a>";} 
	
	if($mode == 'All' || getTeam($playerColor) == 2) 
	{$ply = "<a href='".$website."?u=$playerName'><span class='$playerColor'>$playerName</span></a>";}

	$timeSec = secondsToTime($time/1000);
				
				$drawTable = "replay_left";
				include("".OS_CURRENT_THEME_PATH."/game_log.php");
				
				if($mode == 'All') 
				{echo "<td class='all'>[All] $text</td>"; $str .= "<td class='all'>[All] $text</td>";}
				
				else if($mode == 'System') {
					 if($content['type'] == 'Start') 
					 {
					 $drawTable = "replay_text";
					 include("".OS_CURRENT_THEME_PATH."/game_log.php");
					 }
					 
					        else if($content['type'] == 'Hero')
							{
								$victim = trim($content['victim']);
								$killer = $content['killer'];
	if($firstBlood)
	{
		if($content['killer'] < 12)
		{
		$drawTable = "replay_first_blood";
		include("".OS_CURRENT_THEME_PATH."/game_log.php");
		$firstBlood = false;
		}
		else
		{
		$drawTable = "replay_action";
		include("".OS_CURRENT_THEME_PATH."/game_log.php");
		}
	 }
	  else
	  {
	  if($victim == $killer)
	  {
	  $drawTable = "replay_victim_killer";
	  include("".OS_CURRENT_THEME_PATH."/game_log.php");
	  }
	  else if(($victim < 6 && $killer < 6) || ($victim > 6 && $killer > 6) && $killer <= 11)
	  {
	  $drawTable = "replay_denie_teammate";
	  include("".OS_CURRENT_THEME_PATH."/game_log.php");
	  }
	  else
	  {
	  $drawTable = "replay_hero_action";
	  include("".OS_CURRENT_THEME_PATH."/game_log.php");
	  }
	}
  }
	  else if($content['type'] == 'Courier')
	  {
	  $victim = trim($content['victim']);
	  $killer = $content['killer'];
	  $drawTable = "replay_courier";
	  include("".OS_CURRENT_THEME_PATH."/game_log.php");
	  }
	  else if($content['type'] == 'Tower')
	  {
	  $killer = $content['killer'];
	  $drawTable = "replay_tower";
	  include("".OS_CURRENT_THEME_PATH."/game_log.php");
	  }
	  else if($content['type'] == 'Rax')
	  {
	  $drawTable = "replay_rax";
	  include("".OS_CURRENT_THEME_PATH."/game_log.php");
	  $killer = $content['killer'];
	  }
	  else if($content['type'] == 'Throne') {
	  $drawTable = "replay_throne";
	  include("".OS_CURRENT_THEME_PATH."/game_log.php");
	  }
	  else if($content['type'] == 'Tree')   {
	  $drawTable = "replay_tree";
	  include("".OS_CURRENT_THEME_PATH."/game_log.php");
	  }
	  }
	 else
	{
	if ($mode == 9)
	  {
	  $drawTable = "replay_priv";
	  include("".OS_CURRENT_THEME_PATH."/game_log.php");
	  }
       else
       {
	   if(getTeam($playerColor) == 1) {
	   $drawTable = "replay_allies1";
	   include("".OS_CURRENT_THEME_PATH."/game_log.php");
	   }
	     else
	     { 
	     $drawTable = "replay_allies2";
	     include("".OS_CURRENT_THEME_PATH."/game_log.php");
	     }
	    }
       }
	}

			}
		}	
	}
	$drawTable = "replay_footer_table";
	include("".OS_CURRENT_THEME_PATH."/game_log.php");
	//$drawTable = "last_div";
    //include("themes/".$DefaultStyle."/game_log.php");
		 
		 //STORE PARSED DATA INTO ARRAY. WITH textarea YOU CAN OUTPUT HTML CODE FOR GAME LOG.
		
		 $htmlOutput = $str;
		 
		 $permLink = "http://".$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"]);
		 /*
		 $parts=explode(",", $str);
		 
		 for($i=0; $i <= count($parts)-1; ++$i) {
		 echo "<div align='left'>$parts[$i]</div>";
		 $fix = str_replace("<a href='user.php?u=","<a href='$permLink/user.php?u=",$parts[$i]);
		 $fix = str_replace("<span class='","<span style='color:",$fix);
		 $htmlOutput .= "<div style=\"text-align: left;\">$fix</div>\n";
		 }
		 /*
		  echo "<div align='left'><textarea style='width:400px;height:140px;'>".$htmlOutput."</textarea></div>";
		 */


?>		