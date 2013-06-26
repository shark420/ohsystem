<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
os_footer();
?>

<div id="footer-wrapper" align="center">
  <table>
   <tr>
     <td width="900">
	 <?=show_debug( $_debug  )?>
	 Powered by <a target="_blank" href="http://openstats.iz.rs/">OpenStats <?=OS_VERSION?></a> 
	 </td>
   </tr>
  </table>
</div>

</div>
<?php os_after_footer(); ?>
</body>
</html>