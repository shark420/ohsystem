<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

    $HomeTitle = $lang["items"] ;
	$HomeDesc = $lang["items"];
	$HomeKeywords = strtolower( os_strip_quotes($lang["items"])).','.$HomeKeywords;
   
    if ( isset($_GET["search_items"]) AND strlen($_GET["search_items"])>=2  ) {
	    $search_items = safeEscape( $_GET["search_items"]);
		$sql = "AND (name) LIKE ? ";
	 } else $sql = ""; 
	 
    if ( isset($_GET["search_items"]) AND strlen($_GET["search_items"])>=2  ) {
	
	$sth = $db->prepare("SELECT * FROM ".OSDB_ITEMS." as Items
	WHERE item_info !='' AND name != 'Aegis Check' 
	AND name != 'Arcane Ring' AND name NOT LIKE 'Disabled%' $sql
	GROUP BY (shortname) 
	ORDER BY (shortname) ASC");
	
	if ( !empty($sql) ) $sth->bindValue(1, "%".$search_items."%", PDO::PARAM_STR);
	
	$result = $sth->execute();
	
	$numrows = $sth->rowCount() ;

	} else { 
    $sth = $db->prepare("SELECT * FROM ".OSDB_ITEMS." WHERE item_info !='' AND name != 'Aegis Check' 
	AND name != 'Arcane Ring' AND name NOT LIKE 'Disabled%' GROUP BY (shortname)"); 	
	
	$result = $sth->execute();
	
	$numrows = $sth->rowCount() ;
	}

	$result_per_page = $ItemsPerPage;
	$draw_pagination = 0;
	//$total_comments  = $numrows;
	include('inc/pagination.php');
	$draw_pagination = 1;

	$sth = $db->prepare("SELECT * FROM ".OSDB_ITEMS." as Items
	WHERE item_info !='' AND name != 'Aegis Check' 
	AND name != 'Arcane Ring' AND name NOT LIKE 'Disabled%' $sql
	GROUP BY (shortname) 
	ORDER BY (shortname) ASC 
	LIMIT $offset, $rowsperpage");
	
	if ( !empty($sql) ) $sth->bindValue(1, "%".$search_items."%", PDO::PARAM_STR);
	$result = $sth->execute();
	
	//if ($db->num_rows() )
	
	$c=0;
    $ItemsData = array();
	 
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	$ItemsData[$c]["itemid"]  = ($row["itemid"]);
	$ItemsData[$c]["name"]  = ($row["name"]);
	$ItemsData[$c]["shortname"]  = ($row["shortname"]);
	$ItemsData[$c]["item_info"]  = convEnt($row["item_info"]);
	$ItemsData[$c]["icon"]  = ($row["icon"]);
	$c++;
	}
?>