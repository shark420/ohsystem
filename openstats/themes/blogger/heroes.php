<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

  if ( !isset($search_heroes) ) $search_heroes=$lang["search_heroes"] ;
?>

<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed">
  
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
	 <th width="200"></th>
	 <th></th>
	</tr>
<?php foreach ( $HeroesData as $Hero ) { ?>
    <tr class="row">
	 <td width="72" class="padLeft">
	   <a href="<?=OS_HOME?>?hero=<?=$Hero["original"]?>"><img width="64" height="64" src="<?=OS_HOME?>img/heroes/<?=$Hero["original"]?>.gif" alt="<?=$Hero["original"]?>" /></a>
	 </td>
	 <td><a href="<?=OS_HOME?>?hero=<?=$Hero["original"]?>"><?=$Hero["description"]?></a></td>
	 <td><?php if ($GuidesPage == 1) { ?>
	 <a href="<?=OS_HOME?>?guides=<?=$Hero["original"]?>"><?=$lang["guides"]?></a>
	 <?php } ?></td>
	</tr>
<?php } ?>
  </table>
  
<?php
include('inc/pagination.php');
?>  
  
     </div>
    </div>
   </div>
  </div>
</div>
