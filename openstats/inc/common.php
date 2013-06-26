<?php
/*********************************************
<!-- 
*   	DOTA OPENSTATS
*   
*	Developers: Ivan.
*	Contact: ivan.anta@gmail.com - Ivan
*
*	
*	Please see http://openstats.iz.rs
*	and post your webpage there, so I know who's using it.
*
*	Files downloaded from http://openstats.iz.rs
*
*	Copyright (C) 2010  Ivan
*
*
*	This file is part of DOTA OPENSTATS.
*
* 
*	 DOTA OPENSTATS is free software: you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation, either version 3 of the License, or
*    (at your option) any later version.
*
*    DOTA OPEN STATS is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with DOTA OPEN STATS.  If not, see <http://www.gnu.org/licenses/>
*
-->
**********************************************/
if (!isset($website) ) {header('HTTP/1.1 404 Not Found'); die; }

$errors = "";

if ( isset($_SESSION["user_lang"]) AND file_exists("lang/".$_SESSION["user_lang"].".php") ) {
   $default_language = $_SESSION["user_lang"];
}

include("Hook.php");
include("default-constants.php");
include("common-queries.php");

date_default_timezone_set($TimeZone);

function add_event($event, $value = NULL, $callback = NULL)
{
    static $events;

    // Adding or removing a callback?
    if($callback !== NULL)
    {
        if($callback)
        {
            $events[$event][] = $callback;
        }
        else
        {
            unset($events[$event]);
        }
    }
    elseif(isset($events[$event])) // Fire a callback
    {
        foreach($events[$event] as $function)
        {
            $value = call_user_func($function, $value);
        }
        return $value;
    }
}

	
  function safeEscape($text)
  {
   if (is_numeric($text)) $text=floor($text);
  $text = htmlspecialchars(strip_tags($text));
  $text = htmlentities($text);
  $text = str_replace ('"','',$text);
  $text = str_replace(array("%20", "\"", "'", "\\", "=", ";", ":"), array("","","","","","",""), $text);
  return $text;
  }
  
  
  function EscapeStr($text)
  {
  $text = htmlentities($text);
  //$text = str_replace(array("%20", "\"", "'", "\\", "=", ";", ":"), "", $text);
  return $text;
  }
  
function FilterData($data) {

    if ( is_array($data) ) foreach( $data as $d ) {
	$d = trim(htmlentities(strip_tags($d)));
	$data[] = $d;
	}
	else $data = trim(htmlentities(strip_tags($data)));
 
    if (get_magic_quotes_gpc())
        if ( !is_array($data) ) $data = stripslashes($data);
 
    if ( !is_array($data) ) $data = htmlentities(trim($data));
 
    return $data;
}

function ShowUserAvatar( $avatar, $w = "64", $h = "64", $style="", $RemoveAvatar = "Remove Avatar", $Default = 0 ) {
  
    if ($w>=8 AND $h>=8) { $dim =' width="'.$w.'" height="'.$h.'"'; } else $dim = "";
	if ( !empty($style) ) $_style=' style="'.$style.'"'; else $_style = "";

  if ( !empty($avatar) ) {
  ?>
  <img src="<?=$avatar?>" <?=$dim?> alt="avatar" <?=$_style?> class="imgvaligntop user_avatar" />
  <?php if ($RemoveAvatar!="") { ?>
  <input type="checkbox" name="removeAvatar" value="1" /> <?=$RemoveAvatar?>
  <?php } ?>
  <?php
  } else {
    if ( $Default == 1) {
	?><img src="img/avatar_64.png" <?=$dim?> alt="avatar" <?=$_style?> class="imgvaligntop user_avatar" /><?php
	}
  }
}

function UploadAvatar ( $allowed = "0", $CurrentAvatar = "", $UploadTitle = "Upload Image" ) {
  if ( $allowed == 1 AND empty($CurrentAvatar) ) {
  ?>
  <input name="avatar_upload" id="new_image" size="30" type="file" class="fileUpload" /> 
  <?php /* <button name="submit_avatar" type="submit" class="menuButtons"><?=$UploadTitle?></button><?php */ ?>
  <?php
  }
}

function OS_is_admin() {
  if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) {
  return true;
  } else return false;
}

function OS_PluginEdit($edit = "" ) {
  if ( !empty($edit) AND isset($_GET["plugins"]) AND isset($_GET["edit"]) AND $_GET["edit"] == $edit ) return true;
  else 
  if ( isset($_GET["plugins"]) AND isset($_GET["edit"]) ) return true;
      return false;
}

function OS_WinLoseIcon( $winner = 0) {
     if ($winner == 1) { ?>
 	 <img src="<?=OS_HOME?>img/winner.png" alt="*" width="24" height="24" class="imgvalign" />
 	 <?php } ?>
 	 <?php if ($winner == 2) { ?>
 	 <img src="<?=OS_HOME?>img/loser.png"  alt="*" width="24" height="24" class="imgvalign" />
 	 <?php } ?>
 	 <?php if ($winner == 0) { ?>
 	 <img src="<?=OS_HOME?>img/draw.png"   alt="*" width="24" height="24" class="imgvalign" />
 	 <?php }
}

function EditUserLink($id) {
 ?>
 <a href="adm/?users&amp;edit=<?=$id?>">edit</a>
 <?php
}

function UserGender( $gender, $male = "Male", $female = "female" ) {
   if ( $gender == 1 ) return $male;
   if ( $gender == 2 ) return $female;
}
  
  function secondsToTime($seconds, $h = ":", $m = ":", $s = "")//Returns the time like 1:43:32
{
	$hours = floor($seconds/3600);
	$secondsRemaining = $seconds % 3600;
	
	$minutes = floor($secondsRemaining/60);
	$seconds_left = $secondsRemaining % 60;
	
	if($hours != 0)
	{
		if(strlen($minutes) == 1)
		{
		$minutes = "0".$minutes;
		}
		if(strlen($seconds_left) == 1)
		{
		$seconds_left = "0".$seconds_left;
		}
		return $hours."$h".$minutes."$m".$seconds_left."$s";
	}
	else
	{
		if(strlen($seconds_left) == 1)
		{
		$seconds_left = "0".$seconds_left;
		}
		return $minutes."$m".$seconds_left."";
	}
}

   function millisecondsToTime($milliseconds)//returns the time like 5.2 (5 seconds, 200 milliseconds)
{
	$return="";
	$return2="";
     // get the seconds
	$seconds = floor($milliseconds / 1000) ;
	$milliseconds = $milliseconds % 1000;
	$milliseconds = round($milliseconds/100,0);
	
	// get the minutes
	$minutes = floor($seconds / 60) ;
	$seconds_left = $seconds % 60 ;

	// get the hours
	$hours = floor($minutes / 60) ;
	$minutes_left = $minutes % 60 ;
	
// A little unneccasary with minutes and hours,,  but HEY  everythings possible
	if($hours)
	{
		$return ="$hours"."h ";
	}
	if($minutes_left)
	{
	    if ( $minutes_left<=9 ) $minutes_left = "0".$minutes_left;
		$return2 ="$minutes_left"."m ";
	}
	
	if ( !$hours ) $ms = ".".$milliseconds; else $ms = "";
return $return.$return2.$seconds_left.$ms;
}  

