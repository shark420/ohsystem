<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

    $HomeTitle = ($lang["guides"]);
    
	//ALL HEROES
	
	if ( isset($_GET["guides"]) AND !empty($_GET["guides"]) ) {
	  $SelectedHero = safeEscape($_GET["guides"]);
	  $sql = " AND g.hid = :hid"; 
	} else {
	$SelectedHero = ''; $sql = '';
	}
	
    $sth = $db->prepare("SELECT * FROM ".OSDB_HEROES." 
	WHERE original!='' GROUP BY (description) ORDER BY (description) ASC ");
	
	$result = $sth->execute();
	
	$c=0;
    $ListHeroesData = array();
	
	while ($row2 = $sth->fetch(PDO::FETCH_ASSOC)) {
	$ListHeroesData[$c]["hid"]         = $row2["heroid"];
	$ListHeroesData[$c]["original"]    = $row2["original"];
	$ListHeroesData[$c]["description"] = $row2["description"];
	
	if ($SelectedHero == $ListHeroesData[$c]["original"]) {
	$ListHeroesData[$c]["selected"] = 'selected="selected" style="background-color: yellow"'; 
	$HomeTitle = ($lang["guides"]). " | ".  $row2["description"];
	$HomeDesc = $lang["guides"]." - ".os_strip_quotes($row2["description"]);
	}
	else $ListHeroesData[$c]["selected"] = '';
	   
	$c++;
	}
   
     $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_GUIDES." as g WHERE g.id>=1 $sql LIMIT 1");
	 if ( !empty($SelectedHero) ) $sth->bindValue(':hid', $SelectedHero, PDO::PARAM_STR); 
	 $result = $sth->execute();
	 
   	 $r = $sth->fetch(PDO::FETCH_NUM);
	 $numrows = $r[0];
	 $result_per_page = $HeroesPerPage;
	 $draw_pagination = 0;
	 $total_comments  = $numrows;
	 include('inc/pagination.php');
	 $draw_pagination = 1;
	 
	 $sth = $db->prepare("SELECT g.hid, g.link, g.id, g.title, h.original, h.description 
     FROM ".OSDB_GUIDES." as g
     LEFT JOIN ".OSDB_HEROES." as h ON h.heroid = g.hid
	 WHERE g.id>=1 $sql
     ORDER BY h.description ASC
     LIMIT $offset, $rowsperpage");
	 if ( !empty($SelectedHero) ) $sth->bindValue(':hid', $SelectedHero, PDO::PARAM_STR); 
	 $result = $sth->execute();
	 
	 $c=0;
     $GuidesData = array();
	  while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	 $GuidesData[$c]["hid"]        = $row["hid"];
	 $GuidesData[$c]["link"]       = $row["link"];
	 $GuidesData[$c]["id"]         = $row["id"];
	 $GuidesData[$c]["title"]      = $row["title"];
	 $GuidesData[$c]["original"]   = $row["original"];
	 $GuidesData[$c]["description"]= $row["description"];
	 $c++;
	 }
?>