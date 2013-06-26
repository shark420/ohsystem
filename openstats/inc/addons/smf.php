<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

if ( !is_logged() ) {

  if ( file_exists( $smf_forum .'SSI.php' ) ) {
	 include($smf_forum .'SSI.php');
	 
	 global $context, $txt, $scripturl;
	 
	 if ($context["user"]["id"]>=1) {
	    $SMF_id = $context["user"]["id"]; 
		$SMF_session_var = $context['session_var'];
		$SMF_sid = &$context['session_id'];
	    $SMF_username = $context["user"]["name"]; 
		$SMF_email = $context["user"]["email"]; 
		$SMF_website = $smf_forum_url."index.php?action=profile;u=".$SMF_id; 
        $userID=loadMemberData($SMF_username, true, 'profile');
        loadMemberContext($userID[0]);
        $SMF_avatar = $memberContext[$userID[0]]['avatar']['href'];
		
        require_once('inc/common.php');
        require_once('inc/class.db.PDO.php');
        //require_once('inc/db_connect.php');

        $OSDB = new db("mysql:host=".OSDB_SERVER.";dbname=".OSDB_DATABASE."", OSDB_USERNAME, OSDB_PASSWORD);

         $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." 
		 WHERE user_email = :SMF_email AND smf_id = :SMF_id ");
		 
         $sth->bindValue(':SMF_email', $SMF_email, PDO::PARAM_STR);
         $sth->bindValue(':SMF_id', $SMF_id, PDO::PARAM_STR);
   
        $result = $sth->execute();
		 
		if ( $sth->rowCount()<=0 ) {
	    //CREATE NEW USER (from phpbb database)
	    $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE LOWER(user_name) = :SMF_username ");
	    $sth->bindValue(':SMF_username', strtolower($SMF_username), PDO::PARAM_STR);
	    $result = $sth->execute();
	    if ( $sth->rowCount()>=1 ) {
	    $SMF_username = $SMF_username."_".$SMF_id;
		$pass = generate_hash(5);
		$hash = generate_hash(12);
		$password_db = generate_password($pass, $hash); 
		
	 		$db->insert(OSDB_USERS, array(
			  "user_name" => $SMF_username,
			  "user_email" => $SMF_email,
			  "user_password" => $password_db,
			  "password_hash" => $hash,
			  "user_joined" => (int) time(),
			  "user_level" => 0,
			  "user_last_login" => (int)time(),
			  "user_ip" => $_SERVER["REMOTE_ADDR"],
			  "user_avatar" => $SMF_avatar,
			  "smf_id" => $SMF_id,
			  "user_website" => $SMF_website
                                 ));
		
		$insert = $db->query("INSERT INTO ".OSDB_USERS."(user_name, user_email, user_password, password_hash, user_joined, user_level, user_last_login, user_ip, user_avatar, smf_id, user_website )
	   VALUES('".$SMF_username."', '".$SMF_email."', '".$password_db."', '".$hash."', '".(int) time()."', '0', '".(int) time()."', '".safeEscape($_SERVER["REMOTE_ADDR"])."', '".$SMF_avatar."', '".$SMF_id."', '".$SMF_website."' )");
	   
	   $id = $db->lastInsertId();
	   $_SESSION["user_id"] = $id ;
	   $_SESSION["username"] =$SMF_username;
	   $_SESSION["email"]    = $SMF_email;
	   $_SESSION["level"]    = 0;
	   $_SESSION["can_comment"]    = 1;
	   $_SESSION["logged"]    = time();
	   $_SESSION["smf"]    = $SMF_id;
	   //$_SESSION["logout"]    = $smf_forum_url."?action=logout;".$SMF_session_var."=".$SMF_sid;
	   $logout = $scripturl . '?action=logout;' . $SMF_session_var . '=' . $SMF_sid ;
	   //Maybe SMF bug. Session verification not working...set forum link instead logout link.
	   $logout =$smf_forum_url;
	   $_SESSION["logout"]    = $logout;
	    }
		
	   }
	   else {
	  //UPDATE USER DATA
	  $row = $sth->fetch(PDO::FETCH_ASSOC);
	  
		$result = $db->update(OSDB_USERS, array(
		   "user_last_login" => (int) time(),
		   "user_avatar" => $SMF_avatar
	                      ), "user_id = ".(int)$row["user_id"].""); 
	  
	   $_SESSION["user_id"] = $row["user_id"] ;
	   $_SESSION["username"] = $row["user_name"];
	   $_SESSION["email"]    = $SMF_email;
	   $_SESSION["level"]    = $row["user_level"];
	   $_SESSION["can_comment"]    = 1;
	   $_SESSION["logged"]    = time();
	   $_SESSION["smf"]    = $SMF_id;
	   //$_SESSION["logout"]    = $smf_forum_url."?action=logout;".$SMF_session_var."=".$SMF_sid;
	   $logout = $scripturl . '?action=logout;' . $SMF_session_var . '=' . $SMF_sid ;
	   //Maybe SMF bug. Session verification not working on logout...set forum link instead logout link.
	   $logout =$smf_forum_url;
	   $_SESSION["logout"]    = $logout;
	  }

	   
	}
  }
}
?>