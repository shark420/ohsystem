<?php
if (!isset($website) ) { header('HTTP/1.1 404 Not Found'); die; }
?>
  <div class="main-nav-main" style="position:fixed; top: 0px; left: 0px;">
   <div class="ct-wrapper">
<ul class="dd_menu">
  <li><a class="menuButtons" href="<?=OS_HOME?>adm/">Dashboard</a>
    <ul>
	  <li><a href="<?=OS_HOME?>adm/?cfg">Configuration</a> </li>
	</ul>
  </li>
  <li><a class="menuButtons" href="<?=OS_HOME?>adm/?games">Games</a></li>
  <li><a class="menuButtons" href="<?=OS_HOME?>adm/?players">Players</a></li>
  <li><a class="menuButtons" href="<?=OS_HOME?>adm/?posts">Posts</a></li>
  <li><a class="menuButtons" href="<?=OS_HOME?>adm/?comments">Comments</a></li>
  <li><a class="menuButtons" href="<?=OS_HOME?>adm/?users">Members</a></li>
  <li>
  <a class="menuButtons" href="<?=OS_HOME?>adm/?bans">Bans</a>
    <ul>
	  <li><a href="<?=OS_HOME?>adm/?warns">Warns</a></li>
	  <li><a href="<?=OS_HOME?>adm/?ban_reports">Ban Reports</a></li>
	  <li><a href="<?=OS_HOME?>adm/?ban_appeals">Ban Appeals</a></li>
	</ul>
  </li>
  <li><a class="menuButtons" href="<?=OS_HOME?>adm/?admins"><?=$lang["admins"]?></a></li>
  <!--// <li><a class="menuButtons" href="<?=OS_HOME?>adm/?menu">Menu editor</a></li> //-->
  <li><a class="menuButtons" href="<?=OS_HOME?>adm/?plugins">Plugins</a></li>
  <li><a class="menuButtons" href="javascript:;">Misc</a>
     <ul>
	   <li><a href="<?=OS_HOME?>adm/?guides">Guides</a></li>
	 <?php if (isset($GameListPatch) AND $GameListPatch==1) { ?>
	   <li><a href="<?=OS_HOME?>adm/?gamelist">Gamelist</a></li>
	 <?php } ?>
	   <li><a href="<?=OS_HOME?>adm/?heroes"><?=$lang["heroes"]?></a></li>
	   <li><a href="<?=OS_HOME?>adm/?items"><?=$lang["items"]?></a></li>
	   <li><a href="<?=OS_HOME?>adm/?about_us"><?=$lang["about_us"]?></a></li>
	   <li><a href="<?=OS_HOME?>adm/?safelist"><?=$lang["safelist"]?></a></li>
	   <li><a href="<?=OS_HOME?>adm/?notes">Notes</a></li>
	   <li><a href="<?=OS_HOME?>adm/?optimize_tables">Optimize Tables</a></li>
	 </ul>
  </li>
  
  <li><a class="menuButtons" href="<?=OS_HOME?>adm/?logout"><?=substr($_SESSION["username"],0,20)?></a>
    <ul>
	  <li><a href="<?=OS_HOME?>adm/?users&amp;edit=<?=$_SESSION["user_id"]?>">Edit Account</a></li>
	  <li><a href="<?=OS_HOME?>">Go to OS&raquo; </a></li>
	  <li><a href="<?=OS_HOME?>adm/?logout">(logout)</a></li>
	</ul>
  </li>
</ul> 

</div>
</div>