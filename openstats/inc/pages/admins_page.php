<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

     $HomeTitle = $lang["admins"] ;
	 $HomeDesc = $lang["admins"];
	 $HomeKeywords = strtolower( os_strip_quotes($lang["admins"])).','.$HomeKeywords;
    
	 $sth = $db->prepare("SELECT COUNT(*) FROM ".OSDB_ADMINS." WHERE id>=1 LIMIT 1");
	 $result = $sth->execute();
	 $r = $sth->fetch(PDO::FETCH_NUM);
	 $numrows = $r[0];
	 $result_per_page = $TopPlayersPerPage;
	 $draw_pagination = 0;
	 include('inc/pagination.php');
	 $draw_pagination = 1;
	 
	 $c=0;
    $AdminsData = array();
	
	$sth = $db->prepare("SELECT * FROM ".OSDB_ADMINS." WHERE id>=1 ORDER BY id DESC LIMIT $offset, $rowsperpage");
	
	$result = $sth->execute();
	
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	$AdminsData[$c]["id"]        = (int)($row["id"]);
	$AdminsData[$c]["name"]  = ($row["name"]);
	$AdminsData[$c]["server"]  = ($row["server"]);
	$c++;
	}
?>