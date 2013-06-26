<?php
//Plugin: Realm username and birthday
//Author: Ivan
//Add custom field:  Realm username on and user birthday <a href="../?profile" target="_blank">profile page</a>.

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';


if ($PluginEnabled == 1  ) {

  if ( isset($_GET["profile"]) AND is_logged() ) {
  AddEvent("os_custom_user_fields","OS_CustomField");
  AddEvent("os_init","OS_UpdateCustomField");
  }
  
  if ( isset($_GET["action"]) AND $_GET["action"] == "profile" AND isset($_GET["id"]) )
  AddEvent("os_custom_user_fields", "OS_DisplayCustomField");

function OS_CustomField() {
 //Display realm username edit field on profile page
 if ( isset($_GET["profile"]) ) {
 global $db;
 $uid = (int)$_SESSION["user_id"];
 
 $realmUnValue = OS_get_custom_field( $uid, "realm_username" );
 
 $BirthValue   = OS_get_custom_field( $uid, "user_birthday" );
 
 if ( !empty($BirthValue) ) {
 $UserBirth = explode("-", $BirthValue); //we use day-month-year DATEFORMAT
 $dd = $UserBirth[0];
 $mm = $UserBirth[1];
 $yy = $UserBirth[2];
 } else { $dd = ""; $mm = ""; $yy = ""; }
 ?>
 <tr>
   <td class="padLeft">Realm username:</td>
   <td><input type="text" value="<?=$realmUnValue?>" name="realm_un" /></td>
 </tr>
  <tr>
   <td class="padLeft">Birthday:</td>
   <td>
    <select name="birthday_day">
	  <option value=""></option>
	  <?php for ($i = 1; $i <= 31; $i++) { 
	  if ( $i == $dd ) $sel = 'selected="selected"'; else $sel = ""; ?>
	  <option <?=$sel?> value="<?=$i?>"><?=$i?></option>
	  <?php } ?>
	</select>
	 <select name="birthday_month">
	   <option value=""></option>
	  <?php for ($i = 1; $i <= 12; $i++) { 
	  if ( $i == $mm ) $sel = 'selected="selected"'; else $sel = ""; ?>
	  <option <?=$sel?>  value="<?=$i?>"><?=getMonthName($i)?></option>
	  <?php } ?>
	</select>
    <select name="birthday_year">
	  <option value=""></option>
	  <?php for ($i = 1940; $i <= date("Y")-5; $i++) { 
	  if ( $i == $yy ) $sel = 'selected="selected"'; else $sel = ""; ?>
	  <option <?=$sel?>  value="<?=$i?>"><?=$i?></option>
	  <?php } ?>
	</select>
   </td>
 </tr>
 <?php 
 }
}
 
function OS_UpdateCustomField() {
  
  //Update data 
  if ( isset( $_POST["change_profile"]) AND isset($_POST["realm_un"]) AND is_logged() ) {
    global $db;
	$realm_un = safeEscape( $_POST["realm_un"]);
	$dd = safeEscape( $_POST["birthday_day"]);
	$mm = safeEscape( $_POST["birthday_month"]);
	$yy = safeEscape( $_POST["birthday_year"]);
	
	if ( $dd<=0 OR $dd>31 )           $dd = "";       else $dd = $dd.'-';
	if ( $mm<=0 OR $mm>12 )           $mm = "";       else $mm = $mm.'-';
	if ( $yy<=1930 OR $yy>date("Y") ) $yy = "";       else $yy = $yy.'';
	
	$user_birth = $dd.$mm.$yy;
	
	$uid = (int)$_SESSION["user_id"];
	//Check if data already exists
	OS_add_custom_field($uid, "realm_username" ,  $realm_un );
	
	if ( $dd == "" OR $mm=="" OR $yy == "" )
	OS_delete_custom_field($uid, "user_birthday");
	else
	OS_add_custom_field($uid, "user_birthday" ,  $user_birth );
  }
}

 //Display custom field: Realm username
 //we can use this function on custom page
 function OS_DisplayCustomField() {
    global $db;
    
	//FUNCTION: OS_GetAction
	//OS_GetAction is $_GET["action"]
	//OS_GetAction("profile") same as $_GET["action"] == "profile";
	
	if ( OS_GetAction("profile") AND isset($_GET["id"]) AND is_numeric($_GET["id"]) ) {
    $uid = (int)$_GET["id"];
	
	 $RealmUn     = OS_get_custom_field( $uid, "realm_username" );
	 $UserBirth   = OS_get_custom_field( $uid, "user_birthday" );
     $UserBirth   = str_replace("-", " ", $UserBirth );
	?>
	<tr>
	    <td width="130" class="padLeft"><b>Realm username:</b></td>
		<td><?=$RealmUn?></td>
	</tr>
	<tr>
	    <td width="130" class="padLeft"><b>Birthday:</b></td>
		<td><?=$UserBirth?></td>
	</tr>
	<?php
	}
 }

}
?>