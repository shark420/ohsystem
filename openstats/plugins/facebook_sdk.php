<?php
//Plugin: Facebook Login
//Author: Ivan
//Facebook Login

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';
//Enable edit plugin options
$PluginOptions = '1';

$ThisPlugin = basename(__FILE__, '');

if ($PluginEnabled == 1  ) {

if ( OS_is_admin() AND OS_PluginEdit( $ThisPlugin ) ) {

   if ( isset($_POST["fb_appid"]) ) {
   $fb_appid = strip_tags( (int) $_POST["fb_appid"]);
   $fb_enable = safeEscape($_POST["fb_enable"]);
   write_value_of('$FacebookAppID', "$FacebookAppID", $fb_appid , '../config.php' );
   write_value_of('$FBLogin', "$FBLogin", $fb_enable , '../config.php' );
   $FacebookAppID = $fb_appid;
   $FBLogin = $fb_enable;
   }
   
    $Option = "";
	
	if ( $FacebookAppID == 'FB Application ID' OR !is_numeric( $FacebookAppID ) ) {
	$Option.='<div style="color: red; font-weight:bold;" class="padLeft">You have not set Facebook Application ID.<div>Facebook login will not work.</div><a href="https://developers.facebook.com/apps" target="_blank">Click here to create Facebook App</a></div>';
	}
	
	$sel = array();
	if ($FBLogin == 1) $sel[0] = 'selected="selected"'; else $sel[0] = "";
	if ($FBLogin == 0) $sel[1] = 'selected="selected"'; else $sel[1] = "";
	if ($FBLogin == 0) $info = 'Facebook login must be enabled by default'; else $info= '';
    $Option.= '<form action="" method="post">
	<table>
	  <tr>
	    <td width="100">FB Application ID:</td>
		<td><input size="40" type="text" value="'.$FacebookAppID.'" name="fb_appid" /></td>
	  </tr>
	  <tr>
	    <td width="100">Enable FB Login:</td>
		<td><select name="fb_enable">
		<option '.$sel[0].' value="1">Enable</option>
		<option '.$sel[1].' value="0">Disable</option>
		</select><div>'.$info.'</div></td>
	  </tr>
	  <tr>
	    <td width="150"></td>
		<td>
		<input type="submit" value = "Submit" class="menuButtons" />
		<a href="'.OS_HOME.'adm/?plugins" class="menuButtons">&laquo; Back</a>
		</td>
	  </tr> 
	</table>
    </form>';
	

}

    if ( !is_logged() AND (OS_GetAction("facebook") OR isset($_GET["fb"]) )  ) {
	AddEvent("os_start",  "OS_RedirectFB"); 
	AddEvent("os_content","OS_FacebookLogin");
	AddEvent("os_start","OS_CheckFacebookLogin");
	}
	
	if ( OS_GetAction("facebook") AND is_logged() ) {
	  header('location:'.OS_HOME.''); die;
	}
	
	function OS_CheckFacebookLogin() {
	  if ( isset($_POST["fb_name"]) AND isset($_POST["fb_email"]) AND isset($_POST["fb_id"]) ) {
	    global $db;
		$errors = '';
		
		$FBID = ( trim($_POST["fb_id"]) );
		$gender = safeEscape( trim($_POST["fb_gender"]) );
		$name = strip_tags( trim($_POST["fb_name"]) );
		$email = safeEscape( trim($_POST["fb_email"]) );
		$IP = safeEscape($_SERVER["REMOTE_ADDR"]);
		$avatar = 'https://graph.facebook.com/'.$FBID.'/picture/?type=large';
		$www = 'http://www.facebook.com/profile.php?id='.$FBID.'';
		$pass = generate_hash(5);
        $hash = generate_hash(12);
	    $password_db = generate_password($pass, $hash); 
		
		if ( empty($FBID) OR strlen($FBID)<=6 ) $errors='1';
		if ( strlen($name)<=3 ) $errors='2';
		if ( strlen($email)<=6 ) $errors='3';
		
		if ( !empty($errors) ) {
		  header('location:'.OS_HOME.'?action=facebook&error='.$errors);
		  die;
		}
		
		if ($gender=="male")   $gen = 1; else
        if ($gender=="female") $gen = 2; else $gen = 0;
		
		$sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_fbid =:FBID AND user_email =:email");
		$sth->bindValue(':FBID', $FBID, PDO::PARAM_STR); 
		$sth->bindValue(':email', $email, PDO::PARAM_STR); 
		$result = $sth->execute();
		//echo $FBID ;
		//echo $db->num_rows($result);
		//NEW USER
		if ( $sth->rowCount()<=0 ) {
		
		//Check if username already exists
		$sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE LOWER(user_name) =:name ");
		$sth->bindValue(':name', strtolower($name), PDO::PARAM_STR); 
		if ( $sth->rowCount()>=1 ) $name.=" ".rand(100,1000);
		
    $db->insert( OSDB_USERS, array(
	"user_name" => $name,
	"user_fbid" => $FBID,
	"user_password" => $password_db,
	"password_hash" => $hash,
	"user_email" => $email,
	"user_joined" => (int) time(),
	"user_level" => 0,
	"user_last_login" => (int)time(),
	"user_ip" => $IP,
	"user_avatar" => $avatar,
	"user_website" => $www,
	"user_gender" => $gen
                                 ));
		
		 $id = $db->lastInsertId(); 
	    $_SESSION["user_id"] = $id ;
	    $_SESSION["username"] =$name;
	    $_SESSION["email"]    = $email;
	    $_SESSION["level"]    = 0;
	    $_SESSION["can_comment"]    = 1;
	    $_SESSION["logged"]    = time();
	    $_SESSION["fb"]    = $FBID;
	    header("location: ".OS_HOME.""); die;
		} else {
		//UPDATE USER DATA
		if ( $gen>=1 ) $sql_update = ", user_gender = '".(int)$gen."'"; else $sql_update = "";;
		$update = $db->prepare("UPDATE ".OSDB_USERS." SET user_last_login = '".time()."',user_avatar = '".strip_tags($avatar)."', user_website = '".strip_tags($www)."' $sql_update 
		WHERE user_email = '".$email."' AND user_fbid = '".$FBID."' LIMIT 1");
		
		$result = $update->execute();
		
	    $row = $sth->fetch(PDO::FETCH_ASSOC);
   	    $id = $row["user_id"];
	    $_SESSION["user_id"] = $id ;
	    $_SESSION["username"] =$row["user_name"];
	    $_SESSION["email"]    = $row["user_email"];
	    $_SESSION["level"]    = $row["user_level"];
	    $_SESSION["can_comment"]    = $row["can_comment"];
	    $_SESSION["logged"]    = time();
	    $_SESSION["fb"]    = $FBID;
	    header("location: ".OS_HOME.""); die;
		}
	  }
	}
	
	function OS_RedirectFB() {
	  if ( isset($_GET["fb"]) ) { header("location: ".OS_HOME."?action=facebook"); die; }
	}
	
	function OS_FacebookLogin() {
	global $FacebookAppID;
	?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
    <div class="content section">
     <div class="widget Blog">
      <div class="blog-posts hfeed padLeft">
	  
      <div style="margin-top: 30px;">&nbsp;</div>
	  
	  <?php
	  if ( isset($_GET["error"]) ) {
	    
		if ( $_GET["error"] == 1) { ?><h4>Identification of Facebook account is not valid</h4><?php }
		if ( $_GET["error"] == 2) { ?><h4>Facebook account name is not valid</h4><?php }
		if ( $_GET["error"] == 3) { ?><h4>Facebook account email is not valid</h4><?php }
	  }
	  ?>
<div id="fb-root"></div>
<script>
  window.fbAsyncInit = function() {
    // init the FB JS SDK
    FB.init({
      appId      : '<?=$FacebookAppID?>', // App ID from the App Dashboard
      channelUrl : '<?=OS_HOME?>/channel.html', // Channel File for x-domain communication
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
	   
	   document.getElementById("fb_image_show").innerHTML = '<img src="'+avatar+'" alt="" />';
	   document.getElementById("fb_image").value = avatar;
	   document.getElementById("fb_name").value = user_name;
	   document.getElementById("fb_name_show").innerHTML = user_name;
	   document.getElementById("fb_email").value = user_email;
	   document.getElementById("fb_id").value = response.id;
	   document.getElementById("fb_gender").value = response.gender;
	   document.getElementById("fb_logged").innerHTML = '';
	   document.getElementById("fb_welcome").innerHTML = '<h2>Welcome</h2>';
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

<div id="fb_logged"><fb:login-button show-faces="false" width="200" max-rows="1"></fb:login-button>
<div>Click on the button above to sign in with your FB account</div>
</div>

<div id="fb_welcome"></div>
<table>
   <tr>
     <td width="210"><div id="fb_image_show"></div></td>
	 <td>
	   <div id="fb_name_show"></div>
	   <form action="" method="post">
	     <input type="hidden" value="" id="fb_name" name="fb_name" />
		 <input type="hidden" value="" id="fb_email" name="fb_email" />
		 <input type="hidden" value="" id="fb_id" name="fb_id" />
		 <input type="hidden" value="" id="fb_gender" name="fb_gender" />
		 <input type="hidden" value="" id="fb_image" name="fb_image" />
		 <div><input type="submit" value="Click here to login with this account" class="menuButtons" /> </div>
	   </form>
	 </td>
   </tr>
</table>

<div style="margin-top: 360px;">&nbsp;</div>
     </div>
    </div>
   </div>
 </div>
</div>	  
	<?php
	}

}
?>