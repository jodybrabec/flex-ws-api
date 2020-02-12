<?php
/********************************************************************************
 * top.inc.php - called by /var/www/html/include/top.php
 * EXTERNAL Web site.
 * ********************************************************************************** */


if (preg_match('/^\/parking\/garage-reservation\/cashier\//si', $_SERVER['PHP_SELF']))
	locationHref('https://www.pts.arizona.edu/garage_reservation/cashier/');


/****
 * WEBSITE DOWN CONFIG AND OUTPUT MESSAGES, ALSO FOR WINTER SHUTDOWN
 */
websiteDownConfig();



/******************************* Available Web Services array ********************
 *
 * Set or un-set various web services.
 * checkEnabled uses this array, and does checks-n-balances.
 */
$servicesAvailable = array();

// Renders any FALSE $servicesAvailable to TRUE - for testing.
$GLOBALS['debug_web_service'] = array(); // Example: $GLOBALS['debug_web_service'][]	= 'Marriott App';

$servicesAvailable['Marriott App']		= true; // marriott/
$servicesAvailable['Marriott Reports']	= true; // marriott/admin.php
$servicesAvailable['Bicycle Search']	= false; // bikereg/search.php

$servicesAvailable['Garage Full App']	= true; // parking/visitor.php  AND  PTS employees /parking/garage-full-app/
$servicesAvailable['Garage Reservaton']= true; // shortWinterClosure(); // garage-reservation/
$servicesAvailable['Garage Coupon']		= false; // garage-reservation/customer/coupon.php
$servicesAvailable['News and Media']	= true; // /news/index.php


$GLOBALS['OPEN_REG_START']	= '2016-04-25 08:00:00'; // also used in account/special_conditions.php
$GLOBALS['CLOSE_ALL_REG']	= '2016-04-22 16:00:00'; // also used in home page index.php

//if (withinTimeframe($GLOBALS['CLOSE_ALL_REG'],$GLOBALS['OPEN_REG_START']))	{
//	$servicesAvailable['Renew Payroll'] = false;	// /regpd/  jumps to /account/regnotice.php?per_reg=renewpayroll
//	$servicesAvailable['Renew Current'] = false;	// /regcur/ jumps to /account/regnotice.php?per_reg=renewcurrent
//	$servicesAvailable['Open Registration'] = false;
//}
//else
if (withinTimeframe($GLOBALS['OPEN_REG_START'],'2017-03-01 23:59:59')) {
	//$servicesAvailable['Permit Application'] = false; // account/application.php -- see permits/application.php
	$servicesAvailable['Renew Payroll'] = false;	// /regpd/  jumps to /account/regnotice.php?per_reg=renewpayroll
	$servicesAvailable['Renew Current'] = false;	// /regcur/ jumps to /account/regnotice.php?per_reg=renewcurrent
	$servicesAvailable['Open Registration'] = true;
}
//else {
//	$servicesAvailable['Renew Payroll'] = true;	// /regpd/  jumps to /account/regnotice.php?per_reg=renewpayroll
//	$servicesAvailable['Renew Current'] = true;	// /regcur/ jumps to /account/regnotice.php?per_reg=renewcurrent
//	$servicesAvailable['Open Registration'] = false;
//}


// account/waitlist.php and /index.php -- If person is doing 'Open Registration', then
//		Check Waitlist will be enabled essentially in waitlist .php
$servicesAvailable['Check Waitlist']		= false;

// These next two are used in:  account/permit_purchase.php?referral=perpurchase  and in:  account/permit_payoff.php
$servicesAvailable['Permit Purchase']	= true;
// Should we sell a free I-permit (RFID) and assign it to the sold garage permit?
$servicesAvailable['I-Permits']			= true;
// This applies to Cat Cards too: If 'I-Permits In Permit RFID' is false then customer will not be assigned the [free] I-Permit (RFID number) within his
//	purchased permit's custom RFID field - see Flex_Permissions .php.  Because it's too slow!!!  But, the Entity RFID custom field will still be updated with RFID number.

// If $servicesAvailable['I-Permits'] is true, then do we want to sell New I-permits?  If false, then only EXISTING I-permits will be updated.
$servicesAvailable['New-I-Permits']		= false;

$servicesAvailable['I-Permits In Permit RFID']	= true; // This applies to Cat Cards too
// If 'First Choice Only' is true then only Priority One permits can be sold.
$servicesAvailable['First Choice Only']	= false; //  withinTimeframe('2010-01-01 00:00:00','2016-08-01 02:59:59') ? true : false;

// Allow to generate PDF temp permits. -- Flex_Permissions .php
$servicesAvailable['Temporary Permits']	= withinTimeframe('2016-01-01 00:00:00','2017-03-01 23:59:59') ? true : false;

$servicesAvailable['Motorcycle Purchase'] = false; // account/mc_purchase.php?referral=mcpurchase
$servicesAvailable['Motorcycle Purchase Payroll'] = false; // account/mc_purchase.php?referral=mcpurchase

