<?php
/* Called from top.php */

include_once 'top_Ext_Head.php';
?>

<div style="height:42px; width:100%; background:#003366; margin:0; padding:0;" id="uaheader">
 <a href="http://www.arizona.edu/" target="_blank" tabindex="-1"><img src="/img/logos/uaLogo.gif" alt="The University of Arizona Home Page" border="0" align="left" style="padding-left:15%;padding-top:1px;" /></a>
</div>

<div id="content">

<table width="100%" border="0" cellpadding="0" cellspacing="0" id="printBlocker">
<tr>
<td align="right" style="padding-top:1px;">
<br />

<table border="0" cellpadding="0" cellspacing="0" style="white-space:nowrap;">

  <tr>

	<td valign="middle" style="padding:0;">
	 <a href="/"><img src="/img/logos/pts.gif" width="366" height="25" border="0" alt="Parking and Transportation Home Page" /></a>
	</td>

	<td valign="middle" style="padding:0; width:227px">

	<?php
	include_once $_SERVER['DOCUMENT_ROOT'].'/search.php';
	?>

	</td>
  </tr>

<?php
if ($path_parts['uri'] != '/index.php' && $path_parts['uri'] != '/index.dev.php')
{
	?>
	<tr>
	<td colspan="2" align="left" valign="middle" style="padding-top:3px;">
	 <img src="/img/logos/menu_top_new.gif" width="431" height="15" alt="Top Menu Bar" border="0" usemap="#menuMap" id="menuMap" />
	 <map name="menuMap">
		 <area shape="rect" coords="0,1,56,14" href="/account/index.php" alt="Quick Links" />
		 <area shape="rect" coords="65,0,138,16" href="/transportation/index.php" alt="Transportation" />
		 <area shape="rect" coords="149,0,187,16" href="/account/index.php" alt="Permits" />
		 <area shape="rect" coords="199,1,282,15" href="/parking/index.php" alt="Parking &amp; Maps" />
		 <area shape="rect" coords="292,1,347,17" href="/about/index.php" alt="About PTS" />
		 <area shape="rect" coords="358,0,431,16" href="/news/index.php" alt="News &amp; Media" />
	 </map>
	</td>
	</tr>
	<?php
}
?>

</table>


</td>
</tr>

</table>

<div id="bodydiv">


<?php
if (@$_SERVER['HTTPS'] && (@sizeof($_SESSION['entity']) || @sizeof($_SESSION['eds_data'])))
{
	?>
	 <span style="float:right;">
	  <input type="button" value="Logout" onclick="document.location.href='/index.php?logout=1'" class="submitterBtnRed" style="font-weight:bold; font-style:italic; cursor:pointer;" />
	 </span>
	<?php
}


if (@$GLOBALS['WINTER_SHUTDOWN'])
{
	?>
	<br />
	<table width="750" border="3" cellspacing="0" cellpadding="5" style="padding:0; border:0; border-bottom:1px solid grey;">
	 <tr>
		<td style="padding:0;">
		 <table width="100%" border="0" cellpadding="0" cellspacing="0" style="padding:0; border:0;">
			<tr>
			 <td width="16" height="25" class="menu_NW" style="padding:0;"><img src="/img/blankPix.gif" width="16" height="25"></td>
			 <td class="menu_N" valign="middle" style="padding:0;">University Winter Shutdown</td>
			 <td width="16" height="25" class="menu_NE" style="padding:0;"><img src="/img/blankPix.gif" width="16" height="25"></td>
			</tr>
		 </table>
		</td>
	 </tr>
	 <tr>
		<td align="left" valign="top" bgColor="#ffffff" style="font-size:14px; padding:0; padding-top:3px; padding-bottom:7px; padding-left:10px; padding-right:5px; border-left:1px solid grey; border-right:1px solid grey;">
		<?php echo $GLOBALS['WINTER_SHUTDOWN_MSG']; ?>
		</td>
	 </tr>
	</table>
	<?php
}
?>

<?php
if ($GLOBALS['WEBSITE_DOWN'])
{
	// This is just to put WEBSITE_DOWN_MSG at top of page.
	checkEnabled('xxxxxxxxxxxxxxxxxxx');
}
?>