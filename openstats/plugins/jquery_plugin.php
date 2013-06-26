<?php
//Plugin: Add JQuery plugin to header
//Author: Ivan
//Enable JavaScript Library that simplifies HTML document traversing, event handling, animating, and Ajax interactions for rapid web development.

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '1';

if ($PluginEnabled == 1  ) {
   
   AddEvent("os_js","OS_JQuery");
   
   function OS_JQuery() {
?>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<?php
   }
   
}
?>