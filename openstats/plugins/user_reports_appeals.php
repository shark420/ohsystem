<?php
//Plugin: User reports and appeals
//Author: Ivan
//Users can preview their reports and appeals

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }


$PluginEnabled = '0';
//Enable edit plugin options
//$PluginOptions = '1';

if ($PluginEnabled == 1) {
    
	//?action=reports
    if ( OS_GetAction("reports") AND is_logged() ) AddEvent("os_content",  "OS_MyReports");
	//?action=appeals
	if ( OS_GetAction("appeals") AND is_logged() ) AddEvent("os_content",  "OS_MyAppeals");
	
	//Add to main menu (MISC link)
	if ( is_logged() )
	AddEvent("os_add_menu_misc",  "OS_ShowReportAppealMenu"); 
	
	function OS_ShowReportAppealMenu() {
	?>
	<li><a href="<?=OS_HOME?>?action=reports">My Reports</a></li>
	<li><a href="<?=OS_HOME?>?action=appeals">My Appeals</a></li>
	<?php
	}
	
	//REPORTS
	function OS_MyReports() {
	 
	   global $db;
	   global $lang;
	   
	   $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_REPORTS." WHERE user_id = :uid LIMIT 1");
       $sth->bindValue(':uid', OS_GetUserID(), PDO::PARAM_INT); 
	   $r = $sth->fetch(PDO::FETCH_NUM);
	   $result = $sth->execute();
	   $numrows = $r[0];
	 
	   $result_per_page = 10;
	   $offset = os_offset( $numrows, $result_per_page ); //calculate offset for pagination
	   
	   $sth = $db->prepare("SELECT r.*, b.name as banname, s.player, s.id as reported_id
	   FROM ".OSDB_REPORTS." as r 
	   LEFT JOIN ".OSDB_BANS." as b ON LOWER(b.name) = LOWER(player_name)
	   LEFT JOIN ".OSDB_STATS." as s ON s.id = r.player_id
	   WHERE r.user_id = :uid
	   ORDER BY r.status ASC, r.added DESC LIMIT $offset, $result_per_page");
	   $sth->bindValue(':uid', OS_GetUserID(), PDO::PARAM_INT); 
	   $result = $sth->execute();
	   ?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section" id="content recent-posts">
    <div class="widget Blog" id="Blog1">
     <div class="blog-posts hfeed">
	 <div align="center" class="entry clearfix padLeft padTop">
	   <h2 class="title">My Reports</h2>
	   
	   <table>
	     <tr>
		   <th width="160" class="padLeft">Reported player</th>
		   <th width="150">Status</th>
		   <th>Reason</th>
		   <th width="150">Report date</th>
		 </tr>
	<?php  while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { 
	   $reason = trim(strip_tags($row["reason"]));
	   if ( empty($reason) ) $reason = 'n/a';
	   
	   if ($row["status"] == 1) $status = 'solved'; else $status = 'under review';
	?> 
	     <tr style="height:50px;" class="row">
		   <td width="160" class="padLeft"><a href="<?=OS_HOME?>?u=<?=$row["player_id"]?>" target="_blank"><?=$row["player_name"]?></a></td>
		   <td><?=$status?></td>
		   <td><a href="javascript:;" title="<?=$reason?>" onclick="showhide('<?=$row["reported_id"]?>-<?=$row["added"]?>')" >Show reason</a><div id="<?=$row["reported_id"]?>-<?=$row["added"]?>" style="display:none;"><?=$reason?></div></td>
		   <td><?=date(OS_DATE_FORMAT, $row["added"])?></td>
		 </tr>
    <?php } 
	?>	
	   </table>
	<?php  os_pagination( $numrows, $result_per_page ); ?>   
	   <div style="margin-top: 140px;"></div>
	  </div> 
     </div>
    </div>
   </div>
  </div>
</div>
	   <?php
	 
	}
	
	
	//APPEALS
	function OS_MyAppeals() {
	 
	   global $db;
	   global $lang;
	   
	   $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_APPEALS." WHERE user_id = :uid LIMIT 1");
       $sth->bindValue(':uid', OS_GetUserID(), PDO::PARAM_INT); 
	   $r = $sth->fetch(PDO::FETCH_NUM);
	   $result = $sth->execute();
	   $numrows = $r[0];
	 
	   $result_per_page = 10;
	   $offset = os_offset( $numrows, $result_per_page ); //create offset for pagination
	   
	   $sth = $db->prepare("SELECT *
	   FROM ".OSDB_APPEALS." WHERE user_id = :uid
	   ORDER BY status ASC, added DESC LIMIT $offset, $result_per_page");
       $sth->bindValue(':uid', OS_GetUserID(), PDO::PARAM_INT); 
	   $r = $sth->fetch(PDO::FETCH_NUM);
	   $result = $sth->execute();
	   
	   ?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section" id="content recent-posts">
    <div class="widget Blog" id="Blog1">
     <div class="blog-posts hfeed">
	 <div align="center" class="entry clearfix padLeft padTop">
	   <h2 class="title">My Appeals</h2>
	   
	   <table>
	     <tr>
		   <th width="160" class="padLeft">Reported player</th>
		   <th width="150">Status</th>
		   <th>Reason</th>
		   <th width="150">Report date</th>
		 </tr>
	<?php  while ($row = $sth->fetch(PDO::FETCH_ASSOC)) { 
	   $reason = trim(strip_tags($row["reason"]));
	   if ( empty($reason) ) $reason = 'n/a';
	   
	   if ($row["status"] == 1) $status = 'solved'; else $status = 'under review';
	?> 
	     <tr style="height:50px;" class="row">
		   <td width="160" class="padLeft"><a href="<?=OS_HOME?>?u=<?=$row["player_name"]?>" target="_blank"><?=$row["player_name"]?></a></td>
		   <td><?=$status?></td>
		   <td><a href="javascript:;" title="<?=$reason?>" onclick="showhide('<?=$row["player_id"]?>-<?=$row["added"]?>')" >Show reason</a>
		   <div id="<?=$row["player_id"]?>-<?=$row["added"]?>" style="display:none;">
		   <?=$reason?>
		   <div><b>Game url:</b> <?=$row["game_url"]?></div>
		   <div><b>Replay url:</b> <?=$row["replay_url"]?></div>
		   </div>
		   </td>
		   <td><?=date(OS_DATE_FORMAT, $row["added"])?></td>
		 </tr>
    <?php } 
	?>	
	   </table>
	<?php  os_pagination( $numrows, $result_per_page ); ?>   
	   <div style="margin-top: 140px;"></div>
	  </div> 
     </div>
    </div>
   </div>
  </div>
</div>
	   <?php
	 
	}


}
?>