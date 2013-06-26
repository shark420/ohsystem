<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$code = generate_hash(8);
$_SESSION["code"] = $code;
if ( isset($errors) AND !empty($errors) ) { ?>
<div><?=$errors?></div>
<?php
}
?><a name="comments"></a><?php

if ( isset($CommentsData) AND !empty($CommentsData) ) {
?>
<div class="comments" id="comments">
<h4><?=$lang["comments"]?> (<?=$CommentsData[0]["total_comments"]?>)</h4>
<div class="comments-content">
<div id="comment-holder">
<ol>
<?php
   foreach($CommentsData as $Comment) {
   ?>
   <li class="comment">
	<div class="CommentWrapper">
	   <div class="comment-header padLeft">
<?php if (!empty($Comment["fb"]) ) { ?>
<a href="http://www.facebook.com/profile.php?id=<?=$Comment["fb"]?>" target="_blank"><img src="https://graph.facebook.com/<?=$Comment["fb"]?>/picture" alt="*" width="64" height="64" class="imgvaligntop" /> <?=$Comment["username"]?></a>
<?php } else { ?>
	    <span class="<?php if ($Comment["user_level"] == 9) { ?>CommentAdmin<?php } ?>"><img src="<?=$Comment["avatar"]?>" alt="*" width="64" height="64" class="imgvaligntop" /> 
		<b><?=LinkUserProfile($Comment["username"], $Comment["website"])?></b>
		</span>, 
<?php } ?>
		<i><?=$Comment["date"]?></i>
        <?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
		<a style="float: right; padding-right: 10px;" href="javascript:;" onclick="if (confirm('Delete comment?') ) { location.href='<?=OS_HOME?>?post_id=<?=$Comment["post_id"]?>&delete_comment=<?=$Comment["id"]?>' }" >&times;</a>
        | <a style="padding-left: 10px; font-size:11px;" href="<?=OS_HOME?>adm/?comments&amp;edit=<?=$Comment["id"]?>">edit comment</a>
		<?php } ?>
	   </div>
	   <div class="comment-content padLeft">
	   <?=$Comment["text"]?>
	      <div class="CommentReply">
	        <a onclick="quote_user('<?=$Comment["username_clean"]?>')" href="javascript:;"><?=$lang["reply"] ?></a></div>
	      </div>
	 </div>
  </li>
  <div class="padTop"></div>
   <?php
   }
?>
</ol>
</div>
</div>
</div>
<?php
$numrows = $total_comments;
$result_per_page = $CommentsPerPage;
include('inc/pagination.php');
}
//COMMENT FORM
if ($CommentsAllowed == 1 ) {
?>
<div class="padTop"></div>
<form action method="post">
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
	    <input <?php if ( !os_canComment() ) { ?>disabled<?php } ?> class="menuButtons" type="submit" value="<?=$lang["add_comment_button"]?>" name="add_comment" />
	  </td>
	</tr>
	</table>
	
	<input type="hidden" value="<?=(int)safeEscape( $_GET["post_id"] )?>" name="pid" />
	<input type="hidden" value="<?=$code?>" name="code" />
</form>
<?php } ?>