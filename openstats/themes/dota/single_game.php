<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>

  <div align="center">
  
  <table class="tableBig">
  <tr>
    <td class="padLeft">
	  <div align="center">
	     <h1><a href="<?=$website?>?game=<?=(int) $_GET["game"]?>"><?=$GameData[0]["gamename"]?></a></h1>
	  </div>
	</td>
  </tr>
  <tr>
	<td class="padTop">
	<div align="center">
	<b><?=$lang["date"]?>:</b> <?=$GameData[0]["datetime"]?>,
	<b><?=$lang["duration"]?>:</b> <?=$GameData[0]["duration"]?>
<?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
    <a href="<?=$website?>/adm/?games&amp;game_id=<?=(int) $_GET["game"]?>"><img src="<?=$website?>/adm/edit.png" alt="*" width="16" height="16" /></a>
<?php } ?>
    </div>
   </td>
  </tr>
  </table>
<?php if (isset($GameData[0]["replay"]) AND !empty($GameData[0]["replay"]) ) { ?>
<table class="tableBig">
  <tr class="h32">
    <td class="padLeft padTop padBottom">
	   <div align="center">
	   <a class="menuButtons" href="<?=$GameData[0]["replay"]?>"><?=$lang["download_replay"]?></a>
	   <a onclick="showhide('game_log');" class="menuButtons" href="#gamelog"><?=$lang["view_gamelog"] ?></a>
	   </div>
	</td>
  </tr>
  </table>
<?php } ?>
  
  <div class="padTop"></div>
  
  <div style="margin-top: 16px; margin-bottom: 10px; display: none;">
  <h2>
    <?=$GameData[0]["gamename"]?>, 
    <b><?=$lang["duration"]?>:</b> <?=$GameData[0]["duration"]?>, 
    <b><?=$lang["date"]?>:</b> <?=$GameData[0]["datetime"]?>
  </h2>
  </div>
  <?php
  $ScourgeRow = 0;
  $SentinelRow = 0;
  $counter = 0;
  foreach ($GameData as $Game) {
  $counter++;
  if ( $Game["newcolour"] >5 AND $ScourgeRow == 0 ) {
  $ScourgeRow = 1; 
  ?>


<table>
  <tr class="scourgeRow">
  <td width="850" class="aligncenter" align="center">
	 <?php
	 if ($GameData[0]["winner"] == 1) { ?><?=$lang["scou_loser"]?><?php }   else
	 if ($GameData[0]["winner"] == 2) { ?><?=$lang["scou_winner"]?><?php }  else
	 if ($GameData[0]["winner"] == 0) { ?><?=$lang["draw_game"]?><?php }
	 ?>
	 </td>
    </tr>
</table>
	
  <table>
  <tr>
    <th width="75" class="padLeft"><?=$lang["hero"]?></th>
    <th width="220"><?=$lang["player"]?></th>
    <th width="90" ><?=$lang["kda"]?></th>
	<th width="90" ><?=$lang["cdn"]?></th>
	<th width="90" ><?=$lang["trc"]?></th>
	<th width="90" ><?=$lang["gold"]?></th>
	<th width="180"><?=$lang["left"]?></th>
  </tr>
    </table>
  <?php
  }
  
  
  if ( $Game["newcolour"] <=5 AND $SentinelRow == 0 ) {
  $SentinelRow = 1; 
  ?>


<table>
  <tr class="sentinelRow">
  <td width="850" class="aligncenter" align="center">
	 <?php
	 if ($GameData[0]["winner"] == 1) { ?><?=$lang["sent_winner"]?><?php } else
	 if ($GameData[0]["winner"] == 2) { ?><?=$lang["sent_loser"]?><?php }  else
	 if ($GameData[0]["winner"] == 0) { ?><?=$lang["draw_game"]?><?php }
	 ?>
	 </td>
    </tr>
</table>
	
  <table>
  <tr>
    <th width="75" class="padLeft"><?=$lang["hero"]?></th>
    <th width="220"><?=$lang["player"]?></th>
    <th width="90" ><?=$lang["kda"]?></th>
	<th width="90" ><?=$lang["cdn"]?></th>
	<th width="90" ><?=$lang["trc"]?></th>
	<th width="90" ><?=$lang["gold"]?></th>
	<th width="180"><?=$lang["left"]?></th>
  </tr>
</table>
  <?php
  }
  
  ?>
  <table>
  <tr class="row SingleGameRow <?=$Game["hideslot"]?> ">
 <td width="75" class="padLeft slot<?=$counter?>">
 <a href="<?=$website?>?hero=<?=$Game["heroid"]?>"><img <?=ShowToolTip("<div>".$Game["description"]."</div>", $website.'img/heroes/'.($Game["hero"]), 100, 64, 64)?> src="<?=$website?>img/heroes/<?=$Game["hero"]?>" alt="hero" width="48" height="48" /></a></td>
 <td width="220">
 <h4>
	<?php if (isset($Game["letter"]) AND !empty($Game["letter"]) ) { ?>
	<img <?=ShowToolTip($Game["country"], $website.'img/flags/'.($Game["letter"]).'.gif', 130, 21, 15)?> class="imgvalign" width="21" height="15" src="<?=$website?>img/flags/<?=$Game["letter"]?>.gif" alt="" />
	<?php } ?>
  <a href="<?=$website?>?u=<?=$Game["userid"]?>"><?=$Game["full_name"]?></a> 
  <?php if (strtolower($BestPlayer) == strtolower($Game["name"]) ) { ?><img src="<?=$website?>img/winner.png" class="imgvalign" width="24" height="24" title="<?=$lang["best_player"] . $Game["name"] ?>" /> <?php } ?>
 </h4>
 <div>
 <a href="<?=$website?>?item=<?=$Game["item1"]?>"><img <?=ShowToolTip("<div>".($Game["itemname1"])."</div>", $website.'img/items/'.$Game["itemicon1"], 100, 64, 64)?> src="<?=$website?>img/items/<?=$Game["itemicon1"]?>" alt="item1" width="32" height="32" /></a>
  <a href="<?=$website?>?item=<?=$Game["item2"]?>"><img <?=ShowToolTip("<div>".($Game["itemname2"])."</div>", $website.'img/items/'.$Game["itemicon2"], 100, 64, 64)?>src="<?=$website?>img/items/<?=$Game["itemicon2"]?>" alt="item2" width="32" height="32" /></a>
  <a href="<?=$website?>?item=<?=$Game["item3"]?>"><img <?=ShowToolTip("<div>".($Game["itemname3"])."</div>", $website.'img/items/'.$Game["itemicon3"], 100, 64, 64)?> src="<?=$website?>img/items/<?=$Game["itemicon3"]?>" alt="item3" width="32" height="32" /></a>
  <a href="<?=$website?>?item=<?=$Game["item4"]?>"><img <?=ShowToolTip("<div>".($Game["itemname4"])."</div>", $website.'img/items/'.$Game["itemicon4"], 100, 64, 64)?> src="<?=$website?>img/items/<?=$Game["itemicon4"]?>" alt="item4" width="32" height="32" /></a>
  <a href="<?=$website?>?item=<?=$Game["item5"]?>"><img <?=ShowToolTip("<div>".($Game["itemname5"])."</div>", $website.'img/items/'.$Game["itemicon5"], 100, 64, 64)?> src="<?=$website?>img/items/<?=$Game["itemicon5"]?>" alt="item5" width="32" height="32" /></a>
  <a href="<?=$website?>?item=<?=$Game["item6"]?>"><img <?=ShowToolTip("<div>".($Game["itemname6"])."</div>", $website.'img/items/'.$Game["itemicon6"], 100, 64, 64)?> src="<?=$website?>img/items/<?=$Game["itemicon6"]?>" alt="item6" width="32" height="32" /></a>
 </div>
 </td>
 <td width="90" class="statsscore">
 	  <span class="won"><?=($Game["kills"])?></span> / 
	  <span class="lost"><?=$Game["deaths"]?></span> / 
	  <span class="assists"><?=$Game["assists"]?></span>
 </td>
 <td width="90" class="statsscore">
  	  <span class="won"><?=($Game["creepkills"])?></span> / 
	  <span class="lost"><?=$Game["creepdenies"]?></span> / 
	  <span class="assists"><?=$Game["neutralkills"]?></span>
 </td>
 <td width="90" class="statsscore">
   	  <span class="won"><?=($Game["towerkills"])?></span> / 
	  <span class="lost"><?=$Game["raxkills"]?></span> / 
	  <span class="assists"><?=$Game["courierkills"]?></span>
 </td>
 <td width="90" class="statsscore"><?=$Game["gold"]?></td>
 <td width="180" class="statsscore">
 <?=$Game["left"]?>
 <div class="left_reason overflow_hidden"><?=$Game["leftreason"]?></div>
 </td>
   </tr>
  </table>
  <?php
  }
  ?>
  <div class="padTop"></div>
  <table class="tableBig">
