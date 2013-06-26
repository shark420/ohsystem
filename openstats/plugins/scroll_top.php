<?php
//Plugin: Scroll to top (jQuery)
//Author: Ivan
//Scroll to top plugin. <b>jQuery</b> must be enabled.


if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '1';

if ($PluginEnabled == 1  ) {

   AddEvent("os_head","OS_ScrollToTop");
   
   function OS_ScrollToTop() {
?>
<script type="text/javascript">	
jQuery.noConflict();
var $jq = jQuery;
$jq(function(){
			
  //$("#content").remove();
  $jq("body").append('<div id="scroll_to_top"><a href="#top">Go top</a></div>');
  $jq("#scroll_to_top a").css({'display' : 'none', 'z-index' : '9', 'position' : 'fixed', 'top' : '100%', 'width' : '72px', 'margin-top' : '-55px', 'right' : '50px', 'margin-left' : '-50px', 'height' : '20px', 'padding' : '3px 5px', 'font-size' : '14px', 'text-align' : 'center', 'padding' : '3px', 'color' : '#ffffff', 'background-color' : '#222222', '-moz-border-radius' : '5px', '-khtml-border-radius' : '5px', '-webkit-border-radius' : '5px', 'opacity' : '.8', 'text-decoration' : 'none'});	
  
  $jq('#scroll_to_top a').click(function(){
  $jq('html, body').animate({scrollTop:0}, 'slow');
  });
  
  $jq('.gototop').remove(); //Remove go to top (in template)
  
	var scroll_timer;
	var displayed = false;
	var top = $jq(document.body).children(0).position().top+800;
	$jq(window).scroll(function () {
		window.clearTimeout(scroll_timer);
		scroll_timer = window.setTimeout(function () {
			if($jq(window).scrollTop() <= top)
			{
				displayed = false;
				$jq('#scroll_to_top a').fadeOut(500);
			}
			else if(displayed == false)
			{
				displayed = true;
				$jq('#scroll_to_top a').stop(true, true).show().click(function () { $jq('#scroll_to_top a').fadeOut(500); });
			}
		}, 100);
	});
  
});
</script>
<?php
   }
   
}
?>