///////////////////////////////////////////////////////////////
     function replayDuration($seconds)
{
	$minutes = floor($seconds/60);
	$seconds_left = $seconds % 60;
	
	if(strlen($seconds_left) == 1)
	{
	$seconds_left = "0".$seconds_left;
	}
	return $minutes."m".$seconds_left."s";
}

   
    function getTeam($color)
{
	switch ($color) {
		case 'red': return 0;
		case 'blue': return 1;
		case 'teal': return 1;
		case 'purple': return 1;
		case 'yellow': return 1;
		case 'orange': return 1;
		case 'green': return 0;
		case 'pink': return 2;
		case 'gray': return 2;
		case 'light-blue': return 2;
		case 'dark-green': return 2;
		case 'brown': return 2;
		case 'observer': return 0;
	}
}

    function getMonthName($month,
	$ljan = "JAN",
	$lfeb = "FEB",
	$lmar = "MAR",
	$lapr = "APR",
	$lmay = "MAY",
	$ljun = "JUN",
	$ljul = "JUL",
	$laug = "AUG",
	$lsep = "SEP",
	$loct = "OCT",
	$lnov = "NOV",
	$ldec = "DEC"
	           ) 
	{
	if ($month == 1) $rmonth = $ljan;
	if ($month == 2) $rmonth = $lfeb;
	if ($month == 3) $rmonth = $lmar;
	if ($month == 4) $rmonth = $lapr;
	if ($month == 5) $rmonth = $lmay;
	if ($month == 6) $rmonth = $ljun;
	if ($month == 7) $rmonth = $ljul;
	if ($month == 8) $rmonth = $laug;
	if ($month == 9) $rmonth = $lsep;
	if ($month == 10) $rmonth = $loct;
	if ($month == 11) $rmonth = $lnov;
	if ($month == 12) $rmonth = $ldec;
	
	return $rmonth;
	
	}
	
	function getDays($m){
	return 31;
	if ($m == 1) return 31;
	if ($m == 2) return 28;
	if ($m == 3) return 31;
	if ($m == 4) return 30;
	if ($m == 5) return 31;
	if ($m == 6) return 30;
	if ($m == 7) return 31;
	if ($m == 8) return 30;
	if ($m == 9) return 31;
	if ($m == 10) return 30;
	if ($m == 11) return 31;
	if ($m == 12) return 30;
	}

   
///////////////////////

function ShowToolTip($text, $img = "", $width = "", $imgwidth = "", $imgheight = "") {
  ?> onMouseout="hidetooltip()" onMouseover="tooltip('<?=os_strip_quotes($text)?>', '<?=$img?>', <?=$width?>, <?=$imgwidth?>, <?=$imgheight?>)" 
  <?php
}

function convEnt($text){
return str_replace(
array('&amp;amp;quot;', "&amp;amp;", '<br>', '&amp;#039;', '&#039;', '&quot;', '&amp;', '&#36;', '&lt;', '&gt;'), 
array('"',             "&",          "\r\n",  "'",         "'",      '"',      '&amp;', '$',     '<',    '>'), $text);
}

function convEnt2($text){
return strip_tags(str_replace(
array("'", '"', "<", ">",'$'), 
array('&#039;', '&quot;','&lt;', '&gt;','&#36;'), $text));
}


function os_strip_quotes($text) {
return str_replace(array("'", '"'), array("", ''), $text);
}

///////////////////////

   function my_nl2br($str, $rep = "\r\n", $max = 2) {
$arr = explode("\r\n", $str);
$str = '';
$nls = 0;
    foreach($arr as $line) {
    $str .= $line;
    if (empty($line)) {
    $nls++;
    } else {
    $nls = 0;
           }
      if ($nls < $max) {
      $str .= $rep;
                       }
      }
return substr($str, 0, strlen($str) - strlen($rep));
}

function br2nl($string)
{
    return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
}

function OS_StrToUTF8( $text ) {
   $text = htmlspecialchars( html_entity_decode( $text ), ENT_QUOTES, 'UTF-8'); 
   return $text;
}

function PrepareTextDB($text) {

  $text = ( trim($text) );
  while ( strstr($text, "\n\n") ) $text = str_replace("\n\n", "\n", $text);
  $text = str_replace("\n\r\n\r", "\n\r", $text);
  while ( strstr($text, "\n\r\n\r") ) $text = str_replace("\n\r\n\r", "\n\r", $text);
  $text = str_replace("\n", "<br />", $text);
  $text = preg_replace('/\v+|\\\[rn]/','<br />', $text);
  $text = nl2br($text)."" ;
  while ( strstr($text, "<br /><br />") ) $text = str_replace("<br /><br />", "<br />", $text);
  $text = str_replace("<br /><br />", "<br />", $text);
  
  $text = stripslashes($text);
  
  $text = EscapeStr( ($text) );
  
  //if (get_magic_quotes_gpc())
  //$text = stripslashes($text);
	
  return $text;
}

function BBCode ($text) {
$search = array(
    '@\[(?i)b\](.*?)\[/(?i)b\]@si',
    '@\[(?i)i\](.*?)\[/(?i)i\]@si',
    '@\[(?i)u\](.*?)\[/(?i)u\]@si',
	'#\[s\](.*?)\[/s\]#is',
	'/\[ul\]/is',
	'/\[\/ul\]/is',
	'/\[li\]/is',
	'/\[\/li\]/is',
    '#\[img\](.*?)\[/img\]#i',
    '@\[(?i)url=(.*?)\](.*?)\[/(?i)url\]@si',
	'/\[url\]([^\"]*?)\[\/url\]/si',
	'/\[font(#[A-F0-9]{6})\](.+?)\[\/font\]/is',
	'/\[font=([^\]]*?)\]([\s\S]*?)\[\/font\]/is',
	'/\[color(#[A-F0-9]{6})\](.+?)\[\/color\]/is',
	'/\[color=([^\]]*?)\]([\s\S]*?)\[\/color\]/is',
	'~\[quote\]~is',
	'~\[/quote\]~is',
	'~\[quote=(.+?)\]~is',
	'/\[justify\][\r\n]*(.+?)\[\/justify\][\r\n]*/si',
	'/\[youtube=http:\/(\/www\.|\/[a-z]+\.|\/)youtube\.com\/watch\?v=([a-zA-Z0-9-_]+)(.*)\]/si',
	'/\[youtube]http:\/(\/www\.|\/[a-z]+\.|\/)youtube\.com\/watch\?v=([a-zA-Z0-9-_]+)(.*)\[\/youtube\]/si',
    '@\[(?i)code\](.*?)\[/(?i)code\]@si',
	'/\[code\](.*?)\[\/code\]/is',
	'/\[left\](.*?)\[\/left\]/is',
	'/\[right\](.*?)\[\/right\]/is',
	'/\[center\](.*?)\[\/center\]/is',
	'#\[size=([1-9]|1[0-9]|24)\](.*?)\[/size\]#is',
	'/\[hl\][\r\n]*(.+?)\[\/hl\][\r\n]*/is',
	'/\[php\](.*?)\[\/php\]/is',
	'/\[spoiler\][\r\n]*(.+?)\[\/spoiler\][\r\n]*/si',
);
$replace = array(
    '<b>\\1</b>',
    '<i>\\1</i>',
    '<u>\\1</u>',
	'<span style="text-decoration: line-through;">$1</span>',
	'<ul>',
	'</ul>',
	'<li>',
	'</li>',
    '<img src="\\1"/>',
    '<a href="\\1" target="_blank">\\2</a>',
	'<a href="\\1" target="_blank">\\1</a>',
	'<span style="color:\\1">\\2</span>',
	'<span style=\"color: $1\">$2</span>',
	'<span style="color:\\1">\\2</span>',
	'<span style="color: $1">$2</span>',
	
	'<table style="width:90%" border=0><tr><td class="singlequoting">',
	'</td></tr></table>',
	
	'<table style="width:90%"><tr><td class="quoting">\\1</td></tr><tr><td class="quote">\\2',
	
	'<div align="justify">\\1</div>',
	'<object width=\"640\" height=\"385\"><param name=\"movie\" value=\"http://www.youtube.com/v/\\2\"></param><param name=\"wmode\" value=\"transparent\"></param><embed src=\"http://www.youtube.com/v/\\2\" type=\"application/x-shockwave-flash\" wmode=\"transparent\" width=\"640\" height=\"385\"></embed></object>',
	'<object width=\"640\" height=\"385\"><param name=\"movie\" value=\"http://www.youtube.com/v/\\2\"></param><param name=\"wmode\" value=\"transparent\"></param><embed src=\"http://www.youtube.com/v/\\2\" type=\"application/x-shockwave-flash\" wmode=\"transparent\" width=\"640\" height=\"385\"></embed></object>',
    '<pre>\\1</pre>',	
	'<pre>$1</pre>',
	'<div style="text-align: left;">$1</div>',
	'<div style="text-align: right;">$1</div>',
	'<div style="text-align: center;">$1</div>',
	'<span style="font-size: $1px;">$2</span>',
	'<span class="hl">\\1</span>',
	'<pre class="brush: php;">$1</pre>',
	'<div class="spoilerdiv"><input class="spoiler" type="button" onclick="showSpoiler(this);" value="Show/Hide" /><div class="inner" style="display:none;">$1</div></div>');
return preg_replace($search , $replace, $text);
}

