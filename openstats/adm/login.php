<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div id="wrapper">
<div id="logo">
  <h1>Login</h1>
</div>  

  <div class="main-nav-main" style="position:fixed; top: 0px; left: 0px;">
   <div class="ct-wrapper">
<ul class="dd_menu">
  <li><a class="menuButtons" href="<?=$website?>"><?=$lang["home"]?></a></li>
  <?php if ($TopPage == 1) { ?>
  <li><a class="menuButtons" href="<?=$website?>?top"><?=$lang["top"]?></a></li>
  <?php } ?>
  <li><a class="menuButtons" href="<?=$website?>?games"><?=$lang["game_archive"]?></a></li>
   <?php if ($HeroesPage == 1 AND $ItemsPage == 1 ) { ?>
  <li><a class="menuButtons" href="javascript:;"><?=$lang["media"]?></a>
	 <ul>
	   <?php if ($HeroesPage == 1) { ?>
       <li><a href="<?=$website?>?heroes"><?=$lang["heroes"]?></a></li>
	   <?php } ?>
	   <?php if ($ItemsPage == 1) { ?>
	   <li><a href="<?=$website?>?items"><?=$lang["items"]?></a></li>
	   <?php } } ?>
	 </ul>
  </li>
<?php if ($BansPage==1) { ?>
  <li>
  <a class="menuButtons" href="<?=$website?>?bans"><?=$lang["bans"]?></a>
    <ul>
	   <li><a href="<?=$website?>?bans"><?=$lang["all_bans"]?></a></li>
<?php if ($BanReports==1) { ?>
	   <li><a href="<?=$website?>?ban_report"><?=$lang["ban_report"]?></a></li>
 <?php } ?>	  
<?php if ($BanAppeals==1) { ?>
	   <li><a href="<?=$website?>?ban_appeal"><?=$lang["ban_appeal"]?></a></li>
 <?php } ?>	  
	<?php if ($WarnPage == 1) { ?>
       <li><a href="<?=$website?>?warn"><?=$lang["warn"]?></a></li>
    <?php } ?>	
	</ul>
  </li>
	<?php if ($SafelistPage == 1) { ?>
    <li><a class="menuButtons" href="<?=$website?>?safelist"><?=$lang["safelist"]?></a></li>
	<?php } ?>
 <?php } ?>
   <?php if ($AdminsPage == 1) { ?>
   <li><a class="menuButtons" href="<?=$website?>?admins"><?=$lang["admins"]?></a></li>
   <?php } ?>
   
  <?php if ($AboutUs == 1) { ?>
    <li><a class="menuButtons" href="<?=$website?>?about_us"><?=$lang["about_us"]?></a></li>
    <?php } ?>	
   
   <?php if (!is_logged() ) { ?>
    <li><a class="menuButtons" href="<?=$website?>?login"><?=$lang["login_register"]?></a></li>
   <?php } ?>
   <?php if (is_logged() ) { ?>
   <li>
    <a class="menuButtons" href="<?=$website?>?profile"><b><?=substr($_SESSION["username"],0,30)?></b></a>
      <ul>
	    <?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
	    <li><a href="<?=$website?>adm/"><b><?=$lang["admin_panel"]?></b></a></li>
	    <?php } ?>
		 <li><a href="<?=$website?>?profile"><?=$lang["profile"]?></a></li>
		 <li><a href="<?=$website?>?logout"><?=$lang["logout"]?></a> </li>
	  </ul>
   </li>
   <?php } ?>
</ul> 
</div> 
</div>



<div align="center" style="background-color: #fff; width: 960px; margin: 0 auto; padding-top: 18px; border: 10px solid #2B0202; border-radius: 10px;">

<?php if (isset($errors) AND !empty($errors) ) { ?>
<div style="color: red;"><?=$errors?></div>
<?php } ?>
     <form action="" method="post">
	 <table>
	 <tr>
	   <th width="100"></th>
	   <th width="300" class="alignleft">Please login to continue:</th>
	  </tr>
	  
	  <tr class="row">
	   <td width="100" class="alignleft padLeft">E-mail:</td>
	   <td width="300"> <input type="text" value="" name="login_email" /></td>
	  </tr>
	  
	  <tr class="row">
	   <td width="100" class="alignleft padLeft">Password:</td>
	   <td width="300"> <input type="password" value="" name="login_password" /></td>
	  </tr>
	  
	  <tr class="row">
	   <td width="100" class="alignleft padLeft"></td>
	   <td width="300"> <input class="menuButtons" type="submit" value="Login" name="login_" /></td>
	  </tr>
	 
	 </table>
	
	 </form>
</div>
<div style="margin-bottom: 60px;">&nbsp; </div>
<?php
//var_dump($_SESSION);
include('../themes/'.$DefaultStyle.'/footer.php');
?>
</div>