<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

      $BanAppeal = "";
	  
	  if (isset($_POST["submit_appeal"]) ) {
	     $player = safeEscape(trim($_POST["player_appeal"]));
		 $subject = safeEscape(trim($_POST["subject"]));
		 $reason = safeEscape(trim($_POST["message"]));
		 $reason = my_nl2br( trim($_POST["message"]) );
		 $reason = nl2br($reason);
		 $reason = EscapeStr( ($reason) );
		 $game_url   = EscapeStr(trim($_POST["game_url"]));
		 $replay_url = EscapeStr(trim($_POST["replay_url"]));
		 $errors = "";
		 
		 if (strlen($player)<=2 ) $errors.="<div>".$lang["error_report_player"]."</div>";
		 if (strlen($reason)<=3 ) $errors.="<div>".$lang["error_report_reason"]."</div>";
		  if ( !is_logged() )  $errors ="<div>".$lang["error_report_login"]."</div>";
		 
		 if ( isset($_SESSION["last_report"]) AND $_SESSION["last_report"] + $BanReportTime > time() ) {
		 $TimeLeft = time() - $_SESSION["last_report"];
		 $errors = "<div>".$lang["error_report_time2"]." ".($BanReportTime-$TimeLeft)." ".$lang["error_sec"]." </div>";
		 }
		 
		 if ( empty($errors) ) {
            $sth = $db->prepare("SELECT * FROM ".OSDB_BANS." WHERE name=:player LIMIT 1");
			$sth->bindValue(':player', $player, PDO::PARAM_STR); 
			$result = $sth->execute();
			
			if ($sth->rowCount()<=0 ) $errors.="<div>".$lang["error_no_player"]."</div>";
			
			if ( empty($errors) ) {
			  $row = $sth->fetch(PDO::FETCH_ASSOC);
			  $PID = $row["id"];
			  
			  $db->insert(OSDB_APPEALS, array(
			  "player_id" => (int)$PID,
			  "player_name" => $player,
			  "user_id" => (int) $_SESSION["user_id"],
			  "reason" => $reason,
			  "game_url" => $game_url,
			  "replay_url" => $replay_url,
			  "added" => (int)time(),
			  "status" => 0,
			  "user_ip" => $_SERVER["REMOTE_ADDR"]
                                 ));

			  $_SESSION["last_report"] = time();
			  
	          require_once('plugins/index.php');
	          os_init();
			  header('location: '.OS_HOME.'?ban_appeal&success'); die;
			}
			
         }		 
	  }
	  
	  if ( !empty($_GET["ban_appeal"]) AND strlen($_GET["ban_appeal"])>=2  ) {
	  
	        $BanAppeal = safeEscape( trim($_GET["ban_appeal"]));
	  
            $sth = $db->prepare("SELECT * FROM ".OSDB_BANS." WHERE name=:player LIMIT 1");
			$sth->bindValue(':player', $BanAppeal, PDO::PARAM_STR); 
			$result = $sth->execute();
	  
	  
	      if ( $sth->rowCount()>=1 ) {
	      $row = $sth->fetch(PDO::FETCH_ASSOC);
		  $BanAppealName = $row["name"];
		  $BanAppealDate = $row["date"];
		  $BanAppealGamename= $row["gamename"];
		  $BanAppealAdmin= $row["admin"];
		  $BanAppealReason= $row["reason"];
		  $BanAppealServer= $row["server"];
	     }
	  }
?>
