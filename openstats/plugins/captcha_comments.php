<?php
//Plugin: Captcha on comments
//Author: Ivan
//Adds simple captcha on comments

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';

if ($PluginEnabled == 1  ) {

   AddEvent("os_init","OS_CheckCaptcha", 1);
   AddEvent("os_after_comment_form",  "OS_SimpleCaptcha");
   AddEvent("os_comment_form",  "OS_Anchor");
   
   function OS_Anchor() {
   ?>
   <a href="javascript:;" name="SubmitComment"></a>
   <?php
   }
   
   function OS_SimpleCaptcha() {
    
	$code = strtoupper(generate_hash(5));
	$code = str_replace(array("o", "0"), array("x", "x"), $code); 
	$_SESSION["c_code"] = $code;
	?>
	<table>
	 <tr>
	   <td width="68"><input type="text" size="3" value="" name="c_code" /> </td>
	   <td><h2><?=$code?></h2> </td>
	 </tr>
	</table>
	<?php
   }
   
   function OS_CheckCaptcha() {
   
    if ( isset($_POST["post_comment"]) ) {
	    
	if (isset($_GET["post_id"]) AND is_numeric($_GET["post_id"]) ) 
	$backTo = OS_HOME.'?post_id='.safeEscape($_GET["post_id"])."&amp;".generate_hash(12)."#SubmitComment";
	else $backTo = '';
	$CaptchaError = '<h2>Invalid captcha</h2><div><a href="'.$backTo.'">&laquo; Back</a></div>';
		
	   if (!isset($_POST["c_code"]) OR !isset($_SESSION["c_code"]) ) os_trigger_error( $CaptchaError); 
	   
	   if ( ($_POST["c_code"]) != ($_SESSION["c_code"])) {
	    os_trigger_error( $CaptchaError." " );
	   } else {
	        $code = generate_hash(5);
	        $code = str_replace(array("o", "0"), array("x", "x"), $code);
	        $_SESSION["c_code"] = $code;
	   }
	   
	}
   
   }
   //OS_CheckCaptcha();
   
}


?>