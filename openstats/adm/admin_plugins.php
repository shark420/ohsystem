<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
//Simple plguin system

$plugins_dir = '../'.OS_PLUGINS_DIR;
?>

<div align="center">
<?php

if ($handle = opendir($plugins_dir)) {
?>
<table>
<tr>
  <th class="padLeft" width="280">Plugin</th>
  <th width="94"></th>
  <th width="90">Author</th>
  <th></th>
</tr>
<?php
   //load plugins
   while (false !== ($PluginFile = readdir($handle))) {
	  $PluginName = "";
	  $PluginAuthor = "";
	  $PluginDesc = ""; 
	  if ($PluginFile !="." AND  $PluginFile !="index.php" AND $PluginFile !=".." AND strstr($PluginFile,".php")==true ) {
	  //enable/disable
	  if ( isset($_GET["disable"]) AND file_exists($plugins_dir.safeEscape($_GET["disable"] ) )  AND $PluginFile == $_GET["disable"] ) {
	    $PluginEnabled = 1;
		write_value_of('$PluginEnabled', "$PluginEnabled", 0 , $plugins_dir.safeEscape($_GET["disable"]) );
		echo $PluginFile.' - disabled <a href="'.OS_HOME.'adm/?plugins">&laquo; Back</a>';
	  }
	  else
	  if ( isset($_GET["enable"]) AND file_exists($plugins_dir.safeEscape($_GET["enable"] ) ) AND $PluginFile == $_GET["enable"] ) {
	    $PluginEnabled = 0;
		write_value_of('$PluginEnabled', "$PluginEnabled", 1 , $plugins_dir.safeEscape($_GET["enable"]) );
		echo $PluginFile.' - enabled <a href="'.OS_HOME.'adm/?plugins">&laquo; Back</a>';
	  }
	  else
	  if ( isset($_GET["delete"]) AND file_exists($plugins_dir.safeEscape($_GET["delete"] ) ) ) {
	    $PluginEnabled = 0;
		unlink(  $plugins_dir.safeEscape($_GET["delete"]) );
	  }
	  
	  if ( file_exists($plugins_dir.$PluginFile) ) {
	  	  
	  $PluginName   = trim(str_replace("//Plugin:", "", readLine($plugins_dir.$PluginFile, 2)));
	  $PluginAuthor = trim(str_replace("//Author:", "", readLine($plugins_dir.$PluginFile, 3)));
	  $PluginDesc   = trim(str_replace("//", "", readLine($plugins_dir.$PluginFile, 4)));
	  
	  if ( !empty($PluginName) AND !empty( $PluginAuthor ) ) {
	  include($plugins_dir.$PluginFile);
	  
	  if ( isset($PluginOptions) AND $PluginOptions == 1 AND $PluginEnabled == 1) {
	   $PluginEdit = '<a href="'.OS_HOME.'adm/?plugins&amp;edit='.$PluginFile.'#'.$PluginFile.'"><img src="'.OS_HOME.'adm/edit.png" alt="edit" width="16" height="16" /> Edit</a>';
	   
	   if ( isset($_GET["edit"]) AND $_GET["edit"] == $PluginFile) {
	   $PluginEdit = '<a href="'.OS_HOME.'adm/?plugins#'.$PluginFile.'"><img src="'.OS_HOME.'adm/edit.png" alt="edit" width="16" height="16" /> &laquo; Edit</a>';
	   }
	   
	  } else { $PluginEdit = ''; }
	  
	  $color = 'D57272';
	  if ( $PluginEnabled == 1) $color = '72D57A'; else $color = '665858';
	  ?>
	  <tr style="height: 45px;">
	    <td class="padLeft">
		<a href="javascript:;" name="<?=$PluginFile?>"></a>
		<div style="margin-bottom:16px;">
		  <span style="font-weight: bold; color: #<?=$color?>"><?=$PluginName?></span>
		  <div style="font-size:11px;"><?=$PluginDesc?></div>
		</div>
		</td>
		<td><?php 
		if ($PluginEnabled == 1) {
		?><a href="javascript:;" onclick="if ( confirm('Disable plugin?') ) { location.href='<?=$website?>adm/?plugins&amp;disable=<?=$PluginFile?>#<?=$PluginFile?>' }"><img class="imgvalign" src="<?=$website?>adm/check.png" alt="" width="16" height="16" /> Disable</a><?php
		} else {
		?><a href="javascript:;" onclick="if ( confirm('Enable plugin?') ) { location.href='<?=$website?>adm/?plugins&amp;enable=<?=$PluginFile?>#<?=$PluginFile?>' }"><img class="imgvalign" src="<?=$website?>adm/uncheck.png" alt="" width="16" height="16" /> Enable</a><?php
		}
		?></td>
		<td><?=$PluginAuthor?></td>
		<td>
		<?php 
		//"trick" to properly display plugin option (cause of fixed menu). 
		//if user click on edit just set margin 
		if ( isset($_GET["edit"]) AND $_GET["edit"] == $PluginFile AND isset($Option) ) { ?>
		<div style="margin-top: 30px;">&nbsp;</div>
		<?php } ?>
		  <?=$PluginEdit?>
		  <a style="float:right; margin-right:10px;" href="javascript:;" onclick="if ( confirm('Delete plugin?') ) { location.href='<?=$website?>adm/?plugins&amp;delete=<?=$PluginFile?>' }"><img class="imgvalign" src="<?=$website?>adm/del.png" alt="" width="16" height="16" /></a>
		<?php 
		if ( isset($_GET["edit"]) AND $_GET["edit"] == $PluginFile AND isset($Option) ) {
		?>
		<div style="margin-bottom: 90px;">Options:
		    <div><?=$Option?></div>
		</div>
		<?php
		}
		?>
		  
		</td>
	  </tr>
	  <?php
	  if ( isset($PluginOptions) ) { unset($PluginOptions); unset($Option); }
		  }
	    }
	  }
	}
?>
</table>
<?php
}
?>
<div style="margin-bottom: 250px;">&nbsp;</div>
</div>