// u-pass (aka suntran, sun tran) -- account/bus_purchase.php?referral=buspurchase
$servicesAvailable['U-Pass Purchase'] = true; // withinTimeframe('2014-01-01 00:00:00','2016-03-31 23:59:59') ? true : false;
// Allows employee payroll ('U-Pass Purchase' must also be true)
$servicesAvailable['U-Pass Purchase Payroll'] = true; // withinTimeframe('2014-01-01 00:00:00','2016-03-31 23:59:59') ? true : false;
// Enable selling of Mobile U-Pass
$servicesAvailable['U-Pass Mobile Purchase'] = true; // ('U-Pass Purchase' must also be true)
// ucell promo -- account/bus_purchase.php?referral=buspurchase  and  U-CellPass/dir.
// $servicesAvailable['U-Cell Promo'] = withinTimeframe('2010-01-01 00:00:00','2015-09-06 00:00:00') ? true : false;

$servicesAvailable['T2 API Powerpark']	= true; // Internal/powerpark directory.

$servicesAvailable['Citation Payments']		= true; // citations/payments.php
$servicesAvailable['Citation Appeals']			= true; // citations/appeals.php
$servicesAvailable['Vehicle Registration']	= true;
$servicesAvailable['Bicycle Registration']	= true; // true; // shortWinterClosure(); // account/bicycle.php


// If false, then can only view T2 addresses of type UA.  If true, then allows to add an address to T2
// We are no longer allowing ANY editing of addresses.
$servicesAvailable['Change Address']			= false; // account/address.php

$servicesAvailable['Appeal Status']				= false; // citations/appeal_status.php (NOT WORKING YET)

$servicesAvailable['Point of Sale']				= true; // parking/opencash.php
$servicesAvailable['Transient Count']			= true; // parking/occupancy.php
$servicesAvailable['Transient Capacity']		= true; // Internal/powerpark/index.php

//if (preg_match('/\/garage-reservation\/(administrator\/|xxxxxx\/)/si', $_SERVER['PHP_SELF']))
//	$servicesAvailable['Webauth Garage']	= true; // So customers (not Admins) can use new webauth (include/login_external.php) for garage res.			//else
$servicesAvailable['Webauth Garage']	= true; // So customers (not Admins) can use new webauth (include/login_external.php) for garage res.




//if (@$_GET['editing'] == 'on') {
//	$_SESSION['editing'] = 1;
//} elseif (@$_GET['editing'] == 'off') {
//	$_SESSION['editing'] = 0;
//}

if (!@$_GET['logout'] && @!$_GET['pts_logout_note'])
{
	if (@$_SESSION['webauth_data']['netid'])
	{
		$now_sess = time();
		if (isset($_SESSION['discard_after']) && $now_sess > $_SESSION['discard_after']) {
			// this session has worn out its welcome; kill it and start a brand new one
			locationHref('/index.php?logout=44144');
			// session_unset(); session_destroy(); session_start();
		}
		// either new or old, it should live at most for 25 mins (1500 seconds)
		$_SESSION['discard_after'] = $now_sess + 1500;
	}
}




if (@!$_SERVER['HTTPS'])
{
	// If on a non-secure page, remove all private session data
	unset($_SESSION['cuinfo']);
	unset($_SESSION['pts_cuinfo']);
	unset($_SESSION['cuid']);
}

//xxxxx get rid of soon:
//to fix movement between GR and External login vars
if (isset($_SESSION['cuinfo']['auth'])) {
	$_SESSION['gr_cuinfo'] = $_SESSION['cuinfo'];
	unset($_SESSION['cuinfo']);
}
if (isset($_SESSION['pts_cuinfo'])) {
	$_SESSION['cuinfo'] = $_SESSION['pts_cuinfo'];
	unset($_SESSION['pts_cuinfo']);
}

// =========================== BEGIN Debug Settings ==============================


$debugIP_1 = @array(
	'128.196.6.66',				// Jody
	'128.196.6.10',				// JoAnn
	//'128.196.6.46',				// David
	// '128.196.6.195',			// Adam
);
/***
 * If debugging from home, then bypass $pts_ip_range (below) by setting $allow_custom_debug_ip to true
 * BUT, set the timeframe above for only a few hours into the future!!!
 */
if (withinTimeframe('2000-01-01', '2016-08-11 11:01:01')){
	$allow_custom_debug_ip = true;
	$debugIP_1[]	= '150.135.113.212';
}


