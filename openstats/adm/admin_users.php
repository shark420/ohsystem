<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
$errors = "";
if ( isset($_GET["search_users"]) ) $s = safeEscape($_GET["search_users"]); else $s=""; 

?>
<div align="center" class="padBottom">
	 <form action="" method="get">
	 <table>
	   <tr>
	    <td width="290">
		  <input type="hidden" name="users" />
		  <input style="width: 180px; height: 24px;" type="text" name="search_users" value="<?=$s?>" />
		  <input class="menuButtons" type="submit" value="Search users" />
		</td>
	   </tr>
	 </table>
	 </form>
</div>
<?php
if ( isset($_GET["activate"]) AND is_numeric($_GET["activate"]) ) {
   $id = safeEscape( $_GET["activate"]);
   $update = $db->prepare("UPDATE ".OSDB_USERS." SET code = '' WHERE user_id = '".(int) $id."' LIMIT 1");
   $result = $update->execute();
} 

//delete
  if ( isset( $_GET["del"]) AND is_numeric($_GET["del"]) ) {
      $id = safeEscape( (int) $_GET["del"] );
	  $delete = $db->prepare("DELETE FROM ".OSDB_USERS." WHERE user_id ='".(int)$id."' LIMIT 1 ");
	  $result = $delete->execute();
	  //$delete = $db->query("DELETE FROM comments WHERE user_id ='".(int)$id."' ");
	  ?>
	  <div align="center">
	  <h2>User successfully deleted. <a href="<?=$website?>adm/?users">&laquo; Back</a></h2>
	  </div>
	  <?php 
  }
