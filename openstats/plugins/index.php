<?php
//nothing to do here

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }


$plugins_dir = OS_PLUGINS_DIR;


if ($handle = opendir( OS_PLUGINS_DIR )) {
   while (false !== ($file = readdir($handle))) 
	{
	  if ($file !="." AND  $file !="index.php" AND $file !=".." AND strstr($file,".php")==true ) {
	  //load plugins
	  include(OS_PLUGINS_DIR.$file);
	  }
	}
}
?>