<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>

<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed">
	 
     <?=OS_SortTopPlayers()?>
	 
	 <?=OS_ComparePlayers( 'form_start' )?>
    <table>
     <tr> 
	   <th width="32" class="padLeft">&nbsp;</th>
	   <th width="160"><?=$lang["player"]?></th>
	   <th width="80"><?=$lang["score"]?></th>
	   <th width="80"><?=$lang["games"]?></th>
	   <th width="40"><span <?=ShowToolTip($lang["longest_streak"]." / ".$lang["losing_streak"], OS_HOME.'img/winner.png', 230, 32, 32)?>><img src="<?=OS_HOME?>img/streak.gif" width="20" /></span></th>
	   <th width="30"><span <?=ShowToolTip($lang["zero_deaths"], OS_HOME.'img/winner.png', 210, 32, 32)?>><img src="<?=OS_HOME?>img/winner.png" width="20" /></span></th>
	   <th width="90"><?=$lang["wld"]?></th>
	   <th width="70"><?=$lang["wl_percent"]?></th>
	   <th width="120"><?=$lang["kda"]?></th>
	   <th width="160"><?=$lang["cdn"]?></th>
	   <th width="120"><?=$lang["tr"]?></th>
	  </tr>
<?php 
foreach ($TopData as $Data) {
  ?>
  <tr class="row">
    <td width="32" class="padLeft"><?=$Data["counter"]?></td>
    <td width="180" class="font12">
	<?=OS_ComparePlayers( 'checkbox', $Data["id"] )?>
	
	<?=OS_ShowUserFlag( $Data["letter"], $Data["country"] )?>
	<?=OS_TopUser($Data["id"], $Data["player"])?>
	<?=OS_IsUserGameBanned( $Data["banned"], $lang["banned"] )?>	
	<?=OS_IsUserGameAdmin( $Data["admin"], $lang["admin"] )?>
	<?=OS_IsUserGameWarned( $Data["warn"],  $Data["warn_expire"], $lang["warned"] )?>
	<?=OS_IsUserGameSafe( $Data["safelist"], $lang["safelist"] )?>
	<?=OS_IsUserGameLeaver( $Data["leaver"], $lang["leaves"].": ".$Data["leaver"]."<div>".$lang["stayratio"].": ".$Data["stayratio"]."%</div>" )?>
	</td>
	<td width="80" class="font12"><?=$Data["score"]?></td>
    <td width="80" class="font12"><?=$Data["games"]?></td>
	<td width="40" class="font12">
	  <span class="won"><?=$Data["maxstreak"]?></span> / 
	  <span class="lost"><?=$Data["maxlosingstreak"]?></span>
	</td>
	<td width="30" class="font12"><?=$Data["zerodeaths"]?></td>
	<td width="90" class="font12">
	  <span class="won"><?=$Data["wins"]?></span>/
	  <span class="lost"><?=$Data["losses"]?></span>/
	  <span class="draw"><?=$Data["draw"]?></span>
	  </td>
	<td width="60" class="font12"><?=$Data["winslosses"]?>%</td>
    <td width="150" class="font12">
	  <span class="won"><?=($Data["kills"])?></span>/
	  <span class="lost"><?=$Data["deaths"]?></span>/
	  <span class="assists"><?=$Data["assists"]?></span>
	</td>
	<td width="160" class="font12">
	  <span class="won"><?=$Data["creeps"]?></span>/
	  <span class="lost"><?=$Data["denies"]?></span>/
	  <span class="assists"><?=$Data["neutrals"]?></span>
	
	</td>
    <td width="120" class="font12">
	  <span class="won"><?=$Data["towers"]?></span>/
	  <span class="assists"><?=$Data["rax"]?></span>
	</td>
  </tr>
   
  <?php
}
?>	  
    </table>
	<?=OS_ComparePlayers( 'submit' )?>
     </div>
    </div>
   </div>
  </div>
</div>
<?php
	 include('inc/pagination.php');
?>