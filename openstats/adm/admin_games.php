<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$duration = 0; //
$filter = "";
$orderby = "id DESC";

$ReplayLocation = "../".$ReplayLocation;
  
if ( isset($_GET["del"]) AND is_numeric($_GET["del"]) ) {
   $id = safeEscape( $_GET["del"]);
   $del1 = $db->prepare("DELETE FROM ".OSDB_DG." WHERE gameid = '".$id."' "); //dotagames
   $result = $del1->execute();
   $del2 = $db->prepare("DELETE FROM ".OSDB_DP." WHERE gameid = '".$id."' "); //dotaplayers
   $result = $del2->execute();
   $del3 = $db->prepare("DELETE FROM ".OSDB_GP." WHERE gameid = '".$id."' "); //gameplayers
   $result = $del3->execute();
   $del4 = $db->prepare("DELETE FROM ".OSDB_GAMES." WHERE id = '".$id."' ");  //games
   $result = $del4->execute();
   $deleted = 1;
   if ($deleted ) { ?>
   <div align="center">
   <h2>Game successfully deleted</h2>
   <a href="<?=OS_HOME?>adm/?games">&laquo; Back</a></h2>
   </div>
   <?php } 
   
}

if ( isset($_GET["edit"]) AND is_numeric($_GET["edit"]) ) {
   $gameid = safeEscape( (int) $_GET["edit"] );
   $sth = $db->prepare(  getSingleGame( (int)$gameid ) );
   $result = $sth->execute();
   $row = $sth->fetch(PDO::FETCH_ASSOC);
	 
	$creatorname  = ($row["creatorname"]);
	$duration  = secondsToTime($row["duration"]);
	$datetime  = date($DateFormat,strtotime($row["datetime"]));
	$date  = ($row["datetime"]);
	$gamename  = ($row["gamename"]);
	$winner = ($row["winner"]);
	
	//REPLAY
	 $duration = secondsToTime($row["duration"]);
     $replayDate =  strtotime($row["datetime"]);  //3*3600 = +3 HOURS,   +0 minutes.
     $replayDate = date("Y-m-d H:i",$replayDate);
     $gametimenew = substr(str_ireplace(":","-",date("Y-m-d H:i",strtotime($replayDate))),0,16);
	 $gid =  $gameid;
	 $gamename = $row["gamename"];
	 include('../inc/get_replay.php');
     //DELETE REPLAY
     if (isset($_GET["del_replay"]) ) {
       if ( file_exists($replayloc) ) unlink( $replayloc );
     }
	 
	 if ( file_exists($replayloc) ) $Replay = urlencode($replayloc); else $Replay = "";
	 //END REPLAY
		
	$sth = $db->prepare( getGameInfo($gameid) );
	$result = $sth->execute();
	?>
	<div align="center">
	<h2>
	<?php if ($row["stats"] == 1) { ?><img src="<?=OS_HOME?>adm/ranked.png" width="16" height="16" class="imgvalign" alt="ranked" /><?php } ?>
	<?php if ($row["stats"] == 0) { ?><img src="<?=OS_HOME?>adm/unranked.png" width="16" height="16" class="imgvalign" alt="ranked" /><?php } ?>
	<?=($row["gamename"])?>
	</h2>
	
	<table>
	<tr>
	 <th width="80"  class="padLeft">Duration</th>
	 <th width="140">Date</th>
	 <th width="140">Creator</th>
	 <th width="64" >Winner</th>
	 <th width="80" >Views</th>
	</tr>
	<tr>
	 <td width="80" class="padLeft"><?=secondsToTime($row["duration"])?></td>
	 <td width="140"><?=date($DateFormat,strtotime($row["datetime"]))?></td>
	 <td width="140"><?=$row["creatorname"]?></td>
	 <td width="64">
	 <b>
	   <?php if ($row["winner"] == 1) { ?>Sentinel<?php } ?>
	   <?php if ($row["winner"] == 2) { ?>Scourge<?php } ?>
	   <?php if ($row["winner"] == 0) { ?>Draw<?php } ?>
	 </b>
	 </td>
	 <td width="80"><?=$row["views"]?></td>
	</tr>
	</table>
	<div class="padTop"></div>
	
	<?php if (isset( $Replay) AND !empty($Replay ) ) { ?>
	 <a href="<?=$Replay?>"><img src="<?=OS_HOME?>img/replay.gif" class="imgvalign" alt="replay" width="32" height="32" /></a>
	 <a href="javascript:;" onclick="if (confirm('Delete replay?') ) {  location.href='<?=OS_HOME?>adm/?games&amp;edit=<?=$gameid?>&amp;del_replay' }">Delete replay?</a>
	 <?php } ?>
	 
	<!--
	<select name="stats">
	<?php if ($row["stats"] == 0)  $sel ='selected="selected"'; else $sel = ""; ?>
	  <option <?=$sel?> value="0">Unranked</option>
	<?php if ($row["stats"] == 1)  $sel ='selected="selected"'; else $sel = ""; ?>
	  <option <?=$sel?> value="1">Ranked</option>
	</select>
	<input type="button" value="Update" class="menuButtons" onclick="if (confirm('It is not recommended to manually change game status. Are you sure you want to continue?')) { location.href='<?=OS_HOME?>adm/?games&amp;edit=<?=$gameid?>&amp;update=<?=$row["stats"]?>' }" />
	<div class="padTop"></div>
	-->
	<table>
	<tr>
	  <th width="32" class="padLeft">Hero</th>
	  <th width="60">Slot</th>
	  <th width="150">Player</th>
	  <th width="170">Items</th>
	  <th width="80">K/D/A</th>
	  <th width="80">C/D/N</th>
	  <th width="220">Left</th>
	</tr>
	<?php
	$scourge = 0;
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	if ( $row["newcolour"]>5  AND $scourge == 0 ) { $scourge = 1; ?></table> <table><tr><th width="804">Scourge</th></tr></table> <table><?php } 
	
	if ( $row["newcolour"]>5  ) { $row["newcolour"] = $row["newcolour"] - 1; }
	if ( $row["colour"]>5  ) { $row["colour"] = $row["colour"] - 1; }
	?>
	<tr class="row">
	 <td width="32" class="padLeft"><img src="<?=OS_HOME?>img/heroes/<?=$row["hero"]?>.gif" alt="hero" width="24" height="24" /></td>
	 <td width="60"><?=$row["newcolour"]?> -> <?=$row["colour"]?></td>
	 <td width="150">
	 <a href="<?=OS_HOME?>?u=<?=strtolower($row["name"]) ?>"><?php if ( strtolower($row["name"]) == strtolower($row["banname"]) ) { ?><span class="banned"><?=($row["name"])?></span><?php } 
	 else { ?><?=($row["name"])?><?php } ?></a>
	 </td>
	 <td width="170">
	 <img src="<?=OS_HOME?>img/items/<?php if (!empty($row["itemicon1"])) echo $row["itemicon1"]; else echo "empty.gif"; ?>" alt="item1" width="24" height="24" />
	 <img src="<?=OS_HOME?>img/items/<?php if (!empty($row["itemicon2"])) echo $row["itemicon2"]; else echo "empty.gif"; ?>" alt="item2" width="24" height="24" />
	 <img src="<?=OS_HOME?>img/items/<?php if (!empty($row["itemicon3"])) echo $row["itemicon3"]; else echo "empty.gif"; ?>" alt="item3" width="24" height="24" />
	 <img src="<?=OS_HOME?>img/items/<?php if (!empty($row["itemicon4"])) echo $row["itemicon4"]; else echo "empty.gif"; ?>" alt="item4" width="24" height="24" />
	 <img src="<?=OS_HOME?>img/items/<?php if (!empty($row["itemicon5"])) echo $row["itemicon5"]; else echo "empty.gif"; ?>" alt="item5" width="24" height="24" />
	 <img src="<?=OS_HOME?>img/items/<?php if (!empty($row["itemicon6"])) echo $row["itemicon6"]; else echo "empty.gif"; ?>" alt="item6" width="24" height="24" />
	 </td>
	 <td width="80">
	  <span class="won"><?=($row["kills"])?></span> / 
	  <span class="lost"><?=$row["deaths"]?></span> / 
	  <span class="assists"><?=$row["assists"]?></span>
	 </td>
	 <td width="80">
  	  <span class="won"><?=($row["creepkills"])?></span> / 
	  <span class="lost"><?=$row["creepdenies"]?></span> / 
	  <span class="assists"><?=$row["neutralkills"]?></span>
	 </td>
	 <td width="220" class="overflow_hidden"><?=$row["leftreason"]?></td>
	</tr>
    <?php	
	}
	?>
	</table>
	<div class="padTop"></div>
	<div class="padTop bottom"></div>
	<?php
	
}

