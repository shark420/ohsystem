<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div align="center">
	 
     <h2><?=$lang["members"]?></h2>
	 
  <table>
    <tr>
	 <th width="72"   class="padLeft"><?=$lang["avatar"] ?></th>
	 <th width="200"><?=$lang["username"] ?></th>
	 <th width="160"><?=$lang["joined"]?></th>
	 <th><?=$lang["user_info"]?></th>
	</tr>
<?php foreach ( $MembersData as $User ) { ?>
    <tr class="row">
	 <td width="72"  class="padLeft font13"><?=ShowUserAvatar ($User["user_avatar"], 64, 64, "", 0, 1) ?></td>
	 <td width="200" class="font13"><?=$User["user_name"]?></td>
	 <td width="160"><?=date($DateFormat, $User["user_joined"])?></td>
	 <td>
	    <div><?=$User["user_location"]?></div>
		<div><?=$User["user_realm"]?></div>
		<div><?=AutoLinkShort($User["user_website"], 'target="_blank"')?></div>
		<div><?=UserGender($User["user_gender"])?></div>
		<?=os_display_custom_fields()?>
	 </td>
	</tr>
<?php } ?>
  </table>
  
<?php
include('inc/pagination.php');
?> 
</div>