<?php
if (!isset($website) ) {header('HTTP/1.1 404 Not Found'); die; }

if ( !isset($s) ) $s = $lang["search_players"];
?>
<body>

<div class="blogoutter-wrapper">
<div class="bloginner-wrapper">
<div class="ct-wrapper">
<div class="header-wrapper">

<div class="header section" id="header">
 <div class="widget Header" id="Header1">
  <div id="header-inner">
    <div class="titlewrapper">
     <h1 class="title"><a href="<?=OS_HOME?>"><?=$DefaultHomeTitle?></a></h1>
    </div>
    <div class="descriptionwrapper">
    <p class="description"><span><?=$DefaultHomeDescription?></span></p>
    </div>
  </div>
  </div>
</div>

<div class="header-right-wrap section" id="header-right-wrap">
  <div class="widget HTML' id='HTML22">
    <div id="header-right">
    <div id="search">
     <form action="" id="search-form" method="get">
     <div><input id="s" name="search" onblur='if (this.value == "") {this.value = "<?=$s?>";}' onfocus='if (this.value == "<?=$s?>") {this.value = ""}' type="text" value='<?=$s?>'/>
     <input id="sbtn" type="submit" value="" /></div>
     </form>
    </div>
    </div>
  </div>
</div>

</div>
</div>
</div>

<div class="navigation section" id="navigation">
 <div class="widget HTML" id="HTML99">
  <div class="main-nav-main">
   <div class="ct-wrapper">
   
<ul class="sf-menu">
  <li><a href="<?=OS_HOME?>"><?=$lang["home"]?></a></li>
  <?php if ($TopPage == 1) { ?>
  <li><a href="<?=OS_HOME?>?top"><?=$lang["top"]?></a></li>
  <?php } ?>
  <li><a href="<?=OS_HOME?>?games"><?=$lang["game_archive"]?></a></li>
<?php if ($HeroesPage == 1 AND $ItemsPage == 1 ) { ?>
  <li><a href="javascript:;"><?=$lang["media"]?></a>
	 <ul>
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
  <li>
  <a href="<?=OS_HOME?>?bans"><?=$lang["bans"]?></a>
    <ul>
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
   <li>
    <a href="<?=OS_HOME?>?profile"><b><?=substr($_SESSION["username"],0,30)?></b></a>
      <ul>
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
</ul>
  </div>
 </div>

<?php os_top_menu() ?>

 </div>
</div>