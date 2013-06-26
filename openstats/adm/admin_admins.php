<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
$errors = "";

if (( isset($_GET["del"]) OR isset($_GET["edit"]) OR isset($_GET["add"]) ) AND $_SESSION["level"]<=9 ) {
	  ?>
	  <div align="center">
	    <h2>Only root administrators can access this options</h2>
	  </div>
	  <?php 
}

//delete
  if ( isset( $_GET["del"]) AND is_numeric($_GET["del"]) AND $_SESSION["level"]>9) {
      $id = safeEscape( (int) $_GET["del"] );
	  $sth = $db->prepare("DELETE FROM ".OSDB_ADMINS." WHERE id =? LIMIT 1 ");
	  $sth->bindValue(1, (int)$id, PDO::PARAM_INT);
	  $result = $sth->execute();
	  if ( isset($_GET["n"]) )
	  
	$result = $db->update(OSDB_STATS, array(
		   "admin" => 0
	                                    ), "LOWER(player) = ".safeEscape( trim( strtolower($_GET["n"])) )."");
	  ?>
	  <div align="center">
	  <h2>Admin successfully deleted. <a href="<?=$website?>adm/?admins">&laquo; Back</a></h2>
	  </div>
	  <?php 
  }
//eDIT
  if ( (isset( $_GET["edit"]) AND is_numeric($_GET["edit"]) AND $_SESSION["level"]>9 ) OR isset($_GET["add"]) AND $_SESSION["level"]>9  ) {
   $name = ""; $server = ""; $access = "";
   if ( isset($_GET["edit"])  AND is_numeric($_GET["edit"])  ) $id = safeEscape( (int) $_GET["edit"] );
   //UPDATE
    if ( isset($_POST["edit_admin"]) ) {
	  $name     = safeEscape( $_POST["name"]);
	  $server   = safeEscape( $_POST["server"]);
	  $access   = safeEscape( $_POST["access"]);
	  
	  if ( strlen( $name)<=2 ) $errors.="<div>Field Name does not have enough characters</div>";
	  
	  
	  $time = date( "Y-m-d H:i:s", time() );
	  
	  if ( isset($_GET["edit"]) ) $sql = "UPDATE ".OSDB_ADMINS." SET 
	  name= '".$name."', server = '".$server."', access = '".$access."' WHERE id ='".$id."' LIMIT 1 ";
	  
	  if ( isset($_GET["add"]) ) { $sql = "INSERT INTO ".OSDB_ADMINS."(name, server, access) VALUES('".$name."', '".$server."', '".$access."' )";
	  $update = $db->query("UPDATE ".OSDB_STATS." SET admin = 1 WHERE LOWER(player) = LOWER('".$name."') LIMIT 1");
	  }
	  
	  if ( empty($errors) ) {
	  $sth = $db->prepare($sql);
	  $result = $sth->execute();
	  
	  if ( $result ) {
	  	  ?>
	  <div align="center">
	    <h2>Admin successfully updated. <a href="<?=$website?>adm/?admins">&laquo; Back</a></h2>
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
	 $sth = $db->prepare("SELECT * FROM ".OSDB_ADMINS." WHERE id = :id ");
	 $sth->bindValue(':id', (int) $id, PDO::PARAM_INT); 	
	 $result = $sth->execute();
	 $row = $sth->fetch(PDO::FETCH_ASSOC);
	 $name       = ( $row["name"]);
	 $server     = ( $row["server"]);
	 $access = ( $row["access"]);
	 $button = "Edit Admin";
	 } else { $button = "Add Admin"; }
	 
	 if ( isset($_GET["add"]) AND !empty($_GET["add"]) ) {
	    $name = trim( safeEscape($_GET["add"]) );
	 }
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
	     <td width="80"  class="padLeft">Server:</td>
		 <td><input name="server" style="width: 380px; height: 28px;" type="text" value="<?=$server?>" /></td>
	   </tr>
	   <tr class="row">
	     <td width="80"  class="padLeft">Access:</td>
		 <td><input name="access" style="width: 80px; height: 28px;" type="text" value="<?=$access?>" />
		 Ghost One only
		 </td>
	   </tr>
	   <tr>
	     <td width="80"></td>
		 <td class="padTop padBottom">
		 <input type="submit" value="Submit" name="edit_admin" class="menuButtons" />
		 <a class="menuButtons" href="<?=$website?>adm/?admins">&laquo; Back</a>
		 </td>
	   </tr>
	  </table>
	  </div>
	 </form>
	 <?php
  }

  $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_ADMINS." ");
  $result = $sth->execute();
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  $result_per_page = 30;
?>
<div align="center">
<div class="padBottom padTop"><a class="menuButtons" href="<?=$website?>adm/?admins&amp;add">[+] Add Admin</a></div>
<?php
  
  $draw_pagination = 1;
  $SHOW_TOTALS = 1;
  include('pagination.php');
  
   $sth = $db->prepare("SELECT * FROM ".OSDB_ADMINS." ORDER BY id DESC LIMIT $offset, $rowsperpage");
   $result = $sth->execute();
   ?>
   <table>
    <tr>
	  <th width="120" class="padLeft">Admin</th>
	  <th width="64">Action</th>
	  <th width="90">Access</th>
	  <th width="140">Server</th>
	</tr>
   <?php
   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { ?>
   <tr class="row" style="height:30px;">
     <td width="120" class="padLeft font12"><a href="<?=$website?>adm/?admins&amp;edit=<?=$row["id"]?>"><?=$row["name"]?></a></td>
	 <td width="64" class="font12">
	 <a href="<?=$website?>adm/?admins&amp;edit=<?=$row["id"]?>"><img src="<?=$website?>adm/edit.png" alt="img" /></a>
	 <a href="javascript:;" onclick="if (confirm('Delete Admin?') ) { location.href='<?=$website?>adm/?admins&amp;del=<?=$row["id"]?>&amp;n=<?=$row["name"]?>' }"><img src="<?=$website?>adm/del.png" alt="img" /></a>
	 </td>
	 <td width="90" class="overflow_hidden font12"><?=$row["access"]?></td>
	 <td width="140" class="overflow_hidden font12"><span title="<?=$row["server"]?>"><?=stripslashes($row["server"])?></span></td>
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