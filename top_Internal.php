<?php
header("Cache-Control: private");
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$mn_ymd	= date('Ymd');
$mn_m_d	= date('m/d');

//exitWithBottom('<br><br><big>Force Internal Website Shutdown or force Internal Website Error - so as to allow External web usage.');

if ($path_parts['root_base'] != 'demo')
{

// colors for cybersource links.
$GLOBALS['cyberColor'] = '#B39201';

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>
 <?php echo $thisPageTitle; /* Set in top.php */ ?>
</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<link rel="stylesheet" type="text/css" href="/css/base.css"/>
<link rel="stylesheet" type="text/css" media="print" href="/css/printable.css"/>
<script language="JavaScript" type="text/javascript" src="/js/base.js"></script>
<script language="JavaScript" type="text/javascript" src="/js/spin.js"></script>


<!-- INTERNAL and EXTERNAL web sites -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<!-- COULD USE local: /js/mootools/jquery-1.8.3.js -->
<script type="text/javascript">
// Better to replace '$()' with 'jQuery()' than using noConflict
// Added noConflict so that mootools will work (jQuery conflicts with $(...) functions)
//jQuery.noConflict();
</script>

<link rel="stylesheet" media="screen" href="/js/superfish/superfish.css" />
<script src="/js/superfish/superfish.js"></script>

</head>
<body>


<table width="100%" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#cc0033; margin:0px; padding:0px;" id="uaheader">
 <tr>

  <td width="1" style="margin:0; padding:0; padding-right:14px;">
	<a href="http://www.arizona.edu/"><img src="/images/logos/uaLogoBlueBoxSm.gif" alt="PTS Intranet" style="border:1px solid white;" /></a>
  </td>

  <td valign="middle" style="color:#fff; font-size:16px; padding:0; padding-right:16px;">
	<a href="/" style="font-weight:bold; color:#fff;">PTS Intranet</a>
	<div style="font-size:12px; padding:0; padding-top:3px;">not public accessible</div>
  </td>

  <td align="right" valign="bottom" id="XXXinternalMenu" style="margin:0; padding:0; white-space:nowrap;">



<ul class="sf-menu">
 <li>
  <a href="/services/index.php">Services</a>
	<ul>
	<li><a href="/services/index.php">Services</a></li>
	<li><a href="/time_sheets.php">Time Sheets</a></li>
	<li><a href="/service_request.php">Service Request</a></li>
	<li><a href="/docs/index.php">Documents</a></li>
	<li><a href="/maps/index.php">Maps</a></li>
</ul>
</li>

<li><a href="/divisions/index.php">Divisions</a>
<ul>
	<li><a href="/divisions/index.php">Divisions</a></li>
	<li><a href="/administration/index.php">Administration</a></li>
	<li><a href="/administration/marketing.php">Marketing</a></li>
	<li><a href="/alternative_transportation/index.php">Alternative Transportation</a></li>
	<li><a href="/appeals/index.php">Appeals</a></li>
	<li><a href="/business_office/index.php">Business Office</a></li>
	<li><a href="/customer_relations/index.php">Customer Relations</a></li>
	<li><a href="/enforcement/index.php">Enforcement</a></li>
	<li><a href="/field_operations/index.php">Field Operations</a></li>
	<li><a href="/garage_cashiering/index.php">Garage Cashiering</a></li>
	<li><a href="/mis/index.php">Information Technology</a></li>
	<li><a href="/special_events/index.php">Special Events</a></li>
	<li><a href="/visitors/index.php">Visitor Programs</a></li>
</ul>
</li>

<li><a href="/logs/index.php">Web Transactions</a>
<ul>
	<li><a href="/logs/index.php">Web Transactions</a></li>

	<li style="white-space:nowrap;"><table><tr>
		<td><a href="/logs/index.php?type=1">Appeals</a></td>
		<td style="padding:0 4px 0 4px;"> | </td>
		<td><a href="/logs/index.php?type=1&yyyymmdd=<?php echo $mn_ymd;?>" style="font-style:italic;"><?php echo $mn_m_d;?></a></td>
	</tr></table></li>

	<li style="white-space:nowrap;"><table><tr>
		<td><a href="/logs/index.php?type=7&info=21">Payroll Registrations</a></td>
		<td style="padding:0 4px 0 4px;"> | </td>
		<td><a href="/logs/index.php?type=7&info=21&yyyymmdd=<?php echo $mn_ymd;?>" style="font-style:italic;"><?php echo $mn_m_d;?></a></td>
	</tr></table></li>

	<li style="white-space:nowrap;"><table><tr>
		<td><a href="/logs/index.php?type=2">Citation Payments</a></td>
		<td style="padding:0 4px 0 4px;"> | </td>
		<td><a href="/logs/index.php?type=2&yyyymmdd=<?php echo $mn_ymd;?>" style="font-style:italic;"><?php echo $mn_m_d;?></a></td>
	</tr></table></li>

	<li style="white-space:nowrap;"><table><tr>
		<td><a href="/logs/index.php?type=3">Credit Card Payments</a></td>
		<td style="padding:0 4px 0 4px;"> | </td>
		<td><a href="/logs/index.php?type=3&yyyymmdd=<?php echo $mn_ymd;?>" style="font-style:italic;"><?php echo $mn_m_d;?></a></td>
	</tr></table></li>

	<li style="white-space:nowrap;"><table><tr>
		<td><a href="/logs/index.php?type=5">Customer Records</a></td>
		<td style="padding:0 4px 0 4px;"> | </td>
		<td><a href="/logs/index.php?type=5&yyyymmdd=<?php echo $mn_ymd;?>" style="font-style:italic;"><?php echo $mn_m_d;?></a></td>
	</tr></table></li>

	<?php /**********************************
	<li style="white-space:nowrap;"><table><tr>
		<td><a href="/logs/index.php?type=8">Customer Login Activity</a></td>
		<td style="padding:0 4px 0 4px;"> | </td>
		<td><a href="/logs/index.php?type=8&yyyymmdd=<?php echo $mn_ymd;?>" style="font-style:italic;"><?php echo $mn_m_d;?></a></td>
	</tr></table></li>
	 ***************************************/ ?>

	<li style="white-space:nowrap;"><table><tr>
		<td><a href="/logs/index.php?type=14">Miscellaneous Fees</a></td>
		<td style="padding:0 4px 0 4px;"> | </td>
		<td><a href="/logs/index.php?type=14&yyyymmdd=<?php echo $mn_ymd;?>" style="font-style:italic;"><?php echo $mn_m_d;?></a></td>
	</tr></table></li>

	<li style="white-space:nowrap;"><table><tr>
		<td><a href="/logs/index.php?type=13">Payment Plan</a></td>
		<td style="padding:0 4px 0 4px;"> | </td>
		<td><a href="/logs/index.php?type=13&yyyymmdd=<?php echo $mn_ymd;?>" style="font-style:italic;"><?php echo $mn_m_d;?></a></td>
	</tr></table></li>

	<li style="white-space:nowrap;"><table><tr>
		<td><a href="/logs/index.php?type=7&info=19">Permit Sales</a></td>
		<td style="padding:0 4px 0 4px;"> | </td>
		<td><a href="/logs/index.php?type=7&info=19&yyyymmdd=<?php echo $mn_ymd;?>" style="font-style:italic;"><?php echo $mn_m_d;?></a></td>
	</tr></table></li>

	<li style="white-space:nowrap;"><table><tr>
		<td><a href="/logs/index.php?type=7">Permit Transactions</a></td>
		<td style="padding:0 4px 0 4px;"> | </td>
		<td><a href="/logs/index.php?type=7&yyyymmdd=<?php echo $mn_ymd;?>" style="font-style:italic;"><?php echo $mn_m_d;?></a></td>
	</tr></table></li>

	<li style="width:120%; text-align: left; border-bottom:2px #929AAF solid;"><a href="/logs/index.php?type=4&info=22">Pick-Up Requests</a></li>
	<li style="width:120%; text-align: left;"><a href="/logs/index.php?type=11">Web Comments</a></li>
	<li style="width:120%; text-align: left;"><a href="/logs/index.php?type=4">Address Changes</a></li>
	<li style="width:120%; text-align: left;"><a href="/logs/index.php?type=6">Form Activity</a></li>
	<li style="width:120%; text-align: left; border-bottom:2px #929AAF solid;"><a href="/logs/index.php?type=9">Vehicle Registration Changes</a></li>
	<li style="width:120%; text-align: left;"><a href="/awstats/awstats.pl?config=parking.arizona.edu">Web Stats - External</a></li>
	<li style="width:120%; text-align: left;"><a href="/awstats/awstats.pl?config=www.pts.arizona.edu">Web Stats - Internal</a></li>
	<li style="width:120%; text-align: left;"><a href="http://128.196.6.95/usage/index.php">Access Stats 2006</a></li>
	<!-- <li><a href="/logs/rawlog.php">Raw Log Search</a></li> -->
</ul>
</li>

	<li><a href="/web/index.php">Web Management</a>
<ul>
	<li style="width:120%; text-align: left;">
	 <a href="/web/index.php">Web Management</a></li>
	<li style="width:120%; text-align: left;">
	 <a href="/auth/?popupWidth=0" onclick="NewWindow(this.href, 'wnId_1', 0, 0, 'yes', 1); return false" onfocus="this.blur()" style="white-space: nowrap;">User Accounts: T2-Web Service</a></li>
	<li style="width:120%; text-align: left;">
	 <a href="/auth/web-edit.php?popupWidth=0" onclick="NewWindow(this.href, 'wnId_2', 0, 0, 'yes', 1); return false" onfocus="this.blur()" style="white-space: nowrap;">User Accounts: PTS Web Editing</a></li>
	<li style="width:120%; text-align: left;">
	 <a href="/news/index.php?popupWidth=900" onclick="NewWindow(this.href, 'wnId_3', 900, 0, 'yes', 1); return false" onfocus="this.blur()" style="white-space: nowrap;">News Manager</a></li>
	<li style="width:120%; text-align: left;">
	 <a href="/parking/garage_reservation/administrator/index.php" style="white-space: nowrap;">Visitor Reservations</a></li>
	<!-- <li style="width:120%; text-align: left;">
	 <a href="/web/options.php?popupWidth=550" onclick="NewWindow(this.href, 'wnId_4', 550, 0, 'yes', 1); return false" onfocus="this.blur()" style="white-space: nowrap;">Web Page Options</a></li> -->
	<!-- <li style="width:120%; text-align: left;">
	 <a href="/web/forsale.php?popupWidth=550" onclick="NewWindow(this.href, 'wnId_6', 550, 0, 'yes', 1); return false" onfocus="this.blur()" style="white-space: nowrap;">Permits for Sale</a></li> -->
</ul>
</li>

</ul>

</td>
</tr>
</table>

<div id="content" style="padding-top:12px;">


<?php /************************** Internal Sub-menus **************************************/ ?>


<?php

//if ($path_parts['dirname'] != '/divisions' && $path_parts['dirname'] != '/') {
function menuDivTop()
{
	?>
	<div style="text-align:left; font-size:12px; padding:4px; margin-top:9px; margin-bottom:12px; border:solid 1px #2586D7;">
	<span style="font-weight:bold; color:#003366;">Menu: &nbsp; </span>
	<?php
}

switch ($path_parts['dirname'])
{
	case '/administration':
	case '/reports':
		menuDivTop();
		?>
		  <a href="/administration/index.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Administration Home</a>
		  <big>&middot;</big>&nbsp;<a href="/administration/hr.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Human Resources</a>
		  <big>&middot;</big>&nbsp;<a href="/administration/sops.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Standard Operating Procedures</a>
		  <!-- <big>&middot;</big>&nbsp;<a href="/reports/permitsoldcompare.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Comparison of Permits Sold</a> -->
		  <!-- <big>&middot;</big>&nbsp;<a href="/administration/survey_viewer.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Permit Survey</a> -->
		  <big>&middot;</big>&nbsp;<a href="/service_request.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Service Request</a>
			</div>
		<?php
		break;

	case '/appeals':
		menuDivTop();
		?>
			<a href="/appeals/index.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Department Home</a>
			<big>&middot;</big>&nbsp;<a href="/appeals/citations.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Citation Directory</a>
			<big>&middot;</big>&nbsp;<a href="/appeals/special_plates.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Arizona Specialized Plates</a>
			</div>
		<?php
		break;

	case '/alternative_transportation':
		menuDivTop();
		?>
			<a href="/alternative_transportation/index.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Alternative Transportation</a>
			<big>&middot;</big>&nbsp;<a href="/alternative_transportation/fleet.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Fleet</a>
			</div>
		<?php
		break;

	case '/business_office':
		menuDivTop();
		?>
		   <a href="/business_office/index.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Business Office Home</a>
			<big>&middot;</big>&nbsp;<a href="/business_office/sis_posting.php" style="white-space:nowrap; padding:3px; font-weight:bold;">SIS Description Protocols</a>
			<big>&middot;</big>&nbsp;<a href="/business_office/powerpark_citations.php" style="white-space:nowrap; padding:3px; font-weight:bold;">PowerPark Citation Status Codes</a>
			<big>&middot;</big>&nbsp;<a href="/business_office/jobs.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Job Editor</a>
			<big>&middot;</big>&nbsp;<a href="/service_request.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Service Request</a>
			</div>
		<?php
		break;

	case '/customer_relations':
		menuDivTop();
		?>
		   <a href="/customer_relations/index.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Customer Relations Home</a>
			<big>&middot;</big>&nbsp;<a href="/customer_relations/complaints.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Customer Complaint Form</a>
			<big>&middot;</big>&nbsp;<a href="/customer_relations/complaintreport.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Complaint Reports</a>
			<big>&middot;</big>&nbsp;<a href="/customer_relations/error_messages.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Web Error Messages</a>
			<big>&middot;</big>&nbsp;<a href="/customer_relations/sis_posting.php" style="white-space:nowrap; padding:3px; font-weight:bold;">SIS Description Protocols</a>
			<big>&middot;</big>&nbsp;<a href="/customer_relations/powerpark_citations.php" style="white-space:nowrap; padding:3px; font-weight:bold;">PowerPark Citation Status Codes</a>
			<big>&middot;</big>&nbsp;<a href="/customer_relations/va_voc_rehab.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Chapter 31 VA VOC REHAB student list</a>
			<big>&middot;</big>&nbsp;<a href="/customer_relations/interactive/permitpurchase/step1.html" style="white-space:nowrap; padding:3px; font-weight:bold;">Online Permit Purchase Tutorial</a>
			<big>&middot;</big>&nbsp;<a href="/customer_relations/reserve-upload.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Reserved Permit List Upload</a>
			<big>&middot;</big>&nbsp;<a href="http://parking.arizona.edu/parkingmap/corridor-check.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Shuttle Corridor Address Verification</a>
			<big>&middot;</big>&nbsp;<a href="/customer_relations/coin_counting.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Coin Counting Schedule</a>
			<big>&middot;</big>&nbsp;<a href="/service_request.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Service Request</a>
			</div>
		<?php
		break;

	case '/enforcement':
		menuDivTop();
		?>
			<a href="/enforcement/index.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Enforcement Home</a>
			<big>&middot;</big>&nbsp;<a href="/enforcement/citations.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Citation Directory</a>
			<big>&middot;</big>&nbsp;<a href="/enforcement/status_codes.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Citation Status Codes</a>
			<big>&middot;</big>&nbsp;<a href="/enforcement/radio_code.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Radio Code List</a>
			<big>&middot;</big>&nbsp;<a href="/service_request.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Service Request</a>
			</div>
		<?php
		break;

	case '/field_operations':
		menuDivTop();
		?>
			<a href="/field_operations/index.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Field Operations Home</a>
			<big>&middot;</big>&nbsp;<a href="/field_operations/ahsc_notices.php" style="white-space:nowrap; padding:3px; font-weight:bold;">AHSC Notices</a>
			<big>&middot;</big>&nbsp;<a href="/field_operations/lot_closures.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Current Lot Closure Notices</a>
			<big>&middot;</big>&nbsp;<a href="/field_operations/bike_maps.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Bike Route Maps</a>
			<big>&middot;</big>&nbsp;<a href="/field_operations/motorcycle_maps.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Motorcycle Maps</a>
			<big>&middot;</big>&nbsp;<a href="/field_operations/garage_maps.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Garage Maps</a>
			<big>&middot;</big>&nbsp;<a href="/field_operations/meter_pay.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Meter Pay By Space Maps </a>
			<big>&middot;</big>&nbsp;<a href="/field_operations/lot_maps.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Lot Maps</a>
			<big>&middot;</big>&nbsp;<a href="/field_operations/lot_address_wb.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Lot Address Workbook</a>
			<big>&middot;</big>&nbsp;<a href="/servicerequest/index.php?view=2" style="white-space:nowrap; padding:3px; font-weight:bold;">Administer Service Requests</a>
			<big>&middot;</big>&nbsp;<a href="/service_request.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Create Service Request</a>
			<big>&middot;</big>&nbsp;<a href="/field_operations/upload.php" style="white-space:nowrap; padding:3px; font-weight:bold;">Upload Files Page</a>
			</div>
		<?php
		break;

	case '/garage_cashiering':
		menuDivTop();
		?>
			<a href="/garage_cashiering/index.php" style="white-space:nowrap;">Garage Cashiering Home</a>
			&nbsp; <big>&middot;</big> &nbsp; <a href="/garage_cashiering/garages.php" style="white-space:nowrap;">Garage Maps (Entry/Exit)</a>
			&nbsp; <big>&middot;</big> &nbsp; <a href="http://parking.arizona.edu/marriott/" style="white-space:nowrap;">Marriott Pass Lookup</a>
			&nbsp; <big>&middot;</big> &nbsp; <a href="/garage_reservation/cashier/" style="white-space:nowrap;">Garage Reservations</a>
			</div>
		<?php
		break;

	case '/mis':
		menuDivTop();
		?>
			<a href="/mis/index.php" style="white-space:nowrap;">Information Technology Department Home</a>
			&nbsp; <big>&middot;</big> &nbsp; <a href="/service_request.php" style="white-space:nowrap;">Service Request</a>
			</div>
		<?php
		break;

	case '/special_events':
		menuDivTop();
		?>
			<a href="/special_events/index.php" style="white-space:nowrap;">Special Events Home</a>
			&nbsp; <big>&middot;</big> &nbsp; <a href="/service_request.php" style="white-space:nowrap;">Service Request</a>
			</div>
		<?php
		break;

	case '/visitors':
		menuDivTop();
		?>
			<a href="/visitors/index.php" style="white-space:nowrap;">Visitor Programs Home</a>
			&nbsp; <big>&middot;</big> &nbsp; <a href="/service_request.php" style="white-space:nowrap;">Service Request</a>
			&nbsp; <big>&middot;</big> &nbsp; <a href="/visitors/upload.php" style="white-space:nowrap;">Upload Files Page</a>
			</div>
		<?php
		break;

}
?>

<script>
jQuery(document).ready(function(){
	jQuery('ul.sf-menu').superfish();
});
</script>

<?php

}

include_once 'top_Debug.php';

?>