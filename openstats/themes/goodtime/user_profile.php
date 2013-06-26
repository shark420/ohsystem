<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

if ( isset($ProfileData) AND !empty($ProfileData)  ) { 
?>
<div class="entry clearfix" >
   <h2 class="title"><?=$_SESSION["username"]?></h2>
 
<script type="text/javascript">
 setTimeout('RemoveDiv2("message")', 4000);
</script> 

<form action="" method="post" enctype="multipart/form-data">
<table class="Table500px">

    <tr>
	  <td class="padLeft" width="130"><?=$lang["username"]?>:</td>
	  <td>
	  <input disabled class="field" type="text" value="<?=trim($_SESSION["username"])?>" />
<?php if ($ProfileData[0]["user_fbid"] >=1) {  ?>
      <a target="_blank" href="http://www.facebook.com/profile.php?id=<?=$ProfileData[0]["user_fbid"]?>"><img src="<?=OS_HOME?>img/facebook_icon.png" alt="" class="imgvalign" /></a>
<?php } ?>
	  </td>
	</tr>
	
    <tr>
	  <td class="padLeft" width="130"><?=$lang["email"]?>:</td>
	  <td><input disabled class="field" type="text" value="<?=trim($ProfileData[0]["user_email"])?>" /></td>
	</tr>
	
    <tr>
	  <td class="padLeft" width="130"><?=$lang["password"]?>:</td>
	  <td><input name="pw_1" class="field" type="password" value="" /></td>
	</tr>

    <tr>
	  <td class="padLeft" width="130"><?=$lang["confirm_password"]?>:</td>
	  <td><input name="pw_2" class="field" type="password" value="" /></td>
	</tr>	
	
    <tr>
	  <td class="padLeft" width="130"></td>
	  <td align="left">
	  <input style="text-align:left;" name="pw_confirm"  type="checkbox" value="1" /> <?=$lang["change_password"]?>?  
	  </td>
	</tr>
	
    <tr>
	  <td class="padLeft" width="130"><?=$lang["avatar"]?>:</td>
	  <td>
	  <?=ShowUserAvatar(trim($ProfileData[0]["user_avatar"]), 64, 64, "", $lang["remove_avatar"]);?>
	  <?=UploadAvatar($AllowUploadAvatar, $ProfileData[0]["user_avatar"] ) ?>
	  </td>
	</tr>
	
    <tr>
	  <td class="padLeft" width="130"><?=$lang["location"] ?>:</td>
	  <td><input name="location" class="field" type="text" value="<?=trim($ProfileData[0]["user_location"])?>" /></td>
	</tr>
	
    <tr>
	  <td class="padLeft" width="130"><?=$lang["realm"] ?>:</td>
	  <td><input name="realm" class="field" type="text" value="<?=trim($ProfileData[0]["user_realm"])?>" /></td>
	</tr>
	
    <tr>
	  <td class="padLeft" width="130"><?=$lang["website"] ?>:</td>
	  <td><input name="website" class="field" type="text" value="<?=trim($ProfileData[0]["user_website"])?>" /></td>
	</tr>
	
    <tr>
	  <td class="padLeft" width="130"><?=$lang["gender"] ?>:</td>
	  <td>
	   <select name="gender">
	     <option value="0"></option>
<?php if ( $ProfileData[0]["user_gender"] == 1 ) $sel = 'selected="selected"'; else $sel = ''; ?>
		 <option <?=$sel?> value="1"><?=$lang["male"] ?></option>
<?php if ( $ProfileData[0]["user_gender"] == 2 ) $sel = 'selected="selected"'; else $sel = ''; ?>
		 <option <?=$sel?> value="2"><?=$lang["female"] ?></option>
	   </select>
	  </td>
	</tr>
	<?=os_custom_user_fields()?>
    <tr>
	  <td class="padLeft" width="130"></td>
	  <td>
	     <div class="padTop"></div>
	     <input name="change_profile" class="menuButtons" type="submit" value="<?=$lang["submit"] ?>" /> 
<span id="message">
 <?php if (isset( $_GET["updated"])) { ?><?=$lang["profile_changed"]?><?php } 
 else { 
    if ( isset( $_GET["pwchange"] ) AND $_GET["pwchange"] == 1) { ?><?=$lang["password_changed"]?><?php }
	if ( isset( $_GET["pwchange"] ) AND $_GET["pwchange"] == 2) { ?><?=$lang["error_passwords"]?><?php }
	if ( isset( $_GET["pwchange"] ) AND $_GET["pwchange"] == 3) { ?><?=$lang["error_short_pw"]?><?php }
 } ?> 
</span>
		 <div class="padTop"></div>
	  </td>
	</tr>
	
</table>
</form>
</div>
<div style="margin-top: 100px;"></div>
<?php } ?>