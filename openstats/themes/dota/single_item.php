<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

?>
<div align="center">
<?php
foreach ( $ItemData as $Item) {
?>
  <table>
    <tr>
	  <th class="padLeft"><?=$Item["shortname"]?></th>
	</tr>
  </table>
  
  <table>
   <tr>
     <td class="padLeft" width="100"><img src="<?=$website?>img/items/<?=$Item["icon"]?>" alt="" class="imgvalign" /> </td>
	 <td>
	 <?=$Item["shortname"]?>
	   <?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
	   | <a href="<?=$website?>adm/?items&amp;edit=<?=$Item["itemid"]?>">[edit]</a>
	   <?php } ?>
	 </td>
   </tr>
      <tr>
     <td class="padLeft" width="100"></td>
	 <td><?=$Item["item_info"]?></td>
   </tr>
  </table>
<?php } 
//include('inc/get_heroes_by_item.php');  
?>
  
</div>