function BBDecode ($text) {
$search = array(
    '/<a href=\"([^<> \n\r\[\]]+?)\" target=\"(_new|_blank)\">(.+?)<\/a>/i',
	'/<span style="color: (.+?)">(.+?)<\/span>/is',
    '~<table style="width:90%" border=0><tr><td class="singlequoting">(.+?)</td></tr></table>~is',
    '/<a\s[^<>]*?href=\"?([^<>]*?)\"?(\s[^<>]*)?>([^<>]*?)<\/a>/si',
    '/<b>(.+?)<\/b>/is',
    '/<u>(.+?)<\/u>/is',
	'/<i>(.+?)<\/i>/is',
	'/<span style="font-size: (.+?)px;\">(.+?)<\/span>/is',
	'/<span style="text-decoration: line-through;\">(.+?)<\/span>/is',
	
	'~<table style="width:90%"><tr><td class="quoting">(.+?)</td></tr><tr><td class="quote">(.+?)</td></tr></table>~is',
	
	'~<table style="width:90%"><tr><td class="quoting">(.+?)</td></tr><tr><td class="quote">~is',
	
	'~</td></tr></table>~is',
	
	'/<div style="text-align: center;">(.+?)<\/div>/is',
	'/<div style="text-align: left;">(.+?)<\/div>/is',
	'/<div style="text-align: right;">(.+?)<\/div>/is',
	'/<div align="justify">(.+?)<\/div>/is',
	'/<img src=\"([^<> \n\r\[\]&]+?)\" alt=\"(.+?)\" (title=\"(.+?)\" )?\/>/si',
	'/<img\s[^<>]*?src=\"?([^<>]*?)\"?(\s[^<>]*)?\/?>/si',
	'/<object width=\"[0-9]+\" height=\"[0-9]+\"><param name=\"movie\" value=\"http:\/\/www\.youtube\.com\/v\/([a-zA-Z0-9-_]+)\"><\/param><param name=\"wmode\" value=\"transparent\"><\/param><embed src=\"http:\/\/www\.youtube\.com\/v\/([a-zA-Z0-9-_]+)\" type=\"application\/x-shockwave-flash\" wmode=\"transparent\" width=\"[0-9]+\" height=\"[0-9]+\"><\/embed><\/object>/si',
	'/<span class="hl">(.+?)<\/span>/is',
	'/<pre class="brush: php;">(.+?)<\/pre>/is',
	'/<div class="spoilerdiv"><input class="spoiler" type="button" onclick="showSpoiler\(this\);" value="Show\/Hide" \/><div class="inner" style="display:none;">(.+?)<\/div><\/div>/is'
);
$replace = array(
    '[url=\\1]\\3[/url]',
    '[color=\\1]\\2[/color]',
    '[quote]\\1[/quote]',
    '[url]$3[/url]',
    '[b]\\1[/b]',
	'[u]\\1[/u]',
	'[i]\\1[/i]',
	'[size=\\1]\\2[/size]',
	'[s]\\1[/s]',
	'[quote=\\1]\\2[/quote]',
	'[quote=\\1]',
	'[/quote]',
	'[center]\\1[/center]',
	'[left]\\1[/left]',
	'[right]\\1[/right]',
	'[justify]\\1[/justify]',
	'[img=\\1]\\2[/img]',
	'[img]$1[/img]',
	'[youtube]http://www.youtube.com/watch?v=\\1[/youtube]',
	'[hl]\\1[/hl]',
	'[php]\\1[/php]',
	'[spoiler]\\1[/spoiler]'
);
return preg_replace($search , $replace, $text);
}

function is_logged() {
   if ( 
     isset( $_SESSION["username"]) AND  
     isset( $_SESSION["email"])    AND  
     isset( $_SESSION["level"])    AND  
     isset( $_SESSION["logged"])   AND  
     isset( $_SESSION["user_id"]) 
   ) 
   return true;
   else return false;
}

function os_is_logged() {
   if ( 
     isset( $_SESSION["username"]) AND  
     isset( $_SESSION["email"])    AND  
     isset( $_SESSION["level"])    AND  
     isset( $_SESSION["logged"])   AND  
     isset( $_SESSION["user_id"]) 
   ) 
   return true;
   else return false;
}

