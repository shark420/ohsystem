<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
      </div>
     </div>
   </div>
 </div>
</div>
<div id="footer">
	<div class="container clearfix">
		
	  <?php os_footer();?>
		<div class="clear"></div>
	</div>
</div>

<div id="footer-bottom">
	<div class="container clearfix">
	 <?php os_after_footer(); ?>
	</div>
</div>

</body>
</html>