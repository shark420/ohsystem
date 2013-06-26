<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div align="center">
	 
	 <?php if (isset($ListHeroesData) AND !empty($ListHeroesData) ) { ?>
	  <form action="" method="get">
	  <select name="guides">
	   <option value=""><?=$lang["all_guides"]?></option>
	  <?php foreach ( $ListHeroesData as $HeroesData ) { ?>
	    <option <?=$HeroesData["selected"]?> value="<?=$HeroesData["original"]?>"><?=$HeroesData["description"]?></option>
	  <?php } ?>
	  </select>
	  <input type="submit" value="<?=$lang["choose"]?>" class="menuButtons" />
	  </form>
	 <?php } ?>
	 
	 <table>
      <tr>
	   <th width="72" class="padLeft"><?=$lang["guides"]?></th>
	   <th></th>
	   <th></th>
	  </tr>
	  <?php foreach ( $GuidesData as $Guide ) { ?>
	  <tr>
	    <td class="padLeft">
		   <a href="<?=$website?>?hero=<?=$Guide["original"]?>"><img width="64" height="64" src="<?=$website?>img/heroes/<?=$Guide["original"]?>.gif" alt="<?=$Guide["original"]?>" /></a>
		</td>
		<td width="180"><?=$Guide["description"]?></td>
		<td><a href="<?=$Guide["link"]?>" target="_blank"><?=$Guide["title"]?></a></td>
	  </tr>
	  <?php } ?>
	  </table>
<?php
include('inc/pagination.php');
?>  
  
</div>