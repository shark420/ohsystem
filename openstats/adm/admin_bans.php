<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
$errors = "";

if ( isset($_GET["search_bans"]) ) $s = safeEscape($_GET["search_bans"]); else $s=""; 
?>
<div align="center" class="padBottom">
	 <form action="" method="get">
	 <table>
	   <tr>
	    <td width="290">
		
		  <input type="hidden" name="bans" />
		  <input style="width: 180px; height: 24px;" type="text" name="search_bans" value="<?=$s?>" />
		  <input class="menuButtons" type="submit" value="Search" />
		</td>
	    <td>
		<a class="menuButtons" href="<?=$website?>adm/?bans&amp;add">[+] Add Ban</a>
		<?php if ( !isset($_GET["duplicate"]) ) { ?>
		<a class="menuButtons" href="<?=$website?>adm/?bans&amp;duplicate">Find duplicate bans</a>
		<?php } else { ?>
		<a class="menuButtons" href="<?=$website?>adm/?bans">Show All bans</a>
		<?php } ?>
		</td>
	   </tr>
	 </table>
	 </form>
</div>
<?php
//delete
  if ( isset( $_GET["del"]) AND is_numeric($_GET["del"]) ) {
      $id = safeEscape( (int) $_GET["del"] );
	  $sth = $db->prepare("DELETE FROM ".OSDB_BANS." WHERE id ='".(int)$id."' LIMIT 1 ");
	  $result = $sth->execute();
	  
	  ?>
	  <div align="center">
	  <h2>Ban successfully deleted. <a href="<?=$website?>adm/?bans">&laquo; Back</a></h2>
	  </div>
	  <?php 
  }
