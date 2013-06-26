<?php
//Plugin: User profiles
//Author: Ivan
//Display user profile information

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '1';
//Enable edit plugin options
//$PluginOptions = '1';

if ($PluginEnabled == 1) {
  
    AddEvent("os_start",    "OS_UserProfiles");
	AddEvent("os_content",  "OS_DisplayProfile");
	
	function OS_UserProfiles() {
	  
	  global $MembersData;
	  global $CommentsData;
	  
	  //MEMBERLIST
	  if ( !empty($MembersData) ) {
	    for ($i=0; $i<count($MembersData); $i++ ) {
	    $MembersData[$i]["user_name"] = '<a href="'.OS_HOME.'?action=profile&amp;id='.$MembersData[$i]["id"].'">'.( $MembersData[$i]["user_name"] ).'</a>';
	    }
	  }
	  //COMMENTS
	  if ( !empty($CommentsData) ) {
	    for ($i=0; $i<count($CommentsData); $i++ ) {
		if (!empty($CommentsData[$i]["username"] ) )
	    $CommentsData[$i]["username"] = '<a href="'.OS_HOME.'?action=profile&amp;id='.$CommentsData[$i]["user_id"].'">'.( $CommentsData[$i]["username"] ).'</a>';
	    }
	  }

	}
	
	function OS_DisplayProfile() {
	
	global $db;
	  //USER DATA
	  if ( isset($_GET["action"]) AND isset($_GET["id"]) AND $_GET["action"] = "profile" AND is_numeric($_GET["id"]) ) {
	    $userID = safeEscape( (int) $_GET["id"] );
		
		$sth = $db->prepare("SELECT u.*, COUNT(c.user_id) as total_comments 
		FROM ".OSDB_USERS." as u 
		LEFT JOIN ".OSDB_COMMENTS." as c ON c.user_id = u.user_id
		WHERE u.user_id = :userID LIMIT 1");
		
		$sth->bindValue(':userID', $userID, PDO::PARAM_INT); 
		$result = $sth->execute();
		if ( $sth->rowCount()>=1 ) {
           while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
		   $avatar = $row["user_avatar"];
		   if ( empty($avatar) ) $avatar = OS_HOME."img/avatar_64.png";
		   
		   if ( file_exists("inc/geoip/geoip.inc") ) {
		   include_once("inc/geoip/geoip.inc");
		    $GeoIPDatabase = geoip_open("inc/geoip/GeoIP.dat", GEOIP_STANDARD);
	       	$Letter   = geoip_country_code_by_addr($GeoIPDatabase, $row["user_ip"]);
	       	$Country  = geoip_country_name_by_addr($GeoIPDatabase, $row["user_ip"]);
			
	       	if (empty( $Letter ) ) {
	       	$Letter  = "blank";
	       	$Country = "Reserved";
	       	}
			
		     geoip_close($GeoIPDatabase);
	        }
		   ?> 
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
    <div class="content section">
     <div class="widget Blog">
      <div class="blog-posts hfeed padTop padLeft entry clearfix">
	   <table>
	     <tr>
		    <td width="150"><img src="<?=$avatar?>" alt="user_avatar" width="150" /></td>
			<td>
			<h2><?=$row["user_name"]?> 
			<img <?=ShowToolTip($Country , OS_HOME.'img/flags/'.($Letter).'.gif', 130, 21, 15)?> class="imgvalign" width="21" height="15" src="<?=OS_HOME?>img/flags/<?=$Letter?>.gif" alt="" /> </h2>
			<?php if ( OS_is_admin() ) { ?>
	        <div class="padLeft"><?=$row["user_email"]?> | <?=EditUserLink( $userID )?></div>
	        <?php } ?>
			
			</td>
		 </tr>
	   </table>
	   
	   <table>
	     <tr>
		   <td width="130" class="padLeft"><b>Comments:</b></td> <td><?=$row["total_comments"]?></td>
		 </tr>
	     <tr>
		   <td width="130" class="padLeft"><b>Registered:</b></td> <td><?=date(OS_DATE_FORMAT, $row["user_joined"])?></td>
		 </tr>
	     <tr>
		   <td width="130" class="padLeft"><b>Location:</b></td> <td><?=($row["user_location"])?></td>
		 </tr>
	     <tr>
		   <td width="130" class="padLeft"><b>Realm:</b></td> <td><?=($row["user_realm"])?></td>
		 </tr>
	     <tr>
		   <td width="130" class="padLeft"><b>Website:</b></td> <td><?=AutoLinkShort($row["user_website"] , 'target="_blank"' )?></td>
		 </tr>
	     <tr>
		   <td width="130" class="padLeft"><b>Gender:</b></td> <td><?=UserGender($row["user_gender"])?></td>
		 </tr>
	     <tr>
		   <td width="130" class="padLeft"><b>Last login:</b></td> <td><?php if ($row["user_last_login"]>=1) echo date(OS_DATE_FORMAT, $row["user_last_login"])?></td>
		 </tr>
		 <?=os_custom_user_fields()?>
	   </table>
	   
	   <div class="padTop"></div>
	   
	   <?php
	   //GET LATEST COMMENTS
	   global $db;
	    $sth = $db->prepare("SELECT c.user_id, c.post_id, c.text, c.`date`, n.news_title
		FROM ".OSDB_COMMENTS." as c 
		LEFT JOIN ".OSDB_NEWS." as n ON n.news_id = c.post_id
		WHERE c.user_id = :userID AND n.status >= 1
		ORDER BY c.`date` DESC
		LIMIT 50");
		
		$sth->bindValue(':userID', $userID, PDO::PARAM_INT); 
		$result = $sth->execute();
		
		if ( $sth->rowCount() >=1 ) {
        ?>
		<h4>Latest comments</h4>
		<table>
		<?php
          while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
		  $ShortText = limit_words( convEnt($row["text"]), 30);
		  $ShortText = str_replace(array("'", '"'), array(" "," "), $ShortText);
		  ?>
		  <tr>
		    <td width="220"><a <?=ShowToolTip( $ShortText,  OS_HOME.'img/arrow.png', 290, 21, 15 )?> href="<?=OS_HOME?>?post_id=<?=$row["post_id"]?>#comments"><?=$row["news_title"]?></a></td>
			<td><?=date(OS_DATE_FORMAT, $row["date"])?></td>
		  </tr>
		  <?php
		  }
		?>
		</table>
		<?php
		}
	   ?>

	   <div style="margin-top:200px">&nbsp;</div>
     </div>
    </div>
   </div>
 </div>
</div>
		   <?php
		   }
        } else {
        header('location:'.OS_HOME.'?404');
        }		
	  }
	}

}
?>