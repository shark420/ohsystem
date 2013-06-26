<?php
/* 
Ivan Antonijević (ivan.anta [at] gmail.com), 2012
*/
if (strstr($_SERVER['REQUEST_URI'],basename(__FILE__))){header('HTTP/1.1 404 Not Found');die;}

$prefix = ""; 

$strana = "&amp;page=";
if (!isset($end) ) $end = "";

if ( isset($HOME_PAGE) AND $HOME_PAGE== 1 AND !isset($_GET["post_id"]) ) $strana = "?page=";

if ( isset($_GET["admins"]) )     $prefix.="?admins";
if ( isset($_GET["games"]) )      $prefix.="?games";
if ( isset($_GET["top"]) )        $prefix.="?top";
if ( isset($_GET["bans"]) )       $prefix.="?bans";
if ( isset($_GET["heroes"]) )     $prefix.="?heroes";
if ( isset($_GET["items"]) )      $prefix.="?items";
if ( isset($_GET["warn"]) )       $prefix.="?warn";
if ( isset($_GET["safelist"]) )   $prefix.="?safelist";
if ( isset($_GET["members"]) )    $prefix.="?members";
if ( isset($_GET["guides"]) )     $prefix.="?guides";
if ( isset($_GET["game"]) AND is_numeric($_GET["game"]) )   $prefix.="?game=".safeEscape( (int)$_GET["game"] );

if ( isset($_GET["post_id"]) ) {
  $strana = "&amp;page=";
  $prefix.="?post_id=".safeEscape((int)$_GET["post_id"]);
  $end = "#comments";
}

if ( isset($_GET["search"]) AND isset($s) ) $prefix.="?search=".$s;
if ( isset($_GET["search_bans"]) AND isset($search_bans) ) $prefix.="&amp;search_bans=".$search_bans;
if ( isset($_GET["search_heroes"]) AND isset($search_heroes) ) $prefix.="&amp;search_heroes=".$search_heroes;
if ( isset($_GET["search_items"]) AND isset($search_items) ) $prefix.="&amp;search_items=".$search_items;

if ( isset($_GET["L"]) AND strlen($_GET["L"]) == 1 ) $prefix.="&amp;L=".$_GET["L"];

if ( isset($_GET["sort"]) ) $prefix.="&amp;sort=".safeEscape( $_GET["sort"]);
if ( isset($_GET["uid"]) )  $prefix.="&amp;uid=".safeEscape( (int) $_GET["uid"]);
if ( isset($_GET["h"]) )  $prefix.="&amp;h=".safeEscape( $_GET["h"]);
if ( isset($_GET["u"]) )    { $prefix.="?u=".safeEscape( (int) $_GET["u"]).""; $end ="#game_history"; }

if ( isset($_GET["m"]) )  $prefix.="&amp;m=".safeEscape( (int) $_GET["m"]);
if ( isset($_GET["y"]) )  $prefix.="&amp;y=".safeEscape( (int) $_GET["y"]);
if ( isset($_GET["compare"]) ) $prefix.="&amp;compare";

              $rowsperpage = $result_per_page;

              $totalpages = ceil($numrows / $rowsperpage);
              if (isset($_GET['page']) && is_numeric($_GET['page'])) {
                  $currentpage = (int)$_GET['page'];
              } else {
                  $currentpage = 1;
              }
              if ($currentpage > $totalpages) {
                  $currentpage = $totalpages;
              }
              if ($currentpage < 1) {
                  $currentpage = 1;
              }
              if ($totalpages <= 1) {
                  $totalpages = 1;
              }

              $offset = ($currentpage - 1) * $rowsperpage;
              if (isset($_GET['page']) AND is_numeric($_GET['page'])){
                          $current_page = safeEscape($_GET['page']);
                          }

                          if (!isset($current_page)) {
                              $current_page = 1;
                          }
              if (!isset($MaxPaginationLinks) ) $range = 5;
			  else  $range = $MaxPaginationLinks;
			  
              if ($range >= $totalpages) {
                  $range = $totalpages;
              }
			  
			  if ($current_page > $totalpages) {$current_page = $totalpages;}
			  
if ($draw_pagination == 1 AND $totalpages>=2) { 
             ?>
	  <div class="pagination2"> 
	   <?php
              if ($currentpage > 1) {
                  ?><a class="button orange" href="<?=OS_HOME?><?=$prefix?>"><span>&laquo;</span></a><?php
                  $prevpage = $currentpage - 1;
                  ?><a class="button orange" href="<?=OS_HOME?><?=$prefix?><?=$strana?><?=$prevpage?><?=$end?>"><span><?=$lang["previous_page"]?></span></a><?php
              }
              for ($x = ($currentpage - $range); $x < (($currentpage + $range) + 1); $x++) {
                  if (($x > 0) && ($x <= $totalpages)) {
                      if ($x == $currentpage) {
                         ?>
					  <a class="button orange" href="javascript:;"><span class="active"><?=$x?></span></a><?php
                      } else {
                          ?>
					  <a class="button orange" href="<?=OS_HOME?><?=$prefix?><?=$strana?><?=$x?><?=$end?>"><span><?=$x?></span></a><?php
                      }
                  }
              }
              if ($currentpage != $totalpages) {
                  $nextpage = $currentpage + 1;
                 ?>
				 <a class="button orange" href="<?=OS_HOME?><?=$prefix?><?=$strana?><?=$nextpage?><?=$end?>"><span><?=$lang["next_page"]?></span></a>
				 
				 <a class="button orange" href="<?=OS_HOME?><?=$prefix?><?=$strana?><?=$totalpages?><?=$end?>"><span><?=$totalpages?></span></a><?php
              }
             ?>   
			<?php if (isset($SHOW_TOTALS) ) { ?>
			 &nbsp;
			 <span class="totals"><?=$lang["page"]?> <b><?=$current_page?></b> <?=$lang["pageof"]?> <?=$totalpages?> 
			 (<?=$numrows?> <?=$lang["total"]?>)
			 </span>
			 <?php } ?>
			 </div>
			 <?php
}

?>