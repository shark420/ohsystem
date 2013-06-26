<?php 

if (isset($_GET["ref"]) AND strstr($_GET["ref"], "$website") )
$ref = urlencode($_GET["ref"]); else $ref = "";

if (   $FBLogin == 1) {

require 'inc/fb/facebook.php';
$facebook = new Facebook(array(
  'appId'  => $FacebookAppID,
  'secret' => $FacebookAppSecret,
  'cookie' => true,
));

$accessToken = $facebook->getAccessToken();
$user = $facebook->getUser();
$debug="";
if ($user) {
  try {
    // Proceed knowing you have a logged in user who's authenticated.
    $user_profile = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    error_log($e);
    $user = null;
  }
}

$params = array(
  'scope' => 'email',
  'redirect_uri' => $website.'/?fb'
);

// Login or logout url will be needed depending on current user state.
if ($user) {
  $logoutUrl = $facebook->getLogoutUrl();
} else {
  $loginUrl = $facebook->getLoginUrl($params);
}

if ($user){ 
  while (list($key, $value) = each($user_profile)) {
  $debug.= "<div>$key = $value</div>";
  if ($key=="name")     $name = $value;
  if ($key=="email")    $email = $value; 
  if ($key=="gender")   $gender = $value; else $gender = ""; 
  if ($key=="birthday") $birth = date("d- m-Y", strtotime($value)); else $birth = "";
      }
 $fb_code = sha1($user.$email);
// setcookie("fb_name",  $name,  time()+3600*6, "/");
 //setcookie("fb_email", $email, time()+3600*6, "/");
// setcookie("fb_id",    $user,  time()+3600*6, "/");
// setcookie("fb_token", $fb_code,  time()+3600*6, "/");
//if (!isset($_COOKIE["fb_name"]) )  setcookie("fb_name",  $name,  time()+3600, "/");
//if (!isset($_COOKIE["fb_email"]) ) setcookie("fb_email", $email, time()+3600, "/");
//if (!isset($_COOKIE["fb_id"]) )    setcookie("fb_id",    $user,  time()+3600, "/");
}

if ( !$user ) {
  ?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
    <div class="content section" id="content">
     <div class="widget Blog" id="Blog1">
      <div class="blog-posts hfeed padLeft">
	  
      <div style="margin-top: 30px;">&nbsp;</div>
	  
<div id="fb-root"></div>
<script>
  window.fbAsyncInit = function() {
    // init the FB JS SDK
    FB.init({
      appId      : '167972846578751', // App ID from the App Dashboard
      channelUrl : '//openstats.iz.rs/channel.html', // Channel File for x-domain communication
      status     : true, // check the login status upon init?
      cookie     : true, // set sessions cookies to allow your server to access the session?
      xfbml      : true  // parse XFBML tags on this page?
    });
 
  FB.login(function(response) {
      if (response.authResponse) {
     console.log('Welcome!  Fetching your information.... ');
     FB.api('/me', function(response) {
       console.log('Good to see you, ' + response.name + '.');
	   avatar = 'https://graph.facebook.com/'+response.id+'/picture?type=large';
	   www = 'http://www.facebook.com/profile.php?id='+response.id;
	   user_email  = response.email;
	   user_name   = response.name;
	   user_gender = response.gender;
	   //alert('Good to see you, ' + response.name + '.'+ response.email+' - '+response.id+' - '+response.gender+'- ' +response.birthday );
     });
   } else {
     console.log('User cancelled login or did not fully authorize.');
	 //alert('User cancelled login or did not fully authorize.');
   }
 }, {scope: 'email'});
 
	
  };

  // Load the SDK's source Asynchronously
  // Note that the debug version is being actively developed and might 
  // contain some type checks that are overly strict. 
  // Please report such bugs using the bugs tool.
  (function(d, debug){
     var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement('script'); js.id = id; js.async = true;
     js.src = "//connect.facebook.net/en_US/all" + (debug ? "/debug" : "") + ".js";
     ref.parentNode.insertBefore(js, ref);
   }(document, /*debug*/ false));
   

</script>
	  
<fb:login-button show-faces="false" width="200" max-rows="1"></fb:login-button>

      <a href="<?php echo $loginUrl; ?>"><img src="<?=$website?>img/fb_connect.png" width="300" height="50" alt="FB CONNECT" /></a>
      <div>Click on the button above to sign in with your FB account</div>
      <div style="margin-top: 360px;">&nbsp;</div>
	  
     </div>
    </div>
   </div>
 </div>
</div>
  <?php
} 

if ( $user AND isset($email) AND strlen($email)>=5 ) {
   $result = $db->query("SELECT * FROM users WHERE user_email = '".$email."' AND user_fbid = '".$user."' ");
   if ( $db->num_rows($result)<=0 ) {
     $pass = generate_hash(5);
     $hash = generate_hash(12);
	 $password_db = generate_password($pass, $hash); 
	 $avatar = 'https://graph.facebook.com/'.$user.'/picture?type=large';
     $www = 'http://www.facebook.com/profile.php?id='.$user.'';
	 if ($gender=="male")   $gen = 1; else
     if ($gender=="female") $gen = 2; else $gen = 0;
	 
     $insert = $db->query("INSERT INTO users(user_name, user_fbid, user_password, password_hash, user_email, user_joined, user_level, user_last_login, user_ip, user_avatar, user_website, user_gender) 
	 VALUES('".safeEscape($name)."', '".$user."', '".$password_db."', '".$hash."', '".safeEscape($email)."', '".(int)time()."', '0', '".(int)time()."', '".safeEscape($_SERVER["REMOTE_ADDR"])."', '".strip_tags($avatar)."', '".($www)."', '".$gen."')");
	  $id = $db->get_insert_id();
	  $_SESSION["user_id"] = $id ;
	  $_SESSION["username"] =$name;
	  $_SESSION["email"]    = $email;
	  $_SESSION["level"]    = 0;
	  $_SESSION["can_comment"]    = 1;
	  $_SESSION["logged"]    = time();
	  $_SESSION["fb"]    = $user;
	  header("location: ".$website."");
   } else {
      $avatar = 'https://graph.facebook.com/'.$user.'/picture';
      $www = 'http://www.facebook.com/profile.php?id='.$user.'';
	  if ($gender=="male")   $gen = 1; else
      if ($gender=="female") $gen = 2; else $gen = 0;
      
	  //UPDATE USER DATA
	  $update = $db->query("UPDATE users SET user_last_login = '".time()."',user_avatar = '".$avatar."', user_website = '".$www."' WHERE user_email = '".$email."' AND user_fbid = '".$user."' LIMIT 1");
   
      $row = $db->fetch_array($result,'assoc');
   	  $id = $row["user_id"];
	  $_SESSION["user_id"] = $id ;
	  $_SESSION["username"] =$row["user_name"];
	  $_SESSION["email"]    = $row["user_email"];
	  $_SESSION["level"]    = $row["user_level"];
	  $_SESSION["can_comment"]    = $row["can_comment"];
	  $_SESSION["logged"]    = time();
	  $_SESSION["fb"]    = $user;
	  ?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
    <div class="content section" id="content">
     <div class="widget Blog" id="Blog1">
      <div class="blog-posts hfeed padLeft">
	  
         <h4>You have successfully logged in</h4>
         <a href="<?=OS_HOME?>">Click here to continue</a>	
         <div style="margin-top: 360px;">&nbsp;</div>
		 
     </div>
    </div>
   </div>
 </div>
</div>	  
	  <?php
   }
   ?>
   <a href="<?php echo $logoutUrl; ?>">Logout</a>
   <?php
  } else if ($user AND ( !isset($email) OR strlen($email)<=5 ) ) echo "Unable to get account information from facebook.";

}
?>