if (@in_array($_SERVER['REMOTE_ADDR'], $debugIP_1))
{
	/***
	 * Made it this far.  Now limit to only PTS IPs.
	 * PTS_IP_RANGE - is the range 128.196.6.* - which says "Pts Building" in /etc/httpd/conf/httpd.internal.conf
	 * PTS_HOME_RANGE - is the range 150.135.113.* - for when connecting from home
	 * keywords: Internal, pts, employees, restricted, intranet.
	 */
	$pts_ip_range		= @preg_match('/^128.196.6\.*/si',   $_SERVER['REMOTE_ADDR']);

	if (@$_SERVER['REMOTE_ADDR'] == '150.135.113.212')	$GLOBALS['jody'] = true; // Jody - 128.196.6.66   128.196.6.46  150.135.113.212

	if ($pts_ip_range || @$allow_custom_debug_ip)	//  || @preg_match('/^150.135.113\.*/si', $_SERVER['REMOTE_ADDR'])
	{
		//=========================== Set any debug settings below.

		//-------------- PARAMETER $_GET['live_db_mode'] is set in top_Debug php - to switch db's (live or test).
		if (@$_GET['live_db_mode'] == 'is_true') {
			$_SESSION['live_db_mode'] = true;
		} else if (@$_GET['live_db_mode'] == 'is_false') {
			$_SESSION['live_db_mode'] = false;
		} else if (@!isset($_SESSION['live_db_mode'])) {
			$_SESSION['live_db_mode'] = true;
		}

		if (isset($_GET['output_debug']))
			$_SESSION['output_debug'] = $_GET['output_debug'];
		else if (@!$_SESSION['output_debug_initalized'])
		{
			$_SESSION['output_debug_initalized'] = 1; // $_SESSION['output_debug_initalized'] used only in this file.
			$_SESSION['output_debug'] = 1;
		}

		$GLOBALS['DEBUG_ERROR']		= true; // shows php & DB sql errors - usually fatal errors.

		if ($_SESSION['output_debug'])
		{
			/****
			 * output_sql_debug is set in tob_Debug .php, and used in database .php to save or show sql queries:
			 * 		If set to 1, then EVERY single sql query will be recorded in a tab-delimited csv file html/logs/database.csv.txt
			 * 		If set to 2, then every query will be displayed (if $GLOBALS['pts_user_`'] is true)
			 */
			if (isset($_GET['output_sql_debug']))
				$_SESSION['output_sql_debug'] = $_GET['output_sql_debug'];

			$GLOBALS['DEBUG_CLASSES']	= true; // Used in flex_ws/config.php, so as to allow echoing class info (via __toString() function)
			$GLOBALS['DEBUG_DEBUG']		= true; // General - placed in various files. Shows DB sql, php warnings and $debug data within .php file
			$GLOBALS['DEBUG_LOG']		= true; // For log reporting (usually pertaining to PARKING.LOG table) - see Internal/logs/
			// $GLOBALS['DEBUG_WARN']	= true;

			if (!@$_SESSION['live_db_mode'])
			{
				$GLOBALS['database_test_db'] = true;
				$GLOBALS['test_db_conn_name'] = 'n7_test';
			}

			if (($GLOBALS['database_test_db'] || $GLOBALS['jody']) && @$_POST['netidMorph'])
			{
				if (sizeof(@$_SESSION['entity']))
				{
					locationHref('/?logout=1');
					exit;
				}
				$_SESSION['netidMorph'] = ctype_alnum(trim(@$_POST['netidMorph'])) ? trim($_POST['netidMorph']) : '';
			}

			if ($GLOBALS['jody'])
			{
				// force live db even though coming from testing.  Used here and in cybersource_sa/payment_form_api_sa .php
				//$GLOBALS['live_db_with_cybersource_test_site'] = true;
				// For debuggers set any of	 these to force a service to be available - $servicesAvailable. most likely we want to test web services on test db
				//$servicesAvailable['Renew Payroll'] = false;	// /regpd/  jumps to /account/regnotice.php?per_reg=renewpayroll
				//$GLOBALS['debug_web_service'][] = 'U-Pass Purchase';
			}
		}
		else
		{
			unset($_SESSION['output_sql_debug']);
		}
	}
}


if (@$GLOBALS['live_db_with_cybersource_test_site'])
{
	// dangerous, only use when test server is down
	$_POST['req_merchant_defined_data90'] = '';
}

/**********************************************************************************
 * Cybersource is dong a Silent Order Post (SA / SOP), so no more depending on SESSION vars or IP addresses.
 * Use Cybersource's POST data to determine if is cybersource test environment? If so then switch to OUR test DB.
 * ********************************* */
if (@$_POST['req_reference_number'])
{
	/*	 * *
	 * Coming from Cybersource, Secure Acceptance.
	 * If req_merchant_defined_data90 set to the garbled text (defined in payment_form_api_sa .php), then we
	 * are testing - see also 'cyber_testing' in 'merchant_defined_data90' in payment_form_api_sa .php
	 */
	if (@$_POST['req_merchant_defined_data90'] == '541_garble_t_db') {
		// Use test db.
		$GLOBALS['database_test_db'] = true;
		$GLOBALS['test_db_conn_name'] = 'n7_test';
	}
}

// If debugger takes his ip out of $debugIP_1, then we want to make sure there is no body-snatching going on round' heah!
if (!$GLOBALS['database_test_db'] && !$GLOBALS['jody'] && @$_SESSION['netidMorph'])
{
	unset($_SESSION['netidMorph']);
	locationHref('/index.php?logout=333');
}

/******	STOPWATCH for performance testing ******
  // To run stopwatch, just insert the following in any file:
  //		if (is_object($GLOBALS['stopwatch'])) $GLOBALS['stopwatch']->show();
  //		Also, see "stopwatch" in bottom .php
  if ($GLOBALS['DEBUG_DEBUG'])
  {
  include_once 'stopwatch.php';
  $GLOBALS['stopwatch'] = new StopWatch();
  }
 * * */


