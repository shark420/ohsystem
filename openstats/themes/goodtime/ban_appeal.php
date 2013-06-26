<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }

?>
<div class="entry clearfix" >

<h2 class="title"><?=$lang["ban_appeal"]?></h2>

<?php if (isset($_GET["success"]) ) { ?>
<h2><?=$lang["appeal_successfull"] ?></h2>
<?php } ?>
<form action="" method="get">
<table class="Table500px">
  <tr>
    <th class="padLeft"><?=$lang["verify_appeal"]?></th>
  </tr>
  <tr class="row">
    <td class="padLeft"><?=$lang["verify_appeal_info"]?></td>
  </tr>
  <tr class="row">
    <td class="padLeft">
	<input type="text" value="<?=$BanAppeal?>" name="ban_appeal" class="field" />
	<input type="submit" value="Check" class="menuButtons" />
	</td>
  </tr>
  <tr>
    <td class="padLeft">&nbsp;</td>
  </tr>
</table>
</form>

<?php if (isset($BanAppealName ) AND !empty($BanAppealName ) ) { ?>
  <table class="Table500px">
    <tr>
	  <td class="padLeft">
	  <div class="padTop"></div>
<?php if ( is_logged() ) { ?>
	  <div><?=$lang["appeal_here"]?> <a href="#appeal" onclick="showhide('appeal')"><?=$lang["here"] ?></a></div>
<?php } else { ?>
	  <div><?=$lang["you_must_be"] ?> <a href="<?=OS_HOME?>?login"><?=$lang["logged_in"]?></a> <?=$lang["to_appeal"] ?></div>
<?php } ?>
	  <div class="padTop"></div>
	  <div><?=$lang["appeal_ban_date"]?> <?=date( $DateFormat, strtotime( $BanAppealDate ) )?></div>
	  <div><a href="<?=OS_HOME?>?u=<?=strtolower($BanAppealName)?>"><?=$BanAppealName?></a> <?=$lang["was_banned"]?> 
	  <b><?=$BanAppealAdmin?></b></div></div>
	  <div class="padTop"></div>
	  <div><b><?=$lang["reason"]?>:</b> <?=$BanAppealReason?></div>
	  <div><b><?=$lang["server"]?>:</b> <?=$BanAppealServer?></div>
	  <div><b><?=$lang["game"] ?>:</b> <?=$BanAppealGamename?></div>
	  </td>
	</tr>
   </table>
   
   
<a name="appeal"></a>

<?php
if ( isset($errors) AND !empty($errors) ) { ?>
<div class="padTop"><?=$errors?></div>
<?php } ?>
<form action="" method="post">
<div id="appeal" style="display: none;">
   <div class="padTop"></div>
   <table class="Table500px">
     <tr>
	   <th class="padLeft"><?=$lang["appeal_for"]?> <?=$BanAppealName?></th>
     </tr>
	 <tr>
	    <td class="padLeft">
		   <textarea style="width: 450px; height: 100px;">Nick: <?=$BanAppealName?><?php echo "\n"; ?>Ban reason: <?=$BanAppealReason?><?php echo "\n"; ?>Date: <?=date( $DateFormat, strtotime( $BanAppealDate ) )?><?php echo "\n"; ?>Admin: <?=$BanAppealAdmin?></textarea>
		 </td>
     </tr>
	 <tr>
	   <th class="padLeft"><?=$lang["subject"]?>:</th>
     </tr>
	 <tr>
	    <td class="padLeft">
		   <input disabled type="text" value="[BAN APPEAL] <?=$BanAppealName?>" class="field" />
		   <input hidden type="text" value="<?=$BanAppealName?>" class="field" name="player_appeal" />
		   <input hidden type="text" value="[BAN APPEAL] <?=$BanAppealName?>" class="field" name="subject" />
		</td>
     </tr>
     <tr>
	   <th class="padLeft"><?=$lang["your_message"] ?>:</th>
     </tr>
	 <tr>
	    <td class="padLeft">
		   <textarea style="width: 450px; height: 100px;" name="message"></textarea>
		 </td>
     </tr>
     <tr>
	   <th class="padLeft"><?=$lang["game_url"]?>:</th>
     </tr>
	 <tr>
	    <td class="padLeft">
		   <input type="text" value="Your game URL here" class="field" name="game_url" />
		</td>
     </tr>
     <tr>
	   <th class="padLeft"><?=$lang["replay_url"]?>:</th>
     </tr>
	 <tr>
	    <td class="padLeft">
		   <input type="text" value="" class="field" name="replay_url" />
		</td>
     </tr>
	 <tr>
	    <td class="padLeft">
		<div class="padTop"></div>
		   <input type="submit" value="<?=$lang["submit"]?>" class="menuButtons" name="submit_appeal" />
		<div class="padTop"></div>
		</td>
     </tr>
	 
	 </table>
	 
</div>
</form>
<?php 
} 
?>
<div style="height:300px;">&nbsp;</div>
</div>