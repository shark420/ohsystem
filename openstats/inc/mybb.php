<?php

//YOU NEED TO SET COOKIE PATH TO /   (one "forward slash")
// Go to: Admin CP -> Configuration -> Settings -> General Configuration -> set Cookie Path to /

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

if ( !is_logged() ) {

if (!defined('IN_MYBB') ) define('IN_MYBB', 1);

global $mybb, $lang, $query, $db, $cache, $plugins, $displaygroupfields;

   if ( file_exists($mybb_forum.'global.php') ) {
   require_once ($mybb_forum.'global.php');

   $mybb_username = $mybb->user["username"];
   $mybb_email = $mybb->user["email"];
   $mybb_avatar = $mybb_forum_url.$mybb->user["avatar"];
   $mybb_uid = $mybb->user["uid"];
   $mybb_logoutkey = $mybb->user["logoutkey"];
   $mybb_ismoderator = $mybb->user["ismoderator"];
   $LogOutUrl = $mybb_forum_url.'member.php?action=logout&logoutkey='.$mybb_logoutkey;
   
   $mybb_website = $mybb_forum_url."member.php?action=profile&uid=".$mybb_uid;
   
if ( $mybb_uid>=1 ) {
   
   require_once('inc/class.database.php');
   require_once('inc/db_connect.php');
   
   $check = $db->query("SELECT * FROM users WHERE user_email = '".$mybb_email."' AND other_id = '".$mybb_uid."'");
   
	if ( $db->num_rows($check)<=0 ) {
	//CREATE NEW USER (from mybb database)
	
	 $checkUn = $db->query("SELECT * FROM users WHERE (user_name) = ('".$mybb_username."') ");
	 if ( $db->num_rows($checkUn)>=1 ) {
	    $mybb_username = $mybb_username."_".$mybb_uid;
	 }
	
	   $pass = generate_hash(5);
       $hash = generate_hash(12);
	   $password_db = generate_password($pass, $hash); 
	   $insert = $db->query("INSERT INTO users(user_name, user_email, user_password, password_hash, user_joined, user_level, user_last_login, user_ip, user_avatar, other_id, user_website )
	   VALUES('".$mybb_username."', '".$mybb_email."', '".$password_db."', '".$hash."', '".(int) time()."', '0', '".(int) time()."', '".safeEscape($_SERVER["REMOTE_ADDR"])."', '".$mybb_avatar."', '".$mybb_uid."' , '".$mybb_website."' )");
	   $id = $db->get_insert_id();
	   $_SESSION["user_id"] = $id ;
	   $_SESSION["username"] =$mybb_username;
	   $_SESSION["email"]    = $mybb_email;
	   $_SESSION["level"]    = 0;
	   $_SESSION["can_comment"]    = 1;
	   $_SESSION["logged"]    = time();
	   $_SESSION["mybb"]    = $mybb_uid;
	   $_SESSION["logout"]    = $LogOutUrl;
	} else {
	  //UPDATE USER DATA
	  $row = $db->fetch_array($check,'assoc');
	  
	  $update = $db->query("UPDATE users SET 
	  user_last_login = '".(int) time()."',
	  user_avatar = '".$mybb_avatar."',
	  user_website = '".$mybb_website."'
	  WHERE user_id = '".$row["user_id"]."' 
	  LIMIT 1");
	  
	   $_SESSION["user_id"] = $row["user_id"] ;
	   $_SESSION["username"] = $row["user_name"];
	   $_SESSION["email"]    = $mybb_username;
	   $_SESSION["level"]    = $row["user_level"];
	   $_SESSION["can_comment"]    = 1;
	   $_SESSION["logged"]    = time();
	   $_SESSION["mybb"]    = $mybb_uid;
	   $_SESSION["logout"]    = $LogOutUrl;
	  }
   
   } 

  } else { die('File not exists: '.$mybb_forum.'global.php'); }
}
?>