<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>

<div align="center">

<div class="padTop padBottom">

  <form action="" method="get">
    <?=$lang["sortby"]?> <input type="hidden" name="top" />
	<select name="sort">
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "score" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="score"><?=$lang["score"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "games" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="games"><?=$lang["games"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "wins" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="wins"><?=$lang["wins"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "losses" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="losses"><?=$lang["losses"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "draw" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="draw"><?=$lang["draw"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "kills" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="kills"><?=$lang["kills"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "deaths" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="deaths"><?=$lang["deaths"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "assists" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="assists"><?=$lang["assists"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "ck" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="ck"><?=$lang["ck"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "cd" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="cd"><?=$lang["cd"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "nk" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="nk"><?=$lang["nk"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "player_name" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="player_name"><?=$lang["player_name"]?></option>
	</select>
	<input class="menuButtons" type="submit" value="<?=$lang["submit"]?>" />
  </form>
</div>

    <table>
     <tr> 
	   <th width="32" class="padLeft">&nbsp;</th>
	   <th width="160"><?=$lang["player"]?></th>
	   <th width="80"><?=$lang["score"]?></th>
	   <th width="80"><?=$lang["games"]?></th>
	   <th width="90"><?=$lang["wld"]?></th>
	   <th width="90"><?=$lang["wl_percent"]?></th>
	   <th width="120"><?=$lang["kda"]?></th>
	   <th width="120"><?=$lang["cdn"]?></th>
	   <th width="120"><?=$lang["tr"]?></th>
	  </tr>
<?php 
foreach ($TopData as $Data) {
  ?>
  <tr class="row">
    <td width="32" class="padLeft"><?=$Data["counter"]?></td>
    <td width="160" class="font12">
	<?php if (isset($Data["letter"]) AND !empty($Data["letter"]) ) { ?>
	<img <?=ShowToolTip($Data["country"], $website.'img/flags/'.($Data["letter"]).'.gif', 130, 21, 15)?> class="imgvalign" width="21" height="15" src="<?=$website?>img/flags/<?=$Data["letter"]?>.gif" alt="" />
	<?php } ?>
	<a href="<?=$website?>?u=<?=$Data["id"]?>"><?=$Data["player"]?></a>
	</td>
	<td width="80" class="font12"><?=$Data["score"]?></td>
    <td width="80" class="font12"><?=$Data["games"]?></td>
	<td width="90" class="font12">
	  <span class="won"><?=$Data["wins"]?></span>/
	  <span class="lost"><?=$Data["losses"]?></span>/
	  <span class="draw"><?=$Data["draw"]?></span>
	  </td>
	<td width="90" class="font12"><?=$Data["winslosses"]?>%</td>
    <td width="120" class="font12">
	  <span class="won"><?=($Data["kills"])?></span>/
	  <span class="lost"><?=$Data["deaths"]?></span>/
	  <span class="assists"><?=$Data["assists"]?></span>
	</td>
	<td width="120" class="font12">
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
</div>
<?php
	 include('inc/pagination.php');
?>