function os_logout() {
   if ( isset($_SESSION["user_id"]) )     unset($_SESSION["user_id"]);
   if ( isset($_SESSION["username"]) )    unset($_SESSION["username"]);
   if ( isset($_SESSION["email"]) )       unset($_SESSION["email"]);
   if ( isset($_SESSION["level"]) )       unset($_SESSION["level"]);
   if ( isset($_SESSION["can_comment"]) ) unset($_SESSION["can_comment"]);
   if ( isset($_SESSION["logged"]) )      unset($_SESSION["logged"]);
   if ( isset($_SESSION["www"]) )         unset($_SESSION["www"]);
   session_destroy();
}

  function convHTML($ic1,$ic2,$ic3,$ic4,$ic5,$ic6,$HTML,$hero,$hero2,$url){
  
              $HTML = str_replace("$ic1","",$HTML);
			  $HTML = str_replace("$ic2","",$HTML);
			  $HTML = str_replace("$ic3","",$HTML);
			  $HTML = str_replace("$ic4","",$HTML);
			  $HTML = str_replace("$ic5","",$HTML);
			  $HTML = str_replace("$ic6","",$HTML);
			  $HTML = str_replace("'./img/items/","'$url/img/items/",$HTML);
			  $HTML = str_replace("$hero",
			  "<img title='$hero2' alt='' width='32' height='32' src='$url/img/heroes/$hero2.gif' />",$HTML);
			  //$HTML = BBDecode($HTML);
              //$HTML = strip_tags($HTML);
			  return $HTML;
  
  } 
  //Get links from playdota.com (eg. http://www.playdota.com/heroes/HERO_NAME )
  //Function autogenerated by script (this is still experimental feature)
  function get_HeroByID($hid) {
  $return = "";
     if ($hid == 'H06S') $return = 'admiral'; 
     if ($hid == 'N01I') $return = 'alchemist'; 
     if ($hid == 'N0HP') $return = 'ancient-apparition'; 
     if ($hid == 'EDEM') $return = 'anti-mage'; 
     if ($hid == 'OPGH') $return = 'axe'; 
     if ($hid == 'OSHD') $return = 'bane-elemental'; 
     if ($hid == 'O016') $return = 'batrider'; 
     if ($hid == 'H00D') $return = 'beastmaster'; 
     if ($hid == 'HVSH') $return = 'bloodseeker'; 
     if ($hid == 'E004') $return = 'bone-fletcher'; 
     if ($hid == 'NAKA') $return = 'bounty-hunter'; 
     if ($hid == 'H008') $return = 'bristleback'; 
     if ($hid == 'U006') $return = 'broodmother'; 
     if ($hid == 'U00F') $return = 'butcher'; 
     if ($hid == 'H000') $return = 'centaur-warchief'; 
     if ($hid == 'U00A') $return = 'chaos-knight'; 
     if ($hid == 'H00T') $return = 'clockwerk-goblin'; 
     if ($hid == 'HJAI') $return = 'crystal-maiden'; 
     if ($hid == 'H00N') $return = 'dark-seer'; 
     if ($hid == 'UC76') $return = 'death-prophet'; 
     if ($hid == 'UC18') $return = 'demon-witch'; 
     if ($hid == 'UC42') $return = 'doom-bringer'; 
     if ($hid == 'HLGR') $return = 'dragon-knight'; 
     if ($hid == 'NBRN') $return = 'drow-ranger'; 
     if ($hid == 'USYL') $return = 'dwarven-sniper'; 
     if ($hid == 'OTCH') $return = 'earthshaker'; 
     if ($hid == 'EMOO') $return = 'enchantress'; 
     if ($hid == 'UKTL') $return = 'enigma'; 
     if ($hid == 'EC45') $return = 'faceless-void'; 
     if ($hid == 'N00B') $return = 'faerie-dragon'; 
     if ($hid == 'E02J') $return = 'disruptor'; 
     if ($hid == 'H00I') $return = 'geomancer'; 
     if ($hid == 'H00K') $return = 'goblin-techies'; 
     if ($hid == 'H00V') $return = 'gorgon'; 
     if ($hid == 'O01F') $return = 'guardian-wisp'; 
     if ($hid == 'E02N') $return = 'gyrocopter'; 
     if ($hid == 'H00A') $return = 'holy-knight'; 
     if ($hid == 'H00U') $return = 'invoker'; 
     if ($hid == 'NBBC') $return = 'juggernaut'; 
     if ($hid == 'HBLM') $return = 'keeper-of-the-light'; 
     if ($hid == 'ULIC') $return = 'lich'; 
     if ($hid == 'U00C') $return = 'lifestealer'; 
     if ($hid == 'E002') $return = 'lightning-revenant'; 
     if ($hid == 'N01O') $return = 'lone-druid'; 
     if ($hid == 'UDEA') $return = 'lord-of-avernus'; 
     if ($hid == 'HMBR') $return = 'lord-of-olympus'; 
     if ($hid == 'U008') $return = 'lycanthrope'; 
     if ($hid == 'UC11') $return = 'magnataur'; 
     if ($hid == 'E005') $return = 'moon-rider'; 
     if ($hid == 'O00P') $return = 'morphling'; 
     if ($hid == 'H071') $return = 'murloc-nightcrawler'; 
     if ($hid == 'HC49') $return = 'naga-siren'; 
     if ($hid == 'UC60') $return = 'necrolic'; 
     if ($hid == 'U00E') $return = 'necrolyte'; 
     if ($hid == 'U000') $return = 'nerubian-assassin'; 
     if ($hid == 'UBAL') $return = 'nerubian-weaver'; 
     if ($hid == 'EC77') $return = 'netherdrake'; 
     if ($hid == 'UDRE') $return = 'night-stalker'; 
     if ($hid == 'H00H') $return = 'oblivion'; 
     if ($hid == 'U00P') $return = 'obsidian-destroyer'; 
     if ($hid == 'HMKG') $return = 'ogre-magi'; 
     if ($hid == 'HARF') $return = 'omniknight'; 
     if ($hid == 'NPBM') $return = 'pandaren-brewmaster'; 
     if ($hid == 'EWAR') $return = 'phantom-assassin'; 
     if ($hid == 'OGRH') $return = 'phantom-lancer'; 
     if ($hid == 'E02F') $return = 'phoenix'; 
     if ($hid == 'N00R') $return = 'pit-lord'; 
     if ($hid == 'N01V') $return = 'priestess-of-the-moon'; 
     if ($hid == 'EMNS') $return = 'prophet'; 
     if ($hid == 'UC01') $return = 'queen-of-pain'; 
     if ($hid == 'H001') $return = 'rogue-knight'; 
     if ($hid == 'H00Q') $return = 'sacred-warrior'; 
     if ($hid == 'U00K') $return = 'sand-king'; 
     if ($hid == 'E02H') $return = 'shadow-demon'; 
     if ($hid == 'NFIR') $return = 'shadow-fiend'; 
     if ($hid == 'N01W') $return = 'shadow-priest'; 
     if ($hid == 'ORKN') $return = 'shadow-shaman'; 
     if ($hid == 'N01A') $return = 'silencer'; 
     if ($hid == 'NC00') $return = 'skeleton-king'; 
     if ($hid == 'H004') $return = 'slayer'; 
     if ($hid == 'UC91') $return = 'slithereen-guard'; 
     if ($hid == 'EEVI') $return = 'soul-keeper'; 
     if ($hid == 'E01B') $return = 'spectre'; 
     if ($hid == 'O00J') $return = 'spiritbreaker'; 
     if ($hid == 'HC92') $return = 'stealth-assassin'; 
     if ($hid == 'UCRL') $return = 'stone-giant'; 
     if ($hid == 'H00S') $return = 'storm-spirit'; 
     if ($hid == 'O015') $return = 'tauren-chieftain'; 
     if ($hid == 'E01Y') $return = 'templar-assassin'; 
     if ($hid == 'OFAR') $return = 'tidehunter'; 
     if ($hid == 'NTIN') $return = 'tinker'; 
     if ($hid == 'EKEE') $return = 'tormented-soul'; 
     if ($hid == 'HAMG') $return = 'treant-protector'; 
     if ($hid == 'N016') $return = 'troll-warlord'; 
     if ($hid == 'E02I') $return = 'tuskarr'; 
     if ($hid == 'E00P') $return = 'twin-head-dragon'; 
     if ($hid == 'H00R') $return = 'undying'; 
     if ($hid == 'HUTH') $return = 'ursa-warrior'; 
     if ($hid == 'HVWD') $return = 'vengeful-spirit'; 
     if ($hid == 'EC57') $return = 'venomancer'; 
     if ($hid == 'E01C') $return = 'warlock'; 
     if ($hid == 'N0EG') $return = 'windrunner'; 
     if ($hid == 'E01A') $return = 'witch-doctor';
	 if ($hid == 'E02X') $return = 'grand-magus';
 
  return $return;
  }
  
  function limit_words($string, $word_limit, $dots = 1)
{
    $string = (strip_tags($string));
	$string = str_replace("\r\n","",$string);
	$string = str_replace("&nbsp;","",$string);
	$string = str_replace("\n","",$string);
	//$string = preg_replace('/\<script>(.*?)</script>/si', '', $string);
	//while( strstr($string, "\t") ) $string = str_replace("\t","",$string); 
    $words = explode(" ",$string);
	if ($dots==1) $add = ""; else $add = "";
    return implode(" ",array_splice($words,0,$word_limit)).$add;
}
  
  function generate_password($pass, $salt = "0#'open73^743_stats_923^$&_") {
  $password = substr(md5($salt),0,3).sha1($pass.$salt).substr(md5($pass),0,3).substr(sha1($salt),0,3);
  return $password;
  }
  
  function generate_hash($length=22, $alphnum = 0 ) {
 $alphanum = '0123456789qwertyuiopasdfghjklzxcvbnmqwertyuiopasdfghjklzxcvbnmqwertyuiopasdfghjklzxcvbnm0123456789';
 if ($alphnum==1) $alphanum.="!@#$^()_|";
 $rand = strtolower(substr(str_shuffle($alphanum), 0, $length));
 return $rand;
 }

 function write_value_of($var,$oldval,$newval, $file)
{
 $contents = file_get_contents($file);
 $regex = '~\\'.$var.'\s+=\s+\''.$oldval.'\';~is';
 $contents = preg_replace($regex, "$var = '$newval';", $contents);
 file_put_contents($file, $contents);
}
  if ($OS_INSTALLED == 0 AND !file_exists("install/install.php") ) { os_trigger_error("Missing install folder."); }
  if ($OS_INSTALLED == 0) { require_once("install/install.php"); die; } else {
   if (file_exists("install") )             os_trigger_error("Please delete <b>install/</b> folder");
   if (file_exists("install/install.php") ) os_trigger_error("Please delete or rename <b>install.php</b>");
   if (file_exists("sql_data.sql") ) os_trigger_error("Please delete or rename <b>sql_data.sql</b>");
   if (file_exists("sql_heroes_items.sql") ) os_trigger_error("Please delete or rename <b>sql_heroes_items.sql</b>");
}

	function get_value_of($name, $file = "../config.php")
    {
    $lines = file($file);
	 $val = array();
     foreach (array_values($lines) AS $line)
     {
	   if (strstr($line,"="))
	   {
       list($key, $val) = explode('=', trim($line) );
       if (trim($key) == $name)
          {$val = str_replace(";","",$val); $val = str_replace("'","",$val); 
		  $val = str_replace('"',"",$val);  return $val;}
       }
     }
     return false;
  }

