<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed">
	 
<?=OS_show_errors( $errors );?>

<h2><?=$lang["ban_report"]?></h2>

   <div class="padTop"></div>
   <form action="" method="post">
   <table class="Table500px">
     <tr>
	   <th class="padLeft"><?=$lang["report_player"]?>:</th>
     </tr>
	 <tr>
	    <td class="padLeft">
		   <input type="text" value="<?=$ReportedPlayer?>" class="field" name="report_player" />
		 </td>
     </tr>
	 <tr>
	   <th class="padLeft"><?=$lang["subject"]?>:</th>
     </tr>
	 <tr>
	    <td class="padLeft">
		   <input type="text" value="[BAN REPORT]" class="field" name="subject" />
		</td>
     </tr>
     <tr>
	   <th class="padLeft"><?=$lang["report_reason"] ?>:</th>
     </tr>
	 <tr>
	    <td class="padLeft">
		   <textarea style="width: 450px; height: 100px;" name="message"></textarea>
		 </td>
     </tr>
     <tr>
	   <th class="padLeft"><?=$lang["game_url"]?>:</th>
     </tr>
	 <tr>
	    <td class="padLeft">
		   <input type="text" value="Your game URL here" class="field" name="game_url" />
		</td>
     </tr>
     <tr>
	   <th class="padLeft"><?=$lang["replay_url"]?>:</th>
     </tr>
	 <tr>
	    <td class="padLeft">
		   <input type="text" value="" class="field" name="replay_url" />
		</td>
     </tr>
	 <tr>
	    <td class="padLeft">
		<div class="padTop"></div>
		   <input type="submit" value="<?=$lang["report_submit"]?>" class="menuButtons" name="submit_report" />
		<div class="padTop"></div>
		</td>
     </tr>
	 
	 </table>
	 </form>
     </div>
    </div>
   </div>
  </div>
</div>