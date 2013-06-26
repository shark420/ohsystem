<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>

<div id="footer-wrapper">
 <div class="ct-wrapper">
  <div class="footer-wrap section" id="footer-wrap">
  <?php os_footer();?>
   <div class="widget HTML" id="HTML2">
    <div>Generated in: <?=$total_time?> sec.</div>
    <div class="gototop"><a href="#" rel="nofollow">Return to top of page</a> </div>
    <div class="creds">Copyright &#169; <?=date("Y")?> &#183; Powered by <a target="_blank" href="http://openstats.iz.rs/">OpenStats <?=OS_VERSION?></a> </div>
    <div class="clr"></div>
   </div>
  </div>
 </div><!-- /ct-wrapper -->
</div><!-- footer-wrapper -->

</div><!-- blogouter-wrapper -->

<?php os_after_footer(); ?>
</body>
</html>