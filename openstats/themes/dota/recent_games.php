<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

if ( isset($RecentGamesData) AND !empty($RecentGamesData) ) {
?>

<div align="center">

<div class="padTop"></div>
  <h2><?=$lang["recent_games"]?></h2>
<?php
  if ( isset($_GET["uid"]) AND is_numeric($_GET["uid"]) AND isset($GamesData[0]["player"]) ) {
   ?>
   <div class="padTop padBottom">
     <h2><?=$lang["game_history"]?> <a href="<?=$website?>?u=<?=(int)$_GET["uid"]?>"><?=$GamesData[0]["player"]?></a></h2>
   </div>
   <?php
  }
?>
  <table>
    <tr>
	 <th width="220" class="padLeft"><?=$lang["game"]?></th>
	 <th width="80"><?=$lang["duration"]?></th>
	 <th width="50"><?=$lang["type"]?></th>
	 <th width="140"><?=$lang["date"]?></th>
	  <th width="160"><?=$lang["map"]?></th>
	 <th width="160"><?=$lang["creator"]?></th>
   </tr>
  <?php
  
  foreach ($RecentGamesData as $Games) {
  ?>
  <tr class="row">
	 <td width="220" class="padLeft">
	 <a href="<?=$website?>?game=<?=$Games["id"]?>"><span class="winner<?=$Games["winner"]?>"><?=$Games["gamename"]?></span></a>
	 <?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
	 <a style="float: right; font-size:11px; padding-right: 5px;" href="<?=$website?>adm/?games&amp;game_id=<?=$Games["id"]?>">Edit</a>
	 <?php } ?>
	 <?php if (isset( $Games["replay"]) AND !empty($Games["replay"] ) ) { ?>
	 <img class="float_right padRight" src="<?=$website?>img/replay.gif" alt="replay" width="16" height="16" />
	 <?php } ?>
	 </td>
	 <td width="80"><?=secondsToTime($Games["duration"])?></td>
	 <td width="50"><?=$Games["type"]?></td>
	 <td width="140"><?=date($DateFormat, strtotime($Games["datetime"]))?></td>
	 <td width="160"><?=$Games["map"]?></td>
	 <td width="160"><?=$Games["ownername"]?></td>
   </tr>
  <?php
  }
  ?>
  </table>
  </div>
 <?php } ?>