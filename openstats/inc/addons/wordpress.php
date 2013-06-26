<?php

//ADD 
//   define('COOKIEPATH', '/'); 
//in wp.config.php


if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$wp_load = '1';

if ( !is_logged() ) {

   if ( file_exists( $wp_path.'wp-load.php' ) ) require( $wp_path.'wp-load.php' );

   
   global $current_user;
   $current_user = wp_get_current_user();
   $WP_ID = $current_user->ID;
   $WP_username = $current_user->user_login;
   $WP_user_email = $current_user->user_email;
   
   //print_r($current_user); // ___DEBUG___
      
   require_once('inc/common.php');
   require_once('inc/class.db.PDO.php');
   require_once('inc/db_connect.php');
   global $db;
   $check = $db->prepare("SELECT * FROM ".OSDB_USERS." 
	WHERE user_email = :WP_user_email AND phpbb_id = :phpbb_id");

	$check->bindValue(':WP_user_email', $WP_user_email, PDO::PARAM_STR); 
	$check->bindValue(':phpbb_id', (int)$WP_ID, PDO::PARAM_INT); 
	$result = $check->execute();

   		if ($check->rowCount()<=0 ) {
	    //CREATE NEW USER (from phpbb database)
	    $checkUn = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE LOWER(user_name) = LOWER('".$WP_username."') ");
		$result = $checkUn->execute();
	    if ( $checkUn->rowCount()>=1 ) {
	    $WP_username = $WP_username."_".$WP_ID;
		$pass = generate_hash(5);
		$hash = generate_hash(12);
		$password_db = generate_password($pass, $hash); 
		/*
		$sth = $db->prepare("INSERT INTO ".OSDB_USERS."(user_name, user_email, user_password, password_hash, user_joined, user_level, user_last_login, user_ip, user_avatar, phpbb_id, user_website )
	   VALUES('".$WP_username."', '".$WP_user_email."', '".$password_db."', '".$hash."', '".(int) time()."', '0', '".(int) time()."', '".safeEscape($_SERVER["REMOTE_ADDR"])."', '', '".$WP_ID."', '' )");
	   */
    $db->insert( OSDB_USERS, array(
	"user_name" => $WP_username,
	"user_email" => $WP_user_email,
	"user_password" => $password_db,
	"password_hash" => $hash,
	"user_joined" => (int) time(),
	"user_level" => '0', 
	"user_last_login" => (int) time(), 
	"user_ip" => $_SERVER["REMOTE_ADDR"], 
	"user_avatar" => '', 
	"phpbb_id" => $WP_ID, 
	"user_website" => '', 
                                 ));
	 
	   $id = $db->lastInsertId(); 
	   
	   $_SESSION["user_id"] = $id ;
	   $_SESSION["username"] =$WP_username;
	   $_SESSION["email"]    = $WP_user_email;
	   $_SESSION["level"]    = 0;
	   $_SESSION["can_comment"]    = 1;
	   $_SESSION["logged"]    = time();
	   $_SESSION["wp"]    = $WP_ID;
	   $logout = wp_logout_url();
	   $_SESSION["logout"]    = $logout;
	    }
		
	   }
	   else {
	  //UPDATE USER DATA
	  $row = $check->fetch(PDO::FETCH_ASSOC);
	  
	  $update = $db->prepare("UPDATE ".OSDB_USERS." SET 
	  user_last_login = '".(int) time()."' WHERE user_id = '".$row["user_id"]."' LIMIT 1");
	  
	  $result = $update->execute();
	  
	   $_SESSION["user_id"] = $row["user_id"] ;
	   $_SESSION["username"] = $row["user_name"];
	   $_SESSION["email"]    = $WP_user_email;
	   $_SESSION["level"]    = $row["user_level"];
	   $_SESSION["can_comment"]    = 1;
	   $_SESSION["logged"]    = time();
	   $_SESSION["smf"]    = $WP_ID;
	   $logout = wp_logout_url();
	   $_SESSION["logout"]    = $logout;
	  }


}
?>