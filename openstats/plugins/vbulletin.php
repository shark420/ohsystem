<?php
//Plugin: VB Integration
//Author: Ivan
//vBulletin 4.x Integration

/*
INSTRUCTIONS:

open inc/common.php

find function 
strip_quotes

and replace with
os_strip_quotes

( line 899 and line 1275 )
*/
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';

$PluginOptions = '1';

$VBulletinPath = '../vbulletin/';
$ThisPlugin = basename(__FILE__, '');

define('VB_FORUM_PATH', $VBulletinPath);
define('FORUM_PATH_DIR', dirname( VB_FORUM_PATH.'global.php' ) ); //FIND PATH TO VB FORUM
define('FORUM_URL', 'http://localhost/vbulletin/'); //URL TO VB

if ($PluginEnabled == 1 AND file_exists($VBulletinPath."global.php") ) {
$Message = "";
//Change options
if ( isset($_POST["VBulletinPath"]) ) {
   $PATH = safeEscape($_POST["VBulletinPath"]);
   if ( file_exists("../".$PATH."global.php") ) {
   write_value_of('$VBulletinPath', "$VBulletinPath", $PATH , $plugins_dir.basename(__FILE__, '') );
   $VBulletinPath = $PATH;
   }
   else $Message = 'File <span style="color:red">'.$PATH."global.php". "</span> does not exist. <br />Path is not changed!";
}

//If user can edit plugin options
if ( OS_is_admin() AND OS_PluginEdit( $ThisPlugin ) ) {
//Show following options when user click on edit icon for this plugin
//Display all smilies
$Option = '
<form action="" method="post" >
  VB Path: <input size="30" type="text" value="'.$VBulletinPath.'" name="VBulletinPath" />
  <div class="padTop">
  <div>'.$Message.'</div>
  <input type="submit" value = "Save" class="menuButtons" />
  <a href="'.OS_HOME.'adm/?plugins" class="menuButtons">&laquo; Back</a>
  </div>
</form>';
}


if ( !is_logged() ) {
global $db;
$cwd = getcwd();
chdir(FORUM_PATH_DIR);
require_once('./global.php');
require_once('./includes/init.php'); // includes class_core.php
require_once('./includes/class_dm.php'); // for class_dm_user.php
require_once('./includes/class_dm_user.php'); // for user functions
require_once('./includes/functions.php'); // vbsetcookie etc.
require_once('./includes/functions_login.php'); // process login/logout
require_once('./includes/functions_user.php'); // enable us to sort out activation

chdir($cwd);

global $vbulletin;
//var_dump($vbulletin->userinfo); die;

$userID = $vbulletin->userinfo[userid];
if ($userID>=1) {
$userName = $vbulletin->userinfo[username];
$userEmail = $vbulletin->userinfo[email];
$userAvatar = FORUM_URL."/image.php?u=".$userID;
$VBWebsite = FORUM_URL."member.php?".$userID;
$LogOutUrl = FORUM_URL.'login.php?do=logout&logouthash='.$vbulletin->userinfo['logouthash'];
//$LogOutUrl = FORUM_URL."member.php?".$userID;

   $db = new db("mysql:host=".OSDB_SERVER.";dbname=".OSDB_DATABASE."", OSDB_USERNAME, OSDB_PASSWORD);
   $sth = $db->prepare("SET NAMES 'utf8'");
   $result = $sth->execute();
  
      //using |phpbb_id| field for user ID
	  $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_email = :user_email AND phpbb_id = :userID");
	  
	  $sth->bindValue(':user_email', $userEmail, PDO::PARAM_STR); 
	  $sth->bindValue(':userID', $userID, PDO::PARAM_INT); 
	  
      $result = $sth->execute();
	  
	if ( $sth->rowCount()<=0 ) {
  
  
     $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE LOWER(user_name) = :userName ");
	 $sth->bindValue(':userName', strtolower($userName), PDO::PARAM_STR); 
	 $result = $sth->execute();
	 if ( $sth->rowCount()>=1 ) {
	    $userName = $userName."_f".$userID;
	 }

	   $pass = generate_hash(5);
       $hash = generate_hash(12);
	   $password_db = generate_password($pass, $hash); 
	   
	 		$db->insert(OSDB_USERS, array(
			  "user_name" => $userName,
			  "user_email" => $userEmail,
			  "user_password" => $password_db,
			  "password_hash" => $hash,
			  "user_joined" => (int) time(),
			  "user_level" => 0,
			  "user_last_login" => (int)time(),
			  "user_ip" => $_SERVER["REMOTE_ADDR"],
			  "user_avatar" => $userAvatar,
			  "phpbb_id" => $userID,
			  "user_website" => $VBWebsite
                                 ));
	   $id = $db->get_insert_id();
	   $_SESSION["user_id"] = $id ;
	   $_SESSION["username"] =$userName;
	   $_SESSION["email"]    = $userEmail;
	   $_SESSION["level"]    = 0;
	   $_SESSION["can_comment"]    = 1;
	   $_SESSION["logged"]    = time();
	   $_SESSION["phpbb_id"]    = $userID;
	   $_SESSION["logout"]    = $LogOutUrl;
	 
	 
	} else {

	  $row = $sth->fetch(PDO::FETCH_ASSOC);
	  
		$result = $db->update(OSDB_USERS, array(
		   "user_last_login" => (int) time(),
		   "user_avatar" => $userAvatar,
		   "user_website" => $VBWebsite
	                      ), "user_id = ".(int)$row["user_id"].""); 

	   $_SESSION["user_id"] = $row["user_id"] ;
	   $_SESSION["username"] = $row["user_name"];
	   $_SESSION["email"]    = $userEmail;
	   $_SESSION["level"]    = $row["user_level"];
	   $_SESSION["can_comment"]    = $row["can_comment"];
	   $_SESSION["logged"]    = time();
	   $_SESSION["phpbb_id"]    = $userID;
	   $_SESSION["logout"]    = $LogOutUrl;
	} 

  }
 }
}
?>