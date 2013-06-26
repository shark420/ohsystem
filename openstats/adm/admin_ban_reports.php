<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
$errors = "";

?>
<div align="center">
<h2>Ban Reports</h2>

<?php

	 if ( file_exists("../inc/geoip/geoip.inc") ) {
	 include("../inc/geoip/geoip.inc");
	 $GeoIPDatabase = geoip_open("../inc/geoip/GeoIP.dat", GEOIP_STANDARD);
	 $GeoIP = 1;
	 }

  if ( isset($_GET["del"]) AND isset($_GET["t"]) AND is_numeric($_GET["t"]) ) {
     $del = safeEscape( $_GET["del"]);
	 $t = safeEscape( $_GET["t"]);
	 $sth = $db->prepare("DELETE FROM ".OSDB_REPORTS." 
	 WHERE LOWER(player_name) = LOWER('".$del."') AND added = '".$t."' LIMIT 1");
	 $result = $sth->execute();
  }
  
  if ( isset($_GET["edit"]) AND isset($_GET["t"]) AND is_numeric($_GET["t"]) ) {
	  
     $id = safeEscape( $_GET["edit"]);
	 $t = safeEscape( $_GET["t"]);
	 
    if ( isset($_GET["close"]) ) {
	$sth  = $db->prepare("UPDATE ".OSDB_REPORTS." SET status = 1 
	WHERE LOWER(player_name) = LOWER('".$id."') AND added = '".$t."' LIMIT 1");  
	$result = $sth->execute();
	}
	
	if ( isset($_GET["open"]) ) {
	$sth  = $db->prepare("UPDATE ".OSDB_REPORTS." SET status = 0 
	WHERE LOWER(player_name) = LOWER('".$id."') AND added = '".$t."' LIMIT 1");  
	$result = $sth->execute();
	}
	
	if ( isset($_GET["ban_user"]) ) {
	$date = date("Y-m-d H:i:s", time() );
	$sth  = $db->prepare("INSERT INTO ".OSDB_BANS." (name, date, admin, reason) VALUES('".$id."', '".$date."', '".$_SESSION["username"]."', 'Reported by other player') ");  
	$result = $sth->execute();
	$sth  = $db->prepare("UPDATE ".OSDB_REPORTS." SET status = 2 
	WHERE LOWER(player_name) = LOWER('".$id."') AND added = '".$t."' LIMIT 1");  
	$result = $sth->execute();
	}
	 
	 $sth = $db->prepare("SELECT b.*, u.user_name 
	 FROM ".OSDB_REPORTS." as b 
	 LEFT JOIN ".OSDB_USERS." as u ON u.user_id = b.user_id
	 WHERE LOWER(player_name) = LOWER('".$id."') AND added = '".$t."' LIMIT 1");
	 $result = $sth->execute();
	 
	 if ( $sth->rowCount()>=1 ) {
	 $row = $sth->fetch(PDO::FETCH_ASSOC);
	 
	 $sth = $db->prepare("SELECT * FROM ".OSDB_BANS." WHERE LOWER(name) = LOWER('".$row["player_name"]."') LIMIT 1");
	 $result = $sth->execute();
	 
	 
	 if ( $sth->rowCount()>=1 ) $Banned = 1; else $Banned = 0;
	 
	if ($GeoIP == 1 ) {
	$Letter   = geoip_country_code_by_addr($GeoIPDatabase, $row["user_ip"]);
	$Country  = geoip_country_name_by_addr($GeoIPDatabase, $row["user_ip"]);
	}
	 ?>
	 <table class="Table800px">
	   <tr>
	    <td width="150" class="padLeft"><b>Reported player:</td>
		<td><a target="_blank" href="<?=$website?>?u=<?=strtolower($row["player_name"])?>"><span class="banned"><?=$row["player_name"]?></span></a></td>
	   </tr>
	   <tr>
	    <td width="150" class="padLeft"><b>Reported by:</td>
		<td><span><?=$row["user_name"]?></span> <span style="padding-left: 26px;"> <?php if ($GeoIP == 1 AND !empty($Letter) ) { ?><img src="<?=$website?>img/flags/<?=$Letter?>.gif" class="imgvalign" title="<?=$Country?>" alt="" /><?php } ?> <?=$row["user_ip"]?> ( <?=$Country?> )</span></td>
	   </tr>
	   <tr>
	    <td width="150" class="padLeft"><b>Reason:</td>
		<td><textarea disabled style="width: 500px; height: 110px;"><?=stripslashes(str_replace("<br />", " ", $row["reason"]))?></textarea></td>
	   </tr>
	   <tr>
	    <td width="150" class="padLeft"><b>Game URL:</td>
		<td><span><?=$row["game_url"]?></span></td>
	   </tr>
	   <tr>
	    <td width="150" class="padLeft"><b>Replay URL:</td>
		<td><span><?=$row["replay_url"]?></span></td>
	   </tr>
	   <tr>
	    <td width="150" class="padLeft"><b>Date of Report:</td>
		<td><span><?=date($DateFormat,  $row["added"])?></span></td>
	   </tr>
	   <tr>
	    <td width="150" class="padLeft"><b>Action:</td>
		<td>
		<div class="padTop"></div>
<?php if ($row["status"] == 1) { ?>
      <div><i>This REPORT is closed.</i></div>
<?php } ?>
<?php if ($Banned == 1) { ?>
      <div><i>This user is already BANNED.</i></div>
<?php } ?>

<?php if ($row["status"] == 0) { ?>
		  <a class="menuButtons" onclick="if (confirm('Ban user <?=$row["player_name"]?>?') ) { location.href='<?=$website?>adm/?ban_reports&amp;edit=<?=strtolower($row["player_name"])?>&amp;t=<?=$row["added"]?>&amp;ban_user' }" href="javascript:;">Ban User "<?=$row["player_name"]?>"</a>
		  
		  <a class="menuButtons" onclick="if (confirm('Ban user <?=$row["player_name"]?>?') ) { location.href='<?=$website?>adm/?bans&amp;add=<?=strtolower($row["player_name"])?>' }" href="javascript:;">Manually Ban User</a>
<?php } ?>
<?php if ($row["status"] == 0) { ?>
		  <a class="menuButtons" href="<?=$website?>adm/?ban_reports&amp;edit=<?=strtolower($row["player_name"])?>&amp;t=<?=$row["added"]?>&amp;close">Close Report</a>
<?php } ?>
<?php if ($row["status"] == 1) { ?>
		  <a class="menuButtons" href="<?=$website?>adm/?ban_reports&amp;edit=<?=strtolower($row["player_name"])?>&amp;t=<?=$row["added"]?>&amp;open">Open Report</a>
<?php } ?>
		<div class="padTop"></div>
		</td>
	   </tr>
	 </table>
	 <div class="padTop"></div>
	 <?php
	 }
  }

  $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_REPORTS." LIMIT 1");
  $result = $sth->execute();
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  $result_per_page = 30;
  
  $draw_pagination = 1;
  $SHOW_TOTALS = 1;
  include('pagination.php');
  
  $ord = "b.added DESC ";
  
  if ( isset($_GET["sort"]) AND $_GET["sort"] == "unsolved" ) $ord = "b.status ASC,  b.added DESC ";
  if ( isset($_GET["sort"]) AND $_GET["sort"] == "solved" )   $ord = "b.status DESC, b.added DESC ";
  
  $sth = $db->prepare("SELECT b.*, u.user_name 
  FROM ".OSDB_REPORTS." as b 
  LEFT JOIN ".OSDB_USERS." as u ON u.user_id = b.user_id
  ORDER BY $ord 
  LIMIT $offset, $rowsperpage");
  $result = $sth->execute();
  ?>
  Sort by: <a href="<?=$website?>adm/?ban_reports&amp;sort=solved">Solved</a> | 
  <a href="<?=$website?>adm/?ban_reports&amp;sort=unsolved">Unsolved</a> | 
  <a href="<?=$website?>adm/?ban_reports">Report Time</a>
<table class="Table800px">
    <tr>
	  <th width="190" class="padLeft">Reported player</th>
	  <th width="140" class="padLeft">Reported by</th>
	  <th width="240">Reason</th>
	  <th width="150">Added</th>
	</tr>
<?php
   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { ?>
   <tr class="row font13" style="height: 55px;">
     <td>
	 <?php if ($row["status"] == 0) { ?>
	 <img src="<?=$website?>adm/uncheck.png" alt="*" class="imgvalign" width="16" height="16" />
	 <?php } else { ?>
	 <img src="<?=$website?>adm/check.png" alt="*" class="imgvalign" width="16" height="16" />
	 <?php } ?>
	   <a target="_blank" href="<?=$website?>?u=<?=strtolower($row["player_name"])?>"><span class="sentinel"><?=$row["player_name"]?></span></a>
	   <div style="float: right" class="font12">
	   <a href="<?=$website?>adm/?ban_reports&edit=<?=strtolower($row["player_name"])?>&t=<?=$row["added"]?>"><img src="<?=$website?>adm/edit.png" alt="*" width="16" height="16" /></a> 
	   <a href="javascript:;" onclick="if (confirm('Delete this report?') ) { location.href='<?=$website?>adm/?ban_reports&del=<?=strtolower($row["player_name"])?>&t=<?=$row["added"]?>' }" ><img src="<?=$website?>adm/del.png" alt="*" width="16" height="16" /></a>
	   </div>
	 </td>
	 <td><a class="padLeft" href="<?=$website?>adm/?users&amp;edit=<?=($row["user_id"])?>"><?=$row["user_name"]?></a> </td>
	 <td><?=limit_words($row["reason"], 12)?></td>
	 <td><?=date($DateFormat, $row["added"])?></td>
   </tr>
   <?php } 
   ?>
   </table>
<?php
include('pagination.php');

if ( isset($GeoIP) AND $GeoIP == 1) geoip_close($GeoIPDatabase);
?>

</div>