//--- configure debug settings ---
massageDebug();

//******************************** END debug settings *************************************


// File names in which you want a custom <title> tag, otherwise all pages use 'DEFAULT' for page <title>
$fileTitles = array(
	 'DEFAULT' => 'UA Parking and Transportation Services (PTS)',
	 'error.php' => 'Page Not Found - Error 404'
);


// Files (or '/directory') in which you DO NOT want to make_htmlentities.
// Example for '/directory': If dir is [External|Internal]/aaa/bbb/ccc/index.php, then you can use '/aaa' (dirname_root) and '/ccc' (dirname), but NOT '/bbb'
$noHtmlEntFiles = array('/findandreplace');
$made_htmlentities = false; // global var

if (!$made_htmlentities) {
	if (!in_array($path_parts['basename'], $noHtmlEntFiles) && !in_array($path_parts['dirname'], $noHtmlEntFiles) && !in_array($path_parts['dirname_root'], $noHtmlEntFiles)) {
		make_htmlentities($_POST);
		make_htmlentities($_GET);
	}
}


if (@isset($_GET['internal_typ']) && $_GET['internal_dta'])
	jump2internal();

require_once 'debug_trace.php';

require_once 'event_log.php';
require_once 'log.php';


if (@!$_GET['noTopBottom'])
{
	// Set custom page title.
	if (isset($fileTitles[$path_parts['basename']])) {
		$thisPageTitle = $fileTitles[$path_parts['basename']];
	} else {
		$thisPageTitle = $fileTitles['DEFAULT'];
	}

	if (in_array($path_parts['basename'], $noIncFiles) || in_array($path_parts['dirname'], $noIncFiles) ||
		in_array($path_parts['dirname_root'], $noIncFiles) || in_array($path_parts['dirname'] . '/' . $path_parts['basename'], $noIncFiles))
	{
		// get rid of noTopBottom some day.
		$inc_top_bottom = false;
	}

	if ($inc_top_bottom)
	{
		// Various global vars set here:
		include_once 'customer_functions.php';
		include_once 'functions/menu.php';

		if (preg_match('/^\/parking\/garage-reservation\//si', $_SERVER['PHP_SELF']) || preg_match('/^\/garage_test\//si', $_SERVER['PHP_SELF']))
			include_once 'gr/header_newgr.php';
	}
	else
	{
		if ($GLOBALS['DEBUG_ERROR'])
			include_once 'top_Debug.php';
	}
	unset($noIncFiles);
}


/*****************
if (@sizeof($_SESSION['auth_webedit'])) {
	// PTS web editor is logged in - 'auth_webedit' session var is set - set in login_webedit .php
	include_once 'webedit/process_webedit.php';
}
else if (@$_GET['webedit_gui']) {
	// Go to log-in - If good login, sets $_SESSION['auth_webedit'].
	locationHref('/accountuser/webedit.php');
} else if (isset($_GET['WEB_DIR_ID_APRV']) && preg_match('/^\d+$/', $_GET['WEB_DIR_ID_APRV'])) {
	if (@$_SESSION['webedit-approver']) {
		// Web edit approver is logged in so go approve web edits!
		include_once 'webedit/process_webedit.php?WEB_DIR_ID_APRV='.$_GET['WEB_DIR_ID_APRV'];
	} else {
		// Go to log-in - If good login, sets $_SESSION['webedit-approver']
		include_once '/accountuser/webedit-approvals.php?WEB_DIR_ID_APRV='.$_GET['WEB_DIR_ID_APRV'];
	}
}
**************/


