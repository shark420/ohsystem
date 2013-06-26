<?php
//Plugin: Captcha on registration
//Author: Ivan
//Adds simple captcha on registration

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '1';

if ($PluginEnabled == 1  ) {
   
   AddEvent("os_start","OS_CheckCaptchaRegistration", 1);
   
   AddEvent("os_registration_fields",  "OS_CaptchaOnRegistration");
   
   function OS_CheckCaptchaRegistration() {
     $error = "";
	if ( isset($_SESSION["r_code"]) AND $_SESSION["r_code"] == "OK") { /* CAPTCHA OK */ } else {
       if ( isset($_POST["register_"]) ) {
       $error = "";
	     if ( !isset($_POST["r_captcha"]) OR !isset($_SESSION["r_code"]) ) 
	     $error.="<h2>Invalid captcha form</h2>"; else
	     if ( $_POST["r_captcha"] != $_SESSION["r_code"] )
	     $error.="<h2>Invalid captcha code</h2>";
	     else
	     if ( !isset($_SESSION["r_trap1"]) OR !isset($_SESSION["r_trap2"]) )
		 $error.="<h2>Invalid captcha form</h2>";
		 else
		 if ( !isset($_POST[ $_SESSION["r_trap1"]]) )
		 $error.="<h2>Invalid captcha form</h2>";
		 else
		 if ( isset($_SESSION["r_trap1"]) AND isset($_SESSION["r_trap2"]) AND isset($_POST[ $_SESSION["r_trap1"]]) AND $_SESSION["r_trap2"] != $_POST[ $_SESSION["r_trap1"] ] )
	     $error.="<h2>Invalid captcha form</h2>";
		 
	     if ( !empty($error) ) os_trigger_error( $error." " );
	   
	     $_SESSION["r_code"] = "OK";
	   }
	 }
   }
   
   function OS_CaptchaOnRegistration() {

   if ( isset($_SESSION["r_code"]) AND $_SESSION["r_code"] == "OK") { /* CAPTCHA OK */ } else {
   $code = rand(100,10000);
   $_SESSION["r_code"] = $code;
   
   $trap1 = generate_hash(16);
   $trap2 = generate_hash(8);
   $_SESSION["r_trap1"] = $trap1;
   $_SESSION["r_trap2"] = $trap2;
   ?>
   <tr>
     <td class="padLeft">Captcha:</td>
	 <td class="padLeft">
	 <input type="text" size="1" value="" name="r_captcha"/>
	 <input type="hidden" name="<?=$trap1?>" value="<?=$trap2?>" />
	 <span style="font-size:26px; font-weight:bold;"><?=$code?></span>
	 </td>
   </tr>
   <?php
     }
   }
   
}