<?php
//Plugin: Guests comments
//Author: Ivan
//Enable comments for guests

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';

//$PluginOptions = '1';

if ($PluginEnabled == 1  ) {


  if ( !is_logged() ) {
  
   AddEvent("os_start",           "OS_GuestNewComment");
   AddEvent("os_start",           "OS_AllowGuestComments", 2);
   AddEvent("os_comment_form",    "OS_AddGuestNameField",  2);
   //Remove message: "You need to be logged in to post a comment."
   $lang["comment_not_logged"] = "";
   }
   
   //FUNCTIONS
   function OS_AllowGuestComments() {
     define('OS_ALWAYS_ENABLE_TEXTAREA', '1');
   }
   
   function OS_AddGuestNameField() {
    if ( isset($_COOKIE["os_guest"] ) ) $username = trim( safeEscape($_COOKIE["os_guest"])  );
	else $username = "";
    ?>
	<a href="javascript:;" name="GuestComment"></a>
	<div style="margin-bottom:9px;">Your name: <input type="text" value="<?=$username?>" name="GuestName" class="field" /> </div>
	<?php
   }
   
   //NEW COMMENT
   function OS_GuestNewComment() {
   
     if ( isset($_POST["post_comment"]) ) {
	 
	 //os_trigger_error( "aaaaaaaaaa");
	   global $lang;
	   global $db;
	   $errors = "";
	   $text =  ($_POST["post_comment"]);
	   $id = safeEscape( (int) $_GET["post_id"]);
	   $GuestName = safeEscape( trim($_POST["GuestName"]) );
	   
	   $backTo = OS_HOME.'?post_id='.safeEscape($id )."&amp;".generate_hash(12)."#GuestComment";
	   
	   if ( $id<=0 ) $errors.="<div>".$lang["error_invalid_form"]."</div>";
	   if (strlen($GuestName  )<2) $errors.="<div>Field name have too few characters.</div>";
	   if ( $_SESSION["code"] != $_POST["code"])  $errors.="<div>".$lang["error_invalid_form"]."</div>";
	   if ( $_POST["pid"] != $id )                $errors.="<div>".$lang["error_invalid_form"]."</div>";
	   if ( strlen($text)<=3 )   $errors.="<div>".$lang["error_text_char"] ."</div>";
	   
	   $sth = $db->prepare("SELECT * FROM ".OSDB_NEWS." WHERE news_id =:id AND allow_comments = 1");
	   $sth->bindValue(':id', (int) $id, PDO::PARAM_INT); 
	   $result = $sth->execute();
	   if ( $sth->rowCount()<=0 ) $errors.="<div>".$lang["error_comment_not_allowed"]."</div>";
	   
	   if ( !empty($errors) ) { 
	   $errors.='<div><a href="'.$backTo.'">&laquo; Back</a></div>';
	   os_trigger_error( $errors); 
	   } else {
	     //INSERT NEW COMMENT
		 
		 //Set cookie for guest username
		 @setcookie("os_guest", $GuestName, time()+3600*24*7, "/");
		 
		 $textDB = PrepareTextDB("<b>$GuestName</b>".":<br /> ".$text);
		 
		$db->insert( OSDB_COMMENTS, array(
		"user_id" => 0,
		"page" => 'news',
		"post_id" => (int) $id,
		"text" => $textDB,
		"date" => (int) time(),
		"user_ip" => $_SERVER["REMOTE_ADDR"]
                                 ));
	 
	     $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_COMMENTS." WHERE post_id=:id LIMIT 1");
		 $sth->bindValue(':id', (int) $id, PDO::PARAM_INT); 
		 $result = $sth->execute();
	     $r = $sth->fetch(PDO::FETCH_NUM);
	     $TotalComments = $r[0];
		 
		   $result = $db->update(OSDB_NEWS, array(
		   "comments" => $TotalComments
	                                    ), "news_id = ".(int)$id."");
		 
		 	 if ( $result ) {
	         header("location: ".$website."?post_id=".$id."#comments"); die;
	         }
	 
	   } //INSERT comment
	 } //end POST comment
   
   } //end function: OS_GuestNewComment
}
?>