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
	<div>Page generated in: <?=$total_time?> sec with <?=$db->get_query_cout()?> queries.</div>
	<?php
	}
  }
?>