<?php
if (!isset($website) ) {header('HTTP/1.1 404 Not Found'); die; }

if ( !isset($s) ) $s = $lang["search_players"];
?>
<body id="home" class="home blog chrome et_includes_sidebar">
	<div id="header-top" class="clearfix">
		<div class="container clearfix">
			<!-- Start Logo -->					
			<a href="<?=OS_HOME?>"><img src="<?=OS_HOME?>themes/<?=OS_THEMES_DIR?>/images/logo.png" alt="Logo" id="logo"/></a>
			<p id="slogan"><?=$DefaultHomeDescription?></p>
			<!-- End Logo -->
			
<?=os_top_menu()?>
<?=os_main_menu()?>

<div id="cat-nav" class="clearfix">	
	<div id="cat-nav-left"> </div>
	 <div id="cat-nav-content"> 
					
	<ul class="superfish nav clearfix">	
	  <li class="cat-item cat-item-1"><a href="<?=OS_HOME?>"><?=$lang["home"]?></a></li>
	   <?php if ($TopPage == 1) { ?>
       <li class="cat-item cat-item-2"><a href="<?=OS_HOME?>?top"><?=$lang["top"]?></a></li>
       <?php } ?>
	   <li class="cat-item cat-item-3"><a href="<?=OS_HOME?>?games"><?=$lang["game_archive"]?></a></li>
	
	<?php if ($HeroesPage == 1 AND $ItemsPage == 1 ) { ?>
      <li class="cat-item cat-item-4"><a href="javascript:;"><?=$lang["media"]?></a>
	    <ul class='children'>
		
		  <?=os_add_menu_misc()?>
		  
	      <?php if ($GuidesPage == 1) { ?>
          <li class="cat-item cat-item-5"><a href="<?=OS_HOME?>?guides"><?=$lang["guides"]?></a></li>
	      <?php } ?>
	      <?php if ($HeroesPage == 1) { ?>
          <li class="cat-item cat-item-6"><a href="<?=OS_HOME?>?heroes"><?=$lang["heroes"]?></a></li>
	      <?php } ?>
	      <?php if ($HeroVote == 1) { ?>
          <li class="cat-item cat-item-7"><a href="<?=OS_HOME?>?vote"><?=$lang["heroes_vote"]?></a></li>
	      <?php } ?>
	      <?php if ($ItemsPage == 1) { ?>
	      <li class="cat-item cat-item-8"><a href="<?=OS_HOME?>?items"><?=$lang["items"]?></a></li>
	      <?php }  ?>
	    </ul>
      </li>
	  <?php } ?>
	  
     <?php if ($BansPage==1) { ?>
     <li class="cat-item cat-item-8"><a href="<?=OS_HOME?>?bans"><?=$lang["bans"]?></a>
      <ul class='children'>
	     <li class="cat-item cat-item-9"><a href="<?=OS_HOME?>?bans"><?=$lang["all_bans"]?></a></li>
         <?php if ($BanReports==1) { ?>
	     <li class="cat-item cat-item-10"><a href="<?=OS_HOME?>?ban_report"><?=$lang["ban_report"]?></a></li>
        <?php } ?>	  
        <?php if ($BanAppeals==1) { ?>
	    <li class="cat-item cat-item-11"><a href="<?=OS_HOME?>?ban_appeal"><?=$lang["ban_appeal"]?></a></li>
        <?php } ?>	  
	    <?php if ($WarnPage == 1) { ?>
        <li class="cat-item cat-item-12"><a href="<?=OS_HOME?>?warn"><?=$lang["warn"]?></a></li>
      <?php } ?>	
	  </ul>
     </li>
	 <?php if ($SafelistPage == 1) { ?>
     <li class="cat-item cat-item-13"><a href="<?=OS_HOME?>?safelist"><?=$lang["safelist"]?></a></li>
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
      <ul class='children'>
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
					<!-- Start Searchbox -->
	<div id="search-form">
	   <form method="get" id="searchform1" action="">

		<div><input id="searchinput" name="search" onblur='if (this.value == "") {this.value = "<?=$s?>";}' onfocus='if (this.value == "<?=$s?>") {this.value = ""}' type="text" value='<?=$s?>'/></div>
		
		<input type="image" src="<?=OS_HOME?>themes/<?=OS_THEMES_DIR?>/images/search_btn.png" id="searchsubmit" />
		</form>
	</div>
				<!-- End Searchbox -->	
				</div> <!-- end #cat-nav-content -->
				<div id="cat-nav-right"> </div>
			</div>	<!-- end #cat-nav -->	
		</div> 	<!-- end .container -->
	</div> 	<!-- end #header-top -->

 <div id="content">
  <div class="container">
   <div id="main-content-wrap">
	<div id="main-content" class="clearfix">
	 <div id="recent-posts" class="clearfix">