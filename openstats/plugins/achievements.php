<?php
//Plugin: User Achievements
//Author: Ivan
//Display user achievements on single user page

if (!isset($website) ) { header("HTTP/1.1 404 Not Found"); die; }

$PluginEnabled = '0';
//Enable edit plugin options
$PluginOptions = '1';

$ThisPlugin = basename(__FILE__, '');

$killsGoal = '500';
$assistsGoal = '200';
$winslossesGoal = '85';
$kpGoal = '60';
$gamesGoal = '50';
$winsGoal = '50';
$creepsGoal = '500';
$denieGoal = '500';
$towersGoal = '50';
$neutralsGoal = '500';
$PlayTimeGoal = '24';

define('killsGoal', $killsGoal);
define('assistsGoal', $assistsGoal);
define('winslossesGoal', $winslossesGoal);
define('kpGoal', $kpGoal);
define('gamesGoal', $gamesGoal);
define('winsGoal', $winsGoal);
define('creepsGoal', $creepsGoal);
define('denieGoal', $denieGoal);
define('towersGoal', $towersGoal);
define('neutralsGoal', $neutralsGoal);
define('PlayTimeGoal', $PlayTimeGoal);

if ($PluginEnabled == 1  ) {

  


if ( OS_is_admin() AND OS_PluginEdit( $ThisPlugin ) ) {

if ( isset($_POST["editAchievements"]) ) {
   write_value_of('$killsGoal', "$killsGoal", $_POST["killsGoal"] , $plugins_dir.basename(__FILE__, '') );
   write_value_of('$assistsGoal', "$assistsGoal", $_POST["assistsGoal"] , $plugins_dir.basename(__FILE__, '') );
   write_value_of('$winslossesGoal',"$winslossesGoal",$_POST["winslossesGoal"],$plugins_dir.basename(__FILE__, '') );
   write_value_of('$kpGoal', "$kpGoal", $_POST["kpGoal"] , $plugins_dir.basename(__FILE__, '') );
   write_value_of('$gamesGoal', "$gamesGoal", $_POST["gamesGoal"] , $plugins_dir.basename(__FILE__, '') );
   write_value_of('$winsGoal', "$winsGoal", $_POST["winsGoal"] , $plugins_dir.basename(__FILE__, '') );
   write_value_of('$creepsGoal', "$creepsGoal", $_POST["creepsGoal"] , $plugins_dir.basename(__FILE__, '') );
   write_value_of('$denieGoal', "$denieGoal", $_POST["denieGoal"] , $plugins_dir.basename(__FILE__, '') );
   write_value_of('$towersGoal', "$towersGoal", $_POST["towersGoal"] , $plugins_dir.basename(__FILE__, '') );
   write_value_of('$neutralsGoal', "$neutralsGoal", $_POST["neutralsGoal"] , $plugins_dir.basename(__FILE__, '') );
   write_value_of('$PlayTimeGoal', "$PlayTimeGoal", $_POST["PlayTimeGoal"] , $plugins_dir.basename(__FILE__, '') );
   
   $Option = '
   <div style="height:230px;">
     <h2>Updated. </h2>
     <a href="'.OS_HOME.'adm/?plugins&amp;edit='.basename(__FILE__).'&amp;'.generate_hash(8).'#'.basename(__FILE__).'" class="menuButtons">Refresh</a>
   </div>';
}
else {

  function OS_AddAchieveOption ( $text = "", $value = 0, $valueName = '', $text2 = "", $more = "" ) {
    
	$option = '<tr class="row">
	  <td align="right">'.$text.'</td>
	  <td width="58" align="left" class="padLeft">
	     <input size="1" type="text" value="'.$value.'" name="'.$valueName.'" />'.$more.'
	  </td>
	  <td align="left">'.$text2.'</td>
	</tr>';
	
	return $option;
	
  }

$Option = '<form action="" method="post" ><table>';
   
$Option.= OS_AddAchieveOption ( "Kill" ,      killsGoal,      'killsGoal', "enemy heroes." );
$Option.= OS_AddAchieveOption ( "Assist in" , assistsGoal,    'assistsGoal', "kills." );
$Option.= OS_AddAchieveOption ( "Achieve" ,   winslossesGoal, 'winslossesGoal', "wins." );
$Option.= OS_AddAchieveOption ( "Achieve" ,   kpGoal,         'kpGoal', "kills." );
$Option.= OS_AddAchieveOption ( "Play" ,      gamesGoal,      'gamesGoal', "games." );
$Option.= OS_AddAchieveOption ( "Win" ,       winsGoal,       'winsGoal', "games." );
$Option.= OS_AddAchieveOption ( "Kill" ,      creepsGoal,     'creepsGoal', "creeps." );
$Option.= OS_AddAchieveOption ( "Deny" ,      denieGoal,      'denieGoal', "creeps." );
$Option.= OS_AddAchieveOption ( "Destroy" ,   towersGoal,     'towersGoal', "towers." );
$Option.= OS_AddAchieveOption ( "Kill" ,      neutralsGoal,   'neutralsGoal', "neutrals." );
$Option.= OS_AddAchieveOption ( "Play at least" , PlayTimeGoal, 'PlayTimeGoal', "hours." );

$Option.='</table><div class="padTop"></div><input name="editAchievements" type="submit" value = "Save" class="menuButtons" /> &nbsp; <a href="'.OS_HOME.'adm/?plugins" class="menuButtons">&laquo; Back</a></form>';
  }
}

  function OS_UserAchievements() {
  
    global $UserData;
	global $TimePlayed;
		
	if ( OS_single_user() AND !empty($UserData) ) {

   
    $score = OS_Number( $UserData[0]["score"] );
	$kills = OS_Number( $UserData[0]["kills"] );
	$assists = OS_Number( $UserData[0]["assists"] );
	$deaths = OS_Number( $UserData[0]["deaths"] );
	$winslosses = ( $UserData[0]["winslosses"] );
	$kd = OS_Number( $UserData[0]["kd"] );
	$games = OS_Number( $UserData[0]["games"] );
	$wins = OS_Number( $UserData[0]["wins"] );
	$losses = OS_Number( $UserData[0]["losses"] );
	$creeps = OS_Number( $UserData[0]["creeps"] );
	$towers = OS_Number( $UserData[0]["towers"] );
	$denies = OS_Number( $UserData[0]["denies"] );
	$rax = OS_Number( $UserData[0]["rax"] );
	$kpm = OS_Number( $UserData[0]["kpg"] );
	$dpm = OS_Number( $UserData[0]["dpg"] );
	$neutrals = OS_Number( $UserData[0]["neutrals"] );
	
	if ($kills >0) $kp = ROUND($kills/($kills+$deaths), 4)*100;  else {$kp = 0;}
	
	$PTIME = explode("h ", $TimePlayed["timeplayed"]);
	
	$PlayedTime = $PTIME[0];

	?>
	<div class="padTop"></div>
	<div class="padTop"></div>
	
	<div class="aligncenter">
	   <a href="javascript:;" onclick="showhide('achievements')"><h2>Achievements</h2></a>
	</div>
	
	<div id="achievements">
	  <table>
	  <?=OS_ShowAchievement( $kills,      killsGoal,      "kills.png",    "Kill",       "enemy heroes.", "" )?>
	  <?=OS_ShowAchievement( $assists,    assistsGoal,    "assist.png",   "Assist in",  "kills.",        "" )?>
	  <?=OS_ShowAchievement( $winslosses, winslossesGoal, "wins.png",     "Achieve ",   "wins.",         "%" )?>
      <?=OS_ShowAchievement( $kp,         kpGoal,         "killperc.gif", "Achieve ",   "kills.",        "%" )?>
      <?=OS_ShowAchievement( $games,      gamesGoal,      "games.png",    "Play ",      "games.",        "" )?>
      <?=OS_ShowAchievement( $wins,       winsGoal,       "winperc.gif",  "Win ",       "games.",        "" )?>
	  <?=OS_ShowAchievement( $creeps,     creepsGoal,     "creeps.gif",   "Kill ",      "creeps.",       "" )?>
	  <?=OS_ShowAchievement( $denies,     denieGoal,      "denies.gif",   "Deny ",      "creeps.",       "" )?>
      <?=OS_ShowAchievement( $towers,     towersGoal,     "towers.png",   "Destroy ",   "towers.",       "" )?>
      <?=OS_ShowAchievement( $neutrals,   neutralsGoal,   "neutrals.png", "Kill ",      "neutrals.",     "" )?>
      <?=OS_ShowAchievement( $PlayedTime, PlayTimeGoal,   "play.gif",  "Play at least", "hours.",        "" )?>
	  </table>
	  <?php
	  ?>
	</div>
	<?php
	
	}

  }
  
  function OS_ShowAchievement( $value = "", $goalValue= "", $icon= "", $txt1 = "", $txt2 = "", $more = "" ) {
    if ( $goalValue>=1 ) {
    ?>
	<?php if ($value>=$goalValue) { $col = '33B412'; $a=1;} else { $col = '818181'; $a = 0; } ?>
	<tr>
		  <td width="64"><?=OS_AchievementIcon( $icon, $a )?></td>
		  <td style="color: #<?=$col?>" class="padLeft imgvalign"><?=$txt1?> <?=$goalValue?><?=$more?> <?=$txt2?> ( <?=$value?><?=$more?> )</td>
	</tr>
	<?php
	}
  }
  
  function OS_AchievementIcon( $image,  $achieved = 1 ) {
  
    if ($achieved == 0 ) $style='style="opacity: 0.1; filter: alpha(opacity = 15);"'; else $style= "";
	
    $icon = '<img '.$style.' src="'.OS_HOME.OS_PLUGINS_DIR.'/achievements/'.$image.'" alt="image" width="64" height="64" />';
	
	return $icon;
  }
  
    AddEvent("os_display_custom_fields",   "OS_UserAchievements");
  
  
}
?>