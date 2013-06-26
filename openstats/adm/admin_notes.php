<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
$errors = "";

//delete
  if ( isset( $_GET["del"]) AND is_numeric($_GET["del"]) ) {
      $id = safeEscape( (int) $_GET["del"] );
	  $sth = $db->prepare("DELETE FROM ".OSDB_NOTES." WHERE id ='".(int)$id."' LIMIT 1 ");
	  $result = $sth->execute();
	  ?>
	  <div align="center">
	  <h2>Note successfully deleted. <a href="<?=$website?>adm/?notes">&laquo; Back</a></h2>
	  </div>
	  <?php 
  }
//eDIT
  if ( (isset( $_GET["edit"]) AND is_numeric($_GET["edit"]) ) OR isset($_GET["add"])  ) {
   $name = ""; $server = ""; $note = "";
   if ( isset($_GET["edit"])  AND is_numeric($_GET["edit"])  ) $id = safeEscape( (int) $_GET["edit"] );
   //UPDATE
    if ( isset($_POST["edit_list"]) ) {
	  $name     = safeEscape( trim($_POST["name"]));
	  $server   = safeEscape( trim($_POST["server"]));
	  $note   = EscapeStr( trim($_POST["note"]));
	  $note = strip_tags( strip_quotes($note) );
	  
	  if ( strlen( $name)<=2 ) $errors.="<div>Field Name does not have enough characters</div>";
	  if ( strlen( $name)>20 ) $errors.='<div>Field "Player Name" contains too many characters</div>';
	  
	  $time = date( "Y-m-d H:i:s", time() );
	  
	  if ( isset($_GET["edit"]) ) $sql = "UPDATE ".OSDB_NOTES." SET 
	  name= '".$name."', server = '".$server."', note = '".$note."'
	  WHERE id ='".$id."' LIMIT 1 ";
	  
	  if ( isset($_GET["add"]) ) $sql = "INSERT INTO ".OSDB_NOTES."(name, server, note) VALUES('".$name."', '".$server."', '".$note."' )";
	  
	  if ( empty($errors) ) {
	  $sth = $db->prepare($sql);
	  $result = $sth->execute();
	  if ( $result ) {
	  	  ?>
	  <div align="center">
	    <h2>User successfully updated. <a href="<?=$website?>adm/?notes">&laquo; Back</a></h2>
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
	 $sth = $db->prepare("SELECT * FROM ".OSDB_NOTES." WHERE id = '".$id."' ");
	 $result = $sth->execute();
	 $row = $sth->fetch(PDO::FETCH_ASSOC);
	 $name       = ( $row["name"]);
	 $server     = ( $row["server"]);
	 $note     = ( $row["note"]);
	 $button = "Edit User";
	 } else { $button = "Add Note"; }
	 ?>
	 
	 <form action="" method="post">
	 <div align="center">
	 <h2><?=$button?></h2>
	 <table>
	   <tr class="row">
	     <td width="120" class="padLeft">Player Name:</td>
		 <td><input name="name" style="width: 380px; height: 28px;" type="text" value="<?=$name ?>" /></td>
	   </tr>
	   <tr class="row">
	     <td width="120"  class="padLeft">Server:</td>
		 <td><input name="server" style="width: 380px; height: 28px;" type="text" value="<?=$server?>" /></td>
	   </tr>
	   <tr class="row">
	     <td width="120"  class="padLeft">Note:</td>
		 <td><textarea name="note" style="width: 400px; height: 70px;"><?=$note?></textarea></td>
	   </tr>
	   <tr>
	     <td width="80"></td>
		 <td class="padTop padBottom">
		 <input type="submit" value="Submit" name="edit_list" class="menuButtons" />
		 <a class="menuButtons" href="<?=$website?>adm/?notes">&laquo; Back</a>
		 </td>
	   </tr>
	  </table>
	  </div>
	 </form>
	 <?php
  }

  $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_NOTES."");
  $result = $sth->execute();
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  $result_per_page = 30;
?>
<div align="center">
<div class="padBottom padTop"><a class="menuButtons" href="<?=$website?>adm/?notes&amp;add">[+] Add Note</a></div>
<?php
  
  $draw_pagination = 1;
  $SHOW_TOTALS = 1;
  include('pagination.php');
  
   $sth = $db->prepare("SELECT * FROM ".OSDB_NOTES." ORDER BY id DESC LIMIT $offset, $rowsperpage");
   $result = $sth->execute();
   ?>
   <table>
    <tr>
	  <th width="180" class="padLeft">Player name</th>
	  <th width="64">Action</th>
	  <th width="150">Server</th>
	  <th width="120">Note</th>
	</tr>
   <?php
   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { ?>
   <tr class="row" style="height:32px;">
     <td width="180" class="padLeft font12"><a href="<?=$website?>adm/?notes&amp;edit=<?=$row["id"]?>"><?=$row["name"]?></a></td>
	 <td width="64" class="font12">
	 <a href="<?=$website?>adm/?notes&amp;edit=<?=$row["id"]?>"><img src="<?=$website?>adm/edit.png" alt="img" /></a>
	 <a href="javascript:;" onclick="if (confirm('Delete Note?') ) { location.href='<?=$website?>adm/?notes&amp;del=<?=$row["id"]?>' }"><img src="<?=$website?>adm/del.png" alt="img" /></a>
	 </td>
	 <td width="150" class="font12"><span title="<?=$row["server"]?>"><?=stripslashes($row["server"])?></span></td>
	 <td width="120" class="font12"><span title="<?=$row["note"]?>"><?=limit_words($row["note"], 10)?></span></td>
    </tr>
   <?php 
   }
?>
  </table>
<?php
include('pagination.php');
?>
</div>
  
  <div style="margin-top: 180px;">&nbsp;</div>