<?php
if (!isset($website) ) {header('HTTP/1.1 404 Not Found'); die; }

if ( !isset($s) ) $s="Search players...";
?>
<body>
<div id="wrapper">
<div id="logo">
  <h1><?=$LogoText?></h1>
  <div class="search_top">
  <form action="" method="get">
   <input type="text" value="<?=$s?>" style="height: 26px;" onblur= "if (this.value == '')  {this.value = '<?=$s?>';}" onfocus="if (this.value == '<?=$s?>') {this.value = '';}" name="search" />
   <input type="submit" value="<?=$lang["search"]?>" class="menuButtons" />
   </form>
  </div>
</div> 




<div class="mainmenu">
<ul class="dd_menu">
  <li><a class="menuButtons" href="<?=$website?>"><?=$lang["home"]?></a></li>
  <?php if ($TopPage == 1) { ?>
  <li><a class="menuButtons" href="<?=$website?>?top"><?=$lang["top"]?></a></li>
  <?php } ?>
  <li><a class="menuButtons" href="<?=$website?>?games"><?=$lang["game_archive"]?></a></li>
   <?php if ($HeroesPage == 1 AND $ItemsPage == 1 ) { ?>
  <li><a class="menuButtons" href="javascript:;"><?=$lang["media"]?></a>
	 <ul>
	 
	   <?=os_add_menu_misc()?>
	   
	   <?php if ($GuidesPage == 1) { ?>
       <li><a href="<?=$website?>?guides"><?=$lang["guides"]?></a></li>
	   <?php } ?>
	   <?php if ($HeroesPage == 1) { ?>
       <li><a href="<?=$website?>?heroes"><?=$lang["heroes"]?></a></li>
	   <?php } ?>
	   <?php if ($HeroVote == 1) { ?>
       <li><a href="<?=$website?>?vote"><?=$lang["heroes_vote"]?></a></li>
	   <?php } ?>
	   <?php if ($ItemsPage == 1) { ?>
	   <li><a href="<?=$website?>?items"><?=$lang["items"]?></a></li>
	   <?php } ?>
	 </ul>
  </li>
  <?php } ?>
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
	
  <?php if ($MemberListPage == 1) { ?>
    <li><a class="menuButtons" href="<?=$website?>?members"><?=$lang["members"]?></a></li>
    <?php } ?>	
   
   <?php if (!is_logged() AND $UserRegistration == 1) { ?>
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
<?php if (isset($_SESSION["phpbb"]) ) { ?>
       <li><a href="<?=$website?>?logout&amp;sid=<?=$_SESSION["sid"]?>"><?=$lang["logout"]?></a></li>
<?php } else { ?>
		 <li><a href="<?=$website?>?logout"><?=$lang["logout"]?></a></li>
<?php } ?>
	  </ul>
   </li>
   <?php } ?>
</ul> 
</div> 

<div class="mainmenu" style="display: none"><!-- //Old menu //-->
    <a class="menuButtons" href="<?=$website?>"><?=$lang["home"]?></a>  
	<?php if ($TopPage == 1) { ?>
    <a class="menuButtons" href="<?=$website?>?top"><?=$lang["top"]?></a> 
	<?php } ?>
    <a class="menuButtons" href="<?=$website?>?games"><?=$lang["game_archive"]?></a>
	<?php if ($HeroesPage == 1) { ?>
    <a class="menuButtons" href="<?=$website?>?heroes"><?=$lang["heroes"]?></a>
	<?php } ?>
	<?php if ($ItemsPage == 1) { ?>
    <a class="menuButtons" href="<?=$website?>?items"><?=$lang["items"]?></a> 
	<?php } ?>
	<?php if ($BansPage == 1) { ?>
    <a class="menuButtons" href="<?=$website?>?bans"><?=$lang["bans"]?></a> 
	<?php } ?>
	<?php if ($WarnPage == 1) { ?>
    <a class="menuButtons" href="<?=$website?>?warn"><?=$lang["warn"]?></a> 
    <?php } ?>	
	<?php if ($AdminsPage == 1) { ?>
    <a class="menuButtons" href="<?=$website?>?admins"><?=$lang["admins"]?></a>
    <?php } ?>		
	<?php if ($SafelistPage == 1) { ?>
    <a class="menuButtons" href="<?=$website?>?safelist"><?=$lang["safelist"]?></a> 
	<?php } ?>
	<?php if ($AboutUs == 1) { ?>
    <a class="menuButtons" href="<?=$website?>?about_us"><?=$lang["about_us"]?></a>
    <?php } ?>	
	<?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
	<a class="menuButtons" href="<?=$website?>adm/"><b><?=$lang["admin_panel"]?></b></a> 
	<?php } ?>
<?php if (is_logged()) { ?>
	<a class="menuButtons" href="<?=$website?>?profile"><b><?=$_SESSION["username"]?></b></a> 
	<a class="menuButtons" href="<?=$website?>?logout"><?=$lang["logout"]?></a> 
<?php } ?>
<?php if (!is_logged()) { ?>
    <a class="menuButtons" href="<?=$website?>?login"><?=$lang["login_register"]?></a> 
<?php } ?>
</div>