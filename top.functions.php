<?php
/*
 * top.functions.php
 * TODO: Use this in /var/www/html/include/top.inc.php as well - then can just include in /var/www/html/include/top.php only
 */


//**************** don't need this
function makeSecure($make_secure = true)
{
	return;
}




function trimVars(&$elem)
{
	// Just trim all POST and GET vars.

	if (!is_array($elem))
		$elem = trim($elem);
	else
		foreach ($elem as $key => $value)
			$elem[$key] = trimVars($value);
	return $elem;
}


function make_htmlentities(&$elem, $setMade = true)
{
	// Convert $_GET, $_POST, or any other array/vars into htmlentities. This is mainly to avoid injection of <script> code.
	global $made_htmlentities;

	if ($setMade)
		$made_htmlentities = true;

	if (!is_array($elem))
		$elem = htmlentities($elem, ENT_QUOTES);
	else
		foreach ($elem as $key => $value) // If array key (i.e. POST/GET name) is "pass[word]" then ignore.
			if (!preg_match('/^pass/i', $key) && !preg_match('/^user/i', $key))
				$elem[$key] = make_htmlentities($value);

	return $elem;
}


function unmake_htmlentities(&$elem)
{
	if (!is_array($elem))
		$elem = html_entity_decode($elem, ENT_QUOTES);
	else
		foreach ($elem as $key => $value)
			$elem[$key] = unmake_htmlentities($value);
	return $elem;
}



function withinTimeframe($from_d, $to_d)
{
	// If the current time (now) is between $from_d and $to_d, then return true, else false.
	// Format (time optional): YYYY-MM-DD [HH:ii:ss]     EXAMPLE: '2010-01-01 00:00:00'
	// keywords: date(''), compare dates.

	$now	= strtotime('now');
	$from	= strtotime($from_d);
	$to	= strtotime($to_d);
	if ($from <= $now && $to >= $now)
		return true;
	else
		return false;
}


function locationHref($jumpLink)
{
	if ($jumpLink)
	{
		if (@$_SERVER['REMOTE_ADDR'] == '128.196.6.66') {
			//echoNow('$jumpLink: '.$jumpLink.'<br>');
			//sleep(1);
		}
		echo "
		<script type='text/javascript'>
		document.location.href='" . $jumpLink . "';
		</script>
		";
		exit; //################ important after redirect!!!!
	}
}


function echoNow($str = '')
{
	/*	 * **********************************
	 * Outputs the current buffer (web page), so you can see what's going on now.
	  EXAMPLE: echoNow('<br />Please Wait');
	 * Tech note: ob_end_flush could just be called once I think. I believe it clears all existing buffers.
	 * *************** */

	echo $str;

	// This seems to work without ob_start, cool
	while (@ob_end_flush());
	flush();
}





function exitWithBottom($warnMsg = '', $errorLogFile = '', $errorLogString = '')
{
	// Don't just do an 'exit', first sticks in bottom php

	global $inc_top_bottom; // used in bottom.php

	if ($warnMsg)
		echo "<div class='warning' align='center'>" . $warnMsg . "</div>";

	if ($errorLogFile && $errorLogString)
		logError($errorLogFile, $errorLogString . "\nHTML OUTPUT:" . $warnMsg);

	include_once 'bottom.php'; // bottom.php pages are auto-called in /etc/php.ini, but we are exiting here.
	exit;
}




