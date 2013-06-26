<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

if ( !$_GET OR (isset($_GET["delete_file"]) OR isset($_GET["delete_cache"]) OR isset($_GET["delete_replay_cache"]) OR isset($_GET["view_cache"]) OR isset($_GET["optimize_tables"]) )) {

   	$sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_GAMES." 
	WHERE map LIKE '%dota%' AND stats = 0 AND duration>='".$MinDuration."'" );
	$result = $sth->execute();
    $r = $sth->fetch(PDO::FETCH_NUM);
    $TotalGamesForUpdate = $r[0];
	
   	$sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_GAMES." 
	WHERE map LIKE '%dota%' AND stats = 1" );
	$result = $sth->execute();
    $r = $sth->fetch(PDO::FETCH_NUM);
    $TotalRankedGames = $r[0];
	
	$sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_BANS." 
	WHERE id>=1" );
	$result = $sth->execute();
    $r = $sth->fetch(PDO::FETCH_NUM);
    $TotalBans = $r[0];
	
	$sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_STATS." 
	WHERE id>=1" );
	$result = $sth->execute();
    $r = $sth->fetch(PDO::FETCH_NUM);
    $TotalRankedUsers = $r[0];
	
	$sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_ADMINS." 
	WHERE id>=1" );
	$result = $sth->execute();
    $r = $sth->fetch(PDO::FETCH_NUM);
    $TotalAdmins = $r[0];
	
	$sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_USERS." 
	WHERE user_id>=1" );
	$result = $sth->execute();
    $r = $sth->fetch(PDO::FETCH_NUM);
    $TotalUsers = $r[0];
	
	$sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_NEWS." 
	WHERE news_id>=1" );
	$result = $sth->execute();
    $r = $sth->fetch(PDO::FETCH_NUM);
    $TotalNews = $r[0];
	
   	$sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_COMMENTS." 
	WHERE id >= 1" );
	$result = $sth->execute();
    $r = $sth->fetch(PDO::FETCH_NUM);
    $TotalComments = $r[0];
	
   	$sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_REPORTS."" );
	$result = $sth->execute();
    $r = $sth->fetch(PDO::FETCH_NUM);
    $TotalBanReports = $r[0];
	
   	$sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_APPEALS."" );
	$result = $sth->execute();
    $r = $sth->fetch(PDO::FETCH_NUM);
    $TotalBanAppeals = $r[0];
	
	$sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_GUIDES."" );
	$result = $sth->execute();
    $r = $sth->fetch(PDO::FETCH_NUM);
    $TotalGuides = $r[0];
	
	$c = 0;
	$CachedFiles = array();
	$cacheDir = "../inc/cache/pdheroes";
