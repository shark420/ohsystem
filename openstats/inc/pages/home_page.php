<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

     $HOME_PAGE = 1;
	 
	 if ( isset($_GET["post_id"]) AND is_numeric($_GET["post_id"]) ) {
	 
	 $id = safeEscape( (int) $_GET["post_id"]);
	 $SinglePost = 1;
	 $sql = " AND news_id =:news_id";
	//GET COMMENTS
	 $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_COMMENTS." WHERE post_id=:post_id");
	 
	 $sth->bindValue(':post_id', (int) $id, PDO::PARAM_INT); 
	 $result = $sth->execute();
	  
	 $r = $sth->fetch(PDO::FETCH_NUM);
	 $numrows = $r[0];
	 $result_per_page = $CommentsPerPage;
	 $draw_pagination = 0;
	 $total_comments  = $numrows;
	 include('inc/pagination.php');
	 $draw_pagination = 1;
	 
	 $CommentOrder = "id DESC";
	 if ( isset($SortComments) AND $SortComments == 2)  $CommentOrder = "date DESC";
	 if ( isset($SortComments) AND $SortComments == 3)  $CommentOrder = "date ASC";
	 
	  $sth = $db->prepare("SELECT c.*, u.user_name, u.user_fbid, u.user_avatar, u.user_website, u.user_gender, u.user_level, u.phpbb_id, u.smf_id
	  FROM  ".OSDB_COMMENTS." as c
	  LEFT JOIN ".OSDB_USERS." as u ON u.user_id = c.user_id
	  WHERE c.post_id=:post_id ORDER BY c.$CommentOrder LIMIT $offset, $rowsperpage");
	  
	  $sth->bindValue(':post_id', (int) $id, PDO::PARAM_INT); 
	  $result = $sth->execute();
	  $c=0;
     $CommentsData = array();
	 
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	$CommentsData[$c]["id"]        = (int)($row["id"]);
	$CommentsData[$c]["total_comments"] = $total_comments;
	$CommentsData[$c]["username"]  = ($row["user_name"]);
	$CommentsData[$c]["username_clean"]  = ($row["user_name"]);
	$CommentsData[$c]["user_id"]  = ($row["user_id"]);
	$CommentsData[$c]["post_id"]  = ($row["post_id"]);
	//$CommentsData[$c]["text"]  = convEnt($row["text"]);
	if (isset($AutoLinkComments) AND $AutoLinkComments == 2)
	$CommentsData[$c]["text"]  = RemoveLinks( $row["text"], $AutoLinkTextReplace  );
	else
	if (isset($AutoLinkComments) AND $AutoLinkComments == 1)
	$CommentsData[$c]["text"]  = AutoLinkShort( $row["text"], 'target="_blank" class="u_links"' );
	else 
	if (isset($AutoLinkComments) AND $AutoLinkComments == 1 AND isset($AutoLinkFull) AND $AutoLinkFull == 1)
	$CommentsData[$c]["text"]  = AutoLinkFull( $row["text"], 'target="_blank" class="u_links"' );
	
	//$CommentsData[$c]["text"]  = html_entity_decode( $CommentsData[$c]["text"] );
	$CommentsData[$c]["text"]  = convEnt($CommentsData[$c]["text"]);
	//Quote user - bold username
	if ( strstr($CommentsData[$c]["text"] , "@" ) AND strstr($CommentsData[$c]["text"] , ", ") ) {
	$CommentsData[$c]["text"]  = preg_replace('#\@(.*?)\, #i', '<b>@\\1</b>, ', $CommentsData[$c]["text"] );
	}
	
	$CommentsData[$c]["date"]  = date($DateFormat, $row["date"]);
	$CommentsData[$c]["user_ip"]  = ($row["user_ip"]);
	$CommentsData[$c]["fb"]  = ($row["user_fbid"]);
	$CommentsData[$c]["avatar"]  = ($row["user_avatar"]);
	if (empty($row["user_avatar"]) ) $CommentsData[$c]["avatar"] = $website."img/avatar_64.png";
	$CommentsData[$c]["website"]  = ($row["user_website"]);
	$CommentsData[$c]["gender"]  = ($row["user_gender"]);
	$CommentsData[$c]["post_id"]  = ($row["post_id"]);
	$CommentsData[$c]["user_level"]  = ($row["user_level"]);
	$CommentsData[$c]["phpbb_id"]  = ($row["phpbb_id"]);
	$CommentsData[$c]["smf_id"]  = ($row["smf_id"]);
	$c++;
	}	
	//$db->free($result);	
		
		
	 } else $sql = "";
	 
	 $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_NEWS." WHERE news_id>=1 $sql LIMIT 1");
	 
     if ( isset($_GET["post_id"]) AND is_numeric($_GET["post_id"]) ) {
	 $sth->bindValue(':news_id', (int) $id, PDO::PARAM_INT); 
	 }
	 
	 $result = $sth->execute();
	 $r = $sth->fetch(PDO::FETCH_NUM);
	 $numrows = $r[0];
	 $result_per_page = $NewsPerPage;
	 $draw_pagination = 0;
	 include('inc/pagination.php');
	 $draw_pagination = 1;
	 
	 $sth = $db->prepare("SELECT * FROM  ".OSDB_NEWS." WHERE news_id>=1 AND status=1 $sql ORDER BY news_id DESC 
	 LIMIT $offset, $rowsperpage");
	 
     if ( isset($_GET["post_id"]) AND is_numeric($_GET["post_id"]) ) {
	 $sth->bindValue(':news_id', (int) $id, PDO::PARAM_INT); 
	 }
	 $result = $sth->execute();
	 
	 $c=0;
     $NewsData = array();
	 
	 if ( $numrows<=0 AND isset($_GET["post_id"]) ) { header('location: '.OS_HOME.'?404'); die; } 
	 
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	
	if ( isset($_GET["post_id"]) AND is_numeric($_GET["post_id"]) ) {
	$HomeTitle = ($row["news_title"]);
	$HomeDesc = limit_words( convEnt( $row["news_content"]), 15);
	$HomeDesc =removeDoubleSpaces($HomeDesc);
	$HomeKeywords = AutoKeywords($row["news_title"]).','.$HomeKeywords;
	$CommentsAllowed = $row["allow_comments"];
	
	if ( !isset($updateViews) ) 
	$updateViews = $db->query("UPDATE ".OSDB_NEWS." SET views = views+1 WHERE news_id = '".$row["news_id"]."' LIMIT 1");
	
	}
	
	$NewsData[$c]["id"]        = (int)($row["news_id"]);
	$id = (int)($row["news_id"]);
	$NewsData[$c]["title"]  = ($row["news_title"]);
	if ( !isset($_GET["post_id"]) AND isset($NewsWordLimit) AND $NewsWordLimit>=2 ) {
	$NewsData[$c]["text"]  = limit_words(convEnt($row["news_content"]), $NewsWordLimit);
	$NewsData[$c]["read_more"] = '<a class="read_more" href="'.$website.'?post_id='.$id.'">'.$lang["read_more"] .'</a>';
	}
	else {
	$NewsData[$c]["text"]  = convEnt($row["news_content"]);
	$NewsData[$c]["read_more"] = '';
	}
	
	$NewsData[$c]["full_text"]  = convEnt($row["news_content"]);
	//$NewsData[$c]["text"]  = str_replace("\n","<br />", $NewsData[$c]["text"]);
	$NewsData[$c]["date"]  = date( $DateFormat, ($row["news_date"]) );
	$NewsData[$c]["date_int"]  = ($row["news_date"]);
	$NewsData[$c]["comments"]  = ($row["comments"]);
	$NewsData[$c]["allow_comments"]  = ($row["allow_comments"]);
	$c++;
	}	
	//$db->free($result);	
	
	//GAMELIST PATCH
	if ( isset($GameListPatch ) AND $GameListPatch  == 1 AND !$_GET) {
	  $sth = $db->prepare( "SELECT * FROM ".OSDB_GAMELIST." "  );
	  $result = $sth->execute();
	  $c=0;
	  $LiveGamesData = array();
	  $CurrentPlayers = array();
	  $LivePlayers = array();
	   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	  
	  $LiveGamesData[$c]["id"]  = ($row["id"]);
	  $LiveGamesData[$c]["botid"]  = ($row["botid"]);
	  $LiveGamesData[$c]["gamename"]  = ($row["gamename"]);
	  $LiveGamesData[$c]["ownername"]  = ($row["ownername"]);
	  $LiveGamesData[$c]["creatorname"]  = ($row["creatorname"]);
	  $LiveGamesData[$c]["map"]  = ($row["map"]);
	  $LiveGamesData[$c]["slotstaken"]  = ($row["slotstaken"]);
	  $LiveGamesData[$c]["slotstotal"]  = ($row["slotstotal"]);
	  $LiveGamesData[$c]["usernames"]  = ($row["usernames"]);
	  $LiveGamesData[$c]["totalgames"]  = ($row["totalgames"]);
	  $LiveGamesData[$c]["totalplayers"]  = ($row["totalplayers"]);
	  
	  $LiveGamesData[$c]["players"] = explode("\t", $row["usernames"]);
	  
	  /*
	  for($i = 0; $i < count( $CurrentPlayers[$c] ) - 2; $i+=3) {
	    $username = $CurrentPlayers[$c][$i];
		$realm = $CurrentPlayers[$c][$i + 1];
		$ping = $CurrentPlayers[$c][$i + 2];
		$LivePlayers[$i][$c]["username"] = $CurrentPlayers[$c][$i];
		$LivePlayers[$i][$c]["realm"] = $CurrentPlayers[$c][$i + 1];
		$LivePlayers[$i][$c]["ping"] = $CurrentPlayers[$c][$i + 2];

	  }
	  */
	  $c++;
	  }
	  //$db->free($result);	
	}
	
    //RECENT GAMES
	if ( $RecentGames == 1 AND (!isset($_GET["post_id"]) AND !$_GET )) {
    $sth = $db->prepare( getAllGames($MinDuration, 0, $TotalRecentGames )  );
	$result = $sth->execute();
	$c=0;
	
    $RecentGamesData = array();
	while ( $row = $sth->fetch(PDO::FETCH_ASSOC) ) {
	$RecentGamesData[$c]["id"]        = (int)($row["id"]);
	//$RecentGamesData[$c]["map"]  = convEnt2(substr($row["map"], strripos($row["map"], '\\')+1));
	//$RecentGamesData[$c]["map"] = reset( explode(".w", $RecentGamesData[$c]["map"] ) );
	//$RecentGamesData[$c]["map"] = substr($RecentGamesData[$c]["map"],0,20);
	
	$GetMap = convEnt2(substr($row["map"], strripos($row["map"], '\\')+1));
	$Map = explode(".w", $GetMap );
	$RecentGamesData[$c]["map"]  = $Map[0];
	
	$RecentGamesData[$c]["datetime"]  = ($row["datetime"]);
	$RecentGamesData[$c]["gamename"]  = ($row["gamename"]);
	$RecentGamesData[$c]["ownername"]  = ($row["ownername"]);
	$RecentGamesData[$c]["duration"]  = ($row["duration"]);
	$RecentGamesData[$c]["creatorname"]  = ($row["creatorname"]);
	$RecentGamesData[$c]["winner"]  = ($row["winner"]);
	$RecentGamesData[$c]["type"]  = ($row["type"]);
	
	//REPLAY
	 $duration = secondsToTime($row["duration"]);
     $replayDate =  strtotime($row["datetime"]);  //3*3600 = +3 HOURS,   +0 minutes.
     $replayDate = date("Y-m-d H:i",$replayDate);
     $gametimenew = substr(str_ireplace(":","-",date("Y-m-d H:i",strtotime($replayDate))),0,16);
	 $gid =  (int)($row["id"]);
	 $gamename = $RecentGamesData[$c]["gamename"];
	 include('inc/get_replay.php');
	 
	 if ( file_exists($replayloc) ) $RecentGamesData[$c]["replay"] = $replayloc; 
	 else $RecentGamesData[$c]["replay"] = "";
	 //END REPLAY
	$c++;
	}	
	//$db->free($result);	
	}
?>