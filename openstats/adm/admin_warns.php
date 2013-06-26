<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
$errors = "";

if ( isset($_GET["search_warns"]) ) $s = safeEscape($_GET["search_warns"]); else $s=""; 
?>
<div align="center" class="padBottom">
	 <form action="" method="get">
	 <table>
	   <tr>
	    <td width="290">
		
		  <input type="hidden" name="warns" />
		  <input style="width: 180px; height: 24px;" type="text" name="search_warns" value="<?=$s?>" />
		  <input class="menuButtons" type="submit" value="Search" />
		</td>
	    <td>
		<a class="menuButtons" href="<?=$website?>adm/?warns&amp;add">[+] Add Warn</a>
		</td>
	   </tr>
	 </table>
	 </form>
</div>
<?php
//delete
  if ( isset( $_GET["del"]) AND is_numeric($_GET["del"]) ) {
      $id = safeEscape( (int) $_GET["del"] );
	  $delete = $db->prepare("DELETE FROM ".OSDB_BANS." WHERE id ='".(int)$id."' LIMIT 1 ");
	  $result = $delete->execute();
	  if ( isset($_GET["n"]) AND !empty($_GET["n"]) ) {
	  $delete = $db->prepare("UPDATE ".OSDB_STATS." SET warn=0, warn_expire='0000-00-00 00:00:00' 
	  WHERE id ='".safeEscape( trim($_GET["n"]) )."' LIMIT 1 ");
	  $result = $delete->execute();
	  }
	  ?>
	  <div align="center">
	  <h2>Warn successfully deleted. <a href="<?=$website?>adm/?warns">&laquo; Back</a></h2>
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
	  $warnc       = safeEscape( trim( (int) $_POST["warnc"]));
	  
	  $d  = safeEscape( trim($_POST["d"]));
	  $m  = safeEscape( trim($_POST["m"]));
	  $y  = safeEscape( trim($_POST["y"]));
	  $h  = safeEscape( trim($_POST["h"]));
	  $i  = safeEscape( trim($_POST["i"]));
	  
	  $expire = "$y-$m-$d $h:$i:00";
	  
	  $expireT = strtotime($expire );
	  $expireSql = date( 'Y-m-d H:i:00', $expireT );
	  
	  if ( $d<0 OR $d>31 ) $expire = '';
	  if ( $m<0 OR $m>12 ) $expire = '';
	  if ( $y<date("Y") OR $y>date("Y")+10 ) $expire = '';
	  if ( $h<0 OR $h>24 ) $expire = '';
	  if ( $i<0 OR $i>60 ) $expire = '';
	  
	  if ( strlen( $name)<=2 ) $errors.="<div>Field Name does not have enough characters</div>";
	  
	  
	  $time = date( "Y-m-d H:i:s", time() );
	  
	  if ( isset($_GET["edit"]) ) $sql = "UPDATE ".OSDB_BANS." SET 
	  name= '".$name."', server = '".$server."', reason = '".$reason."', ip='".$ip."', admin = '".$admin."', gamename='".$gn."', warn = '$warnc', expiredate = '$expireSql' 
	  WHERE id ='".$id."' LIMIT 1 ";
	  
	  if ( isset($_GET["add"]) ) { $sql = "INSERT INTO ".OSDB_BANS."(name, server, reason, ip, admin, gamename, date, warn, expiredate) 
	  VALUES('".$name."', '".$server."', '".$reason."', '".$ip."', '".$admin."', '".$gn."', '".$time ."', '$warnc', '".$expireSql."'  )";
	  $update = $db->query("UPDATE ".OSDB_STATS." SET warn = $warnc, warn_expire = '$expireSql' 
	  WHERE (player) = ('".$name."') LIMIT 1");
	  }
	  
	  if ( empty($errors) ) {
	  $s=1;
	  $sth = $db->prepare($sql);
	  $result = $sth->execute();
	  if ( $s ) {
	  	  ?>
	  <div align="center">
	    <h2>Warn successfully updated. <a href="<?=$website?>adm/?bans">&laquo; Back</a></h2>
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
	 $expire   = ( $row["expiredate"]);
	 $warnc    = $row["warn"];
	 	 
	 $d = date("d", strtotime($expire) );
	 $m = date("m", strtotime($expire) );
	 $y = date("Y", strtotime($expire) );
	 $h = date("H", strtotime($expire) );
	 $i = date("i", strtotime($expire) );
	 
	 $button = "Edit warn";
	 } else { 
	 $expire = time()+3600*5;
	 $sel = array();
	 $sel[1] = ''; $sel[2] = ''; $sel[3] = ''; $sel[4] = ''; $sel[5] = ''; $sel[6] = ''; $sel[7] = '';
	 if ( isset($_GET["expire"]) ) {
		 if ($_GET["expire"] == '1') { $expire = time()+3600; $sel[1]='selected="selected"';} 
		 if ($_GET["expire"] == '10') { $expire = time()+3600*10; $sel[2]='selected="selected"';} 
		 if ($_GET["expire"] == '1d') { $expire = time()+3600*24; $sel[3]='selected="selected"';}  
		 if ($_GET["expire"] == '2d') { $expire = time()+3600*48; $sel[4]='selected="selected"';}  
		 if ($_GET["expire"] == '7d') { $expire = time()+3600*24*7; $sel[5]='selected="selected"'; }
		 if ($_GET["expire"] == '1m') { $expire = time()+3600*24*30; $sel[6]='selected="selected"';} 
		 if ($_GET["expire"] == '2m') { $expire = time()+3600*24*60; $sel[7]='selected="selected"';} 
	 }
	 
	 $d = date("d", ($expire) );
	 $m = date("m", ($expire) );
	 $y = date("Y", ($expire) );
	 $h = date("H", ($expire) );
	 $i = date("i", ($expire) );
	 $button = "Add warn"; 
	 $warnc='';
	 if (isset($_GET["add"]) AND !empty($_GET["add"]) AND strlen($_GET["add"])>=2 ) {
	   $name = safeEscape( $_GET["add"]);
	 }
	 
	 }
	 
	 if ( isset($_POST["edit_ban"] ) AND isset($_GET["add"]) ) {
	 $name = ''; $admin=''; $gn='';$ip=''; $reason='';$server='';$warnc='';
	 }
	 ?>
	 <div align="center">
	 <h2><?=$button?></h2>
	 
	 <?php if (isset($_GET["add"])  ) { 
	 $name = safeEscape( $_GET["add"]);
	 ?>
	 <div align="left" class="padLeft">
	 <form action="" method="get">
	 Expire: <input type="hidden" name="warns" />
		 <input type="hidden" name="add" value="<?=$name?>" />
		 <select name="expire">
		    <option <?=$sel[1]?> value="1">1 hour</option>
			<option <?=$sel[2]?> value="10">10 hour</option>
			<option <?=$sel[3]?> value="1d">1 day</option>
			<option <?=$sel[4]?> value="2d">2 days</option>
			<option <?=$sel[5]?> value="7d">7 days</option>
			<option <?=$sel[6]?> value="1m">1 month</option>
			<option <?=$sel[7]?> value="2m">2 months</option>
		 </select>
		 <input type="submit" value="Auto set expire" class="menuButtons" />
	 </form>
	 </div>
	 <?php } ?>
	 
	 <form action="" method="post">
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
	   <tr class="row">
	     <td class="padLeft">Expire date:</td>
		 <td>
		 <input type="text" size="1" value="<?=$d?>" name="d" /> -
		 <input type="text" size="1" value="<?=$m?>" name="m" /> -
		 <input type="text" size="2" value="<?=$y?>" name="y" /> &nbsp; &nbsp;  
		 <input type="text" size="1" value="<?=$h?>" name="h" /> : 
		 <input type="text" size="1" value="<?=$i?>" name="i" /> 
		 DAY-MONTH-YEAR HOUR:MIN (hour: 0-23h)
		 <?php if ( isset($_GET["expire"]) ) { ?>
		 <div><b>Expire date: </b> <?=date($DateFormat, $expire)?></div>
		 <?php } ?>
		 </td>
	   </tr>
	   <tr class="row">
	     <td width="80"  class="padLeft">Warn count:</td>
		 <td><input type="text" size="1" value="<?=$warnc?>" name="warnc" /></td>
	   </tr>
	   <tr>
	     <td width="80"></td>
		 <td class="padTop padBottom">
		 <input type="submit" value="Submit" name="edit_ban" class="menuButtons" /> &nbsp; &nbsp; &nbsp; &nbsp;
		 <a class="menuButtons" href="<?=$website?>adm/?warns">&laquo; Back to Bans</a>
<?php if ( isset($_GET["edit"])  AND is_numeric($_GET["edit"])  ) { ?>
		 <a onclick="if (confirm('Delete warn?') ) { location.href='<?=$website?>adm/?warns&amp;del=<?=$id?>' }" class="menuButtons" href="javascript:;">&times; Delete Warn</a><?php } ?>
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
	$delete = $db->prepare( $sql );
	$result = $delete->execute();
	if ( $c ) { ?>Deleted <?=$c?> ban(s)<?php
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
  
  $sth = $db->prepare( "SELECT COUNT(*) FROM ".OSDB_BANS." WHERE id>=1 $sql AND YEAR(expiredate)>='1980'" );
  $result = $sth->execute();
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  
?>
<div align="center">
<?php
  $result_per_page = 20;
  $draw_pagination = 1;
  $SHOW_TOTALS = 1;
  include('pagination.php');
    
   $sth = $db->prepare("SELECT * FROM ".OSDB_BANS." 
   WHERE id>=1 $sql AND YEAR(expiredate)>='1980' ORDER BY id DESC LIMIT $offset, $rowsperpage");
   $result = $sth->execute();
   $message = ""; ?>
   <form method="post" name="delete" action="">
   <table>
    <tr>
	  <th width="180" class="padLeft"><input type="checkbox" onClick="toggle(this)" /> Player</th>
	  <th width="64">Action</th>
	  <th width="260">Info</th>
	  <th width="140">Banned by</th>
	  <th width="120">Date/Expire</th>
	</tr>
   <?php
    echo $message ;
   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { ?>
   <tr class="row" style="height:36px;">
     <td width="180" class="padLeft font12">
	 <input type="checkbox" name="checkbox[]" value="<?=$row["id"]?>"> 
	 <?php if ( isset($_GET["duplicate"]) ) { ?>
	 <a href="<?=OS_HOME?>adm/?warns=&search_bans=<?=trim($row["name"])?>"><span style="color:red;">[show]</span></a>
	 <?php } ?>
	 <a href="<?=$website?>adm/?warns&amp;edit=<?=$row["id"]?>"><?=$row["name"]?></a></td>
	 <td width="64" class="font12">
	 <a href="<?=$website?>adm/?warns&amp;edit=<?=$row["id"]?>"><img src="<?=$website?>adm/edit.png" alt="img" /></a>
	 <a href="javascript:;" onclick="if (confirm('Delete ban?') ) { location.href='<?=$website?>adm/?warns&amp;del=<?=$row["id"]?>&amp;n=<?=$row["name"]?>' }"><img src="<?=$website?>adm/del.png" alt="img" /></a>
	 </td>
	 <td width="260" class="overflow_hidden font12"><span title="<?=$row["reason"]?>"><?=stripslashes($row["reason"])?></span>
	 <div><b>Warns:</b> <?=$row["warn"]?></div>
	 <div><b>Expire:</b> <i><?=date($DateFormat, strtotime($row["expiredate"]))?></i></div>
	 </td>
	 <td width="140" class="font12"><?=$row["admin"]?></td>
	 <td width="120" class="font12">
	 <i><?=date($DateFormat, strtotime($row["date"]))?></i>
	 </td>
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