if ( $PlayDotaHeroes == 1 AND file_exists($cacheDir) ) {
   if ($handle = opendir($cacheDir)) {
       while (false !== ($file = readdir($handle))) {
	    if ($file !="." AND  $file !="index.html" AND $file !=".."  ) {
		$CachedFiles[$c]["path"] = $cacheDir; 
		$CachedFiles[$c]["file"] = $file; 
		$c++;
		}
	   
	   }
   }
}
$TotalFiles = $c;
	?>
	<a name="files"></a>
	<div align="center" style="margin-top: 6px; margin-bottom: 100px;">
	<?php if (isset($OptimizedTables ) ) { ?>
	<h2>All tables successfully optimized <a href="<?=OS_HOME?>adm/">[OK]</a></h2>
	<?php } ?>
	
	<?php
	if ( isset($_SESSION["intro_message"]) ) echo $_SESSION["intro_message"];
	?>
	<table>
	  <tr>
	    <th class="padLeft" width="200">Dashboard</th>
		<th></th>
	  </tr>
	  <tr class="row">
	    <td width="200" class="padLeft"><b>Unranked games:</b></td>
	    <td>
		<?=number_format($TotalGamesForUpdate,0)?>
		<?php if ($TotalGamesForUpdate>=1) { ?><a class="menuButtons" href="<?=OS_HOME?>adm/update_stats.php">Update</a><?php } ?>
		</td>
	  </tr>
	  
	  <tr class="row">
	    <td width="200" class="padLeft"><b>Ranked games:</b></td>
	    <td>
		<a href="<?=OS_HOME?>adm/?games"><?=number_format($TotalRankedGames,0)?></a>
		</td>
	  </tr>
	  
	  <tr class="row">
	    <td width="200" class="padLeft"><b>Ranked Players:</b></td>
	    <td>
		<a href="<?=OS_HOME?>adm/?players"><?=number_format($TotalRankedUsers,0)?></a>
		</td>
	  </tr>
	  
	  <tr class="row">
	    <td width="200" class="padLeft"><b>Total Bans:</b></td>
	    <td>
		<a href="<?=OS_HOME?>adm/?bans"><?=number_format($TotalBans,0)?></a>
		</td>
	  </tr>
	  
	  <tr class="row">
	    <td width="200" class="padLeft"><b>Total Admins:</b></td>
	    <td>
		<a href="<?=OS_HOME?>adm/?admins"><?=number_format($TotalAdmins,0)?></a>
		</td>
	  </tr>
	  
	  <tr class="row">
	    <td width="200" class="padLeft"><b>Total Members:</b></td>
	    <td>
		<a href="<?=OS_HOME?>adm/?users"><?=number_format($TotalUsers,0)?></a>
		</td>
	  </tr>
	  
	  <tr class="row">
	    <td width="200" class="padLeft"><b>Total Posts:</b></td>
	    <td>
		<a href="<?=OS_HOME?>adm/?posts"><?=number_format($TotalNews,0)?></a>
		</td>
	  </tr>
	  <tr class="row">
	    <td width="200" class="padLeft"><b>Total Comments:</b></td>
	    <td>
		<a href="<?=OS_HOME?>adm/?comments"><?=number_format($TotalComments,0)?></a>
		</td>
	  </tr>
	  
	  <tr class="row">
	    <td width="200" class="padLeft"><b>Ban Reports:</b></td>
	    <td>
		<a href="<?=OS_HOME?>adm/?ban_reports"><?=number_format($TotalBanReports,0)?></a>
		</td>
	  </tr>
	  
	  <tr class="row">
	    <td width="200" class="padLeft"><b>Ban Appeals:</b></td>
	    <td>
		<a href="<?=OS_HOME?>adm/?ban_appeals"><?=number_format($TotalBanAppeals,0)?></a>
		</td>
	  </tr>
	  
	  <tr class="row">
	    <td width="200" class="padLeft"><b>Guides:</b></td>
	    <td>
		<a href="<?=OS_HOME?>adm/?guides"><?=number_format($TotalGuides, 0)?></a>
		</td>
	  </tr>
	  
	</table>
	<div class="padTop"></div>
	<table>
	<tr>
	<td width="480" class="padLeft"><a class="menuButtons" href="javascript:;" onclick="if (confirm('Are you sure you want to reset all statistics?') ) {  location.href='<?=OS_HOME?>adm/update_stats.php?reset' }" >Reset Statistics</a>
	  
	  <a class="menuButtons" href="javascript:;" onclick="if(confirm('Enable All Comments?') ) { location.href='<?=OS_HOME?>adm/?posts&amp;com=1' }">Enable All Comments</a>
	  
	  <a class="menuButtons" href="javascript:;" onclick="if(confirm('Disable All Comments?') ) { location.href='<?=OS_HOME?>adm/?posts&amp;com=0' }">Disable All Comments</a>
	
	</td>
	</tr>
	</table>
	
	<div class="padTop"></div>
	<table>
	<tr>
	<td width="170" class="padLeft"><b>Cached files:</b> <?=$TotalFiles?></td>
	<td>
	<a class="menuButtons" href="<?=OS_HOME?>adm/?delete_cache">Delete</a><?php if ($TotalFiles>=1) { ?> | 
	<a class="menuButtons" href="<?=OS_HOME?>adm/?view_cache#files">View files</a><?php } ?>
	</td>
	</tr>
