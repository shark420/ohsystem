<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div class="entry clearfix" >
  <h2 class="title"><?=$lang["admins"] ?></h2>
   <table>
    <tr>
       <th width="180" class="padLeft"><?=$lang["admin"] ?></th>
	   <th><?=$lang["server"] ?></th>
    </tr>
   
   <?php foreach($AdminsData as $Admin) { ?>
    <tr>
       <td width="180" class="padLeft"><a href="<?=OS_HOME?>?u=<?=strtolower($Admin["name"])?>"><?=$Admin["name"]?></a></td>
	   <td><?=$Admin["server"]?></td>
    </tr>
   <?php } ?>
   </table>
</div>
  <?php
include('inc/pagination.php');
?>
<div style="height:180px;">&nbsp;</div>