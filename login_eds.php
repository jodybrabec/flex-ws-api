<?php

/***************
 * Use ldap EDS service to get customers' data:
 *		Cat Card #.
 *		Can't get MAIL address, but Joann can, but thta data is 24 hours old.
 * For my uits EDS access and documentation, see https://siaapps.uits.arizona.edu/home/accounts/
 * Using username and bindPw below, can search individual:
 *		https://eds.arizona.edu/people/16808753 | jbrabec | ...
 * keywords: A username and password are being requested by https://eds.arizona.edu. The site says: "EDS authentication required"
 */

//$searchFilter	= '(uaid=109106543384)'; // dbkey
//$searchFilter	= '(emplid=16808753)';

//$netid			= 'jbrent42'; // Can use wildcards, jbrabec
//$searchFilter	= '(uid='.$netid.')';

//$searchFilter	= '(emplid=03402650)';
//$ldapDATA = searchLDAP($searchFilter);
//if ($GLOBALS['DEBUG_DEBUG']) var_dump($ldapDATA);



function searchLDAP($searchFilter)
{
	/***
	 * called by Flex_Permittions.php
	 * returns $entryData[$aProperty][i]
	 *   (if using wildcards in searchFilter, then maybe do this: returns array $entryData[uaid][i][$aProperty])
	 */

	//if ($GLOBALS['jody'])
	//	exitWithBottom('hey nowwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwww');

	$username	= 'parking-permits';
	$bindPw		= 'gv0TxVGrtYJGg8VLo8omZ9Y48tHkpvN6'; // yBShXaENyEdMnL7XCzNb2G6yivbzKqNc

	$bindPw		= stripslashes(str_replace('\\', '\\\\', $bindPw)); // the password allows backslashes but php screws them up
	$username	= strtolower($username); // make netid lowercase.


	$ldapUrl			= "ldaps://eds.arizona.edu";
	$bindDn			= "uid=" . $username . ",ou=App Users,dc=eds,dc=arizona,dc=edu";
	$searchBase		= "ou=People,dc=eds,dc=arizona,dc=edu";

	// establish LDAP connection
	$ldap = ldap_connect($ldapUrl);
	if (!$ldap)	exitWithBottom("Could not connect to LDAP server");

	// bind as app user
	if (!ldap_bind($ldap, $bindDn, $bindPw)) exitWithBottom(ldap_error($ldap));

	$entryData = array();

	if (($sr = ldap_search($ldap, $searchBase, $searchFilter)) == FALSE)	exitWithBottom(ldap_error($ldap));

	// if ($GLOBALS['DEBUG_DEBUG']) echo "<hr>ldap_search ==== \$searchBase:$searchBase ==== \$searchFilter:$searchFilter ====\n\n";

	/****   // ############# HUGE, GET'S EVERYTHING UNDER THE SUN!!!
	$entry = ldap_get_entries($ldap, $sr);
	echo '................. COUNT: '.$entry["count"].'<br>';
	echo do_dump($entry);
	exitWithBottom('<hr><br>done......................<hr>');  // */

	// see http://sia.uits.arizona.edu/eds_attributes
	// First array MUST be "uaid" $propertiesToLoad[0]
	$propertiesToLoad = array(
		'uaid', 'emplid', 'uid', 'sn', 'givenname', 'isonumber', 'edupersonprimaryaffiliation', 'edupersonaffiliation',
		'modifytimestamp', 'mail', 'employeephone', 'dateofbirth', 'studentinforeleasecode'
	);

	$i = 0;
	for ($entryID = ldap_first_entry($ldap, $sr); $entryID != false; $entryID = ldap_next_entry($ldap, $entryID)) {
		if (++$i == 66)	break;
		// if ($GLOBALS['DEBUG_DEBUG']) echo "\n================ $i (max 66) ====================\n";

		$uaid			= ''; // dbkey, ENTITY.TERTIARY_UID
		$emplid		= '';
		$uid			= ''; // netid
		$isonumber	= ''; // cat card id, ENTITY.ENT_TERTIARY_ID
		$classificationStr = '';

		foreach ($propertiesToLoad as $k_p => $aProperty) {
			unset($vals);
			$vals = @ldap_get_values($ldap, $entryID, $aProperty);
			if (@is_array($vals)) {
				foreach (@$vals as $k => $v) {
					$kStr = (string) $k;
					$vStr = (string) $v;
					if ($kStr == (string) 'count')
						continue;

					// if ($GLOBALS['DEBUG_DEBUG']) echo '~~~~~~~~~~~~~' . $aProperty . '['.$kStr.']: ' . $vStr . "\n";

					switch ($aProperty) {
						case 'uaid':
							$uaid = $vStr; // dbkey
							break;
						case 'emplid':
							$emplid = $vStr;
							break;
						case 'uid':
							$uid = $vStr; // netid
							break;
						case 'edupersonaffiliation': // don't think this is being used - should be using eduPersonPrimaryAffiliation
							// http://www.iia.arizona.edu/eds_attributes#edupersonaffiliationoverview
							// Make these affiliate strings match getEslDefUid() in Flex_Entities .php
							if ($vStr == 'employee' || $vStr == 'staff' || $vStr == 'faculty' || $vStr == 'studentworker' || $vStr == 'gradasst')
								$classificationStr .= 'Employee';
							else if ($vStr == 'student')
								$classificationStr .= 'Student';
							break;
						case 'isonumber':
							$isonumber = $vStr;
							break;
					}
					$entryData[$aProperty][$kStr] = $vStr;
				}
				if ($classificationStr == 'StudentEmployee')
					$classificationStr = 'EmployeeStudent';
				elseif (!$classificationStr)
					$classificationStr = 'No Classification';
			} else {
				// if ($GLOBALS['DEBUG_DEBUG']) echo "\n";
			}
		}

		//if (!isset($mysqli2)) $mysqli2 = new database();
		//if ($GLOBALS['DEBUG_DEBUG']) echo "trying this: GetEntity(".$mysqli2->WS_CONN.", '', $uaid, $emplid, $uid, $classificationStr);\n\n";
		// ($wsConn, $ent_uid='', $dbkey='', $emplid='', $netid='', $uid_class_string='', $catid='', $update_T2=true, $T2_netid_update=true)
		//	$entity = new GetEntity('', $uaid, $emplid, $uid, $classificationStr);
		//	if ($GLOBALS['DEBUG_DEBUG']) echo $entity;
		//	if ($entity->ENT_UID)
		//	{
		//		if ($GLOBALS['DEBUG_DEBUG']) echo "FINALLY, FOUND!!!\n\n";
		//	}

	}
	return $entryData;
}



//for ($i=0; $i<$entry["count"]; $i++) {
//	if ($i==20)	break;
//	$prop = 'eduPersonAffiliation';
//	$vals = ldap_get_values($ldap, $entry, $prop);
//	foreach($vals as $k => $v) {
//		if ($GLOBALS['DEBUG_DEBUG']) echo $prop . ': ' . $k . ': ' . $v . "\n";
//	}
//}

//$entry = ldap_first_entry($ldap, $sr);
//if ($entry) {
//	$prop = 'eduPersonAffiliation';
//	$vals = ldap_get_values($ldap, $entry, $prop);
//	foreach($vals as $k => $v) {
//		echo $prop . ': ' . $k . ': ' . $v . "\n";
//	}
//	$prop = 'emplid';
//	$vals = ldap_get_values($ldap, $entry, $prop);
//	foreach($vals as $k => $v) {
//		echo $prop . ': ' . $k . ': ' . $v . "\n";
//	}
//}

//ldap_close($ldap);
