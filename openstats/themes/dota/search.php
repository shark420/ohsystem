<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

  
if ( isset($SearchData) AND !empty($SearchData) ) {
?>
  <div align="center">
  <h2><?=$lang["search_results"]?> <?=$s?></h2>
  
   <div style="margin-top: 20px;">
	<table>
	<tr>
	 <th width="220" class="padLeft"><?=$lang["player_name"]?></th>
	 <th width="90"><?=$lang["score"]?></th>
	 <th width="90"><?=$lang["games"]?></th>
	 <th width="180"><?=$lang["kda"]?></th>
	</tr>
	<?php
	foreach ($SearchData as $Search) {
	?>
	<tr class="row">
	  <td width="220" class="padLeft font12">
	  <?php if (isset($Search["letter"]) ) { ?>
	  <img  <img <?=ShowToolTip($Search["country"], $website.'img/flags/'.strtoupper($Search["letter"]).".gif", 130, 21, 15)?>  class="imgvalign" width="21" height="15" src="<?=$website?>img/flags/<?=$Search["letter"]?>.gif" alt="" />
	  <?php } ?>
	  <a href="<?=$website?>?u=<?=$Search["id"]?>"><?=$Search["player"]?></a>
	  </td>
	  <td width="90" class="font12"><?=$Search["score"]?></td>
	  <td width="90" class="font12"><?=$Search["games"]?></td>
	  <td width="180" class="font12">
	    <span class="won"><?=($Search["kills"])?></span>/
	    <span class="lost"><?=$Search["deaths"]?></span>/
	    <span class="assists"><?=$Search["assists"]?></span>
	  </td>
	</tr>
	<?php
	}
	?>
	</table>
  </div>
 </div>
	<?php
	 $SHOW_TOTALS = 1;
	 include('inc/pagination.php');
	} else {
	?>
	<h2><?=$lang["user_not_found"]?></h2>
	<?php
	}
?>