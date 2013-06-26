<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

  foreach ( $UserData as $User ) {
  ?>
  <div align="center">
  
  <h1>
    <?php if (isset($User["letter"]) ) { ?>
    <img <?=ShowToolTip($User["country"], $website.'img/flags/'.($User["letter"]).'.gif', 130, 21, 15)?> class="imgvalign" width="21" height="15" src="<?=$website?>img/flags/<?=$User["letter"]?>.gif" alt="" />
    <?php } ?>
    <?=$User["player"]?>   
	<?php if ( isset( $User["banname"] ) AND !empty( $User["banname"] ) ) { ?> - <span class="banned"><?=$lang["banned"]?></span><?php } ?>
  </h1>
  
  <?php
  if ( isset( $User["banname"] ) AND !empty( $User["banname"] ) ) {
  ?>
  <div class="padTop"><b><?=$lang["reason"]?>:</b> <span class="banned padTop"><?=$User["reason"]?></span></div>
  <div><b><?=$lang["bannedby"]?>:</b> <span class="banned"><a href="<?=$website?>?u=<?=strtolower($User["admin"])?>"><?=$User["admin"]?></a></span></div>
  <?php
  }
  ?>
  
  <div class="padTop">
  <table class="Table500px">
      <tr class="row">
	  <th class="padLeft" width="120"><?=$lang["stats"] ?></th>
	  <th width="160"></th>
	  <th width="90"></th>
	  <th width="175"></th>
	</tr>
    <tr class="row">
	  <td class="padLeft" width="120"><b><?=$lang["score"]?>:</b></td>
	  <td width="160"><?=$User["score"]?></td>
	  <td width="90"><b><?=$lang["win_percent"] ?>:</b></td>
	  <td width="160"><?=$User["winslosses"]?> %</td>
	</tr>
	
    <tr class="row">
	  <td class="padLeft" width="120"><b><?=$lang["kills"]?>:</b></td>
	  <td width="160"><?=$User["kills"]?></td>
	  <td width="90"><b><?=$lang["assists"]?>:</b></td>
	  <td width="160"><?=$User["assists"]?></td>
	</tr>
	
    <tr class="row">
	  <td class="padLeft" width="120"><b><?=$lang["deaths"]?>:</b></td>
	  <td width="160"><?=$User["deaths"]?></td>
	  <td width="90"><b><?=$lang["kd_ratio"]?>:</b></td>
	  <td width="160"><?=($User["kd"])?></td>
	</tr>
	
    <tr class="row">
	  <td class="padLeft" width="120"><b><?=$lang["games"]?>:</b></td>
	  <td width="160"><a href="<?=$website?>?games&amp;uid=<?=$User["id"]?>"><?=$User["games"]?></a></td>
	  <td width="90"><b><?=$lang["wl"] ?>:</b></td>
	  <td width="160"><?=($User["wins"])?> / <?=($User["losses"])?></td>
	</tr>
	
    <tr class="row">
	  <td class="padLeft" width="120"><b><?=$lang["ck"] ?>:</b></td>
	  <td width="160"><?=$User["creeps"]?></td>
	  <td width="90"><b><?=$lang["towers"]?>:</b></td>
	  <td width="160"><?=($User["towers"])?></td>
	</tr>
	
    <tr class="row">
	  <td class="padLeft" width="120"><b><?=$lang["cd"]?>:</b></td>
	  <td width="160"><?=$User["denies"]?></td>
	  <td width="90"><b><?=$lang["rax"]?>:</b></td>
	  <td width="160"><?=($User["rax"])?></td>
	</tr>
	
    <tr class="row">
	  <td class="padLeft" width="120"><b><?=$lang["kpm"]?>:</b></td>
	  <td width="160"><?=$User["kpm"]?></td>
	  <td width="90"><b><?=$lang["dpm"]?>:</b></td>
	  <td width="160"><?=($User["dpm"])?></td>
	</tr>
	
    <tr class="row">
	  <td class="padLeft" width="120"><b><?=$lang["neutrals"]?>:</b></td>
	  <td width="160"><?=$User["neutrals"]?></td>
	  <td width="90"></td>
	  <td width="160"></td>
	</tr>
	
	
  </table>
  </div>
  <div class="padTop"></div>
  <table class="Table500px">
    <tr class="scourgeRow">
	  <td class="padLeft"><?=$lang["time_played"] ?>:</td>
	  <td><?=$TimePlayed["timeplayed"]?></td>
	</tr>
  </table>
 <div class="padTop"></div>
<?php
  include('themes/'.$DefaultStyle.'/single_user_hero_stats.php');
?> 

  <div class="padTop"></div>
   <!-- FASTEST AND LONGEST GAME -->
<?php if (isset($FastestGame ) AND !empty($FastestGame) ) { ?> 
  <table class="Table500px">
    <tr>
	  <th class="padLeft" width="250"><?=$lang["fastest_game"]?></th>
	  <th><?=$lang["duration"]?></th>
	  <th><?=$lang["kills"]?></th>
	  <th><?=$lang["deaths"]?></th>
	  <th><?=$lang["assists"]?></th>
	</tr>
    <tr>
	  <td width="250" class="slot<?=$FastestGame["newcolour"]?> padLeft font12">
	      <a href="<?=$website?>?game=<?=$FastestGame["gameid"]?>"><?=$FastestGame["gamename"]?></a>
	  </td>
	  <td><?=$FastestGame["duration"]?></td>
	  <td><?=$FastestGame["kills"]?></td>
	  <td><?=$FastestGame["deaths"]?></td>
	  <td><?=$FastestGame["assists"]?></td>
	</tr>
	
   </table>
   
<?php } ?>
<?php if (isset($LongestGame ) AND !empty($LongestGame) ) { ?> 
  <table class="Table500px">
    <tr>
	  <th class="padLeft" width="250"><?=$lang["longest_game"]?></th>
	  <th><?=$lang["duration"]?></th>
	  <th><?=$lang["kills"]?></th>
	  <th><?=$lang["deaths"]?></th>
	  <th><?=$lang["assists"]?></th>
	</tr>
    <tr>
	  <td width="250" class="slot<?=$LongestGame["newcolour"]?> padLeft font12">
	      <a href="<?=$website?>?game=<?=$LongestGame["gameid"]?>"><?=$LongestGame["gamename"]?></a>
	  </td>
	  <td><?=$LongestGame["duration"]?></td>
	  <td><?=$LongestGame["kills"]?></td>
	  <td><?=$LongestGame["deaths"]?></td>
	  <td><?=$LongestGame["assists"]?></td>
	</tr>
	
   </table>
   
<?php } ?>
  
  <div class="padTop"></div>
  <div class="padTop"></div>
  
<?=os_display_custom_fields()?>

<table class="Table500px">
<tr>
  <td>
     <div class="padTop aligncenter" align="center">
       <h2><a name="game_history" href="<?=$website?>?games&amp;uid=<?=$User["id"]?>"><?=$lang["user_game_history"] ?></a></h2>
    </div>
  </td>
</tr>
</table>
  
   <table>
    <tr>
	 <th width="220" class="padLeft"><?=$lang["game"]?></th>
	 <?php if (isset($_GET["u"]) ) { ?>
	 <th width="40"><?=$lang["hero"]?></th>
	 <th width="90"><?=$lang["kda"]?></th>
	 <th width="90"><?=$lang["cdn"]?></th>
	 <?php } ?>
	 <th width="80"><?=$lang["duration"]?></th>
	 <th width="50"><?=$lang["type"]?></th>
	 <th width="140"><?=$lang["date"]?></th>
	 <?php if (!isset($_GET["u"]) ) { ?>
	 <th width="160"><?=$lang["map"]?></th>
	 <?php } ?>
	 <th width="160"><?=$lang["creator"]?></th>
   </tr>
  <?php
  
  foreach ($GamesData as $Games) {
  ?>
  <tr class="row GameHistoryRow">
	 <td width="220" class="padLeft overflow_hidden slot<?=$Games["newcolour"]?>">
 	   <?=OS_WinLoseIcon( $Games["win"] )?>
	   <a href="<?=$website?>?game=<?=$Games["id"]?>"><span class="winner<?=$Games["winner"]?>"><?=$Games["gamename"]?></span></a>
	   <?=OS_IsUserGameLeaver($Games["leaver"])?>
	 </td>
	 <?php if (isset($_GET["u"]) ) { ?>
	 <td width="40" height="40"><?=OS_UserHeroHistoryLink($User["id"], $Games["hero"], $lang["show_hero_history"]) ?></td>
	 <td width="90">
	 	<span class="won"><?=($Games["kills"])?></span> / 
	    <span class="lost"><?=$Games["deaths"]?></span> / 
	    <span class="assists"><?=$Games["assists"]?></span>
	 </td>
	 <td width="90">
	 	<span class="won"><?=($Games["creepkills"])?></span> / 
	    <span class="lost"><?=$Games["creepdenies"]?></span> / 
	    <span class="assists"><?=$Games["neutrals"]?></span>
	 </td>
	 <?php } ?>
	 <td width="80"><?=secondsToTime($Games["duration"])?></td>
	 <td width="50"><?=$Games["type"]?></td>
	 <td width="140"><?=date($DateFormat, strtotime($Games["datetime"]))?></td>
	 <?php if (!isset($_GET["u"]) ) { ?>
	 <td width="160"><?=$Games["map"]?></td>
	 <?php } ?>
	 <td width="160"><?=$Games["ownername"]?></td>
   </tr>
  <?php
  }
  ?>
  </table> 
  </div>
  <?php
   $SHOW_TOTALS = 1;
   include('inc/pagination.php');
  }
 ?>