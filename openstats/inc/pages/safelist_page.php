<?php

if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

    $HomeTitle = $lang["safelist"] ;
	$HomeDesc = $lang["safelist"];
	$HomeKeywords = strtolower( os_strip_quotes($lang["safelist"])).','.$HomeKeywords;

     $sth = $db->prepare("SELECT COUNT(*) FROM  ".OSDB_SAFELIST." WHERE id>=1 LIMIT 1");
	 $result = $sth->execute();
	 $r = $sth->fetch(PDO::FETCH_NUM);
	 $numrows = $r[0];
	 $result_per_page = $TopPlayersPerPage;
	 $draw_pagination = 0;
	 include('inc/pagination.php');
	 $draw_pagination = 1;
	  
	 $sth = $db->prepare("SELECT * FROM  ".OSDB_SAFELIST." WHERE id>=1 LIMIT $offset, $rowsperpage");
	 $result = $sth->execute();
	 $c=0;
     $SafelistData = array();

	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	$SafelistData[$c]["id"]        = (int)($row["id"]);
	$SafelistData[$c]["server"]  = ($row["server"]);
	$SafelistData[$c]["name"]  = ($row["name"]);
	$SafelistData[$c]["voucher"]  = ($row["voucher"]);
	$c++;
	}	
?>