//GAMES HISTORY
if ( isset($_GET["sort"]) ) {
   if ( $_GET["sort"] == "id" )       $orderby = "id DESC"; else 
   if ( $_GET["sort"] == "duration" ) $orderby = "duration DESC"; else 
   if ( $_GET["sort"] == "type" )     $orderby = "type ASC"; else 
   if ( $_GET["sort"] == "creator" )  $orderby = "(creatorname) ASC"; 
   if ( $_GET["sort"] == "draw" )     { $orderby = "id DESC"; $filter = " AND dg.winner = 0"; }
   if ( $_GET["sort"] == "sentinel" )     { $orderby = "id DESC"; $filter = " AND dg.winner = 1"; }
   if ( $_GET["sort"] == "scourge" )     { $orderby = "id DESC"; $filter = " AND dg.winner = 2"; }
} 

if ( isset($_GET["game_id"]) AND is_numeric($_GET["game_id"]) ) {
    $id = safeEscape( (int) $_GET["game_id"]);
	$filter = " AND g.id = '".$id."' ";
}

?>
<div align="center">

  <form action="" method="get">  
  Sort by:
  <input type="hidden" name="games" />
<select name="sort">
<?php if (isset($_GET["sort"]) AND $_GET["sort"] =="id") $sel = 'selected="selected"'; else $sel = ""; ?>
    <option <?=$sel?> value="id">ID</option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] =="duration") $sel = 'selected="selected"'; else $sel = ""; ?>
	<option <?=$sel?> value="duration">Duration</option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] =="type") $sel = 'selected="selected"'; else $sel = ""; ?>
	<option <?=$sel?> value="type">Type</option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] =="creator") $sel = 'selected="selected"'; else $sel = ""; ?>
	<option <?=$sel?> value="creator">Creator</option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] =="sentinel") $sel = 'selected="selected"'; else $sel = ""; ?>
	<option <?=$sel?> value="sentinel">Winner: Sentinel</option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] =="scourge") $sel = 'selected="selected"'; else $sel = ""; ?>
	<option <?=$sel?> value="scourge">Winner: Scourge</option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] =="draw") $sel = 'selected="selected"'; else $sel = ""; ?>
	<option <?=$sel?> value="draw">Only Draw Games</option>
