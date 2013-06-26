<?php
//Plugin: Number format on Top and User Page
//Author: Ivan
//Change number format on Top and User Stats page.

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';
//Enable edit plugin options
$PluginOptions = '1';

$DecimalPoint = '.';

if ($PluginEnabled == 1) {

if ( isset($_POST["DecimalPoint"]) ) {
   $Char = safeEscape($_POST["DecimalPoint"]);
   write_value_of('$DecimalPoint', "$DecimalPoint", $Char , $plugins_dir.basename(__FILE__, '') );
   $DecimalPoint = $Char;
}

if ( OS_is_admin() AND OS_PluginEdit() ) {
//Show following options when user click on edit icon for this plugin
$Option = '
<form action="" method="post" >
  <input size="1" type="text" value="'.$DecimalPoint.'" name="DecimalPoint" />
  <input type="submit" value = "Change decimal point" class="menuButtons" />
  <a href="'.$website.'adm/?plugins" class="menuButtons">Cancel</a>
</form>';
}
  
  function NumberFormatTopPage( $dec_point = '.' ) {
    global $TopData;
	
	//Top page
	if ( !empty($TopData) ) {
	  
	  for ($c=0; $c<count($TopData); $c++ ) {
	  $TopData[$c]["score"]  = str_replace(",", $dec_point, $TopData[$c]["score"]);
	  $TopData[$c]["games"]  = str_replace(",", $dec_point, $TopData[$c]["games"]);
	  $TopData[$c]["wins"]   = str_replace(",", $dec_point, $TopData[$c]["wins"]);
	  $TopData[$c]["losses"] = str_replace(",", $dec_point, $TopData[$c]["losses"]);
	  $TopData[$c]["draw"]   = str_replace(",", $dec_point, $TopData[$c]["draw"]);
	  $TopData[$c]["kills"]  = str_replace(",", $dec_point, $TopData[$c]["kills"]);
	  $TopData[$c]["deaths"] = str_replace(",", $dec_point, $TopData[$c]["deaths"]);
	  $TopData[$c]["assists"]= str_replace(",", $dec_point, $TopData[$c]["assists"]);
	  $TopData[$c]["creeps"] = str_replace(",", $dec_point, $TopData[$c]["creeps"]);
	  $TopData[$c]["denies"] = str_replace(",", $dec_point, $TopData[$c]["denies"]);
	  }
	  
	   return array($TopData);
	  
	}
	
	//Single user page
	global $UserData;
	
	if ( !empty($UserData) ) {
	  for ($c=0; $c<count($UserData); $c++ ) {
	  $UserData[$c]["score"]  = str_replace(",", $dec_point, $UserData[$c]["score"]);
	  $UserData[$c]["games"]  = str_replace(",", $dec_point, $UserData[$c]["games"]);
	  $UserData[$c]["wins"]   = str_replace(",", $dec_point, $UserData[$c]["wins"]);
	  $UserData[$c]["losses"] = str_replace(",", $dec_point, $UserData[$c]["losses"]);
	  $UserData[$c]["draw"]   = str_replace(",", $dec_point, $UserData[$c]["draw"]);
	  $UserData[$c]["kills"]  = str_replace(",", $dec_point, $UserData[$c]["kills"]);
	  $UserData[$c]["deaths"] = str_replace(",", $dec_point, $UserData[$c]["deaths"]);
	  $UserData[$c]["assists"]= str_replace(",", $dec_point, $UserData[$c]["assists"]);
	  $UserData[$c]["creeps"] = str_replace(",", $dec_point, $UserData[$c]["creeps"]);
	  $UserData[$c]["denies"] = str_replace(",", $dec_point, $UserData[$c]["denies"]);
	  $UserData[$c]["neutrals"] = str_replace(",", $dec_point, $UserData[$c]["neutrals"]);
	  }
	}
	
  }
  
  NumberFormatTopPage($DecimalPoint);

}

?>