<?php
if (!isset($website) ) {header('HTTP/1.1 404 Not Found'); die; }
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
 	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="content-style-type" content="text/css" />
	<meta name="author" content="Ivan Antonijevic" />
	<meta name="rating" content="Safe For Kids" />
 	<meta name="description" content="<?=$HomeDesc?>" />
	<meta name="keywords" content="<?=$HomeKeywords?>" />
	<?=os_add_meta()?>
	
	<title><?=$HomeTitle?></title>
	
<link rel="index" title="<?=OS_HOME_TITLE?>" href="<?=OS_HOME?>" />
<link rel="stylesheet" href="<?=OS_HOME?><?=OS_CURRENT_THEME_PATH?>style.css" type="text/css" />
<script type="text/javascript" src="<?=OS_HOME?>scripts.js"></script>
<?php os_js() ?>
<?php os_head(); ?>
</head>
