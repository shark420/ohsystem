<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

  if ( !isset($search_heroes) ) $search_heroes=$lang["search_heroes"] ;
?>

<div align="center">
  
<div style="margin-bottom: 12px;">
  <form action="" method="get">
  <input type="hidden" name="heroes" />
   <input 
   type="text" 
   value="<?=$search_heroes?>" 
   style="height: 26px;" 
   onblur= "if (this.value == '')  {this.value = '<?=$search_heroes?>';}"
   onfocus="if (this.value == '<?=$search_heroes?>') {this.value = '';}" 
   name="search_heroes"
   />
   <input type="submit" value="Search" class="menuButtons" />
   </form>
</div>
  
  <table>
    <tr>
	 <th width="72" class="padLeft"><?=$lang["hero"]?></th>
	 <th></th>
	</tr>
<?php foreach ( $HeroesData as $Hero ) { ?>
    <tr class="row">
	 <td width="72" class="padLeft">
	   <a href="<?=$website?>?hero=<?=$Hero["original"]?>"><img width="64" height="64" src="<?=$website?>img/heroes/<?=$Hero["original"]?>.gif" alt="<?=$Hero["original"]?>" /></a>
	 </td>
	 <td><a href="<?=$website?>?hero=<?=$Hero["original"]?>"><?=$Hero["description"]?></a></td>
	</tr>
<?php } ?>
  </table>
  
<?php
include('inc/pagination.php');
?>  
  
</div>
