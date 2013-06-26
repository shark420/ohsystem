<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div class="entry clearfix" >
  
  <table class="tableBig">
  <tr>
    <td class="padLeft">
	  <div align="center">
	     <h1><a href="<?=OS_HOME?>?game=<?=(int) $_GET["game"]?>"><?=$GameData[0]["gamename"]?></a></h1>
	  </div>
	</td>
  </tr>
  <tr>
	<td class="padTop">
	<div align="center">
	<b><?=$lang["date"]?>:</b> <?=$GameData[0]["datetime"]?>,
	<b><?=$lang["duration"]?>:</b> <?=$GameData[0]["duration"]?>
    <?=OS_EditGame( $_GET["game"] )?>
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
  foreach ($GameData as $Game) { ?>

<?php if ( $Game["side"] == "sentinel" ) { //SENTINEL ROW ?>
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
  <?php } ?>
  
  <?php if ( $Game["side"] == "scourge" ) { //SCOURGE ROW  ?>
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
<?php }?> 
  <table>
  <tr class="row SingleGameRow <?=$Game["hideslot"]?> ">
 <td width="75" class="padLeft slot<?=$Game["counter"]?>">
 <a href="<?=OS_HOME?>?hero=<?=$Game["heroid"]?>"><img <?=ShowToolTip("<div>".$Game["description"]."</div>", OS_HOME.'img/heroes/'.($Game["hero"]), 100, 64, 64)?> src="<?=OS_HOME?>img/heroes/<?=$Game["hero"]?>" alt="hero" width="48" height="48" /></a></td>
 <td width="240">
 
 <h4>
    <?=OS_ShowUserFlag( $Game["letter"], $Game["country"] )?>
    <?=OS_SingleGameUser($Game["userid"], $Game["full_name"], $Game["name"], $BestPlayer)?>
	<span class="player_scores<?=$Game["class"]?>"><?=$Game["score_points"]?></span>
	<?=OS_IsUserGameAdmin( $Game["admin"], $lang["admin"] )?>
	<?=OS_IsUserGameWarned( $Game["warn"],  $Game["warn_expire"], $lang["warned"] )?>
	<?=OS_IsUserGameBanned( $Game["banned"], $lang["banned"] ) ?>
	<?=OS_IsUserGameSafe( $Game["safelist"], $lang["safelist"] )?>
 </h4>
 
 <div>
 <?=OS_ShowItem( $Game["item1"], $Game["itemname1"], $Game["itemicon1"] )?>
 <?=OS_ShowItem( $Game["item2"], $Game["itemname2"], $Game["itemicon2"] )?>
 <?=OS_ShowItem( $Game["item3"], $Game["itemname3"], $Game["itemicon3"] )?>
 <?=OS_ShowItem( $Game["item4"], $Game["itemname4"], $Game["itemicon4"] )?>
 <?=OS_ShowItem( $Game["item5"], $Game["itemname5"], $Game["itemicon5"] )?>
 <?=OS_ShowItem( $Game["item6"], $Game["itemname6"], $Game["itemicon6"] )?>
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
 <td width="180" class="statsscore<?=$Game["leaver"]?>">
 <?=$Game["left"]?>
 <div class="left_reason overflow_hidden"><?=$Game["leftreason"]?></div>
 <?=OS_IsUserGameLeaver( $Game["leaver"], $lang["leaver"])?>
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