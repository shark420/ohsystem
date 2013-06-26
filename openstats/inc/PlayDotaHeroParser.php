<?php
    if (!function_exists('curl_init')) {
	echo "CURL function is disabled. Please check your php configuration.";
	}
	else {
	if (strstr($_SERVER['REQUEST_URI'], basename(__FILE__) ) )
    { header('HTTP/1.1 404 Not Found'); die; }
	
function get_data($url, $timeout = 8) {
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6 (.NET CLR 3.5.30729)');
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
 }
 
$DIR  = "inc/cache/";
$DIR2 = "inc/cache/pdheroes/";
if (!file_exists($DIR) )  { mkdir($DIR."/");  chmod($DIR."/",0777);  file_put_contents($DIR.  "index.html", ""); }
if (!file_exists($DIR2) ) { mkdir($DIR2."/"); chmod($DIR2."/",0777); file_put_contents($DIR2. "index.html", ""); }

    $gURL = "http://www.playdota.com/heroes/$pdHero";
    $content = get_data($gURL);
	  
$dom = new DOMDocument();
@$dom->loadHTML($content);
$xpath = new DOMXPath($dom);

//  $_imgs[0] = Hero icon;
//  $_imgs[1] = Hero name;
//  $_imgs[2] = STR;
//  $_imgs[3] = AGI;
//  $_imgs[4] = INT;
//  $_imgs[5] = Animated Hero image;
//  $_imgs[6] = SKILL 1;
//  $_imgs[7] = SKILL 2;
//  $_imgs[8] = SKILL 3;
//  $_imgs[9] = SKILL 4 - ULTI;

//  $_notes[0] = SKILL 1 NOTES
//  $_notes[1] = SKILL 2 NOTES
//  $_notes[2] = SKILL 3 NOTES
//  $_notes[3] = SKILL 4 NOTES

$c = 0;
$_imgs        = array();
$_notes       = array();
$_atr         = array();
$_intro       = array();
$_skills_desc = array();
$_adv_stats = "";
//All images
$entries = $xpath->query("//div[@class='hLeft']//img");
foreach($entries as $e)
  {$_imgs[$c] =  $e->getAttribute("src"); $c++;}
  
  if (!empty($_imgs[1]) ) {
  
 $c = 0;
//Attributes
$entries = $xpath->query("//div[@class='hLeft']//li");
foreach($entries as $e)
   {$_atr[$c] = $e->textContent; $c++;}
   
//Hero Name
$entries = $xpath->query("//h1[@class='class']");
foreach($entries as $e)
   $Hero_NAME =  $e->textContent;
   
  	$HomeTitle = ($Hero_NAME);
	$HomeDesc = strip_quotes($Hero_NAME);
	$HomeKeywords = strtolower( strip_quotes($Hero_NAME)).','.$HomeKeywords;

 $c = 0;   
//Skills Descr
$entries = $xpath->query("//div[@class='hLeft']//p");
foreach($entries as $e)
  {$_skills_desc[$c] = $e->textContent; $c++;}
  
//Advanced stats
$entries = $xpath->query("//ul[@class='adv']//li");
foreach($entries as $e)
   {$_adv_stats.= $e->textContent . '<br />';}

$c = 0;
//Hero Introduction
$entries = $xpath->query("//div[@id='info']//p");
foreach($entries as $e)
   {$_intro[$c] = $e->textContent; $c++;}

$c = 0;   
//ADV Skills
$entries = $xpath->query("//div[@class='notes']");
foreach($entries as $e)
   {$_notes[$c]= $e->textContent; $c++;}
   
$c = 0;   
//Skilla name
$entries = $xpath->query("//div[@class='hLeft']//h2");
foreach($entries as $e)
   {$_skills_name[$c]= $e->textContent; $c++;}

$_adv_stats = str_replace(array(
"Affiliation:",
"Attack Animation:",
"Damage:",
"Casting Animation:",
"Armor:",
"Base Attack Time:",
"Movespeed:",
"Missile Speed:",
"Attack Range:", 
"Sight Range:"), 

array(
"<span class='PlayDotaAdvStats'>Affiliation:</span></td><td>",
"</td></tr><tr class='row'><td class='padLeft' width='150'><span class='PlayDotaAdvStats'>Attack Animation:</span></td><td>",
"</td></tr><tr class='row'><td class='padLeft' width='150'><span class='PlayDotaAdvStats'>Damage:</span></td><td>",
"</td></tr><tr class='row'><td class='padLeft' width='150'><span class='PlayDotaAdvStats'>Casting Animation:</span></td><td>",
"</td></tr><tr class='row'><td class='padLeft' width='150'><span class='PlayDotaAdvStats'>Armor:</span></td><td>",
"</td></tr><tr class='row'><td class='padLeft' width='150'><span class='PlayDotaAdvStats'>Base Attack Time:</span></td><td>",
"</td></tr><tr class='row'><td class='padLeft' width='150'><span class='PlayDotaAdvStats'>Movespeed:</span></td><td>",
"</td></tr><tr class='row'><td class='padLeft' width='150'><span class='PlayDotaAdvStats'>Affiliation:</span></td><td>",
"</td></tr><tr class='row'><td class='padLeft' width='150'><span class='PlayDotaAdvStats'>Attack Range:</span></td><td>", 
"</td></tr><tr class='row'><td class='padLeft' width='150'><span class='PlayDotaAdvStats'>Sight Range:</span></td><td>",
), $_adv_stats);  

$_notes[0] = str_replace("Notes","<span class='PlayDotaNotes'>Notes</span>",$_notes[0]); 
$_notes[1] = str_replace("Notes","<span class='PlayDotaNotes'>Notes</span>",$_notes[1]); 
$_notes[2] = str_replace("Notes","<span class='PlayDotaNotes'>Notes</span>",$_notes[2]); 
$_notes[3] = str_replace("Notes","<span class='PlayDotaNotes'>Notes</span>",$_notes[3]); 

$_skills_name[0] = str_replace($_skills_name[0],"<span class='PlayDotaSkillName'>$_skills_name[0]</span>",$_skills_name[0]); 
$_skills_name[1] = str_replace($_skills_name[1],"<span class='PlayDotaSkillName'>$_skills_name[1]</span>",$_skills_name[1]); 
$_skills_name[2] = str_replace($_skills_name[2],"<span class='PlayDotaSkillName'>$_skills_name[2]</span>",$_skills_name[2]); 
$_skills_name[3] = str_replace($_skills_name[3],"<span class='PlayDotaSkillName'>$_skills_name[3]</span>",$_skills_name[3]); 

$HeroHTML = '
<div align="center" style="width: 600px; margin: 0px auto;">

	<div class="page-header">
          <h1>'.$Hero_NAME.'</h1>
    </div>
	<div class="PlayDotaHeaderWrapper">
	<img class="PlayDotaHeroImg3" width="120" height="120" src="'.$_imgs[5].'" alt="Hero Animated" />
	<img class="PlayDotaHeroImg2" src="'.$_imgs[1].'" alt="'.$hero.'"  /> 
	<img class="PlayDotaHeroImg" src="'.$_imgs[0].'" alt="Hero" width="64" height="64" /> 
	
	</div>
	<table style="width: 600px;">
	    <tr>
		   <th></th>
		   <th>Stats</th>
		 </tr>
	  	<tr>
			<td width = "35" class="padTop"><img class="imgvalign" width="32" height="32" src="'.$_imgs[2].'" alt="" border=0 /></td>
			<td class="PDAtrName padTop">'.str_replace("Strength","<b>Strength:</b>",$_atr[0]).'</td>
		</tr>
		<tr>
			<td><img class="imgvalign" width="32" height="32" src="'.$_imgs[3].'" alt="" border=0 /></td>
			<td class="PDAtrName">'.str_replace("Agility","<b>Agility:</b>",$_atr[1]).'</td>
		</tr>
		<tr>
			<td><img class="imgvalign" width="32" height="32" src="'.$_imgs[4].'" alt="" border=0 /></td>
			<td class="PDAtrName">'.str_replace("Intelligence","<b>Intelligence:</b>",$_atr[2]).'</td>
		 </tr>
	</table>
   <!--<table class="PDHeroTAble">
         <tr>
             <td>
			     <table>
				   <tr>
				     <td><div align="center">'.$hero.'</div></td>
				  </tr>
				 </table>
				 <table>
				  <tr>
				      <td class="padLeft" width="80"><img src="'.$_imgs[0].'" alt="" border=0 /></td>
					  <td class="padLeft" width="180" valign="top"><img src="'.$_imgs[1].'" alt="" border=0  />
					  <div class="PDHeroName">'.$Hero_NAME.'</div></td>
					  <td><img width="80" height="80" src="'.$_imgs[5].'" alt="" border=0 /></td>
				  </tr>
				  </table>
				  <table>
				  				 <tr><td></td><td></td></tr>
				  <tr>
				      <td width = "35"><img width="32" height="32" src="'.$_imgs[2].'" alt="" border=0 /></td>
					  <td class="PDAtrName">'.str_replace("Strength","<b>Strength:</b>",$_atr[0]).'</td>
				  </tr>
				  <tr>
				      <td><img width="32" height="32" src="'.$_imgs[3].'" alt="" border=0 /></td>
					  <td class="PDAtrName">'.str_replace("Agility","<b>Agility:</b>",$_atr[1]).'</td>
				  </tr>
				  <tr>
				      <td><img width="32" height="32" src="'.$_imgs[4].'" alt="" border=0 /></td>
					  <td class="PDAtrName">'.str_replace("Intelligence","<b>Intelligence:</b>",$_atr[2]).'</td>
				  </tr>
				  
	</table>-->
				 <table style="margin-top: 20px; width: 600px;">
				 <tr>
				     <th class="PlayDotaAdvStatsTitle padLeft">Advanced statistics</th><th></th>
				 </tr>
				 <tr class="row">
				     <td width="150" class="padLeft PDAdvStats">'.$_adv_stats.'</td>
				 </tr>
				 </table>
				 <table style="margin-top: 20px">
				 <tr><td></td><td></td></tr>
				 <tr class="PDHeroSkills">
				     <td class="padLeft padTop PDHeroSkillsLeft" valign="top" width = "150"><b>'.$_skills_name[0].'</b><br /><img width="64" height="64" src="'.$_imgs[6].'" alt="" border=0 /></td>
					 <td class="PDHeroSkillDesc padTop" valign="top">'.$_skills_desc[0].'<div class="PDNotes">'.$_notes[0].'</div></td>
				 </tr>
				 <tr class="PDHeroSkills">
				     <td class="padLeft PDHeroSkillsLeft" valign="top" width = "69"><b>'.$_skills_name[1].'</b><br /><img width="64" height="64" src="'.$_imgs[7].'" alt="" border=0 /></td>
					 <td class="PDHeroSkillDesc" valign="top">'.$_skills_desc[1].'<div class="PDNotes">'.$_notes[1].'</div></td>
				 </tr>
				 <tr class="PDHeroSkills">
				     <td class="padLeft PDHeroSkillsLeft" valign="top" width = "69"><b>'.$_skills_name[2].'</b><br /><img width="64" height="64" src="'.$_imgs[8].'" alt="" border=0 /></td>
					 <td class="PDHeroSkillDesc" valign="top">'.$_skills_desc[2].'<div class="PDNotes">'.$_notes[2].'</div><br /></td>
				 </tr>
				 <tr class="PDHeroSkills">
				     <td class="padLeft PDHeroSkillsLeft" valign="top" width = "69"><b>'.$_skills_name[3].'</b><br /><img width="64" height="64" src="'.$_imgs[9].'" alt="" border=0 /></td>
					 <td class="PDHeroSkillDesc" valign="top">'.$_skills_desc[3].'<div class="PDNotes">'.$_notes[3].'</div></td>
				 </tr>
				 </table>
				 
				 <table>
				 <tr><th class="padLeft PDHeroTableTitle">Hero Introduction</th></tr>
				 <tr>
				      <td class="padLeft PDHeroHistory">'.$_intro[0].'</td>
				 </tr>
				 <tr><th class="padLeft PDHeroTableTitle">Background story</th></tr>
				 <tr>
				      <td class="padLeft PDHeroHistory">'.$_intro[1].'</td>
				 </tr>
				 </table>
			     
		    </td>
		 </tr>
		 
   </table>
   </div>';
   //echo $HeroHTML;
   file_put_contents("./inc/cache/pdheroes/$pdHero.html", $HeroHTML);
   }
   }
   ?>
 <!--  č -->