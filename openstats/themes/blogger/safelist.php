<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>

<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed">
  <h2><?=$lang["safelist"]?></h2>
  <table>
   <tr>
     <th width="160" class="padLeft"><?=$lang["player"]?></th>
	 <th width="180"><?=$lang["server"] ?></th>
	 <th width="180"><?=$lang["voucher"]?></th>
   </tr>
  <?php
  foreach ($SafelistData as $SafeList) {
  ?>
  <tr class="row">
    <td width="160" class="padLeft">
	  <a href="<?=OS_HOME?>?u=<?=strtolower($SafeList["name"])?>"><?=$SafeList["name"]?></a>
	</td>
	 <td width="180" class="overflow_hidden" ><?=$SafeList["server"]?></td>
	 <td width="180" class="overflow_hidden"><?=$SafeList["voucher"]?></td>
  </tr>
  <?php
  }
  ?>
  </table>
     </div>
    </div>
   </div>
  </div>
</div>
<?php
  $SHOW_TOTALS = 1;
  include('inc/pagination.php');
?>