<?php if ($PlayerKills>0) { ?> 
    <tr class="row"  height="26" >
	  <td width="180"class="padLeft"><div class="best_player_title"><?=$lang["best_player"] ?></span></div>
	  <td width="180" class="padLeft" style="text-align:left;"><h4 class="best_player">
	   <a href="<?=OS_HOME?>?u=<?=($BestPlayerID)?>"><?=$BestPlayer?></a> <img src="<?=OS_HOME?>img/best.png" class="imgvalign" width="24" height="24" /> 
	   </h4></td>
	  <td></td>
	</tr>  
    <tr class="row">
	  <td width="180" class="padLeft"><b><?=$lang["most_kills"]?></b></td>
	  <td width="180" class="padLeft"> <h4><a href="<?=OS_HOME?>?u=<?=($MostKillsID)?>"><?=$MostKills?></a></h4></td>
	  <td class="padLeft"><?=$PlayerKills?></td>
	</tr> 
<?php } ?>
<?php if ($PlayerAssists>0) { ?> 
    <tr class="row">
	  <td width="180" class="padLeft"><b><?=$lang["most_assists"]?></b></td>
	  <td width="180" class="padLeft"> <h4><a href="<?=OS_HOME?>?u=<?=($MostAssistsID)?>"><?=$MostAssists?></a></h4></td>
	  <td class="padLeft"><?=$PlayerAssists?></td>
<?php } ?>
	</tr> 
