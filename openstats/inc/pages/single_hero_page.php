<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

    $heroid = safeEscape( $_GET["hero"] );
	$pdHero = get_HeroByID($heroid);
	$hero = str_replace("-"," ",strtoupper($pdHero));
	
	//get hero data from playdota website
	if ($PlayDotaHeroes == 1) {
	 if ( !file_exists("inc/cache/pdheroes/".$pdHero.".html") ) include('inc/PlayDotaHeroParser.php');
	 }
	  else {
	 //get hero data from database
	 $sth = $db->prepare("SELECT * FROM ".OSDB_HEROES." WHERE heroid = :heroid LIMIT 1");
	 $sth->bindValue(':heroid', $heroid, PDO::PARAM_STR); 
	 $result = $sth->execute();
	 $c=0;
     $HeroData = array();
	 $row = $sth->fetch(PDO::FETCH_ASSOC);
	 $HeroData[$c]["id"]        = (int)($row["heroid"]);
	 $HeroData[$c]["original"]  = ($row["original"]);
	 $HeroData[$c]["description"]  = ($row["description"]);
	 $HeroData[$c]["summary"]  = convEnt($row["summary"]);
	 $HeroData[$c]["stats"]  = convEnt($row["stats"]);
	 $HeroData[$c]["skills"]  = convEnt($row["skills"]);
	 
	$HomeTitle = ($row["description"]);
	$HomeDesc = os_strip_quotes($row["summary"]);
	$HomeKeywords = strtolower( os_strip_quotes($row["description"])).','.$HomeKeywords;
	 }
	 
	if ($GuidesPage == 1) {
	 $sth = $db->prepare("SELECT * FROM ".OSDB_GUIDES." WHERE hid = :heroid ");
	 $sth->bindValue(':heroid', $heroid, PDO::PARAM_STR); 
	 $result = $sth->execute();
	 $c=0;
     $HeroDataGuides = array();
	  while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	  $HeroDataGuides[$c]["id"]        = ($row["id"]);
	  $HeroDataGuides[$c]["title"]  = ($row["title"]);
	  $HeroDataGuides[$c]["link"]  = ($row["link"]);
	  $c++;
	  }	
	}
	 
?>