function __autoload($class_name)
{
	// __autoload Only includes files (classes) when needed - when instanciated.
	switch ($class_name)
	{
		case 'webauthNetid':
			include_once 'login_webauth.php';
			break;
		case 'database':
			include_once 'database.php';
			break;
		//------------------------- flex_ws classes -------------------
		case 't2webservice':
		case 'InsertUpdateCustomFlds':
		case 'cacheT2':
			include_once 'flex_ws/config.php';
			break;
		case 'GetAllCitations':
		case 'GetCitation':
		case 'GetEntCitations':
		case 'GetViolationCodes':
		case 'Flex_IVR': // because using Flex_IVR::function_name()
			include_once 'flex_ws/Flex_IVR.php';
			break;
		case 'PayContraventions':
			include_once 'flex_ws/Flex_Contraventions.php';
			break;
		case 'Flex_Permissions':
		case 'GetPermissionPriceFixed':
		case 'GetPermissionPriceFixed_bus':
		case 'ReservePermission':
		case 'UnreservePermission':
		case 'AuthPermissionSale':
		case 'SellPermission':
		case 'EditPermitDates':
		case 'GetPermissions':
		case 'PerNumToPerUID':
		case 'PerNumToPecUID':
		case 'GetRFID':
		case 'GetSuntran':
		case 'GetAvailableIPermit':
		case 'GetAvailableBus':
		case 'GetAvailableBicycle':
		case 'PayPermissionBalance':
		case 'AddPermissionFee':
		case 'GetPermissionBalance':
		case 'sellAnIPermit':
			include_once 'flex_ws/Flex_Permissions.php';
			break;
		case 'GetEligibleWaitlists':
		case 'GetAwardedPermits':
		case 'InsertWaitlistRequest':
		case 'GetCurrentWaitlists':
		case 'RemoveWaitlistRequest':
			include_once 'flex_ws/Flex_Waitlists.php';
			break;
		case 'GetFacilityList':
		case 'GetOccupancyData':
			include_once 'flex_ws/Flex_Occupancy.php';
			break;
		case 'InsertUpdateVehicle':
		case 'InsertUpdateVehicleAssociation':
		case 'GetVehicleAssociation':
		case 'GetVehicleInfo':
			include_once 'flex_ws/Flex_Vehicles.php';
			break;
		case 'InsertUpdateEntity':
		case 'GetEntity':
		case 'InsertUpdateEmailAddress':
		case 'InsertUpdateAddress':
		case 'GetAddressEmail':
		case 'PayOutAccount':
		case 'OptInTextMessage':
			include_once 'flex_ws/Flex_Entities.php';
			break;
		case 'InsertAdjudication':
		case 'get_adj_uid':
			include_once 'flex_ws/Flex_Adjudications.php';
			break;
		case 'ExecuteQuery':
		case 'InsertUpdateNotes':
		case 'get_document_note':
		case 'IsFlexAvailable':
		case 'getLotFullIncidents':
			include_once 'flex_ws/Flex_Misc.php';
			break;
		case 'ChangeFacilityCapacity':
			include_once 'flex_ws/Flex_Facility.php';
			break;
		case 'PayMiscSaleItems':
			include_once 'flex_ws/Flex_MiscSaleItems.php';
			break;
		case 'InsertDocument':
			include_once 'flex_ws/Flex_Reporting.php';
			break;
		case 'customException':
			include_once 'flex_ws/customException.php';
			break;
	}
}

/**********************
  // There is an issue with the Oracle client where the LD_LIBRARY_PATH variable will randomly not be set or fails. This sets the variable and, as a result, PHP must be run in safe mode.
  // Also, within /etc/httpd/conf/httpd.conf, we have: SetEnv LD_LIBRARY_PATH /usr/lib/oracle/10.2.0.3/client
  $envCheck = @getenv("LD_LIBRARY_PATH");    if (!$envCheck) {   $f = fopen("/var/www2/html/errorlog","a",false);
  fwrite($f,"---STARTING LOG: ");    // putenv("LD_LIBRARY_PATH=/usr/lib/oracle/10.2.0.3/client");
  fwrite($f,$_SERVER['REMOTE_ADDR']." : ".date("Ymd H:i")." : " . getenv("LD_LIBRARY_PATH") . " : ".$_SERVER['REQUEST_URI']."\n");   fclose($f);   }
 * *********************/

unset($_SESSION['debug_msg_shown']); // So that debug messages only show once per page.


