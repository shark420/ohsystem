<?php
//Plugin: Online Users
//Author: Ivan
//Track users online

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';

$PluginOptions = '1';

$ThisPlugin = basename(__FILE__, '');

   $OnlineTime = 600; 
   $GuestSessionTime = 300; //300 sec - 5 min
   
define("OS_OnlineTime" ,       600); //600 sec - 10 min
define("OS_GuestSessionTime" , 300); //300 sec - 5 min

	//On plugin activation create database table if not exists
    if ( PluginEnabled( $ThisPlugin ) ) {
	
        $sth = $db->prepare("SHOW TABLES LIKE 'online_users'");
		$result = $sth->execute();
		
		if ( $sth->rowCount()<=0 ) {
		 $createTB = $db->prepare ('CREATE TABLE IF NOT EXISTS `online_users` (
		 `user_id` int(11) NOT NULL,
		 `timedate` int(11) NOT NULL,
		 `user_ip` varchar(20) COLLATE utf8_bin NOT NULL,
		 `user_agent` varchar(100) COLLATE utf8_bin NOT NULL,
		 KEY `user_id` (`user_id`)
		 ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;');
		 $result = $createTB->execute();
		}
    }
	
	//Remove table on deactivation
	if ( PluginDisabled( $ThisPlugin ) ) {
	   $DropTable = $db->prepare("DROP TABLE IF EXISTS `online_users`;");
	   $result = $DropTable->execute();
	}


if ($PluginEnabled == 1  ) {
   //unset($_SESSION["last_log"]);
   if ( OS_is_admin() AND OS_PluginEdit( $ThisPlugin ) ) {
     $Option = '<b>Online:</b>';
	 $sth = $db->prepare("SELECT o.user_id, o.timedate, o.user_ip, o.user_agent, u.user_name
	 FROM `online_users` as o 
	 LEFT JOIN ".OSDB_USERS." as u ON u.user_id = o.user_id
	 ORDER BY o.timedate DESC
	 LIMIT 100");
	 
	 $result = $sth->execute();

	 if ( file_exists("../inc/geoip/geoip.inc") ) {
	 include("../inc/geoip/geoip.inc");
	 $GeoIPDatabase = geoip_open("../inc/geoip/GeoIP.dat", GEOIP_STANDARD);
	 $GeoIP = 1;
	 }
	 
	 $Option.='<table>';
	 $uc=0;
	 while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	 
	$Letter   = geoip_country_code_by_addr($GeoIPDatabase, $row["user_ip"]);
	$Country  = geoip_country_name_by_addr($GeoIPDatabase, $row["user_ip"]);
	
	if ($GeoIP == 1 AND empty($Letter ) ) {
	$Letter  = "blank";
	$Country = "Reserved";
	}
	 
	 $img = '<img title="'.$Country.'" src="'.OS_HOME.'img/flags/'.$Letter.'.gif" width="21" height="15" class="imgvalign" />';
	 if ( empty($row["user_name"]) ) $row["user_name"] = "<i>guest</i>"; 
	 $Option.='<tr>
	 <td width="285" class="padLeft">'.$img.' <a href="javascript:;" onclick="showhide(\'user-'.$uc.'\')">'.$row["user_name"].'</a> ('.$row["user_ip"].')
	   <div id="user-'.$uc.'" style="display:none; font-size:12px">
	   '.$row["user_agent"].'
	   </div>
	 </td>
	 <td>'.date(OS_DATE_FORMAT, $row["timedate"]).'</td>
	 </tr>';
	 $uc++;
	 }
	 $Option.='</table>';
	 
	 $Option.='<div><b>Total:</b> '.$sth->rowCount().' &nbsp; <a href="'.$website.'adm/?plugins" class="menuButtons">&laquo; Back</a> </div>';
	 $Option.='';
	 
	 if ( isset($GeoIP) AND $GeoIP == 1) geoip_close($GeoIPDatabase);
   }
   
   AddEvent("os_start",  "OS_TrackUsers");
   //Shows only on Home Page
   if (!$_GET) {
   AddEvent("os_after_content",  "OS_ShowOnline");
	}

   //Remove data from database on logout
   AddEvent("os_init",  "OS_LogOutDeleteOnline");
   
   function OS_TrackUsers() {
   
   global $db;
   
   if ( is_logged() ) $uid = $_SESSION["user_id"]; else $uid =0;

   $CurrentTime = time();
  
   
   //purge old sessions
   $del = $db->prepare("DELETE FROM `online_users` WHERE timedate<'".($CurrentTime-OS_OnlineTime)."' ");
   $result = $del->execute();
   //LOGGED USERS
   if ( is_logged() ) {
   $sth = $db->prepare("SELECT COUNT(*) FROM `online_users` WHERE user_id>= 1 AND user_id =:uid");
   $sth->bindValue(':uid', (int)$uid, PDO::PARAM_INT); 
   $result = $sth->execute();
   $r = $sth->fetch(PDO::FETCH_NUM);
   $Total= $r[0];
   
     if ( $Total<=0 ) {
	$db->insert( 'online_users', array(
	"user_id" => (int)$uid,
	"timedate" => (int) $CurrentTime,
	"user_ip" => $_SERVER["REMOTE_ADDR"],
	"user_agent" => $_SERVER["HTTP_USER_AGENT"]
                                 ));
     } else {
	 
	$result = $db->update('online_users', array(
		   "timedate" => (int)$CurrentTime
	                                    ), "user_id = ".(int)$uid."");
      }
   
   } else {
   //GUESTS
   
   if ( !isset($_SESSION["last_log"] ) ) {
   
     $_SESSION["last_log"] = $CurrentTime;
     $NewGuest = $db->query("INSERT INTO `online_users`(user_id, timedate, user_ip, user_agent) VALUES('0','".(int) time() ."','".$_SERVER["REMOTE_ADDR"]."','".$_SERVER["HTTP_USER_AGENT"]."')");
    } else {
	
	    if ( $CurrentTime >  $_SESSION["last_log"] + OS_GuestSessionTime ) {
	     //echo $CurrentTime - $_SESSION["last_log"]. " - " . $GuestSessionTime;
	     unset( $_SESSION["last_log"] );
	    }
	   }
   
   } //guests
      
   } //end OS_TrackUsers function
   
   function OS_ShowOnline() {
     $CurrentTime = time();
	 
     global $db;
	 $sth = $db->prepare("SELECT COUNT(*) FROM `online_users` WHERE user_id>= 1 
	 AND timedate>'".($CurrentTime-OS_OnlineTime)."' ");
	 $result = $sth->execute();
	 $r = $sth->fetch(PDO::FETCH_NUM);
	 $TotalOnline = $r[0];
	 
	 $sth = $db->prepare("SELECT COUNT(*) FROM `online_users` WHERE user_id<= 0 
	 AND timedate>'".($CurrentTime-OS_OnlineTime)."' ");
	 $result = $sth->execute();
	 $r = $sth->fetch(PDO::FETCH_NUM);
	 $TotalGuests = $r[0];
	 
	 $TotalUsers = $TotalGuests  +  $TotalOnline;
	 ?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed padLeft padTop padBottom">
	 
	 <div>
	 In total there are <b><?=$TotalUsers?></b> users online :: <?=$TotalOnline?> registered and <?=$TotalGuests?> guests (based on users active over the past 10 minutes)
	 </div>
	 
	 <?php 
	 if ( $TotalOnline >= 1 ) {
	 $sth = $db->prepare("SELECT o.user_id, o.timedate, o.user_ip, o.user_agent, u.user_name, u.user_level
	 FROM `online_users` as o 
	 LEFT JOIN ".OSDB_USERS." as u on u.user_id = o.user_id
	 WHERE o.user_id>= 1 AND o.timedate>'".($CurrentTime-OS_OnlineTime)."'
	 LIMIT 50");
	 
	 $result = $sth->execute();
	 
	$OnlineUsers = array();
	$c=0;
	$BuildOnlineUsers = "";
	 while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	 $OnlineUsers[$c]["user_id"]  = ($row["user_id"]);
	 $OnlineUsers[$c]["user_ip"]  = ($row["user_ip"]);
	 $OnlineUsers[$c]["timedate"]  = ($row["timedate"]);
	 $OnlineUsers[$c]["user_agent"]  = ($row["user_agent"]);
	 $OnlineUsers[$c]["user_name"]  = ($row["user_name"]);
	 $c++;
	 $style = "";
	 if ( $row["user_level"] >=9) $style = ' style="color: #009900"';
	 if ( $row["user_level"] ==10) $style = ' style="color: #B40303"';
	 $BuildOnlineUsers.="<span".$style.">".$row["user_name"]."</span>, ";
	 }
	 
	 $BuildOnlineUsers = substr($BuildOnlineUsers, 0, -2);
	 ?>
	 <div>Registered users:</div>
	 <div>
	 <?=$BuildOnlineUsers?> 
	 <?php
	  }
	 ?>
	  </div>
	  
	 </div>
    </div>
   </div>
  </div>
</div>
	 <?php
   }
	
    function OS_LogOutDeleteOnline() {
     global $db;
	 if ( isset($_GET["logout"]) AND is_logged() ) {
	    $sth = $db->prepare("DELETE FROM `online_users` WHERE user_id=:user_id");
		$sth->bindValue(':user_id', (int)$_SESSION["user_id"], PDO::PARAM_INT); 
		$result = $sth->execute();
	 }
	 
   }
   
}

?>