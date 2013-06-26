<?php
	include("../config.php");
	
  $time = microtime();
  $time = explode(' ', $time);
  $time = $time[1] + $time[0];
  $start = $time;
	
	include("../inc/common.php");
	require_once('../lang/'.$default_language.'.php');
	include("../inc/class.db.PDO.php");
	//include("../inc/class.database.php");
	//$DBDriver ="mysql";
	include("../inc/db_connect.php");
	
if ( file_exists('../themes/'.$DefaultStyle.'/functions.php') )
include('../themes/'.$DefaultStyle.'/functions.php');


	include("admin_func.php");
	include("admin_sys.php");
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

 	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="content-style-type" content="text/css" />
	<meta name="author" content="Ivan Antonijevic" />
	<meta name="rating" content="Safe For Kids" />
 	<meta name="description" content="<?=$HomeDesc?>" />
	<meta name="keywords" content="<?=$HomeKeywords?>" />
	<title><?=$HomeTitle?></title>
	<?php if (file_exists("../themes/blogger/style.css") ) { ?>
	<link rel="stylesheet" href="<?=OS_HOME?>themes/blogger/style.css" type="text/css" />
	<?php } ?>
	<link rel="stylesheet" href="<?=OS_HOME?>adm/style.css" type="text/css" />
    <script type="text/javascript" src="<?=OS_HOME?>scripts.js"></script>
<?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
    <script type="text/javascript" src="<?php echo OS_HOME;?>adm/ckeditor/ckeditor.js"></script>
<?php } ?>
    <script type="text/javascript">
	function toggle(source) {
    checkboxes = document.getElementsByName('checkbox[]');
    for(var i in checkboxes)
    checkboxes[i].checked = source.checked;
    }
	</script>
</head>
  
<body>

<?php if (is_logged() AND isset($_SESSION["level"] ) AND $_SESSION["level"]>=9 ) { ?>
<div id="wrapper">
<div id="logo" style="margin-top: 45px;">
  <h1>ADMINISTRATION</h1>
</div>  

<?php include("admin_menu.php"); ?>

<div align="center" style="background-color: #fff; width: 960px; margin: 0 auto; padding-top: 18px; border: 10px solid #2B0202; border-radius: 10px;">
<?php include('admin_pages.php'); ?>
</div>
<div style="margin-bottom: 60px;">&nbsp; </div>
<?php
include('../themes/'.$DefaultStyle.'/footer.php');
?>
</div>
<?php } else {  include("login.php"); } ?>