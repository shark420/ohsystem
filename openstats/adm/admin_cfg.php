<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$error = ""; $smferror = ""; $wperror = "";
$disp = '<img src="'.$website.'adm/del.png" class="imgvalign" width="16" height="16" alt="" />'; 
$enap = '<img src="'.$website.'adm/check.png" class="imgvalign" width="16" height="16" alt="" />'; 

$HomeTitle = trim(get_value_of('$HomeTitle'));

if ( isset( $_POST["update_config"]) ) {
   
   write_value_of('$HomeTitle', "$HomeTitle", trim($_POST["c_sitename"]) , "../config.php");
   write_value_of('$HomeDesc', "$HomeDesc", trim($_POST["c_descr"]) , "../config.php");
   write_value_of('$HomeKeywords', "$HomeKeywords", trim($_POST["c_key"]) , "../config.php");
   write_value_of('$default_language', "$default_language", trim($_POST["c_lang"]) , "../config.php");
   write_value_of('$DateFormat', "$DateFormat", trim($_POST["c_date"]) , "../config.php");
   write_value_of('$DefaultStyle', "$DefaultStyle", trim($_POST["c_style"]) , "../config.php");
   write_value_of('$GamesPerPage', "$GamesPerPage", trim($_POST["c_gpp"]) , "../config.php");
   write_value_of('$TopPlayersPerPage', "$TopPlayersPerPage", trim($_POST["c_tpp"]) , "../config.php");
   write_value_of('$HeroesPerPage', "$HeroesPerPage", trim($_POST["c_hpp"]) , "../config.php");
   write_value_of('$ItemsPerPage', "$ItemsPerPage", trim($_POST["c_ipp"]) , "../config.php");
   write_value_of('$CommentsPerPage', "$CommentsPerPage", trim($_POST["c_cpp"]) , "../config.php");
   write_value_of('$UserActivation', "$UserActivation", trim($_POST["c_activation"]) , "../config.php");
   write_value_of('$PlayDotaHeroes', "$PlayDotaHeroes", trim($_POST["c_heroes_data"]) , "../config.php");
   if ($_POST["c_pages"]<=0) $_POST["c_pages"] = 3;
   if ($_POST["c_pages"]>=8) $_POST["c_pages"] = 3;
   write_value_of('$MaxPaginationLinks', "$MaxPaginationLinks", trim($_POST["c_pages"]) , "../config.php");
   write_value_of('$TopPage', "$TopPage", trim($_POST["c_top"]) , "../config.php");
   write_value_of('$HeroesPage', "$HeroesPage", trim($_POST["c_hero"]) , "../config.php");
   write_value_of('$ItemsPage', "$ItemsPage", trim($_POST["c_item"]) , "../config.php");
   write_value_of('$BansPage', "$BansPage", trim($_POST["c_ban"]) , "../config.php");
   write_value_of('$WarnPage', "$WarnPage", trim($_POST["c_warn"]) , "../config.php");
   write_value_of('$AdminsPage', "$AdminsPage", trim($_POST["c_admin"]) , "../config.php");
   write_value_of('$SafelistPage', "$SafelistPage", trim($_POST["c_safe"]) , "../config.php");
   write_value_of('$GuidesPage', "$GuidesPage", trim($_POST["GuidesPage"]) , "../config.php");
   write_value_of('$MemberListPage', "$MemberListPage", trim($_POST["MemberListPage"]) , "../config.php");
   write_value_of('$_debug', "$_debug", trim($_POST["c_debug"]) , "../config.php");
   
   if ( $_POST["c_recent_n"]>=30) $_POST["c_recent_n"] = 30;
   write_value_of('$RecentGames', "$RecentGames", trim($_POST["c_recent"]) , "../config.php");
   write_value_of('$TotalRecentGames', "$TotalRecentGames", trim($_POST["c_recent_n"]) , "../config.php");
   
   write_value_of('$LogoText', "$LogoText", trim($_POST["c_logo"]) , "../config.php");
   
   write_value_of('$updateGames', "$updateGames", trim($_POST["c_upd"]) , "../config.php");
   write_value_of('$updateGamesCron', "$updateGamesCron", trim($_POST["c_cron"]) , "../config.php");
   
   write_value_of('$FBLogin', "$FBLogin", trim($_POST["fb"]) , "../config.php");
   write_value_of('$FacebookAppID', "$FacebookAppID", trim($_POST["c_fbappid"]) , "../config.php");
   write_value_of('$FacebookAppSecret', "$FacebookAppSecret", trim($_POST["c_fbappsec"]) , "../config.php");
   
   write_value_of('$BanAppeals', "$BanAppeals", trim($_POST["BanAppeals"]) , "../config.php");
   write_value_of('$BanReports', "$BanReports", trim($_POST["BanReports"]) , "../config.php");
   $ReportTime = (int) ($_POST["BanReportTime"]);
   if ($ReportTime <= 0) $ReportTime = '180';
   write_value_of('$BanReportTime', "$BanReportTime", trim($ReportTime) , "../config.php");
   
   write_value_of('$ReportUserLink', "$ReportUserLink", trim( (int) $_POST["ReportUserLink"]) , "../config.php");
   
   write_value_of('$AboutUs', "$AboutUs", trim($_POST["AboutUs"]) , "../config.php");
   write_value_of('$UserRegistration', "$UserRegistration", trim((int)$_POST["UserRegistration"]) , "../config.php");
   write_value_of('$AllowComments', "$AllowComments", trim((int)$_POST["AllowComments"]) , "../config.php");
   
   write_value_of('$PHPbb3Integration', "$PHPbb3Integration", trim((int)$_POST["PHPbb3Integration"]) , "../config.php");
   write_value_of('$SMFIntegration', "$SMFIntegration", trim((int)$_POST["SMFIntegration"]) , "../config.php");
   write_value_of('$WPIntegration', "$WPIntegration", trim((int)$_POST["WPIntegration"]) , "../config.php");
   
   write_value_of('$phpbb_forum', "$phpbb_forum", trim($_POST["phpbb_forum"]) , "../config.php");
   write_value_of('$phpbb_forum_url', "$phpbb_forum_url", trim($_POST["phpbb_forum_url"]) , "../config.php");
   
   write_value_of('$smf_forum', "$smf_forum", trim($_POST["smf_forum"]) , "../config.php");
   write_value_of('$smf_forum_url', "$smf_forum_url", trim($_POST["smf_forum_url"]) , "../config.php");
   
   write_value_of('$wp_path', "$wp_path", trim($_POST["wp_path"]) , "../config.php");
   write_value_of('$wp_url', "$wp_url", trim($_POST["wp_url"]) , "../config.php");
   
   $rep = trim($_POST["ReplayLocation"]);
   if ( substr($rep,0,-1) == "/" ) $rep  = substr($rep,0,-1);
   write_value_of('$ReplayLocation', "$ReplayLocation", trim($rep) , "../config.php");
   
   write_value_of('$AutoLinkComments', "$AutoLinkComments", trim($_POST["AutoLinkComments"]) , "../config.php");
   write_value_of('$AutoLinkFull', "$AutoLinkFull", trim($_POST["AutoLinkFull"]) , "../config.php");
   
   write_value_of('$ShowUserHeroStats', "$ShowUserHeroStats", trim($_POST["ShowUserHeroStats"]) , "../config.php");
   write_value_of('$NewsPerPage', "$NewsPerPage", trim($_POST["NewsPerPage"]) , "../config.php");
   
   write_value_of('$HideEmptySlots', "$HideEmptySlots", trim($_POST["HideEmptySlots"]) , "../config.php");
   write_value_of('$OSAppID', "$OSAppID", trim(strip_tags($_POST["OSAppID"])) , "../config.php");
   //write_value_of('$HeroVote', "$HeroVote", trim($_POST["HeroVote"]) , "../config.php");
   //$HvoteShow = trim($_POST["HeroVoteShow"]);
   
   //if ($HvoteShow<=0 OR $HvoteShow>=200) $HvoteShow = 20;
   //write_value_of('$HeroVoteShow', "$HeroVoteShow", $HvoteShow , "../config.php");
   

   $WordLimit = trim($_POST["limit_words"]);
   
   if ($WordLimit>=300) $WordLimit = 40;
   write_value_of('$NewsWordLimit', "$NewsWordLimit", $WordLimit , "../config.php");
   
   $SortComments_ = trim($_POST["sort_comments"]);
   
   if ($SortComments_>3 OR $SortComments_<=0) $SortComments_ = 1;
   write_value_of('$SortComments', "$SortComments", $SortComments_ , "../config.php");
   
   write_value_of('$AllowUploadAvatar', "$AllowUploadAvatar", (int)$_POST["AllowUploadAvatar"] , "../config.php");
   
   $ISize = (int)$_POST["MaxImageSize"];
   if ($ISize>1600 OR $ISize<=16) $ISize = 320; //default
   
   write_value_of('$MaxImageSize', "$MaxImageSize", (int)$ISize , "../config.php");
   
   $IQuality = (int)$_POST["ImageQuality"];
   if ($IQuality>100 OR $IQuality<=1) $IQuality = 85; //default
   
   write_value_of('$ImageQuality', "$ImageQuality", (int)$IQuality , "../config.php");
   
   write_value_of('$ShowMembersCountry', "$ShowMembersCountry", (int)$_POST["ShowMembersCountry"] , "../config.php");
   
   write_value_of('$TimeZone', "$TimeZone", trim($_POST["TimeZone"]) , "../config.php");
   
   write_value_of('$ComparePlayers', "$ComparePlayers", trim($_POST["ComparePlayers"]) , "../config.php");
   
   if ($_POST["MaxPlayersToCompare"]>=50) $_POST["MaxPlayersToCompare"] = 50;
   if ($_POST["MaxPlayersToCompare"]<=1)  $_POST["MaxPlayersToCompare"] = 2;
   write_value_of('$MaxPlayersToCompare', "$MaxPlayersToCompare", trim( (int) $_POST["MaxPlayersToCompare"]) , "../config.php");
   
   //$LeftTimePenalty
   write_value_of('$LeftTimePenalty', "$LeftTimePenalty", trim( (int) $_POST["LeftTimePenalty"]) , "../config.php");
?>
<div align="center">
  <h2>Configuration successfully updated.   <a href="<?=$website?>adm/?cfg">&laquo; Back</a></h2>
  
<div style="height: 800px;">&nbsp;</div>
</div>
<?php
}
else { 

   if ($PHPbb3Integration == 1) {
     if ( !file_exists("../".$phpbb_forum."common.php") ) 
	 $error.="<div><span class='sentinel'><b>Error: file not exists:</b></span> ".$phpbb_forum."common.php"." </div>";
   }
   
   if ($SMFIntegration == 1) {
     if ( !file_exists("../".$smf_forum."SSI.php") ) 
	 $smferror.="<div><span class='sentinel'><b>Error: file not exists:</b></span> ".$smf_forum."SSI.php"." </div>";
   }
   
   if ($WPIntegration == 1) {
     if ( !file_exists("../".$wp_path.'wp-load.php') ) 
	 $wperror.="<div><span class='sentinel'><b>Error: file not exists:</b></span> ".$wp_path.'wp-load.php' ." </div>";
   }
   
?>

<div align="center">
<h2>Configuration</h2>
<a class="menuButtons" href="<?=$website?>adm/?cfg#basic">Basic</a>
<a class="menuButtons" href="<?=$website?>adm/?cfg#integration">Integration</a>
<a class="menuButtons" href="<?=$website?>adm/?cfg#ban">Bans</a>
<a class="menuButtons" href="<?=$website?>adm/?cfg#stats">Stats</a>
<a class="menuButtons" href="<?=$website?>adm/?cfg#pages">Pages</a>
<a class="menuButtons" href="<?=$website?>adm/?cfg#misc">Misc</a>

<?php if ( isset($error) AND !empty($error) ) { ?><h2><?=$error?></h2><?php } ?>
<?php if ( isset($smferror) AND !empty($smferror) ) { ?><h2><?=$smferror?></h2><?php } ?>
<?php if ( isset($wpferror) AND !empty($wpferror) ) { ?><h2><?=$wpferror?></h2><?php } ?>
<form action="" method="post">
    <table>
	<tr>
	  <th width="240"></th>
	  <th class="padLeft"><a name="basic" href="#basic">Basic Configuration</a></th>
	</tr>
	
	 <tr class="row">
	   <td>Site name:</td>
	   <td ><input class="field" type="text" value="<?=$HomeTitle?>" name="c_sitename" /></td>
	 </tr>
	 <tr class="row">
	   <td>Site description:</td>
	   <td ><textarea style="width: 420px; height: 50px;" name="c_descr"><?=$HomeDesc?></textarea></td>
	 </tr>
	 <tr class="row">
	   <td>Site keywords:</td>
	   <td ><textarea style="width: 420px; height: 50px;" name="c_key"><?=$HomeKeywords?></textarea></td>
	 </tr>
	 
	 <tr class="row">
	   <td>Your Logo:</td>
	   <td ><textarea style="width: 420px; height: 50px;" name="c_logo"><?=$LogoText?></textarea></td>
	 </tr>
	 
	 <tr class="row">
	   <td>Session name:</td>
	   <td >
	   <input class="field" type="text" value="<?=$OSAppID?>" name="OSAppID" /><br />
	   If you have more than one site (OpenStats) on the same hosting, it is desirable to define a different session names for each application.<br />
	   <b>Note:</b> after changing this option, you will be logged out from the site.<br />
	   <span style="color:red;">The session name can't consist of digits only, at least one letter must be present. Otherwise a new session id is generated every time.</span>
	   </td>
	 </tr>
	 
	 <tr class="row">
	   <td>Gamelist patch:</td>
	   <td>
	   <?php if (isset($GameListPatch) AND $GameListPatch!=1) { ?>
	   <a class="menuButtons" href="<?=$website?>adm/?gamelist">Install support for gamelist patch</a>
	   <?php } else {  ?>
	   <a class="menuButtons" href="<?=$website?>adm/?gamelist">Gamelist installed</a>
	   <?php } ?>
	   &nbsp; <a href="http://www.codelain.com/forum/index.php?topic=18076.0" target="_blank">More info</a>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td><div class="padTop"></div>Language:<div class="padTop"></div></td>
	   <td >
	   <div class="padTop"></div>
<?php 
if ($handle = opendir("../lang")) {
   ?>
   <select name="c_lang"><?php
   while (false !== ($file = readdir($handle))) 
	{
	  if ($file !="." AND  $file !="index.html" AND $file !=".." AND strstr($file,".png")==false AND strstr($file,".css")==false AND strstr($file,".js")==false AND strstr($file,".php")==true ) {
	  
	  if (trim( str_replace(".php", "", $file) ) == trim($default_language))  $sel="selected"; else $sel = "";
	  ?>
	  <option <?=$sel?> value="<?=str_replace(".php", "", $file)?>"><?=str_replace(".php", "", $file)?></option>
	  <?php
	  }
	}
   ?>
   </select>
   <div class="padTop"></div>
   <?php
}

$utc = new DateTimeZone('UTC');
$dt = new DateTime('now', $utc);

?>
   </td>
	 </tr>
	 	 <tr  class="row">
	   <td>Timezone:</td>
	   <td >
	   <select name="TimeZone">
	   <?php
	   $tzArray = array();
	   $c = 0;
	   foreach(DateTimeZone::listIdentifiers() as $tz) {
       $current_tz = new DateTimeZone($tz);
       $offset =  $current_tz->getOffset($dt);
	   $transition =  $current_tz->getTransitions( time(), time() );
       //$transition =  $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
       $abbr = $transition[0]['abbr'];
	   
	   $tzArray[$c]["offset"] = $offset;
	   $tzArray[$c]["tz"] = $tz;
	   $tzArray[$c]["abbr"] = $abbr;
	   $c++;
       ?>
	   <!--<option <?=$sel ?> value="<?=$tz?>"> [<?=$abbr?> <?=formatOffset($offset)?>] <?=$tz?></option>-->
	   <?php } ?>
	   
	   <?php
	   aasort($tzArray,"offset");

	   foreach ($tzArray as $tz) {
	   if ( $tz["tz"] == $TimeZone ) $sel = 'selected="selected" style="background-color: yellow;"'; else $sel="";
	    ?><option <?=$sel ?> value="<?=$tz["tz"]?>"> (<?=formatOffset($tz["offset"])?> <?=$tz["abbr"]?>) <?=$tz["tz"]?></option><?php
	   }
	   ?>
	   </select>
	   </td>
	 </tr>
	 <tr  class="row">
	   <td>Date Format:</td>
	   <td ><input style="width: 98px;" class="field" type="text" value="<?=$DateFormat?>" name="c_date" /> <?=date( $DateFormat, time() )?></td>
	 </tr>
	 <tr  class="row">
	   <td>Theme:</td>
	   <td >
	   <div class="padTop"></div>
<?php 
if ($handle = opendir("../themes")) {
   ?>
   <select name="c_style"><?php
   while (false !== ($file = readdir($handle))) 
	{
	  if ($file !="." AND  $file !="index.html" AND $file !=".." AND strstr($file,".png")==false AND strstr($file,".css")==false AND strstr($file,".js")==false AND strstr($file,".")==false ) {
	  
	  if (trim( str_replace(".php", "", $file) ) == trim($DefaultStyle))  $sel="selected"; else $sel = "";
	  ?>
	  <option <?=$sel?> value="<?=$file?>"><?=str_replace(".php", "", $file)?></option>
	  <?php
	  }
	}
   ?>
   </select>
   <div class="padTop"></div>
   <?php
}

?>

	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td><div class="padTop"></div>User Registration:<div class="padTop"></div></td>
	   <td >
	   <div class="padTop"></div>
	   <select name="UserRegistration">
		 <?php if ( $UserRegistration == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
	     <option <?=$sel?> value="1">Yes</option>
		 <?php if ( $UserRegistration == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
		 <option <?=$sel?> value="0">No</option>
	   </select>
	   <span class="font12">Allow user registration</span>
	    <div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td><div class="padTop"></div>Allow User Comments:<div class="padTop"></div></td>
	   <td >
	   <div class="padTop"></div>
	   <select name="AllowComments">
		 <?php if ( $AllowComments == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
	     <option <?=$sel?> value="1">Yes</option>
		 <?php if ( $AllowComments == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
		 <option <?=$sel?> value="0">No</option>
	   </select>
	   <span class="font12">Allow users to write comments</span>
	    <div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td><div class="padTop"></div>AutoLink User Comments:<div class="padTop"></div></td>
	   <td >
	   <div class="padTop"></div>
	   <select name="AutoLinkComments">
		 <?php if ( $AutoLinkComments == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
	     <option <?=$sel?> value="1">Yes</option>
		 <?php if ( $AutoLinkComments == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
		 <option <?=$sel?> value="0">No</option>
		 <option <?=$sel?> value="2">Remove All Links</option>
	   </select>
	   <span class="font12">Automatically create links in comments</span>
	    <div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td><div class="padTop"></div>Short/Full URLs:<div class="padTop"></div></td>
	   <td >
	   <div class="padTop"></div>
	   <select name="AutoLinkFull">
		 <?php if ( $AutoLinkFull == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
	     <option <?=$sel?> value="1">Full URL</option>
		 <?php if ( $AutoLinkFull == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
		 <option <?=$sel?> value="0">Short URL</option>
	   </select>
	   <span class="font12">Display short url or full url in user comments <a href="javascript:;" onclick="showhide('url_example')">?example</a></span>
	    <div class="padTop"></div>
		  <div id="url_example" style="display:none;">
		   <div class="font11"><b>Short url:</b> <a target="_blank" href="http://www.codelain.com/forum/index.php?topic=14297.1215">codelain.com/forum/...</a></div>
		   <div class="font11"><b>Full url:</b> <a target="_blank" href="http://www.codelain.com/forum/index.php?topic=14297.1215">http://www.codelain.com/forum/index.php?topic=14297.1215</a></div>
		  </div>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Replay Location:</td>
	   <td ><input style="width:100px;" class="field" type="text" value="<?=$ReplayLocation?>" name="ReplayLocation" /> path without last /</td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Hide empty slots:</td>
	   <td > 
	   <select name="HideEmptySlots">
	   <?php if ($HideEmptySlots == 1) $sel='selected="selected"'; else $sel =""; ?>
	    <option <?=$sel?> value="1">Hide empty slot (default)</option>
		<?php if ($HideEmptySlots == 0) $sel='selected="selected"'; else $sel =""; ?>
		<option <?=$sel?> value="0">Show All</option>
	   </select>
	   <div>Show or hide empty slots on single game page (empty username or where left time =0 )</div>
	   <div class="padTop"></div>
	   </td>
	 </tr>
	 
	<tr>
	  <th></th>
	  <th  class="padLeft"><a name="integration" href="#integration">Integration</a></th>
	</tr>
	 
	 <tr  class="row">
	   <td>Facebook Login:</td>
	   <td >
	   <select name="fb">
		 <?php if ( $FBLogin == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
	     <option <?=$sel?> value="1">Enabled</option>
		 <?php if ( $FBLogin == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
		 <option <?=$sel?> value="0">Disabled</option>
	   </select>
	   <?php if ( $FBLogin == 0 ) echo $disp; ?>
	   <span class="font12"><a href="https://developers.facebook.com/apps" target="_blank">Create FB Application</a></span>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>FB Application ID:</td>
	   <td ><input style="width:420px;" class="field" type="text" value="<?=$FacebookAppID?>" name="c_fbappid" /></td>
	 </tr>
	 
	 <tr  class="row">
	   <td>FB Application Secret:</td>
	   <td ><input style="width:420px;" class="field" type="text" value="<?=$FacebookAppSecret?>" name="c_fbappsec" /></td>
	 </tr>
	 
	<tr>
	  <th></th>
	  <th  class="padLeft">Integration</th>
	</tr>
<?php if ($PHPbb3Integration == 1 ) { $class='class="scourge"'; } else $class=""; ?>
<?php if ($PHPbb3Integration == 1 AND !empty($error)) { $class='class="sentinel"'; }  ?>

	 <tr  class="row">
	   <td <?=$class?>><b>phpbb3</b> Forum integration: <?php if (!empty($error)) echo "<b>Error</b>"; else 
	   if ($PHPbb3Integration == 1 AND empty($error) ) echo '<img src="'.$website.'adm/check.png" width="16" height="16" alt="" />';
	   ?></td>
	   <td  <?=$class?>>
	   <select name="PHPbb3Integration">
		 <?php if ( $PHPbb3Integration == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
	     <option <?=$sel?> value="1">Enabled</option>
		 <?php if ( $PHPbb3Integration == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
		 <option <?=$sel?> value="0">Disabled</option>
	   </select>
		 <span class="font12"><a href="https://www.phpbb.com/" target="_blank">phpbb3 website</a></span>
	   </td>
	 </tr>
	 
	 <tr class="row">
	   <td <?=$class?>>phpbb3 Path:</td>
	   <td <?=$class?> ><input style="width:420px;" class="field" type="text" value="<?=$phpbb_forum?>" name="phpbb_forum" /></td>
	 </tr>
	 
	 <tr class="row">
	   <td <?=$class?>>phpbb3 URL:</td>
	   <td <?=$class?> ><input style="width:420px;" class="field" type="text" value="<?=$phpbb_forum_url?>" name="phpbb_forum_url" /></td>
	 </tr>
	 
<?php if ($SMFIntegration == 1 ) { $class='class="scourge"'; } else $class=""; ?>
<?php if ($SMFIntegration == 1 AND !empty($smferror)) { $class='class="sentinel"'; } ?>
	 
	 <tr  class="row">
	   <td <?=$class?>><b>SMF</b> Forum integration: <?php if (!empty($smferror)) echo "<b>Error</b>"; else 
	   if ($SMFIntegration == 1 AND empty($smferror) ) echo '<img src="'.$website.'adm/check.png" width="16" height="16" alt="" />';
	   ?></td>
	   <td <?=$class?> >
	   <select name="SMFIntegration">
		 <?php if ( $SMFIntegration == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
	     <option <?=$sel?> value="1">Enabled</option>
		 <?php if ( $SMFIntegration == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
		 <option <?=$sel?> value="0">Disabled</option>
	   </select>
	   <span class="font12"><a href="http://www.simplemachines.org/" target="_blank">SMF website</a></span> | 
	   <span><a href="javascript:;" onclick="showhide('smfhelp')">Help?</a></span>
	   <div id="smfhelp" style="display:none;">
	   1.Go to SMF Admin->Configuration->Server Settings->Cookies and Sessions
	   <div>2. Unselect &quot;Enable local storage of cookies&quot;</div>
	   </div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td <?=$class?>>SMF Path:</td>
	   <td <?=$class?> ><input style="width:420px;" class="field" type="text" value="<?=$smf_forum?>" name="smf_forum" /></td>
	 </tr>
	 
	 <tr  class="row">
	   <td <?=$class?>>SMF URL:</td>
	   <td <?=$class?> ><input style="width:420px;" class="field" type="text" value="<?=$smf_forum_url?>" name="smf_forum_url" /></td>
	 </tr>
	 
	 
<?php if ($WPIntegration == 1 ) { $class='class="scourge"'; } else $class=""; ?>
<?php if ($WPIntegration == 1 AND !empty($wperror)) { $class='class="sentinel"'; }  ?>

	 <tr  class="row">
	   <td <?=$class?>><b>Wordpress</b> integration:<?php if (!empty($wperror)) echo "<b>Error</b>"; else 
	   if ($WPIntegration == 1 AND empty($error) ) echo '<img src="'.$website.'adm/check.png" width="16" height="16" alt="" />';
	   ?></td>
	   <td  <?=$class?>>
	   <select name="WPIntegration">
		 <?php if ( $WPIntegration == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
	     <option <?=$sel?> value="1">Enabled</option>
		 <?php if ( $WPIntegration == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?> 
		 <option <?=$sel?> value="0">Disabled</option>
	   </select>
		 <span class="font12"><a href="http://wordpress.org/" target="_blank">Wordpress website</a></span> | 
	   <span><a href="javascript:;" onclick="showhide('wphelp')">Help?</a></span>
	   <div id="wphelp" style="display:none; color: #fff;">
	   - Open <span class="sentinel">wp-config.php</span> in your Wordpress directory and add:
	   <div><span class="scourge">define('COOKIEPATH', '/');</span></div>
	   </div>
	   </td>
	 </tr>
	 
	 <tr class="row">
	   <td <?=$class?>>Wordpress Path:</td>
	   <td <?=$class?> ><input style="width:420px;" class="field" type="text" value="<?=$wp_path?>" name="wp_path" /></td>
	 </tr>
	 
	 <tr class="row">
	   <td <?=$class?>>Wordpress URL:</td>
	   <td <?=$class?> ><input style="width:420px;" class="field" type="text" value="<?=$wp_url?>" name="wp_url" /></td>
	 </tr>
	 
	<tr>
	  <th></th>
	  <th  class="padLeft"><a name="ban" href="#ban">Ban Reports/Appeals</a></th>
	</tr>
	
	 <tr  class="row">
	   <td>Enable Ban Reports:</td>
	   <td >
	   <select name="BanReports">
<?php if ($BanReports == 1) $sel = 'selected="selected"'; else $sel = ""; ?>
	      <option <?=$sel?> value="1">Yes</option>
<?php if ($BanReports == 0) $sel = 'selected="selected"'; else $sel = ""; ?>
		  <option <?=$sel?> value="0">No</option>
	   </select>
	   <?php if ( $BanReports == 0 ) echo $disp; ?>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Enable Ban Appeals:</td>
	   <td >
	   <select name="BanAppeals">
<?php if ($BanAppeals == 1) $sel = 'selected="selected"'; else $sel = ""; ?>
	      <option <?=$sel?> value="1">Yes</option>
<?php if ($BanAppeals == 0) $sel = 'selected="selected"'; else $sel = ""; ?>
		  <option <?=$sel?> value="0">No</option>
	   </select>
	   <?php if ( $BanAppeals == 0 ) echo $disp; ?>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Report Time:</td>
	   <td >
	    <input style="width:70px;" class="field" type="text" value="<?=$BanReportTime?>" name="BanReportTime" /> sec.
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Report User Link:</td>
	   <td >
	   <select name="ReportUserLink">
<?php if ($ReportUserLink == 1) $sel = 'selected="selected"'; else $sel = ""; ?>
	      <option <?=$sel?> value="1">Yes</option>
<?php if ($ReportUserLink == 0) $sel = 'selected="selected"'; else $sel = ""; ?>
		  <option <?=$sel?> value="0">No</option>
	   </select>
	   Add "Report user" link on user page
	   </td>
	 </tr>
	 
	 
	<tr>
	  <th></th>
	  <th  class="padLeft"><a name="stats" href="#stats">Stats</a></th>
	</tr>
	 
	 <tr  class="row">
	   <td>Start points:</td>
	   <td ><input style="width: 98px;" class="field" type="text" value="<?=$ScoreStart?>" name="c_sp" /></td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Win points:</td>
	   <td ><input style="width: 98px;" class="field" type="text" value="<?=$ScoreWins?>" name="c_wp" /></td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Lose points:</td>
	   <td >-<input style="width: 93px;" class="field" type="text" value="<?=$ScoreLosses?>" name="c_lp" /></td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Leave/Disc points:</td>
	   <td >-<input style="width: 93px;" class="field" type="text" value="<?=$ScoreDisc?>" name="c_dp" /></td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Time penalty:</td>
	   <td >
	   <input style="width: 93px;" class="field" type="text" value="<?=$LeftTimePenalty?>" name="LeftTimePenalty" />
	   <div>Time a player leaves before the end of the game, which loses points (<b>$ScoreDisc</b>)</div>
	   <div>Eg. if the user leaves the game 5 minutes before game end he will receive negative points -10</div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td></td>
	   <td >After changing these values, it is necessary to <a href="javascript:;" onclick="if (confirm('Are you sure you want to reset all statistics?') ) {  location.href='<?=$website?>adm/update_stats.php?reset' }" >reset Statistics</a></td>
	 </tr>
	 
	<tr>
	  <th></th>
	  <th  class="padLeft">Stats update</th>
	</tr>
	 
	 <tr  class="row">
	   <td>Update games:</td>
	   <td ><input style="width: 98px;" class="field" type="text" value="<?=$updateGames?>" name="c_upd" />
	   How many games to update at once
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Cronjob update:</td>
	   <td ><input style="width: 98px;" class="field" type="text" value="<?=$updateGamesCron?>" name="c_cron" />
	   How many games to update via cron <a href="javascript:;" onclick="showhide('cronjob')">?Setup</a>
	   <div id="cronjob" style="display: none;">
	   Cron command example: <div>
	   <textarea style="width: 450px; height: 58px;">/usr/local/bin/php <?=realpath(dirname(__FILE__));?>/cron.php > /dev/null</textarea>
	   </div>
	   </div>
	   </td>
	 </tr>
	 
	<tr>
	  <th></th>
	  <th  class="padLeft"><a name="compare" href="#compare">Compare players</a></th>
	</tr>
	
	 <tr  class="row">
	   <td>Compare players:</td>
	   <td >
	   <select name="ComparePlayers">
<?php if ($ComparePlayers == 1) $sel = 'selected="selected"'; else $sel = ""; ?>
	      <option <?=$sel?> value="1">Enabled</option>
<?php if ($ComparePlayers == 0) $sel = 'selected="selected"'; else $sel = ""; ?>
		  <option <?=$sel?> value="0">Disabled</option>
	   </select>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Maximum number of players:</td>
	   <td ><input style="width: 38px;" class="field" type="text" value="<?=$MaxPlayersToCompare?>" name="MaxPlayersToCompare" />
	   The maximum number of players for comparison
	   </td>
	 </tr>
	 
	 
	<tr>
	  <th></th>
	  <th  class="padLeft"><a name="pages" href="#pages">Pages</a></th>
	</tr>
	
	 <tr  class="row">
	   <td>Games per page:</td>
	   <td ><input style="width: 98px;" class="field" type="text" value="<?=$GamesPerPage?>" name="c_gpp" /></td>
	 </tr>
	 <tr  class="row">
	   <td>Top Players per page:</td>
	   <td ><input style="width: 98px;" class="field" type="text" value="<?=$TopPlayersPerPage?>" name="c_tpp" /></td>
	 </tr>
	 <tr  class="row">
	   <td>Heroes per page:</td>
	   <td ><input style="width: 98px;" class="field" type="text" value="<?=$HeroesPerPage?>" name="c_hpp" /></td>
	 </tr>
	 <tr  class="row">
	   <td>Items per page:</td>
	   <td ><input style="width: 98px;" class="field" type="text" value="<?=$ItemsPerPage?>" name="c_ipp" /></td>
	 </tr>
	 <tr  class="row">
	   <td>Posts per page:</td>
	   <td ><input style="width: 98px;" class="field" type="text" value="<?=$NewsPerPage?>" name="NewsPerPage" /></td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Comments per page:</td>
	   <td ><input style="width: 98px;" class="field" type="text" value="<?=$CommentsPerPage?>" name="c_cpp" /></td>
	 </tr>
	 
	 <tr  class="row">
	   <td style="vertical-align:middle;" valign="middle">Recent Games on Home page:</td>
	   <td >
	   <div class="padTop"></div>
	     <select name="c_recent">
		 <?php if ( $RecentGames == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Yes</option>
		<?php if ( $RecentGames == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">No</option>
		 </select>
		 <input style="width:32px;" class="field" type="text" value="<?=$TotalRecentGames?>" name="c_recent_n" /> Number of recent games to show
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 
	<tr  class="row">
	   <td style="vertical-align:middle;" valign="middle">Limit words on home page:</td>
	   <td  style="vertical-align:middle;">
		 <input style="width:32px;" class="field" type="text" value="<?=$NewsWordLimit?>" name="limit_words" /> 0 will disable word limit.
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td><div class="padTop"></div>Sort Comments:<div class="padTop"></div></td>
	   <td >
	   <div class="padTop"></div>
	     <select name="sort_comments">
		 <?php if ( $SortComments == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">By ID</option>
		<?php if ( $SortComments == 2 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="2">Newer first</option>
		<?php if ( $SortComments == 3 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="3">Older first</option>
		 </select>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 
	 <tr  class="row">
	   <td><div class="padTop"></div>User Activation:<div class="padTop"></div></td>
	   <td >
	   <div class="padTop"></div>
	     <select name="c_activation">
		 <?php if ( $UserActivation == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Users must verify their account via email</option>
		<?php if ( $UserActivation == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Instant activation</option>
		<?php if ( $UserActivation == 2 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="2">Disable user registration</option>
		 </select>
		 <?php if ( $UserActivation == 2 ) echo $disp; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td><div class="padTop"></div>Heroes data:<div class="padTop"></div></td>
	   <td >
	   <div class="padTop"></div>
	     <select name="c_heroes_data">
		 <?php if ( $PlayDotaHeroes == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Get heroes data from playdota website</option>
		<?php if ( $PlayDotaHeroes == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Get heroes data from database (default)</option>
		 </select>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td><div class="padTop"></div>Show Hero Stats:<div class="padTop"></div></td>
	   <td >
	   <div class="padTop"></div>
	     <select name="ShowUserHeroStats">
		 <?php if ( $ShowUserHeroStats == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Yes</option>
		<?php if ( $ShowUserHeroStats == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">No</option>
		 </select>
		 Display hero stats on user page
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Pagination links:</td>
	   <td >
	   <div class="padTop"></div>
	   <select name="c_pages">
	   <?php if ($MaxPaginationLinks==1) $sel='selected="selected"'; else $sel = ""; ?>
	     <option <?=$sel?> value="1">1</option>
	   <?php if ($MaxPaginationLinks==2) $sel='selected="selected"'; else $sel = ""; ?>
	     <option <?=$sel?> value="2">2</option>
	   <?php if ($MaxPaginationLinks==3) $sel='selected="selected"'; else $sel = ""; ?>
	     <option <?=$sel?> value="3">3</option>
	   <?php if ($MaxPaginationLinks==4) $sel='selected="selected"'; else $sel = ""; ?>
	     <option <?=$sel?> value="4">4</option>
	   <?php if ($MaxPaginationLinks==5) $sel='selected="selected"'; else $sel = ""; ?>
	     <option <?=$sel?> value="5">5</option>
	   <?php if ($MaxPaginationLinks==6) $sel='selected="selected"'; else $sel = ""; ?>
	     <option <?=$sel?> value="6">6</option>
	   <?php if ($MaxPaginationLinks==7) $sel='selected="selected"'; else $sel = ""; ?>
	     <option <?=$sel?> value="7">7</option>
	   </select>
	   (Links before and after current page) 
	   <div class="padTop"></div>
	   </td>
	 </tr>
	 
	<tr>
	  <th></th>
	  <th  class="padLeft"><a name="pages2" href="#pages2">Enable/Disable Pages</a></th>
	</tr>

	 <tr  class="row">
	   <td>Top page:</td>
	   <td >
	     <select name="c_top">
		 <?php if ( $TopPage == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Enabled</option>
		<?php if ( $TopPage == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select>
		 <?php if ( $TopPage == 0 ) echo $disp; else echo $enap; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Heroes page:</td>
	   <td >
	     <select name="c_hero">
		 <?php if ( $HeroesPage == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Enabled</option>
		<?php if ( $HeroesPage == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select>
		 <?php if ( $HeroesPage == 0 ) echo $disp; else echo $enap; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Items page:</td>
	   <td >
	     <select name="c_item">
		 <?php if ( $ItemsPage == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Enabled</option>
		<?php if ( $ItemsPage == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select>
		 <?php if ( $ItemsPage == 0 ) echo $disp; else echo $enap; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Bans page:</td>
	   <td >
	     <select name="c_ban">
		 <?php if ( $BansPage == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Enabled</option>
		<?php if ( $BansPage == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select>
		 <?php if ( $BansPage == 0 ) echo $disp; else echo $enap; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Warn page:</td>
	   <td >
	     <select name="c_warn">
		 <?php if ( $WarnPage == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Enabled</option>
		<?php if ( $WarnPage == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select>
		 <?php if ( $WarnPage == 0 ) echo $disp; else echo $enap; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Admins page:</td>
	   <td >
	     <select name="c_admin">
		 <?php if ( $AdminsPage == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Enabled</option>
		<?php if ( $AdminsPage == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select>
		 <?php if ( $AdminsPage == 0 ) echo $disp; else echo $enap; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 <tr  class="row">
	   <td>Safelist page:</td>
	   <td >
	     <select name="c_safe">
		 <?php if ( $SafelistPage == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Enabled</option>
		<?php if ( $SafelistPage == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select>
		 <?php if ( $SafelistPage == 0 ) echo $disp; else echo $enap; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Guides page:</td>
	   <td >
	     <select name="GuidesPage">
		 <?php if ( $GuidesPage == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Enabled</option>
		<?php if ( $GuidesPage == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select>
		 <?php if ( $GuidesPage == 0 ) echo $disp; else echo $enap; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>About Us page:</td>
	   <td >
	     <select name="AboutUs">
		 <?php if ( $AboutUs == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Enabled</option>
		<?php if ( $AboutUs == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select>
		 <?php if ( $AboutUs == 0 ) echo $disp; else echo $enap; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Memberlist page:</td>
	   <td >
	     <select name="MemberListPage">
		 <?php if ( $MemberListPage == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Enabled</option>
		<?php if ( $MemberListPage == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select>
		 <?php if ( $MemberListPage == 0 ) echo $disp; else echo $enap; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
<!--	 
	<tr  class="row">
	   <td>Hero Votes Page:</td>
	   <td >
	   <select name="HeroVote">
<?php if ($HeroVote == 1) $sel = 'selected="selected"'; else $sel = ""; ?>
	      <option <?=$sel?> value="1">Yes</option>
<?php if ($HeroVote == 0) $sel = 'selected="selected"'; else $sel = ""; ?>
		  <option <?=$sel?> value="0">No</option>
	   </select>
	   <?php if ( $HeroVote == 0 ) echo $disp; else echo $enap; ?>
	   <input type="text" size="3" maxlength="5" value="<?=$HeroVoteShow?>" name="HeroVoteShow" /> Display heroes on vote stats page
	   </td>
	 </tr>
-->
	 
	<tr>
	  <th></th>
	  <th  class="padLeft"><a name="misc" href="#misc">Misc</a></th>
	</tr>
	
	 <tr  class="row">
	   <td>User Avatar:</td>
	   <td >
	     <select name="AllowUploadAvatar">
		 <?php if ( $AllowUploadAvatar == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Users can upload avatars</option>
		<?php if ( $AllowUploadAvatar == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select>
		 <?php if ( $AllowUploadAvatar == 0 ) echo $disp; else echo $enap; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	<tr  class="row">
	   <td style="vertical-align:middle;" valign="middle">Max image size:</td>
	   <td  style="vertical-align:middle;">
		 <input style="width:32px;" class="field" type="text" value="<?=$MaxImageSize?>" name="MaxImageSize" /> px
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	<tr  class="row">
	   <td style="vertical-align:middle;" valign="middle">Image quality:</td>
	   <td  style="vertical-align:middle;">
		 <input style="width:32px;" class="field" type="text" value="<?=$ImageQuality?>" name="ImageQuality" /> 1-100
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Show user country:</td>
	   <td >
	     <select name="ShowMembersCountry">
		 <?php if ( $ShowMembersCountry == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Display country</option>
		<?php if ( $ShowMembersCountry == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select> 
		 <?php if ( $ShowMembersCountry == 0 ) echo $disp; else echo $enap; ?>
		 Display user country on memberlist page
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	 <tr  class="row">
	   <td>Enable debug:</td>
	   <td >
	     <select name="c_debug">
		 <?php if ( $_debug == 1 ) $sel = 'selected="selected"'; else $sel = ""; ?>
		    <option <?=$sel?> value="1">Enabled</option>
		<?php if ( $_debug == 0 ) $sel = 'selected="selected"'; else $sel = ""; ?>
			<option <?=$sel?> value="0">Disabled</option>
		 </select>
		 <?php if ( $_debug == 0 ) echo $disp; else echo $enap; ?>
		<div class="padTop"></div>
	   </td>
	 </tr>
	 
	<tr>
	  <td></td>
	  <td>
	  <div class="padTop"></div>
	  <input type="submit" value="Save Configuration" class="menuButtons" name="update_config" />
	  <div class="padTop"></div>
	  </td>
	</tr>
	 	
	</table>

</form>

</div>
<?php } ?>