<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>

<div align="center">
  
  <div class="padTop"></div>
  <h2><?=$lang["current_games"]?> <a class="menuButtons" href="<?=$website?>"><?=$lang["refresh"]?></a></h2>
  
  <table>
  <tr>
    <th class="padLeft" width="240"><?=$lang["game_name"]?></th>
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