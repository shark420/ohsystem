<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }


$DefaultHomeTitle = $HomeTitle;
$DefaultHomeDescription = $HomeDesc;

foreach($_POST as $key => $value) {
    $_POST[$key] = FilterData($value);
}

foreach($_GET as $key => $value) {
    $_GET[$key] = FilterData($value);
}

include(OS_PAGE_PATH."registration_login_page.php"); 
include(OS_PAGE_PATH."add_comment_page.php"); 

	 //If "u" is not a number, found in the database this user (if exists)
	 if ( isset($_GET["u"]) AND !is_numeric( $_GET["u"]) ) { 
	    $uid = OS_StrToUTF8( trim($_GET["u"]) );
		
		$sth = $db->prepare("SELECT *
	    FROM ".OSDB_STATS." as s WHERE s.player = :player LIMIT 1");
		$sth->bindValue(':player', $uid, PDO::PARAM_STR); 
		$result = $sth->execute();
		
		if ( $sth->rowCount()>=1 ) {
		   $row = $sth->fetch(PDO::FETCH_ASSOC);
		   header( 'location: '.OS_HOME.'?u='.$row["id"] ); die; 
		}
	 }

  
  if ( isset($_GET["games"]) OR isset($_GET["u"]) )            include(OS_PAGE_PATH."games_page.php"); 
  if ( isset($_GET["game"]) AND is_numeric($_GET["game"]) )    include(OS_PAGE_PATH."single_games_page.php"); else
  if ( isset( $_GET["top"]) AND $TopPage==1)                   include(OS_PAGE_PATH."top_page.php"); else
  if ( isset( $_GET["u"]) )                                    include(OS_PAGE_PATH."user_stats_page.php"); else
  if ( isset($_GET["search"]) AND strlen($_GET["search"])>=2 ) include(OS_PAGE_PATH."search_page.php"); else
  if ( isset($_GET["bans"]) AND $BansPage == 1)                include(OS_PAGE_PATH."bans_page.php"); else
  if ( isset( $_GET["admins"]) AND $AdminsPage == 1 )          include(OS_PAGE_PATH."admins_page.php"); else
  if ( isset( $_GET["warn"]) AND $WarnPage == 1 )              include(OS_PAGE_PATH."warn_page.php"); else
  if ( isset($_GET["safelist"]) AND $SafelistPage == 1)        include(OS_PAGE_PATH."safelist_page.php"); else
  if ( isset($_GET["heroes"]) AND $HeroesPage == 1)            include(OS_PAGE_PATH."heroes_page.php"); else
  if ( isset($_GET["hero"]) AND $HeroesPage == 1)              include(OS_PAGE_PATH."single_hero_page.php"); else
  if ( isset($_GET["guides"]) AND $GuidesPage == 1)            include(OS_PAGE_PATH."guides_page.php"); else
  if ( isset($_GET["members"]) AND $MemberListPage == 1 )      include(OS_PAGE_PATH."memberlist_page.php"); else
  if ( isset($_GET["items"]) AND $ItemsPage == 1)              include(OS_PAGE_PATH."items_page.php"); else
  if ( isset($_GET["item"]) AND $ItemsPage == 1)               include(OS_PAGE_PATH."single_item_page.php"); else

  if (  OS_is_home_page())                                     include(OS_PAGE_PATH."home_page.php"); else
  if ( isset($_GET["profile"]) AND os_is_logged() )            include(OS_PAGE_PATH."user_profile_page.php"); else
  if ( isset($_GET["ban_report"]) AND $BanReports == 1)        include(OS_PAGE_PATH."ban_reports_page.php"); else
  if ( isset($_GET["ban_appeal"]))                             include(OS_PAGE_PATH."ban_appeals_page.php");
   
   include("inc/compare_players.php");
   
   AddEvent("os_add_meta","OS_MetaVersion");
   
   function OS_MetaVersion() {
   ?>
<meta name="generator" content="OpenStats <?=OS_VERSION?>" />
<?php
   }
?>