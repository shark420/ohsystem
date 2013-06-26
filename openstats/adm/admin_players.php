<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
$errors = "";
if ( isset($_GET["search_users"]) ) $s = safeEscape($_GET["search_users"]); else $s=""; 
?>

<div align="center" class="padBottom">
	 <form action="" method="get">
	 <table>
	   <tr>
	    <td width="290">
		  <input type="hidden" name="players" />
		  <input style="width: 180px; height: 24px;" type="text" name="search_users" value="<?=$s?>" />
		  <input class="menuButtons" type="submit" value="Search players" />
		</td>
	   </tr>
	 </table>
	 </form>
</div>
<?php
 
  //OPTIONS
  //admins - remove
  $del = 1;
  if ( isset($_GET["remove_admin"]) AND !empty($_GET["remove_admin"]) ) {
    $remove = safeEscape( trim($_GET["remove_admin"]) );
	$sth = $db->prepare("DELETE FROM ".OSDB_ADMINS." WHERE (name) = ('".$remove."') ");
	$result = $sth->execute();
	if ( $del ) echo '<h2>User removed from admins</h2>';
	$sth = $db->prepare("UPDATE ".OSDB_STATS." SET admin = 0 WHERE (player) = ('".$remove."')");
	$result = $sth->execute();
  }
  //safelist - remove
    if ( isset($_GET["remove_safe"]) AND !empty($_GET["remove_safe"]) ) {
    $remove = safeEscape( trim($_GET["remove_safe"]) );
	$sth = $db->prepare("DELETE FROM ".OSDB_SAFELIST." WHERE (name) = ('".$remove."') ");
	$result = $sth->execute();
	if ( $del ) echo '<h2>User removed from safelist</h2>';
	$sth = $db->prepare("UPDATE ".OSDB_STATS." SET safelist = 0 WHERE (player) = ('".$remove."')");
	$result = $sth->execute();
  }
  
  //ban - remove
    if ( isset($_GET["remove_ban"]) AND !empty($_GET["remove_ban"]) ) {
    $remove = safeEscape( trim($_GET["remove_ban"]) );
	$sth = $db->prepare("DELETE FROM ".OSDB_BANS." WHERE (name) = ('".$remove."') ");
	$result = $sth->execute();
	if ( $del ) echo '<h2>User removed from Bans</h2>';
	$sth = $db->prepare("UPDATE ".OSDB_STATS." SET banned = 0, warn=0, warn_expire='0000-00-00 00:00:00' 
	WHERE (player) = ('".$remove."')");
	$result = $sth->execute();
  }
  
  //warn - remove
    if ( isset($_GET["remove_warn"]) AND !empty($_GET["remove_warn"]) ) {
    $remove = safeEscape( trim($_GET["remove_warn"]) );
	$sth = $db->prepare("DELETE FROM ".OSDB_BANS." WHERE (name) = ('".$remove."') ");
	$result = $sth->execute();
	if ( $del ) echo '<h2>User removed from Warns</h2>';
	$sth = $db->query("UPDATE ".OSDB_STATS." SET banned = 0, warn=0, warn_expire='0000-00-00 00:00:00' 
	WHERE (player) = ('".$remove."')");
	$result = $sth->execute();
  }
   
   //SEARCH
   if ( isset($_GET["search_users"]) AND strlen($_GET["search_users"])>=2 ) {
     $search_users = safeEscape( $_GET["search_users"]);
	 $sql = " AND (player) LIKE ('%".$search_users."%') ";
  } else {
   $sql = "";
   $search_users= "";
  }
  
  if ( isset($_GET["sort"])   AND $_GET["sort"] == 'admins' )   $sql.=' AND admin>=1 '; 
  if ( isset($_GET["sort"])   AND $_GET["sort"] == 'banned' )   $sql.=' AND banned>=1 '; 
  if ( isset($_GET["sort"])   AND $_GET["sort"] == 'safelist' ) $sql.=' AND safelist>=1 '; 
  if ( isset($_GET["sort"])   AND $_GET["sort"] == 'warns' )    $sql.=" AND warn>=1"; 
  
  $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_STATS." WHERE id>=1 $sql ");
  $result = $sth->execute();
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  $result_per_page = 30;
    
  $draw_pagination = 1;
  $SHOW_TOTALS = 1;
  include('pagination.php');
  
  $sth = $db->prepare("SELECT * FROM ".OSDB_STATS." WHERE id>=1 $sql 
  ORDER BY score DESC LIMIT $offset, $rowsperpage");
  $result = $sth->execute();
  
  ?>
  <div align="center">
  
  Show: 
  <a class="menuButtons" href="<?=OS_HOME?>adm/?players&amp;sort=admins">Admins</a>
  <a class="menuButtons" href="<?=OS_HOME?>adm/?players&amp;sort=banned">Banned</a>
  <a class="menuButtons" href="<?=OS_HOME?>adm/?players&amp;sort=safelist">On Safelist</a>
  <a class="menuButtons" href="<?=OS_HOME?>adm/?players&amp;sort=warns">Warns</a>
  <a class="menuButtons" href="<?=OS_HOME?>adm/?players">All ranked players</a>
   <table>
    <tr>
	  <th width="220" class="padLeft">Player</th>
	  <th width="120">Score</th>
	  <th width="180">Action</th>
	  <th class="padLeft">Status</th>
	</tr>
    <?php
	 if ( file_exists("../inc/geoip/geoip.inc") ) {
	 include("../inc/geoip/geoip.inc");
	 $GeoIPDatabase = geoip_open("../inc/geoip/GeoIP.dat", GEOIP_STANDARD);
	 $GeoIP = 1;
	 }
	 
	 if ( isset($_GET["page"]) AND is_numeric($_GET["page"]) ) $p = '&amp;page='.safeEscape( $_GET["page"] );
	 else $p = '';
	 
	 if ( isset($_GET["sort"]) ) $p.= '&amp;sort='.safeEscape( $_GET["sort"] );
	 else $p = '';
	 
	 //LOOP
	 while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
   
	if ($GeoIP == 1 ) {
	$Letter   = geoip_country_code_by_addr($GeoIPDatabase, $row["ip"]);
	$Country  = geoip_country_name_by_addr($GeoIPDatabase, $row["ip"]);
	}
	if ($GeoIP == 1 AND empty($Letter) ) {
	$Letter = "blank";
	$Country  = "Reserved";
	}
	
	if ( $row["admin"] >= 1 )    
	$is_admin = '<img width="16" height="16" src="ranked.png" alt="" class="imgvalign"/> Admin'; 
	else $is_admin = "";
	
	if ( $row["safelist"] >= 1 ) $is_safe = '<img width="16" height="16" src="check.png" alt="" class="imgvalign"/> Safelist'; else $is_safe = "";
	
	if ( $row["banned"] >= 1 )   $banned = '<img width="16" height="16" src="del.png" alt="" class="imgvalign"/>  <span style="color:red">Banned</span>'; else $banned = "";
	
	if ( $row["warn"] >= 1 ) {
	$warnDate = date( $DateFormat, strtotime($row["warn_expire"]) );
	$warn = '<span style="color:red">Warned: '.$row["warn"]."x (expire: $warnDate) </span>"; 
	}
	else $warn = "";
	?>
	<tr class="row">
	  <td><img <?=ShowToolTip($Country , OS_HOME.'img/flags/'.$Letter.'.gif', 130, 21, 15)?> class="imgvalign" width="21" height="15" src="<?=OS_HOME?>img/flags/<?=$Letter?>.gif" alt="" /> 
	  <a href="<?=OS_HOME?>?u=<?=$row["id"]?>"><?=$row["player"]?></a>
	  </td>
	  <td><?=number_format($row["score"],0)?></td>
	  <td>
	  <a href="javascript:;" onclick="showhide('o_<?=$row["id"]?>')">[+] edit</a>
	  <div id="o_<?=$row["id"]?>" style="display:none;">
	  
	  <div><?=$row["player"]?></div>
	  
	  <div>
	  <?php if (!empty($is_admin) ) { ?>
	  <a class="menuButtons" href="javascript:;" onclick="if (confirm('Remove admin?') ) { location.href='<?=OS_HOME?>adm/?players&amp;remove_admin=<?=$row["player"]?><?=$p?>' }">&raquo; Remove admin</a>
	  <?php } else { ?>
	  <a class="menuButtons" href="javascript:;" onclick="if (confirm('Set user as admin?') ) { location.href='<?=OS_HOME?>adm/?players&admins&amp;add=<?=$row["player"]?>' }">&raquo; Add as admin</a>
	  <?php } ?>
	  </div>
	  
	  <div>
	  <?php if (!empty($is_safe) ) { ?>
	  <a class="menuButtons" href="javascript:;" onclick="if (confirm('Remove from safelist?') ) { location.href='<?=OS_HOME?>adm/?players&amp;remove_safe=<?=$row["player"]?><?=$p?>' }">&raquo; Remove from safelist</a>
	  <?php } else { ?>
	  <a class="menuButtons" href="javascript:;" onclick="if (confirm('Add user to safelist?') ) { location.href='<?=OS_HOME?>adm/?safelist&amp;add=<?=$row["player"]?>' }">&raquo; Add on Safelist</a>
	  <?php } ?>
	   </div>
	   
	  <div>
	  <?php if (!empty($banned) ) { ?>
	  <a class="menuButtons" href="javascript:;" onclick="if (confirm('Remove from bans?') ) { location.href='<?=OS_HOME?>adm/?players&amp;remove_ban=<?=$row["player"]?><?=$p?>' }">&raquo; Remove Ban</a>
	  <?php } else { ?>
	  <a class="menuButtons" href="javascript:;" onclick="if (confirm('Ban player?') ) { location.href='<?=OS_HOME?>adm/?bans&amp;add=<?=$row["player"]?>' }">&raquo; Ban player</a>
	  <?php } ?>
	   </div>
	   
	  <div>
	  <?php if (!empty($warn) ) { ?>
	  <a class="menuButtons" href="javascript:;" onclick="if (confirm('Remove Warn?') ) { location.href='<?=OS_HOME?>adm/?players&amp;remove_warn=<?=$row["player"]?><?=$p?>' }">&raquo; Remove Warn</a>
	  <?php } else { ?>
	  <a class="menuButtons" href="javascript:;" onclick="if (confirm('Warn player?') ) { location.href='<?=OS_HOME?>adm/?warns&amp;add=<?=$row["player"]?>' }">&raquo; Warn player</a>
	  <?php } ?>
	   </div>
	   
	  </div>
	  </td>
	  <td>
	  <?=$is_admin?> 
	  <?=$is_safe?> 
	  <?=$banned?> 
	  <?=$warn?> 
	  </td>
	</tr>
	<?php
	}
	if ( isset($GeoIP) AND $GeoIP == 1) geoip_close($GeoIPDatabase);
?>
  </table>
  <?php  include('pagination.php'); ?>
  </div>
  <div style="margin-top: 180px;">&nbsp;</div>