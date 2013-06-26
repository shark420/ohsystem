<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div class="entry clearfix" >
  
  <!--<h2><?=$lang["login"]?></h2>-->
  
  <?php if (isset($errors) AND !empty($errors) ) { ?>
  <div><?=$errors?></div>
  <?php } ?>
  
  <?php 
  //FACEBOOK LOGIN BUTTON
  if ($FBLogin == 1) { ?>
<a href="<?php echo OS_HOME; ?>?fb"><img src="<?=OS_HOME?>img/fb_connect_small.png" width="154" height="22" alt="FB CONNECT" /></a>
<?php } ?>

  <form action="" method="post">
  <div style="float:left; width: 320px;">
  <table class="Table500px">
   <tr><th width="100"></th><th class="padLeft"><?=$lang["login"]?></th></tr>
   <tr class="row">
     <td width="100" class="padLeft"><?=$lang["email"]?>: </td>
     <td class="padLeft"><input class="field" type="text" value="" name="login_email" /></td>
   </tr>
   <tr class="row">
     <td width="100" class="padLeft"><?=$lang["password"]?>: </td>
     <td class="padLeft"><input class="field" type="password" value="" name="login_pw" /></td>
  </tr>
  <?=os_login_fields()?>
   <tr class="row">
     <td width="100" class="padLeft"></td>
     <td class="padLeft">
       <div class="padTop"></div>
       <input type="submit" value="<?=$lang["login"]?>" class="menuButtons" name="login_" />
       <div class="padBottom"></div>
      </td>
  </tr>
  </table>
  </form>
  </div>


  
  <?php if (isset($registration_errors) AND !empty($registration_errors) ) { ?>
  <div><?=$registration_errors?></div>
  <?php } ?>
  
  <?php
  if ( $UserActivation != 2 ) {
  ?>
  <div style="float:left; width: 350px; margin-left:50px;">
<form action="" method="post">
  <table class="Table500px">
    <tr><th width="140"></th><th class="padLeft"><?=$lang["register"]?></th></tr>
    <tr class="row">
      <td width="140" class="padLeft"><?=$lang["username"]?>: </td>
      <td class="padLeft"><input class="field" type="text" value="" name="reg_un" /></td>
   </tr>
   <tr class="row">
      <td width="140" class="padLeft"><?=$lang["email"]?>: </td>
      <td class="padLeft"><input class="field" type="text" value="" name="reg_email" /></td>
   </tr>
   <tr class="row">
      <td width="140" class="padLeft"><?=$lang["password"]?>: </td>
      <td class="padLeft"><input class="field" type="password" value="" name="reg_pw" /></td>
  </tr>
  <tr class="row">
      <td width="140" class="padLeft"><?=$lang["confirm_password"]?>: </td>
     <td class="padLeft"><input class="field" type="password" value="" name="reg_pw2" /></td>
  </tr>
  <?=os_registration_fields()?>
  <tr class="row">
     <td width="100" class="padLeft"></td>
     <td class="padLeft">
       <div class="padTop"></div>
       <input type="submit" value="<?=$lang["register"]?>" class="menuButtons" name="register_" />
       <div class="padBottom"></div>
      </td>
  </tr>
  </table>
</form>
</div>
 <?php } else { ?>
 <div style="height:260px;"></div>
 <?php } ?>
  <div style="height:800px;"></div>
</div>