<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
$errors = "";

if ( isset($SafelistPage) ) {
//delete
  if ( isset( $_GET["del"]) AND is_numeric($_GET["del"]) ) {
      $id = safeEscape( (int) $_GET["del"] );
	  $delete = $db->prepare("DELETE FROM ".OSDB_SAFELIST." WHERE id ='".(int)$id."' LIMIT 1 ");
	  $result = $delete->execute();
	  if ( isset($_GET["n"]) ) {
	  $delStats = $db->prepare("UPDATE ".OSDB_STATS." SET safelist = 0 
	  WHERE (player) = ('".safeEscape( trim($_GET["n"]) )."') LIMIT 1");
	  $result = $delStats->execute();
	  }
	  ?>
	  <div align="center">
	  <h2>User successfully deleted. <a href="<?=OS_HOME?>adm/?safelist">&laquo; Back</a></h2>
	  </div>
	  <?php 
  }
//eDIT
  if ( (isset( $_GET["edit"]) AND is_numeric($_GET["edit"]) ) OR isset($_GET["add"])  ) {
   $name = ""; $server = ""; $voucher = "";
   if ( isset($_GET["edit"])  AND is_numeric($_GET["edit"])  ) $id = safeEscape( (int) $_GET["edit"] );
   //UPDATE
    if ( isset($_POST["edit_list"]) ) {
	  $name     = safeEscape( $_POST["name"]);
	  $server   = safeEscape( $_POST["server"]);
	  $voucher   = safeEscape( $_POST["voucher"]);
	  
	  if ( strlen( $name)<=2 ) $errors.="<div>Field Name does not have enough characters</div>";
	  
	  
	  $time = date( "Y-m-d H:i:s", time() );
	  
	  if ( isset($_GET["edit"]) ) $sql = "UPDATE ".OSDB_SAFELIST." SET 
	  name= '".$name."', server = '".$server."', voucher = '".$voucher."'
	  WHERE id ='".$id."' LIMIT 1 ";
	  
	  if ( isset($_GET["add"]) ) { $sql = "INSERT INTO ".OSDB_SAFELIST."(name, server, voucher) VALUES('".$name."', '".$server."', '".$voucher."' )";
	  $update = $db->prepare("UPDATE ".OSDB_STATS." SET safelist = 1 
	  WHERE (player) = ('".$name."') LIMIT 1");
	  $result = $update->execute();
	  }
	  
	  if ( empty($errors) ) {
	  $sth = $db->prepare($sql);
	  $result = $sth->execute();
	  $ok = 1;
	  if ( $ok ) {
	  	  ?>
	  <div align="center">
	    <h2>User successfully updated. <a href="<?=OS_HOME?>adm/?safelist">&laquo; Back</a></h2>
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
	 $sth = $db->prepare("SELECT * FROM ".OSDB_SAFELIST." WHERE id = '".$id."' ");
	 $result = $sth->execute();
	 $row = $sth->fetch(PDO::FETCH_ASSOC);
	 $name       = ( $row["name"]);
	 $server     = ( $row["server"]);
	 $voucher     = ( $row["voucher"]);
	 $button = "Edit User";
	 } else { $button = "Add User to Safelist"; }
	 
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
	     <td width="80"  class="padLeft">Voucher:</td>
		 <td><input name="voucher" style="width: 380px; height: 28px;" type="text" value="<?=$voucher?>" /></td>
	   </tr>
	   <tr>
	     <td width="80"></td>
		 <td class="padTop padBottom">
		 <input type="submit" value="Submit" name="edit_list" class="menuButtons" />
		 <a class="menuButtons" href="<?=OS_HOME?>adm/?safelist">&laquo; Back</a>
		 </td>
	   </tr>
	  </table>
	  </div>
	 </form>
	 <?php
  }

  $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_SAFELIST."");
  $result = $sth->execute();
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  $result_per_page = 30;
?>
<div align="center">
<div class="padBottom padTop"><a class="menuButtons" href="<?=OS_HOME?>adm/?safelist&amp;add">[+] Add User to Safelist</a></div>
<?php
  
  $draw_pagination = 1;
  $SHOW_TOTALS = 1;
  include('pagination.php');
  
   $sth = $db->prepare("SELECT * FROM ".OSDB_SAFELIST." ORDER BY id DESC LIMIT $offset, $rowsperpage");
   $result = $sth->execute();
   ?>
   <table>
    <tr>
	  <th width="180" class="padLeft">User</th>
	  <th width="64">Action</th>
	  <th width="150">Server</th>
	  <th width="120">Voucher</th>
	</tr>
   <?php
   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { ?>
   <tr class="row" style="height:32px;">
     <td width="180" class="font12 padLeft"><a href="<?=OS_HOME?>adm/?safelist&amp;edit=<?=$row["id"]?>"><?=$row["name"]?></a></td>
	 <td width="64" class="font12">
	 <a href="<?=OS_HOME?>adm/?safelist&amp;edit=<?=$row["id"]?>"><img src="<?=OS_HOME?>adm/edit.png" alt="img" /></a>
	 <a href="javascript:;" onclick="if (confirm('Delete User from Safelist?') ) { location.href='<?=OS_HOME?>adm/?safelist&amp;del=<?=$row["id"]?>&amp;n=<?=$row["name"]?>' }"><img src="<?=OS_HOME?>adm/del.png" alt="img" /></a>
	 </td>
	 <td width="150" class="font12 overflow_hidden"><span title="<?=$row["server"]?>"><?=stripslashes($row["server"])?></span></td>
	 <td width="120" class="font12 overflow_hidden"><span title="<?=$row["voucher"]?>"><?=stripslashes($row["voucher"])?></span></td>
    </tr>
   <?php 
   }
?>
  </table>
<?php
include('pagination.php');
?>
  </div>
<?php } else { ?>
<div align="center">
  <h2>SafeList disabled</h2>
  <div>Please enable SafeList</div>
</div>
<div style="margin-top: 480px;">&nbsp;</div>
<?php } ?>
  
  <div style="margin-top: 180px;">&nbsp;</div>