<?php if (isset($_GET["view_cache"]) AND $TotalFiles>=1) { ?>
	  <?php 
	  foreach($CachedFiles as $File) {
	  $FilePath = str_replace("../", "", $File["path"]);
	  ?>
    <tr class="row">
	  <td width="480" class="padLeft">
	  <div>
	  <?=str_replace("../", "", $File["path"])?>/<b><?=$File["file"]?></b>
	  <a href="<?=OS_HOME?>adm/?delete_file=<?=urlencode($FilePath)?>/<?=urlencode($File["file"])?>">&times;</a>
	  </div>
	  </td>
	</tr>
	  <?php
	  }
	  ?>
<?php } ?>
	
	</table>
	
<?php 	
		

	$c = 0;
	$CachedReplayFiles = array();
	$cacheReplayDir = "../".$ReplayLocation;
	

	if ( isset($_GET["delete_replay_cache"]) AND file_exists($cacheReplayDir) ) {
	   if ($handle = opendir($cacheReplayDir)) {
	    while (false !== ($file = readdir($handle))) { 
		 if (file_exists($cacheReplayDir."/".$file) AND substr($file, -4) == "html"  AND  $file !="index.html" ) 
		 unlink($cacheReplayDir."/".$file);
		}
	   }
	}
	
if ( file_exists($cacheReplayDir) ) {
   if ($handle = opendir($cacheReplayDir)) {
       while (false !== ($file = readdir($handle))) {
	    if ($file !="." AND  $file !="index.html" AND $file !=".."  ) {
		if (substr($file, -4) == "html" ) {
		$CachedReplayFiles[$c]["path"] = $cacheReplayDir; 
		$CachedReplayFiles[$c]["file"] = $file; 
		}
		$c++;
		}
	   
	   }
   }
}

if (count($CachedReplayFiles) >=0 ) { ?>

	<table>
	<tr>
	<td width="170" class="padLeft"><b>Cached replay files:</b> <?=count($CachedReplayFiles)?></td>
	<td>
	<a class="menuButtons" href="<?=OS_HOME?>adm/?delete_replay_cache">Delete cached replay files</a>
	</td>
	</tr>
	</table>
	<?php
}
?>
	
<?php if (defined('OS_VERSION') ) { ?>	
	<div class="padTop">
	Version: <?=OS_VERSION?>
	</div>
<?php } ?>	
	</div>
<div style="margin-top: 220px;">&nbsp;</div>
	<?php
} else 
if ( isset( $_GET["gamelist"]) )     include('admin_gamelist_patch.php');   else

if ( isset( $_GET["posts"]) )     include('admin_posts.php');   else
if ( isset( $_GET["bans"]) )      include('admin_bans.php');    else
if ( isset( $_GET["admins"]) )    include('admin_admins.php');  else
if ( isset( $_GET["safelist"]) )  include('admin_safelist.php');else
if ( isset( $_GET["users"]) )     include('admin_users.php');   else
if ( isset( $_GET["games"]) )     include('admin_games.php');   else
if ( isset( $_GET["comments"]) )  include('admin_comments.php');else
if ( isset( $_GET["cfg"]) )       include('admin_cfg.php');     else
if ( isset( $_GET["notes"]) )     include('admin_notes.php');   else
if ( isset( $_GET["ban_reports"]) )  include('admin_ban_reports.php'); else
if ( isset( $_GET["ban_appeals"]) )  include('admin_ban_appeals.php'); else 
if ( isset( $_GET["about_us"]) )     include('admin_about_us.php'); else
if ( isset( $_GET["heroes"]) )       include('admin_heroes.php'); else
if ( isset( $_GET["items"]) )       include('admin_items.php'); else 
if ( isset( $_GET["guides"]) )      include('admin_guides.php'); else
if ( isset( $_GET["plugins"]) )     include('admin_plugins.php'); else 
if ( isset( $_GET["players"]) )     include('admin_players.php'); else
if ( isset( $_GET["warns"]) )       include('admin_warns.php');
?>
