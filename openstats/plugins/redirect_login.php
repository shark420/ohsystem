<?php
//Plugin: Redirect user after log in
//Author: Ivan
//Enable user redirection after logged in.

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '1';
//Enable edit plugin options
$PluginOptions = '1';

$RedirectLogin = '8';

$ThisPlugin = basename(__FILE__, '');

//Hard coded plugin - currently dont have time to improve it.

if ( $RedirectLogin == 1 ) define("OS_REDIRECT_TO", 'profile' ); else
if ( $RedirectLogin == 2 ) define("OS_REDIRECT_TO", 'top' ); else
if ( $RedirectLogin == 3 ) define("OS_REDIRECT_TO", 'games' ); else
if ( $RedirectLogin == 4 ) define("OS_REDIRECT_TO", 'admins' ); else
if ( $RedirectLogin == 5 ) define("OS_REDIRECT_TO", 'about_us' ); else
if ( $RedirectLogin == 6 ) define("OS_REDIRECT_TO", 'members' ); else
if ( $RedirectLogin == 7 ) define("OS_REDIRECT_TO", 'bans' ); else
if ( $RedirectLogin == 8 ) define("OS_REDIRECT_TO", 'last_page' ); else
define("OS_REDIRECT_TO", '' );

if ($PluginEnabled == 1  ) {

  if ( !isset($_GET["login"]) AND $RedirectLogin == 8) {

	AddEvent("os_start","OS_GetCurrentPage");
	
	function OS_GetCurrentPage() {
	
	if (isset($_SERVER["HTTP_REFERER"]) AND strstr($_SERVER["HTTP_REFERER"], OS_HOME) ) {
	
    $pref = "";
		 
	 if ( substr(OS_HOME,0,7) == "http://" )   $pref = "http://"; else
	 if ( substr(OS_HOME,0,7) == "https://" )  $pref = "https://";
	 
	 $CurrentPage = $pref . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
	 if ( strstr($CurrentPage, OS_HOME) AND !isset($_GET["login"]) AND !strstr($CurrentPage, "action=signature") ) $_SESSION["last_page"] = $CurrentPage;
	 
	 //echo  $CurrentPage;
	  }
	}
	
  }

if ( OS_is_admin() AND OS_PluginEdit() ) {

if ( isset($_POST["RedirectPage"]) ) {
   $emailError = "";
   $RedirectPage = safeEscape($_POST["RedirectPage"]);
	
	  if ( is_numeric($RedirectPage) ) {
      write_value_of('$RedirectLogin', "$RedirectLogin", $RedirectPage , $plugins_dir.basename(__FILE__, '') );
      $RedirectLogin = $RedirectPage;
      }
}

$sel = array();
if ($RedirectLogin == 0) $sel[0]='selected = "selected"'; else $sel[0] = '';
if ($RedirectLogin == 1) $sel[1]='selected = "selected"'; else $sel[1] = '';
if ($RedirectLogin == 2) $sel[2]='selected = "selected"'; else $sel[2] = '';
if ($RedirectLogin == 3) $sel[3]='selected = "selected"'; else $sel[3] = '';
if ($RedirectLogin == 4) $sel[4]='selected = "selected"'; else $sel[4] = '';
if ($RedirectLogin == 5) $sel[5]='selected = "selected"'; else $sel[5] = '';
if ($RedirectLogin == 6) $sel[6]='selected = "selected"'; else $sel[6] = '';
if ($RedirectLogin == 7) $sel[7]='selected = "selected"'; else $sel[7] = '';
if ($RedirectLogin == 8) $sel[8]='selected = "selected"'; else $sel[8] = '';

//Show following options when user click on edit icon for this plugin
if ( OS_is_admin() AND OS_PluginEdit() )
$Option = '<div><b>Redirect after login:</b></div>
<form action="" method="post" >
  <select name="RedirectPage">
   <option '.$sel[8].' value="0">Last page visited</option>
   <option '.$sel[0].' value="0">Home page</option>
   <option '.$sel[1].' value="1">Profile page</option>
   <option '.$sel[2].' value="2">Top page</option>
   <option '.$sel[3].' value="3">Games page</option>
   <option '.$sel[4].' value="4">Admins page</option>
   <option '.$sel[5].' value="5">About Us page</option>
   <option '.$sel[6].' value="6">Members page</option>
   <option '.$sel[7].' value="7">Bans page</option>
  </select>
  <input type="submit" value = "Submit" class="menuButtons" />
  <a href="'.$website.'adm/?plugins" class="menuButtons">Cancel</a>
</form>';
}
   
   AddEvent("os_init", "EventWhenUserLogIn");
   AddEvent("os_start","RedirectToHomePage");
   
   function EventWhenUserLogIn() {
    if ( isset( $_GET["login"]) AND isset($_POST["login_"] ) AND empty($errors) AND is_logged()) {
	
	 if ( defined('OS_REDIRECT_TO') AND OS_REDIRECT_TO!="" ) {
	 
	     if (OS_REDIRECT_TO == 'last_page' AND isset( $_SESSION["last_page"] ) AND strstr($_SESSION["last_page"], OS_HOME) ) {
		 
		 if ( empty($_SESSION["last_page"]) OR $_SESSION["last_page"] == "last_page" OR isset($_GET["last_page"]) ) 
		 { header("location: ".OS_HOME); die; }
		 
		 header("location: ".$_SESSION["last_page"]); die;
	   }
	   header("location: ".OS_HOME."?".OS_REDIRECT_TO); die;
	   }
	}
   }
   
   function RedirectToHomePage() {
     if ( isset($_GET["last_page"]) ) { header("location: ".OS_HOME); die; }
   }
}
?>