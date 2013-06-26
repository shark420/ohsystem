<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

//DELETE COMMENT - ADMIN
if (os_is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 AND isset($_GET["delete_comment"]) AND isset($_GET["post_id"]) ) {
   $id = safeEscape( (int) $_GET["delete_comment"] );
   $pid = safeEscape( (int) $_GET["post_id"] );
   
   $del_1 = $db->exec("DELETE FROM ".OSDB_COMMENTS." WHERE id = '".(int) $id."' AND post_id = '".(int) $pid."' LIMIT 1");
   
   $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_COMMENTS." WHERE post_id=:post_id LIMIT 1");
   $sth->bindValue(':post_id', (int) $pid, PDO::PARAM_INT); 
   $result = $sth->execute();
   $r = $sth->fetch(PDO::FETCH_NUM);
   $TotalComments =  $r[0];
   $update = $db->exec("UPDATE ".OSDB_NEWS." SET ".OSDB_COMMENTS." = '".(int) $TotalComments."' WHERE news_id = '". (int) $pid."' ");
   
   header('location: '.OS_HOME.'?post_id='.$pid.'#comments'); die;
}

  if ( isset($_POST["add_comment"]) AND os_is_logged() AND isset($_GET["post_id"]) AND is_numeric($_GET["post_id"]) AND isset($_SESSION["code"]) AND isset($_POST["code"]) AND isset($_POST["pid"]) ) {
  
   require_once(OS_PLUGINS_DIR.'index.php');
   os_init();
  
  $id = safeEscape( (int) $_GET["post_id"]);
  $text = OS_StrToUTF8( $_POST["post_comment"] );
  $text =  (PrepareTextDB( $text ));
  //$text = EscapeStr( ($text) );
  //$text = (($text));
  $errors = "";
  
  //Check if comments is allowed for this post
  $sth = $db->prepare("SELECT * FROM ".OSDB_NEWS." WHERE news_id=:news_id AND allow_comments = 1");
  $sth->bindValue(':news_id', (int) $id, PDO::PARAM_INT); 
  
  $result = $sth->execute();

  if ( $sth->rowCount()<=0 ) $errors.="<div>".$lang["error_comment_not_allowed"]."</div>";
  
  if ( $_SESSION["code"] != $_POST["code"])  $errors.="<div>".$lang["error_invalid_form"]."</div>";
  if ( $_POST["pid"] != $id )                $errors.="<div>".$lang["error_invalid_form"]."</div>";
  if ( strlen($text)<=3 )   $errors.="<div>".$lang["error_text_char"] ."</div>";
  
  if ( empty($errors) ) {
	 
    $db->insert( OSDB_COMMENTS, array(
	"user_id" => (int)$_SESSION["user_id"],
	"page" => 'news',
	"post_id" => (int) $id,
	"text" => $text,
	"date" => time(),
	"user_ip" => $_SERVER["REMOTE_ADDR"]
                                 ));
								 
     $InsertID = $db->lastInsertId(); 

	 $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_COMMENTS." WHERE post_id=:post_id LIMIT 1");
	 $sth->bindValue(':post_id', (int) $id, PDO::PARAM_INT); 
	 $result = $sth->execute();
	 
	 $r = $sth->fetch(PDO::FETCH_NUM);
     $TotalComments = $r[0];
		   $result = $db->update(OSDB_NEWS, array(
		   "comments" => $TotalComments
	                                    ), "news_id = ".(int)$id."");
	 
	 if ( $result ) {
	    header("location: ".OS_HOME."?post_id=".$id."#comments"); die;
	 }
  }
  
  }
?>