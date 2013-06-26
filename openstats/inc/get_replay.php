<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

      //REPLAY NAME HANDLING CODE
    $replaygamename=str_ireplace("|","_",str_ireplace(">","_",str_ireplace("<","_",str_ireplace("?","_",str_ireplace("*","_",str_ireplace(":","_",str_ireplace("/","_",str_ireplace("\\","_",$gamename))))))));
	
    $replayloc="GHost++ ".$gametimenew." ".$replaygamename." (".($duration).").w3g";

    if(!file_exists($ReplayLocation.'/'.$replayloc))
    {													//Time handling isn't perfect. Check time + 1 and time - 1
	$replayloc="GHost++ ".$gametimenew." ".$replaygamename." (".($duration-1).").w3g";
	
	    if(!file_exists($ReplayLocation.'/'.$replayloc))
	    {
		$replayloc="GHost++ ".$gametimenew." ".$replaygamename." (".($duration+1).").w3g";
		    if(!file_exists($ReplayLocation.'/'.$replayloc))
		    {
			$replayloc="GHost++ ".$gametimenew." ".$replaygamename.".w3g";
		    }
	    }
    }
	//Modify Ghost++ to match replay with name format: Ghost++ GAME_ID.w3g
	//uakf.b solution: http://www.codelain.com/forum/index.php?topic=14297.msg108849#msg108849
	if(!file_exists($ReplayLocation.'/'.$replayloc)) {
	$replayloc= "GHost++ ".$gid.".w3g";
	}
	
    $replayurl = $ReplayLocation.'/'.str_ireplace("#","%23", str_ireplace("\\","_",str_ireplace("/","_",str_ireplace(" ","%20",$replayloc))));
	
    $replayloc = $ReplayLocation.'/'.str_ireplace("\\","_",str_ireplace("/","_",$replayloc));
	
    $txtReplay = substr($replayloc,0,-4).".html";
?>