function checkEnabled($service_name = '', $make_enabled = false, $sowOfflineMsg = false)
{
	/**********************************************
	 * Checks to see if web service $servicesAvailable[$service_name] is enabled - returns true or false.
	 * Enable/Disable a service by entering true/false below - $servicesAvailable.
	 * You can force a service to be enabled by calling this function and setting $make_enabled to true;
	 * or, if you are admin, you can set $GLOBALS['debug_web_service'] to a $service_name.
	 * $sowOfflineMsg is true, then echo a standard red message (ONLY IF $service_name IS SET TO FALSE): "$service_name: currently offline."
	 * *******************************************/
	global $servicesAvailable, $inc_top_bottom;

	// The $servicesAvailable items in which you DO NOT want 24 X 7 DB access, cuz we don't want 24 X 7 for for cybersource payments.
	$cyberDowntimeServices = array('Citation Payments', 'Permit Purchase', 'U-Pass Purchase'); // 'Garage Coupon'

	// service_name may change, so keep here on top.
	$debug_service_name = in_array($service_name, $GLOBALS['debug_web_service']) ? $service_name : '';
	if ($debug_service_name && @$GLOBALS['debug_service_url'][$service_name])
		$debug_service_name = '<a href="' . $GLOBALS['debug_service_url'][$service_name] . '">' . $debug_service_name . '</a>';


	// If Open Reg is true, and if Payroll or Current is true, then set Open Reg to false.
	if ($servicesAvailable['Open Registration'] || in_array('Open Registration', $GLOBALS['debug_web_service']))
	{
		if (@$servicesAvailable['Renew Payroll'] || in_array('Renew Payroll', $GLOBALS['debug_web_service']) ||
				  @$servicesAvailable['Renew Current'] || in_array('Renew Current', $GLOBALS['debug_web_service'])) {
			$servicesAvailable['Open Registration'] = false;
			if ($service_name == 'Open Registration')
				$debug_service_name = ''; // This renders usless any 'Open Registration' value in $GLOBALS['debug_web_service']
			if ($GLOBALS['DEBUG_DEBUG']) {
				echo '<div align="center" style="font-size:12px; border:2px solid red; padding:0; margin:4px;">
					DEBUG MESSAGE: Service <b>"Open Registration"</b> changed from <b>Enabled</b> to <b>Disabled</b> because
					Renew Payroll or Renew Current is true.</div>';
			}
		}
	}

	if (@$GLOBALS['WINTER_SHUTDOWN'])
	{
		/***
		 * If in Winter Shutdown, then see if $service_name (parameter) matches any items in $cyberDowntimeServices; if it does match
		 * then this service will be down (set $service_name to some random text, as it should not be empty.)
		 */
		if (in_array($service_name, $cyberDowntimeServices))
			$service_name = '['.$service_name.']'; // give it some other [name] so as to make sure service not available.
	}

	/*********
	  //########## Doing checks-n-balances logic for permit Reg and Renew.
	  // If all is well, set $GLOBALS['renew_type'] to one of the three services.
	  $open_err = '';
	  if ($GLOBALS['DEBUG_ERROR']) {
	  if (in_array('Open Registration', $GLOBALS['debug_web_service'])) {
	  if (!$servicesAvailable['Renew Payroll'] && !in_array('Renew Payroll', $GLOBALS['debug_web_service']))
	  {  $GLOBALS['debug_web_service'] = 'Renew Payroll';
	  $servicesAvailable['Renew Payroll'] = true;
	  $open_err .= ' ~~ DEBUG: Setting \'Renew Payroll\' to true.';  }
	  if (in_array('Renew Current', $GLOBALS['debug_web_service']))  {
	  $servicesAvailable['Renew Current'] = false;
	  $open_err .= ' ~~ DEBUG:  Setting \'Renew Current\' to false. ';  }
	  } else if ($servicesAvailable['Open Registration']) {
	  $servicesAvailable['Open Registration'] = false;
	  $open_err .= ' ~~ DEBUG: Setting \'Open Registration\' to false. ';	}
	  if ($open_err)	{
	  if (!$_SESSION['alreadyDebugNote']) {
	  echo '<div align="center" style="font-weight:bold; font-size: 16px; color:#D15E00;
	  border:1px solid #D15E00; padding:1px; margin-bottom:5px; margin-top:7px;">';
	  echo $open_err . '</div>';
	  $_SESSION['alreadyDebugNote'] = true;  } }
	  $open_err = '';
	  }
	  if ($open_err) exitWithBottom ('<div style="font-weight:bold; padding: 33px;">'.$open_err.'</div>');
	 *************/

	// Only for Open Reg will this remain true.
	//if ($servicesAvailable['Open Registration']) $_SESSION['allow_emplid'] = true;
	//else $_SESSION['allow_emplid'] = false;

	$ret_val = $servicesAvailable[$service_name] ? true : false;

	$ret_val = $make_enabled ? true : $ret_val;

	$ret_val = downTimeCyber($service_name, $cyberDowntimeServices) ? false : $ret_val;

	if (!$ret_val && $debug_service_name)
	{
		if (@!$_SESSION['debug_msg_shown'][$debug_service_name] && $inc_top_bottom) {
			echo '<div align="center" style="font-size:12px; color:grey; border:1px dashed #E5FF00; padding:0; margin:4px;"><div style="border:1px solid #E5FF00;">';
			echo 'Service <b>' . $debug_service_name . '</b> changed from <b>Disabled</b> to <b>Enabled</b>; IP ' . $_SERVER['REMOTE_ADDR'] . ' in top.inc.php</div></div>';
			$_SESSION['debug_msg_shown'][$debug_service_name] = true;
		}
		$ret_val = true;
	}

	if (!$ret_val && $sowOfflineMsg) {
		?>
		<div class="warning" style="font-weight:bold; padding:10px 0 25px 0;">
			Online Service: <?php echo $service_name; ?> is currently offline.</div>
		<?php
	}

	if (!$ret_val)
		webDownMsg(); // If WEBSITE_DOWN_MSG has a value, then this will display that message.

	return $ret_val;
}



function webDownMsg()
{
	/*	 * *
	 * After showing WEBSITE_DOWN_MSG message, erase the message because we only want to show message
	 * once per web page. (Remember, checkEnabled can be called many times)
	 */
	if ($GLOBALS['WEBSITE_DOWN_MSG']) {
		echo '<div style="padding:5px; margin:7px; border:3px solid #c03; background-color:#06f; font-weight:bold; color:white;">'
		. $GLOBALS['WEBSITE_DOWN_MSG'] . '</div>';
		$GLOBALS['WEBSITE_DOWN_MSG'] = '';
	}
}


