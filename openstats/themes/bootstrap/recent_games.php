<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

if ( isset($RecentGamesData) AND !empty($RecentGamesData) ) {
?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed">
  <h4><?=$lang["recent_games"]?></h4>
<?php
  if ( isset($_GET["uid"]) AND is_numeric($_GET["uid"]) AND isset($GamesData[0]["player"]) ) {
   ?>
   <div class="padTop padBottom">
     <h2><?=$lang["game_history"]?> <a href="<?=OS_HOME?>?u=<?=(int)$_GET["uid"]?>"><?=$GamesData[0]["player"]?></a></h2>
   </div>
   <?php
  }
?>
  <table class="table-hover table table-condensed table-bordered">
    <tr>
	 <th style="width: 220px" class="padLeft"><?=$lang["game"]?></th>
	 <th style="width: 80px"><?=$lang["duration"]?></th>
	 <th style="width: 50px"><?=$lang["type"]?></th>
	 <th style="width: 140px"><?=$lang["date"]?></th>
	  <th style="width: 160px"><?=$lang["map"]?></th>
	 <th style="width: 160px"><?=$lang["creator"]?></th>
   </tr>
  <?php
  
  foreach ($RecentGamesData as $Games) {
  ?>
  <tr>
	 <td style="width: 220px" class="padLeft">
	 <a href="<?=OS_HOME?>?game=<?=$Games["id"]?>"><span class="winner<?=$Games["winner"]?>"><?=$Games["gamename"]?></span></a>
	 <?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
	 <a style="float: right; font-size:11px; padding-right: 5px;" href="<?=OS_HOME?>adm/?games&amp;game_id=<?=$Games["id"]?>">Edit</a>
	 <?php } ?>
	 <?php if (isset( $Games["replay"]) AND !empty($Games["replay"] ) ) { ?>
	 <img class="float_right padRight" src="<?=OS_HOME?>img/replay.gif" alt="replay" width="16" height="16" />
	 <?php } ?>
	 </td>
	 <td style="width: 80px"><?=secondsToTime($Games["duration"])?></td>
	 <td style="width: 50px"><?=$Games["type"]?></td>
	 <td style="width: 140px"><?=date($DateFormat, strtotime($Games["datetime"]))?></td>
	 <td style="width: 160px"><?=$Games["map"]?></td>
	 <td style="width: 160px"><?=$Games["ownername"]?></td>
   </tr>
  <?php
  }
  ?>
  </table>
	 
     </div>
    </div>
   </div>
  </div>
</div>
 <?php } ?>