function logError($errorLogFile, $errorLogString)
{
	// Save error to file $errorLogFile. (will be stored here: /var/www2/html/logs/).
	// Example call:
	//		logError('flexRelated.txt', __FILE__.':'.__LINE__ ."\n".$errorLogString);
	global $debugIP_1;

	$reducedMsg = '<span style="font-weight:bold; color:red; font-size:12px;">Reduced Message: ' .
			  preg_replace('/^.*?<REDUCED_HTML_START>(.*?)<REDUCED_HTML_END>.*$/si', "$1", $errorLogString) . '</span>';

	// Prepend date:
	$errorLogString = "=========== " . date("Ymd H:i") . " IP: " . $_SERVER['REMOTE_ADDR'] . " ===========\n" . $errorLogString;
	// Append post and session to err msg:
	if (@sizeof($_POST))
		$errorLogString .= "-------------_POST:\n" . print_r($_POST, true) . "\n";
	if (sizeof($_SESSION))
		$errorLogString .= "-------------_SESSION:\n" . print_r($_SESSION, true) . "\n";

	// Get rid of all those NASTY spaces in empty Arrays.
	$errorLogString = preg_replace('/Array\s+\(\s+\)\s+/si', "Array ()\n", $errorLogString);
	// Replace <br> with newlines.
	$errorLogString = preg_replace('/<br\s?\/?\s?>/si', "\n", $errorLogString);
	$errorLogString = strip_tags($errorLogString);

	$errorLogFile = '/var/www2/html/logs/' . $errorLogFile;

	if (in_array($_SERVER['REMOTE_ADDR'], $debugIP_1)) {
		$tmpErr = '<br /><div style="white-space:nowrap;"><small>$$$$$$$$$$$ Skipping log file output ' . $errorLogFile . ' $$$$$$$$$$$$$$$$</small></div>';
		$tmpErr .= $reducedMsg;
		$tmpErr .= '<br /><small>$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$</small><br />';
		echo $tmpErr;
		// $GLOBALS['debugEchos_data'] .= $tmpErr;
	} else {
		$errorLogString = preg_replace('/password[^\n]+/si', 'password ***x***', $errorLogString);
		$OUT_FILE = fopen($errorLogFile, 'a', false);
		fwrite($OUT_FILE, $errorLogString);
		fclose($OUT_FILE);
		//'PTS-PermitServices@email.arizona.edu';
	}
}





function debugEchos($file_line, $html = '', $db_conn = '', $query = '', $qVars = array(), $fatalError = false, $sleep = 0)
{
	/*	 * ***************************************************************************
	  Call debugEchos anywhere, and in the end, $GLOBALS['debugEchos_data'] will be spit out.
	  Set $fatalError to true to exit (die) at the end of this function.
	  EXAMPLE CALL:
	  if ($GLOBALS['DEBUG_DEBUG'])
	  debugEchos(__FILE__.':'.__LINE__, '<strong>blaaaaa</strong>', $db_conn, $searchQuery, $qVars);
	 * *************************************************************************** */
	// first param: __FILE__.':'.__LINE__
	// $file_line should be called as: __FILE__.':'.__LINE__
	$GLOBALS['debugEchos_data'] .= '<pre><span style="font-weight:normal; background:#fcc;">';
	$GLOBALS['debugEchos_data'] .= '~~~~~~~~~~~~~~~~~~~~~~~~~~~~ BEGIN debugEchos OUTPUT: ' . $file_line . '~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~<br>';

	$GLOBALS['debugEchos_data'] .= "";
	if ($query)
		$GLOBALS['debugEchos_data'] .= "Database Connection: " . $db_conn->serviceName . "\nDB Query: " . preg_replace('/\t/si', ' ', trim($query)) . "\n";
	if (sizeof($qVars))
		$GLOBALS['debugEchos_data'] .= '$qVars: ' . print_r($qVars, true) . "\n";

	$GLOBALS['debugEchos_data'] .= '<a href="https://www.pts.arizona.edu/logs/index.php" target="_blank">Popup the Web Transactions - try keyword search!</a>' . "\n";

	if ($html)
		$GLOBALS['debugEchos_data'] .= $html;

	$GLOBALS['debugEchos_data'] .= '<br>~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ END debugEchos OUTPUT ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~';
	$GLOBALS['debugEchos_data'] .= "</span></pre>";

	if ($sleep)
		sleep($sleep);

	if ($fatalError) {
		if ($GLOBALS['DEBUG_DEBUG'] || $GLOBALS['DEBUG_ERROR']) {
			echo_debugs(); // top.inc.php and maybe bottom.php
			die();
		}
	}
}


