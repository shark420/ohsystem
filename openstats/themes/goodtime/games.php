<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div class="entry clearfix">
<?php
  if ( isset($_GET["uid"]) AND is_numeric($_GET["uid"]) AND isset($GamesData[0]["player"]) ) {
   ?>
   <div class="padTop padBottom">
     <h2><?=$lang["game_history"]?> <a href="<?=OS_HOME?>?u=<?=(int)$_GET["uid"]?>"><?=$GamesData[0]["player"]?></a> <?=OS_HeroIcon( $GamesData[0]["hero_history"] )?></h2>
   </div>
   <?php
  }
?>
<?=DisplayGameFilter($StartYear) ?>
  <table>
    <tr>
	 <th width="240" class="padLeft"><?=$lang["game"]?></th>
	 <th width="80"><?=$lang["duration"]?></th>
	 <th width="50"><?=$lang["type"]?></th>
	 <th width="140"><?=$lang["date"]?></th>
	  <th width="160"><?=$lang["map"]?></th>
	 <th width="120"><?=$lang["creator"]?></th>
   </tr>
  <?php
  
  foreach ($GamesData as $Games) {
  ?>
  <tr class="row">
	 <td width="240" class="padLeft" class="font12">
	 <a href="<?=OS_HOME?>?game=<?=$Games["id"]?>"><span class="font13 winner<?=$Games["winner"]?>"><?=$Games["gamename"]?></span></a>
	 <?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
	 <a style="float: right; font-size:11px; padding-right: 5px;" href="<?=OS_HOME?>adm/?games&amp;game_id=<?=$Games["id"]?>">Edit</a>
	 <?php } ?>
	 <?php if (isset( $Games["replay"]) AND !empty($Games["replay"] ) ) { ?>
	 <img class="float_right padRight" src="<?=OS_HOME?>img/replay.gif" alt="replay" width="16" height="16" />
	 <?php } ?>
	 </td> 
	 <td width="80"  class="font12"><?=secondsToTime($Games["duration"])?></td>
	 <td width="50"  class="font12"><?=$Games["type"]?></td>
	 <td width="140" class="font12"><?=date($DateFormat, strtotime($Games["datetime"]))?></td>
	 <td width="160" class="font12"><?=$Games["map"]?></td>
	 <td width="120" class="font12"><?=$Games["ownername"]?></td>
   </tr>
  <?php
  }
  ?>
  </table>

</div>
  <?php
  $SHOW_TOTALS = 1;
  include('inc/pagination.php');
?>