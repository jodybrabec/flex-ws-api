<?php

/***
 * login_functions.php
 */

include_once '/var/www2/include/login_webauth.php';

function urlExternal($url)
{
	if (preg_match('/parking\.arizona\.edu/', $url))
		return true;
	else if (preg_match('/128\.196\.6\.9/', $url))
		return true;
	else
		return false;
}

function path2url($full_path_uri)
{
	/***
	 * Convert an absolute path to a url and return it.
	 */
	if (preg_match('/' . preg_quote('/var/www2/html', '/') . '(.*)$/si', $full_path_uri, $matches))
		$url = 'https://' . $_SERVER["HTTP_HOST"] . $matches[1];
	else if (preg_match('/' . preg_quote('/var/www/html/Internal', '/') . '(.*)$/si', $full_path_uri, $matches))
		$url = 'https://' . $_SERVER["HTTP_HOST"] . $matches[1];
	return $url;
}

function url2path($url)
{
	/***
	 * Convert a url to an absolute path and return it - if directory add a trailing slash.
	 */
	$url_parts		= parse_url($url);
	$path_parts		= pathinfo($url_parts['path']);

	$full_path_uri	= urlExternal($url) ? '/var/www2/html' : '/var/www/html/Internal';
	$full_path_uri	.= $url_parts['path'];

	if (!@$path_parts['extension']) {
		// Inserting a directory path, so add trailing slash if not there.
		$full_path_uri = preg_replace('/^(.*[^\/])$/si', '$1/', $full_path_uri);
	}
	return $full_path_uri;
}


function thisUrlAuthorized($tryNETID, $type)
{
	/***
	 * INPUT:
	 *		$tryNETID - PTS employee netid.
	 *		$type SHALL be 'auth_webedit' OR 'auth_web_service' - name of session array var.
	 * OUTPUT
	 *		Return path of current file - if netid is authorized for current web page file or directory.
	 */

	global $path_parts;

	$current_file_path = '';

	// $type is $_SESSION['auth_webedit'] OR $_SESSION['auth_web_service']
	$dirFullPath = $_SERVER['DOCUMENT_ROOT'] . $path_parts['dirname'];
	$dirFullSlash = $dirFullPath . '/';
	$uriFullPath = $_SERVER['DOCUMENT_ROOT'] . $path_parts['uri'];
	if (sizeof($_SESSION[$type])) {
		//------------------- Verify that person is authorized to be in current directory, or file (uri). ----------------------------
		// $type SHALL be 'auth_webedit' OR 'auth_web_service'
		if (!$current_file_path) {
			// First see if authorized for entire directory.
			$current_file_path = (in_array($dirFullPath, $_SESSION[$type])	&& $dirFullPath)	? $uriFullPath : '';
		}
		if (!$current_file_path) {
			// First see if authorized for entire directory - WITH trailing slash.
			$current_file_path = (in_array($dirFullSlash, $_SESSION[$type])	&& $dirFullSlash)	? $uriFullPath : '';
		}
		if (!$current_file_path) {
			// See if authorized for current file.
			$current_file_path = (in_array($uriFullPath, $_SESSION[$type])	&& $uriFullPath)	? $uriFullPath : '';
		}
	}
	//if ($GLOBALS['jody']) {
	//	echo "<hr>\$current_file_path: $current_file_path<br>$dirFullPath<br>$uriFullPath<br>";
	//	exitWithBottom();
	//	return true;
	//}
	return $current_file_path;
}


function setEntNetid($no_t2_account = true)
{
	/***
	 * If $no_t2_account true, then $_SESSION['entity'][] will be set to webauth data $_SESSION['eds_data'][].
	 * We are trying to get away from using T2 GetEntity for people to log in.  We would need to go through
	 * every script that uses this file, and make sure only $_SESSION['entity']['NETID'] is used (not $_SESSION['entity']['ENT_UID'])
	 */
	global $mysqli2;

	if ($no_t2_account) {
		// Still need to check to see if NETID is found in PARKING.AUTH_USER_SERVICE (later on) -- if not, then fail.
		// Set just a few $_SESSION['entity'] array items.
		$_SESSION['entity']['NETID'] = strtolower($_SESSION['eds_data']['netid']); // just in case
		$_SESSION['entity']['givenname_sn'] = $_SESSION['eds_data']['givenname'] . ' ' . $_SESSION['eds_data']['sn'];
	} else {
		// T2 account required, so only set $_SESSION['entity'] array (via setEntSession) ONLY if have T2 account.
		if (!$mysqli2) $mysqli2 = new database();
		// ($wsConn, $ent_uid='', $dbkey='', $emplid='', $netid='', $uid_class_string='', $catid='', $update_T2=true, $T2_netid_update=true)
		$ent_cuinfo = new GetEntity('', $_SESSION['eds_data']['dbkey'], $_SESSION['eds_data']['emplid'], $_SESSION['eds_data']['netid'], '', '', false, false);
		$ent_cuinfo->setEntSession();
		if ($GLOBALS['DEBUG_DEBUG']) echo $ent_cuinfo;
	}
}



function getReturnUri($uri = '')
{
	// If $uri IS empty, then returns the calling url with it's get params intact.
	global $path_parts;

	if (!$uri) {
		$uri = $path_parts['uri'];
		if ($path_parts['query_string']) {
			$uri .= '?' . $path_parts['query_string'];
			$uri = preg_replace('/logout=.&?/si', '', $uri); // don't include get param logout=1
			$uri = preg_replace('/debugErrorOnly=false&?/si', '', $uri);
		}
	}

	// Must have a '?' because more get params are added below.
	if (!preg_match('/\?/si', $uri))
		$uri .= '?';

	$uri = preg_replace('/loginTry=\d*&?/si', '', $uri); // Get rid of the GET param loginTry=xxxx
	$uri .= '&loginTry=' . time(); // this is just used for debugging, feel free to destroy it.

	$uri = preg_replace('/&+/si', '&', $uri);
	$uri = preg_replace('/\?&/si', '?', $uri);
	return $uri;
}


//=========================== OLDER FUNCTIONS ==============================

function debugNote($msg, $print_all = false)
{
	/***
	 * Used here and other .php files.
	 * Appends various debug messages ($msg) to $GLOBALS['debug_notes']
	 * If $print_all is set, then print all $GLOBALS['debug_notes'] and then sets $GLOBALS['debug_notes'] = '';
	 */
	if ($GLOBALS['DEBUG_DEBUG']) {
		if ($msg != '') {
			@$GLOBALS['debug_notes'] .= '<div style="color:black; font-size:1em; border:1px solid grey; padding:1px; margin:1px;">
				ADMIN note: ' . $msg . '</div>';
		}
		if ($print_all) {
			echo $GLOBALS['debug_notes'];
			$GLOBALS['debug_notes'] = '';
		}
	}
}