function massageDebug()
{
	// Sets up various debug settings and limitations.
	//
	// These directories should probably never have debug warnings:

	if (preg_match('/\/garage_reservation\/administrator/si', $_SERVER['PHP_SELF']) && !preg_match('/\/findandreplace/si', $_SERVER['PHP_SELF'])) {
		// DON'T CHANGE TO TRUE
		$GLOBALS['DEBUG_DEBUG'] = false;
		$GLOBALS['DEBUG_WARN'] = false;
		$GLOBALS['DEBUG_LOG'] = false;
	}

	if ($GLOBALS['DEBUG_ERROR'] || $GLOBALS['DEBUG_LOG'] || $GLOBALS['DEBUG_WARN']) {
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', true);
		ini_set('ignore_repeated_errors', false); // default is false i think
		if ($GLOBALS['DEBUG_WARN'])
			error_reporting(E_ALL);
		elseif ($GLOBALS['DEBUG_ERROR'])
			error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
	}
}



function sessionReset()
{
	/*	 * *
	 * Destroy and then reset SESSION.
	 * May also need to jump to webauth.arizona.edu to logout
	 */
	// Preserve various session vars which you DO NOT want destroyed.
	$saveSessVars = array();
	if (isset($_SESSION['output_debug_initalized']))
		$saveSessVars['output_debug_initalized'] = $_SESSION['output_debug_initalized'];
	if (@isset($_SESSION['output_debug']))
		$saveSessVars['output_debug'] = $_SESSION['output_debug'];
	if (isset($_SESSION['req_reference_number_save']))
		$saveSessVars['req_reference_number_save'] = $_SESSION['req_reference_number_save'];
	if (@isset($_SESSION['turnOffDebug']))
		$saveSessVars['turnOffDebug'] = $_SESSION['turnOffDebug'];
	if (isset($_SESSION['live_db_mode']))
		$saveSessVars['live_db_mode'] = $_SESSION['live_db_mode'];

	unset($_SESSION['netidMorph']);
	session_destroy();
	session_start();

	foreach ($saveSessVars as $k => $v)
		$_SESSION[$k] = $v;

	// The session var $_SESSION['goodWebauthLogin'] set in login_external.php
	if (@$_SESSION['goodWebauthLogin'] || @$_GET['logout'])
	{
		if (@!$_GET['pts_logout_note'])
		{
			// Set various GET params to make page sticky after login.
			$url_a = "https://webauth.arizona.edu/webauth/logout?logout_href=http://" . $_SERVER["HTTP_HOST"]."/";
			$url_b = @$_GET['tempr'] ? 'servicerequest/index.php?tempr=' . $_GET['tempr'] . '&' : '?';
			$url_c = "pts_logout_note=1&logout_text=Return%20to%20PTS%20>>%20>>%20>>%20>>%20>>%20>>%20>>%20>>%20>>";

			locationHref($url_a . $url_b . $url_c);
		}
	}
}




function unmake_amps()
{
	// Gets rid of 'amp;' in $_GET param names - so $_GET['amp;myVar'] becomes $_GET['myVar']
	// Made this because sometimes '&amp;' will wind up in the URL, as in:
	//   https://parking.arizona.edu/parking/garage-reservation/administrator/guest_editor.php?date=20-DEC-11&amp;id=81724&amp;list=1
	foreach ($_GET as $g_param => $g_value) {
		if (preg_match('/^amp\;(.*)$/i', $g_param)) {
			$g_paramNew = preg_replace('/^amp\;(.*)$/i', '$1', $g_param);
			unset($_GET[$g_param]);
			if ($g_paramNew != '') {
				// Only if $g_paramNew has a value, becaues sometimes '&amp;' winds up at the end of a url ($_GET['amp;'])
				$_GET[$g_paramNew] = $g_value;
			}
		}
	}
}




