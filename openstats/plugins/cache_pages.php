<?php
//Plugin: Cache pages for non-logged in users
//Author: Ivan
//Full page caching for users who are not logged in. <br />Not recommended for use with GameList patch.

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';
//Enable edit plugin options
$PluginOptions = '1';

$CacheTime = '60';
$cacheDir = 'cache/';

$ThisPlugin = basename(__FILE__, '');

//Plugin disable - purge cache
if ( PluginDisabled( $ThisPlugin ) ) {

  $handleCache = opendir("../".$cacheDir);
  
  while (false !== ($Cfile = readdir($handleCache))) {
    if ( $Cfile!='.' AND $Cfile!='..' )
    
     if ( file_exists("../".$cacheDir.$Cfile) AND (strstr($Cfile, ".php") OR strstr($Cfile, ".html") ) AND !empty($Cfile) ) 
	 unlink("../".$cacheDir.$Cfile);
  }
}

if ($PluginEnabled == 1  ) {

define("OS_CURRENT_THEME", $DefaultStyle);


if ( isset($_POST["CacheTime"]) ) {
   $Time = safeEscape($_POST["CacheTime"]);
   write_value_of('$CacheTime', "$CacheTime", $Time , $plugins_dir.basename(__FILE__, '') );
   $CacheTime = $Time;
}

if ( OS_is_admin() AND OS_PluginEdit( $ThisPlugin ) ) {
$count = 0;
if (file_exists("../".$cacheDir) AND $handleCache = opendir("../".$cacheDir)) {
  while (false !== ($Cfile = readdir($handleCache))) {
    if ( $Cfile!='.' AND $Cfile!='..' )
    $count++;
     if ( isset($_POST["DeleteCachedFiles"]) AND file_exists("../".$cacheDir.$Cfile) AND (strstr($Cfile, ".php") OR strstr($Cfile, ".html") ) AND !empty($Cfile)  OR (isset($_GET["disable"]) AND $_GET["disable"] == $cacheDir.$Cfile) ) 
	 unlink("../".$cacheDir.$Cfile);
  }
}

if ( isset($_POST["DeleteCachedFiles"]) ) {
  $count = 0; 
  //if ( file_exists("../".$cacheDir) AND is_dir( "../".$cacheDir ) ) rmdir( "../".$cacheDir );
}

$TotalCachedFiles = $count;

$Option = '<form action="" method="post">
  Cache time: <input size="2" type="text" value="'.$CacheTime.'" name="CacheTime" /> min.
  <input type="submit" value = "Submit" class="menuButtons" />
  <a href="'.$website.'adm/?plugins" class="menuButtons">Cancel</a>
</form>

<div style="border-top:1px solid #ccc; margin-top: 10px;">Cached files: '.$TotalCachedFiles.'</div>
<div>
<form action="" method="post">
  <input type="submit" name="DeleteCachedFiles" value = "Delete cached files" class="menuButtons" />
</form>
</div>';
}

if (!is_logged() AND !isset($_GET["login"]) AND !isset($_GET["vote"]) ) {
 	$break = explode('/',$_SERVER["SCRIPT_NAME"]);
	$cPage = $break[count($break) - 1];
	$cPage = str_replace(".php","",$cPage);
	$CacheTopPage = $cacheDir.OS_CURRENT_THEME."_".$cPage."_".$_SERVER['QUERY_STRING'].".php";
	$CacheTopPage = str_replace(array("&", "="), array("-", "_"), $CacheTopPage );
   if ( file_exists($CacheTopPage)  AND time() - $CacheTime*60 < filemtime($CacheTopPage) ) {
    include($CacheTopPage);
	//echo "cached!";
	die;
   }
   
   ob_start();
   
 AddEvent("os_after_footer","CacheThis");
   
   
 function CacheThis( $cacheDir = 'cache/' ) {
 
    if ( !file_exists( $cacheDir ) ) {
	  mkdir($cacheDir."");
	  chmod($cacheDir."",0777);
	  file_put_contents($cacheDir."index.html", "");
	}
 
    $pageContents = ob_get_contents()."
</body>
</html>";
    ob_end_clean();
	
    $CachePage = CreateFileName( $cacheDir );
    file_put_contents($CachePage, $pageContents);
	
	echo $pageContents;
 }
   

 function CreateFileName( $cacheDir = 'cache/' ) {

 	$break = explode('/',$_SERVER["SCRIPT_NAME"]);
	$cPage = $break[count($break) - 1];
	$cPage = str_replace(".php","",$cPage);
	$CacheTopPage = $cacheDir.OS_CURRENT_THEME."_".$cPage."_".$_SERVER['QUERY_STRING'].".php";
	$CacheTopPage = str_replace(array("&", "="), array("-", "_"), $CacheTopPage );
	
 return $CacheTopPage;
 
    }
  }
}

?>