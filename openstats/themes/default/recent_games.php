<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

if ( isset($RecentGamesData) AND !empty($RecentGamesData) ) {
?>

<div align="center">
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
  <table style="width:824px;">
    <tr>
	 <th width="220" class="alignleft padLeft"><?=$lang["game"]?></th>
	 <th width="80" class="alignleft"><?=$lang["duration"]?></th>
	 <th width="50" class="alignleft"><?=$lang["type"]?></th>
	 <th width="140" class="alignleft"><?=$lang["date"]?></th>
	 <th width="160" class="alignleft"><?=$lang["map"]?></th>
	 <th width="160" class="alignleft"><?=$lang["creator"]?></th>
   </tr>
  <?php
  
  foreach ($RecentGamesData as $Games) {
  ?>
  <tr class="row">
	 <td width="220" class="alignleft padLeft">
	 <a href="<?=$website?>?game=<?=$Games["id"]?>"><span class="winner<?=$Games["winner"]?>"><?=$Games["gamename"]?></span></a>
	 <?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
	 <a style="float: right; font-size:11px; padding-right: 5px;" href="<?=$website?>adm/?games&amp;game_id=<?=$Games["id"]?>">Edit</a>
	 <?php } ?>
	 <?php if (isset( $Games["replay"]) AND !empty($Games["replay"] ) ) { ?>
	 <img class="float_right padRight" src="<?=$website?>img/replay.gif" alt="replay" width="16" height="16" />
	 <?php } ?>
	 </td>
	 <td width="80" class="alignleft"><?=secondsToTime($Games["duration"])?></td>
	 <td width="50" class="alignleft"><?=$Games["type"]?></td>
	 <td width="140" class="alignleft"><?=date($DateFormat, strtotime($Games["datetime"]))?></td>
	 <td width="160" class="alignleft"><?=$Games["map"]?></td>
	 <td width="160" class="alignleft"><?=$Games["ownername"]?></td>
   </tr>
  <?php
  }
  ?>
  </table>
  </div>
 <?php } ?>