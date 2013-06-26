<?php
//Plugin: Quote user
//Author: Ivan
//Quote user option on comments

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';

if ($PluginEnabled == 1) {

   global $CommentsData;
   
   	if ( !empty($CommentsData) ) {
	  
	  for ($i=0; $i<count($CommentsData); $i++ ) {
	  
	  $username = $CommentsData[$i]["username_clean"];
	  $search = '/\[quote=(.*?)\](.*?)\[\/quote\]/is';
	  $replace = '<div style="background-color: #EEE; border: 1px solid #D6D6D6; padding: 6px; color: #3D3D3D; "><b>$1</b>:<br /> <i>$2</i></div>';
	  
	  $CommentsData[$i]["text"] = preg_replace($search , $replace, $CommentsData[$i]["text"]);
	  
	  //Add quote button/link
	  $CommentsData[$i]["text"] = '<div id="comment-'.$CommentsData[$i]["id"].'">'.( $CommentsData[$i]["text"] )."</div>";
	  $CommentsData[$i]["text"].='<div style="float:right;"><a href="javascript:;" onclick="QuoteText(\''.$CommentsData[$i]["id"].'\', \''.$username.'\')">[quote]</a></div>';
	  }
	  
	}



  AddEvent("os_head",  "QuoteTextJs");   //ADD javascript to header

  function QuoteTextJs() {
  ?><script type="text/javascript">
    function QuoteText(id, name) {
	document.getElementById("text_message").focus();
	txt = js_strip_tags(document.getElementById("comment-"+id).innerHTML);
	textarea = document.getElementById("text_message").value;
	
	if (name=="") name="guest";
	
	html = textarea+"[quote="+name+"]"+txt+"[/quote]\n";
	document.getElementById("text_message").value = html;
	}
	
function js_strip_tags(html){
 
		//PROCESS STRING
		if(arguments.length < 3) {
			html=html.replace(/<\/?(?!\!)[^>]*>/gi, '');
		} else {
			var allowed = arguments[1];
			var specified = eval("["+arguments[2]+"]");
			if(allowed){
				var regex='</?(?!(' + specified.join('|') + '))\b[^>]*>';
				html=html.replace(new RegExp(regex, 'gi'), '');
			} else{
				var regex='</?(' + specified.join('|') + ')\b[^>]*>';
				html=html.replace(new RegExp(regex, 'gi'), '');
			}
		}
 
		//CHANGE NAME TO CLEAN JUST BECAUSE 
		var clean_string = html;
 
		//RETURN THE CLEAN STRING
		return clean_string;
	}
   </script>
  <?php
  }
  
}
?>