function downTimeCyber($serviceName, $cyberDowntimeServices)
{
	/************************
	 * $servicesAvailable items in which you DO NOT want 24 X 7 DB access, cuz we don't want 24 X 7 for for cybersource payments.
	 * If within timeframe specified below, then return true (web service down), else false.
	 * Also gives warnings if current service $serviceName is in $cyberDowntimeServices array - regardless of time.
	 *******************/

	if ($_SERVER['PHP_SELF'] == '/index.php')
		return false;

	if (in_array($serviceName, $cyberDowntimeServices))
	{
		// Cybersource daily downtime - covers daylight savings time since Arizona does not observe
		$friendlyStartTime = '10:30pm';
		$startTime	= 2230;
		$friendlyEndTime = '1:30am';
		$endTime		= 130;
if (@$GLOBALS['jody']) {
		$friendlyStartTime = '10:30pm';
		$startTime	= 2230;
		$friendlyEndTime = '1:30am';
		$endTime		= 130;
}
		// Give an extra ten minutes if coming from a Cybersource payment -- req_reference_number is set
		if (@$_POST['req_reference_number'] || @$_SESSION['tenMin'])
		{
			$_SESSION['tenMin'] = true; // tenMin is only used here in top.inc.php
			$endTime = 140;
		}
		// 'G' is 24 hours without leading 0s, 'i' is minutes WITH leading 0s
		if (date('Gi') > $startTime || date('Gi') < $endTime)
		{
			//------------------------ everything is down!!! -------------------------
			echo '<div align="center" class="warning" style="padding:5px; margin:10px; border:1px solid #c03;">'
				. 'This service is currently unavailable, between '.$friendlyStartTime.' and '.$friendlyEndTime.' </div>';
			//if (@$GLOBALS['jody']) {
			//	echo '<div align="center" class="warning" style="padding:10px; border:1px solid red;">well, ok, good for jody.</div>';
			//	return false;    }

			return true;
		}
		else
		{
			//----------------------- Good to go, so return false, with Downtime warning. --------------------
			// Var $_SESSION['alreadyWarned'] is set to false in bottom.php
			if (!@$_SESSION['alreadyWarned'] && !@$_POST['req_reference_number'])
			{
				$_SESSION['alreadyWarned'] = true;
				echo '<div align="center" class="warning" style="padding:5px; margin:10px; color: #c03;">';
				echo '<em>This service is unavailable nightly between '.$friendlyStartTime.' and '.$friendlyEndTime.'.</em></div>';
			}
			return false;
		}
	}
	else
	{
		// Not even a cybersource service, so return false.
		return false;
	}
}



function jump2internal()
{
	/*	 * **
	 * We don't want peoples to see Internal URL, so to insert an internal link via email for example (from comments web page) make
	 * a link to our External home page with to get params: http://parking.arizona.edu/?internal_typ=log&internal_dta=111.111.111.111
	 * and this will jump to https://www.pts.arizona.edu/logs/index.php?wildcard=internal_dta&numMonths=1 - if user is internal IP.
	 */

	// only go to internal web pages if no 403 error.
	if (!preg_match('/<head>\s*<title>403/si', file_get_contents('https://www.pts.arizona.edu/'))) {
		switch ($_GET['internal_typ']) {
			case 'log':
				$internalURL = 'https://www.pts.arizona.edu/logs/index.php?wildcard=' . urlencode(@$_GET['getcode']) . '&numMonths=1';
				break;
			case 'entity':
				// $internalURL = 'http://128.196.6.197/PowerPark/entity/view.aspx?id='.$_GET['internal_dta'];
				break;
			default:
				$internalURL = '';
		}
		if ($internalURL)
			locationHref($internalURL);
	}
}



function websiteDownConfig()
{
	/****
	 * WEBSITE DOWN CONFIG AND OUTPUT MESSAGES, ALSO FOR WINTER SHUTDOWN
	 */

	/********************** WINTER_SHUTDOWN ***********************
	 * This function sets $GLOBALS['WINTER_SHUTDOWN'] to true or false.
	 * If true then any services in the $cyberDowntimeServices will be disabled. */
	$GLOBALS['WINTER_SHUTDOWN'] = winterClosure();
	if ($GLOBALS['WINTER_SHUTDOWN']) {
		// this message is used in top_External .php
		$GLOBALS['WINTER_SHUTDOWN_MSG'] = 'Starting Thursday, December 24, 2015, the University of Arizona is closed for winter break. PTS reopens '
			. 'for business on Monday, January 4, 2016. PTS web services (citations, permit payments, etc.) will not be available during the closure.';
	}


	$GLOBALS['WEBSITE_DOWN_MSG'] = '';
	/**********************  WEBSITE_DOWN_MSG *********************************
	 * This only shows the message WEBSITE_DOWN_MSG on home page, and also on any page which checks for an available $servicesAvailable web service.
	 * You need make FALSE (or comment out) all the $servicesAvailable services below which you don't want active.
	  if (withinTimeframe('2014-12-10 22:30:00', '2014-12-17 00:00:00')) {
	  $GLOBALS['WEBSITE_DOWN_MSG'] = 'Website down for maintenance. Online services will be available later this week.  We apologize for the inconvenience.';
	  //'Our website is currently experiencing technical difficulties. We apologize for any inconvenience this may cause.  For assistance you may call, 520-626-7275.
	  //If we are not able to immediately take your call, you may leave a message and we will return your call as soon as possible.';	  }
	 */
}


