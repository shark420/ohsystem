<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

  if ( !isset($search_bans) ) $search_bans="Search bans...";
  ?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed">
  
<div style="margin-bottom: 12px;">
  <form action="" method="get">
  <input type="hidden" name="bans" />
   <input 
   type="text" 
   value="<?=$search_bans?>" 
   style="height: 26px;" 
   onblur= "if (this.value == '')  {this.value = '<?=$search_bans?>';}"
   onfocus="if (this.value == '<?=$search_bans?>') {this.value = '';}" 
   name="search_bans"
   />
   <input type="submit" value="Search" class="menuButtons" />
   </form>
</div>
  <table>
   <tr>
     <th width="160" class="padLeft"><?=$lang["player"] ?></th>
	 <th width="180"><?=$lang["reason"] ?></th>
	 <th width="180"><?=$lang["game_name"]?></th>
	 <th width="130"><?=$lang["date"]?></th>
	 <th width="120"><?=$lang["bannedby"]?></th>
   </tr>
  <?php
  foreach ($BansData as $Ban) {
  ?>
  <tr class="row">
    <td width="160" class="padLeft">
	  <?php if (isset($Ban["letter"]) ) { ?>
	  <img class="imgvalign" width="21" height="15" src="<?=OS_HOME?>img/flags/<?=$Ban["letter"]?>.gif" alt="" />
	  <?php } ?>
	  <a href="<?=OS_HOME?>?u=<?=strtolower($Ban["name"])?>"><?=$Ban["name"]?></a>
	</td>
	 <td width="180" class="overflow_hidden" ><?=$Ban["reason"]?></td>
	 <td width="180" class="overflow_hidden"><?=$Ban["gamename"]?></td>
	 <td width="130"><?=$Ban["date"]?></td>
	 <td width="120"><?=$Ban["admin"]?></td>
  </tr>
  <?php
  }
  ?>
  </table>
     </div>
    </div>
   </div>
  </div>
</div>
  <?php
  $SHOW_TOTALS = 1;
  include('inc/pagination.php');
?>