<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

//LOGOUT
if ( isset($_GET["logout"]) AND is_logged() ) {
  require_once(OS_PLUGINS_DIR.'index.php');
  os_init();
  os_logout();
  //logout redirect
  if ( isset($_SESSION["logout"]) ) {
  header("location: ".$_SESSION["logout"].""); die;
  } else {
  header("location: ".OS_HOME.""); die;
  }
}


//USER ACTIVATION - login&code=$code&e=$email
if ( !is_logged() AND isset($_GET["login"]) AND isset($_GET["code"]) AND isset($_GET["e"]) AND strlen($_GET["code"])>=8 ) {
   $code = safeEscape( $_GET["code"]);
   $e = $_GET["e"];
   $errors = "";
   
   if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i", $e)) 
   $errors.="<div>".$lang["error_email"]."</div>";
   if ( empty($errors) ) {
   $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_email=:user_email AND code=:code ");
   
   $sth->bindValue(':user_email', $e, PDO::PARAM_STR); 
   $sth->bindValue(':code', $code, PDO::PARAM_STR); 
   
   $result = $sth->execute();
   
   if ( $sth->rowCount()>=1) {
   
    $update = $db->update(OSDB_USERS, array("code" => ''), "user_email = '".$e."' AND code = '".$code."'");
  
	 $errors.="<div>".$lang["acc_activated"]."</div>"; //Not error...just a message
   } else $errors.="<div>".$lang["invalid_link"]."</div>";
   
   }
}

  if ( isset($_GET["login"]) )    {
    $HomeTitle = ($lang["login"]);
    if (isset($_GET["success"]) ) $registration_errors = '<div  style="padding-left:370px;"><h3>'.$lang["succes_registration"].'</h3></div>';
	
	if ( is_logged() AND isset($_GET["success"]) ) { header("location: ".OS_HOME.""); die; } 
  }

//LOGIN
if ( isset( $_GET["login"]) AND !is_logged() AND isset($_POST["login_"] ) ) {

   $email = safeEscape( $_POST["login_email"]);
   $password = safeEscape( $_POST["login_pw"]);
   $errors = "";
   if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i", $email)) 
   $errors.="<div>".$lang["error_email"]."</div>";
   if ( strlen($password)<=2 ) $errors.="<div>".$lang["error_short_pw"]."</div>";
   
   if ( empty($errors) ) {
   
    $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_email=:user_email ");
   
    $sth->bindValue(':user_email', $email, PDO::PARAM_STR); 
   
    $result = $sth->execute();
   
	  if ( $sth->rowCount()>=1 ) {
	  
	  $row = $sth->fetch(PDO::FETCH_ASSOC);
	  $CheckPW = generate_password($password, $row["password_hash"]);
	  
	  if (!empty($row["code"]) ) $errors.="<div>".$lang["error_inactive_acc"]."</div>";
	  
	  if ( $CheckPW == $row["user_password"] AND empty($errors)) {
	  $_SESSION["user_id"] = $row["user_id"];
	  $_SESSION["username"] = $row["user_name"];
	  $_SESSION["email"]    = $row["user_email"];
	  $_SESSION["level"]    = $row["user_level"];
	  $_SESSION["can_comment"]    = $row["can_comment"];
	  $_SESSION["logged"]    = time();
	  $_SESSION["user_lang"]    = $row["user_lang"];
	  
	  $LastLogin = $db->update(OSDB_USERS, array("user_last_login" => (int)time() ), "user_email = '".$email."'");

	  require_once(OS_PLUGINS_DIR.'index.php');
	  os_init();
	  header("location: ".OS_HOME.""); die;
	  }
	  
	 }  else $errors.="<div>".$lang["error_invalid_login"]."</div>";
   }
}

