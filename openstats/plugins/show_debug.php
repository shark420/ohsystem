<?php
//Plugin: Show debug information in footer
//Author: Ivan
//Enable debug in footer

$PluginEnabled = '0';

if ($PluginEnabled == 1  ) {
   
   AddEvent("os_footer","OS_DEBUG");
   
   function OS_DEBUG() {
    show_debug(1);
   }
}
?>