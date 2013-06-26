<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

  $time = microtime();
  $time = explode(' ', $time);
  $time = $time[1] + $time[0];
  $finish = $time;
  $total_time = round(($finish - $start), 4);
  
  
  function show_debug($os_debug = 0  ) {
    if ($os_debug == 1) {
	global $time, $finish, $total_time, $db;
	?>
	<div>Page generated in: <?=$total_time?> sec.</div>
	<?php
	}
  }
  
  AddEvent("os_top_menu", "OS_CustomMenu");
  
  function OS_CustomMenu() {
  //remove: 
  //   style="display:none;" 
  //to enable custom menu
  ?>
  <div class="ct-wrapper" style="display:none;">
  <div class="sub-nav-menu">
   <ul class="sf-menu">
     <li><a href="#">Category #1</a></li>
     <li><a href="#">Category #2</a>
   <ul>
     <li><a href="#">Sub Category 1.1</a>
         <ul>
            <li><a href="#">Sub Category 2.1</a></li>
            <li><a href="#">Sub Category 2.2</a></li>
            <li><a href="#">Sub Category 2.3</a></li>
         </ul>
     </li>
     <li><a href="#">Sub Category 1.2</a></li>
     <li><a href="#">Sub Category 1.3</a></li>
    </ul>
      </li>
      <li><a href="#">Category #3</a></li>
      <li><a href="#">Category #4</a></li>
      <li><a href="#">Category #5</a></li>
    </ul>
</div>
</div>
  <?php
  }
  
  
  function OS_GetFirstImage($text) {
  $c = 0;
  $_imgs  = array();
  $dom = new DOMDocument();
  @$dom->loadHTML( convEnt($text) );
  $xpath = new DOMXPath($dom);
  $entries = $xpath->query('//img');
  $default = OS_HOME."themes/".OS_THEMES_DIR."/img/dota_banner.png";
  
    foreach($entries as $e)
    {
    $_imgs[$c] =  $e->getAttribute("src"); $c++; break;
	}
	
  if (!empty($_imgs[0]) ) return $_imgs[0];
  else return $default;
}
?>