<?php if ($PlayerDeaths>0) { ?> 
    <tr class="row">
	  <td width="180" class="padLeft"><b><?=$lang["most_deaths"] ?></b></td>
	  <td width="180" class="padLeft"> <h4><a href="<?=OS_HOME?>?u=<?=($MostDeathsID)?>"><?=$MostDeaths?></a></h4></td>
	  <td class="padLeft"><?=$PlayerDeaths?></td>
	</tr> 
<?php } ?>
<?php if ($PlayerCK>0) { ?>
	<tr class="row">
	  <td width="180" class="padLeft"><b><?=$lang["top_ck"]?></b></td>
	  <td width="180" class="padLeft"> <h4><a href="<?=OS_HOME?>?u=<?=($MostCKID)?>"><?=$MostCK?></a></h4></td>
	  <td class="padLeft"><?=$PlayerCK?></td>
	</tr> 
<?php } ?>
<?php if ($PlayerCD>0) { ?>
	<tr class="row">
	  <td width="180" class="padLeft"><b><?=$lang["top_cd"]?></b></td>
	  <td width="180" class="padLeft"> <h4><a href="<?=OS_HOME?>?u=<?=($MostCDID)?>"><?=$MostCD?></a></h4></td>
	  <td class="padLeft"><?=$PlayerCD?></td>
	</tr> 
<?php } ?>
   </table>
 
<?=os_display_custom_fields()?>
 
<?php 
//REPLAY - GAME LOG
include("inc/show_gamelog.php"); 
?>

</div>