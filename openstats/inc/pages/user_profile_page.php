<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
   
   if ( isset($_POST["change_profile"]) ) {
      //$avatar = EscapeStr( trim($_POST["avatar"]));
	  $location = safeEscape( trim($_POST["location"]));
	  $realm = safeEscape( trim($_POST["realm"]));
	  $www = EscapeStr( trim($_POST["website"]));
	  $gender = EscapeStr( trim($_POST["gender"]));
	  
	  $lang = EscapeStr( trim($_POST["lang"]));
	  
	  $sql = "UPDATE ".OSDB_USERS." SET ";
	  
	  if ( !file_exists("lang/".$lang.".php") ) $lang = ""; else {
	  $sql.="user_lang = '".$lang."', ";
	  $_SESSION["user_lang"] = $lang;
	  }
	  
	  //User want to remove avatar
	  if ( isset($_POST["removeAvatar"] ) AND $_POST["removeAvatar"] == 1 ) {
	  $avatar = "";
	  $sql.="user_avatar = '".$avatar."', ";
	  //Check before delete avatar
	  $sth = $db->prepare("SELECT user_avatar FROM ".OSDB_USERS." WHERE user_name = :user_name ");
	  
	  $sth->bindValue(':user_name', $_SESSION["username"], PDO::PARAM_STR); 
	  $result = $sth->execute();
	  
	  if ( $sth->rowCount()>=1 ) {
	     $row = $sth->fetch(PDO::FETCH_ASSOC);
	     if ( strstr( $row["user_avatar"],  $website) ) {
		   $delAvatar = str_replace($website, "", $row["user_avatar"]);
		   if ( file_exists($delAvatar)  ) unlink( $delAvatar );
		 }
	    }
	  }
	  
	  //if ( is_valid_url($avatar) OR empty($avatar) )   $sql.="user_avatar = '".$avatar."', ";
	  //if ( strlen($location)>=3 )                
	  $sql.="user_location = '".$location."', ";
	  //if ( strlen($realm)>=3 )                   
	  $sql.="user_realm = '".$realm."', ";
	  if ( is_valid_url($www) OR empty($www) )         $sql.="user_website = '".$www."', ";
	  
	  if ( isset($_POST["pw_confirm"]) AND $_POST["pw_confirm"] == 1 ) {
	    $pass1 = $_POST["pw_1"];
		$pass2 = $_POST["pw_2"];
		
		if ( $pass1 == $pass2 ) $pwchange = 2; //Passwords not same
		if ( strlen($pass1)<3 ) $pwchange = 3; // Password have too few characters
		
		if ( $pass1 == $pass2 AND strlen($pass1)>=3 ) {
		   	$hash = generate_hash(16,1);
	     	$password_db = generate_password($pass2, $hash);
			$sql.="user_password = '".$password_db."', password_hash = '".$hash."', ";
			$pwchange = 1; //Password successfully changed
		}
	  }
	  
	  if ( $gender == 1) $gen = 1; else if ( $gender == 2) $gen = 2; else $gen = 0;
	  $sql.="user_gender = '".$gen."' ";
	  
	  $sql.=" WHERE user_name = '".$_SESSION["username"]."' ";
	  
	  $update = $db->prepare($sql);
	  $result = $update->execute();
	  
	  /* //=======================================================
	                         UPLOAD AVATAR
	  */ //=======================================================
	  if ( $AllowUploadAvatar == 1 AND isset( $_FILES["avatar_upload"] ) AND !empty( $_FILES["avatar_upload"]) ) {
	     $imagename = strtolower($_FILES['avatar_upload']['name']);
		 $fileExt = end( explode('.', $imagename) );
		 $savedName = generate_hash(4)."_".generate_hash(12).".".$fileExt;
         $source = $_FILES['avatar_upload']['tmp_name'];
         $target = "img/avatars/".$savedName;
		 //die($fileExt);
		 $allowtype = array('gif', 'jpg', 'jpe', 'jpeg', 'png');
         if (in_array($fileExt, $allowtype)) {
		   move_uploaded_file($source, $target);
		   list($width, $height) = getimagesize($target); 
		   if ( $width > $MaxImageSize ) $modwidth = $MaxImageSize; else $modwidth = $width;
           $diff = $width / $modwidth;
           $modheight = $height / $diff; 
		   
		   if ( $width>=8 AND $height>=8) {
           $tn = imagecreatetruecolor($modwidth, $modheight) ; 
		   
		   if ( $fileExt == "jpg")  $image = imagecreatefromjpeg($target); else
		   if ( $fileExt == "jpeg") $image = imagecreatefromjpeg($target); else 
		   if ( $fileExt == "gif" ) $image = imagecreatefromgif($target); else
		   if ( $fileExt == "png" ) $image = imagecreatefrompng($target);
		   //if ( $fileExt == "bmp" ) $image = imagecreatefromjpeg($target) ;
           imagecopyresampled($tn, $image, 0, 0, 0, 0, $modwidth, $modheight, $width, $height); 
		   
		   //SAVE IMAGE AND REMOVE UPLOADED
		   $NewSavedName = generate_hash(4)."_".generate_hash(12).".".$fileExt;
		   $NewTarget = "img/avatars/".$NewSavedName;
		   
		   if ( $fileExt == "jpg")  imagejpeg($tn, $NewTarget, $ImageQuality); else
		   if ( $fileExt == "jpeg") imagejpeg($tn, $NewTarget, $ImageQuality); else
           if ( $fileExt == "png" ) imagepng($tn,  $NewTarget); else
		   if ( $fileExt == "gif" ) imagegif($tn,  $NewTarget);
		   //if ( $fileExt == "bmp" ) imagejpeg($tn, $NewTarget);
		   
		   $user_avatar = $website.$NewTarget;
           $sth = $db->prepare("UPDATE ".OSDB_USERS." SET 
		   user_avatar = :user_avatar
		   WHERE user_name = :user_name LIMIT 1");
		   $sth->bindValue(":user_avatar", $user_avatar, PDO::PARAM_STR);
		   $sth->bindValue(":user_name", $_SESSION["username"], PDO::PARAM_STR);
		   
		   $result = $sth->execute();
		   
		   //REMOVE TEMP (ORIGINAL) FILE
		   if ( file_exists($target) ) unlink( $target );
		   }
		   
	     }
	  }
	  //END UPLOAD AVATAR
	  require_once(OS_PLUGINS_DIR.'index.php');
	  os_init();
  
	  if ( isset($pwchange) ) { header('location: '.OS_HOME.'?profile&pwchange='.$pwchange); die; }

	  header('location: '.OS_HOME.'?profile&updated'); die;
   }
   
   
      $c=0;
	  $ProfileData = array();
      $id = safeEscape( (int) $_SESSION["user_id"] );
	  $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_id = :userid AND code = ''");
	  
	  $sth->bindValue(':userid', $id, PDO::PARAM_INT);
	  
	  $result = $sth->execute();
	  
	  $row = $sth->fetch(PDO::FETCH_ASSOC);
	  
	  $ProfileData[$c]["id"]        = (int)($row["user_id"]);
	  $ProfileData[$c]["user_name"]  = ($row["user_name"]);
	  $ProfileData[$c]["user_email"]  = ($row["user_email"]);
	  $ProfileData[$c]["user_joined"]  = ($row["user_joined"]);
	  $ProfileData[$c]["user_level"]  = ($row["user_level"]);
	  $ProfileData[$c]["user_last_login"]  = ($row["user_last_login"]);
	  $ProfileData[$c]["user_ip"]  = ($row["user_ip"]);
	  $ProfileData[$c]["user_avatar"]  = ($row["user_avatar"]);
	  $ProfileData[$c]["user_location"]  = ($row["user_location"]);
	  $ProfileData[$c]["user_realm"]  = ($row["user_realm"]);
	  $ProfileData[$c]["user_website"]  = ($row["user_website"]);
	  $ProfileData[$c]["user_gender"]  = ($row["user_gender"]);
	  $ProfileData[$c]["user_fbid"]  = ($row["user_fbid"]);
	  $ProfileData[$c]["can_comment"]  = ($row["can_comment"]);	
	  
	  if ( !empty($row["user_lang"]) ) $ProfileData[$c]["user_lang"]  = ($row["user_lang"]);	else
	  $ProfileData[$c]["user_lang"]  = "english";	
	  $c=0;
	  
	  $UserLang = array();
	  
if ($handle = opendir("lang")) {
   while (false !== ($file = readdir($handle))) 
	{
	  if ($file !="." AND  $file !="index.html" AND $file !=".." AND strstr($file,".png")==false AND strstr($file,".css")==false AND strstr($file,".js")==false AND strstr($file,".php")==true ) {
	  
	  if (trim( str_replace(".php", "", $file) ) == trim( $ProfileData[0]["user_lang"] )) $UserLang[$c]["selected"] = 'selected="selected"';
	  else $UserLang[$c]["selected"] = "";
	  
	  $UserLang[$c]["lang"] = $file;
	  $UserLang[$c]["lang_name"] = str_replace(".php", "", $file);
	  
	  $c++;
	  }
	}
	
}
?>