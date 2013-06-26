<?php
//Plugin: Smilies in comments
//Author: Ivan
//This plugin adds smiles in user comments.

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '1';
//Enable edit plugin options
$PluginOptions = '1';
$SmiliesPath = 'img/smilies/';
$ThisPlugin = basename(__FILE__, '');

if ($PluginEnabled == 1) {

//Change options
if ( isset($_POST["SmiliesPath"]) ) {
   $PATH = safeEscape($_POST["SmiliesPath"]);
   write_value_of('$SmiliesPath', "$SmiliesPath", $PATH , $plugins_dir.basename(__FILE__, '') );
   $SmiliesPath = $PATH;
}

//If user can edit plugin
if ( OS_is_admin() AND OS_PluginEdit( $ThisPlugin ) ) {
//Show following options when user click on edit icon for this plugin
//Display all smilies
$Option = '
<form action="" method="post" >
  <input size="30" type="text" value="'.$SmiliesPath.'" name="SmiliesPath" />
  <input type="submit" value = "Change smilies path" class="menuButtons" />
  <a href="'.$website.'adm/?plugins" class="menuButtons">Cancel</a>
</form>

<div><a href="javascript:;" onclick="showhide(\'smilies\')">Show all</a></div>';

$Option.='<div style="display:none; background-color: #fff;" id="smilies">';

if (file_exists("../".$SmiliesPath) AND $handleSmilies = opendir("../".$SmiliesPath)) {
  while (false !== ($icon = readdir($handleSmilies))) {
   if (strstr($icon, ".gif") OR strstr($icon, ".jpg") OR strstr($icon, ".png"))
   $Option.='<img src="../'.$SmiliesPath.$icon.'" alt="" />';
  }
}

if (!file_exists("../".$SmiliesPath)) $Option.='<span style="color:red;">Folder not exists</span>';

$Option.='</div>';
}

  function ShowSmilesInComments() {
    global $CommentsData;
	if ( !empty($CommentsData) ) {
	  
	  for ($i=0; $i<count($CommentsData); $i++ ) {
	  //replace comment text with Smilies function
	  $CommentsData[$i]["text"] = LoadSmilies( $CommentsData[$i]["text"] );
	  }
	  
	  return array($CommentsData);
	}
	
  }
  
  function LoadSmilies($text, $path = "img/smilies/" ) {
    
	$text = str_replace(
	array(
	";)", //1
	"8)", //2
	":D", //3
	":)", //4
	":(", //5
	":O", //6
	"oO", //7
	":alien:", //8
	":crying:", //9
	":p", //10
	":woot:"), //11
	
	array(
	'<img src="'.$path.'icon_wink.gif" alt="smiley" />',   //1
	'<img src="'.$path.'icon_cool.gif" alt="smiley" />',     //2
	'<img src="'.$path.'icon_laugh.gif" alt="smiley" />',//3
	'<img src="'.$path.'icon_smile.gif" alt="smiley" />',  //4
	'<img src="'.$path.'icon_sad.gif" alt="smiley" />',    //5
	'<img src="'.$path.'icon_surprised.gif" alt="smiley" />', //6
	'<img src="'.$path.'icon_eek.gif" alt="smiley" />',    //7
	'<img src="'.$path.'icon_alien.gif" alt="smiley" />',    //8
	'<img src="'.$path.'icon_crying.gif" alt="smiley" />',    //9
	'<img src="'.$path.'icon_tongue.gif" alt="smiley" />',    //10
	'<img src="'.$path.'icon_woot.gif" alt="smiley" />'),    //11
	$text
	);
	return $text;
  }
  
  function AddSmiliesButtons($path = "img/smilies/") {
    ?>
	<div class="smiliesWrapper">
	 <a href="javascript:;" onclick="AddSmiley(';)')" ><img src="<?=$path?>icon_wink.gif" alt="smiley" /></a>
	 <a href="javascript:;" onclick="AddSmiley('8)')" ><img src="<?=$path?>icon_cool.gif" alt="smiley" /></a>
	 <a href="javascript:;" onclick="AddSmiley(':D')" ><img src="<?=$path?>icon_laugh.gif" alt="smiley" /></a>
	 <a href="javascript:;" onclick="AddSmiley(':)')" ><img src="<?=$path?>icon_smile.gif" alt="smiley" /></a>
	 <a href="javascript:;" onclick="AddSmiley(':(')" ><img src="<?=$path?>icon_sad.gif" alt="smiley" /></a>
	 <a href="javascript:;" onclick="AddSmiley(':O')" ><img src="<?=$path?>icon_surprised.gif" alt="smiley" /></a>
	 <a href="javascript:;" onclick="AddSmiley('oO')" ><img src="<?=$path?>icon_eek.gif" alt="smiley" /></a>
	 <a href="javascript:;" onclick="AddSmiley(':alien:')" ><img src="<?=$path?>icon_alien.gif" alt="smiley" /></a>
	 <a href="javascript:;" onclick="AddSmiley(':crying:')" ><img src="<?=$path?>icon_crying.gif" alt="smiley" /></a>
	 <a href="javascript:;" onclick="AddSmiley(':p')" ><img src="<?=$path?>icon_tongue.gif" alt="smiley" /></a>
	 <a href="javascript:;" onclick="AddSmiley(':woot:')" ><img src="<?=$path?>icon_woot.gif" alt="smiley" /></a>
	</div>
	
<?php
  }
  
  function SmiliesJS() {
  ?>
  <script type="text/javascript">
function AddSmiley(tag) {
   document.getElementById("text_message").focus();
   var html = document.getElementById("text_message").value;
   html=html+""+tag+" ";
   document.getElementById("text_message").value = html;
}
</script>
  <?php
  }
 
  
  AddEvent("os_head",  "SmiliesJS");   //ADD javascript to header
  AddEvent("os_comment_form",  "AddSmiliesButtons");   //ADD SMILIES BUTTONS ON TEXT FORM
  //AddEvent("os_after_comment_form","AddSmiliesButtons"); //or add after comment form
  
  //Display smilies
  ShowSmilesInComments( $SmiliesPath );


}

?>