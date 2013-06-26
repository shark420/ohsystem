<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>

<div align="center">
  <h2><?=$lang["safelist"]?></h2>
  <table>
   <tr>
     <th width="160" class="alignleft padLeft"><?=$lang["player"]?></th>
	 <th width="180" class="alignleft"><?=$lang["server"] ?></th>
	 <th width="180" class="alignleft"><?=$lang["voucher"]?></th>
   </tr>
  <?php
  foreach ($SafelistData as $SafeList) {
  ?>
  <tr class="row">
    <td width="160" class="alignleft padLeft">
	  <a href="<?=$website?>?u=<?=strtolower($SafeList["name"])?>"><?=$SafeList["name"]?></a>
	</td>
	 <td width="180" class="alignleft overflow_hidden" ><?=$SafeList["server"]?></td>
	 <td width="180" class="alignleft overflow_hidden"><?=$SafeList["voucher"]?></td>
  </tr>
  <?php
  }
  ?>
  </table>
</div>
<?php
  $SHOW_TOTALS = 1;
  include('inc/pagination.php');
?>