<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div id="push"></div>

<div id="footer">

    <div class="container">
	<?php os_footer();?>
	<div class="credit">Copyright &#169; <?=date("Y")?> &#183; Powered by <a target="_blank" href="http://openstats.iz.rs/">OpenStats <?=OS_VERSION?></a> </div>
	<?=show_debug( $_debug  )?>
	<?php os_after_footer(); ?>
	 <div class="gototop"><a href="#" rel="nofollow">Return to top of page</a> </div>
	</div>
</div>

</div><!-- container -->
</div><!-- wrapper -->
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/jquery.js"></script>
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/bootstrap-transition.js"></script>
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/bootstrap-alert.js"></script>
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/bootstrap-modal.js"></script>
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/bootstrap-dropdown.js"></script>
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/bootstrap-scrollspy.js"></script>
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/bootstrap-tab.js"></script>
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/bootstrap-tooltip.js"></script>
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/bootstrap-popover.js"></script>
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/bootstrap-button.js"></script>
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/bootstrap-collapse.js"></script>
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/bootstrap-carousel.js"></script>
    <script src="<?=OS_HOME.OS_CURRENT_THEME_PATH?>js/bootstrap-typeahead.js"></script>
	    <script type="text/javascript" src="<?=OS_HOME?>scripts.js"></script>
</body>
</html>