function removeDoubleSpaces($text) {
   while ( strstr($text, "\t") ) $text = str_replace("\t", " ", $text);
   while ( strstr($text, "  ") ) $text = str_replace("  ", " ", $text);
   $text = trim($text);
   return $text;
}

function AutoKeywords( $text ) {
   $text = str_replace(" ", ", ", $text);
   $text = strtolower( os_strip_quotes($text));
   return $text;
}

//Very basic link check
function is_valid_url( $url ) {
   if (function_exists('FILTER_VALIDATE_URL') ) {
     if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) return false;
     else return true;
   }
   else return preg_match('|^http(s)?://[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $url);
}

function LinkUserProfile( $username, $website, $class = "") {
  if (!empty($website) AND is_valid_url($website) ) {
  ?>
  <a <?=$class?> href="<?=$website?>"><?=$username?></a>
  <?php
  } else {
  echo $username;
  }
}

function DisplayGameFilter($year = "", $query = "games" ) {
?>
<form action="" method="get">
<input type="hidden" name="<?=$query?>" />
<select name="m">

 <option value=""></option>
<?php
  $ThisMonth = date('m', time() );
  for ( $counter = 1; $counter <= 12; $counter += 1) {
  $sel = "";
  if ( !isset($_GET["m"]) AND $ThisMonth == $counter )  $sel = 'selected="selected"'; else
  if ( isset($_GET["m"])  AND $_GET["m"] == $counter )  $sel = 'selected="selected"';  

   ?>
   <option <?=$sel?> value="<?=$counter?>"><?=getMonthName($counter)?></option>
   <?php
   }
?>
</select>

<select name="y">
  <?php
  $ThisYear =  date( "Y", time());
  $GoalYear = $year;
  for ( $counter = $ThisYear; $counter >= $GoalYear; $counter -= 1) {
  $sel = "";
  if ( isset($_GET["y"]) AND $_GET["y"] == $counter ) $sel = 'selected="selected"'; else 
  //if ( $year == $counter )  $sel = 'selected="selected"';
   ?>
   <option <?=$sel?> value="<?=$counter?>"><?=($counter)?></option>
   <?php
   }
?>
</select>
<input type="submit" value="Submit" class="menuButtons" />
</form>
<?php
}

