<?php
//Plugin: PM System
//Author: Ivan
//Private message system

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '1';

if ($PluginEnabled == 1  ) {

    if ( is_logged() ) {
	AddEvent("os_add_menu_misc",  "OS_PMMenu"); 
	AddEvent("os_content",  "OS_PMSystem");
	}
	
	  if ( isset($_GET["inbox"]) )      $HomeTitle = "Inbox" ;
	  if ( isset($_GET["sent_items"]) ) $HomeTitle = "Sent items" ;
	  if ( isset($_GET["new_message"]) )$HomeTitle = "New Message" ;
	  if ( isset($_GET["send"]) )       $HomeTitle = "Send Private Message" ;
	  
	//Add "SEND PM" link to user page
	if ( OS_members_page() AND is_logged() ) {
	   AddEvent("os_display_custom_fields",  "OS_SendPMLink"); 
	}
	
	function OS_PMMenu() {
	 //BUG - or something must be fixed...link only
	global $db;
    //$db = new db("mysql:host=".OSDB_SERVER.";dbname=".OSDB_DATABASE."", OSDB_USERNAME, OSDB_PASSWORD);

	
	$sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_CUSTOM_FIELDS." as c WHERE c.field_name LIKE ('%__p.m.0') AND c.field_id = ? ");
	$sth->bindValue(1, OS_GetUserID(), PDO::PARAM_INT);
	$result = $sth->execute();
	$r = $sth->fetch(PDO::FETCH_NUM);
	$numrows = $r[0];
	
	if ( $numrows>=1 ) { ?>
	<li><a href="<?=OS_HOME?>?action=pm&amp;inbox"><b>(<?=$numrows?>) My Messages</b></a></li>
	<?php 
	if ( !OS_GetAction("pm") )
	AddEvent("os_content",  "OS_PMNewMessageNotification");
	} else { ?><li><a href="<?=OS_HOME?>?action=pm&amp;inbox">(<?=$numrows?>) My Messages</a></li><?php }
	
	?><li><a href="<?=OS_HOME?>?action=pm&amp;inbox">My Messages</a></li><?php
	}
	
	function OS_PMNewMessageNotification() {
	?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed padLeft padTop padBottom">
	    <h4><a href="<?=OS_HOME?>?action=pm&amp;inbox">You have a new message</a></h4>
	 </div>
    </div>
   </div>
  </div>
</div>
	<?php
	}
	
	//Add "SEND PM" link to comments
	if ( !empty($CommentsData) AND is_logged() ) {
	
	   for ($i=0; $i<count($CommentsData); $i++ ) {
	     $userID = $CommentsData[$i]["user_id"];
		 $CommentsData[$i]["text"].='<div style="float:right; display:block"> <a href="'.OS_HOME.'?action=pm&amp;send='.$userID.'">[Send PM]</a>&nbsp; </div>';
	   }
	   
	}
	///////////////////////////////////
   
   function OS_SendPMLink() {
    if ( OS_members_page() ) {
	global $User;
    ?><a href="<?=OS_HOME?>?action=pm&amp;send=<?=$User["id"]?>"> [Send PM] </a><?php
	}
   }
   
   function OS_GetUsernameByUserID( $uid) {
   //$db = new db("mysql:host=".OSDB_SERVER.";dbname=".OSDB_DATABASE."", OSDB_USERNAME, OSDB_PASSWORD);
   //$sth = $db->prepare("SET NAMES 'utf8'");
   //$result = $sth->execute();
	 global $db;
	 $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_id = ? LIMIT 1 ");
	 
	 $sth->bindValue(1, (int)$uid, PDO::PARAM_INT);
	 $result = $sth->execute();
	 $row = $sth->fetch(PDO::FETCH_ASSOC);
	 $username = $row["user_name"];
	 return $username;
   }
   
   function OS_PMSystem() {
      
   if ( OS_GetAction("pm") ) {
   global $db;
	  $sth = $db->prepare("SET NAMES 'utf8'");
	  $result = $sth->execute();
	  global $lang;
	  global $DateFormat;
	  $errors = "";
	  ?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed padLeft padTop padBottom">
    
	    <h2>Private Messages</h2>
		
		<div>
		<a class="menuButtons" href="<?=OS_HOME?>?action=pm&amp;inbox">INBOX</a> 
		<a class="menuButtons" href="<?=OS_HOME?>?action=pm&amp;sent_items">SENT ITEMS</a>
		<a class="menuButtons" href="<?=OS_HOME?>?action=pm&amp;new_message">NEW MESSAGE</a>
		</div>
		
		<?php 
		//NEW MESSAGE
		if (isset($_GET["new_message"]) ) {
		
		$PMName = ""; $PMText = "";
		
		  if ( isset($_POST["pm_message"]) AND isset($_POST["pm_name"]) AND isset($_SESSION["code"]) AND isset($_POST["code"])) {
		  $PMText= OS_StrToUTF8( $_POST['pm_message']);  
		  $PMText = PrepareTextDB( strip_tags($PMText) );
		  $PMName = safeEscape( trim($_POST["pm_name"]));
		  if ( $_SESSION["code"] != $_POST["code"] ) $errors.="<h4>Form is not valid. Try again.</h4>";
		  if ( strlen($PMText)<=2 ) $errors.="<h4>There are not enough characters  in the message</h4>";
		  if ( strlen($PMName)<=2 ) $errors.="<h4>Please, write a valid username</h4>";

		  if (strtolower($PMName) == $_SESSION["username"] ) 
		  $errors.="<h4>You can not send messages to yourself</h4>";
		  
		  if ( empty($errors) ) {
		    $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." 
			WHERE LOWER(user_name) = ? LIMIT 1");
			
			$sth->bindValue(1, strtolower($PMName), PDO::PARAM_STR);
			$result = $sth->execute();
			
			if ( $sth->rowCount()<=0 ) $errors.="<h4>User not found</h4>";
			else {
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$userID = $row["user_id"];
			}
		  }
		  
		  if ( !empty( $errors) ) echo $errors; else {
		  
		  if ( isset($userID) AND is_numeric($userID) AND $userID!= OS_GetUserID() ) {
		  OS_add_custom_field( $userID, time() . "_" . OS_GetUserID()."__p.m.0" , $PMText);
		  $PMName = ""; $PMText = "";
		  ?><h4>Message was sent successfully</h4><?php
		  }
		  else {
		  ?><h4>The message could not be sent</h4><?php
		  }
		  
		  }
		}
		
		$code = generate_hash(8);
		$_SESSION["code"] = $code;
		?>
		<form action="" method="post" accept-charset="UTF-8">
		  <table>
		    <tr class="row">
			  <td width="70" class="padLeft"><b>To:</b></td>
			  <td><input type="text" value="<?=$PMName?>" size="65" name="pm_name" /></td>
			</tr>
		    <tr class="row">
			  <td width="70" class="padLeft"><b>Message:</b></td>
			  <td><textarea name="pm_message" rows="9" cols="80" ><?=$PMText?></textarea></td>
			</tr>
		    <tr class="row">
			  <td width="70" class="padLeft"></td>
			  <td><input type="submit" value="Send PM" class="menuButtons" /></td>
			</tr>
		  </table>
		  <input type="hidden" name="code" value="<?=$code?>" />
		</form>
		<?php
		}
		
		//SEND MESSAGE (USER ID)
		if (isset($_GET["send"]) AND is_numeric($_GET["send"]) ) {
		$uid = safeEscape( (int) $_GET["send"] );
		
		if ( OS_GetUserID() ==  $uid) {
		?>
		<h4>You can not send messages to yourself</h4>
		<?php
		} else {
		
		if ( isset( $_POST["pm_message"] ) AND isset($_SESSION["code"]) AND isset($_POST["code"]) ) {

		   if ( $_SESSION["code"] != $_POST["code"] ) $errors.="<div>Form is not valid. Try again.</div>";
		   $PMText = htmlspecialchars( html_entity_decode($_POST['pm_message']), ENT_QUOTES, 'UTF-8');  
		   $PMText = PrepareTextDB( strip_tags($PMText) );
		   
		   if ( strlen($PMText)<=2 ) $errors.="<div>There are not enough characters  in the message</div>";

		
		 if ( !empty($errors) ) { ?><h4><?=$errors?></h4><?php } else {
		 //ADD MESSAGE
		 //ARG: TO - user ID, FROM - time_UserID, message
		 $sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_id = ? LIMIT 1");
		 
		 $sth->bindValue(1, $uid , PDO::PARAM_INT);
		 $result = $sth->execute();
		 if ( $sth->rowCount()>=1 )
		 OS_add_custom_field( $uid, time() . "_" . OS_GetUserID()."__p.m.0" , $PMText);
		 ?><h4>Message was sent successfully</h4><?php
		 }
		}

		
		
		$code = generate_hash(8);
		$_SESSION["code"] = $code;
		
		$sth = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_id = ? LIMIT 1"); 
		$sth->bindValue(1, $uid , PDO::PARAM_INT);
		$result = $sth->execute();
		
		if ( $sth->rowCount()>=1 ) {
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$sendTo = $row["user_name"];
		?>
		<form action="" method="post" accept-charset="UTF-8">
		  <table>
		    <tr class="row">
			  <td width="120" class="padLeft"><b>Send to:</b></td>
			  <td><?=$sendTo?></td>
			</tr>
		    <tr class="row">
			  <td width="120" class="padLeft"><b>Message:</b></td>
			  <td><textarea name="pm_message" rows="9" cols="80" ></textarea></td>
			</tr>
		    <tr class="row">
			  <td width="120" class="padLeft"></td>
			  <td><input type="submit" value="Send PM" class="menuButtons" /></td>
			</tr>
		  </table>
		  <input type="hidden" name="code" value="<?=$code?>" />
		</form>
		<?php 
		if ( isset($_GET["m"]) ) {
		   $sth = $db->prepare("SELECT * FROM ".OSDB_CUSTOM_FIELDS." WHERE field_name = ? ");
		   
		   $sth->bindValue(1, safeEscape($_GET["m"]), PDO::PARAM_STR);
		   $result = $sth->execute();
		   $row = $sth->fetch(PDO::FETCH_ASSOC);
		   $dateFor = explode("_", $row["field_name"]);
		   $date = (int)$dateFor[0];
		   
		   //print_r($dateFor);
		   ?>
		   <div class="padTop"></div>
		   <table>
		    <tr class="row">
		     <td class="padLeft"><b><?=$sendTo?></b>, <?=date($DateFormat, $date)?></td>
            </tr>
			<tr>
			  <td><?=convEnt($row["field_value"])?></td>
			</tr>
		   </table>
		   <?php
		}
		   } else { ?><h4>User not found</h4><?php }
		 }
		}
		
		//SENT ITEMS
		if ( isset($_GET["sent_items"]) AND is_logged() ) {
		
        ?><h4>Sent items</h4><?php		
		//GET ALL MESSAGES
		
		if ( !empty($_GET["sent_items"]) AND is_numeric($_GET["sent_items"]) AND isset($_GET["m"]) ) {
		  $id = safeEscape( (int) $_GET["sent_items"]);
		  $field = safeEscape( $_GET["m"]);
		  $sql = "AND c.field_name = ? ";
		} else $sql = "";
		
		$sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_CUSTOM_FIELDS." as c
		WHERE c.field_name LIKE ? $sql");
		
		$sth->bindValue(1, "%_".OS_GetUserID()."__p.m.%", PDO::PARAM_STR);
		
		if ( !empty($sql) ) $sth->bindValue(2, $field, PDO::PARAM_STR);
		
	    $result = $sth->execute();
   	    $r = $sth->fetch(PDO::FETCH_NUM);
		$numrows = $r[0];
		$result_per_page = 10;
		$offset = os_offset( $numrows, $result_per_page );
		
		$sth = $db->prepare("SELECT c.field_id, c.field_name, c.field_value, u.user_name, u.user_avatar
		FROM ".OSDB_CUSTOM_FIELDS."  as c
		LEFT JOIN ".OSDB_USERS." as u ON u.user_id = c.field_id
		WHERE c.field_name LIKE ? $sql
		ORDER BY c.field_name DESC
		LIMIT $offset, $result_per_page");
		
		$sth->bindValue(1, "%_".OS_GetUserID()."__p.m.%", PDO::PARAM_STR);
		
		if ( !empty($sql) ) $sth->bindValue(2, $field, PDO::PARAM_STR);
		
		$result = $sth->execute();
		?>
		<table>
		<?php
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
		$dateFor = explode("_", $row["field_name"]);
		$date = $dateFor[0];
		
		if (!isset($_GET["m"]) ) $text = limit_words( convEnt($row["field_value"]), 40); else $text = convEnt($row["field_value"]);
		?>
		<tr class="row">
		  <td width="140"><a href="<?=OS_HOME?>?action=pm&sent_items=<?=$row["field_id"]?>&amp;m=<?=$row["field_name"]?>"><b><?=$row["user_name"]?></b>, <?=date($DateFormat, $date)?></a></td>
		  <td><?=$text?> 
		  <?php if (isset($_GET["m"]) ) { ?>
		  <div class="padTop">
		  <a class="menuButtons" href="<?=OS_HOME?>?action=pm&send=<?=$row["field_id"]?>&amp;m=<?=$_GET["m"]?>">[SEND MESSAGE]</a>
		  <a class="menuButtons" href="<?=OS_HOME?>?action=pm&sent_items">&laquo; Back</a>
		  </div>
		  <?php } 
		  else { ?>
		  <a href="<?=OS_HOME?>?action=pm&sent_items=<?=$row["field_id"]?>&amp;m=<?=$row["field_name"]?>">more &raquo; </a>
		  <?php } ?>
		  </td>
		</tr>
		<?php
		}
		if ( $sth->rowCount()<=0 ) {
		?><tr><td>No new messages</td></tr><?php
		}
		?>
		</table>
		<?php
		os_pagination( $numrows, $result_per_page, 5, 1, '&amp;sent_items' );
		}
		
		//INBOX MESSAGES
		if ( isset($_GET["inbox"]) AND is_logged() ) {
		
        ?><h4>Inbox</h4><?php	
		  if ( !empty($_GET["inbox"]) AND is_numeric($_GET["inbox"]) AND isset($_GET["m"]) ) {
		  $id = safeEscape( (int) $_GET["inbox"]);
		  $field = safeEscape( $_GET["m"]);
		  $sql = "AND c.field_name = :field_name ";
		  $field_name = substr($field,0,-1)."1";
		} else $sql = "";
		
		$sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_CUSTOM_FIELDS." as c
		WHERE c.field_id = '".OS_GetUserID()."' $sql");

		//$sth->bindValue(':field_id', "%_".OS_GetUserID()."__p.m.%", PDO::PARAM_STR); 
		//$sth->bindValue(1, "%_".OS_GetUserID()."__p.m.%", PDO::PARAM_STR);
		
		if ( !empty($sql) ) $sth->bindValue(':field_name', $field, PDO::PARAM_STR); 
		
		//$sth->bindValue(2, $field, PDO::PARAM_STR);
		$result = $sth->execute();
		
		$r = $sth->fetch(PDO::FETCH_NUM);
		$numrows = $r[0];
		
		$result_per_page = 10;
		$offset = os_offset( $numrows, $result_per_page );
		
		$sth = $db->prepare("SELECT c.field_id, c.field_name, c.field_value, u.user_name, u.user_avatar
		FROM ".OSDB_CUSTOM_FIELDS."  as c
		LEFT JOIN ".OSDB_USERS." as u ON u.user_id = c.field_id
		WHERE c.field_id = '".OS_GetUserID()."'
		AND field_name LIKE('%__p.m.%')
		$sql
		ORDER BY c.field_name DESC
		LIMIT $offset, $result_per_page");
		
		//$sth->bindValue(':field_id', "%_".OS_GetUserID()."__p.m.%", PDO::PARAM_STR);

		if ( !empty($sql) ) $sth->bindValue(':field_name', $field, PDO::PARAM_STR); 
		$result = $sth->execute();
		//UPDATE "read" message
		
		if ( !empty($_GET["inbox"]) AND is_numeric($_GET["inbox"]) AND isset($_GET["m"]) ) {
		   $field = safeEscape( $_GET["m"]);
		   $field_name = substr($field,0,-1)."1";

		   $result = $db->update(OSDB_CUSTOM_FIELDS, array(
		   "field_name" => $field_name
	                      ), "field_name = '".$field."'"); 
		}
		?>
		<table>
		<?php
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
		$dateFor = explode("_", $row["field_name"]);
		$date = $dateFor[0];
		$FromID = $dateFor[1];
		
		$read = substr($row["field_name"], strlen($row["field_name"])-1, 1 );
        
		if ($read == 1) { $col = '686A6B'; $readTxt = 'read'; } 
		else { $col = 'A41600'; $readTxt = '<b>new</b>';  }
		
		if (!isset($_GET["m"]) ) {
		$text = limit_words(convEnt($row["field_value"]), 12); 
		
		if ($read == 0) $text = '<span style="color: #000;"><b>'.convEnt($text) .'<b/></span>';
		if ($read == 1) $text = '<span style="color: #686A6B;">'.convEnt($text) .'</span>';
		
		} else $text = convEnt($row["field_value"]);

		
		?>
		 <?php if (!isset($_GET["m"]) ) { ?>
		 <tr class="row">
		   <td width="120" class="padLeft">
		   <a href="<?=OS_HOME?>?action=pm&inbox=<?=$FromID?>&amp;m=<?=$row["field_name"]?>"><span style="color: #<?=$col?>"><b><?=OS_GetUsernameByUserID($FromID)?></b></span></a>
		   </td>
		   <td width="600"><a href="<?=OS_HOME?>?action=pm&inbox=<?=$FromID?>&amp;m=<?=$row["field_name"]?>"><?=($text)?></a></td>
		   <td><?=date($DateFormat, $date)?></td>
		 </tr>
		 <?php } else { ?>
		 <tr class="row">
		    <td class="padLeft"><span style="color: #<?=$col?>"><b><?=OS_GetUsernameByUserID($FromID)?></b>, <?=date($DateFormat, $date)?></span></td>
		 </tr>
		 <tr>
		    <td><?=($text)?></td>
		 </tr>
		 <tr>
		   <td><div class="padTop padBottom">
		  <a class="menuButtons" href="<?=OS_HOME?>?action=pm&send=<?=$FromID?>&amp;m=<?=$_GET["m"]?>">[SEND MESSAGE]</a>
		  <a class="menuButtons" href="<?=OS_HOME?>?action=pm&inbox">&laquo; Back</a>
		  </div></td>
		 </tr>
		 <?php } ?>
		<?php
		}
		if ( $sth->rowCount()<=0) {
		?><tr><td>No new messages</td></tr><?php
		}
		?>
		</table>
		<?php
		os_pagination( $numrows, $result_per_page, 5, 1, '&amp;inbox' );
		}
	    ?>
		<div class="padTop" style="margin-top:124px;"></div>
	 </div>
    </div>
   </div>
  </div>
</div>	 
	  <?php
	  }
	  
   }
}