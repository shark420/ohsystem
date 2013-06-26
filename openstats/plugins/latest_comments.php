<?php
//Plugin: Latest Comments
//Author: Ivan
//Just plugin example. Latest comments.

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

$PluginEnabled = '0';

$PluginOptions = '1';

$ThisPlugin = basename(__FILE__, '');

if ($PluginEnabled == 1  ) {
  
  if ( OS_is_admin() AND OS_PluginEdit( $ThisPlugin ) )
  $Option = 'Latest Comments URL: <div><a href="'.OS_HOME.'?action=comments" target="_blank">'.OS_HOME.'?action=comments</a></div>
  <a href="'.$website.'adm/?plugins" class="menuButtons">&laquo; Back</a>';
  
  AddEvent("os_content",  "LatestCommentsPage");
  
  function LatestCommentsPage() {
  if ( isset($_GET["action"]) AND $_GET["action"] == "comments") {
?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section" id="content recent-posts">
    <div class="widget Blog" id="Blog1">
     <div class="blog-posts hfeed">
	 <div align="center" class="entry clearfix padLeft padTop">
	   <h2 class="title">Latest comments</h2>

	<?php 
	 global $db;
	 
	 if ( isset($_GET["uid"]) AND is_numeric($_GET["uid"]) ) {
	    $uid = safeEscape( (int) $_GET["uid"] );
		$sql = " AND u.user_id =:uid ";
	 } else $sql = "";
	 
	 $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_COMMENTS." as u WHERE u.id>=1 $sql "); 
	 if (!empty($sql) ) $sth->bindValue(':uid', (int) $uid, PDO::PARAM_INT); 
	 $result = $sth->execute();
	 $r = $sth->fetch(PDO::FETCH_NUM);
	 $numrows = $r[0];
	 
	 $result_per_page = 10;
	 $offset = os_offset( $numrows, $result_per_page );
	 $sth = $db->prepare("SELECT c.id, c.user_id, c.page, c.post_id, c.text, c.date, c.user_ip, n.news_title, u.user_name, u.user_avatar
	 FROM ".OSDB_COMMENTS." as c 
	 LEFT JOIN ".OSDB_USERS." as u ON u.user_id = c.user_id
	 LEFT JOIN ".OSDB_NEWS." as n ON n.news_id = c.post_id
	 WHERE n.status = 1 $sql
	 ORDER BY c.date DESC
	 LIMIT $offset, $result_per_page"); 
	 
	 if (!empty($sql) ) $sth->bindValue(':uid', (int) $uid, PDO::PARAM_INT); 
	 $result = $sth->execute();
	 ?>
<table>
	 <?php
	 while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	 if ( !empty($row["user_avatar"]) ) $avatar = '<img src="'.$row["user_avatar"].'" alt="" width="32" height="32" class="imgvaligntop" />'; else $avatar = '';
	 ?>
	 <tr>
	   <td class="padLeft"><?=$avatar ?> <b><?=$row["user_name"]?></b> <a href="<?=OS_HOME?>?post_id=<?=$row["post_id"]?>#comments"><?=$row["news_title"]?></a>  - <i><?=date( OS_DATE_FORMAT, $row["date"])?></i> </td>
	 </tr>
	 <tr>
	   <td class="padLeft padBottom"> 
	      <div style="padding-bottom:18px;"><?=limit_words($row["text"], 20)?> <a href="<?=OS_HOME?>?post_id=<?=$row["post_id"]?>#comments">... &raquo;</a></div>
	   </td>
	 </tr>
	 <?php
	 
	}

	?>   
	</table>
	<?php 	 
	 os_pagination( $numrows, $result_per_page );
	 ?>
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
}
?>