function AutoLinkShort($text, $target = "" )
{
   $pattern  = '#\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
   $callback = create_function('$matches', '
       $url       = array_shift($matches);
       $url_parts = parse_url($url);

       $text = parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH);
       $text = preg_replace("/^www./", "", $text);

       $last = -(strlen(strrchr($text, "/"))) + 1;
       if ($last < 0) {
           $text = substr($text, 0, $last) . "&hellip;";
       }

       return sprintf(\'<a '.$target.' rel="nofollow" href="%s">%s</a>\', $url, $text);
   ');

   return preg_replace_callback($pattern, $callback, $text);
}

function AutoLinkFull($text, $target = "") {
  $pattern = "/(((http[s]?:\/\/)|(www\.))(([a-z][-a-z0-9]+\.)?[a-z][-a-z0-9]+\.[a-z]+(\.[a-z]{2,2})?)\/?[a-z0-9.,_\/~#&=;%+?-]+[a-z0-9\/#=?]{1,1})/is";
  $text = preg_replace($pattern, " <a ".$target." href='$1'>$1</a>", $text);
  // fix URLs without protocols
  $text = preg_replace("/href='www/", "href='http://www", $text);
  return $text;
}

function RemoveLinks($text, $replaceTxt) {
  $pattern = "/(((http[s]?:\/\/)|(www\.))(([a-z][-a-z0-9]+\.)?[a-z][-a-z0-9]+\.[a-z]+(\.[a-z]{2,2})?)\/?[a-z0-9.,_\/~#&=;%+?-]+[a-z0-9\/#=?]{1,1})/is";
  $text = preg_replace($pattern, "<i>".$replaceTxt."</i>", $text);
  // fix URLs without protocols
  //$text = preg_replace("/href='www/", "href='http://www", $text);
  return $text;
}

function GetHeroIcon() {
  if ( isset($_GET["h"]) AND file_exists("img/heroes/".strtoupper($_GET["h"]).".gif" ) ) {
   ?>
   <img class="imgvalign" src="img/heroes/<?=strtoupper($_GET["h"])?>.gif" alt="*" width="24" height="24" />
   <?php
  }
}

function os_get_footer() {
  include('themes/'.OS_THEMES_DIR.'/footer.php');
}

function os_commentForm( $style = 'style="width: 420px; height: 120px;"') {
  os_comment_form();
  
  if ( !defined('OS_ALWAYS_ENABLE_TEXTAREA') ) $disabled = 'disabled'; else $disabled = "";
  ?>
  <textarea <?php if ( !is_logged() ) { echo $disabled; } ?>  id="text_message" <?=$style?> name="post_comment"></textarea>
  <?php
  os_after_comment_form();
}

function os_canComment() {
   
   if ( is_logged() ) return true;
   
   if (!is_logged() ) {
	  if ( defined('OS_ALWAYS_ENABLE_TEXTAREA') ) return true;
	  else return false;
   }
   
}

function os_offset( $numrows, $result_per_page = 10 ) {

              $rowsperpage = $result_per_page;
              $totalpages = ceil($numrows / $rowsperpage);
              if (isset($_GET['page']) && is_numeric($_GET['page'])) {
                  $currentpage = (int)$_GET['page'];
              } else {
                  $currentpage = 1;
              }
              if ($currentpage > $totalpages) {
                  $currentpage = $totalpages;
              }
              if ($currentpage < 1) {
                  $currentpage = 1;
              }
              if ($totalpages <= 1) {
                  $totalpages = 1;
              }

              $offset = ($currentpage - 1) * $rowsperpage;
			  
	return $offset;
}

function os_pagination( $numrows, $result_per_page = 10, $MaxPaginationLinks = 5, $draw_pagination = 1, $prefix="", $SHOW_TOTALS = "" ) {

  global $lang;
  $start = "";
  
  if ( isset($_GET["action"]) ) $start="?action=".safeEscape($_GET["action"]);
  
              $rowsperpage = $result_per_page;
              $totalpages = ceil($numrows / $rowsperpage);
              if (isset($_GET['page']) && is_numeric($_GET['page'])) {
                  $currentpage = (int)$_GET['page'];
              } else {
                  $currentpage = 1;
              }
              if ($currentpage > $totalpages) {
                  $currentpage = $totalpages;
              }
              if ($currentpage < 1) {
                  $currentpage = 1;
              }
              if ($totalpages <= 1) {
                  $totalpages = 1;
              }

              $offset = ($currentpage - 1) * $rowsperpage;
              if (isset($_GET['page']) AND is_numeric($_GET['page'])){
                          $current_page = safeEscape($_GET['page']);
                          }

                          if (!isset($current_page)) {
                              $current_page = 1;
                          }
              if (!isset($MaxPaginationLinks) ) $range = 5;
			  else  $range = $MaxPaginationLinks;
			  
              if ($range >= $totalpages) {
                  $range = $totalpages;
              }
			  
			  if ($current_page > $totalpages) {$current_page = $totalpages;}
			  
if (isset($draw_pagination) AND $draw_pagination == 1 AND $totalpages>=2) {
?>
	  <div class="pagination2"> 
	   <?php
              if ($currentpage > 1) {
                  ?><a class="button orange" href="<?=OS_HOME?><?=$start.$prefix?>"><span>&laquo;</span></a><?php
                  $prevpage = $currentpage - 1;
                  ?><a class="button orange" href="<?=OS_HOME?><?=$start.$prefix?>&amp;page=<?=$prevpage?>"><span><</span></a><?php
              }
              for ($x = ($currentpage - $range); $x < (($currentpage + $range) + 1); $x++) {
                  if (($x > 0) && ($x <= $totalpages)) {
                      if ($x == $currentpage) {
                         ?>
					  <a class="button orange" href="javascript:;"><span class="active"><?=$x?></span></a><?php
                      } else {
                          ?>
					  <a class="button orange" href="<?=OS_HOME?><?=$start.$prefix?>&amp;page=<?=$x?>"><span><?=$x?></span></a><?php
                      }
                  }
              }
              if ($currentpage != $totalpages) {
                  $nextpage = $currentpage + 1;
                 ?>
				 <a class="button orange" href="<?=OS_HOME?><?=$start.$prefix?>&amp;page=<?=$nextpage?>"><span>></span></a>
				 
				 <a class="button orange" href="<?=OS_HOME?><?=$start.$prefix?>&amp;page=<?=$totalpages?>"><span><?=$totalpages?></span></a><?php
              }
             ?>   
			<?php if (isset($SHOW_TOTALS) AND !empty($SHOW_TOTALS) ) { ?>
			 &nbsp;
			 <span class="totals"><?=$lang["page"]?> <b><?=$current_page?></b> <?=$lang["pageof"]?> <?=$totalpages?> 
			 (<?=$numrows?> <?=$lang["total"]?>)
			 </span>
			 <?php } ?>
			 </div>
			 <?php
    }
}

function os_trigger_error( $text = "" ) {
  //include('themes/'.OS_THEMES_DIR.'/header.php');
  ?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="content-style-type" content="text/css" />
	<meta name="author" content="Ivan Antonijevic" />
	<meta name="rating" content="Safe For Kids" />
</head>

  <body>
     <div style="width:680px; height: 200px; border: 18px solid #ccc; text-align:center; margin: 0 auto; margin-top: 64px; padding-top: 16px; color: #5F5F5F; font-family: 'courier new', 'times new roman', monospace; font-size: 14px; border-radius: 10px;">
	   <?=$text?>
	 </div>
  </body>
</html>
<?php
die;
}

function PluginEnabled( $plugin ) {
  if ( !empty($plugin) ) {
  
    if ( isset($_GET["enable"]) AND $_GET["enable"] == $plugin ) return true;
	else 
	  return false;
		
  }
}

function PluginDisabled( $plugin ) {
  if ( !empty($plugin) ) {
  
    if ( isset($_GET["disable"]) AND $_GET["disable"] == $plugin ) return true;
	else 
	  return false;
		
  }
}

function OS_is_single() {
   if (isset($_GET["post_id"]) AND is_numeric($_GET["post_id"]) ) return true;
   else return false;
}

function OS_post_edit_link( $id ) {
   if ( OS_is_admin() AND $id>=1 ) {
   ?>
   <a href="<?=OS_HOME?>adm/?posts&amp;edit=<?=$id?>">edit entry</a> | 
   <?php
   }
}

function OS_top_page() {
   if ( isset($_GET["top"]) ) return true;
}

function OS_games_page() {
   if ( isset($_GET["games"]) ) return true;
}

function OS_heroes_page() {
   if ( isset($_GET["heroes"]) ) return true;
}

function OS_items_page() {
   if ( isset($_GET["items"]) ) return true;
}

function OS_bans_page() {
   if ( isset($_GET["bans"]) ) return true;
}

function OS_admins_page() {
   if ( isset($_GET["admins"]) ) return true;
}

function OS_profile_page() {
   if ( isset($_GET["profile"]) ) return true;
}

function OS_login_page() {
   if ( isset($_GET["login"]) ) return true;
}

function OS_single_user() {
   if ( isset($_GET["u"]) AND is_numeric($_GET["u"]) AND !isset($_GET["action"]) ) return true;
}

function OS_members_page() {
   if ( isset($_GET["members"]) ) return true;
}

function OS_single_game_page() {
   if ( isset($_GET["game"]) ) return true;
}

//short function for $_GET["action"]
function OS_GetAction( $str = "" ) {
   
   if ( empty($str) AND isset( $_GET["action"] ) AND $_GET["action"] == "" ) return true;
     else 
     if ( !empty($str) AND isset($_GET["action"]) AND $_GET["action"] == $str ) return true;
   else
   return false;
   
}
//Get user id of logged user
function OS_GetUserID() {
   if ( isset($_SESSION["user_id"]) AND is_numeric($_SESSION["user_id"]) )
   return $_SESSION["user_id"];
}
//Get user email of logged user
function OS_GetUserEmail() {
   if ( isset($_SESSION["email"]) AND 
      preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,6}$/i", $_SESSION["email"]) 
      )
	  return $_SESSION["email"];
}

