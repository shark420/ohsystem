<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

    if ( isset($_GET["success"]) ) $errors = $lang["report_successfull"] ;
   
    if ( isset($_GET["user"]) ) {
	   $ReportedPlayer = safeEscape(trim($_GET["user"]));
	} else $ReportedPlayer = "";
      
	  if (isset($_POST["submit_report"]) ) {
	     $player = safeEscape(trim($_POST["report_player"]));
		 $subject = safeEscape(trim($_POST["subject"]));
		 $reason = safeEscape(trim($_POST["message"]));
		 $reason = my_nl2br( trim($_POST["message"]) );
		 $reason = nl2br($reason);
		 $reason = EscapeStr( ($reason) );
		 $game_url   = EscapeStr(trim($_POST["game_url"]));
		 $replay_url = EscapeStr(trim($_POST["replay_url"]));
		 $errors = "";
		 
		 if ( strlen($player)<=2 )  $errors.="<div>".$lang["error_report_player"]."</div>";
		 if ( strlen($subject)<=2 ) $errors.="<div>".$lang["error_report_subject"]."</div>";
		 if ( strlen($reason)<=2 )  $errors.="<div>".$lang["error_report_reason"]."</div>";
		 if ( !is_logged() )  $errors ="<div>".$lang["error_report_login"]."</div>";
		 
		 if ( isset($_SESSION["last_report"]) AND $_SESSION["last_report"] + $BanReportTime > time() ) {
		 $TimeLeft = time() - $_SESSION["last_report"];
		 $errors = "<div>".$lang["error_report_time"]." ".($BanReportTime-$TimeLeft)." ".$lang["error_sec"]." </div>";
		 }
		 
		 if ( empty($errors) ) {
		     $sth = $db->prepare("SELECT * FROM ".OSDB_GP." WHERE name=:player LIMIT 1");
			 $sth->bindValue(':player', $player, PDO::PARAM_STR); 
			 $result = $sth->execute();

			if ( $sth->rowCount()<=0 ) {
			$errors.="<div>".$lang["error_no_player"]."</div>";
			
			$sth = $db->prepare("SELECT * FROM ".OSDB_BANS." WHERE name=:player LIMIT 1");
			$sth->bindValue(':player', $player, PDO::PARAM_STR); 
			if ( $sth->rowCount()>=1 ) {
			$errors.="<div>".$lang["error_already_banned"]."</div>";
			}
			
			} else {
			
			$sth = $db->prepare("SELECT * FROM ".OSDB_STATS." WHERE player=:player LIMIT 1");
			$sth->bindValue(':player', $player, PDO::PARAM_STR); 
			
			 if ( $sth->rowCount()>=1 ) {
			 $row = $sth->fetch(PDO::FETCH_ASSOC);
			 $PID = $row["id"];
			 } 
			 else $PID = 0; //Player not ranked yet

			 $db->insert(OSDB_REPORTS, array(
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
	         require_once(OS_PLUGINS_DIR.'index.php');
	         os_init();
			 header('location: '.OS_HOME.'?ban_report&success');
			 
			}

		 }
	  }
?>