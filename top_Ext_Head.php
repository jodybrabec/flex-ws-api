<?php
/* Called from top_External .php and top_Mobile .php, also called from top_inc .php for minimal web layout. */
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>
 <?php echo $thisPageTitle;?>
</title>

<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>

<link rel="stylesheet" type="text/css" href="/css/base.css"></link>
<link rel="stylesheet" type="text/css" media="print" href="/css/printable.css"></link>

<script language="JavaScript" type="text/javascript" src="/js/base.js"></script>
<script language="JavaScript" type="text/javascript" src="/js/spin.js"></script>

</head>
<body>

<?php if (@!$GLOBALS['WEBSITE_DOWN_MSG']) { ?>
	<!-- INTERNAL, GR, and EXTERNAL web sites -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
	<!-- COULD USE local: /js/mootools/jquery-1.8.3.js -->
	<script type="text/javascript">
	// Better to replace '$()' with 'jQuery()' than using noConflict
	// Added noConflict so that mootools will work (jQuery conflicts with $(...) functions)
	//jQuery.noConflict();
	</script>
<?php } ?>

<?php
include_once 'top_Debug.php';
?>
