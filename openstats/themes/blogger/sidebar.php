<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div class="sidebar-wrapper">
<div class="sidebar section" id="sidebar">
<?php if ($GameListPatch == 1 AND isset($LiveGamesData) ) {  ?>
<div class="widget Label" id="Label2">
<h2><?=$lang["current_games"]?> </h2>
<div class="widget-content list-label-widget-content">
  <table>
  <tr>
    <th class="padLeft" width="170"><?=$lang["game_name"]?></th>
	<th><?=$lang["slots"] ?></th>
	<th></th>
  </tr>
  <?php
  foreach ( $LiveGamesData as $LiveGames ) {
  if (!empty($LiveGames["gamename"]) ) {
  ?>
  <tr>
    <td class="padLeft">
	   <a href="javascript:;" onclick="showhide('<?=$LiveGames["botid"]?>')"><?=$LiveGames["gamename"]?></a>
	<div id="<?=$LiveGames["botid"]?>" style="display:none;">
	 <table>
	 <?php
	 //print_r($LiveGames["players"]);
	 for($i = 0; $i < count( $LiveGames["players"] ) - 2; $i+=3) {
	 	$username = $LiveGames["players"][$i];
		$realm = $LiveGames["players"][$i + 1];
		$ping = $LiveGames["players"][$i + 2];
		
		if ( $username == "" ) {
		?>
		<tr>
		  <td><?=$lang["empty"] ?></td>
		  <td></td>
		</tr>
		<?php
		} else {
		?>
        <tr>
		  <td><b><?=$username?></b></td>
		  <td><?=$ping?> <?=$lang["ms"] ?></td>
		</tr>
		<?php
		}
	 }
	 ?>
	 </table>
	</div>
	   
	</td>
	<td><?=$LiveGames["slotstaken"]?> / <?=$LiveGames["slotstotal"]?></td>
  </tr>
  <?php } 
  }
  ?>
  </table>
  
  <a class="menuButtons" href="<?=OS_HOME?>"><?=$lang["refresh"]?></a>
</div>
</div>
<?php } ?>

<?php   if ( isset($RecentGamesData) AND !empty($RecentGamesData) ) { ?>
<div class="widget Label" id="Label2">
 <h2><?=$lang["recent_games"]?></h2>
<div class="widget-content list-label-widget-content">

<?php
  if ( isset($_GET["uid"]) AND is_numeric($_GET["uid"]) AND isset($GamesData[0]["player"]) ) {
   ?>
   <div class="padTop padBottom">
     <h2><?=$lang["game_history"]?> <a href="<?=OS_HOME?>?u=<?=(int)$_GET["uid"]?>"><?=$GamesData[0]["player"]?></a></h2>
   </div>
   <?php
  }
?>
  <table>
    <tr>
	 <th width="275" class="padLeft"><?=$lang["game"]?></th>
	 <th width="80"><?=$lang["duration"]?></th>
   </tr>
  <?php
  foreach ($RecentGamesData as $Games) {
  ?>
  <tr class="row">
	 <td width="260" class="padLeft">
	 <a href="<?=OS_HOME?>?game=<?=$Games["id"]?>"><span class="winner<?=$Games["winner"]?>"><?=$Games["gamename"]?></span></a>
	 <?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
	 <a style="float: right; font-size:11px; padding-right: 5px;" href="<?=OS_HOME?>adm/?games&amp;game_id=<?=$Games["id"]?>">Edit</a>
	 <?php } ?>
	 <?php if (isset( $Games["replay"]) AND !empty($Games["replay"] ) ) { ?>
	 <img class="float_right padRight" src="<?=OS_HOME?>img/replay.gif" alt="replay" width="16" height="16" />
	 <?php } ?>
	 </td>
	 <td width="80"><?=secondsToTime($Games["duration"])?></td>
   </tr>
  <?php
  }
  ?>
  </table>

</div>
</div>
<?php } ?>

</div>
</div>