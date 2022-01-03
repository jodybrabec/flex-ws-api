<?php

/*****************
 * login_external .php and login_internal .php are similar
 * If no $_SESSION['entity']['ENT_UID'], then jump to webauth to log in,
 *		so as to create $_SESSION['entity'] array and $_SESSION['eds_data'] array.
 * May 2014, Using $_SESSION['ignore_t2'] (set to loginReturnURI in calling file), so that we don't need to wory about
 *		creating a t2 account - just need to be logged in via webauth/eds -- set in account/sungo/optform .php
 * If non-secussful webauth login then exit.
 * $loginReturnURI is set to calling file's uri (with get params); or can
 *		also be set within the calling file if you need to override get params.
 * @$_POST['login'] is for old ldapLogin login.
 * Note: any var that contains "ldap", does not imply ldap connection is to be used.
 ****************/

include_once '/var/www2/include/login_functions.php';

if (@$loginReturnURI)
	$_SESSION['loginReturnURI'] = $loginReturnURI;
else if (@$_SESSION['loginReturnURI'])
	$loginReturnURI = $_SESSION['loginReturnURI'];
else
	$loginReturnURI = getReturnUri(@$loginReturnURI); // $loginReturnURI to be set to calling file's url.

if (!@$_SESSION['loginReturnURI'])
	$_SESSION['loginReturnURI'] = $loginReturnURI;

$error = $goodWebauthLogin = '';

if (@$_SESSION['ignore_t2'] == $_SESSION['loginReturnURI'])
	$valid_cust = @$_SESSION['goodWebauthLogin']; // was using $_SESSION['eds_data']['emplid'];
else
	$valid_cust = @$_SESSION['entity']['ENT_UID'];


if (!$valid_cust) {
	//===================== Login Via Webauth =========================

	spinnerWaiting();

	if (@!$_GET['insert_new_cust']) {
		/******
		 * This function is called TWICE here:
		 *		Once from our end - this function jumps customer over to webauth login url.
		 *		Then called again upon return from webauth login.
		 *		login_webauth.php
		 */
		if (@$_GET['ticket']) {
			//-------------------- STEP 2 -- "fopen" -- coming from webauth, if successful get customer data --------------
			$loginWeb = new webauthNetid($_GET['ticket']); // sets $_SESSION['webauth_data']['netid']
			// added !$_SESSION['ignore_t2'] param, because garage reservation people have much different webauth data.
			$ent_cuinfo = $loginWeb->getEdsInfo($_SESSION['webauth_data']['netid'], !$_SESSION['ignore_t2']); // sets $_SESSION['eds_data'] array
			if ($GLOBALS['DEBUG_DEBUG']) echoNow(Flex_Funcs::makeDivDataBar('--- webauthNetid', $loginWeb, 'div_id_logW'));

			//	if ($_SESSION['eds_data']['emplid'])	$goodWebauthLogin = $_SESSION['goodWebauthLogin'] = true;
			//	else	$_SESSION['goodWebauthLogin'] = false;
		} else {
			//--------------------------- STEP 1 -- JUMP TO WEBAUTH URL ----------------------------
			$loginWeb = new webauthNetid();
			// locationHref is done in in webauthNetid, but doing exit here just for good code style.
			exitWithBottom();
		}
	}

	if ($_SESSION['eds_data']['emplid'])
		$goodWebauthLogin = $_SESSION['goodWebauthLogin'] = true;
	else
		$_SESSION['goodWebauthLogin'] = false;
}

// Have thesse way down here because login_webauth .php restores session-saved GET vars.
$purOrReg		= setPurOrReg();
$purOrRegParam = setPurOrRegParam($purOrReg);


if (@$goodWebauthLogin) {
	/**********
	 * THESE STATEMENTS MAY NOT ALL BE TRUE.............
	 * If the emplid in T2 matches LDAP, then $ent_cuinfo->emplidmatch will be true. (same for $ent_cuinfo->netidmatch).
	 * If $ent_cuinfo->emplidmatch AND $ent_cuinfo->netidmatch are BOTH false, then include the "new account" form.
	 * If $ent_cuinfo->emplidmatch is false, but $ent_cuinfo->netidmatch true, then error.
	 *		Maybe later if $ent_cuinfo->netidmatch is false then update via GetEntity - if update_t2 is true that is.
	 **********/

	if (@$_SESSION['ignore_t2'] == $_SESSION['loginReturnURI']) {
		// We know $valid_cust is true, so lets go back to what we is doing.
		unset($_SESSION['loginReturnURI']);
		unset($_SESSION['ignore_t2']);
		locationHref($loginReturnURI);
	} elseif ($_SESSION['entity']['ENT_UID'] && $ent_cuinfo->emplidmatch && $ent_cuinfo->netidmatch) // $ent_cuinfo->dbkeymatch
	{
		// Good login (ldap matches t2), let's go!
		unset($_SESSION['loginReturnURI']);
		unset($_SESSION['ignore_t2']);
		locationHref($loginReturnURI);
	} elseif ($ent_cuinfo->netidmatch && !$ent_cuinfo->emplidmatch) {
		$errCode = 2290;
		$error = 'emplid does not match.';
	} elseif (!$ent_cuinfo->netidmatch && $ent_cuinfo->emplidmatch) {
		$errCode = 2290;
		$error = 'netid does not match.';
	} elseif (!$ent_cuinfo->netidmatch && !$ent_cuinfo->emplidmatch && $ent_cuinfo->dbkeymatch) {
		$errCode = 2291;
		$error = 'netid and emplid do not match, but uaid DOES match.';
	} else {
		// LDAP login successful, but NO T2 account found, yet,
		// (!$ent_cuinfo->netidmatch && !$ent_cuinfo->emplidmatch), so create a customer form.

		if ($purOrReg == 'renewpayroll' || $purOrReg == 'renewcurrent') {
			$errCode = 2292;
			$errZZ = 'UA login successful for (' . $purOrReg . ') but no PTS account, or netid mismatch or emplid mismatch.';
			new event_log('login_transaction', 'error', @$_SESSION['entity']['ENT_UID'], '0', '', makeErrorCode($errCode, $errZZ));
			exitWithBottom('<b>CODE: ' . getErrorCode($errCode) . '</b>, ' . CONTACT_CR);
		} else if (!@$_SESSION['ignore_t2']) // 20160223 - not really needed, but just in case.
		{
			// Customer has UA eds account, but NOT t2, so have customer create T2 account.
			// $new_cust_form set here only.
			// Used in address_form .php and new_cust .php and bicycle .php.
			if ($purOrReg && !@$_SESSION['entity']['ENT_UID'])
				$new_cust_form = true;
		} else {
			unset($_SESSION['ignore_t2']);
		}
	}
}

