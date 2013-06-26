<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

//INSERT botid entry
if ( isset($_POST["create_bot"]) AND isset($_POST["botID"]) AND is_numeric($_POST["botID"]) ) {

    $check = $db->prepare("SHOW TABLES LIKE '".OSDB_GAMELIST."'"); //always check for gamelist table
	$result = $check->execute();
	if ( $check->rowCount()>=1 ) {
	
	   $botID = safeEscape( $_POST["botID"] );
	   $gamelist = $db->prepare("INSERT INTO gamelist (botid) VALUES ('".(int) $botID."'); ");
	   $result = $gamelist->execute();
	   $update   = $db->prepare("UPDATE gamelist SET gamename = '', ownername = '', creatorname =  '', map = '', slotstaken = 0, slotstotal = 0, usernames = '', totalgames = 0, totalplayers = 0; ");
	   $result = $update->execute();
	}
}

//REMOVE BOT ID
if ( isset($_GET["gamelist"]) AND isset($_GET["remove_botid"])  AND is_numeric($_GET["remove_botid"]) ) {

   $check = $db->prepare("SHOW TABLES LIKE '".OSDB_GAMELIST."'"); //always check for gamelist table
   $result = $check->execute();
	if ( $check->rowCount()>=1 ) {
	  $botID = safeEscape( $_GET["remove_botid"] );
	  $delete = $db->prepare("DELETE FROM `".OSDB_GAMELIST."` WHERE botid = '".(int) $botID ."' ");
	  $result = $delete->execute();
	}

}

//INSTALL
if ( isset($_GET["gamelist"]) AND isset($_GET["install"]) ) {

    $check = $db->prepare("SHOW TABLES LIKE '".OSDB_GAMELIST."'"); //check again
	$result = $check->execute();
	if ( $check->rowCount()<=0 ) {
	   $gl = 1;
	   $gamelist = $db->prepare("CREATE TABLE gamelist (id INT NOT NULL PRIMARY KEY AUTO_INCREMENT, botid INT, gamename VARCHAR(128), ownername VARCHAR(32), creatorname VARCHAR(32), map VARCHAR(100), slotstaken INT, slotstotal INT, usernames VARCHAR(512), totalgames INT, totalplayers INT) ENGINE = MEMORY; ");
	   $result = $gamelist->execute();
	   if ($gl) {
	   write_value_of('$GameListPatch', "$GameListPatch", 1 , "../config.php");
	   }
	   
	}
}

//REMOVE
if ( isset($_GET["gamelist"]) AND isset($_GET["remove"]) ) {

    $check = $db->prepare("SHOW TABLES LIKE '".OSDB_GAMELIST."'"); //check again
	$result = $check->execute();
	if ( $check->rowCount()>=1 ) {
	
	   $gamelist = $db->prepare("DROP TABLE ".OSDB_GAMELIST." ");
	   $result = $gamelist->execute();
	   write_value_of('$GameListPatch', "$GameListPatch", 0 , "../config.php");
	   
	}
}
?>
<div align="center"> 

<h2>Gamelist</h2>

<?php
  $check = $db->prepare("SHOW TABLES LIKE '".OSDB_GAMELIST."'");
  $result = $check->execute();
  if ( $check->rowCount()<=0 ) {
  ?>
<table>
  <tr>
    <th class="padLeft">
     <div align="center">
       <a href="<?=$website?>adm/?gamelist&amp;install" class="menuButtons">Install gamelist table</a>
     </div>
    </th>
  </tr>
  <tr>
  <td class="padLeft">
    <div align="center" style="height: 48px; padding-top: 9px;">
      <a href="http://www.codelain.com/forum/index.php?topic=18076.0" target="_blank">Click here</a> for more information about gamelist patch on codelain forum
    </div>
  </td>
  </tr>
</table>
  <?php
  } else {
  ?>
  <table>
  <tr>
    <th class="padLeft">
     <div align="center">
       Gamelist patch installed
	   <a href="javascript:;" onclick="if(confirm('Remove gamelist patch support?') ) { location.href='<?=$website?>adm/?gamelist&remove' }" class="menuButtons">Remove</a>
     </div>
    </th>
  </tr>
  </table>
  <!--CREATE BOTID ENTRY-->
  <div>&nbsp;</div>
  <form action="?gamelist" method="post">
  <table>
    <tr>
	  <td class="padLeft" width="160">Create Bot ID entry:</td>
	  <td>
	  <input type="text" value="" name="botID" size="2" />
	  <input type="submit" class="menuButtons" value="Submit" name="create_bot" /> 
	  <span>eg. type <b>1</b> if your bot's ID is 1</span> </td>
	</tr>
  </table>
  </form>
  
  <div>&nbsp;</div>
  <table>
  <tr>
    <th class="padLeft" width="40">Bot id</th>
    <th class="padLeft" width="240">Game name</th>
	<th width="100">Slots / Total slots</th>
	<th></th>
  </tr>
  <?php
  $sth = $db->prepare("SELECT * FROM ".OSDB_GAMELIST." ORDER BY botid ASC");
  $result = $sth->execute();
  while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
  ?>
  <tr>
    <td class="padLeft"><?=$row['botid']?></td>
    <td class="padLeft"><?=$row['gamename']?></td>
	<td><?=$row['slotstaken']?> / <?=$row['slotstotal']?></td>
	<td><a href="javascript:;" onclick="if(confirm('Remove bot id (<?=$row['botid']?>)?') ) { location.href='<?=$website?>adm/?gamelist&remove_botid=<?=$row['botid']?>' }" class="">[x] remove bot id</a></td>
  </tr>
  <?php
  }
  ?>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
  </tr>
  </table>
  <?php
  }
?>
<div>&nbsp;</div>
<a href="<?=$website?>adm/?gamelist" class="menuButtons">Refresh</a>

</div>