<?php
//Plugin: Anti-flood
//Author: Ivan
//Basic Anti-flood plugin

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';

if ($PluginEnabled == 1  ) {
    
	$MyIP = $_SERVER["REMOTE_ADDR"];
    if ( !isset( $_SESSION["u_" . $MyIP ] ) ) $_SESSION["u_" . $MyIP ] = time();
	
	if ( !isset($_SESSION["last_activity"]) ) $_SESSION["last_activity"] = 0; 
	
	if ( $_SESSION["u_" . $MyIP ] + 10 <= time() ) $_SESSION["last_activity"]=0;
	
	if ( $_SESSION["u_" . $MyIP ] + 5 <= time() AND $_SESSION["last_activity"]>=1)  
	$_SESSION["last_activity"]-=1;
	
	if ( $_SESSION["u_" . $MyIP ] + 3 <= time() ) $_SESSION["u_" . $MyIP ] = time();
	if ( $_SESSION["u_" . $MyIP ] + 2 <= time() ) $_SESSION["last_activity"]+=1;
	
    if ( $_SESSION["last_activity"]>=5 ) {
			header('HTTP/1.0 503 Service Unavailable');
			header('Status: 503 Service Unavailable');
			header("Retry-After: 10");
			print "<html><meta http-equiv='refresh' content='10'><body><h2>Our server is currently overloaded, your request will be repeated automatically in 10 seconds</h2>";
			die();
	}
	
	//AddEvent("os_content","OS_Debug_");
	
	function OS_Debug_() {
	$MyIP = $_SERVER["REMOTE_ADDR"];
	?>
	Ses: <?=$_SESSION["u_" . $MyIP ]?> | <?=time()?> | <?=$_SESSION["last_activity"]?>
	<?php
	}
}
?>