<?php
if (!isset($website) ) {header('HTTP/1.1 404 Not Found'); die; }
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="author" content="Ivan Antonijevic" />
	<meta name="rating" content="Safe For Kids" />
 	<meta name="description" content="<?=$HomeDesc?>" />
	<meta name="keywords" content="<?=$HomeKeywords?>" />
	<?=os_add_meta()?>
	
    <title><?=$HomeTitle?></title>

    <link rel="stylesheet" href="<?=OS_HOME?><?=OS_CURRENT_THEME_PATH?>style.css" type="text/css" />
	<link rel="index" title="<?=OS_HOME_TITLE?>" href="<?=OS_HOME?>" />

<!--[if lt IE 7]>
	<link rel="stylesheet" type="text/css" href="<?=OS_HOME?>themes/<?=OS_THEMES_DIR?>/ie6style.css" />
	<script type="text/javascript" src="<?=OS_HOME?>themes/<?=OS_THEMES_DIR?>/DD_belatedPNG_0.0.8a-min.js"></script>
	<script type="text/javascript">DD_belatedPNG.fix('img#logo, #cat-nav-left, #cat-nav-right, #search-form, #cat-nav-content, div.top-overlay, .slide .description, div.overlay, a#prevlink, a#nextlink, .slide a.readmore, .slide a.readmore span, .recent-cat .entry .title, #recent-posts .entry p.date, .footer-widget ul li, #tabbed-area ul#tab_controls li span');</script>
<![endif]-->
<!--[if IE 7]>
	<link rel="stylesheet" type="text/css" href="<?=OS_HOME?>themes/<?=OS_THEMES_DIR?>/ie7style.css" />
<![endif]-->
<!--[if IE 8]>
	<link rel="stylesheet" type="text/css" href="<?=OS_HOME?>themes/<?=OS_THEMES_DIR?>/ie8style.css" />
<![endif]-->

<script type="text/javascript">
	document.documentElement.className = 'js';
</script>

<script type="text/javascript" src="<?=OS_HOME?>scripts.js"></script>
<script type='text/javascript' src='<?=OS_HOME?>themes/<?=OS_THEMES_DIR?>/resources/jquery-1.7.2.js'></script>

<style type="text/css">.recentcomments a{display:inline !important;padding:0 !important;margin:0 !important;}</style>
	<style type="text/css">
		#et_pt_portfolio_gallery { margin-left: 2px; }
		.et_pt_portfolio_item { margin-left: 11px; }
		.et_portfolio_small { margin-left: -14px !important; }
		.et_portfolio_small .et_pt_portfolio_item { margin-left: 22px !important; }
		.et_portfolio_large { margin-left: -12px !important; }
		.et_portfolio_large .et_pt_portfolio_item { margin-left: 13px !important; }
	</style>
<?php os_js() ?>	
<?php os_head(); ?>
</head>