//eDIT
  if ( (isset( $_GET["edit"]) AND is_numeric($_GET["edit"]) ) OR isset($_GET["add"])  ) {
   $name = ""; $email = "";
   if ( isset($_GET["edit"])  AND is_numeric($_GET["edit"])  ) $id = safeEscape( (int) $_GET["edit"] ); else $id =0;
   //UPDATE
    if ( isset($_POST["edit_user"]) ) {
	  $name     = safeEscape( $_POST["name"]);
	  $email   = safeEscape( $_POST["email"]);
	  //if not root admin do not change access level
	  if ( $_SESSION["level"] <=9) {
	  $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_id = '".$id."' ");
	  $result = $sth->execute();
	  $UserROW = $sth->fetch(PDO::FETCH_ASSOC);
	  $level = $UserROW["user_level"]; 
	  
	    if ( isset($_POST["level"]) AND $level > $_POST["level"] ) {
	    $e_message = 'Only root admins can change user roles'; 
		
	    }
	  }
	  else 
	  $level   = safeEscape( $_POST["level"]);
	  $avatar     = strip_tags( $_POST["avatar"]);
	  $www        = strip_tags( $_POST["www"]);
	  $gender     = safeEscape( $_POST["gender"]);
	  $sql_update_pw = "";
	   
	  if ( isset( $_POST["chpw"]) AND $_POST["chpw"] == 1 AND !isset($_GET["add"]) ) {
	    $password = $_POST["password_"];
	    $password2 = $_POST["password_2"];
		
		if ( strlen($password)<=2 ) $errors.="<div>Field Password does not have enough characters</div>";
		if ($password!=$password2)  $errors.="<div>Password and confirmation password do not match</div>";
		
		if ( empty($errors) ) {
		  $hash = generate_hash(16,1);
		  $password_db = generate_password($password, $hash);
		  $sql_update_pw = ", user_password = '".$password_db."', password_hash = '".$hash."' ";
		}
		
	  }
	  
	  if ( isset($_GET["add"]) ) {
	    $password = $_POST["password_"];
	    $password2 = $_POST["password_2"];
		
		if ( strlen($password)<=2 ) $errors.="<div>Field Password does not have enough characters</div>";
		if ($password!=$password2)  $errors.="<div>Password and confirmation password do not match</div>";
		$hash = generate_hash(16,1);
		$password_db = generate_password($password, $hash);
		
	  }
	  
	  if ( strlen( $name)<=2 ) $errors.="<div>Field Name does not have enough characters</div>";
	  if (!preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i", $email)) 
	  $errors.="<div>E-mail address is not valid</div>";
	  
	  $time = date( "Y-m-d H:i:s", time() );
	  
	  if ( isset($_GET["edit"]) ) $sql = "UPDATE ".OSDB_USERS." SET 
	  user_name= '".$name."', user_email = '".$email."', user_level = '".$level."', user_website = '".$www."', user_avatar = '".$avatar."', user_gender = '".$gender."' 
	  $sql_update_pw 
	  WHERE user_id ='".$id."' LIMIT 1 ";
	  
	  if ( isset($_GET["add"]) ) $sql = "INSERT INTO ".OSDB_USERS."(user_name, user_email, user_password, password_hash, user_joined) VALUES('".$name."', '".$email."', '".$password_db."', '".$hash."', '".time()."')";
	  
	  $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE (user_name) = ('".$name."') AND user_id!='".$id."' ");
	  $result = $sth->execute();
	  if ( $sth->rowCount() >=1 )  $errors.="<div>Username already taken</div>";
	  
	  $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." 
	  WHERE (user_email) = ('".$email."') AND user_id!='".$id."' AND user_fbid!='' ");
	  $result = $sth->execute();
	  if ( $sth->rowCount() >=1 AND !isset($_GET["edit"]) )  $errors.="<div>E-mail already taken</div>";
	  
	  if ( empty($errors) ) {
	  $sth = $db->prepare($sql);
	  $result = $sth->execute();
	  $r=1;
	  if ( $r ) {
	  	  ?>
	  <div align="center">
	    <h2>User successfully updated. <a href="<?=$website?>adm/?users">&laquo; Back</a></h2>
		<?php if ( isset($e_message) AND !empty($e_message) ) echo $e_message; ?>
	  </div>
	  <?php 
	  }
	 } else {
	?>
	<div align="center"><?=$errors?></div>
	<?php
	}
	}
  
     if ( isset($_GET["edit"])  AND is_numeric($_GET["edit"])  ) {
	 $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_id = '".$id."' ");
	 $result = $sth->execute();
	 $row = $sth->fetch(PDO::FETCH_ASSOC);
	 $name       = ( $row["user_name"]);
	 $email      = ( $row["user_email"]);
	 $level      = ( $row["user_level"]);
	 $avatar     = ( $row["user_avatar"]);
	 $www        = ( $row["user_website"]);
	 $gender     = ( $row["user_gender"]);
	 $button = "Edit User";
	 } else { $button = "Add User"; $level = ""; $avatar  = ""; $www  = ""; $gender = "";}
	 ?>
	 
	 <form action="" method="post">
	 <div align="center">
	 <h2><?=$button?></h2>
	 <table>
	   <tr class="row">
	     <td width="80" class="padLeft">Name:</td>
		 <td><input name="name" style="width: 380px; height: 28px;" type="text" value="<?=$name ?>" /></td>
	   </tr>
	   <tr class="row">
	     <td width="80"  class="padLeft">E-mail:</td>
		 <td><input name="email" style="width: 380px; height: 28px;" type="text" value="<?=$email?>" /></td>
	   </tr>
	   <?php if ( !isset($_GET["add"]) ) { ?>
	   <tr class="row">
	     <td width="80"  class="padLeft">Password:</td>
		 <td>
		 <input type="checkbox" name="chpw" value="1" onclick="showhide('cw')" /> Change password?
		 <div id="cw" style="display: none;">
		   <div><input type="password" value="" name="password_" /></div>
		   <div>Confirm password:</div>
		   <div><input type="password" value="" name="password_2" /></div>
		 </div>
		 </td>
	   </tr>
	   <?php } else { ?>
	   <tr class="row">
	     <td width="80"  class="padLeft">Password:</td>
		 <td>
		   <div><input type="password" value="" name="password_" /></div>
		   <div>Confirm password:</div>
		   <div><input type="password" value="" name="password_2" /></div>
		 </td>
	   </tr>
	   <?php } ?>
	   <tr class="row">
	     <td width="80"  class="padLeft">Avatar:</td>
		 <td>
		 <input name="avatar" style="width: 380px; height: 28px;" type="text" value="<?=$avatar?>" />
		 <?php
		 if ( !empty($avatar) ) {
		 ?>
		 <a href="javascript:;" onclick="showhide('avatar')">Show avatar</a>
		 <div id="avatar" style="display:none">
		   <a href="<?=$avatar?>" target="_blank"><img src="<?=$avatar?>" width="320" alt="avatar" /></a>
		 </div>
		 <?php
		 }
		 ?>
		 </td>
	   </tr>
	   <tr class="row">
	     <td width="80"  class="padLeft">Website:</td>
		 <td><input name="www" style="width: 380px; height: 28px;" type="text" value="<?=$www?>" /></td>
	   </tr>
	   <tr class="row">
	     <td width="80"  class="padLeft">Gender:</td>
		 <td>
		 <select name="gender">
		   <option value="0" ></option>
		   <?php if ($gender == 1) $sel ='selected="selected"'; else $sel = ''; ?>
		   <option <?=$sel?> value="1" >Male</option>
		   <?php if ($gender == 2) $sel ='selected="selected"'; else $sel = ''; ?>
		   <option <?=$sel?> value="2" >Female</option>
		 </select>
		 </td>
	   </tr>
	   
	   <tr class="row">
	     <td width="80"  class="padLeft">Role:</td>
		 <td>
		 <div class="padTop"></div>
		 <select name="level">
		 <?php if ($level<=1) $sel='selected="selected"'; else $sel = ""; ?>
		   <option <?=$sel?> value="0">Member</option>
		 <?php
		 // only root admin can add admins 
		 if ( $_SESSION["level"] >9) $dis = "";   else $dis = "disabled";
		 ?>
		 <?php if ($level>=9 AND $level<10) $sel='selected="selected"'; else $sel = ""; ?>
		   <option <?=$sel.$dis?> value="9">Admin</option>
		<?php if ($level>=10) $sel='selected="selected"'; else $sel = ""; ?>
		   <option <?=$sel?> value="10">root</option>
		 </select>
		 <div class="padBottom"></div>
		 </td>
	   </tr>
	   <tr>
	      <td width="80">Last login:</td>
		  <td><?=date($DateFormat, $row["user_last_login"])?></td>
	   </tr>
	   <tr>
	     <td width="80"></td>
		 <td class="padTop padBottom">
		 <input type="submit" value="Submit" name="edit_user" class="menuButtons" />
		 <a class="menuButtons" href="<?=$website?>adm/?admins">&laquo; Back</a>
		 </td>
	   </tr>
	  </table>
	  </div>
	 </form>
	 <?php
  }
  
  if ( isset($_GET["search_users"]) AND strlen($_GET["search_users"])>=2 ) {
     $search_users = safeEscape( $_GET["search_users"]);
	 $sql = " AND (user_name) LIKE ('%".$search_users."%') ";
  } else {
   $sql = "";
   $search_users= "";
  }

  $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_USERS." WHERE user_id>=1 $sql ");
  $result = $sth->execute();
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  $result_per_page = 30;
  
  $ord = 'user_id DESC';
  $sel = array(); $sel[0]='';$sel[1]='';$sel[2]='';$sel[3]='';$sel[4]='';
  if ( isset($_GET["sort"]) AND $_GET["sort"] == "id" )     { $ord = 'user_id DESC'; $sel[0]='selected="selected"'; }else
  if ( isset($_GET["sort"]) AND $_GET["sort"] == "name" )   { $ord = '(user_name) ASC'; $sel[1]='selected="selected"'; } else
  if ( isset($_GET["sort"]) AND $_GET["sort"] == "email" )  { $ord = '(user_email) ASC'; $sel[2]='selected="selected"'; } else
  if ( isset($_GET["sort"]) AND $_GET["sort"] == "joined" ) { $ord = 'user_joined DESC'; $sel[3]='selected="selected"'; } else
  if ( isset($_GET["sort"]) AND $_GET["sort"] == "login" )  { $ord = 'user_last_login DESC'; $sel[4]='selected="selected"'; }
