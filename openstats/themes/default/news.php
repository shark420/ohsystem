<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>

  <div align="center" class="padTop">
<?php if (!isset($lang["recent_news"]) ) { ?>
  <h2><?=$lang["recent_news"]?></h2><?php } ?>
  <?php
   foreach ( $NewsData as $News ) {
   ?>
   <table>
   <tr>
     <th class="padLeft">
	 <div style="text-align:left;">
	   <a href="<?=$website?>?post_id=<?=$News["id"]?>"><?=$News["title"]?></a>
	  </div>
	 </th>
   </tr>
   <tr>
     <td class="padLeft">
	   <div class="padAll"><?=$News["text"]?> <?=$News["read_more"]?></div>
	   
	   <div class="padBottom" style="margin-top: 6px; border-top: 1px solid #2A2E36; font-size: 11px;">
	   <a style="font-size: 11px;" href="<?=$website?>?post_id=<?=$News["id"]?>#comments"><?=$News["comments"]?> <?=$lang["total_comments"]?></a> | 
	   <?=date($DateFormat, strtotime($News["date"]))?>
	   <?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
	   | <a href="<?=$website?>adm/?posts&amp;edit=<?=$News["id"]?>">edit entry</a>
	   <?php } ?>
	   </div>
	 </td>
   </tr>
   </table>
   <div class="padBottom"></div>
   <?php
   }
   ?>
   
   <?php
   if ( isset($_GET["post_id"]) AND is_numeric($_GET["post_id"]) ) {
   include("themes/".$DefaultStyle."/comment_form.php");
   }
   
   ?>
   
</div>
<?php
   if ( !isset($_GET["post_id"]))
   include('inc/pagination.php');
?>