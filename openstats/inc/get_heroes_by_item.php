<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
	
	$PluginEnabled = '1';
	$TotalHeroes = '12';
	
	if ($PluginEnabled==1)
	{

	$itemid = $Item["icon"];
	if ($TotalHeroes>=100) $TotalHeroes = "100";
	
	$sql = getMostUsedHeroByItem("", $itemid, $TotalHeroes, $Item["shortname"] );
    $result = $db->prepare($sql);
	$result = $sth->execute();
	if ($sth->rowCount()>=1) {
	?>
	<div>
	    <h2><b>Most used by:</b></h2>
	<?php
	  while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	  $hero = strtoupper($row["hero"]);
      $heroName = convEnt2($row["heroname"]);
      $itemName = convEnt2($Item["shortname"]);
	  $itemName2 = convEnt2($Item["shortname"]);
      $totals = $row["total"];
	  ?>
	  <a href="<?=$website?>?hero=<?=$hero?>" <?=ShowToolTip($heroName." used ". $itemName2." ". $totals."x", "", 200, 21, 15)?>><img width="48" height="48" src="<?=$website?>img/heroes/<?=$hero?>.gif" alt="<?php echo $heroName; ?>" /></a>
	  <?php
	  }
	  $db->free($result);
	?>
    </div>
	<?php
	}
  }
?>