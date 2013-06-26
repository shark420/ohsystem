<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div align="center">
  <h2><?=$lang["admins"] ?></h2>
   <table>
    <tr>
       <th width="180" class="padLeft"><?=$lang["admin"] ?></th>
	   <th><?=$lang["server"] ?></th>
    </tr>
   
   <?php foreach($AdminsData as $Admin) { ?>
    <tr>
       <td width="180" class="padLeft"><a href="<?=$website?>?u=<?=strtolower($Admin["name"])?>"><?=$Admin["name"]?></a></td>
	   <td><?=$Admin["server"]?></td>
    </tr>
   <?php } ?>
   </table>
</div>
  <?php
include('inc/pagination.php');
?>