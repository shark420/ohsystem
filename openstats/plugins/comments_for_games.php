<?php
//Plugin: Comments for single games
//Author: Ivan
//Allow comments on the single games page

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';

$PluginOptions = '0';

if ($PluginEnabled == 1  ) {

   if ( OS_single_game_page() ) {
     
	 AddEvent("os_display_custom_fields",  "OS_SingleGameComments");
	 AddEvent("os_start",  "OS_SubmitSingleGameComments");
   }
   
   function OS_SubmitSingleGameComments() {
   
     //DELETE COMMENT (ADMIN)
     if ( isset($_GET["delete_comment"]) AND OS_is_admin() AND isset($_GET["game"]) AND isset($_GET["user"]) ) {
	    $gid = safeEscape( (int) $_GET["game"]);
		$uid = safeEscape( (int) $_GET["user"]);
		$delField = safeEscape( $_GET["delete_comment"]);
		OS_delete_custom_field($uid, $delField);
		header('location: '.OS_HOME.'?game='.$gid.'&status=2#comments'); die;
	 }
	 
	 //SUBMIT COMMENT
	 if ( isset($_POST["game_add_comment"]) AND isset($_SESSION["code"]) AND isset($_POST["code"]) AND $_POST["code"] == $_SESSION["code"] AND isset($_POST["gid"]) AND $_POST["gid"] == $_GET["game"] ) {
	 
	    $gid = safeEscape( (int) $_GET["game"] );
		$text =  (PrepareTextDB( $_POST["post_comment"]));
		
		if ( strlen($text) <=2) { header('location: '.OS_HOME.'?game='.$gid.'&status=0#comments'); die; }
		
		//if we can get user ID, add comment in custom field table
		//field name = time_gameID_sgc . 
		//Example: 1361045379_1124_sgc (sgc - single game comment)
		
		$FieldName = time()."_".$gid."_sgc";
		if ( OS_GetUserID() )
		OS_add_custom_field( OS_GetUserID(), $FieldName , $text);
		
		$code = generate_hash(10);
		$_SESSION["code"] = $code;
		
		header('location: '.OS_HOME.'?game='.$gid.'&status=1#comments');
	    die;
	 }
	 
   }
   
   
   //GET ALL COMMENTS FOR SINGLE GAME
   function OS_GetSingleGameComments() {
     global $db;
	 global $lang;
	 global $DateFormat;
	 $gid = safeEscape( (int) $_GET["game"] );
	 
	 $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_CUSTOM_FIELDS." 
	 WHERE field_name LIKE  ? ");
	 
	 $sth->bindValue(1, "%".($gid)."_sgc", PDO::PARAM_STR);
	 $result = $sth->execute();
	 
	 $r = $sth->fetch(PDO::FETCH_NUM);
	 $numrows = $r[0];
	 $result_per_page = 10;
	 $draw_pagination = 0;
	 $website = OS_HOME;
	 $prefix ="?game=".$gid."";
	 $end = '#comments';
	 //echo $numrows;
	 include('inc/pagination.php');
	 $draw_pagination = 1;
	 
	 $sth = $db->prepare("SELECT f.field_id, f.field_name, f.field_value, u.user_id, u.user_name, u.user_level, u.user_ip, u.user_avatar, u.user_gender, u.can_comment, u.user_fbid, u.user_website
	 FROM ".OSDB_CUSTOM_FIELDS." as f
	 LEFT JOIN ".OSDB_USERS." as u ON u.user_id = f.field_id
	 WHERE f.field_name LIKE  ?
	 ORDER BY f.field_name DESC
	 LIMIT $offset, $rowsperpage");
	 
	 $sth->bindValue(1, "%".($gid)."_sgc", PDO::PARAM_STR);
	 $result = $sth->execute();
	 ?>
<div class="comments" id="comments">

<div class="comments-content">
<div id="comment-holder">
<ol>
	 <?php
	 while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	 $commentDate = explode("_", $row["field_name"] );
	 $date = $commentDate[0];
	 $gameID = $commentDate[1];
	 $CommentText = convEnt($row["field_value"]);
	 if ( strstr($CommentText , "@" ) AND strstr($CommentText , ", ") ) {
	 $CommentText  = preg_replace('#\@(.*?)\, #i', '<b>@\\1</b>, ', $CommentText );
	 }
	 
	 //load smilies if plugin "Smilies in comments" enabled
	 if ( function_exists('LoadSmilies') )
	 $CommentText  = LoadSmilies($CommentText);
?>
   <li class="comment">
	<div class="CommentWrapper">
	   <div class="comment-header padLeft">
<?php if (!empty($row["user_fbid"]) ) { ?>
<a href="http://www.facebook.com/profile.php?id=<?=$row["user_fbid"]?>" target="_blank"><img src="https://graph.facebook.com/<?=$row["user_fbid"]?>/picture" alt="*" width="64" height="64" class="imgvaligntop" /> <?=$row["user_name"]?></a>
<?php } else { ?>
	    <span class="<?php if ($row["user_level"] == 9) { ?>CommentAdmin<?php } ?>"><img src="<?=$row["user_avatar"]?>" alt="*" width="64" height="64" class="imgvaligntop" /> 
		<b><?=LinkUserProfile($row["user_name"], $row["user_website"])?></b>
		</span>, 
<?php } ?>
		<i><?=date($DateFormat, $date)?></i>
        <?php if (is_logged() AND OS_is_admin() ) { ?>
		<a style="float: right; padding-right: 10px;" href="javascript:;" onclick="if (confirm('Delete comment?') ) { location.href='<?=OS_HOME?>?game=<?=$gameID?>&delete_comment=<?=$row["field_name"]?>&user=<?=$row["field_id"]?>&hash=<?=generate_hash(10)?>#comments' }" >&times;</a>
		<?php } ?>
	   </div>
	   <div class="comment-content padLeft">
	   <?=$CommentText?>
	      <div class="CommentReply">
	        <a onclick="quote_user('<?=$row["user_name"]?>')" href="javascript:;"><?=$lang["reply"] ?></a></div>
	      </div>
	 </div>
  </li>
<?php
	 }
?>
</ol>
</div>
</div>
</div>
<?php
    include('inc/pagination.php');
   }
   
   //COMMENT FORM
   function OS_SingleGameComments() {
   global $lang;
   global $FBLogin;
   $code = generate_hash(10);
   $_SESSION["code"] = $code;
    ?>

<div class="clr"></div><a name="comments"></a>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed">
	 <div align="left" class="entry clearfix padLeft padTop">
	   <h2 class="title"><?=$lang["comments"]?></h2>
<?php
  //error 1 - comment has  too few characters
  if ( isset($_GET["status"]) ) {
    if ( $_GET["status"] == 0 ) { ?><h4>Comment has  too few characters (note: HTML not allowed)</h4><?php } 
	if ( $_GET["status"] == 1 ) { ?><h4>Comment was successfully added</h4><?php } 
	if ( $_GET["status"] == 2 AND OS_is_admin() ) { ?><h4>Comment was successfully deleted</h4><?php } 
  }
  //Display comments
  OS_GetSingleGameComments();
?>
	<form action="" method="post">
	<table>
<?php if ( !is_logged() ) { ?> 
	<tr>
	  <td class="padLeft padTop padBottom">
	     <?=$lang["comment_not_logged"]?>
		 <div class="font12">
<?php   if ($FBLogin == 1) { ?>
		 <a href="<?php echo OS_HOME; ?>?fb"><img src="<?=OS_HOME?>img/fb_connect_small.png" width="154" height="22" alt="FB CONNECT" class="imgvalign" /></a> &middot; 
<?php } ?> 
   		 <a href="<?=OS_HOME?>?login"><?=$lang["login_register"]?></a>
         </div>
	  </td>
	</tr>
<?php } else { ?>
    <tr>
	<td class="padLeft"><b><?=$lang["logged_as"]?></b> <?=$_SESSION["username"]?> <?php if (isset($_SESSION["phpbb"]) ) { ?><a class="font12" href="<?=OS_HOME?>?logout&amp;sid=<?=$_SESSION["sid"]?>"><?=$lang["logout"]?></a><?php } else { ?><a class="font12" href="<?=OS_HOME?>?logout"><?=$lang["logout"]?></a><?php } ?></td>
	</tr>
<?php } ?> 
	<tr>
	  <th width="810" class="alignleft padLeft"><?=$lang["add_comment"]?></th>
	</tr>
	<tr>
	  <td class="padLeft padTop padBottom">
	     <?=os_commentForm()?>
	  </td>
	</tr>
	<tr>
	  <td class="padLeft padTop padBottom">
	    <input <?php if ( !os_canComment() ) { ?>disabled<?php } ?> class="menuButtons" type="submit" value="<?=$lang["add_comment_button"]?>" name="game_add_comment" />
	  </td>
	</tr>
	</table>
	
	<input type="hidden" value="<?=(int)safeEscape( $_GET["game"] )?>" name="gid" />
	<input type="hidden" value="<?=$code?>" name="code" />
</form>
	   
	   <div style="margin-top: 40px;"></div>
	  </div> 
     </div>
    </div>
   </div>
  </div>
</div>
	<?php
   }

}
?>