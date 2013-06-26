<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

if ( !is_logged() ) {

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : $phpbb_forum ;
$phpEx = substr(strrchr(__FILE__, '.'), 1);
if ( file_exists( $phpbb_forum .'common.php' ) ) {
include($phpbb_forum .'common.php');
     $user->session_begin();
     $auth->acl($user->data);
     $user->setup();
	 
     $phpbb_sid = $user->data['session_id'];
	 $phpbb_path = $phpbb_forum;
	 $phpbb_avatar = $phpbb_forum_url."images/avatars/gallery/".$user->data['user_avatar'];
	 $phpbb_userID = $user->data['user_id'];
	 $phpbb_userType = $user->data['user_type'];
	 $phpbb_userEmail = $user->data['user_email'];
	 $phpbb_logoutURL = $phpbb_forum_url."ucp.php?mode=logout&sid=".$phpbb_sid;
     $phpbb_user = trim($user->data['username']);

if ( $phpbb_userID>=1 AND $phpbb_userType!=2 ) {

   require_once('inc/common.php');
   require_once('inc/class.db.PDO.php');
   //require_once('inc/db_connect.php');

   $OSDB = new db("mysql:host=".OSDB_SERVER.";dbname=".OSDB_DATABASE."", OSDB_USERNAME, OSDB_PASSWORD);
	 
	$sth = $OSDB->prepare("SELECT * FROM ".OSDB_USERS." 
	WHERE user_email = :phpbb_userEmail AND phpbb_id = :phpbb_userID");
	
   $sth->bindValue(':phpbb_userEmail', $phpbb_userEmail, PDO::PARAM_STR);
   $sth->bindValue(':phpbb_userID', $phpbb_userID, PDO::PARAM_INT);
   
   $result = $sth->execute();

	if ( $sth->rowCount()<=0 ) {
	//CREATE NEW USER (from phpbb database)
	
	 $sth = $OSDB->prepare("SELECT * FROM ".OSDB_USERS." WHERE LOWER(user_name) = :mybb_username ");
	 $sth->bindValue(':mybb_username', strtolower($mybb_username), PDO::PARAM_STR);
	 $result = $sth->execute();
	 if (  $sth->rowCount()>=1 ) {
	    $phpbb_user = $phpbb_user."_".$phpbb_userID;
	 }
	
	 $pass = generate_hash(5);
     $hash = generate_hash(12);
	 $password_db = generate_password($pass, $hash); 
	 
	 		$OSDB->insert(OSDB_USERS, array(
			  "user_name" => $phpbb_user,
			  "user_email" => $phpbb_userEmail,
			  "user_password" => $password_db,
			  "password_hash" => $hash,
			  "user_joined" => (int) time(),
			  "user_level" => 0,
			  "user_last_login" => (int)time(),
			  "user_ip" => $_SERVER["REMOTE_ADDR"],
			  "user_avatar" => $phpbb_avatar,
			  "phpbb_id" => $phpbb_userID
                                 ));
								 
	   $id = $OSDB->lastInsertId(); 
	   
	   $_SESSION["user_id"] = $id ;
	   $_SESSION["username"] =$phpbb_user;
	   $_SESSION["email"]    = $phpbb_userEmail;
	   $_SESSION["level"]    = 0;
	   $_SESSION["can_comment"]    = 1;
	   $_SESSION["logged"]    = time();
	   $_SESSION["phpbb"]    = $phpbb_userID;
	   $_SESSION["sid"]    = $phpbb_sid;
	   $_SESSION["logout"]    = $phpbb_logoutURL;
	} else {
	  //UPDATE USER DATA
	  $row = $sth->fetch(PDO::FETCH_ASSOC);
	  
		$result = $OSDB->update(OSDB_USERS, array(
		   "user_last_login" => (int) time(),
		   "user_avatar" => $phpbb_avatar
	                      ), "user_id = ".(int)$row["user_id"].""); 
	  
	   $_SESSION["user_id"] = $row["user_id"] ;
	   $_SESSION["username"] = $row["user_name"];
	   $_SESSION["email"]    = $phpbb_userEmail;
	   $_SESSION["level"]    = $row["user_level"];
	   $_SESSION["can_comment"]    = 1;
	   $_SESSION["logged"]    = time();
	   $_SESSION["phpbb"]    = $phpbb_userID;
	   $_SESSION["sid"]    = $phpbb_sid;
	   $_SESSION["logout"]    = $phpbb_logoutURL;
	  }
	
   }
	 
 }

}
?>