$spinner_added = 0;
function spinnerWaiting($msg='Please Wait........')
{
	/***
	 * Put spinnerWaiting() at the top of any file to start spiny thing
	 */
	global $spinner_added;
	if (!$spinner_added++)
	{
		?>
		<!-- Only need spinner_id div tag once. destroyed when ".ready" below. Uses spin.js -->
		<div id="spinner_id" style="position:absolute; left:38%; top:267px; z-index: 100;"></div>
		<script type="text/javascript">
		spinner_x_div = document.getElementById('spinner_id');
		spinner_x_div.innerHTML = "<div style='padding-top:22px; font-size:1.1em; text-shadow: 0 0 3px #99f; opacity: 0.9; background:white; position:relative;'><?php echo htmlentities($msg);?></div>";
		var spinner_x = new Spinner(spin_opts).spin(spinner_x_div);
		$(document).ready(function(){
			// Stop spinner when page fully loaded.
			if (spinner_x) spinner_x.stop();
			spinner_x_div.innerHTML = "";
		});
		</script>
		<?php
	}
	else
	{
		echo "\n".'<script type="text/javascript">/*** '.__FILE__.':'.__LINE__.' NOTE: spinnerWaiting called previously ***/</script>'."\n";
	}
	echoNow();
}




function echo_debugs() {
	// Echos the $GLOBALS['debugEchos_data']. Called here (if die() is used) and in bottom.php

	if ($GLOBALS['debugEchos_data'])
	{
		echo removePass($GLOBALS['debugEchos_data']); // in debug_trce .php
		unset($GLOBALS['debugEchos_data']);
	}
}


/*** xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
 //* Encode sensitive data (like entity session data) if not in https.
 //* Do not do for Internal because services like "masive emailer" will not decode entity session data.
//xxxxxxxxxxif (@$_SERVER['HTTPS'] == 'on')	decodeEntity();
//xxxxxxxxxelse	encodeEntity();
function encodeEntity() {
//* Encodes entity info (emplid, netid, etc).
//* This is url-safe, just in case.
if (@!$_SESSION['entity_coded']) {
	if (@$_SESSION['flexid_netid']) {
		$_SESSION['entity_coded'] = true;
		$v = $_SESSION['flexid_netid']; // just for consictancy.
		$_SESSION['flexid_netid'] = rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
	}
	if (@sizeof($_SESSION['entity'])) {
		$_SESSION['entity_coded'] = true;
		foreach ($_SESSION['entity'] as $k => $v)
			$_SESSION['entity'][$k] = rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
	}
	if (@sizeof($_SESSION['eds_data'])) {
		$_SESSION['entity_coded'] = true;
		foreach ($_SESSION['eds_data'] as $k => $v)
			$_SESSION['eds_data'][$k] = rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
	} } }
function decodeEntity() {
//* un-do what encodeEntity did.
if (@$_SESSION['entity_coded']) {
  session_regenerate_id(); // Going from http to https, so regenerate session id, for extra security.
  if (@$_SESSION['flexid_netid']) {
	  $_SESSION['entity_coded'] = false;
	  $v = $_SESSION['flexid_netid']; // just for consictancy.
	  $_SESSION['flexid_netid'] = base64_decode(str_pad(strtr($v, '-_', '+/'), strlen($v) % 4, '=', STR_PAD_RIGHT));
  }
  if (@sizeof($_SESSION['entity'])) {
	  $_SESSION['entity_coded'] = false;
	  foreach ($_SESSION['entity'] as $k => $v)
		  $_SESSION['entity'][$k] = base64_decode(str_pad(strtr($v, '-_', '+/'), strlen($v) % 4, '=', STR_PAD_RIGHT));
  }
  if (@sizeof($_SESSION['eds_data'])) {
	  $_SESSION['entity_coded'] = false;
	  foreach ($_SESSION['eds_data'] as $k => $v)
		  $_SESSION['eds_data'][$k] = base64_decode(str_pad(strtr($v, '-_', '+/'), strlen($v) % 4, '=', STR_PAD_RIGHT));
  } } }
**********xxxxxxxxxxxxxxxxxxxx********/


?>
