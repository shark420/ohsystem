<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div align="center">
  <h2><?=$lang["admins"] ?></h2>
   <table>
    <tr>
       <th width="180" class="alignleft padLeft"><?=$lang["admin"] ?></th>
	   <th width="180" class="alignleft"><?=$lang["server"] ?></th>
    </tr>
   
   <?php foreach($AdminsData as $Admin) { ?>
    <tr>
       <td width="180" class="alignleft padLeft"><a href="<?=$website?>?u=<?=strtolower($Admin["name"])?>"><?=$Admin["name"]?></a></td>
	   <td width="180" class="alignleft"><?=$Admin["server"]?></td>
    </tr>
   <?php } ?>
   </table>
</div>
  <?php
include('inc/pagination.php');
?>