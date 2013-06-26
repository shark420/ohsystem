<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>


<?php 
foreach ( $NewsData as $News ) {
?>
<div class="entry clearfix" >

<h2 class="title"><a href="<?=OS_HOME?>?post_id=<?=$News["id"]?>"><?=$News["title"]?></a></h2>
	
    <?php if ( OS_is_single()  ) { ?>

        <div class="post-meta clearfix">
			<p class="meta-info">
			<?=date($DateFormat, strtotime($News["date"]))?> | 
			<a href="<?=OS_HOME?>?post_id=<?=$News["id"]?>#comments"><?=$News["comments"]?> <?=$lang["total_comments"]?></a>
			</p>
		</div>
    <?php } ?>
	<div class="entry-content clearfix">
	<?php if ( !OS_is_single()  ) { ?>
	<a href="<?=OS_HOME?>?post_id=<?=$News["id"]?>">
	<img src="<?=OS_GetFirstImage( $News["full_text"] )?>" class='thumb alignleft' alt='Nam libero tempore, cum soluta nobis est' width='140' height='140' /></a>
	<p class="date"><span><?=date($DateFormat, strtotime($News["date"]))?></span></p>
	<?php } ?>
				
	<p><?=$News["text"]?></p>
						
	</div> <!-- end .entry-content -->
		
	<div class="post-meta clearfix">
	 <p class="meta-info">
	  <?php OS_post_edit_link($News["id"]) ?>
	  <a href="<?=OS_HOME?>?post_id=<?=$News["id"]?>#comments"><?=$News["comments"]?> <?=$lang["total_comments"]?></a>
	  </p>
	<?php if ( !OS_is_single() ) { ?>
	<a href="<?=OS_HOME?>?post_id=<?=$News["id"]?>" class="readmore"><span>Read more</span></a>
	<?php } ?>
	</div>
	
   <?php
   if ( OS_is_single()  ) {
   include("themes/".OS_THEMES_DIR."/comment_form.php");
   }
   ?>
	
</div> <!-- end .entry -->
<?php } ?>
<?php
   if ( !OS_is_single()  )
   include('inc/pagination.php');
?>

<div id="content-bottom">
	<div class="container"></div>
</div>