<?php
if (strstr($_SERVER['REQUEST_URI'], basename(__FILE__) ) ) {header('HTTP/1.1 404 Not Found'); die; }

  $time = microtime();
  $time = explode(' ', $time);
  $time = $time[1] + $time[0];
  $start = $time;
  
   include('config.php');
   require_once('inc/common.php');

   require_once('lang/'.OS_LANGUAGE.'.php');    
   require_once('inc/integration.php');
   if(isset($DBDriver) AND $DBDriver == "mysql" ) require_once('inc/class.database.php'); else
   require_once('inc/class.db.PDO.php'); 
   require_once('inc/db_connect.php');


   os_init();
   require_once('inc/sys.php');  
   require_once('plugins/index.php');
   
   os_start();
   if ( file_exists('themes/'.OS_THEMES_DIR.'/functions.php') )
   include('themes/'.OS_THEMES_DIR.'/functions.php');

   include('themes/'.OS_THEMES_DIR.'/header.php');
   include('themes/'.OS_THEMES_DIR.'/menu.php');

   include('inc/template.php');
   
   os_after_content();
   include('themes/'.OS_THEMES_DIR.'/footer.php');
?>