<?php
//Plugin: Reset password
//Author: Ivan
//With this plugin users can reset their forgotten password.

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '1';
//Enable edit plugin optins
//$PluginOptions = '1';

//unset($_SESSION["password_send"] );

if ($PluginEnabled == 1  ) {

   if ( OS_login_page() AND !os_is_logged() ) AddEvent("os_login_fields",  "OS_ForgotPasswordLink");
   
    if ( OS_GetAction("reset_password") AND os_is_logged() ) {
	  header('location:'.OS_HOME.''); die;
	}
	
   if ( OS_GetAction("reset_password") AND !os_is_logged() )
   { AddEvent("os_content",  "OS_ForgotPassword"); 	$HomeTitle = "Password reminder"; }
   
   function OS_ForgotPasswordLink() {
   ?>
   <tr>
     <td></td>
	 <td class="padLeft">
	   <div><a href="<?=OS_HOME?>?action=reset_password">Forgot your password?</a></div>
	 </td>
	</tr>
   <?php
   }
   
   function OS_ForgotPassword() {
    $errors = "";
	global $db;
	global $mail;
	global $lang;

   if ( isset($_POST["reset_password"]) AND isset($_POST["reset_password_submit"]) ) {
   
    $email = EscapeStr(trim( $_POST["reset_password"] ));
	
	if ( isset($_SESSION["password_send"]) ) 
	$errors.="<h4>You have already sent a request to reset the password. Please check your mail.</h4>";
	
	if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i", $email)) 
	$errors.="<h4>Invalid Email address</h4>";
	
	if ( empty($errors) ) {
	  $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_email = :email LIMIT 1 ");
	  $sth->bindValue(':email', $email, PDO::PARAM_STR); 
	  $result = $sth->execute();
	  if ( $sth->rowCount()<=0 ) $errors.="<h4>Email address does not exist in our database.</h4>";
	  
	  if ( empty( $errors ) ) {
         $code = generate_hash(16);
		 OS_add_custom_field( 0, 'reset_password|'.$email , $code);
	  
	  require("inc/class.phpmailer.php");	  
	  $message = "You have requested a password reset.<br />";
	  $message.= "Click on the link below to reset your password:<br /><br />";
	  $message.= OS_HOME."?action=reset_password&e=".$email."&c=".$code."<br /><br />";
	  $message.= "~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~<br />";
	  $message.= "If you did not request a password reset just ignore this email and delete it.<br />";
	  //$message.=  "".OS_HOME."<br />";
	  $mail  = new PHPMailer();
	  $mail->CharSet = 'UTF-8';
	  $mail->SetFrom('info@openstats.iz.rs', "");
	  $mail->AddReplyTo( $email, $email );
	  $mail->AddAddress($email, "");
	  $mail->Subject = "Password reminder";
	  $mail->MsgHTML($message);
	  $mail->Send();
	  
	  $_SESSION["password_send"] = time();
	  //Not error, just a message
	  $errors="<h4>You have successfully submitted a request to reset your password. Please check your mail.</h4>";
      }	  
	}
   
   }
   
   ?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
    <div class="content section">
     <div class="widget Blog">
      <div class="blog-posts hfeed padLeft">
	  <h2>Reset password</h2>
	  <div class="padTop"></div>
	  
	  <?php 
	  if ( isset($errors) AND !empty($errors) ) echo $errors;
	  ?>
	  <?php if (!isset($_GET["c"]) AND !isset($_GET["e"]) ) { ?>
	  <form action="" method="post">
	  <table style="width:600px;">
	    <tr class="row">
		  <td></td>
		  <td>You can't retrieve your password, but you can set a new one by following a link sent to you by email.</td>
		</tr>
	    <tr class="row">
		  <td width="120" class="padLeft">Email address:</td>
		  <td class="padLeft"><input type="text" name="reset_password" size="39" value="" /></td>
		</tr>
	    <tr class="row">
		  <td width="120" class="padLeft"></td>
		  <td class="padLeft"><input type="submit" name="reset_password_submit" class="menuButtons" value="Send" />
		  <div class="padBottom"></div>
		  </td>
		</tr>
	  </table>
	  </form>
	  <?php } else { 
	  if (isset($_GET["e"]) ) $email = EscapeStr(trim( $_GET["e"] )); else $email = generate_hash(12);
	  if (isset($_GET["c"]) ) $code = EscapeStr(trim( $_GET["c"] )); else $code = generate_hash(12);
	  if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i", $email)) 
	  $errors.="<h4>Invalid Email address</h4>";
	  
	  if ( empty($errors) ) {
	  $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_email = :email LIMIT 1 ");
	  $sth->bindValue(':email', $email, PDO::PARAM_STR); 
	  $result = $sth->execute();
	  if ( $sth->rowCount()<=0 ) $errors.="<h4>Email address does not exist in our database.</h4>";
	  }
	  
	  if ( empty($errors) ) {
	    $value = OS_get_custom_field( 0,  'reset_password|'.$email );
		if ( $code != $value OR strlen($code)<=5 )
		$errors.="<h4>Link has expired, or the password has already been reset</h4>";
	  }
	  
	  //FINALLY RESET
	  if ( empty($errors) AND isset($_POST["reset_1"] ) AND isset($_POST["reset_2"] ) ) {
	    $p1 = strip_tags($_POST["reset_1"]);
		$p2 = strip_tags($_POST["reset_2"]);
		
		if ( $p1!=$p2 ) $errors.="<h4>Both passwords are not the same</h4>";
		else {
		  $hash = generate_hash(16,1);
	      $password_db = generate_password($p1, $hash);
		  
		   $result = $db->update(OSDB_CUSTOM_FIELDS, array(
		   "user_password" => $password_db,
		   "password_hash" => $hash
	                      ), "user_email = '".$email."'"); 
		  
		  //OS_delete_custom_field( 0, 'reset_password|'.$email , $code);
		  $delete = $db->exec("DELETE FROM ".OSDB_CUSTOM_FIELDS." 
		  WHERE field_value='".$code."' AND field_name = 'reset_password|".$email."' LIMIT 1");
		  $PasswordReset = 1;
		}
	  }
	  
	 if ( isset($errors) AND !empty($errors) ) echo $errors; else {
	 
	 if ( isset($PasswordReset) AND $PasswordReset == 1) {
	 ?>
	 <h2>Password has been successfully changed. Now you can log in.</h2>
	 <?php
	 } else {
	  ?>
	  <form action="" method="post">
	  	<table style="width:600px;">
	    <tr class="row">
		  <td class="padLeft">New password:</td>
		  <td class="padLeft"><input type="password" name="reset_1" size="6" value="" /></td>
		</tr>
	    <tr class="row">
		  <td class="padLeft">Repeat password:</td>
		  <td class="padLeft"><input type="password" name="reset_2" size="6" value="" /></td>
		</tr>
	    <tr class="row">
		  <td width="120" class="padLeft"></td>
		  <td class="padLeft"><input type="submit" name="reset_pw" class="menuButtons" value="Reset your password" />
		  <div class="padBottom"></div>
		  </td>
		</tr>
	    </table>
		
	  </form>
	  <?php }
	     }
	  } ?>
	  
	  <div style="height:260px;"></div>
	  </div>
    </div>
   </div>
 </div>
</div>
   <?php
   }
}
?>