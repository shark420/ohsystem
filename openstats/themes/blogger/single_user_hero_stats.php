<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>

<?php
if ( isset($ShowUserHeroStats) AND $ShowUserHeroStats == 1 ) {
   ?>
   <table class="Table500px">
     <tr>
       <th class="padLeft" width="70"><?=$lang["favorite_hero"] ?></th>
       <th width="72"><?=$lang["most_kills"] ?></th>
	   <th width="72"><?=$lang["most_deaths"] ?></th>
	   <th width="72"><?=$lang["most_assists"] ?></th>
	   <th width="72"><?=$lang["most_wins"] ?></th>
	 </tr>
	 <tr>
       <td class="padLeft">
	      <a href="<?=OS_HOME?>?hero=<?=$FavoriteHero["original"]?>"><img src="<?=OS_HOME?>img/heroes/<?=$FavoriteHero["original"]?>.gif" alt="*" /></a>
	   </td>
	   <td>
	      <a href="<?=OS_HOME?>?hero=<?=$MostKillsHero["original"]?>"><img src="<?=OS_HOME?>img/heroes/<?=$MostKillsHero["original"]?>.gif" alt="*" /></a>
	   </td>
	   <td>
	      <a href="<?=OS_HOME?>?hero=<?=$MostDeathsHero["original"]?>"><img src="<?=OS_HOME?>img/heroes/<?=$MostDeathsHero["original"]?>.gif" alt="*" /></a>
	   </td>
	   <td>
	      <a href="<?=OS_HOME?>?hero=<?=$MostAssistsHero["original"]?>"><img src="<?=OS_HOME?>img/heroes/<?=$MostAssistsHero["original"]?>.gif" alt="*" /></a>
	   </td>
	   <td>
	      <a href="<?=OS_HOME?>?hero=<?=$MostWinsHero["original"]?>"><img src="<?=OS_HOME?>img/heroes/<?=$MostWinsHero["original"]?>.gif" alt="*" /></a>
	   </td>
	 </tr>
	 
	 <tr>
	    <td class="padLeft">
		<b><?=$lang["played"]?></b>: 
		<div><a href="<?=OS_HOME?>?games&amp;uid=<?=(int)$_GET["u"]?>&amp;h=<?=$FavoriteHero["original"]?>"><?=$FavoriteHero["played"]?></a></div>
		</td>
		
	    <td>
		<b><?=$lang["kills"]?></b>: 
		<div><a href="<?=OS_HOME?>?game=<?=$MostKillsHero["gameid"]?>"><?=$MostKillsHero["kills"]?></a></div>
		</td>
		
		<td><b><?=$lang["deaths"]?></b>:
		<a href="<?=OS_HOME?>?game=<?=$MostDeathsHero["gameid"]?>"><div><?=$MostDeathsHero["deaths"]?></div></a></td>
		
		<td>
		<b><?=$lang["assists"]?></b>: 
		<a href="<?=OS_HOME?>?game=<?=$MostAssistsHero["gameid"]?>"><div><?=$MostAssistsHero["assists"]?></a></div>
		</td>
		
		<td><b><?=$lang["wins"]?></b>: 
		<div><?=$MostWinsHero["wins"]?></div>
		</td>
	 </tr>
   </table>
   <?php
}
?> 