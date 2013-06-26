<?php
    //---------------------------------------------------\\
   //----------    Replay - GameLog Template    ----------\\
  //_______________________________________________________\\
  
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

if ($drawTable == "replay_header") {
?>
<div class="container">
    <div><a class="menuButtons" name="gamelog" onclick="showhide('game_log');" href="javascript:;"><b><?=$lang["game_log"]?></b></a></div>
</div>

<div id="game_log" style="display: none;">

<div class="container">
   <table class="ReplayTable">
    <tr>
      <td colspan="13" class="ReplayHeader">
       <table>

<?php } 

if ($drawTable == "replay_left") {
?><tr class="row">
   <td align="right" width="82px"><?php echo $timeSec; ?></td>
   <td style="width:130px;padding-right:4px;" class="font12" align="right"><?php echo $ply; ?></td>
<?php } 

if ($drawTable == "replay_text") {
?>
   <td class="GameSystem"><?php echo $text; ?></td>
<?php } 

if ($drawTable == "replay_first_blood") {
?> <td class="GameSystem">
  <div style="background-color:#580202;"><span class="<?php echo $slotcolor[$killer];?>"><?php echo $slotname[$killer];?></span>
		<?php echo $text;?><span class="<?php echo $slotcolor[$victim];?>"><?php echo $slotname[$victim];?></span> <b><?php echo $lang["log_first_blood"]; ?></b></div>
   </td>
<?php } 

if ($drawTable == "replay_action") {
?> <td class="GameSystem">
    <span class="<?php echo $slotcolor[$killer]; ?>"><?php echo $slotname[$killer]; ?></span> <?php echo $text; ?><span class="<?php echo $slotcolor[$victim]; ?>"> <?php echo $slotname[$victim];?></span>
   </td>
<?php }

if ($drawTable == "replay_victim_killer") {
?> <td class="GameSystem"><span class="<?php echo $slotcolor[$killer]; ?>"><?php echo $slotname[$killer]; ?></span> <?php echo $lang["log_suicide"]; ?>
  </td>
<?php } 

if ($drawTable == "replay_denie_teammate") {
?> <td class="GameSystem">
	  <span class="<?php echo $slotcolor[$killer]; ?>"><?php echo $slotname[$killer]; ?></span> <?php echo $lang["log_denied_teammate"]; ?> <span class="<?php echo $slotcolor[$victim];?>"><?php echo $slotname[$victim];?></span>
  </td>
<?php }

if ($drawTable == "replay_hero_action") {
?> <td class="GameSystem"><span class="<?php echo $slotcolor[$killer]; ?>"><?php echo $slotname[$killer] ;?></span> <?php echo $text; ?><span class="<?php echo $slotcolor[$victim]; ?>"> <?php echo $slotname[$victim];?></span>
  </td>
<?php } 

if ($drawTable == "replay_courier") {
?> <td class="GameSystem"><span class="<?php echo $slotcolor[$victim];?>"><?php echo $slotname[$victim];?> </span><?php echo $text;?><span class="<?php echo $slotcolor[$killer];?>"> <?php echo $slotname[$killer];?></span>
  </td>
<?php } 

if ($drawTable == "replay_tower") {
?> <td class="GameSystem"><span class="<?php echo $slotcolor[$killer];?>"><?php echo $slotname[$killer];?></span><?php echo $text; ?> <?php echo $content["side"]; ?>  <?php echo $lang["log_level"] ; ?>  <?php echo $content["level"]; ?><span class="<?php echo strtolower($content['team']);?>"> <?php echo $content["team"];?> </span> <?php echo $lang["log_tower"] ; ?>
  </td>
<?php } 

if ($drawTable == "replay_rax") {
?> <td class="GameSystem"><span class="<?php echo $slotcolor[$killer];?>"><?php echo $slotname[$killer];?></span><?php echo $text;?> <?php echo $content["side"];?> <?php echo $content["raxtype"];?><span class='<?php echo strtolower($content['team']);?>'> <?php echo $content["team"];?></span> <?php echo $lang["log_barracks"]; ?></td>
<?php } 

if ($drawTable == "replay_throne") {
?> <td class="GameSystem"><?php echo $text; ?></td>
<?php } 

if ($drawTable == "replay_tree") {
?> <td class="GameSystem"><?php echo $text; ?></td>
<?php }

if ($drawTable == "replay_priv") {
?> <td align="left" class="scourge"><span style="color:#00A404;"><?php echo $lang["log_priv"]; ?> <?php echo $text; ?></span></td>
<?php }

if ($drawTable == "replay_allies1") {
?> <td align="left" class="sentinel"><span style="color:#B32704;"><?php echo $lang["log_ally"]; ?> <?php echo $text; ?></span></td>
<?php }

if ($drawTable == "replay_allies2") {
?> <td align="left" class="scourge"><span style="color:#00A404;"><?php echo $lang["log_ally"]; ?> <?php echo $text; ?></span></td>
<?php }

if ($drawTable == "replay_footer_table") {
?>       </table>
           </tr>
	       </td>
	  </tr>
	</table>
</div>
</div>
<?php } ?>