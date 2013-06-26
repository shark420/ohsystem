<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div align="center">
<?php

if ( isset($_GET["del"]) ) {
   $res = 1;
   $sth = $db->prepare("DELETE FROM ".OSDB_ITEMS." WHERE itemid = '".safeEscape($_GET["del"])."' LIMIT 1 ");
   $result = $sth->execute();
   if ($res) {
   ?>
   <h2>Item successfully deleted</h2>
   <?php
   }
}

$name = "";
$shortname = "";
$item_info = "";
$price = "";
$type = "";
$icon = "AbyssalBlade.gif";
$itemID = "";

if ( isset($_GET["edit"]) OR isset($_GET["add"]) ) {

   if ( isset($_GET["edit"])) $edit = safeEscape( $_GET["edit"]); else $edit = "";
   
   if ( isset($_POST["edit_item"]) ) {
   $icon = safeEscape($_POST["icon"]);
   $name = convEnt2($_POST["name"]);
   $shortname = convEnt2($_POST["shortname"]);
   
   $item_info = (my_nl2br(convEnt2(trim($_POST["item_info"]))));
   $item_info = str_replace(array("&Scaron;", "&scaron;"),array("Š","š"), $item_info   );
	  
   $price = EscapeStr($_POST["price"]);
   $type = EscapeStr($_POST["type"]);
   $icon = EscapeStr($_POST["icon"]);
   
   if ( strlen($name)>=2 AND strlen($shortname)>=2 ) {
   
   
   if (isset($_GET["edit"]) ) {
        $upd = 1;
	    $update = $db->update(OSDB_ITEMS, array(
		   "name" => $name, 
		   "shortname" => $shortname, 
		   "item_info" => $item_info, 
		   "price" => $price, 
		   "type" => $type, 
		   "icon" => $icon),
		                        "itemid = '".$edit."' ");
	 
	 if ($upd) {
	 ?><h2>Item successfully updated</h2><?php
	 }
	 
	 } //END EDIT
	 else
	 if (isset($_GET["add"]) ) {
	 $id = safeEscape(strtoupper($_POST["itemid"]));
	 
	 $sth = $db->prepare("SELECT * FROM ".OSDB_ITEMS." WHERE (itemid) = ('".$id."')  LIMIT 1 ");
	 $result = $sth->execute();
	 if ($check->rowCount()>=1 )  echo "<h2>Item ID already exists. <a href='".OS_HOME."adm/?items&amp;edit=".$id."'>View</a></h2>";  else
	 
	 if ( strlen($id)<=2 ) echo "<h2>Item ID does not have enought characters</h2>";  
	 else {
	      $ins = 1;
		  $db->insert( OSDB_ITEMS, array(
		  "itemid" => $id,
		  "code" => 0,
		  "name" => $name,
		  "shortname" => $shortname,
		  "item_info" => $item_info,
		  "price" => $price,
		  "type" => $type,
		  "icon" => $icon
                                 ));
	 	 if ($ins) {
	 ?><h2>Item successfully added</h2><?php
	  }
	}
  }
	 
	 
   
   } else echo "<h2>Name does not have enought characters</h2>";
   
   }
   
if ( isset($_GET["edit"]) ) {
   $sth = $db->prepare("SELECT * FROM ".OSDB_ITEMS." WHERE itemid = '".$edit."' LIMIT 1");
   $result = $sth->execute();
   $row = $sth->fetch(PDO::FETCH_ASSOC);
   $name = $row["name"];
   $shortname = $row["shortname"];
   $item_info = $row["item_info"];
   $price = $row["price"];
   $type = $row["type"];
   $icon = $row["icon"];
   $itemID = $row["itemid"];
   $button = "Edit Item";
   } else $button = "ADD Item";
   
   ?>
	
 <script type="text/javascript" >
  function getItemIMG() {
    var img = document.getElementById("itemicon").value;
	document.getElementById("tempImg").src = "../img/items/"+img+"?"+Math.random()*100;
	document.getElementById("IIcon").value = img;
  }
 </script>
   
<h2>Edit item</h2>
 <form action method="post">
   <table class="Table500px">

<tr>
   <td>Item ID:</td>
   <td><?php if (isset($_GET["add"]) ) { ?><input  class="field" type="text" value="" name="itemid" /><?php } else { ?><?=$itemID?><?php } ?></td>
 </tr>
    <tr>
	  <td class="padLeft"><div class="padTop"></div>Icon:</td><td>
	  <div class="padTop"></div>
	  <?php if (isset($_GET["edit"]) OR isset($_GET["add"]) ) { ?>
   <?php
   if ($handle = opendir("../img/items")) {
   ?>
   <img id="tempImg" src="<?=$website?>img/items/<?=$icon?>" alt="" style="vertical-align: top;" />
   <select onchange = "getItemIMG()" name="itemicon" id="itemicon">
   <?php
   while (false !== ($file = readdir($handle))) 
	{
	  if (substr($file,-3) == "gif" ) {
	  
	  if (trim( str_replace(".php", "", $file) ) == trim($icon)."")  {
	  $sel="selected";
	  $cls = 'style="background-color: yellow"';
	  }
	  else { $sel = ""; $cls = ""; }
	  ?>
	  <option <?=$cls ?> <?=$sel?> value="<?=$file?>"><?=$file?></option>
	  <?php
	 }
	}
   ?>
   </select>
   <?php } ?>
	  <?php } ?>
	    <input  class="field" type="text" value="<?=$icon?>" id="IIcon" name="icon" />
	  <div class="padTop"></div></td>
	</tr>
	
    <tr>
	  <td class="padLeft">Name:</td><td><input class="field" type="text" name="name" value="<?=$name?>"  /></td>
	</tr>
    <tr>
	  <td class="padLeft">Short name:</td><td><input  class="field" type="text" value="<?=$shortname?>" name="shortname" /></td>
	</tr>
    <tr>
	  <td class="padLeft">Description:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><div><textarea class="ckeditor" cols="90" id="editor1" name="item_info" rows="20"><?=$item_info?></textarea></div></td>
	</tr>
    <tr>
	  <td class="padLeft"><div class="padTop"></div>Price:</td><td><div class="padTop"></div><input  class="field" type="text" value="<?=$price?>" name="price" /><div class="padTop"></div></td>
	</tr>
    <tr>
	  <td class="padLeft"><div class="padTop"></div>Type:</td><td>
	  <div class="padTop"></div>
	  <select name="type">
	  <?php if ($type=="basic") $sel='selected="selected"'; else $sel =""; ?>
	  <option <?= $sel?> value="basic">basic</option>
	  <?php if ($type=="recipe") $sel='selected="selected"'; else $sel =""; ?>
	  <option <?= $sel?> value="recipe">recipe</option>
	  </select>
	  <div class="padTop"></div></td>
	</tr>
	
   <tr>
     <td></td>
	 <td>
       <div class="padTop"></div>
	   <input type="submit" value="<?= $button?>" class="menuButtons" name="edit_item" />
	   <span class="padLeft">
<?php if (isset($_GET["edit"]) ) { ?>
	   <input type="button" onclick="if (confirm('Delete Item?') ) { location.href='<?=$website?>adm/?items&amp;del=<?=$edit?>' }" value="Delete Item" class="menuButtons" /><?php } ?>
	   	   <a class="menuButtons" href="<?=$website?>?item=<?=$edit?>">View</a>
	   </span>

	   <div class="padTop"></div>
     </td>
   </tr>
	
	</table>
</form>
	 <script type="text/javascript" src="<?php echo $website;?>adm/editor.js"></script>
   <?php
}

