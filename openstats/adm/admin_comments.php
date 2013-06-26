<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
$errors = "";
//delete
  if ( isset( $_GET["del"]) AND is_numeric($_GET["del"]) AND isset($_GET["pid"]) ) {
      $id = safeEscape( (int) $_GET["del"] );
	  $pid = safeEscape( (int) $_GET["pid"] );
	  $sth = $db->prepare("DELETE FROM ".OSDB_COMMENTS." WHERE id ='".(int)$id."' LIMIT 1 ");
	  $result = $sth->execute();
	  $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_COMMENTS." WHERE post_id= '".$pid."' LIMIT 1");
	  $result = $sth->execute();
	  $r = $sth->fetch(PDO::FETCH_NUM);
	  $TotalComments = $r[0];
	  $sth = $db->prepare("UPDATE ".OSDB_NEWS." SET comments = '".$TotalComments."' WHERE news_id = '".$pid."' ");
	  $result = $sth->execute();
	  
	  ?>
	  <div align="center">
	  <h2>Comment successfully deleted. <a href="<?=$website?>adm/?comments">&laquo; Back</a></h2>
	  </div>
	  <?php 
  }
//eDIT
  if ( (isset( $_GET["edit"]) AND is_numeric($_GET["edit"]) )  ) {
   $name = ""; $server = "";
   if ( isset($_GET["edit"])  AND is_numeric($_GET["edit"])  ) $id = safeEscape( (int) $_GET["edit"] );
   //UPDATE
    if ( isset($_POST["edit_comment"]) ) {
	/*
	$text = my_nl2br( trim($_POST["comment"]) );
	$text = nl2br($text);
	$text = EscapeStr( ($text) );
	$text = (($text));
	*/
	$text =  PrepareTextDB($_POST["comment"]);
	  
	  if ( strlen( $text)<=2 ) $errors.="<div>Field Text does not have enough characters</div>";
	  
	  
	  $time = date( "Y-m-d H:i:s", time() );
	  
	  $d = EscapeStr($_POST["_d"]); $m = EscapeStr($_POST["_m"]); $Y = EscapeStr($_POST["_Y"]);
	  $H = EscapeStr($_POST["_H"]); $i = EscapeStr($_POST["_i"]);
	  
	  $DateErr = 0;
	  
	  $PostTime = strtotime($Y."-".$m."-".$d." ".$H.":".$i.":00");
	  
	  $sqlPostDate = ", date = '".$PostTime."' ";
	  
	  if ( $d<=0 OR $d>=32 ) $sqlPostDate = '';
	  if ( $m<=0 OR $m>=13 ) $sqlPostDate = '';
	  if ( $Y<=0) $sqlPostDate = '';
	  if ( $H<0 OR $H>=25 ) $sqlPostDate = '';
	  if ( $i<0 OR $i>=60 ) $sqlPostDate = '';
	  
	  if ( isset($_GET["edit"]) ) $sql = "UPDATE ".OSDB_COMMENTS." SET 
	  text= '".$text."' $sqlPostDate WHERE id ='".$id."' LIMIT 1 ";
	  
	  if ( empty($errors) ) {
	  $sth = $db->prepare($sql);
	  $result = $sth->execute();
	  if ( $result ) {
	  	  ?>
	  <div align="center">
	    <h2>Comment successfully updated. <a href="<?=$website?>adm/?comments">&laquo; Back</a></h2>
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
	 $sth = $db->prepare("SELECT * FROM ".OSDB_COMMENTS." WHERE id = '".$id."' ");
	 $result = $sth->execute();
	 $row = $sth->fetch(PDO::FETCH_ASSOC);
	 $text       = convEnt( $row["text"]);
	 $text = br2nl( $text );
	 while ( strstr($text, "\n\n") ) $text = str_replace("\n\n", "\n", $text);
	 
	 $text = str_replace("\n\r\n\r", "\n\r", $text);
     while ( strstr($text, "\n\r\n\r") ) $text = str_replace("\n\r\n\r", "\n\r", $text);
	 $button = "Edit Comment";
	 } else {  }
	 ?>
	 
	 <form action="" method="post">
	 <div align="center">
	 <h2><?=$button?></h2>
	 <table>
	   <tr class="row">
	     <td width="80" class="padLeft">Comment:</td>
		 <td><textarea name="comment" style="width: 560px; height: 220px;"><?=$text ?></textarea></td>
	   </tr>
	   <tr>
	     <td class="padLeft">Post Date:</td>
		 <td>
		   <input type="text" size="1" name="_d" value="<?=date("d", $row["date"])?>" />
		   <input type="text" size="1" name="_m" value="<?=date("m", $row["date"])?>" />
		   <input type="text" size="2" name="_Y" value="<?=date("Y", $row["date"])?>" />, @
		   <input type="text" size="1" name="_H" value="<?=date("H", $row["date"])?>" />:
		   <input type="text" size="1" name="_i" value="<?=date("i", $row["date"])?>" />:
		 </td>
	   </tr>
	   <tr>
	     <td width="80"></td>
		 <td class="padTop padBottom">
		 <input type="submit" value="Submit" name="edit_comment" class="menuButtons" />
		 <a class="menuButtons" href="<?=$website?>adm/?comments">&laquo; Back</a>
		 </td>
	   </tr>
	  </table>
	  </div>
	 </form>
	 <?php
  }
  
  if (isset($_POST['checkbox']) ) {
    $sql = "DELETE FROM ".OSDB_COMMENTS." WHERE ";
	$c = 0;
	$CommentIDS = "";
	for ($i = 0; $i < count($_POST['checkbox']); $i++) {
	   if ( is_numeric( $_POST['checkbox'][$i] )  ) {
	   $sql.=" id='".(int)$_POST['checkbox'][$i]."' OR ";
	   $CommentIDS.=(int)$_POST['checkbox'][$i].",";
	   $c++;
	   }
	}
	
	if ( $c>=1 ) {
	
	$sql = substr($sql,0, -3);
	
	//UPDATE TOTAL COMMENTS
	$CommentIDS = substr($CommentIDS,0, -1);
	$TotalSelected = explode(",", $CommentIDS);
	//prepare query
	$sqlComments = "SELECT * FROM ".OSDB_COMMENTS." WHERE ";
	for ($i = 0; $i < count($TotalSelected); $i++) {
	$sqlComments.=" id = '".$TotalSelected[$i]."' OR";
	}
	$sqlComments = substr($sqlComments,0, -3). " GROUP BY post_id";
	$sth = $db->prepare( $sqlComments);
	$result = $sth->execute();
	//Get Post IDs from deleted comments
	$PostIDS = "";
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	$PostIDS.=$row["post_id"].",";
	}
	$PostIDS = substr($PostIDS,0, -1);
	//echo $PostIDS; die;
	//DELETE
	$delete = $db->query( $sql );
	if ( isset($delete) AND $delete ) { ?>Deleted <?=$c?> comment(s)<?php
	  //Prepare query and update total comments for each post
    UpdateCommentsByPostIds($PostIDS);
	 }
	}
  }
  
  if ( isset($_GET["post"]) AND is_numeric($_GET["post"]) ) {
     $pid = safeEscape( (int) $_GET["post"] );
	 $sql = "AND c.post_id = '".$pid."' ";
  } else $sql ="";

  $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_COMMENTS." as c 
  WHERE id >= 1 $sql");
  $result = $sth->execute();

  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  $result_per_page = 30;
?>
<div align="center">
<?php
  
  $draw_pagination = 1;
  $SHOW_TOTALS = 1;
  include('pagination.php');
  
   $sth = $db->prepare("SELECT c.*, u.user_name, n.news_title, n.news_id
   FROM ".OSDB_COMMENTS." as c
   LEFT JOIN users as u ON u.user_id = c.user_id
   LEFT JOIN news as n ON n.news_id = c.post_id
   WHERE c.id>=1 $sql
   ORDER BY c.id 
   DESC LIMIT $offset, $rowsperpage");
   $result = $sth->execute();
   ?>
   <form method="post" name="delete" action="">
   <table>
    <tr>
	  <th width="150" class="padLeft"><input type="checkbox" onClick="toggle(this)" /> User</th>
	  <th width="450">Post</th>
      <th width="64">Action</th>
	</tr>
   <?php
   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { ?>
   <tr class="row" style="height:68px;">
     <td width="150" class="padLeft font12">
	 <input type="checkbox" name="checkbox[]" value="<?=$row["id"]?>">
	 <a href="<?=$website?>adm/?comments&amp;edit=<?=$row["id"]?>"><b><?=$row["user_name"]?></b></a>
	 <div style="font-size:11px;"><?=date($DateFormat,$row["date"])?></div>
	 <div style="font-size:11px;">IP: <?=$row["user_ip"]?></div>
	 </td>
	  <td width="450" class="font12" style="width:450px !important; word-wrap:break-word;">
      <div style="text-align:left; font-size:12px; word-wrap:break-word;"><a href="<?=$website?>adm/?comments&amp;edit=<?=$row["id"]?>"><?=$row["news_title"]?></a></div>
	  <?=limit_words(convEnt($row["text"]), 16)?>
	  </td>
	 <td width="64" class="font12">
	 <a href="<?=$website?>adm/?comments&amp;edit=<?=$row["id"]?>"><img src="<?=$website?>adm/edit.png" alt="img" /></a>
	 <a href="javascript:;" onclick="if (confirm('Delete Comment?') ) { location.href='<?=$website?>adm/?comments&amp;del=<?=$row["id"]?>&pid=<?=$row["news_id"]?>' }"><img src="<?=$website?>adm/del.png" alt="img" /></a>
	 </td>
    </tr>
   <?php 
   }
?>
  <tr>
  <td><input class="menuButtons" type="submit" name="Submit" value="Delete Selected"></td>
  <td></td>
  <td></td>
  </tr>
  </table>
  </form>
<?php
include('pagination.php');
?>
  </div>
  
  <div style="margin-top: 180px;">&nbsp;</div>