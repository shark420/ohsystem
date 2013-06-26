<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed">
  <h2><?=$lang["admins"] ?></h2>
   <table class="table table-condensed table-bordered">
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
    </div>
   </div>
  </div>
</div>
  <?php
include('inc/pagination.php');
?>