function OS_Number( $num ) {
   $raw = filter_var( $num, FILTER_SANITIZE_NUMBER_INT);
   return $raw;
}

function OS_Post_Link( $post_id ) {
   $link = OS_HOME.'?post_id='.(int)$post_id;
   return $link;
}

function OS_show_errors( $errors ) {
  if ( isset($errors) AND !empty($errors) ) {
  ?>
  <div class="padTop padBottom padLeft"><?=$errors?></div>
  <?php
  }
}

//Check if home page
function OS_is_home_page() {

 if ( !$_GET OR ( isset($_GET["post_id"])  OR (!isset($_GET["u"]) AND isset($_GET["page"])  ) ) 
   AND !isset($_GET["game"]) 
   AND !isset($_GET["games"]) 
   AND !isset($_GET["heroes"])  
   AND !isset($_GET["items"])
   AND !isset($_GET["warn"])
   AND !isset($_GET["safelist"])
   AND !isset($_GET["bans"])
   AND !isset($_GET["ban_appeals"])
   AND !isset($_GET["ban_reports"])
   AND !isset($_GET["admins"])
   AND !isset($_GET["about_us"])
   AND !isset($_GET["top"])
   AND !isset($_GET["members"])
   AND !isset($_GET["guides"])
   AND !isset($_GET["action"])
                            ) 
   {
    return true;
   } else return false;

}

function OS_GetGameState($PrivOrPub = '', $LangPub = 'PUB', $LangPriv = 'PRIV') {
    if ( $PrivOrPub == 17 ) $state = $LangPub;
	else  $state = $LangPriv;
	
	return $state;
}

function OS_RemoteFileExists($url) {

 $ch = curl_init($url);

curl_setopt($ch, CURLOPT_NOBODY, true);
curl_exec($ch);
$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
return $retcode;
}

function OS_ShowPagination( $total, $results_show = 10,  $show = 1 ) {
  $numrows = $total;
  $result_per_page = $results_show;
  $draw_pagination = $show;
  
  include('inc/pagination.php');
}

function OS_is_banned_player( $player = "" ) {
  if ( !empty( $player) ) return true;
}

function aasort (&$array, $key) {
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];
    }
    asort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}
//Another version
function CSort( &$mdarray, $field) {
foreach ($mdarray as $key => $row) {
    $dates[$key]  = $row[$field]; 
}

array_multisort($dates, SORT_DESC, $mdarray);
}

function OS_ShowUserFlag( $letter, $country = "", $w = '130', $imgw='21',  $imgh='15' ) {
  	if (isset( $letter ) AND !empty( $letter ) ) { ?>
	<img <?=ShowToolTip($country, OS_HOME.'img/flags/'.$letter.'.gif', $w, $imgw, $imgh)?> class="imgvalign" width="21" height="15" src="<?=OS_HOME?>img/flags/<?=$letter?>.gif" alt="<?=$country?>" />
	<?php } 
}

function OS_ShowItem( $itemID, $itemname, $icon = "", $w = '100', $imgw = '64', $imgh = '64') {
  if ( !empty($icon) AND $icon!='empty.gif') {
  ?>
  <a href="<?=OS_HOME?>?item=<?=$itemID?>"><img <?=ShowToolTip("<div>".$itemname."</div>", OS_HOME.'img/items/'.$icon, $w, $imgw, $imgh)?> src="<?=OS_HOME?>img/items/<?=$icon?>" alt="<?=$itemID?>" width="32" height="32" /></a>
  <?php
  } else {
  ?>
  <img src="<?=OS_HOME?>img/items/<?=$icon?>" alt="<?=$itemID?>" width="32" height="32" />
  <?php
  }
}

function OS_ShowHero($heroID, $description = '', $icon, $w = '100', $imgw='64', $imgh='64' ) {
  ?>
  <a href="<?=OS_HOME?>?hero=<?=$heroID?>"><img <?=ShowToolTip("<div>".$description."</div>", OS_HOME.'img/heroes/'.$icon, $w, $imgw, $imgh)?> src="<?=OS_HOME?>img/heroes/<?=$icon?>" alt="hero" width="48" height="48" /></a>
  <?php
}

function OS_EditGame($gid) {
   if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
    <a href="<?=OS_HOME?>adm/?games&amp;game_id=<?=(int) $gid?>"><img src="<?=OS_HOME?>/adm/edit.png" alt="*" width="16" height="16" /></a>
<?php }
}

