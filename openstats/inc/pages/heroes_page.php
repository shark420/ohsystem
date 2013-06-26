<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

    $HomeTitle = $lang["heroes"] ;
	$HomeDesc = $lang["heroes"];
	$HomeKeywords = strtolower( os_strip_quotes($lang["heroes"])).','.$HomeKeywords;
   
    if ( isset($_GET["search_heroes"]) AND strlen($_GET["search_heroes"])>=2  ) {
	    $search_heroes = safeEscape( trim($_GET["search_heroes"]));
		$sql = "AND (description) LIKE ? ";
	 } else $sql = ""; 
   
   
     $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_HEROES." WHERE summary!= '-' $sql LIMIT 1");
	 if ( !empty($sql) ) $sth->bindValue(1, "%".strtolower($search_heroes)."%", PDO::PARAM_STR);
	 
	 $result = $sth->execute();
   	 $r = $sth->fetch(PDO::FETCH_NUM);
	 $numrows = $r[0];
	 $result_per_page = $HeroesPerPage;
	 $draw_pagination = 0;
	 $total_comments  = $numrows;
	 include('inc/pagination.php');
	 $draw_pagination = 1;
	 
	 $sth = $db->prepare("SELECT * FROM ".OSDB_HEROES." WHERE summary!= '-' $sql ORDER BY (description) ASC LIMIT $offset, $rowsperpage");
	 
	 if ( !empty($sql) ) $sth->bindValue(1, "%".strtolower($search_heroes)."%", PDO::PARAM_STR);
	 $result = $sth->execute();
	 $c=0;
     $HeroesData = array();
	 
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	$HeroesData[$c]["id"]        = ($row["heroid"]);
	$HeroesData[$c]["original"]  = ($row["original"]);
	$HeroesData[$c]["description"]  = ($row["description"]);
	$HeroesData[$c]["summary"]  = ($row["summary"]);
	$c++;
	}
?>