</select>
   <input type="submit" class="menuButtons" value="Submit" />
   
   &nbsp; &nbsp; <input type="button" value="Update Stats" class="menuButtons" onclick="location.href='<?=OS_HOME?>adm/update_stats.php'" />
</form>

<?php

  $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_GAMES." as g
  LEFT JOIN ".OSDB_DG." as dg ON g.id = dg.gameid 
  WHERE g.map LIKE '%dota%' AND g.duration>='".$duration."' 
  $filter
  LIMIT 1");
  $result = $sth->execute();
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  $result_per_page = $GamesPerPage;
  $draw_pagination = 1;
  $SHOW_TOTALS = 1;
  include('pagination.php');
  
  $sth = $db->prepare( getAllGames( $duration, $offset, $rowsperpage, $filter, $orderby ) );
  $result = $sth->execute();
  ?>
  <table>
    <tr>
	 <th width="240" class="padLeft ">Game name</th>
	 <th width="64">Action</th>
	 <th width="80">Duration</th>
	 <th width="40">Type</th>
	 <th width="140">Date</th>
	 <th width="150">Creator</th>
	 <th width="80" >Views</th>
	</tr>
  <?php
  while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	//REPLAY
	 $duration = secondsToTime($row["duration"]);
     $replayDate =  strtotime($row["datetime"]);  //3*3600 = +3 HOURS,   +0 minutes.
     $replayDate = date("Y-m-d H:i",$replayDate);
     $gametimenew = substr(str_ireplace(":","-",date("Y-m-d H:i",strtotime($replayDate))),0,16);
	 $gid =  (int)($row["id"]);
	 $gamename = $row["gamename"];
	 include('../inc/get_replay.php');
	 
	 if ( file_exists($replayloc) ) $Replay = ($replayloc); else $Replay = "";
	 //END REPLAY
  ?>
  <tr class="row" style="height:30px;">
    <td width="240" class="padLeft font12 overflow:hidden;">
	<?php if ($row["stats"] == 1) { ?><img <?=ShowToolTip('<div>Ranked game</div>', OS_HOME.'adm/ranked.png', 100, 16, 16)?> src="<?=OS_HOME?>adm/ranked.png" width="16" height="16" class="imgvalign" alt="ranked" /><?php } ?>
	<?php if ($row["stats"] == 0) { ?><img <?=ShowToolTip('<div>Unranked game</div>', OS_HOME.'adm/ranked.png', 100, 16, 16)?> src="<?=OS_HOME?>adm/unranked.png" width="16" height="16" class="imgvalign" alt="ranked" /><?php } ?>
	<a href="<?=OS_HOME?>adm/?games&amp;edit=<?=$row["id"]?>"><span class="winner<?=$row["winner"]?>"><?=$row["gamename"]?></span></a>
	<?php if (isset( $Replay) AND !empty($Replay ) ) { ?>
	 <img class="float_right padRight" src="<?=OS_HOME?>img/replay.gif" alt="replay" width="16" height="16" />
	 <?php } ?>
	</td>
	 <td width="64" class="font12">
	 <a href="<?=OS_HOME?>adm/?games&amp;edit=<?=$row["id"]?>"><img src="<?=OS_HOME?>adm/edit.png" alt="img" /></a>
	 <a href="javascript:;" onclick="if (confirm('Delete Game? (Note: After delete you\'ll have to reset and update the stats)') ) { location.href='<?=OS_HOME?>adm/?games&amp;del=<?=$row["id"]?>' }"><img src="<?=OS_HOME?>adm/del.png" alt="img" /></a>
	 </td>
	<td width="80" class="font12"><?=secondsToTime($row["duration"])?></td>
	<td width="40" class="font12"><?=$row["type"]?></td>
	<td width="140" class="font12"><?=date($DateFormat, strtotime($row["datetime"]))?></td>
	<td width="150" class="font12"><?=$row["creatorname"]?></td>
	<td width="80" class="font12"> <?=($row["views"])?></td>
  </tr>
  <?php
  }
  ?>
  </table>
  </div>
  <?php
  include('pagination.php');
?>