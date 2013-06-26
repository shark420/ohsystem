<?php
//Plugin: Favorite Heroes
//Author: Ivan
//Users can create a list of 5 favorite heroes.

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';

$ThisPlugin = basename(__FILE__, '');

if ($PluginEnabled == 1  ) {
    
	//Favorite heroes hook function
    if ( OS_GetAction("favorite_heroes") AND is_logged() )  { AddEvent("os_content",  "OS_MyFavoriteHeroes"); $HomeTitle = "My Favorite Heroes"; }
	
	//Display login link for non-logged users
	if ( OS_GetAction("favorite_heroes") AND !is_logged() ) { AddEvent("os_content",  "OS_MyFavoriteHeroesLogin"); $HomeTitle = "My Favorite Heroes"; }
	
	//Delete favorite heroes
	if ( OS_GetAction("favorite_heroes") AND is_logged() AND isset($_GET["reset"]) )  AddEvent("os_start",  "OS_MyFavoriteHeroesReset");
	
	//Show favorite heroes on single user page
	if ( OS_GetAction("profile")  AND isset($_GET["id"]) )  AddEvent("os_custom_user_fields",  "OS_ShowFavoriteHeroes");
	
	//Link on Profile page
	if ( OS_profile_page() )  AddEvent("os_custom_user_fields",  "OS_FavoriteHeroesLinkOnProfile");
	
	//Menu MISC link
	AddEvent("os_add_menu_misc",  "OS_FavoriteHeroesMenuLink");
	AddEvent("os_add_menu_misc",  "OS_FavoriteHeroesListMenuLink");
	
	//User List of favorite heroes 
	if ( OS_GetAction("favorite_heroes_list") ) { AddEvent("os_content",  "OS_FavoriteHeroesList"); $HomeTitle = "Favorite Heroes"; }
	
	function OS_MyFavoriteHeroes() {
	?>
 <script type="text/javascript" >
  function getHimg() {
    var img = document.getElementById("heroid").value;
	document.getElementById("himg").src = "<?=OS_HOME?>img/heroes/"+img+".gif?"+Math.random()*100;
  }
  
  function AddHeroToList() {
    var hid = document.getElementById("heroid").value;
	var c = document.getElementById("hslot").value;
	
	if ( c<6 ) {
	document.getElementById("hero"+c).src = "<?=OS_HOME?>img/heroes/"+hid+".gif?"+Math.random()*100;
	document.getElementById("FavHero"+c).value = hid;
	c++;
	  for (var i=1;i<5;i++) {
	    if ( i == c-1 ) { var nothing = 1 }
		else {
		 heroID = document.getElementById("FavHero"+i).value;
		   if ( heroID == hid) {
		    c=c-1;
			document.getElementById("hero"+c).src = "<?=OS_HOME?>img/heroes/blank.gif?"+Math.random()*100;
			document.getElementById("FavHero"+c).value="";
			alert("You already have that hero in the list");
		   }
		}
	  }
	document.getElementById("hslot").value = c;
	} 
	
	if ( c>=6 ) {
	document.getElementById("hinfo").innerHTML = '<input type="submit" value="Create list" class="menuButtons" name="addFavoriteHeroes" /> <span style="padding-left: 200px;"><a href="<?=OS_HOME?>?action=favorite_heroes&amp;reset">Reset list</a></span>';
	}
  }
 </script>
 
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section" id="content recent-posts">
    <div class="widget Blog" id="Blog1">
     <div class="blog-posts hfeed">
	 <div align="left" class="entry clearfix padLeft padTop">
	   <div>&nbsp;</div>
	   
	   <?php
	   if ( isset( $_POST["addFavoriteHeroes"]) ) {
	      $errors = "";
	      $userID = (int) $_SESSION["user_id"];
		  
		  if ( isset($_POST["FavHero1"]) AND  isset($_POST["FavHero2"]) AND  isset($_POST["FavHero3"]) AND  isset($_POST["FavHero4"]) AND  isset($_POST["FavHero5"]) ) {
		  
		  $Favorite1 = safeEscape($_POST["FavHero1"]);
		  $Favorite2 = safeEscape($_POST["FavHero2"]);
		  $Favorite3 = safeEscape($_POST["FavHero3"]);
		  $Favorite4 = safeEscape($_POST["FavHero4"]);
		  $Favorite5 = safeEscape($_POST["FavHero5"]);
		  
		  } else $errors.='You need to select 5 heroes';
		  
		  if ( empty($errors) ) {
		    $field_id = $userID;
			$field_name = time()."_favorite_heroes_";
			$field_value = $Favorite1.'|'.$Favorite2.'|'.$Favorite3.'|'.$Favorite4.'|'.$Favorite5;
			OS_add_custom_field($field_id, $field_name, $field_value);
		  }
		  
	   }

	   
	   global $db;
	   global $DateFormat;
	   $userID = (int) $_SESSION["user_id"];
	   $sth = $db->prepare("SELECT * FROM ".OSDB_CUSTOM_FIELDS." WHERE field_id =:userID AND field_name LIKE('%_favorite_heroes_') ");
	   
	   $sth->bindValue(':userID', (int) $userID, PDO::PARAM_INT); 
	   $result = $sth->execute();
	   
	   if ( $sth->rowCount()>=1 ) {

	     $row = $sth->fetch(PDO::FETCH_ASSOC);
		  
		  $AllHeroes = explode("|", $row["field_value"]);
		  $DateTime = explode("_", $row["field_name"]);
		  $Date = $DateTime[0];
		  ?>
		  <h2 class="title">List of your favorite heroes</h2>
			   
		  <div style="margin-top: 60px;"><?php
		  foreach ( $AllHeroes as $Hero ) {
		    if ( empty($Hero) ) $icon = 'blank'; else $icon = $Hero;
			?>
			<img id="hero1" style="vertical-align: top;" src="<?=OS_HOME?>img/heroes/<?=$icon?>.gif" alt="" /> 
			<?php
		  }
		  ?>
		  <div style="margin-top:30px;">
		  <div><i>List created: <?=date($DateFormat, $Date)?></i></div>
		  <a href="javascript:;" onclick="location.href='<?=OS_HOME?>?action=favorite_heroes&amp;reset'" class="menuButtons" href="">Reset list?</a></div>
		  </div><?php
	   } else {
	   
	   ?>
	   
	   <h2 class="title">Add your favorite heroes in the list</h2><?php
	    if ( isset($errors) AND !empty($errors) ) { ?><h4><?=$errors?></h4><?php }
	   $sth  = $db->prepare("SELECT * FROM ".OSDB_HEROES." 
	   WHERE original!='' GROUP BY LOWER(description) ORDER BY LOWER(description) ASC ");  
	   $result = $sth->execute();
	   ?>
	    <img id="himg" style="vertical-align: top;" src="<?=OS_HOME?>img/heroes/H06S.gif" alt="" />
	<form action="" method="post">	
	<select onchange = "getHimg()" name="heroid" id="heroid">
       <?php
       while ($row2 = $sth->fetch(PDO::FETCH_ASSOC)) {
	   ?>
	   <option value="<?=$row2["original"]?>"><?=$row2["description"]?></option>
	   <?php
	}
   ?>
   </select>
   
   <input type="button" value="Add hero to list" class="menuButtons" onclick="AddHeroToList()"  />
   
   <input type="hidden" name="hslot" id="hslot" value="1" />
   
   <input type="hidden" id="FavHero1" name="FavHero1" value="" />
   <input type="hidden" id="FavHero2" name="FavHero2"  value="" />
   <input type="hidden" id="FavHero3" name="FavHero3" value="" />
   <input type="hidden" id="FavHero4" name="FavHero4" value="" />
   <input type="hidden" id="FavHero5" name="FavHero5" value="" />
   
    <div style="margin-top: 60px;">
	   <img id="hero1" style="vertical-align: top;" src="<?=OS_HOME?>img/heroes/blank.gif" alt="" /> 
	   <img id="hero2" style="vertical-align: top;" src="<?=OS_HOME?>img/heroes/blank.gif" alt="" /> 
	   <img id="hero3" style="vertical-align: top;" src="<?=OS_HOME?>img/heroes/blank.gif" alt="" /> 
	   <img id="hero4" style="vertical-align: top;" src="<?=OS_HOME?>img/heroes/blank.gif" alt="" /> 
	   <img id="hero5" style="vertical-align: top;" src="<?=OS_HOME?>img/heroes/blank.gif" alt="" /> 
	</div>
	
	<div id="hinfo">
	
	</div>
	</form>
	<?php } ?>
	   <div style="margin-top: 180px;"></div>
	  </div> 
     </div>
    </div>
   </div>
  </div>
</div>
	<?php
	}
	
	
	function OS_MyFavoriteHeroesLogin() {
	?>
	<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section" id="content recent-posts">
    <div class="widget Blog" id="Blog1">
     <div class="blog-posts hfeed">
	 <div align="left" class="entry clearfix padLeft padTop">
	    <h2>Please <a href="<?=OS_HOME?>?login">login in</a> to continue</h2>
	 	<div style="margin-top: 180px;"></div>
	  </div> 
     </div>
    </div>
   </div>
  </div>
</div>
	<?php
	}

   function OS_MyFavoriteHeroesReset() {
     global $db;
	 $userID = (int) $_SESSION["user_id"];
	 $sth = $db->prepare("DELETE FROM ".OSDB_CUSTOM_FIELDS." WHERE field_id =:userID AND field_name LIKE('%_favorite_heroes_') ");
	 $sth->bindValue(':userID', (int) $userID, PDO::PARAM_INT); 
	 $result = $sth->execute();
	 
	 header("location: ".OS_HOME."?action=favorite_heroes");
	 die;
   }
   
   function OS_ShowFavoriteHeroes() {
     global $db;
	 $userID = (int) safeEscape($_GET["id"]);
	   $sth = $db->prepare("SELECT * FROM ".OSDB_CUSTOM_FIELDS." WHERE field_id =:userID AND field_name LIKE('%_favorite_heroes_') ");
	   $sth->bindValue(':userID', (int) $userID, PDO::PARAM_INT); 
	   $result = $sth->execute();
	   if ( $sth->rowCount()>=1 ) {

	      $row = $sth->fetch(PDO::FETCH_ASSOC);
		  
		  $AllHeroes = explode("|", $row["field_value"]);
		  ?>
		  <tr>
			  <td class="padLeft"><b>Favorite heroes:</b></td>
			  <td>
		  <?php
		  foreach ( $AllHeroes as $Hero ) {
		    if ( empty($Hero) ) $icon = 'blank'; else $icon = $Hero;
			?>
			<a href="<?=OS_HOME?>?hero=<?=$icon?>"><img id="<?=$icon?>" width="40" height="40" style="vertical-align: top;" src="<?=OS_HOME?>img/heroes/<?=$icon?>.gif" alt="" /></a>
			<?php
		  }
		  ?>
		      </td>
		  </tr>
		  <?php
	   }
	 
   }
   
   function OS_FavoriteHeroesLinkOnProfile() {
   ?>
   <tr class="row">
      <td class="padLeft">Favorite Heroes</td>
	  <td><a href="<?=OS_HOME?>?action=favorite_heroes">Create list of favorite heroes</a></td>
   </tr>
   <?php
   }
   
   function OS_FavoriteHeroesMenuLink() {
   ?>
   <li><a href="<?=OS_HOME?>?action=favorite_heroes">My Favorite Heroes</a></li>
   <?php
   }
   
   function OS_FavoriteHeroesListMenuLink() {
   ?>
    <li><a href="<?=OS_HOME?>?action=favorite_heroes_list">User Top Favorite Heroes</a></li>
   <?php
	 
   }
   
   function OS_FavoriteHeroesList() {
   global $db;
   
   ?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section" id="content recent-posts">
    <div class="widget Blog" id="Blog1">
     <div class="blog-posts hfeed">
	 <div align="left" class="entry clearfix padLeft padTop">
	   <div>&nbsp;</div>

       <h2>List of favorite heroes from users</h2>
	   
	   <?php
	      $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_CUSTOM_FIELDS." WHERE field_name LIKE('%_favorite_heroes_') ");
		  $result = $sth->execute();
		  
	      $r = $sth->fetch(PDO::FETCH_NUM);
	      $numrows = $r[0];
	      $result_per_page = 10;
		  $rowsperpage  = $result_per_page;
		  $offset = os_offset( $numrows, $result_per_page );
	      $draw_pagination = 0;
	      $website = OS_HOME;
	      $prefix ="?action=favorite_heroes_list";
	      $end = '';
	      $draw_pagination = 1;
		  
		  $sth = $db->prepare("SELECT c.*, u.user_name
		  FROM ".OSDB_CUSTOM_FIELDS." as c 
		  LEFT JOIN ".OSDB_USERS." as u ON u.user_id = c.field_id
		  WHERE c.field_name LIKE('%_favorite_heroes_') 
		  ORDER BY c.field_name DESC
		  LIMIT $offset, $rowsperpage");
		  $result = $sth->execute();
		  ?>
		  <table>
		  <?php
		   while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
		    $AllHeroes = explode("|", $row["field_value"]);
		   ?>
		   <tr class="row">
		     <td class="padLeft" width="180"><a href="<?=OS_HOME?>?action=profile&amp;id=<?=$row["field_id"]?>"><?=$row["user_name"]?></a></td>
			 <td><?php
		     foreach ( $AllHeroes as $Hero ) {
		    if ( empty($Hero) ) $icon = 'blank'; else $icon = $Hero;
			?>
			<a href="<?=OS_HOME?>?hero=<?=$icon?>"><img id="<?=$icon?>" width="64" height="64" style="vertical-align: top;" src="<?=OS_HOME?>img/heroes/<?=$icon?>.gif" alt="" /></a>
			<?php
		    }
		    ?>
		     <div>&nbsp;</div>
		    </td>
		   </tr>
		   <?php
		   }
		   ?></table><?php
		  os_pagination( $numrows, $result_per_page, 5, 1 );
	   ?>
	     <div>&nbsp;</div>
	     <div>
		   <a class="menuButtons" href="<?=OS_HOME?>?action=favorite_heroes">Create your list of favorite heroes</a>
		 </div>
	 	<div style="margin-top: 180px;"></div>
	  </div> 
     </div>
    </div>
   </div>
  </div>
</div>	   
	<?php
   }
  
}
?>