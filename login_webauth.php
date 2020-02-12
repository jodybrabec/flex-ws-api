				<?php
				/*****
				 * UA Login Web Services setup: https://siaapps.uits.arizona.edu/home/
				 *		-- https://confluence.arizona.edu/pages/viewpage.action?pageId=31655272
				 *
				 * TEST Account:
				 *		NetId:  pts-test
				 *		UAID:   T737490097340196352  (In file include/flex_ws/Flex_entities.php, this is changed to T73749009734 when inserting new customer.)
				 *		EMPLID: 91222840
				 *		passwd: annoy;weep_Havana:delete
				 *
				 * URL for single-individual search, using $eds_user and pass below:
https://eds.arizona.edu/people/16808753 | jbrabec | ...
				 */

				include_once '/var/www2/include/login_functions.php';

				class webauthNetid
				{
					// Sometimes Webauth will return a blank screen in Firefox - just close browser windows out.
					// Only need to encode stuff like '?' or '&' within my GET params:
					public $description			= 'Webauth+Login+for+PTS'; // urlencoded
					public $webauth_url			= 'https://webauth.arizona.edu/webauth';
					public $webauth_return_url	= ''; // URL where webauth comes to after login -
					public $login_url				= ''; // STEP 1 - jump to webauth to login.
					public $customer_url_data	= ''; // STEP 2 - fopen to get custome rdata.
					public $customer_data		= array(); // customer data from customer_url_data.
					/***
					 * $propertiesToLoad is array of EDS properties (fields) to fetch -- see
					 * https://siaapps.uits.arizona.edu/home/ --> https://confluence.arizona.edu/pages/viewpage.action?pageId=31655272
					 * Others: studentStatus, studentPrimaryCareerProgramPlan, studentAPDesc, employeeFTE, studentCareerProgramPlan, employeePrimaryTitle, dateOfBirth, employeeOfficialOrg, modifytimestamp
					 */
					public $propertiesToLoad	= array(
						'uaId', 'emplId', 'uid', 'isoNumber', 'eduPersonPrimaryAffiliation', 'employeeStatus',
						'employeePhone', 'mail', 'givenName', 'sn', 'cn', 'employeePrimaryOrgReporting'
					);
					public $getFlexData			= true;

					private $net_id				= '';

					public function __construct($GET_ticket = '') // @$_GET['ticket']
					{
						/*****
						 * Called from login_external .php and login_functions .php
						 * Jump to webauth for user to log in, then returns to our site (and then this function is called again)
						 * Sets $_SESSION['webauth_data']['netid'] on success, on fail returns empty.
						 */

						$_SESSION['webauth_data']	= array();
						$this->webauth_return_url	= 'https://' . $_SERVER["HTTP_HOST"] . '/account/login.php';

						if ($GET_ticket) {
							//-------------------- STEP 2 -- "fopen" -- coming from webauth, if successful get customer data --------------
							$this->restore_GET();
							$this->customer_url_data	= $this->webauth_url . '/serviceValidate?ticket=' . $_GET['ticket'] . '&service=' . $this->webauth_return_url;
							$this->customer_data			= fopen($this->customer_url_data, 'r');

							$xml = '';
							while ($line = fgets($this->customer_data))
								$xml .= $line;

							$xmlp = xml_parser_create();
							xml_parse_into_struct($xmlp, $xml, $this->customer_data, $index);
							xml_parser_free($xmlp);

							if ($GLOBALS['DEBUG_DEBUG']) {
								// echoed once in top.inc .php - as "Login Return Data"
								$_SESSION['eds_data_debug'] .= '<hr><pre>webauthNetid function $index: ' . print_r($index, true) . '</pre>';
								$_SESSION['eds_data_debug'] .= '<pre>webauthNetid function customer_data: ' . print_r($this->customer_data, true) . '</pre>';
								//$_SESSION['eds_data_debug'] .= '<pre><hr>var_dump webauthNetid function: '; var_dump($this->customer_data); echo '</pre>';
							}

							if ($this->customer_data[$index['CAS:USER'][0]]['value'] && !isset($index['CAS:AUTHENTICATIONFAILURE'])) {
								//--------------- success!!! we have a valid ticket -----------------------
								$_SESSION['webauth_data']['netid'] = $this->customer_data[$index['CAS:USER'][0]]['value'];
								// $dbKey = $this->customer_data[$index['CAS:DBKEY'][0]]['value']; // Only for Extended web service.
							} else // $index['CAS:AUTHENTICATIONSUCCESS']
							{
								//------------------ login fail!!!!! ---------------------------
								$_SESSION['webauth_data']['netid'] = '';
							}
						} else {
							//--------------------------- STEP 1 -- locationHref -- JUMP TO WEBAUTH URL ----------------------------
							$this->login_url	= $this->webauth_url . '/login?banner=' . $this->description . '&service=' . $this->webauth_return_url;
							$this->save_GET(); // sets get vars to saved session vars - undoes what restore_GET function did below.
							if (!@$_GET['logout'])
								locationHref($this->login_url);
							exit;
						}
					}


					public function getEdsInfo($net_id_str, $getFlexData = true, $singleField = '')
					{
						/***************
						 * INPUT:
						 *		$net_id_str: The netid should have been obtained through webauth login - $_SESSION['webauth_data']['netid']
						 *		$getFlexData: If true (!$ignore_t2) then call T2 GetEntity class (uses $mysqli2)
						 * OUTPUT:
						 *		Sets the $_SESSION['eds_data'] array.
						 * For uits EDS access and documentation, see https://siaapps.uits.arizona.edu/home/accounts/
						 * Using 'Webauth URL' (prefered): Then uses url to get eds data. (No LDAP, so this is the goal)
						 *	OLD way is 'Webauth Login' which uses ldap EDS service to get customers' data via their netid.
						 *
						 * NOTE: Using the username and bindPw, can search individual:  https://eds.arizona.edu/people/16808753 | jbrabec | ...
						 *	NOTE: Eds can't get mailing addresses, but Joann can, but that data is 24 hours old.
						 */

						global $mysqli2;

						$this->getFlexData		= $getFlexData;

						$this->net_id				= $net_id_str;
						$_SESSION['eds_data']	= array();

						// set some DEFAULT values:
						$_SESSION['eds_data']['isonumber']	= ''; // cat card id, ENTITY.ENT_TERTIARY_ID
						$_SESSION['eds_data']['class']		= 'No Classification';

						if (!$this->net_id) exitWithBottom('ERROR: No Net ID');

						if ($singleField) {
							unset($this->propertiesToLoad);
							$this->propertiesToLoad = array($singleField);
						}

						$this->edsURL($this->net_id, $this->propertiesToLoad);

						if ($this->getFlexData) {
							if (!isset($mysqli2)) $mysqli2 = new database();
							// $ent_uid, $dbkey, $emplid, $netid, $uid_class_string, $catcardid, $update_T2=true, $T2_netid_update=true)
							$ent_cuinfo = new GetEntity('', $_SESSION['eds_data']['dbkey'], $_SESSION['eds_data']['emplid'], $_SESSION['eds_data']['netid'], $_SESSION['eds_data']['class'], $_SESSION['eds_data']['isonumber']);
							$ent_cuinfo->setEntSession();
							if (($GLOBALS['database_test_db'] || $GLOBALS['jody']) && @$_SESSION['netidMorph']) {
								// The following vars will ALWAYS be set the actual PTS admin who is morphing:
								//		$_SESSION['webauth_data']['netid']
								//		$_SESSION['entity']['EMAIL_ADDRESS']
								//		$_SESSION['eds_data']['mail']
								$_SESSION['entity']['EMAIL_ADDRESS']	= $_SESSION['webauth_data']['netid'] . '@email.arizona.edu';
								$_SESSION['eds_data']['mail']				= $_SESSION['webauth_data']['netid'] . '@email.arizona.edu';
							}
							if ($GLOBALS['DEBUG_DEBUG']) echo $ent_cuinfo;
						} else {
							$ent_cuinfo = '';
						}

						$this->refineMoreEDS();

						if ($GLOBALS['DEBUG_DEBUG'])
							$_SESSION['eds_data_debug'] .= "<hr>getEdsInfo function - ALL session vars:<pre> " . print_r($_SESSION, true) . "</pre>";

						return $ent_cuinfo;
					}


					private function edsURL($net_id, $edsProperties)
					{
						/*****
						 * called from getEdsInfo function.
						 * INPUT:
						 *		$net_id (customer has already logged in via webauth).
						 *		$edsProperties - which eds data to fetch (like netid, etc).
						 *		$eds_user and $eds_pass is our our admin account (below) - so can get eds data on anybody.
						 * OUTPUT: calls setEdsSession.
						 */

						$eds_user	= 'parking-permits'; // lowercase
						$bindPw		= 'gv0TxVGrtYJGg8VLo8omZ9Y48tHkpvN6'; // yBShXaENyEdMnL7XCzNb2G6yivbzKqNc
						$eds_pass	= stripslashes(str_replace('\\', '\\\\', $bindPw)); // the password allows backslashes but php screws them up

						$url	= 'https://eds.arizona.edu/people/' . $net_id; // could be uaid, etc
						if (($GLOBALS['database_test_db'] || $GLOBALS['jody']) && @$_SESSION['netidMorph']) {
							$url	= 'https://eds.arizona.edu/people/' . $_SESSION['netidMorph'];
						} else {
							unset($_SESSION['netidMorph']);
						}

						$cred	= sprintf('Authorization: Basic %s', base64_encode($eds_user . ':' . $eds_pass));
						$opts	= array('http' => array('method' => 'GET', 'header' => $cred));
						$ctx	= stream_context_create($opts);

						// send our request and retrieve the DSML response
						$dsml	= file_get_contents($url, false, $ctx);
						$xml	= new SimpleXMLElement($dsml);

						// set namespace context for XPath query (may not need this)
						$xml->registerXPathNamespace('dsml', 'http://www.dsml.org/DSML');

						foreach ($edsProperties as $k_p => $aProp) {
							if ($aProp) {
								//	if ($GLOBALS['jody']) {
								//		$xquery = "//dsml:entry/dsml:attr/dsml:value";
								//		$eds_vals_url = $xml->xpath($xquery);
								//		echo '<pre>$xquery: '.$xquery.'<br>';
								//		var_dump($eds_vals_url);
								//		exitWithBottom('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
								//	}
								$xquery = "//dsml:entry/dsml:attr[@name='" . $aProp . "']/dsml:value";
								$eds_vals_url = $xml->xpath($xquery);
								$this->setEdsSession($aProp, $eds_vals_url);
							}
						}
					}


					private function setEdsSession($edsProperty, $edsvals)
					{
						/***
						 * Called by edsURL (was calld by edsLDAP, old way)
						 * Sets $_SESSION['eds_data'] array.
						 */

						foreach ($edsvals as $k => $v) // eds vals
						{
							/***
							 * This "foreach" block should only loop one time ($k !> 0).
							 * Note: The old way used 'edupersonaffiliation', which has multiple, but now using 'edupersonprimaryaffiliation'
							 */
							$kStr = (string) $k;
							$vStr = (string) $v;
							$vStr = trim($vStr); // just in case eds returns data with spaces.
							if ($kStr == (string) 'count') // for ldap data.
								continue;

							// Note how the EDS data has upper-case vars, whereas our session vars don't.
							switch ($edsProperty) {
								case 'uaId':
									$_SESSION['eds_data']['dbkey']	= $vStr; // dbkey, ENTITY.TERTIARY_UID
									if (@$_SESSION['eds_data']['dbkey'] == 'T737490097340196352') {
										// Test account, reduce to 12 chars.
										$_SESSION['eds_data']['dbkey'] = 'T73749009734'; //0196352
									}
									break;
								case 'eduPersonPrimaryAffiliation':
									// Make $classificationStr string match getEslDefUid() in Flex_Entities .php -- setEslDefs
									// To get a nice live random list, search:	https://eds.arizona.edu/people/co*
									if ($vStr == 'admit')					$classificationStr = 'Student';
									else if ($vStr == 'studentworker')	$classificationStr = 'Student';
									else if ($vStr == 'student')			$classificationStr = 'Student';
									else if ($vStr == 'gradasst')		$classificationStr = 'GradAsst';
									else if ($vStr == 'employee')		$classificationStr = 'Employee';
									else if ($vStr == 'staff')			$classificationStr = 'Employee';
									else if ($vStr == 'faculty')			$classificationStr = 'Employee';
									else										$classificationStr = 'No Classification'; // Default
									// same as $_SESSION['entity']['classificationStr']
									$_SESSION['eds_data']['class'] = $classificationStr;
									break;
								case 'uid':
									$_SESSION['eds_data']['netid']	= $vStr; // netid
									break;

								default:
									// Make key lowercase.
									if ($edsProperty) {
										$tmpKey = strtolower($edsProperty);
										$_SESSION['eds_data'][$tmpKey] = $vStr;
									}
									break;
							}
						}
					}


					private function refineMoreEDS()
					{
						/***
						 * Not sure why this is not in setEdsSession function.
						 */
						if ($_SESSION['eds_data']['employeeprimaryorgreporting'] && preg_match('/^.*:([^:]+):([\w\d_]+)\-([^:]+):.*$/si', $_SESSION['eds_data']['employeeprimaryorgreporting'])) {
							/***
							 * A colon (:) separated list containing a PCN (position control number), the Roster Department number for that position,
							 * the college to which the employee reports (formatted as college code and college description, separated by a dash (-)),
							 * and the VP to which the employee reports (formatted as VP code and VP description, separated by a dash (-)).
							 * example:  1867632:9804:PRKG-Parking and Transportation:FCLT-Facilities
							 */
							$_SESSION['eds_data']['deptname']	= preg_replace('/^.*:([^:]+):([\w\d_]+)\-([^:]+):.*$/si', '$3', $_SESSION['eds_data']['employeeprimaryorgreporting']);
							$_SESSION['eds_data']['deptno']		= preg_replace('/^.*:([^:]+):([\w\d_]+)\-([^:]+):.*$/si', '$1', $_SESSION['eds_data']['employeeprimaryorgreporting']);
							if ($_SESSION['eds_data']['deptno'] != '' && ctype_digit($_SESSION['eds_data']['deptno'])) {
								// pad deptno with 0's
								$_SESSION['eds_data']['deptno']		= '0000' . $_SESSION['eds_data']['deptno'];
								$_SESSION['eds_data']['deptno']		= preg_replace('/^\d*(\d{5})$/si', '$1', $_SESSION['eds_data']['deptno']);
							}
						}
						// If givenName has only ONE space, then separate into first name / middle name. Or else put entire givenName into first name.
						$_SESSION['eds_data']['givenname_fn'] = preg_replace('/^([^\s]+)(\s+)([^\s]+)$/si', '$1', $_SESSION['eds_data']['givenname']);
						$_SESSION['eds_data']['givenname_mi'] = preg_replace('/^([^\s]+)(\s+)([^\s]+)$/si', '$3', $_SESSION['eds_data']['givenname']);
						// If givenname doesnt have exactly one space (in which preg_replace failed), then givenname_fn & givenname_mi will = givenname, so erase givenname_mi.
						if ($_SESSION['eds_data']['givenname_fn'] == $_SESSION['eds_data']['givenname'])	$_SESSION['eds_data']['givenname_mi'] = '';
					}



					private function save_GET()
					{
						/***
						 * Save GET vars into SESSION array GET_VARS before going off to webauth url.
						 * After webauth comes back here, restore the GET vars.
						 */
						$_SESSION['GET_VARS'] = array();
						foreach (@$_GET as $k_g => $v_g) {
							if ($k_g == 'ticket')
								continue;
							else
								$_SESSION['GET_VARS'][$k_g] = $v_g;
						}
					}

					private function restore_GET()
					{
						foreach ($_SESSION['GET_VARS'] as $k_g => $v_g) {
							if ($k_g == 'ticket')
								continue;
							@$_GET[$k_g] = $v_g;
						}
						$_SESSION['GET_VARS'] = array();
						unset($_SESSION['GET_VARS']);
					}
				}




				//	################################################## OLD WAY
				//xxxxxx $this->edsLDAP($this->net_id, $this->propertiesToLoad); // old way (ldap)
				//private function edsLDAP($net_id, $edsProperties)	{
				//	//	* INPUT:		$eds_user and $eds_pass is our admin account, to get eds data on anybody we wish.
				//	//	*		$net_id (customer has already logged in via webauth).
				//	//	*		$edsProperties - which eds data to fetch (like netid, etc).
				//	//	* OUTPUT: calls setEdsSession.
				//	$eds_user	= ''; $bindPw		= ''; $eds_pass	= stripslashes(str_replace('\\','\\\\',$bindPw));
				//	$ldapUrl			= 'ldaps://eds.arizona.edu';  $bindDn			= 'uid='.$eds_user.',ou=App Users,dc=eds,dc=arizona,dc=edu';
				//	$searchBase		= 'ou=People,dc=eds,dc=arizona,dc=edu'; $searchFilter	= '(uid='.$net_id.')';
				//	$ldap = ldap_connect($ldapUrl); if (!$ldap)	exitWithBottom('Could not connect to LDAP server');
				//	if (!ldap_bind($ldap, $bindDn, $eds_pass)) exitWithBottom(ldap_error($ldap));
				//	if (($sr = ldap_search($ldap, $searchBase, $searchFilter)) == FALSE)	exitWithBottom(ldap_error($ldap));
				//	// This gets everything, but we are only using to get $entCt (count). Also, using for debugging.
				//	$entry = ldap_get_entries($ldap, $sr);	$entCt = $entry['count'];
				//	if ($entCt > 1) exitWithBottom ('ERROR: Too many entities! ' . CONTACT_CR);
				//	elseif ($entCt < 1) exitWithBottom ('ERROR: Entity not found! For further assistance ' . CONTACT_CR);
				//	// MULTI ENTITIES: for ($entryID=ldap_first_entry($ldap,$sr); $entryID!=false; $entryID=ldap_next_entry($ldap,$entryID))
				//	$entryID = ldap_first_entry($ldap, $sr);
				//	if ($entryID)	{	foreach($edsProperties as $k_p => $aProp) {		if ($aProp) {
				//				$eds_vals_ldap = array();		$eds_vals_ldap = @ldap_get_values($ldap, $entryID, $aProp);
				//				if (sizeof($eds_vals_ldap))			$this->setEdsSession($aProp, $eds_vals_ldap);
				//				else	if ($GLOBALS['DEBUG_DEBUG']) echo '<hr>DEBUG WARNING: no $eds_vals_ldap values found<hr>'."\n";
				//	}	}	}	}



				/*** OLD NOTES, FUNCTIONS
//Changing \$classificationStr from $classificationStr to GradAsst</DIV>";
//	$classificationStr = 'GradAsst';
// 2013-07-27  if ($classificationStr == 'StudentEmployee') $classificationStr = 'EmployeeStudent';
// elseif (!$classificationStr) $classificationStr = 'No Classification';
//	// Make these affiliate strings match getEslDefUid() in Flex_Entities .php
//	if ($vStr=='employee') $classificationStr .= 'Employee';
//	else if ($vStr=='student') $classificationStr .= 'Student';   //break;
				 */
				?>