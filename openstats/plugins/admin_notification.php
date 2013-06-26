<?php
//Plugin: Admin notification
//Author: Ivan
//Notification for administrators

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';
$PluginOptions = '1';
$ThisPlugin = basename(__FILE__, '');

if ($PluginEnabled == 1  ) {

if ( OS_is_admin() AND OS_PluginEdit( $ThisPlugin ) )
$Option = '
<div><a class="menuButtons" href="'.OS_HOME.'?reset_notification" target="_blank">Reset notification</a></div>';
  
  //Check cookies - info about admin last login
  if ( OS_is_admin() ) AddEvent("os_start",   "OS_UserLoginCheck");
  
  if ( OS_is_admin() AND !$_GET ) {
  
  if ( !isset($_SESSION["notification"]) ) AddEvent("os_content", "OS_GetNotifications");
  
  //AddEvent("os_content", "OS_Debug");
  
  }
  
  function OS_UserLoginCheck() {
    
	if ( !isset($_COOKIE["os_last_login"] ) ) {
	@setcookie("os_last_login", time()-(3600*24), time()+3600*24*7, "/");
	header("location: ".OS_HOME.""); die;
	}
	
	//Reset notification
	if ( isset($_GET["reset_notification"]) ) {
	@setcookie("os_last_login", " ", time()-3600*24*7, "/");
	if ( isset($_SESSION["notification"]) ) unset( $_SESSION["notification"] );
	header("location: ".OS_HOME.""); die;
	}
	
	if ( isset($_GET["notification_checked"]) ) {
	@setcookie("os_last_login", time(), time()+3600*24*7, "/");
	$_SESSION["notification"] = "closed";
	header('location:'.OS_HOME.''); die;
	}
	
  }
  
  function OS_GetNotifications() {
    
	global $db;
	if ( !isset($_COOKIE["os_last_login"])  ) {
	@setcookie("os_last_login", time(), time()+3600*24*7, "/");
	header('location:'.OS_HOME.''); die;
	}
	
	$last_visit = (int)$_COOKIE["os_last_login"] ;
	$last_visit_date = date("Y-m-d H:i:s", $last_visit);
?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed padLeft padTop padBottom entry clearfix">
	 <h2 class="title">Notifications:</h2>
	 <style>
	    .notification_links {  }
		.notification_links:hover { color: #0461A0; }
	 </style>
	<?php
	$TotalNotification = 0;
	//APPEALS
	$sth = $db->prepare("SELECT * FROM ".OSDB_APPEALS." WHERE `status`=0 ORDER BY `added` DESC LIMIT 50");
	$result = $sth->execute();
	if ( $sth->rowCount()>=1 ) {
	$TotalNotification++;
	?>
	<div>
	  <a class="notification_links" href="javascript:;" onclick="showhide('appeals_')">(<?=$sth->rowCount()?>) <b>Ban Appeals</b></a>
	</div>
	<div id="appeals_" style="display:none;">
	<table>
	<?php
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	?>
	<tr><td> &raquo; <a class="notification_links" href="<?=OS_HOME?>adm/?ban_appeals&amp;edit=<?=$row["player_name"]?>&amp;t=<?=$row["added"]?>" target="_blank"><?=$row["player_name"]?></a></td></tr>
	<?php
	}
	?></table></div><?php
	}
	
	
	//REPORTS
	$sth = $db->prepare("SELECT * FROM ".OSDB_REPORTS." 
	WHERE status=0 ORDER BY added DESC LIMIT 50");
	
	$result = $sth->execute();
	
	if ( $sth->rowCount()>=1 ) {
	?>
	<div>
	  <a class="notification_links" href="javascript:;" onclick="showhide('report_')">(<?=$sth->rowCount()?>) <b>Ban Reports</b></a>
	</div>
	<div id="report_" style="display:none;">
	<table>
	<?php
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	?>
	<tr><td> &raquo; <a class="notification_links" href="<?=OS_HOME?>adm/?ban_reports&amp;edit=<?=$row["player_name"]?>&amp;t=<?=$row["added"]?>" target="_blank"><?=$row["player_name"]?></a></td></tr>
	<?php
	}
	?></table></div><?php
	}
	
	//COMMENTS
	$sth = $db->prepare("SELECT *, n.news_title FROM ".OSDB_COMMENTS." as c
	LEFT JOIN ".OSDB_NEWS." as n ON n.news_id = c.post_id
	WHERE c.`date`>=:last_visit ORDER BY c.id DESC LIMIT 50");
	
	$sth->bindValue(':last_visit', (int) $last_visit, PDO::PARAM_INT); 
	$result = $sth->execute();
	 
	if ( $sth->rowCount()>=1 ) {
	$TotalNotification++;
	?>
	<div>
	  <a class="notification_links" href="javascript:;" onclick="showhide('comments_')">(<?=$sth->rowCount()?>) <b>Comments</b></a>
	</div>
	<div id="comments_" style="display:none;">
	<table>
	<?php
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	?>
	<tr><td> &raquo; <a class="notification_links" href="<?=OS_HOME?>?post_id=<?=$row["post_id"]?>#comments" target="_blank"><?=$row["news_title"]?></a></td></tr>
	<?php
	}
	?></table></div><?php
	}
	
	//NEWS
	$result = $db->prepare("SELECT * FROM ".OSDB_NEWS." 
	WHERE news_date>=:last_visit ORDER BY news_date DESC LIMIT 50");
	
	$sth->bindValue(':last_visit', (int) $last_visit, PDO::PARAM_INT); 
	$result = $sth->execute();
	
	if ( $sth->rowCount()>=1 ) {
	$TotalNotification++;
	?>
	<div><a class="notification_links" href="javascript:;" onclick="showhide('news_')">(<?=$sth->rowCount()?>) <b>News</b> </a></div>
	<div id="news_" style="display:none;">
	<table>
	<?php
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	?>
	<tr><td><?=$row["news_title"]?></td></tr>
	<?php
	}
	?></table></div><?php
	}
	
	//MEMBERS
		
	$sth = $db->prepare("SELECT * FROM ".OSDB_USERS." 
	WHERE user_joined>=:last_visit ORDER BY user_joined DESC LIMIT 50");
	$sth->bindValue(':last_visit', (int) $last_visit, PDO::PARAM_INT); 
	$result = $sth->execute();
	
	if ( $sth->rowCount()>=1 ) {
	$TotalNotification++;
	?>
	<div>
	<a class="notification_links" href="javascript:;" onclick="showhide('users_')">(<?=$sth->rowCount()?>) <b>New members</b></a></div>
	<div id="users_" style="display:none;">
	<table>
	<?php
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	?>
	<tr><td><?=$row["user_name"]?></td></tr>
	<?php
	}
	?></table></div><?php
    }
	
	//BANS
		
	$sth = $db->prepare("SELECT * FROM ".OSDB_BANS." 
	WHERE `date`>=:last_visit_date ORDER BY `date` DESC LIMIT 50");
	$sth->bindValue(':last_visit_date', $last_visit_date, PDO::PARAM_STR); 
	if ( $sth->rowCount()>=1 ) {
	$TotalNotification++;
	?>
	<div><a class="notification_links" href="javascript:;" onclick="showhide('bans_')">(<?=$sth->rowCount()?>) <b>New bans</b> </a></div>
	<div id="bans_" style="display:none;">
	<table>
	<?php
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	?>
	<tr><td><?=$row["name"]?></td></tr>
	<?php
	}
	?></table></div><?php
	}
	
	//GAMES
		
	$result = $db->query("SELECT * FROM ".OSDB_GAMES." 
	WHERE `datetime`>='".$last_visit_date."' ORDER BY `datetime` DESC LIMIT 50");
	//$sth->bindValue('last_visit_date', $last_visit_date, PDO::PARAM_STR); 
	if ( $sth->rowCount()>=1) {
	$TotalNotification++;
	?>
	<div>
	<a class="notification_links" href="javascript:;" onclick="showhide('games_')">(<?=$sth->rowCount()?>) <b>New games</b> </a></div>
	<div id="games_" style="display:none;">
	<table>
	<?php
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	?>
	<tr><td><?=$row["gamename"]?></td></tr>
	<?php
	}
	?></table></div><?php
	}
	?>
	<?php if ($TotalNotification<=0) { ?>
	<div>No new notification</div>
	<?php } ?>
	<div class="padTop padBottom">
	   <a class="menuButtons" href="<?=OS_HOME?>?notification_checked">Close notifications</a>
	</div>
	
     </div>
    </div>
   </div>
  </div>
</div>	
	<?php
  }
  
}
?>