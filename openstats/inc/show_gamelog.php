<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

if (isset($GameData[0]["replay"]) AND !empty($GameData[0]["replay"]) ) { 

if ( file_exists($replayurl.".html") ) include($replayurl.".html");
else {
ob_start(); 
include('./inc/replay_parser/get_chat.php');
$pageContents = ob_get_contents(); 
file_put_contents($replayurl.".html", $pageContents);
  }  
}
?>