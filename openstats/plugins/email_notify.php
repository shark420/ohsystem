<?php
//Plugin: Notify administrator on new comment
//Author: Ivan
//Enable email notification on new comments

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';
//Enable edit plugin options
$PluginOptions = '1';

$AdminEmail = 'user@domain.com';

$ThisPlugin = basename(__FILE__, '');

define("OS_ADMIN_EMAIL", $AdminEmail);

if ($PluginEnabled == 1  ) {


if ( OS_is_admin() AND OS_PluginEdit( $ThisPlugin ) ) {

if ( isset($_POST["AdminEmail"]) ) {
   $emailError = "";
   $Email = safeEscape($_POST["AdminEmail"]);
   
   if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i", $Email)) 
   $emailError = '<div style="color:red">Invalid email address</div>';
	
	  if (empty($emailError) ) {
      write_value_of('$AdminEmail', "$AdminEmail", $Email , $plugins_dir.basename(__FILE__, '') );
      $AdminEmail = $Email;
      }
}

//Show following options when user click on edit icon for this plugin
$Option = '
<form action="" method="post" >
  <input size="30" type="text" value="'.$AdminEmail.'" name="AdminEmail" class="field" />
  <input type="submit" value = "Admin email" class="menuButtons" />
  <a href="'.$website.'adm/?plugins" class="menuButtons">Cancel</a>
</form>
<div>Enter the email address for notification</div>';
}

if ( isset($emailError ) AND !empty($emailError ) ) $Option.=$emailError;

   
   AddEvent("os_init","SendMailOnNewComment");
   
   function SendMailOnNewComment() {
    if ( isset($_POST["post_comment"]) ) {
	  global $lang;
	  global $mail;
	  $message = "You can see a new comment by clicking on the following link<br />";
	  $message.=  "".OS_HOME."<br />";
	  require("inc/class.phpmailer.php");
	  $mail  = new PHPMailer();
	  $mail->CharSet = 'UTF-8';
	  $mail->SetFrom(OS_ADMIN_EMAIL, "DotA OpenStats");
	  $mail->AddReplyTo($lang["email_from"], $lang["email_from_full"]);
	  $mail->AddAddress(OS_ADMIN_EMAIL, "");
	  $mail->Subject = "New comment";
	  $mail->MsgHTML($message);
	  $mail->Send();
	  
	  //die("mail sent". $lang["email_from"] );
	}
   }

}
?>