//REGISTER
if ( isset( $_GET["login"]) AND !is_logged() AND isset($_POST["register_"] ) ) {

   if ($UserActivation == 2) { 
   	require_once(OS_PLUGINS_DIR.'index.php');
	os_init();
   header('location: '.OS_HOME.''); die; 
   }
   
   $username = OS_StrToUTF8( $_POST["reg_un"] );
   $username = EscapeStr( trim( $username ));
   $email = safeEscape( trim($_POST["reg_email"]));
   $password = safeEscape( $_POST["reg_pw"]);
   $password2 = safeEscape( $_POST["reg_pw2"]);
   $registration_errors = "";

   $AllowedCharacters = 'QWERTZUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklyxcvbnmљњертзуиопшђасдфгхјклчћжѕџцвбнмšđčćžŠĐČĆŽЉЊЕРТЗУИОПШЂАСДФГХЈКЛЧЋЖЅЏЦВБНМ_-';
   
   if (!preg_match ('/^['.$AllowedCharacters.']+$/', $username))
   $registration_errors.="<div>".$lang["error_username"]."</div>";
   
   //die($registration_errors." - ".$username);
   
   if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i", $email)) 
   $registration_errors.="<div>".$lang["error_email"]."</div>";
   if ( strlen($username)<=2 )  $registration_errors.="<div>".$lang["error_short_un"]."</div>";
   if ( strlen($password)<=2 )  $registration_errors.="<div>".$lang["error_short_pw"]."</div>";
   if ( $password!=$password2 ) $registration_errors.="<div>".$lang["error_passwords"]."</div>";
   
   if ( empty($registration_errors) ) {
    //$result = $db->query("SELECT COUNT(*) FROM ".OSDB_USERS." WHERE (user_name) = ('".$username."') ");
	 
   $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_USERS." WHERE user_name=:user_name LIMIT 1");
   $sth->bindValue(':user_name', $username, PDO::PARAM_STR); 
   $result = $sth->execute();
   $r = $sth->fetch(PDO::FETCH_NUM);

     if ( $r[0] >=1 )
	 $registration_errors.="<div>".$lang["error_un_taken"]."</div>";
	 
	 $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_USERS." WHERE user_email=:user_email LIMIT 1");
	 $sth->bindValue(':user_email', $email, PDO::PARAM_STR); 
	 $result = $sth->execute();
	 $r = $sth->fetch(PDO::FETCH_NUM);

     if ( $r[0] >=1 )
	 $registration_errors.="<div>".$lang["error_email_taken"]."</div>";
	 
	  if ( empty($registration_errors) ) {
	  
	  $hash = generate_hash(16,1);
	  $password_db = generate_password($password, $hash);
	  
	  if ($UserActivation == 1) $code = generate_hash(16,1); else $code = '';
	  
    $db->insert( OSDB_USERS, array(
	"user_name" => $username,
	"user_password" => $password_db,
	"password_hash" => $hash,
	"user_email" => $email,
	"user_joined" => (int) time(),
	"user_level" => 0,
	"user_ip" => $_SERVER["REMOTE_ADDR"],
	"can_comment" => 1,
	"code" => $code
                                 ));
	  
	  $result = 1;
	  
	  if ( $code=="" AND $result) {
	    $uid = $db->lastInsertId(); 
	    $_SESSION["user_id"] = $uid;
	    $_SESSION["username"] = $username;
	    $_SESSION["email"]    = $email;
	    $_SESSION["level"]    = 0;
	    $_SESSION["can_comment"]    = 1;
	    $_SESSION["logged"]    = time();
	  
	    $LastLogin = $db->update(OSDB_USERS, array("user_last_login" => (int)time() ), 
		                                                                     "user_email = '".$email."'");
	  }
	  
	  //SEND EMAIL
	  if ($UserActivation == 1) {
	  	    $message = $lang["email_activation1"]." $username,<br />";
	        $message.= $lang["email_activation2"]." $website <br />";
			$message.= $lang["email_activation3"]."<br />";
			$message.= $website."?login&code=$code&e=$email<br />";
	        $message.="------------------------------------------<br />";
	        $message.="$website<br />";
	 
		    //$send_mail = mail($email, "Account Activation", $message, $headers);
			require("inc/class.phpmailer.php");
	        $mail  = new PHPMailer();
			$mail->CharSet = $lang["email_charset"];
			$mail->SetFrom($lang["email_from"], $lang["email_from_full"]);
			$mail->AddReplyTo($lang["email_from"], $lang["email_from_full"]);
			$mail->AddAddress($email, "");
			$mail->Subject = $lang["email_subject_activation"];
			$mail->MsgHTML($message);
			$mail->Send();
	       }
	   header("location: ".OS_HOME."?login&success");
	  }
   }
}
?>