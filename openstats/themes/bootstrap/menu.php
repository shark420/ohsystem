<?php
if (!isset($website) ) {header('HTTP/1.1 404 Not Found'); die; }

if ( !isset($s) ) $s = $lang["search_players"];
?>
<body>

    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
<div class="nav-collapse collapse">
<ul class="nav">
  <li><a href="<?=OS_HOME?>"><?=$lang["home"]?></a></li>
  <?php if ($TopPage == 1) { ?>
  <li><a href="<?=OS_HOME?>?top"><?=$lang["top"]?></a></li>
  <?php } ?>
  <li><a href="<?=OS_HOME?>?games"><?=$lang["game_archive"]?></a></li>
<?php if ($HeroesPage == 1 AND $ItemsPage == 1 ) { ?>
  <li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="javascript:;"><?=$lang["media"]?> <b class="caret"></b></a>
	 <ul class="dropdown-menu">
	   <?=os_add_menu_misc()?>
	   <?php if ($GuidesPage == 1) { ?>
       <li><a href="<?=OS_HOME?>?guides"><?=$lang["guides"]?></a></li>
	   <?php } ?>
	   <?php if ($HeroesPage == 1) { ?>
       <li><a href="<?=OS_HOME?>?heroes"><?=$lang["heroes"]?></a></li>
	   <?php } ?>
	   <?php if ($HeroVote == 1) { ?>
       <li><a href="<?=OS_HOME?>?vote"><?=$lang["heroes_vote"]?></a></li>
	   <?php } ?>
	   <?php if ($ItemsPage == 1) { ?>
	   <li><a href="<?=OS_HOME?>?items"><?=$lang["items"]?></a></li>
	   <?php } ?>
	 </ul>
  </li>
  <?php } ?>
  
<?php if ($BansPage==1) { ?>
  <li class="dropdown">
  <a class="dropdown-toggle" data-toggle="dropdown" href="<?=OS_HOME?>?bans"><?=$lang["bans"]?> <b class="caret"></b></a>
    <ul class="dropdown-menu">
	   <li><a href="<?=OS_HOME?>?bans"><?=$lang["all_bans"]?></a></li>
<?php if ($BanReports==1) { ?>
	   <li><a href="<?=OS_HOME?>?ban_report"><?=$lang["ban_report"]?></a></li>
 <?php } ?>	  
<?php if ($BanAppeals==1) { ?>
	   <li><a href="<?=OS_HOME?>?ban_appeal"><?=$lang["ban_appeal"]?></a></li>
 <?php } ?>	  
	<?php if ($WarnPage == 1) { ?>
       <li><a href="<?=OS_HOME?>?warn"><?=$lang["warn"]?></a></li>
    <?php } ?>	
	</ul>
  </li>
	<?php if ($SafelistPage == 1) { ?>
    <li><a href="<?=OS_HOME?>?safelist"><?=$lang["safelist"]?></a></li>
	<?php } ?>
 <?php } ?>
   <?php if ($AdminsPage == 1) { ?>
   <li><a href="<?=OS_HOME?>?admins"><?=$lang["admins"]?></a></li>
   <?php } ?>
   
  <?php if ($AboutUs == 1) { ?>
    <li><a href="<?=OS_HOME?>?about_us"><?=$lang["about_us"]?></a></li>
    <?php } ?>	
	
  <?php if ($MemberListPage == 1) { ?>
    <li><a href="<?=OS_HOME?>?members"><?=$lang["members"]?></a></li>
    <?php } ?>	
   
   <?php if (!is_logged() AND $UserRegistration == 1) { ?>
    <li><a href="<?=OS_HOME?>?login"><?=$lang["login_register"]?></a></li>
   <?php } ?>
   
   <?php if (is_logged() ) { ?>
   <li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="<?=OS_HOME?>?profile"><b><?=substr($_SESSION["username"],0,30)?></b>  <b class="caret"></b></a>
      <ul class="dropdown-menu">
	    <?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
	    <li><a href="<?=OS_HOME?>adm/"><b><?=$lang["admin_panel"]?></b></a></li>
	    <?php } ?>
		 <li><a href="<?=OS_HOME?>?profile"><?=$lang["profile"]?></a></li>
<?php if (isset($_SESSION["phpbb"]) ) { ?>
       <li><a href="<?=OS_HOME?>?logout&amp;sid=<?=$_SESSION["sid"]?>"><?=$lang["logout"]?></a></li>
<?php } else { ?>
		 <li><a href="<?=OS_HOME?>?logout"><?=$lang["logout"]?></a></li>
<?php } ?>
	  </ul>
   </li>
   <?php } ?>
   
<form class="navbar-form pull-right" method="get">
    <input class="span2" type="text" placeholder="<?=$s?>" id="s" name="search" />
    <button type="submit" class="btn"><?=$lang["search"]?></button>
</form>
</ul>
  </div>
  </div>
 </div>
</div>

<div id="wrap">
<div class="container">