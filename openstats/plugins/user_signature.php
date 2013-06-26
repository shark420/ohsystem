<?php
//Plugin: User Signatures
//Author: Ivan
//Plugin that allows users to create their signatures

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
$PluginEnabled = '0';
//Enable edit plugin options
$PluginOptions = '1';
$SignaturePath = 'img/signatures/';
$SigCacheTime = '60';
$SigMenuLink = '1';
$SigUse = '1';

$ThisPlugin = basename(__FILE__, '');

define('SIG_PATH', $SignaturePath);
define('SIG_CACHE',$SigCacheTime);
define('SIG_MENU', $SigMenuLink);
define('SIG_USE',  $SigUse);

if ($PluginEnabled == 1) {

//If user can edit plugin
if ( OS_is_admin() AND OS_PluginEdit( $ThisPlugin ) ) {

if ( isset($_POST["SigCacheTime"]) ) {
   write_value_of('$SigCacheTime', "$SigCacheTime", (int)$_POST["SigCacheTime"] , $plugins_dir.basename(__FILE__, ''));
   write_value_of('$SigMenuLink', "$SigMenuLink", (int)$_POST["sig_menu"] , $plugins_dir.basename(__FILE__, ''));
   write_value_of('$SigUse', "$SigUse", (int)$_POST["sig_use"] , $plugins_dir.basename(__FILE__, ''));
   $SigCacheTime = (int)$_POST["SigCacheTime"];
   $SigMenuLink = (int)$_POST["sig_menu"];
   $SigUse = (int)$_POST["sig_use"];
} else { $SigCacheTime = SIG_CACHE;  $SigMenuLink = SIG_MENU; $SigUse = SIG_USE; }

if ( $SigMenuLink == 1) $sel[0] = 'selected="selected"'; else $sel[0] = "";
if ( $SigMenuLink == 0) $sel[1] = 'selected="selected"'; else $sel[1] = "";

if ( $SigUse == 1) $sel2[0] = 'selected="selected"'; else $sel2[0] = "";
if ( $SigUse == 0) $sel2[1] = 'selected="selected"'; else $sel2[1] = "";
//Show following options when user click on edit icon for this plugin
//Display all smilies

if ( file_exists("../".SIG_PATH."_signature.jpg")  ) $Background = '<div>Signature background: </div><img src="../'.SIG_PATH.'_signature.jpg" />'; else $Background = '<div>Missing signature background image ( _signature.jpg ) </div>';

$Option = '
<form action="" method="post" >
  <div><b>Signature path:</b> '. SIG_PATH.'</div>
  Cache time: <input size="2" type="text" value="'. $SigCacheTime.'" name="SigCacheTime" /> min. <br />
  
  <div class="padTop padBottom">
    <select name="sig_menu">
	<option '.$sel[0].' value="1">Yes</option>
	<option '.$sel[1].'  value="0">No</option></select> Add link to Misc menu
  </div>
  
  <div>
    <select name="sig_use">
	<option '.$sel2[0].' value="1">Only registered users</option>
	<option '.$sel2[1].'  value="0">All</option></select>  can create signatures
  </div>
  
  '.$Background .'
  
  <br /> <br />
  <div><input type="submit" value = "Save" class="menuButtons" /> 
  <a href="'.$website.'adm/?plugins" class="menuButtons">Cancel</a> </div>
</form>';

    if ( !file_exists( "../".$SignaturePath ) ) {
	  mkdir( "../".$SignaturePath ."");
	  chmod( "../".$SignaturePath ."",0777);
	  file_put_contents("../".$SignaturePath ."index.html", "");
	}

}
	
	if (SIG_USE==1 AND !is_logged() AND OS_GetAction("signature") ) { AddEvent("os_content",  "OS_ShowSignatureForRegistered");  } 
	
	if ( isset($_POST["sig_name"]) AND OS_GetAction("signature") ) {
	   $player = safeEscape( $_POST["sig_name"] );
	   
	   $sth =  $db->prepare("SELECT * FROM ".OSDB_STATS." WHERE LOWER(player) = :player LIMIT 1");
	   $sth->bindValue(':player', strtolower($player), PDO::PARAM_STR); 
	   $result = $sth->execute();
	   if ( $sth->rowCount()>=1 ) {
	     $row = $sth->fetch(PDO::FETCH_ASSOC);
	     header('location: '.OS_HOME.'?action=signature&sig='.$row["id"].''); die;
	   } else {
	   header('location: '.OS_HOME.'?action=signature&not_found'); die;
	   }
	}
	
	//Add link to MISC menu
	if (SIG_MENU == 1) {
	if (SIG_USE==1 AND !is_logged() ) {} else
	AddEvent("os_add_menu_misc",  "OS_ShowSigMenu"); 
	
	}
	
	//Hook function
	if ( OS_GetAction("signature") ) AddEvent("os_start",  "OS_Signatures"); 
	
	function OS_ShowSigMenu() {
	?>
	<li><a href="<?=OS_HOME?>?action=signature">Signatures</a></li>
	<?php
	}
	
	function OS_ShowSignatureForRegistered() {
?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed padLeft padTop padBottom entry clearfix">
	 
	 <h2>You must be <a href="<?=OS_HOME?>?login">logged in</a> to create signatures</h2>
	 
	 <div style="margin-top:180px;">&nbsp; </div>
     </div>
    </div>
   </div>
  </div>
</div> 
<?php
	}
	
	function OS_ShowSignature() {
?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed padLeft padTop padBottom entry clearfix">
 
	 <?php
	 if ( isset($_GET["sig"]) AND !empty($_GET["sig"]) AND is_numeric( $_GET["sig"] ) ) {
	 $image = SIG_PATH."user_".(int)$_GET["sig"].".jpg";
	 ?>
	 <h2>Signature</h2>
	 
	 <table>
	 <tr>
	  <td></td>
	<td>
	 <div>
	 <a href="<?=OS_HOME?>?action=signature&user=<?=(int)$_GET["sig"]?>" target="_blank">
	 <img src="<?=OS_HOME?>?action=signature&user=<?=(int)$_GET["sig"]?>" alt="" />
	 </a>
	 </div>
	 </td>
	 </tr>
	    <tr>
		  <td class="padLeft" width="80">Direct url:</td>
		  <td><input style="padding-left:5px" type="text" size="70" value="<?=OS_HOME?>?action=signature&user=<?=(int)$_GET["sig"]?>" />
		  <a href="<?=OS_HOME?>?action=signature&user=<?=(int)$_GET["sig"]?>" target="_blank">Link</a>
		  </td>
		</tr>
		
	    <tr>
		  <td class="padLeft" width="80">BBCode:</td>
		  <td><textarea style="margin: 0px; width: 453px; height: 62px; padding-left:5px;">[url=<?=OS_HOME?>?u=<?=(int)$_GET["sig"]?>][img]<?=OS_HOME?>?action=signature&user=<?=(int)$_GET["sig"]?>[/img][/url]</textarea>
		  </td>
		</tr>
		
	    <tr>
		  <td class="padLeft" width="80">HTML:</td>
		  <td><textarea style="margin: 0px; width: 453px; height: 62px; padding-left:5px;"><a href="<?=OS_HOME?>?u=<?=(int)$_GET["sig"]?>"><img src="<?=OS_HOME?>?action=signature&user=<?=(int)$_GET["sig"]?>" /></a></textarea>
		  </td>
		</tr>
	 </table>
	 <?php
	 }
	 
	 if ( empty($_GET["sig"]) OR !isset($_GET["sig"]) ) {
	    ?>
	<h2>Create a signature</h2>
	
	<?php if (isset($_GET["not_found"])) { ?>
	<h3>User not found</h3>
	<?php } ?>
	
	<form action="" method="post">
	<input type="hidden" value="signature" name="action" />
	<table>
	   <tr class="row">
	     <td class="padLeft" width="170">Enter player name:</td>
		 <td><input type="text" class="field" name="sig_name" /></td>
	   </tr>
	   <tr class="row">
	     <td width="170"></td>
		 <td><input type="submit" class="menuButtons" name="create_sig" /></td>
	   </tr>
	</table>
	</form>
		<?php
	 }
	 ?>
	 
	 <div style="margin-top:180px;">&nbsp; </div>
     </div>
    </div>
   </div>
  </div>
</div> 
<?php
	}
	
	function OS_Signatures() {
	
	if ( isset($_GET["sig"]) OR !isset($_GET["user"]) ) {
	
	
	if (SIG_USE==1 AND !is_logged() ) { } else
	AddEvent("os_content",  "OS_ShowSignature"); 
	}
	
	if ( isset($_GET["user"] ) AND is_numeric($_GET["user"]) ) {
	
	   global $db;
	   global $lang;
	   $userID = safeEscape((int)$_GET["user"]);
	   
	   $SigPath = SIG_PATH."user_".$userID.".jpg";
	   
	if (file_exists( $SigPath ) AND time() - SIG_CACHE*60 < filemtime( $SigPath ))
	 {  $NewImage =imagecreatefromjpeg( $SigPath );//image create by existing image
         header("Content-type: image/jpeg");// out out the image 
         //imagejpeg($NewImage);//Output image to browser 
		 //file_put_contents('img/signatures/html.html', 'aaa');
         echo file_get_contents( $SigPath ); exit;
	 }
	   
	   $sth = $db->prepare("SELECT * FROM ".OSDB_STATS." WHERE id = :uid ");
	   $sth->bindValue(':uid', $userID, PDO::PARAM_INT); 
	   $result = $sth->execute();
	   if ( $sth->rowCount()>=1) {
	    
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$id        = (int)($row["id"]);
		$player  = ($row["player"]);

		$score  = number_format($row["score"],0);
		$games  = number_format($row["games"],0);
		$wins  = number_format($row["wins"],0);
		$losses  = number_format($row["losses"],0);
		$draw  = number_format($row["draw"],0);
		$kills = number_format($row["kills"],0);
		$deaths  = number_format($row["deaths"],0);
		$assists  = number_format($row["assists"],0);
		$creeps = number_format($row["creeps"],0);
		$denies = number_format($row["denies"],0);
		$neutrals  = number_format($row["neutrals"],0);
		$towers  = ($row["towers"]);
		$rax  = ($row["rax"]);
		$banned  = ($row["banned"]);
		$ip  = ($row["ip"]);
		
		include("inc/geoip/geoip.inc");
	    $GeoIPDatabase = geoip_open("inc/geoip/GeoIP.dat", GEOIP_STANDARD);
		$letter   = geoip_country_code_by_addr( $GeoIPDatabase, $ip );
	    $country  = geoip_country_name_by_addr( $GeoIPDatabase, $ip );
	
	    if (empty($letter) ) {
	    $letter  = "blank";
	    $country = "Reserved";
	    }
		
	    if($row["wins"] == 0 and $row["wins"]+$row["losses"] == 0)
	    $winloose = 0;
	    else
	    $winloose = round( $row["wins"]/( $row["wins"] + $row["losses"] ), 3)*100;
		
		if ( $row["kills"]>=1 )
		$killsPerGame = ROUND( $row["kills"] / $row["games"],1); else $killsPerGame = 0;
		
		$sth = $db->prepare( "SELECT 
		SUM(`left`) 
		FROM ".OSDB_GP." 
		WHERE LOWER(name)= :player LIMIT 1" );
		
		$sth->bindValue(':player', strtolower($player), PDO::PARAM_STR); 
	    $result = $sth->execute();
	    $res = $sth->fetch(PDO::FETCH_ASSOC);
		$TotalDuration=secondsToTime($res["SUM(`left`)"]);
		
		$TotalHours=ROUND($res["SUM(`left`)"]/ 3600,1);
		$TotalMinutes=ROUND($res["SUM(`left`)"]/ 3600*60,1);
		$TimePlayed = secondsToTime( $res["SUM(`left`)"] , $lang["h"], $lang["m"], $lang["s"]);
		
		$dbh = $db->prepare( OS_MostPlayedHero( strtolower($player) ) );
		$result = $dbh->execute();
		$r = $dbh->fetch(PDO::FETCH_ASSOC);
		$MostPlayedHero=strtoupper($r["original"]);
		$MostPlayedHeroName=$r["description"];
		$MostPlayedCount=$r["played"];
		$MostPlayedTime=secondsToTime($r["timeplayed"]);

		$NewImage =imagecreatefromjpeg( SIG_PATH."_signature.jpg");

		$TextColor = imagecolorallocate($NewImage, 255, 246, 0);    //text color - RGB
		$TextColor2 = imagecolorallocate($NewImage, 255, 255, 255); //text color - RGB
		$TextColor3 = imagecolorallocate($NewImage, 253, 193, 193); //text color - RGB
		
imagestring($NewImage, 5, 10, 1, $player, $TextColor);

//score
imagestring($NewImage, 2, 10, 26, "Score:", $TextColor3);
imagestring($NewImage, 2, 68, 26, $score, $TextColor2);

//games
imagestring($NewImage, 2, 10, 42, "Games:", $TextColor3);
imagestring($NewImage, 2, 68, 42, $games, $TextColor2);

//wins
imagestring($NewImage, 2, 10, 58, "Wins:", $TextColor3);
imagestring($NewImage, 2, 68, 58, $winloose."%", $TextColor2);


//Kills Per Game
imagestring($NewImage, 2, 10, 82, $killsPerGame." Kills per game", $TextColor2);
//imagestring($NewImage, 2, 102, 82,$kpg, $TextColor2);


//duration 
imagestring($NewImage, 2, 10, 98,"Time:", $TextColor3);
imagestring($NewImage, 2, 52, 98, $TimePlayed, $TextColor2);

//Creep Kills
imagestring($NewImage, 2, 140, 82, "Creeps:", $TextColor3);
imagestring($NewImage, 2, 200, 82, $creeps, $TextColor2);

//Creep Denies
imagestring($NewImage, 2, 140, 98, "Denies:", $TextColor3);
imagestring($NewImage, 2, 200, 98, $denies, $TextColor2);


//kills
imagestring($NewImage, 2, 140, 26, "Kills:", $TextColor3);
imagestring($NewImage, 2, 200, 26, $kills, $TextColor2);

//deaths
imagestring($NewImage, 2, 140, 42, "Deaths:", $TextColor3);
imagestring($NewImage, 2, 200, 42, $deaths, $TextColor2);

//assists
imagestring($NewImage, 2, 140, 58, "Assists:", $TextColor3);
imagestring($NewImage, 2, 200, 58, $assists, $TextColor2);
    
	//COUNTRY FLAGS
    $NewImage2 = imagecreatefromgif("img/flags/".$letter.".gif");
	imagecopy($NewImage, $NewImage2, 220, 3, 0, 0, imagesx($NewImage2), imagesy($NewImage2));
	imagestring($NewImage, 2, 250, 3, $country, $TextColor2);
    imagedestroy($NewImage2);
	
	//FAVORITE HERO
    $NewImage2 = imagecreatefromgif("img/heroes/".$MostPlayedHero.".gif");
	imagecopyresized($NewImage,$NewImage2,306,54,0,0,32,32,64,64);
	imagedestroy ($NewImage2); 
		
	imagestring($NewImage, 2, 280, 25, "Favorite Hero", $TextColor3);
	imagestring($NewImage, 1, 280, 39, $MostPlayedHeroName, $TextColor2);
	
header("Content-type: cache");// out out the image 
imagejpeg($NewImage, $SigPath, 100);//Output image to browser 
imagedestroy($NewImage);
	
header("Content-type: image/jpeg");// out the image 
$NewImage =imagecreatefromjpeg($SigPath);//image create by existing image
imagejpeg($NewImage);//Output image to browser 
imagedestroy($NewImage); 
exit;

	header("location: ".OS_HOME."?action=signature&sig=".$userID.""); die;
		
	   } else {
	   header("location: ".OS_HOME."?action=signature&unknown"); die;
	   }
	   
	}
	
	}


}
?>