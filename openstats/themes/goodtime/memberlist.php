<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div class="entry clearfix" >
	 
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
	 <td width="200" class="font13">
	 <?php if (isset($User["letter"]) AND !empty($User["letter"]) ) { ?>
	 <img <?=ShowToolTip($User["country"], OS_HOME.'img/flags/'.($User["letter"]).'.gif', 130, 21, 15)?> class="imgvalign" width="21" height="15" src="<?=OS_HOME?>img/flags/<?=$User["letter"]?>.gif" alt="" />
	 <?php } ?>
	   <b><?=$User["user_name"]?></b>
	     <?php if ( OS_is_admin() ) { ?>
	     <div><?=$User["user_email"]?></div><?=EditUserLink($User["id"])?>
	     <?php } ?>
	 </td>
	 <td width="160"><?=date($DateFormat, $User["user_joined"])?></td>
	 <td>
	    <div><b><?=$lang["location"]?></b>: <?=$User["user_location"]?></div>
		<div><b><?=$lang["realm"]?></b>:    <?=$User["user_realm"]?></div>
		<div><b><?=$lang["website"]?></b>:  <?=AutoLinkShort($User["user_website"], 'target="_blank"')?></div>
		<div><b><?=$lang["gender"]?></b>:   <?=UserGender($User["user_gender"])?></div>
		<?=os_display_custom_fields()?>
	 </td>
	</tr>
<?php } ?>
  </table>
  
<?php
include('inc/pagination.php');
?> 
</div>