?>
<div align="center">
<div class="padBottom padTop">
        <form action="" method="get">
		<a class="menuButtons" href="<?=$website?>adm/?users&amp;add">[+] Add User</a>
		<input type="hidden" name="users" />
		 Sort by: <select name="sort">
		  <option <?= $sel[0]?> value="id">ID</option>
		  <option <?= $sel[1]?> value="name">Name</option>
		  <option <?= $sel[2]?> value="email">Email</option>
		  <option <?= $sel[3]?> value="joined">Joined</option>
		  <option <?= $sel[4]?> value="login">Last Login</option>
		 </select>
		 <input type="submit" value="Submit" class="menuButtons" />
		 </form>
</div>
<?php
  
  $draw_pagination = 1;
  $SHOW_TOTALS = 1;
  include('pagination.php');
  
   $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_id>=1 $sql 
   ORDER BY $ord LIMIT $offset, $rowsperpage");
   $result = $sth->execute();
   ?>
   <table>
    <tr>
	  <th width="220" class="padLeft">Username</th>
	  <th width="48">Action</th>
	  <th width="64" class="padLeft">Role</th>
	  <th width="48">Confirmed</th>
	  <th width="150" class="padLeft">Email <?php if ( isset($_GET["sort"]) AND $_GET["sort"] == "login" ) { ?>/ Last login <?php } ?></th>
	  <th width="150" class="padLeft">IP</th>
	  <th width="120">Joined</th>
	</tr>
   <?php
	 if ( file_exists("../inc/geoip/geoip.inc") ) {
	 include("../inc/geoip/geoip.inc");
	 $GeoIPDatabase = geoip_open("../inc/geoip/GeoIP.dat", GEOIP_STANDARD);
	 $GeoIP = 1;
	 }
    
	if ( isset($_GET["page"]) AND is_numeric($_GET["page"]) ) $p = '&amp;page='.(int) $_GET["page"]; else $p = '';
	
   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
   
	if ($GeoIP == 1 ) {
	$Letter   = geoip_country_code_by_addr($GeoIPDatabase, $row["user_ip"]);
	$Country  = geoip_country_name_by_addr($GeoIPDatabase, $row["user_ip"]);
	}
	
	if ( isset( $_GET["edit"] ) AND $row["user_id"] == $_GET["edit"] ) $border = 'style="border: 2px solid #BE0000;"'; else $border = "";
   ?>
   
   <tr class="row" style="height:36px;">
     <td width="220" class="padLeft font12" <?=$border?>>
	 <?php if (!empty($row["user_fbid"]) ) { ?>
	 <a href="http://www.facebook.com/profile.php?id=<?=$row["user_fbid"]?>" target="_blank">
	 <img class="imgvalign" src="https://graph.facebook.com/<?=$row["user_fbid"]?>/picture" alt="" width="32" height="32" />
	 </a>
	 <?php } else { 
	 if (!empty($row["user_avatar"]) AND is_valid_url( $row["user_avatar"] )  ) {
	 ?>
	 <img class="imgvalign" src="<?=$row["user_avatar"]?>" alt="" width="32" height="32" />
	 <?php } else { ?>
	 <img class="imgvalign" src="<?=$website?>img/avatar_64.png" alt="" width="32" height="32" />
	 <?php } 
	 } ?>
	 <a href="<?=$website?>adm/?users&amp;edit=<?=$row["user_id"].$p?>"><b><?=$row["user_name"]?></b></a>
	 </td>
	 <td width="48" class="font12">
	 <a href="<?=$website?>adm/?users&amp;edit=<?=$row["user_id"].$p?>"><img src="<?=$website?>adm/edit.png" alt="img" /></a>
	 <a href="javascript:;" onclick="if (confirm('Delete User?') ) { location.href='<?=$website?>adm/?users&amp;del=<?=$row["user_id"]?>' }"><img src="<?=$website?>adm/del.png" alt="img" /></a>
	 </td>
	 <td width="64" class="font12">
	 <?php if ($row["user_level"]>9) { ?>
	 <span style="color: rgb(199, 31, 31);">root</span>
	 <?php } else if ($row["user_level"]==9) { ?>
	 Admin
	 <?php } else { ?>
	 Member
	 <?php } ?>
	 </td>
	 <td width="48" class="font12">
	   <?php if (!empty($row["code"]) ) { ?><a href="<?=$website?>adm/?users&amp;activate=<?=$row["user_id"]?>">Activate</a><?php } else { ?>Y<?php } ?>
	 </td>
	 <td width="166" class="overflow_hidden font12"><span title="<?=$row["user_email"]?>"><?=stripslashes($row["user_email"])?></span>
	 <?php if ( isset($_GET["sort"]) AND $_GET["sort"] == "login" ) { 
	 ?>
	 <div style="font-size:11px;">
	 <?php 	 if ( date("Y", $row["user_last_login"])>=1990 ) { ?>
	 <div><b>Last login:</b></div>
	 <i><?=date($DateFormat, $row["user_last_login"])?></i>
	 <?php } else { ?><div><b>Last login:</b> <i>never</i></div><?php } ?>
	 </div>
	 <?php
	 
	 } ?>
	 </td>
	 <td width="150" class="overflow_hidden font12">
	 <span style="padding-right: 4px;"> <?php if ($GeoIP == 1 AND !empty($Letter) ) { ?><img src="<?=$website?>img/flags/<?=$Letter?>.gif" class="imgvalign" title="<?=$Country?>" alt="" /><?php } ?></span>
	<?=$row["user_ip"]?>
	 </td>
	 <td width="120" class="overflow_hidden font12"><?=date( $DateFormat, ($row["user_joined"]) )?></td>
    </tr>
   <?php 
   }
   if ( isset($GeoIP) AND $GeoIP == 1) geoip_close($GeoIPDatabase);
?>
  </table>
<?php
include('pagination.php');
?>
  </div>
  
  <div style="margin-top: 180px;">&nbsp;</div>