<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>

<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
    <div class="content section">
     <div class="widget Blog">
      <div class="blog-posts hfeed">
<?php 
foreach ( $NewsData as $News ) {
?>
  <div class="date-outer">
    <h2 class='date-header'><span><?=$News["date"]?></span></h2>
	
<div class="post-outer">
 <div class="post hentry uncustomized-post-template">
  <div class="post-header">
   <div class="post-header-line-1"></div>
    <span class="post-timestamp">
    <?=$News["date"]?>
    </span>
    <span class="post-comment-link">
    <a href="<?=OS_Post_Link($News["id"])?>#comments"><?=$News["comments"]?> <?=$lang["total_comments"]?></a>
    </span>
  </div>
  
  <h1 class="post-title entry-title"><a href="<?=OS_Post_Link($News["id"])?>"><?=$News["title"]?></a></h1>
  
  <div class="post-body entry-content" id="post-body-<?=$News["id"]?>">
  <?php if ( !OS_is_single() ) { ?>
   <div class="post_thumb">
     <a href="<?=OS_Post_Link($News["id"])?>"><img width="120" src="<?=OS_GetFirstImage( $News["full_text"] )?>" alt="post-thumb" /></a>
   </div>
   <?php } ?>
   <?=$News["text"]?> <?=$News["read_more"]?>
   <div style="clear: both;"></div>
  </div>


<div class="post-footer">
  <div class="post-footer-line post-footer-line-1"></div>
  <div class="post-footer-line post-footer-line-2">
<?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
    <span class="post-labels"><a href="<?=OS_HOME?>adm/?posts&amp;edit=<?=$News["id"]?>">edit entry</a></span>
<?php } ?>
  </div>
  <div class="post-footer-line post-footer-line-3">
  <span class="post-location"></span>
  </div>
</div>

</div>
   <?php
   if ( OS_is_single() ) {
   include("themes/".OS_THEMES_DIR."/comment_form.php");
   }
   ?>
</div>

  </div>
   <?php
   }
   ?>
     </div>
    </div>
   </div>
 </div>
</div>
<?php
   if ( !OS_is_single() )
   include('inc/pagination.php');
?>