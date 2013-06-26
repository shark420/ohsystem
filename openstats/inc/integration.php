<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

if ( $PHPbb3Integration==1 )  include("inc/addons/phpbb.php");
if ( $SMFIntegration==1 )     include("inc/addons/smf.php");
if ( $WPIntegration==1 )      include("inc/addons/wordpress.php");

// /--> START: MyBB forum integration

$mybb_forum = 'mybb/';
$mybb_forum_url = 'http://localhost/openstats_new/mybb/';
//Uncomment below to enable mybb forum integration

//require_once("inc/addons/mybb.php");

// /--> END: MyBB forum integration



?>