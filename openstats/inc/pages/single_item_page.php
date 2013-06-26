<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

    $itemID = safeEscape( $_GET["item"] ) ;
	$sth = $db->prepare("SELECT * FROM ".OSDB_ITEMS." WHERE itemid = :itemid ");
	
	$sth->bindValue(':itemid', $itemID, PDO::PARAM_STR); 
	
	$result = $sth->execute();
	
	$c=0;
    $ItemData = array();
	
	$row = $sth->fetch(PDO::FETCH_ASSOC);
	$ItemData[$c]["itemid"]  = ($row["itemid"]);
	$ItemData[$c]["name"]  = ($row["name"]);
	$ItemData[$c]["shortname"]  = ($row["shortname"]);
	$ItemData[$c]["item_info"]  = convEnt($row["item_info"]);
	$ItemData[$c]["itemid"]  = ($row["itemid"]);
	$ItemData[$c]["icon"]  = ($row["icon"]);
	$c++;	
	
    $HomeTitle = $row["shortname"] ;
	$HomeDesc = strip_tags(os_strip_quotes($row["item_info"]));
	$HomeDesc = str_replace("\n", " ", limit_words($HomeDesc, 42) );
	$HomeKeywords = strtolower( os_strip_quotes($row["shortname"])).','.$HomeKeywords;
?>