function OS_SortTopPlayers( $fieldName = 'sort' ) {
  global $lang;
  ?>
    <form action="" method="get">
    <?=$lang["sortby"]?> <input type="hidden" name="top" />
	<select name="<?=$fieldName?>">
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "score" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="score"><?=$lang["score"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "games" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="games"><?=$lang["games"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "wins" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="wins"><?=$lang["wins"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "losses" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="losses"><?=$lang["losses"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "draw" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="draw"><?=$lang["draw"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "kills" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="kills"><?=$lang["kills"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "deaths" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="deaths"><?=$lang["deaths"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "assists" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="assists"><?=$lang["assists"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "ck" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="ck"><?=$lang["ck"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "cd" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="cd"><?=$lang["cd"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "nk" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="nk"><?=$lang["nk"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "player_name" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="player_name"><?=$lang["player_name"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "leaves" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="leaves"><?=$lang["leaves"]?></option>
<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "streak" ) $sel = 'selected="selected"'; else $sel = ''; ?>
	  <option <?=$sel?> value="streak"><?=$lang["streak"]?></option>
	</select>
	<input class="menuButtons" type="submit" value="<?=$lang["submit"]?>" />
	 <?=OS_ComparePlayers( 'link' )?>
	 <?=LettersLink("top", "L")?>
  </form>
  <?php
}

function OS_TopUser($id, $player) {
?><a href="<?=OS_HOME?>?u=<?=$id?>"><?=$player?></a><?php
}

function OS_SingleGameUser($id, $fullname, $name, $bestPlayer = '', $winnerIcon ='best.png' ) {
global $lang;
$text = "<div><b>".$lang["best_player"]."</b> ".$fullname."</div>";
?>
  <a href="<?=OS_HOME?>?u=<?=$id?>"><?=$fullname?></a> 
  <?php if (strtolower($bestPlayer) == strtolower($name) ) { ?><img <?=ShowToolTip($text, OS_HOME.'img/'.$winnerIcon.'', 160, 50, 50)?> src="<?=OS_HOME?>img/<?=$winnerIcon?>" class="imgvalign" width="32" height="32" /> <?php } ?>
<?php
}


function OS_IsUserGameAdmin( $admin = 0, $text = 'Admin', $w = '130', $imgw='32',  $imgh='32' ) {
    if ( $admin >= 1 ) {
    ?>
	<img <?=ShowToolTip($text, OS_HOME.'img/winner.png', $w, $imgw, $imgh)?> class="imgvalign" width="16" height="16" src="<?=OS_HOME?>img/winner.png" alt="Admin" />
	<?php
	}
}

function OS_IsUserGameSafe( $safe = 0, $text = 'Safelist', $w = '130', $imgw='32',  $imgh='32' ) {
    if ( $safe >= 1 ) {
    ?>
	<img <?=ShowToolTip($text, OS_HOME.'img/safelist.png', $w, $imgw, $imgh)?> class="imgvalign" width="16" height="16" src="<?=OS_HOME?>img/safelist.png" alt="safelist" />
	<?php
	}
}

function OS_IsUserGameBanned( $banned = 0, $text = 'Banned', $w = '130', $imgw='24',  $imgh='24' ) {
    if ( $banned >= 1 ) {
    ?>
	<img <?=ShowToolTip($text, OS_HOME.'img/banned.png', $w, $imgw, $imgh)?> class="imgvalign" width="14" height="14" src="<?=OS_HOME?>img/banned.png" alt="banned" />
	<?php
	}
}

function OS_IsUserGameLeaver( $leaver = 0, $text = 'Leaver', $AlwaysDisplay = 0, $w = '130', $imgw='32',  $imgh='32' ) {
    if ( $leaver >= 1 OR $AlwaysDisplay == 1) {
    ?>
	<img <?=ShowToolTip($text, OS_HOME.'img/disc.png', $w, $imgw, $imgh)?> class="imgvalign" width="14" height="14" src="<?=OS_HOME?>img/disc.png" alt="leaver" />
	<?php
	}
}

function OS_IsUserGameWarned( $warn = 0, $expire = '', $text = 'Warned', $w = '180', $imgw='26',  $imgh='26' ) {
    if ( $warn >= 1 ) {
	global $DateFormat;
	global $lang;
	if ( !empty($expire) ) 
	$text.='<div><b>'.$lang["expire"] .':</b> '.date($DateFormat, strtotime($expire) ).'</div>';
    ?>
	<img <?=ShowToolTip($text, OS_HOME.'img/warn.png', $w, $imgw, $imgh)?> class="imgvalign" width="16" height="16" src="<?=OS_HOME?>img/warn.png" alt="Warned" />
	<?php
	}
}

function OS_ComparePlayers( $type = 0, $playerID = 0 ) {
   global $ComparePlayers; global $ComparePlayersData; global $lang; global $MaxPlayersToCompare;
   
if ( $ComparePlayers == 1 ) {

   if ( $type=='link' ) {
     if (isset($_GET["compare"])) { ?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed">
	 <div class="comparePlayersList">
	 <a class="menuButtons" href="<?=OS_HOME?>?top"><?=$lang["compare_back"]?></a>
	 <a class="menuButtons" href="javascript:;" onclick="showhide('compare_list')" ><?=$lang["compare_list"]?> <?php if ( isset($ComparePlayersData) AND !empty($ComparePlayersData) ) { ?>
	 <?=count($ComparePlayersData)?>/<?=$MaxPlayersToCompare?><?php } ?></a>
	 <div id="compare_list">
	 <?php
	 if ( isset($ComparePlayersData) AND !empty($ComparePlayersData) ) {
	 ?><table><?php	 $counter = 0;
		foreach ($ComparePlayersData as $Player) { 
		$counter++;
		?>
		<tr>
		  <td width="24"><?=$counter?></td>
		  <td width="175"><div><a href="<?=OS_HOME?>?u=<?=$Player["id"]?>"><?=$Player["player"]?></a></div></td>
		  <td><a href="javascript:;" onclick="if( confirm('<?=$lang["compare_remove_player"]?>') ) { location.href='<?=OS_HOME?>?top&amp;compare&amp;remove=<?=$Player["id"]?>' }">&times;</a></td>
		</tr>
		<?php
		}
	  ?></table>
	  <?php if ($counter>1) { ?>
	  <div>
	  <a class="menuButtons" href="<?=OS_HOME?>?compare_players"><?=$lang["compare_players"]?></a>
	  <a class="menuButtons" href="<?=OS_HOME?>?top&amp;compare&amp;clear_list"><?=$lang["compare_clear"]?></a>
	  </div>
	  <?php } ?>
	  <?php
	 } else { ?><?=$lang["compare_list_empty"] ?><?php }
	 ?>
	 </div>
	 </div>
     </div>
    </div>
   </div>
  </div>
</div>
	 <?php } else { ?>
	 <?php if ( isset($_GET["sort"]) ) $sort = "&amp;sort=".safeEscape($_GET["sort"]); else $sort =""; ?>
	 <span class="comparePlayersList"><a class="menuButtons compareButton" href="<?=OS_HOME?>?top&amp;compare<?=$sort?>"><?=$lang["compare_compare"] ?></a></span>
	 <?php } ?>
     <?php
     }
   
    if ( $type=='form_start' ) {
	  if (isset($_GET["compare"])) { ?><form action="" method="post"><?php }
	}
	
	if ( $type=='checkbox' ) {
	  if (isset($_GET["compare"])) { ?><input type="checkbox" name="compare[]" value="<?=$playerID?>" /><?php } 
	}
	
	if ( $type=='submit' ) {
	  if (isset($_GET["compare"])) { ?>
	  <input type="submit" value="<?=$lang["compare_add"]?>" name="compare_list_add" class="menuButtons" />
	  <input type="submit" value="<?=$lang["compare_clear"]?>" name="clear_compare_list" class="menuButtons" />
	  <?php if (isset($_SESSION["compare_list"]) AND !empty($_SESSION["compare_list"]) ) { ?>
	  <input type="submit" value="<?=$lang["compare_players"]?>" name="compare_players" class="menuButtons" />
	  <?php } ?>
	  </form><?php }
	}

  }

}

function OS_CheckValue( $bestPlayer = "", $player = "." ) {
   if ( strtolower($bestPlayer) == strtolower($player)) {
   ?><img src="<?=OS_HOME?>img/winner.png" alt="" width="16" height="16" class="imgvalign" /><?php
   }
}

function LettersLink($page = "bans", $qry = "L", $letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ") {
    global $HomeTitle;
	$countAlph = strlen($letters);
	$return = "";
	for ($i = 0; $i <= $countAlph; $i++) {
	$abc = substr($letters,$i,1);
	if ( !empty($abc) ) {
	   if ( (isset($_GET[$page]) AND isset($_GET[$qry]) AND $_GET[$qry] != $abc) OR !isset($_GET[$qry]) )
	   $return.='<a href="'.OS_HOME.'?'.$page.'&amp;'.$qry.'='.$abc.'" class="abc menuButtons">'.strtoupper($abc).'</a> ';
	   else 
	   $return.='<b class="abc-current">'.strtoupper($abc).'</b>';
	   }
	}
	return $return;
}

function OS_DBConnect() {
  $db = new db("mysql:host=".OSDB_SERVER.";dbname=".OSDB_DATABASE."", OSDB_USERNAME, OSDB_PASSWORD);
  return $db;
}

function OS_HeroIcon($hid = "", $w='32', $h = '32') {
  if ( !empty($hid) ) {
  ?>
  <img src="img/heroes/<?=$hid?>.gif" alt="<?=$hid?>" width="<?=$w?>" height="<?=$h?>" class="imgvalign" />
  <?php
  }
}

function OS_UserHeroHistoryLink( $userID, $heroID,  $title = "", $w = '32', $h = '32' ) {
 ?>
 <a title="<?=$title?>" href="<?=OS_HOME?>?games&amp;uid=<?=$userID?>&amp;h=<?=$heroID?>"><?=OS_HeroIcon( $heroID, $w, $h)?></a>
 <?php
}
?>