//eDIT
  if ( (isset( $_GET["edit"]) AND is_numeric($_GET["edit"]) ) OR isset($_GET["add"])  ) {
   $name = ""; $server = ""; $reason = ""; $ip = ""; $admin = ""; $gn="";
   if ( isset($_GET["edit"])  AND is_numeric($_GET["edit"])  ) $id = safeEscape( (int) $_GET["edit"] );
   //UPDATE
    if ( isset($_POST["edit_ban"]) ) {
	  $name     = safeEscape( trim($_POST["name"]));
	  $server   = safeEscape( trim($_POST["server"]));
	  $reason   = EscapeStr( convEnt2(trim($_POST["reason"])));
	  $ip       = safeEscape( trim($_POST["ip"]));
	  $admin    = safeEscape( trim($_POST["admin"]));
	  $gn       = safeEscape( trim($_POST["gn"]));
	  
	  if ( strlen( $name)<=2 ) $errors.="<div>Field Name does not have enough characters</div>";
	  
	  
	  $time = date( "Y-m-d H:i:s", time() );
	  
	  if ( isset($_GET["edit"]) ) $sql = "UPDATE ".OSDB_BANS." SET 
	  name= '".$name."', server = '".$server."', reason = '".$reason."', ip='".$ip."', admin = '".$admin."', gamename='".$gn."' WHERE id ='".$id."' LIMIT 1 ";
	  
	  if ( isset($_GET["add"]) ) { $sql = "INSERT INTO ".OSDB_BANS."(name, server, reason, ip, admin, gamename, date) 
	  VALUES('".$name."', '".$server."', '".$reason."', '".$ip."', '".$admin."', '".$gn."', '".$time ."' )";
	  $sth = $db->prepare("UPDATE ".OSDB_STATS." SET banned = 1 WHERE LOWER(player) = LOWER('".$name."') LIMIT 1");
	  $result = $sth->execute();
	  }
	  
	  if ( empty($errors) ) {
	  $sth = $db->prepare($sql);
	  $result = $sth->execute();
	  if ( $result ) {
	  	  ?>
	  <div align="center">
	    <h2>Ban successfully updated. <a href="<?=$website?>adm/?bans">&laquo; Back</a></h2>
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
	 $sth = $db->prepare("SELECT * FROM ".OSDB_BANS." WHERE id = '".$id."' ");
	 $result = $sth->execute();
	 $row = $sth->fetch(PDO::FETCH_ASSOC);
	 $name     = ( $row["name"]);
	 $server   = ( $row["server"]);
	 $reason   = ($row["reason"]);
	 $ip       = ( $row["ip"]);
	 $admin    = ( $row["admin"]);
	 $gn       = ( $row["gamename"]);
	 $button = "Edit Ban";
	 } else { 
	 $button = "Add Ban"; 
	 if (isset($_GET["add"]) AND !empty($_GET["add"]) AND strlen($_GET["add"])>=2 ) {
	   $name = safeEscape( $_GET["add"]);
	 }
	 
	 }
	 ?>
	 <div align="center">
	 <form action="" method="post">
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
	     <td width="80"  class="padLeft">Reason:</td>
		 <td><input name="reason" style="width: 380px; height: 28px;" type="text" value="<?=$reason?>" /></td>
	   </tr>
	   <tr class="row">
	     <td width="80"  class="padLeft">Gamename:</td>
		 <td><input name="gn" style="width: 380px; height: 28px;" type="text" value="<?=$gn?>" /></td>
	   </tr>
	   <tr class="row">
	     <td width="80"  class="padLeft">IP:</td>
		 <td><input name="ip" style="width: 380px; height: 28px;" type="text" value="<?=$ip?>" /></td>
	   </tr>
	   <tr class="row">
	     <td width="80"  class="padLeft">Banned by:</td>
		 <td><input name="admin" style="width: 380px; height: 28px;" type="text" value="<?=$admin?>" /></td>
	   </tr>
	   <tr>
	     <td width="80"></td>
		 <td class="padTop padBottom">
		 <input type="submit" value="Submit" name="edit_ban" class="menuButtons" /> &nbsp; &nbsp; &nbsp; &nbsp;
		 <a class="menuButtons" href="<?=$website?>adm/?bans">&laquo; Back to Bans</a>
<?php if ( isset($_GET["edit"])  AND is_numeric($_GET["edit"])  ) { ?>
		 <a onclick="if (confirm('Delete ban?') ) { location.href='<?=$website?>adm/?bans&amp;del=<?=$id?>' }" class="menuButtons" href="javascript:;">&times; Delete Ban</a><?php } ?>
		 </td>
	   </tr>
	  </table>
	 </form>
	  </div>
	  <div class="padBottom"></div>
	 <?php
  }
  
  if (isset($_POST['checkbox']) ) {
    $sql = "DELETE FROM ".OSDB_BANS." WHERE ";
	$c = 0;
	for ($i = 0; $i < count($_POST['checkbox']); $i++) {
	   if ( is_numeric( $_POST['checkbox'][$i] )  ) {
	   $sql.=" id='".(int)$_POST['checkbox'][$i]."' OR ";
	   $c++;
	   }
	}
	
	if ( $c>=1 ) {
	
	$sql = substr($sql,0, -3);
	//echo $sql; 
	$delete = $db->query( $sql );
	if ( $delete ) { ?>Deleted <?=$c?> ban(s)<?php
	}
	}
  }
  
  
  if ( isset($_GET["search_bans"]) AND strlen($_GET["search_bans"])>=2 ) {
     $search_bans = safeEscape( $_GET["search_bans"]);
	 $sql = " AND LOWER(name) LIKE LOWER('%".$search_bans."%') ";
  } else {
   $sql = "";
   $search_bans= "";
  }
  
  if ( !isset($_GET["duplicate"])  ) {
  $sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_BANS." WHERE id>=1 $sql" );
  $result = $sth->execute();
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  
  
  } else {
     $sth = $db->prepare( "SELECT count(*), name FROM ".OSDB_BANS." 
	 GROUP BY name having count(*) > 1 ORDER BY name DESC" );
	 $result = $sth->execute();
	 $numrows = $sth->rowCount();
  }
  
?>
<div align="center">
<?php
  $result_per_page = 20;
  $draw_pagination = 1;
  $SHOW_TOTALS = 1;
  include('pagination.php');
    
	if ( !isset($_GET["duplicate"]) ) 
   $sth = $db->prepare("SELECT * FROM ".OSDB_BANS." WHERE id>=1 $sql ORDER BY id DESC LIMIT $offset, $rowsperpage");
   else 
   $sth = $db->prepare( "SELECT count(*), id, name, reason, date, admin 
   FROM ".OSDB_BANS." 
   GROUP BY name having count(*) > 1 ORDER BY id DESC, date DESC" );
   
   $result = $sth->execute();
   ?>
   
   <?php if ( isset($_GET["duplicate"]) ) { 
   if ($numrows<=0) $message = "<tr><td class='padLeft'><h2>No duplicate bans</h2></td><td></td><td></td><td></td><td></td></tr>";
   ?>
   <h2>Duplicate bans</h2>
   <?php } else $message = ""; ?>
   <form method="post" name="delete" action="">
   <table>
    <tr>
	  <th width="180" class="padLeft"><input type="checkbox" onClick="toggle(this)" /> Player</th>
	  <th width="64">Action</th>
	  <th width="260">Reason</th>
	  <th width="140">Banned by</th>
	  <th width="120">Date</th>
	</tr>
   <?php
    echo $message ;
   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { ?>
   <tr class="row" style="height:36px;">
     <td width="180" class="padLeft font12">
	 <input type="checkbox" name="checkbox[]" value="<?=$row["id"]?>"> 
	 <?php if ( isset($_GET["duplicate"]) ) { ?>
	 <a href="<?=OS_HOME?>adm/?bans=&search_bans=<?=trim($row["name"])?>"><span style="color:red;">[show]</span></a>
	 <?php } ?>
	 <a href="<?=$website?>adm/?bans&amp;edit=<?=$row["id"]?>"><?=$row["name"]?></a></td>
	 <td width="64" class="font12">
	 <a href="<?=$website?>adm/?bans&amp;edit=<?=$row["id"]?>"><img src="<?=$website?>adm/edit.png" alt="img" /></a>
	 <a href="javascript:;" onclick="if (confirm('Delete ban?') ) { location.href='<?=$website?>adm/?bans&amp;del=<?=$row["id"]?>' }"><img src="<?=$website?>adm/del.png" alt="img" /></a>
	 </td>
	 <td width="260" class="overflow_hidden font12"><span title="<?=$row["reason"]?>"><?=stripslashes($row["reason"])?></span></td>
	 <td width="140" class="font12"><?=$row["admin"]?></td>
	 <td width="120" class="font12"><i><?=date($DateFormat, strtotime($row["date"]))?></i></td>
    </tr>
   <?php 
   }
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
?>
  </div>