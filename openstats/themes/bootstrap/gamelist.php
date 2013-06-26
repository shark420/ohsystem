<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div class="clr"></div>
 <div class="ct-wrapper">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog">
     <div class="blog-posts hfeed">
  <h4><?=$lang["current_games"]?> <a class="menuButtons refresh" href="<?=OS_HOME?>"><?=$lang["refresh"]?></a></h4>
  
  <table class="table table-condensed table-bordered">
  <tr>
    <th class="padLeft" style="width: 240px"><?=$lang["game_name"]?></th>
	<th><?=$lang["slots"] ?></th>
	<th></th>
  </tr>
  <?php
  foreach ( $LiveGamesData as $LiveGames ) {
  if (!empty($LiveGames["gamename"]) ) {
  ?>
  <tr>
    <td class="padLeft">
	   <a href="javascript:;" onclick="showhide('<?=$LiveGames["botid"]?>')"><?=$LiveGames["gamename"]?></a>
	<div id="<?=$LiveGames["botid"]?>" style="display:none;">
	 <table>
	 <?php
	 //print_r($LiveGames["players"]);
	 for($i = 0; $i < count( $LiveGames["players"] ) - 2; $i+=3) {
	 	$username = $LiveGames["players"][$i];
		$realm = $LiveGames["players"][$i + 1];
		$ping = $LiveGames["players"][$i + 2];
		
		if ( $username == "" ) {
		?>
		<tr>
		  <td><?=$lang["empty"] ?></td>
		  <td></td>
		</tr>
		<?php
		} else {
		?>
        <tr>
		  <td><b><?=$username?></b></td>
		  <td><?=$ping?> <?=$lang["ms"] ?></td>
		</tr>
		<?php
		}
	 }
	 ?>
	 </table>
	</div>
	   
	</td>
	<td><?=$LiveGames["slotstaken"]?> / <?=$LiveGames["slotstotal"]?></td>
  </tr>
  <?php } 
  }
  ?>
  </table>
  
     </div>
    </div>
   </div>
  </div>
</div>