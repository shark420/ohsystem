<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div align="center"> 

<?php 
if ( isset($_GET["com"])  AND is_numeric($_GET["com"]) ) {
  $type = safeEscape( (int) $_GET["com"] );
  $sth = $db->prepare("UPDATE ".OSDB_NEWS." SET allow_comments = '".$type."' ");
  $result = $sth->execute();
}

if ( isset($_GET["publish"])  AND is_numeric($_GET["publish"]) ) {
  $id = safeEscape( (int) $_GET["publish"] );
  $sth = $db->prepare("UPDATE ".OSDB_NEWS." SET status = 1 WHERE news_id = '".$id."' LIMIT 1");
  $result = $sth->execute();
}
if ( isset($_GET["draft"])  AND is_numeric($_GET["draft"]) ) {
  $id = safeEscape( (int) $_GET["draft"] );
  $sth = $db->prepare("UPDATE ".OSDB_NEWS." SET status = 0 WHERE news_id = '".$id."' LIMIT 1");
  $result = $sth->execute();
}
//delete
  if ( isset( $_GET["del"]) AND is_numeric($_GET["del"]) ) {
      $id = safeEscape( (int) $_GET["del"] );
	  $sth       = $db->prepare("DELETE FROM ".OSDB_NEWS." WHERE news_id ='".(int)$id."' LIMIT 1 ");
	  $result = $sth->execute();
	  $sth2 = $db->prepare("DELETE FROM ".OSDB_COMMENTS." WHERE post_id ='".(int)$id."' LIMIT 1 ");
	  $result = $sth2->execute();
	  ?>
	  <div align="center">
	  <h2>Post successfully deleted. <a href="<?=$website?>adm/?posts">&laquo; Back</a></h2>
	  </div>
	  <?php 
  }
   
  //ADD / EDIT POST
  if ( isset( $_GET["add"]) OR (isset($_GET["edit"]) AND is_numeric($_GET["edit"]) ) ) {
  
  if ( isset($_POST["add_post"]) ) {
     $title = EscapeStr($_POST["post_title"]);
	 $status = EscapeStr( (int) $_POST["status"]);
	 $allow_comments = EscapeStr( (int) $_POST["allow_comments"]);
	 $text = (my_nl2br(convEnt2(trim($_POST["post_text"]))));
	 $text = str_replace(array("&Scaron;", "&scaron;"),array("Š","š"), $text   );
	 $errors ="";
	 $time = time();
	 
	 $author = EscapeStr( (int) $_POST["author"]);
	 
	 if ( strlen($title)<=3 ) $errors.="<div>Field Title does not have enough characters</div>";
	 if ( strlen($text)<=5 )  $errors.="<div>Field Text does not have enough characters</div>";
	 
	 if ( empty($errors) ) {
	    
		if ( isset($_GET["add"]) ) {
		$ins = 1;
		$insert = $db->prepare("INSERT INTO ".OSDB_NEWS."(news_title, news_content, news_date, status, allow_comments, author)
		VALUES('".$title."', '".$text."', '".$time."', '".$status."', '".$allow_comments."', '".$author."') ");
		$result = $insert->execute();
		
		if ( $ins) {
		?>
	    <div align="center">
	       <h2>Post successfully added. <a href="<?=$website?>adm/?posts">&laquo; Back</a></h2>
	    </div>		
		<?php
		}
	}
		
		if ( isset($_GET["edit"]) ) {
		$id = safeEscape( (int) $_GET["edit"]);
		$upd = 1;
		$update = $db->prepare("UPDATE ".OSDB_NEWS." SET 
		news_title = '".$title."', 
		news_content = '".$text."', 
		status='".$status."', 
		allow_comments = '".$allow_comments."',
		author = '".$author."'
		WHERE news_id = '".$id."' ");
		$result = $update->execute();
		
	    if ( $upd) {
		?>
	    <div align="center">
	       <h2>Post successfully updated. <a href="<?=$website?>adm/?posts">&laquo; Back</a></h2>
	    </div>		
		<?php
		}
		
		}
		
	 }
  }
  
  if ( isset($_GET["edit"]) AND is_numeric($_GET["edit"]) ) {
    $id = safeEscape( (int) $_GET["edit"]);
	$sth  = $db->prepare("SELECT n.*, u.user_name
	FROM ".OSDB_NEWS." as n 
	LEFT JOIN ".OSDB_USERS." as u ON u.user_id = n.author
	WHERE n.news_id = '".$id."' LIMIT 1 ");
	
	$result = $sth->execute();
	
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$title = $row["news_title"];
	$text = $row["news_content"];
	$status = $row["status"];
	$allow_comments = $row["allow_comments"];
	$author = $row["author"];
  } else {
    $title = "";
	$text  = "";
	$status = 1;
	$allow_comments = $AllowComments;
	$author = 1;
  }
?>
<form action="" method="post">
  <table>
  <tr>
  <td class="padLeft">
  Post Title: <input style="width: 500px; height: 34px; background-color: #fafafa; color: #000;" type="text" value="<?=$title?>" name="post_title" size="75" maxlength="254" />
  
  </td>
  </tr>
  <tr>
  <td align="center">
  <textarea class="ckeditor" cols="90" id="editor1" name="post_text" rows="20"><?=$text?></textarea>
  </td>
  </tr>
    <tr>
  <td class="padLeft">
  <div class="padTop"></div>
    Status: <select name="status">
    <?php if ($status==0) $sel = 'selected="selected"'; else $sel = ""; ?>
    <option <?=$sel?> value="0">Draft</option>
    <?php if ($status==1) $sel = 'selected="selected"'; else $sel = ""; ?>
    <option <?=$sel?> value="1">Published</option>
    </select>
	
    <select name="allow_comments">
    <?php if ($allow_comments==1) $sel = 'selected="selected"'; else $sel = ""; ?>
    <option <?=$sel?> value="1">Allow Comments</option>
    <?php if ($allow_comments==0) $sel = 'selected="selected"'; else $sel = ""; ?>
    <option <?=$sel?> value="0">Disable Comments</option>
    </select>
  <div class="padTop"></div>
  </td>
  </tr>
     <td>
	 Author: 
	 <?php 
	 $qry = $db->prepare("SELECT * FROM ".OSDB_USERS." WHERE user_level>=9 LIMIT 50"); 
	 $result = $qry->execute();
	 ?>
	 <select name="author">
	 <?php
	  while ($r = $qry->fetch(PDO::FETCH_ASSOC)) {
	  if ( $author == $r["user_id"] ) $sel='selected="selected"'; else $sel='';
	  ?>
	  <option <?=$sel?> value="<?=$r["user_id"]?>"><?=$r["user_name"]?></option>
	  <?php
	  }
	 ?>
	 </select>
	 </td>
    <tr>
  <td class="padLeft" style="padding-left:84px;">
  <div class="padTop"></div>

  <div class="padTop"></div>
  </td>
  </tr>
  <tr class="row">
   <td style="padding-left:84px;">
        <div class="padTop padLeft padBottom">
		<input type="submit" value="Submit" class="menuButtons" name="add_post" />
		<a class="menuButtons padPeft" href="<?=$website?>adm/?posts">&times; Back</a>
		</div>
	</td>
  </tr>
  </table>
</form>
	<script type="text/javascript" src="<?php echo $website;?>adm/editor.js"></script>
	
<?php } else { ?>
<div><h2><a href="<?=$website?>adm/?posts&amp;add">[+] Add Post</a></h2></div>
<?php 

  if (isset($_POST['checkbox']) ) {
    $sql  = "DELETE FROM ".OSDB_NEWS." WHERE ";
	$sql2 = "DELETE FROM ".OSDB_COMMENTS." WHERE ";
	$c = 0;
	for ($i = 0; $i < count($_POST['checkbox']); $i++) {
	   if ( is_numeric( $_POST['checkbox'][$i] )  ) {
	   $sql.=" news_id='".(int)$_POST['checkbox'][$i]."' OR ";
	   $sql2.=" post_id='".(int)$_POST['checkbox'][$i]."' OR ";
	   $c++;
	   }
	}
	
	if ( $c>=1 ) {
	
	$sql  = substr($sql,0, -3);
	$sql2 = substr($sql2,0, -3);
	//echo $sql; 
	$delete  = $db->prepare( $sql );
	$result = $delete->execute();
	$delete2 = $db->prepare( $sql2 );
	$result = $delete2->execute();
	if ( $c ) { ?>Deleted <?=$c?> post(s)<?php
	}
	}
  }


if ( !isset($_GET["edit"])  ) {
  $sth  = $db->prepare("SELECT COUNT(*) FROM ".OSDB_NEWS." WHERE news_id>=1 ");
  $result = $sth->execute();
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  $result_per_page = 30;
  $draw_pagination = 1;
  $SHOW_TOTALS = 1;
  include('pagination.php');
  
  $sth  = $db->prepare("SELECT n.*, u.user_name, u.user_level
  FROM ".OSDB_NEWS." as n 
  LEFT JOIN ".OSDB_USERS." as u ON u.user_id = n.author
  WHERE n.news_id>=1 ORDER BY n.news_id DESC 
  LIMIT $offset, $rowsperpage");
  $result = $sth->execute();
  ?>
   <form method="post" name="delete" action="">
  <table>
    <tr>
	  <th width="360" class="padLeft"><input type="checkbox" onClick="toggle(this)" /> Title</th>
	  <th width="120">Action</th>
	  <th width="100">Author</th>
	  <th width="100">Comments</th>
	  <th width="50">Views</th>
	  <th width="120">Added</th>
	</tr>
  <?php
   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
   $title = $row["news_title"];
   if ($row["status"] == 0) $title = "<span style='color: #6F6F6F;'>".$title."</span>";
   ?>
   <tr class="row" style="height:32px;">
     <td width="280" class="padLeft font12">
	   <input type="checkbox" name="checkbox[]" value="<?=$row["news_id"]?>"> 
	   <a href="<?=$website?>adm/?posts&amp;edit=<?=$row["news_id"]?>"><?=$title?></a>
	 </td>
	 <td width="110" class="font12">
	 <?php 
	 if ( isset($_GET["page"]) AND is_numeric($_GET["page"]) ) $p = "&amp;page=".(int)$_GET["page"]; else $p="";
	 if ($row["status"] == 0) { ?><a href="<?=$website?>adm/?posts<?=$p?>&amp;publish=<?=$row["news_id"]?>">Publish</a><?php } ?>
	 <?php if ($row["status"] == 1) { ?><a href="<?=$website?>adm/?posts<?=$p?>&amp;draft=<?=$row["news_id"]?>">Draft</a><?php } ?>
	 <a href="<?=$website?>adm/?posts&amp;edit=<?=$row["news_id"]?>"><img src="<?=$website?>adm/edit.png" alt="img" /></a>
	 <a href="javascript:;" onclick="if (confirm('Delete Post?') ) { location.href='<?=$website?>adm/?posts&amp;del=<?=$row["news_id"]?>' }"><img src="<?=$website?>adm/del.png" alt="img" /></a>
	 </td>
	 <td width="120"><a href="<?=OS_HOME?>adm/?users&amp;edit=<?=$row["author"]?>"><?=$row["user_name"]?></a></td>
	 <td width="40" class="font12"><a href="<?=$website?>adm/?comments&amp;post=<?=$row["news_id"]?>"><?=$row["comments"]?></a> <?php if ($row["allow_comments"] == 0) { ?><span style="font-size:11px;">disabled</span><?php } ?></td>
	 <td width="50"><?=$row["views"]?></td>
	 <td width="120" class="font12"><?=date($DateFormat, ($row["news_date"]))?></td>
   </tr>
   
   <?php }
   ?>
  <tr>
  <td><input class="menuButtons" type="submit" name="Submit" value="Delete Selected"></td>
  <td></td>
  <td></td>
  <td></td>
  <td></td>
  </tr>
   </table>
   </form>
<?php 
include('pagination.php');
  } 
}
?>

<a class="menuButtons" href="javascript:;" onclick="if(confirm('Enable All Comments?') ) { location.href='<?=$website?>adm/?posts&amp;com=1' }">Enable All Comments</a>
<a class="menuButtons" href="javascript:;" onclick="if(confirm('Disable All Comments?') ) { location.href='<?=$website?>adm/?posts&amp;com=0' }">Disable All Comments</a>
</div>

<div style="margin-top: 160px;">&nbsp;</div>