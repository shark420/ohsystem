<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

  $time = microtime();
  $time = explode(' ', $time);
  $time = $time[1] + $time[0];
  $finish = $time;
  $total_time = round(($finish - $start), 4);
  
  //AddEvent("os_top_menu", "OS_TopMenu");
  //AddEvent("os_footer",   "OS_FooterMenu");
  AddEvent("os_after_footer", "OS_CopyRight");
  
  
  function show_debug($os_debug = 0  ) {
    if ($os_debug == 1) {
	global $time, $finish, $total_time, $db;
	?>
	<div>Page generated in: <?=$total_time?> sec with <?=$db->get_query_cout()?> queries.</div>
	<?php
	}
  }
  
  function OS_TopMenu() {
  ?>
<!-- Start Page-menu -->
<div id="page-menu">
 <div id="p-menu-left"> </div>
	<div id="p-menu-content">
				
	<ul class="nav clearfix"> 
	<li class="current_page_item"><a href="#">Home</a></li>
	<li class="page_item page-item-127"><a href="#">About</a>
        <ul class='children'>
	       <li class="page_item page-item-204"><a href="#">Meet The Team</a></li>
	       <li class="page_item page-item-206"><a href="#">Where to Find Us</a></li>
        </ul>
    </li>
    <li class="page_item page-item-130"><a href="#">Contact</a></li>
    <li class="page_item page-item-134"><a href="#">Location</a></li>
    <li class="page_item page-item-132"><a href="#">Partners</a></li>
   </ul>				
				</div>	
				<div id="p-menu-right"> </div>
			</div>	<!-- end #page-menu -->	
<!-- End Page-menu -->	
  <?php
  }

  function OS_FooterMenu() {
  ?>
  <div id="recent-comments-5" class="footer-widget widget_recent_comments">
  <h4 class="widgettitle">Recent Comments</h4>
  
    <ul id="recentcomments">
      <li class="recentcomments">Ryan on <a href="#">Ut sagittis ultrices urna eget erat non purus</a></li>
	  <li class="recentcomments"><a href='#' rel='external nofollow' class='url'>Pavan Somu</a> on 
	  <a href="#">Nulla aliquam commodo</a></li>
	  <li class="recentcomments">daniel on <a href="#">Sed neque ipsum pulvinar eu tristique</a></li>
    </ul>
  
  </div>
  
  <div class="clear"></div>
  <?php
  }
  
  function OS_CopyRight() {
  ?>
<script type="text/javascript" src="<?=OS_THEME_PATH?>resources/superfish.js"></script>	
	<script type="text/javascript">
	//<![CDATA[
		jQuery.noConflict();
	
		jQuery('ul.superfish, #page-menu ul.nav').superfish({ 
			delay:       300,                            // one second delay on mouseout 
			animation:   {opacity:'show',height:'show'},  // fade-in and slide-down animation 
			speed:       'fast',                          // faster animation speed 
			autoArrows:  true,                           // disable generation of arrow mark-up 
			dropShadows: false                            // disable drop shadows 
		});
		
		jQuery('ul.nav > li > a.sf-with-ul').parent('li').addClass('sf-ul'); 
		
		et_search_bar();
		et_footer_improvements('#footer .footer-widget');
		
		<!---- et_switcher plugin v1.3 ---->
		(function($)
		{
			$.fn.et_switcher = function(options)
			{
				var defaults =
				{
				   slides: '>div',
				   activeClass: 'active',
				   linksNav: '',
				   findParent: true, //use parent elements in defining lengths
				   lengthElement: 'li', //parent element, used only if findParent is set to true
				   useArrows: false,
				   arrowLeft: 'prevlink',
				   arrowRight: 'nextlink',
				   auto: false,
				   autoSpeed: 5000
				};

				var options = $.extend(defaults, options);

				return this.each(function()
				{
					var slidesContainer = jQuery(this);
					slidesContainer.find(options.slides).hide().end().find(options.slides).filter(':first').css('display','block');
			 
					if (options.linksNav != '') { 
						var linkSwitcher = jQuery(options.linksNav);
										
						linkSwitcher.click(function(){
							var targetElement;

							if (options.findParent) targetElement = jQuery(this).parent();
							else targetElement = jQuery(this);

							if (targetElement.hasClass('active')) return false;

							targetElement.siblings().removeClass('active').end().addClass('active');

							var ordernum = targetElement.prevAll(options.lengthElement).length;
											
							slidesContainer.find(options.slides).filter(':visible').hide().end().end().find(options.slides).filter(':eq('+ordernum+')').stop(true,true).fadeIn(700);
							return false;
						});
					};
					
					jQuery('#'+options.arrowRight+', #'+options.arrowLeft).click(function(){
					  
						var slideActive = slidesContainer.find(options.slides).filter(":visible"),
							nextSlide = slideActive.next(),
							prevSlide = slideActive.prev();

						if (jQuery(this).attr("id") == options.arrowRight) {
							if (nextSlide.length) {
								var ordernum = nextSlide.prevAll().length;                        
							} else { var ordernum = 0; }
						};

						if (jQuery(this).attr("id") == options.arrowLeft) {
							if (prevSlide.length) {
								var ordernum = prevSlide.prevAll().length;                  
							} else { var ordernum = slidesContainer.find(options.slides).length-1; }
						};

						slidesContainer.find(options.slides).filter(':visible').hide().end().end().find(options.slides).filter(':eq('+ordernum+')').stop(true,true).fadeIn(700);

						if (typeof interval != 'undefined') {
							clearInterval(interval);
							auto_rotate();
						};

						return false;
					});   

					if (options.auto) {
						auto_rotate();
					};
					
					function auto_rotate(){
						interval = setInterval(function(){
							var slideActive = slidesContainer.find(options.slides).filter(":visible"),
								nextSlide = slideActive.next();
						 
							if (nextSlide.length) {
								var ordernum = nextSlide.prevAll().length;                        
							} else { var ordernum = 0; }
						 
							if (options.linksNav === '') 
								jQuery('#'+options.arrowRight).trigger("click");
							else 		 		
								linkSwitcher.filter(':eq('+ordernum+')').trigger("click");
						},options.autoSpeed);
					};
				});
			}
		})(jQuery);
		
		
		var $featuredArea = jQuery('#featured'),
			$all_tabs = jQuery('#all_tabs');
		
		jQuery(window).load( function(){
			if ($featuredArea.length) {
				$featuredArea.addClass('et_slider_loaded').et_switcher({
					useArrows: true ,
											auto: true,
											autoSpeed: 5000											
				});
				
				if ( $featuredArea.find('.slide').length == 1 ){
					jQuery('#featured-control a#prevlink, #featured-control a#nextlink').hide();
				}
			};
		} );
				
		if ($all_tabs.length) {
			$all_tabs.et_switcher({
				linksNav: 'ul#tab_controls li a'
			});
		}; 

				
		<!---- Footer Improvements ---->
		function et_footer_improvements($selector){
			var $footer_widget = jQuery($selector);
		
			if (!($footer_widget.length == 0)) {
				$footer_widget.each(function (index, domEle) {
					if ((index+1)%4 == 0) jQuery(domEle).addClass("last").after("<div class='clear'></div>");
				});
			};
		};
		
		<!---- Search Bar Improvements ---->
		function et_search_bar(){
			var $searchform = jQuery('#cat-nav div#search-form'),
				$searchinput = $searchform.find("input#searchinput"),
				searchvalue = $searchinput.val();
				
			$searchinput.focus(function(){
				if (jQuery(this).val() === searchvalue) jQuery(this).val("");
			}).blur(function(){
				if (jQuery(this).val() === "") jQuery(this).val(searchvalue);
			});
		};
		
	//]]>	
	</script>
	
<?php /* ?>
<script type='text/javascript' src='<?=OS_THEME_PATH?>resources/jquery.easing.v1.3.js'></script>
<script type='text/javascript' src='<?=OS_THEME_PATH?>resources/jquery-fancybox.1.3.4.js'></script>
<?php */ ?>
  <p id="copyright">Powered by  <a href="http://openstats.iz.rs">DotA OpenStats</a></p>
  <?php
  }
  
  
  function OS_GetFirstImage($text) {
  $c = 0;
  $_imgs  = array();
  $dom = new DOMDocument();
  @$dom->loadHTML( convEnt($text) );
  $xpath = new DOMXPath($dom);
  $entries = $xpath->query('//img');
  $default = OS_HOME."themes/".OS_THEMES_DIR."/images/dota_banner.png";
  
    foreach($entries as $e)
    {
    $_imgs[$c] =  $e->getAttribute("src"); $c++; break;
	}
	
  if (!empty($_imgs[0]) ) return $_imgs[0];
  else return $default;
}
?>