function winterClosure()
{
	/*************************************************
	 * Returns true or false.
	 * No cycbersource services.
	 * You must set the Winter shutdown/closure "from" and "to" dates below.
	 * 	See UA calendars here: http://catalog.arizona.edu/acadcals.html
	 *
	 * NOTE: For citation appeal extension dates, see include/citation_functions .php
	 * * */

	// Use IP to test winter closure functionality.
	$debug_test = false; // ($_SERVER['REMOTE_ADDR'] == '150.135.113.215XXXXX') ? true : false;

	/*	 * *******
	 * $winter_from var:
	 * UA Christmas Holiday begins 2013-12-24 - make it 2 days plus 1 & 1/2 hour EARLIER.
	 * Used to be only 1 day earlier, but web activity (sales and appeals) cannot occur on the
	 * 23rd since there will be no one in the office to reconcile/adjudicate them on the 24th - jsc - 20131223
	 * * */
	$winter_from = '2015-12-23 01:30:00'; // 2014-12-23 15:30:00
	// $winter_to var: Return from New Year Holiday 2013-01-02 - make 22 and 1/2 hours EARLIER than midnight.
	$winter_to = '2016-01-03 01:30:00'; // 2015-01-01 01:30:00

	if (withinTimeframe($winter_from, $winter_to) || @$debug_test)
		return true;
	else
		return false;
}


function shortWinterClosure()
{
	// Ah, a short winter says thee groundhog - give a little more time to take care of business.
	return !withinTimeframe('2015-12-23 17:00:00','2016-01-03 00:00:01');
}

if ($GLOBALS['DEBUG_DEBUG'])
{
	$login_data = @$_SESSION['eds_data_debug'] ? $_SESSION['eds_data_debug'] : '';
	$login_data = ($path_parts['basename'] != 'login_external.php') ? $login_data : '';
	if ($login_data) {
		unset($_SESSION['eds_data_debug']);
	} else {
		$login_data = @$_SESSION['ldap_data_debug'] ? $_SESSION['ldap_data_debug'] : '';
		$login_data = ($path_parts['basename'] != 'login_gr.php') ? $login_data : '';
		unset($_SESSION['ldap_data_debug']);
	}

	if ($login_data) {
		// Set in login_external .php AND login-webauth .php
		echo "<div style='padding:1px; border:1px solid orange;'>
			 <div onclick='showOrHide(\"eds_data_debug\");' style='font-size:12px; font-weight:bold; cursor:pointer; color:grey;'>
			  Login Return Data:</div>
			 <div id='eds_data_debug' style='display:none; font-size:10px;' ondblclick='showOrHide(\"eds_data_debug\");'><pre>" .
		$login_data . "<pre></div></div>";
	}
}


/***
 * $_GET['referral'] is permit purchase stuff.
 * $_GET['per_reg'] is permit reg stuff.
 */
function setPurOrReg($guessStatus='')
{
	/***
	 * call like so: $purOrReg = setPurOrReg();
	 * $guessStatus shall be empty string, or 'reg', or 'pur'.
	 */
	if (@$_GET['referral'])
		return urlencode($_GET['referral']);
	else if (@$_GET['per_reg'])
		return urlencode($_GET['per_reg']);
	if ($guessStatus=='reg')
	{
		// No proper get param, so let's guess:
		if (checkEnabled('Open Registration'))
			$_GET['per_reg'] = 'openregistration';
		else if (checkEnabled('Renew Current'))
			$_GET['per_reg'] = 'renewcurrent';
		else if (checkEnabled('Renew Payroll'))
			$_GET['per_reg'] = 'renewpayroll';
		if (@$_GET['per_reg'])
			return @$_GET['per_reg'];
	}
	return '';
}

function setPurOrRegParam($purOrReg)
{
	// Call like so: $purOrRegParam = setPurOrRegParam(setPurOrReg());
	// the function parameter is already secured by setPurOrReg()
	if (@$_GET['referral'])				return 'referral='.$purOrReg;
	else if (@$_GET['per_reg'])		return 'per_reg='.$purOrReg;
	else										return '';
}




/********************************************************************************
  TODO:
 * citation paid in cyber, but not in pp -- 113502283 --- probably permissions ./CyberLogs/ dir was drwxrw-r--.  2 jbrabec web  Nov  7 01:56 CyberLogs
 * Fix most header() function calls with locationHref() below.
 * Check out xdebug Webgrind - http://code.google.com/p/webgrind/wiki/Installation
 * Usa a Site Map generator (just below) to replace 'site map' in include/bottom_External.php  and  External/about/index.php  and External/index.php
 * https://www.google.com/webmasters/tools/home?hl=en
 * http://code.google.com/p/googlesitemapgenerator/downloads/list -- sitemap_linux-x86_64-beta1-20091231.tar.gz
 * Fix ServiceRequest system check4changes.php
 * get rid of 'NEW' on page https://www.pts.arizona.edu/garage_cashiering/index.php
 * Be a hero and see online Oracle Enterprise Mgr. to ananlyze sql inserts/updates/queries to see if can optimize.
 * For Sal's old versions of ffiles, see also /www.mis/html/include
 * Get rid of $outFile stuff in cyberOut.php
 * *************** */

?>