if ($error) {
	new event_log('login_transaction', 'error', @$_SESSION['entity']['ENT_UID'], '0', '', makeErrorCode($errCode, $error));
	unset($_SESSION['entity']);
?>
	<p class="warning" align="center"><?php echo '<b>CODE: ' . getErrorCode($errCode) . '</b>, ' . CONTACT_CR; ?></p>
<?php
}


if (@$new_cust_form || @$_GET['insert_new_cust']) {
	/********************************* Insert a new T2 customer *********************************
	 * $new_cust_form set above - means person has succesfull eds login, but no T2 account - CREATE
	 *		a new T2 customer form, setting a [new] GET parameter $_GET['insert_new_cust'] below in $form_action
	 * If $_GET['insert_new_cust'] is set -- means the new T2 customer form SUBMITTED
	 * new_cust .php includess address_form .php
	 */

	if (!$purOrReg) {
		$errCode = 2297;
		$err = 'Could not create new customer, no GET parameter in new_cust .php.';
		new event_log('login_transaction', 'error', @$_SESSION['entity']['ENT_UID'], '0', '', makeErrorCode($errCode, $err));
		exitWithBottom('<b>CODE ' . getErrorCode($errCode) . '</b>, ' . CONTACT_CR);
	}

	// may change below for open registration.
	$form_action = $path_parts['uri'] . '?' . $purOrRegParam . '&insert_new_cust=1';  // used in new_cust .php and then address_form .php

	if ($purOrReg) {
		//----------------------- new cust login return and form action ---------------------------
		// Upon successful T2 account creation, jump the web to $referral_href.

		if ($purOrReg == 'bikereg') {; //### todo: Maybe set $referral_href to $loginReturnURI
			$referral_href = '/bicycle/registration/?referral=bikereg';
		} else if ($purOrReg == 'buspurchase') {
			$referral_href = $loginReturnURI; // '/transportation/bus-purchase/index.php?referral=buspurchase';
		} else if ($purOrReg == 'perpurchase' && @$new_cust_form) {
			exitWithBottom('<br/><br/><br/>You do not have an account with us yet. Please go to <a href="/account/regnotice.php?per_reg=openregistration">permit registration</a> page.<br/><br/>');
		} else if ($purOrReg == 'openregistration') {
			// First goes to regnotice .php and THEN winds up at /programchanges/index.php.
			$referral_href = '/account/regnotice.php?' . $purOrRegParam;
			// used in address_form .php
			$form_action	= '/account/regnotice.php?' . $purOrRegParam . '&insert_new_cust=1';  // used in address_form .php
		} else {
			$referral_href = '/parking/permit-changes/index.php?' . $purOrRegParam;
		}
	}

	include_once '/var/www2/html/account/new_cust.php';
	exitWithBottom(); //##############################

} else if (!@$new_cust_form) // person has t2 acct.
{

	if ($_SERVER['PHP_SELF'] == "/account/permit_renewal.php" || $_SERVER['PHP_SELF'] == "/parking/permit-changes/index.php") {
		if (@!$_GET['logout']) {
			if (checkEnabled("Renew Payroll") && !checkEnabled("Renew Current")) {
				// Message for everybody, during Renew Payroll - but, only Payrol folks can get this far so why show it?
				//echo '<ul><p class="warning" align="center">This service is currently only available to university
				//	employees enrolled in the Payroll Deduction program.</p></ul>';
			}
			if (checkEnabled("Renew Current") && !checkEnabled("Renew Payroll")) {
				// Message for everybody, during Renew Current - but, only Current reg folks can get this far so why show it?
				//echo '<ul><p class="warning" align="center">This service is currently only available to customers NOT
				//	enrolled in the Payroll Deduction program.</p></ul>';
			}
		}
	}


	if (@$_GET['newcust'] || (@$_SESSION['cuinfo']['pecid'] && @!$_POST['login'])) {
		$errCode = 2296;
		$errUU = "Logged in but unknown error";
		new event_log('login_transaction', 'error', @$_SESSION['entity']['ENT_UID'], '0', '', makeErrorCode($errCode, $errUU));
		exitWithBottom('<b>CODE: ' . getErrorCode($errCode) . '</b>, ' . CONTACT_CR);
	}


	// logout=1 is in top_External .php

	if (!$valid_cust) {
		//--------------------- Not logged in -----------------
		exitWithBottom();
	}
}
unset($valid_cust);
//--------------- Made it thus far, GOOD TO GO ---------------------

?>