?>
<a href="<?=$website?>adm/?items&amp;add" class="menuButtons">[+] ADD Item</a>
<?php if (!isset($_GET["show_all"]) ) { ?>
<a href="<?=$website?>adm/?items&amp;show_all" class="menuButtons">Display ALL</a>
<?php } else { ?>
<a href="<?=$website?>adm/?items" class="menuButtons">Display Default</a>
<?php } ?>
<?php
if ( isset($_GET["show_all"])  ) {
  $sth =  $db->prepare("SELECT COUNT(*) FROM ".OSDB_ITEMS." LIMIT 1");
  $result = $sth->execute();
  $r = $sth->fetch(PDO::FETCH_NUM);
  $numrows = $r[0];
  $result_per_page = 30;
  $draw_pagination = 1;
  $sql="";
} else {
  $sth =  $db->prepare("SELECT * FROM ".OSDB_ITEMS." WHERE item_info!='' GROUP BY (shortname)");
  $result = $sth->execute();
  $numrows = $sth->rowCount();
  $result_per_page = 30;
  $draw_pagination = 1;
  $sql="WHERE item_info!='' GROUP BY (shortname)";
}

  $SHOW_TOTALS = 1;
  include('pagination.php');
  $sth  = $db->prepare("SELECT * FROM ".OSDB_ITEMS." $sql 
  ORDER BY (shortname) ASC 
  LIMIT $offset, $rowsperpage");
  $result = $sth->execute();
  $add = "";
  if ( isset($_GET["show_all"]) ) $add.="&amp;show_all";
  if ( isset($_GET["page"]) ) $add.="&amp;page=".safeEscape( (int)$_GET["page"] );
?>
  <table>
    <tr>
	  <th width="74" class="padLeft">Item</th>
	  <th width="220">Item name</th>
	  <th>Description</th>
	</tr>
<?php
while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {

if ( isset($_GET["edit"]) AND $_GET["edit"] == $row["itemid"] ) $border = 'style="border:6px solid #FCC200;"';
else $border  ="";
  ?>
  <tr>
    <td width="74"><a href="<?=$website?>adm/?items&amp;edit=<?=$row["itemid"].$add?>"><img <?=$border?> src="<?=$website?>img/items/<?=$row["icon"]?>" alt="*" /></a></td>
	<td width="220"><a href="<?=$website?>adm/?items&amp;edit=<?=$row["itemid"].$add?>"><b><?=$row["shortname"]?></b></a>
	<div class="font12"><?=$row["type"]?>, Price: <?=$row["price"]?></div></td>
	<td><?=limit_words(convEnt($row["item_info"]),14)?></td>
  </tr>
  <?php
}
   ?>
   </table>
<?php 
include('pagination.php');

?>
</div>