<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
<div class="clr"></div>
 <div class="ct-wrapper entry clearfix">
  <div class="outer-wrapper">
   <div class="content section">
    <div class="widget Blog" id="Blog1">
     <div class="blog-posts hfeed">
<?php
   if ( isset($HeroVote) AND $HeroVote == 1 ) {
     
	//SHOW STATS
	if ( isset($_GET["stats"]) ) {
	   ?>
	   <div align="center">
	  <form action="" method="get" > 
	  <input type="hidden" name="vote" />
	  <input type="hidden" name="stats" />
	   <?=$lang["sortby"] ?>
	   <select name="sort">
		<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "best") $sel = 'selected="selected"'; else $sel = ""; ?>
		 <option <?=$sel?> value="best"><?=$lang["votes_best"]?></option>
	    <?php if (isset($_GET["sort"]) AND $_GET["sort"] == "won") $sel = 'selected="selected"'; else $sel = ""; ?>
	     <option <?=$sel?> value="won"><?=$lang["votes_won"]?></option>
		<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "lost") $sel = 'selected="selected"'; else $sel = ""; ?>
		 <option <?=$sel?> value="lost"><?=$lang["votes_lost"]?></option>
		<?php if (isset($_GET["sort"]) AND $_GET["sort"] == "total") $sel = 'selected="selected"'; else $sel = ""; ?>
		 <option <?=$sel?> value="total"><?=$lang["votes_total"]?></option>
	   </select>
	   <input type="submit" value="<?=$lang["vote_sort"]?>" class="menuButtons" />
	   </form>
	   
	   <table width="500">
	     <tr>
		   <th class="padLeft" width="70"></th>
		   <th><?=$lang["vote_results"] ?></th>
		 </tr>
		<?php
		$order = '(voteup - votedown) DESC';
		
		if (isset($_GET["sort"]) ) {
		  if ( $_GET["sort"] == "won")  $order = 'voteup DESC';
		  if ( $_GET["sort"] == "lost") $order = 'votedown DESC';
		  if ( $_GET["sort"] == "total") $order = '(voteup + votedown) DESC';
		  
		  if ( $_GET["sort"] == "best") $order = '(voteup - votedown) DESC';
		}
		$sth = $db->prepare("SELECT * FROM heroes WHERE summary!= '-' AND (voteup>=1 OR votedown>=1) ORDER BY ".$order." LIMIT ".$HeroVoteShow." ");

		$result = $sth->execute();
		
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
		?>
		<tr style="height:100px">
		  <td width="70" style="vertical-align: middle;" class="padLeft">
		  <a href="<?=$website?>?hero=<?=$row["heroid"]?>"><img style="vertical-align:middle; padding-right:8px;" width="64" height="64" border=0 src="<?=$website?>img/heroes/<?=$row["heroid"]?>.gif" alt="<?=$row["heroid"]?>" /></a>
		  </td>
		  <td align="left" style="vertical-align: middle;">
		    <?=$row["description"]?>
		    <div style="margin-top:9px">
			<span style="color: green">+ <?=$row["voteup"]?></span> / 
			<span style="color: red">- <?=$row["votedown"]?></span>
			<span> ( <?=$row["voteup"]+$row["votedown"]?> <?=$lang["total"]?> )</span>
			</div>
		    	<!--<div><b>Thumbs Up:</b> <?=$row["voteup"]?></div>
				 <div><b>Thumbs Down:</b> <?=$row["votedown"]?></div>
				 <div><b>Total Votes:</b> <?=$row["voteup"]+$row["votedown"]?></div>
				-->
		  
		  </td>
		</tr>
		<?php
		}
		?>
	   </table>
	   
	   <div style="margin-top: 10px;"><a href="<?=OS_HOME?>?vote"><?=$lang["vote_back"]?></a></div>
	   
	   </div>
	   <?php
	}
    else {
	
     if ( isset($_POST["vote_hero"]) AND isset($_SESSION["code"]) AND isset($_POST["code"]) AND $_POST["code"] == $_SESSION["code"] ) {
	
	$code = generate_hash(14);
	$_SESSION["code"] = $code;
	 
		if ( isset($_POST["h1"]) ) {
		$h1 = safeEscape($_POST["h1"]);
		$h1check = safeEscape($_POST["hero_1"]);
		$h2check = safeEscape($_POST["hero_2"]);
		
		if ( $h1check == $h1 ) $votedown = $h2check;
		if ( $h2check == $h1 ) $votedown = $h1check;
		
		//echo "<b>$h1</b> ($h1check -- $h2check  )  <--> $votedown";
		
		$sth = $db->prepare("UPDATE heroes SET `voteup`   = `voteup`+1   WHERE `heroid` = :h1 ");
		$sth->bindValue(':h1', $h1, PDO::PARAM_STR);
		$result = $sth->execute();
		
		
		$sth = $db->prepare("UPDATE heroes SET `votedown` = `votedown`+1 WHERE `heroid` = '".$votedown."' ");
		$sth->bindValue(':votedown', $votedown, PDO::PARAM_STR);
		$result = $sth->execute();
		
		//GET VOTE RESULTS
		$sth = $db->prepare("SELECT * FROM heroes WHERE summary!= '-' AND `heroid` = :h1check LIMIT 1");
		$sth->bindValue(':h1check', $h1check, PDO::PARAM_STR);
		$result = $sth->execute();
		
		$row1 = $sth->fetch(PDO::FETCH_ASSOC);
		
		
		$sth2 = $db->prepare("SELECT * FROM heroes WHERE summary!= '-' AND `heroid` = :h2check LIMIT 1");
		$sth2->bindValue(':h2check', $h2check, PDO::PARAM_STR);
		$result = $sth2->execute();
		
		$row2 = $sth2->fetch(PDO::FETCH_ASSOC);
	   
	    $h1_id = $row1["heroid"];
		$h1_original = $row1["original"];
		$h1_description = $row1["description"];
		$h1_plus = $row1["voteup"];
		$h1_minus = $row1["votedown"];
		$h1_total = $h1_plus + $h1_minus;
		
	    $h2_id = $row2["heroid"];
		$h2_original = $row2["original"];
		$h2_description = $row2["description"];
		$h2_plus = $row2["voteup"];
		$h2_minus = $row2["votedown"];
		$h2_total = $h2_plus + $h2_minus;
		
		//$h1_percent = round($h1_plus/$h1_plus+$h1_minus, 3)*100;
		//$h2_percent = round($h2_total/$h1_total+$h2_total-$h1_minus, 3)*100;
		
		if ( $h1 == $h1_id ) $h1_description = "<b>$h1_description</b>";
	    if ( $h1 == $h2_id ) $h2_description = "<b>$h2_description</b>";
		?>
		<div align="center">
		  <table width="500">
		  	<tr>
		       <th class="padLeft"><?=$lang["vote_results"]?></th><th></th><th></th>
		    </tr>
		    <tr>
			  <td align="center" style="height: 104px; vertical-align: middle; width:200px;">
			    <img style="vertical-align:middle; padding-right:8px;" width="64" height="64" border=0 src="<?=$website?>img/heroes/<?=$h1_id?>.gif" alt="<?=$h1_id?>" />
			  </td>
			   <td  align="center" style="height: 104px; vertical-align: middle;"></td>
			  <td align="center" style="height: 104px; vertical-align: middle; width:200px;">
			    <img style="vertical-align:middle; padding-right:8px;" width="64" height="64" border=0 src="<?=$website?>img/heroes/<?=$h2_id?>.gif" alt="<?=$h2_id?>" />
			  </td>
			</tr>
			
			<tr>
		       <td align="center" style="height:46px; vertical-align: middle; width:200px;"><?=$h1_description?></td>
			    <td></td>
		       <td align="center" style="height:46px; vertical-align: middle; width:200px;"><?=$h2_description?></td>
		    </tr>
			
			<tr>
		       <td align="center" style="vertical-align: middle; width:200px;">
			     <div><span style="color: green; font-weight:bold;"><?=$lang["vote_won"]?></span> <?=$h1_plus?></div>
				 <div><span style="color: red; font-weight:bold;"><?=$lang["vote_lost"]?></span> <?=$h1_minus?></div>
				 <div><span style="font-weight:bold;"><?=$lang["votes_total"]?>:</span> <?=$h1_total?></div>
			   </td>
			    <td></td>
		       	<td align="center" style="vertical-align: middle; width:200px;">
			     <div><span style="color: green; font-weight:bold;"><?=$lang["vote_won"]?></span> <?=$h2_plus?></div>
				 <div><span style="color: red; font-weight:bold;"><?=$lang["vote_lost"]?></span> <?=$h2_minus?></div>
				 <div><span style="font-weight:bold;"><?=$lang["votes_total"]?>:</span> <?=$h2_total?></div>
			   </td>
		    </tr>
			
		    <tr>
		     <td align="center" style="height:46px; vertical-align: middle; width:200px;"></td>
		     <td align="center" style="height:46px; vertical-align: middle; height:46px;"> 
			    <a href="<?=$website?>?vote" class="menuButtons"><?=$lang["vote_again"]?></a>
			</td>
		     <td align="center" style="height:46px; vertical-align: middle; width:200px;"></td>
		    </tr>
			
		  </table>			  
		</div>
		<?php
		} else {
		?>
		<div align="center">
		 <table width="500">
		    <tr style="height: 154px; vertical-align: middle;">
			<td align="center">
		      <div><?=$lang["vote_error1"]?></div>
			</td>
			</tr>
			<tr>
			 <td align="center" style="height:46px; vertical-align: middle;">
		      <div><a href="<?=$website?>?vote" class="menuButtons"><?=$lang["vote_again"]?></a></div>
			</td>
			</tr>
		</table>
		</div>
		<div style="height:100px;">&nbsp;</div>
		<?php
		}
	 }
    else {
	//////////////////   VOTE  ///////////////////
	//HERO 1 vs HERO 2
	 require_once('inc/class.database.php');
	 require_once('inc/db_connect.php');
	$sth = $db->prepare("SELECT * FROM heroes WHERE summary!= '-' ORDER BY RAND() LIMIT 2");
	$result = $sth->execute();
			
	$c=0;
	$HeroVoteData = array();
	while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
	$HeroVoteData[$c]["id"]        = strtoupper($row["heroid"]);
	$HeroVoteData[$c]["original"]  = ($row["original"]);
	$HeroVoteData[$c]["description"]  = ($row["description"]);
	$HeroVoteData[$c]["summary"]  = ($row["summary"]);
	$c++;
	}
	
	$code = generate_hash(14);
	$_SESSION["code"] = $code;
	?>
	<div align="center">
	<form action="" method="post">
	  <table width="460" style="width:460px;">
	    <tr>
		  <th class="padLeft"><?=$lang["vote_title"] ?></th><th></th><th></th>
		</tr>
		<tr style="height: 154px; vertical-align: middle;">
	      <td align="center" style="height: 154px; vertical-align: middle; width:200px;" >
		    <label for="h1">
		     <img style="vertical-align:middle; padding-right:8px; cursor:pointer;" width="64" height="64" border=0 src="<?=$website?>img/heroes/<?=$HeroVoteData[0]["id"]?>.gif" alt="<?=$HeroVoteData[0]["id"]?>" /> 
		     <input id="h1" name="h1" type="radio" value="<?=$HeroVoteData[0]["id"]?>" />
		    </label>
          </td>
		  
          <td  align="center" style="height: 154px; vertical-align: middle;"><?=$lang["vote_vs"] ?></td>	
		   
		  <td align="center" style="height: 154px; vertical-align: middle; width:200px;">
		     <label for="h2">
		     <img style="vertical-align:middle;padding-left:8px; cursor:pointer; " width="64" height="64" border=0 src="<?=$website?>img/heroes/<?=$HeroVoteData[1]["id"]?>.gif" alt="<?=$HeroVoteData[1]["id"]?>" />
		     <input id="h2" name="h1" type="radio" value="<?=$HeroVoteData[1]["id"]?>" />
		     </label>
		 </td>
		</tr>

		
		<tr>
		   <td align="center" style="height:46px; vertical-align: middle; width:200px;"><?=$HeroVoteData[0]["description"]?></td>
		   <td></td>
		   <td align="center" style="height:46px; vertical-align: middle; width:200px;"><?=$HeroVoteData[1]["description"]?></td>
		</tr>
		
		
		
		<tr>
		  <td align="center" style="height:46px; vertical-align: middle; width:200px;"></td>
		  <td align="center" style="height:46px; vertical-align: middle;"> <input type="submit" value="Vote!" name="vote_hero" class="menuButtons" />  </td>
		  <td align="center" style="height:46px; vertical-align: middle; width:200px;"></td>
		</tr>
	   
	  </table>
	  
	  <input type="hidden" value="<?=$HeroVoteData[0]["id"]?>" name="hero_1" />
	  <input type="hidden" value="<?=$HeroVoteData[1]["id"]?>" name="hero_2" /> 
	  
	  <input type="hidden" value="<?=$code?>" name="code" />
	</form>
	
	<div style="margin-top: 10px;"><a href="<?=$website?>?vote&amp;stats"><?=$lang["vote_display"]?></a></div>
	
	</div>
	<?php
     }
    }
   }
?>
     </div>
    </div>
   </div>
  </div>
</div>
<div style="height:168px;">&nbsp;</div>