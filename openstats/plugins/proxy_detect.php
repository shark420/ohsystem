<?php
//Plugin: Proxy detect
//Author: Ivan
//Proxy detect "proxyfraud.com" service. <br />Prevents users to register/login using proxy or private IP address.

//SERVICE:
//http://proxyfraud.com/api/?IP=

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';
$PluginOptions = '1';

$CheckProxyServer = 'http://proxyfraud.com/api/?IP=';

$AllowLocal = '1';
$AllowProxy = '0';

define("PROXY_SRV", $CheckProxyServer);

define("ALLOW_LOCAL", $AllowLocal);
define("ALLOW_PROXY", $AllowProxy);

$ThisPlugin = basename(__FILE__, '');

//24-bit block	10.0.0.0 - 10.255.255.255	16,777,216	single class A network	10.0.0.0/8 (255.0.0.0)	24 bits	8 bits
//20-bit block	172.16.0.0 - 172.31.255.255	1,048,576	16 contiguous class B network	172.16.0.0/12 (255.240.0.0)	20 bits	12 bits
//16-bit block	192.168.0.0 - 192.168.255.255	65,536	256 contiguous class C network	192.168.0.0/16 (255.255.0.0)	16 bits	16 bits

if ($PluginEnabled == 1) {



function get_data($url, $timeout = 8) {
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6 (.NET CLR 3.5.30729)');
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
 }
 


function CheckPrivateIp( $ip ) {
  if ( ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) )
    {
    // is a local ip address
	os_trigger_error("Local IP Detected");
   }
}

   
   
   function OS_CheckProxy() {
   
   //$_SERVER["REMOTE_ADDR"]
   //$IP = '91.197.129.74';
   //$IP = '93.86.250.237';
   $IP = $_SERVER["REMOTE_ADDR"];
   
   $content = get_data( PROXY_SRV.$IP );

   $dom = new DOMDocument();
   @$dom->loadXML($content);
   
   $check = $dom->getElementsByTagName( "PROXY_DETECTED" );
   
   foreach( $check as $e ) {
   $IsProxy = $e->nodeValue;
   }
   
   return $IsProxy;
   
   }


//EDIT PLUGIN OPTIONS:
if ( OS_is_admin() AND OS_PluginEdit() ) {

   if ( isset($_POST["AllowLocal"]) AND isset($_POST["AllowProxy"]) ) {
     write_value_of('$AllowLocal', "$AllowLocal", (int)$_POST["AllowLocal"] , $plugins_dir.basename(__FILE__, ''));
	 write_value_of('$AllowProxy', "$AllowProxy", (int)$_POST["AllowProxy"] , $plugins_dir.basename(__FILE__, ''));
	 
	$AllowLocal = (int)$_POST["AllowLocal"];
    $AllowProxy = (int)$_POST["AllowProxy"];
   }
   else {
    $AllowLocal = ALLOW_LOCAL;
    $AllowProxy = ALLOW_PROXY;
	}
	
	$sel = array();
	if ( $AllowLocal == 1) $sel[0] = 'selected="selected"'; else $sel[0] = "";
	if ( $AllowLocal == 0) $sel[1] = 'selected="selected"'; else $sel[1] = "";
	if ( $AllowProxy == 1) $sel[2] = 'selected="selected"'; else $sel[2] = "";
	if ( $AllowProxy == 0) $sel[3] = 'selected="selected"'; else $sel[3] = "";
	
if ( OS_is_admin() AND OS_PluginEdit( $ThisPlugin ) ) 
  $Option = '
<form action="" method="post" >
  <select name="AllowLocal">
  <option '.$sel[0].' value="1">Allow</option>
  <option '.$sel[1].' value="0">Disallow</option>
  </select>  Local/Private IP registration/login
  <div class="padTop">
  <select name="AllowProxy">
  <option '.$sel[2].' value="1">Allow</option>
  <option '.$sel[3].' value="0">Disallow</option>
  </select>  Proxy Registration/login
  </div>
  
  <div class="padTop"><input type="submit" value = "Save" class="menuButtons" /> 
  <a href="'.OS_HOME.'adm/?plugins" class="menuButtons">&laquo; Back</a> </div>
  
</form>';

}

   
   //if ( isset($_POST["register_"]) )
   //AddEvent("os_init",  "OS_CheckProxy"); 
   
   if ( isset( $_GET["login"]) ) {
   
    if (ALLOW_LOCAL!=1) CheckPrivateIp( $_SERVER["REMOTE_ADDR"] );
    if (ALLOW_PROXY!=1) $CheckProxy = OS_CheckProxy();
	 
	 if ( isset($CheckProxy) AND $CheckProxy == "YES" ) os_trigger_error("Proxy Detected");
   }

 
}
?>