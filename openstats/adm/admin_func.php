<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }


function UpdateCommentsByPostIds($PostIDS = '') {
   $db = new db("mysql:host=".OSDB_SERVER.";dbname=".OSDB_DATABASE."", OSDB_USERNAME, OSDB_PASSWORD);
   $sth = $db->prepare("SET NAMES 'utf8'");
   $result = $sth->execute();
      //Prepare query and update total comments for each post
      $query = "SELECT * FROM ".OSDB_NEWS." WHERE ";
	  $PIDS = explode(",", $PostIDS);
	    for ($i = 0; $i < count($PIDS); $i++) {
	    $query.=" news_id = '".$PIDS[$i]."' OR";
	    }
        $query = substr($query,0, -3);  
	    $sth = $db->prepare($query);
		$result = $sth->execute();
	    while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	      //echo $row["post_id"]. " | ";
	      $get = $db->prepare("SELECT COUNT(*) FROM ".OSDB_COMMENTS." WHERE post_id= '".$row["news_id"]."' LIMIT 1");
		  $result = $get->execute();
	      $r = $get->fetch(PDO::FETCH_NUM);
	      $TotalComments = $r[0]; 
	      $update = $db->prepare("UPDATE ".OSDB_NEWS." SET comments = '".$TotalComments."' WHERE news_id = '".$row["news_id"]."' ");
		  $result = $update->execute();
	      }
  
}

function readLine($file, $line_num, $delimiter="\n") 
{ 
    $i = 1; 
    $fp = fopen( $file, 'r' ); 
    while ( !feof ( $fp) ) 
    { 
        $buffer = fgets($fp); 
        if( $i == $line_num ) 
        { 
            return $buffer; 
        } 
        $i++; 
        $buffer = ''; 
    } 
    return false; 
}

function OS_Curl( $url ) {
 
       if (function_exists('curl_init')) {
	     $ch = curl_init( $url );
		 curl_setopt($ch, CURLOPT_HEADER, 0);
         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		 curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
		 curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
         $return  = curl_exec ($ch);
         curl_close ($ch);
		 
		 return $return;
		 }
 
}

function formatOffset($offset) {
        $hours = $offset / 3600;
        $remainder = $offset % 3600;
        $sign = $hours > 0 ? '+' : '-';
        $hour = (int) abs($hours);
        $minutes = (int) abs($remainder / 60);

        if ($hour == 0 AND $minutes == 0) {
            $sign = ' ';
        }
        return $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) .':'. str_pad($minutes,2, '0');

}
?>