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
   
   require_once('inc/common.php');
   require_once('inc/class.db.PDO.php');
   //require_once('inc/db_connect.php');

   $OSDB = new db("mysql:host=".OSDB_SERVER.";dbname=".OSDB_DATABASE."", OSDB_USERNAME, OSDB_PASSWORD);
   
   $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_email = :mybb_email AND phpbb_id = :mybb_uid");
   $sth->bindValue(':mybb_email', $mybb_email, PDO::PARAM_STR);
   $sth->bindValue(':mybb_uid', $mybb_uid, PDO::PARAM_STR);
   
   $result = $sth->execute();
   
	if ( $sth->rowCount()<=0 ) {
	//CREATE NEW USER (from mybb database)
	
	 $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE LOWER(user_name) = :mybb_username ");
	 $sth->bindValue(':mybb_username', strtolower($mybb_username), PDO::PARAM_STR);
	 $result = $sth->execute();
	 
	 if ( $sth->rowCount()>=1 ) {
	    $mybb_username = $mybb_username."_".$mybb_uid;
	 }
	
	   $pass = generate_hash(5);
       $hash = generate_hash(12);
	   $password_db = generate_password($pass, $hash); 
	   
		$db->insert(OSDB_USERS, array(
			  "user_name" => $mybb_username,
			  "user_email" => $mybb_email,
			  "user_password" => $password_db,
			  "password_hash" => $hash,
			  "user_joined" => (int) time(),
			  "user_level" => 0,
			  "user_last_login" => (int)time(),
			  "user_ip" => $_SERVER["REMOTE_ADDR"],
			  "user_avatar" => $mybb_avatar,
			  "phpbb_id" => $mybb_uid,
			  "user_website" => $mybb_website
                                 ));
	   
	   $id = $db->lastInsertId(); 
	   
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
	  $row = $sth->fetch(PDO::FETCH_ASSOC);
	  
		   $result = $db->update(OSDB_USERS, array(
		   "user_last_login" => (int) time(),
		   "user_avatar" => $mybb_avatar,
		   "user_website" => $mybb_website
	                      ), "user_id = ".(int)$row["user_id"].""); 
	  
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