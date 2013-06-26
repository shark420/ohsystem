<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
 <script type="text/javascript" >
  function getHimg() {
    var img = document.getElementById("heroid").value;
	document.getElementById("himg").src = "../img/heroes/"+img+".gif?"+Math.random()*100;
  }
 </script>
 
<div align="center">
<h2><a href="<?=OS_HOME?>adm/?guides">Guides</a> <a class="menuButtons" href="<?=$website?>adm/?guides&amp;hid">Add a guide</a></h2>

<?php
  if ( $GuidesPage == 0 ) {
  ?>
  <h2>Note: Guides disabled. <a href="<?=$website?>adm/?cfg#pages2">Enable?</a></h2>
  <?php
  }
?>

<?php
if ( isset( $_GET["hid"]) ) {
   $hid = safeEscape( $_GET["hid"]);
   
   if ( isset($_POST["heroid"]) ) $hid = safeEscape( $_POST["heroid"]);
   
   $sth = $db->prepare("SELECT * FROM ".OSDB_HEROES." WHERE original!='' AND heroid = '".$hid."' ");
   $result = $sth->execute();
   if ( $sth->rowCount()>=1 ) {
   $row = $sth->fetch(PDO::FETCH_ASSOC);
   
   if ( isset($_POST["add_guide"]) AND isset($_POST["guide_url"]) ) {
      $url = EscapeStr($_POST["guide_url"]);
	  $title = EscapeStr( convEnt2($_POST["guide_title"]) );
	  $errors = ""; $edit =  "";
	  
	  $code = $_POST["code"];
	  
	  if ($code != $_SESSION["code"]) $errors.='<div><img src="'.$website.'adm/del.png" alt="edit" /> Invalid form</div>';
	  
	  if ( !strstr($url, "http") ) $errors.="<div>Link is not valid.</div>";
	  
	  if ( empty($errors) ) {
	   
	   if ( isset($_GET["edit"]) AND is_numeric($_GET["edit"]) ) {
	    $edit = safeEscape( (int) $_GET["edit"] );
		$sql = "AND id != '".$edit."'";
	   } else $sql  ="";
	   
	   $check = $db->prepare("SELECT * FROM ".OSDB_GUIDES." WHERE link = '".$url."' $sql ");
	   $result = $check->execute();
	   if ( $check->rowCount()>=1 ) {
	   $errors.="<div>Link already exists</div>";
	   } else {
	   
	     if (!isset($_GET["edit"]) ) {
	     $insert = $db->prepare("INSERT INTO ".OSDB_GUIDES." (hid, title, link) VALUES('".$hid."', '".$title."', '".$url."' )");
		 $result = $insert->execute();
	     } else { //UPDATE
	     $insert = $db->prepare("UPDATE ".OSDB_GUIDES." 
		 SET hid = '".$hid."', title = '".$title."', link = '".$url."' WHERE id = '".$edit."' ");
		 $result = $insert->execute();
	     }
	   
	   }
	   if ( isset($insert) AND $insert ) {
	    ?><h2>Guide successfully added</h2><?php
	   }
	  }
   }
   
  } else {
    $row["original"] = 'H06S';
  }
   
   if ( isset($errors) AND !empty($errors) ) {
   ?>
   <h2><?=$errors?></h2>
   <?php
   }
   
   if ( isset($_GET["edit"]) AND is_numeric($_GET["edit"]) ) {
   $id = safeEscape( (int) $_GET["edit"] );
   $sth = $db->prepare("SELECT * FROM ".OSDB_GUIDES." WHERE id = '".$id."' ");
   $result = $sth->execute();
   if ( $sth->rowCount()>=1 ) {
      $hrow = $sth->fetch(PDO::FETCH_ASSOC);
	  $hid = $hrow["hid"];
	  $title = $hrow["title"];
	  $link = $hrow["link"];
	  $button = "Edit guide";
   }
   } else {
   	  $hid = ""; $title = ""; $link = ""; $button = "Submit guide";
   }
   
   $code = generate_hash(10);
   $_SESSION["code"] = $code;
   ?>
   
  
   
   <form action="" method="post">
    <table>
	<tr>
	  <th></th>
	  <th></th>
	</tr>
	<tr>
	<td class="padLeft">
	<div style="margin-bottom:12px;">
	   <img id="himg" style="vertical-align: top;" src="<?=$website?>img/heroes/<?=$row["original"]?>.gif" alt="" />
	</div>
	   <?php 
	   $sth  = $db->prepare("SELECT * FROM ".OSDB_HEROES." 
	   WHERE original!='' GROUP BY (description) ORDER BY (description) ASC ");  
	   $result = $sth->execute();
	   ?>
	<select onchange = "getHimg()" name="heroid" id="heroid">
       <?php
       while ($row2 = $sth->fetch(PDO::FETCH_ASSOC)) {
	  
	    if ($row2["original"]  == trim($row["original"]) )  {
	    $sel="selected";
	    $cls = 'style="background-color: yellow"';
	   }
	    else { $sel = ""; $cls = ""; }
	   ?>
	   <option <?=$cls ?> <?=$sel?> value="<?=$row2["original"]?>"><?=$row2["description"]?></option>
	   <?php
	}
   ?>
   </select>
   <!--
	<div><input onclick="showhide('himg')" type="checkbox" name="misc" value="1" /> Misc guide</div>
   -->
	</td>
	<td>
	  Title:
	  <div>
	  <input type="text" value="<?=$title ?>" size="80" maxlength="255" name="guide_title" class="field" style="width:450px;" />
	  </div>
	   Guide url:
	  <div>
	    <input type="text" value="<?=$link ?>" size="80" maxlength="255" name="guide_url" class="field" style="width:450px;" />
	  </div>
	</td>
	</tr>
	<tr>
	 <td></td>
	 <td>
	 <input type="submit" value="<?=$button?>" name="add_guide" class="menuButtons" />
	 <input type="hidden" name="code" value="<?=$code?>" />
	 <div>&nbsp;</div>
	 </td>
	</tr>
	</table>
   </form>   
   <?php

}
?>


<?php
   $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_GUIDES."");
   $result = $sth->execute();
   $r = $sth->fetch(PDO::FETCH_NUM);
   $numrows = $r[0];
   $result_per_page = 30;
   $draw_pagination = 1;
   $SHOW_TOTALS = 1;
   include('pagination.php');
   
   $sth = $db->prepare("SELECT g.hid, g.link, g.id, g.title, h.original, h.description 
   FROM ".OSDB_GUIDES." as g
   LEFT JOIN ".OSDB_HEROES." as h ON h.heroid = g.hid
   ORDER BY g.id DESC
   LIMIT $offset, $rowsperpage");
   $result = $sth->execute();
   ?>
   <table>
    <tr>
	 <th class="padLeft">Guide</th>
	 <th>Hero</th>
	 <th width="64"></th>
	 <th>Title</th>
	</tr>

   <?php
   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { 
    ?>
	<tr>
	  <td width="55" class="padLeft">
	   <img style="vertical-align: top;" src="<?=$website?>img/heroes/<?=$row["original"]?>.gif" width="32" height="32" alt="" />
	  </td>
	  <td width="190"><?=$row["description"]?></td>
	  <td>
	  <a href="<?=$website?>adm/?guides&amp;hid=<?=$row["hid"]?>&amp;edit=<?=$row["id"]?>"><img src="<?=$website?>adm/edit.png" alt="edit" /></a>
	  <a href="javascript:;" onclick="if (confirm('Delete guide?') ) { location.href='<?=$website?>adm/?guides&amp;del=<?=$row["id"]?>' }"><img src="<?=$website?>adm/del.png" alt="edit" /></a>
	  </td>
	  <td><a href="<?=$row["link"]?>" target="_blank"><?=$row["title"]?></a></td>
	</tr>
	<?php
   }
?>
   </table>
   <?php include('pagination.php'); ?>
   
   
   
</div>