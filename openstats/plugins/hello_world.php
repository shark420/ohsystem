<?php
//Plugin: Hello World
//Author: Ivan
//Simple plugin example. Random quotes.


if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';

//no options

if ($PluginEnabled == 1  ) {

   AddEvent("os_content","HelloWorldExample");
   
function HelloWorldExample() {

$quotes = array();
$quotes[] = "What's happening?";
$quotes[] = "You're kind of slow for a human, aren't ya?";
$quotes[] = "Is there trouble?";
$quotes[] = "Aah, the great outdoors.";
$quotes[] = "My song is yours.";
$quotes[] = "My song echoes from the deeps.";
$quotes[] = "I await the legions coming.";
$quotes[] = "Our time is short.";
$quotes[] = "The sleeper has awakened.";
$quotes[] = "For the end-of-the-world spell, press Control, Alt, Delete.";
$quotes[] = "Guns don't kill people, I do! Hahaaa!";
$quotes[] = "I hear you man.";
$quotes[] = "Don't worry, be happy.";
$quotes[] = "Dazdingo.";
$quotes[] = "I feel you man.";
$quotes[] = "I'd kiss you, but I've got puke breath.";
$quotes[] = "I will bring honor to my father and my kingdom.";
$quotes[] = "Be quick, time is mana.";
$quotes[] = "I smell magic in the air....or maybe barbecue.";
$quotes[] = "For Quel'thalas.";
$quotes[] = "You require my assistance?";
$quotes[] = "'I'm was stupid.' -'Me too.'";
$quotes[] = "The engine's running.";
$quotes[] = "I've got a rocket in my pocket.";
$quotes[] = "I've got a rocket in my pocket.";

$random_number = rand(0,count($quotes)-1);

$quote = $quotes[$random_number];

?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog" id="Blog1">
     <div class="blog-posts hfeed">
	   <div align="center" class="clearfix padLeft padTop">
	     <h2><?=$quote?></h2>
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