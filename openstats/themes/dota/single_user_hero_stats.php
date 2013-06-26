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
	      <a href="<?=$website?>?hero=<?=$FavoriteHero["original"]?>"><img src="<?=$website?>img/heroes/<?=$FavoriteHero["original"]?>.gif" alt="*" /></a>
	   </td>
	   <td>
	      <a href="<?=$website?>?hero=<?=$MostKillsHero["original"]?>"><img src="<?=$website?>img/heroes/<?=$MostKillsHero["original"]?>.gif" alt="*" /></a>
	   </td>
	   <td>
	      <a href="<?=$website?>?hero=<?=$MostDeathsHero["original"]?>"><img src="<?=$website?>img/heroes/<?=$MostDeathsHero["original"]?>.gif" alt="*" /></a>
	   </td>
	   <td>
	      <a href="<?=$website?>?hero=<?=$MostAssistsHero["original"]?>"><img src="<?=$website?>img/heroes/<?=$MostAssistsHero["original"]?>.gif" alt="*" /></a>
	   </td>
	   <td>
	      <a href="<?=$website?>?hero=<?=$MostWinsHero["original"]?>"><img src="<?=$website?>img/heroes/<?=$MostWinsHero["original"]?>.gif" alt="*" /></a>
	   </td>
	 </tr>
	 
	 <tr>
	    <td class="padLeft">
		<b><?=$lang["played"]?></b>: 
		<div><a href="<?=$website?>?games&amp;uid=<?=(int)$_GET["u"]?>&amp;h=<?=$FavoriteHero["original"]?>"><?=$FavoriteHero["played"]?></a></div>
		</td>
		
	    <td>
		<b><?=$lang["kills"]?></b>: 
		<div><a href="<?=$website?>?game=<?=$MostKillsHero["gameid"]?>"><?=$MostKillsHero["kills"]?></a></div>
		</td>
		
		<td><b><?=$lang["deaths"]?></b>:
		<a href="<?=$website?>?game=<?=$MostDeathsHero["gameid"]?>"><div><?=$MostDeathsHero["deaths"]?></div></a></td>
		
		<td>
		<b><?=$lang["assists"]?></b>: 
		<a href="<?=$website?>?game=<?=$MostAssistsHero["gameid"]?>"><div><?=$MostAssistsHero["assists"]?></a></div>
		</td>
		
		<td><b><?=$lang["wins"]?></b>: 
		<div><?=$MostWinsHero["wins"]?></div>
		</td>
	 </tr>
   </table>
   <?php
}
?> 