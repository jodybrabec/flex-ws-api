<?php

require_once '/var/www2/include/flex_ws/config.php';

abstract class Flex_Entities extends Flex_Funcs
{
	/**************************
	 * Documentation: http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=32
	***************************/

	public $flex_group = 'T2_FLEX_ENTITIES';

	public $UA_DBKEY			= ''; // uaid - Unique ID from LDAP
	public $ENT_UID			= '';
	public $EMPLID				= ''; // (ENTITY_VIEW.ENT_PRIMARY_ID)
	public $NETID				= ''; // (ENTITY_VIEW.ENT_SECONDARY_ID)
	public $CATCARD_ID		= ''; // isonumber - used for garage-permits only (catcard gate-arm readers)

	//------------------- Many of  the following vars are in $entity array.

	/***
	 * $t2_query[Query Name] = Query UID (QUERY_DEF.QDE_NAME => QUERY_DEF.QDE_UID)
	 * For use with API ExecuteQuery class (in Flex_Misc .php)
	 * Query UIDs and Query Names found here: http://128.196.6.197/PowerPark/qm/default.aspx
	 */
	protected static $t2_query = array( //---- LIVE and test DBs
		'Q_GetEntity_all'					=> 10826,
		'Q_getOptInData'					=> 10810,
		'Q_GetEntity_ent'					=> 7349,
		'Q_GetEntity_uaid'				=> 7348,
		'Q_GetEntity_net_and_emp'		=> 7350,
		'Q_GetEntity_net_or_emp'		=> 7464,
		'Q_GetAddressEmail_coe'			=> 7352,
		'Q_GetAddressEmail_coe_all'	=> 7466,
		'Q_GetAddressEmail_cor'			=> 7351,
		'Q_GetAddressEmail_cor_all'	=> 7465,
	);

	/***
	 * $cust_fld_svc.  DATA_DICTIONARY.DAD_FIELD => CONFIG_TABLE.TAB_NAME  (points to DATA_DICTIONARY.TAB_UID)
	 * For use in InsertUpdateCustomFlds (in config .php)
	 */
	protected static $cust_fld_svc = array(
			'OPTIN'			=> 'ENTITY',		// (OptInText, dad_uid=205063)
	);

	//------------------- Entity Email ------------------
	public $Email_Uid			= ''; // COE_EMAIL.COE_UID -- notice "COE" is for email. (for XML it's upper-case)
	// In test db, all NON-EMPTY email_address are changed to PTS-IT-Emails@email.arizona.edu
	public $EMAIL_ADDRESS	= ''; // From COR_EMAIL_VIEW
	public $EMAIL_DEFAULT	= G_EMAIL_FROM; //
	public $EMAIL_TYPE		= ''; // Email type code (COE_ADD_TYPE_LKP.CEL_UID). (default '2004' is WEB) (used to be '1' which is Other)

	//------------------- Entity Address ------------------
	public $Address_Uid		= ''; // lowercase for output
	public $ADDRESS_TYPE		= ''; // is CAL_UID_TYPE: default is 2008, which is web type.  7016 is UA address type.
	public $COR_PRIMARY_STREET	= '';
	public $COR_SUITE_APARTMENT= '';
	public $SECONDARY_STREET= '';
	public $COR_CITY			= '';
	public $STL_UID_STATE	= '';
	public $COR_COUNTRY		= '';
	public $COR_POSTAL_CODE	= '';

	//-------------- Both Email and Address classes -------------
	public $ADDRESS_PRIORITY= ''; // If no address priority is passed on an insert, the address will be inserted at priority 1.
	public $START_DATE		= ''; // Date address becomes active.
	public $END_DATE			= ''; // DATE address becomes inactive.

	// $esl_subclass is a subset of values in ENT_SUBCLASSIFICATION_MLKP.ESL_UID.
	// esl_subclass is OUR version of what we get from webauth - 2008 (Employee), 2024 (Student),
	//		2036 (GradAsst), or default is 2016 (NoClass) - see setEslDefs()
	//	If existing data ENTITY.ESL_UID_SUBCLASS is NOT one of the for numbers above, then defaults to 2016
	public $esl_subclass		= '';
	protected $esl_def		= array();

	public $sqlHist			= array();

	protected function setEslDefs()
	{
		/***
		 * Set the Subclassification var definitions ($esl_def)
		 * Is a subset (four numbers) of a subset of values in ENT_SUBCLASSIFICATION_MLKP.ESL_UID.
		 * Should probably just make these static vars.
		 * see also edupersonprimaryaffiliation at http://www.iia.arizona.edu/eds_attributes
		 * Note: T2 derrives Classification from SubClassification.
		 */
		$aDef = array();
		$aDef['esl_Student']	= 2024; //23 // Student	(old PP pecid is 18)
		$aDef['esl_Employee']= 2008; //08 // Employee	(old PP pecid is 44)
		$aDef['esl_GradAsst']= 2036; //   // Grad Associate Assistant
		$aDef['esl_NoClass']	= 2016; //16 // No Classification
		// esl 2028: visitor
		// esl 2014: no longer affilliated
		// esl 2021: Retiree
		return $aDef;
	}

	protected function getEslString($esl_uid)
	{
		/***
		 * Does the Opposite of getEslDefUid.
		 */
		$esl_uid = trim($esl_uid); // ya never know!
		switch ($esl_uid) {
			case '2008':
				$subclassStr	= 'Employee';
				break;
			case '2024':
				$subclassStr	= 'Student';
				break;
			case '2036':
				$subclassStr	= 'GradAsst';
				break;
			case '2016':
			default:
				$subclassStr	= 'NoClass';
				break;
		}
		return $subclassStr;
	}

	protected function getEslDefUid($subclassStr, $defAry)
	{
		/***
		 * Sets $esl_subclass (see notes for $esl_subclass declaration)
		 * Converts the $subclassStr integer esl id into a string, and returns it.
		 * See login.php -- $subclassStr could be "EmployeeStudent", in which case default is used below here.
		 */
		switch ($subclassStr) {
			case 'Employee':
				$esl_uid	= $defAry['esl_Employee'];
				break;
			case 'Student':
				$esl_uid	= $defAry['esl_Student'];
				break;
			case 'GradAsst':
				$esl_uid	= $defAry['esl_GradAsst'];
				break;
			case 'No Classification':
			case 'EmployeeStudent':
			default:
				$esl_uid	= $defAry['esl_NoClass'];
				break;
		}
		return $esl_uid;
	}

	public function is_email($email)
	{
		if(filter_var($email, FILTER_VALIDATE_EMAIL))
			return true;
		else
			return false;
	}

	protected function set_callback()
	{
		//Called from config.php  - usually to set more vars besides those set by Flex_Funcs::setVars
	}
}


class InsertUpdateEmailAddress extends Flex_Entities
{
	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';
	public $return_xml		= ''; // massaged return_page

	//--------------------- Input XML Parameters ----------------------
	// EMAIL_ADDRESS	- parent -	Required
	// Email_Uid		- parent -	Required if updating
	// EMAIL_TYPE		- parent -	Required if inserting (default '2004' is WEB)
	// ENT_UID			- parent -	Required (ENTITY.ENT_UID)

	//-------------------- Natural XML Output
	// Email_Uid		- parent

	public $flex_function	= 'InsertUpdateEmailAddress';

	public function __construct($EID, $e_address, $coe_UID='', $coe_type=2004, $priority=1, $stDate='now')
	{
		Debug_Trace::traceFunc();
		parent::__construct();

		if (trim($EID))
		{
			// Insert customer email address.
			$this->ENT_UID = strtoupper(trim($EID));
		}
		else if ($coe_UID)
		{
			// UPDATING existing customer email address (coe_UID).
			$this->Email_Uid =	$coe_UID;
		}
		else
		{
			$errCode = 2280;
			$errX = 'ERROR: Could not update or insert EMAIL address!!'; // friendly err message.

			// ($event_type_text, $event_info_text, $ENT_UID, $PAYMENT, $TRANSACTION_ID, $INFO1, $INFO2=null)
			new event_log('login_transaction', 'error', @$_SESSION['entity']['ENT_UID'], '0', '', makeErrorCode($errCode,$errX));

			exitWithBottom('<b>CODE ' . getErrorCode($errCode) . '</b>, ' . CONTACT_CR);
		}

		$this->EMAIL_ADDRESS	= $e_address;
		$this->EMAIL_TYPE		= $coe_type;

		if ($coe_UID)
			$this->Email_Uid			= $coe_UID;
		$this->ADDRESS_PRIORITY		= $priority; // Probably always will be class' default value.
		if ($stDate == 'now') {
			$this->START_DATE			= date('m/d/Y');
		} else if ($stDate) {
			$this->START_DATE			= $stDate;
		} else {
			$errCode = 2281;
			$errX = 'ERROR: No Date Set';

			// ($event_type_text, $event_info_text, $ENT_UID, $PAYMENT, $TRANSACTION_ID, $INFO1, $INFO2=null)
			new event_log('login_transaction', 'error', @$_SESSION['entity']['ENT_UID'], '0', '', makeErrorCode($errCode,$errX));

			exitWithBottom('<b>CODE ' . getErrorCode($errCode) . '</b>, ' . CONTACT_CR);
		}

		$this->send_xml();
	}

	protected function get_xml()
	{
		Debug_Trace::traceFunc();
		/****  This function returns xml data.  ***/
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('ENTITY_UID',			$this->ENT_UID);
		$this->xml_data .= make_param('EMAIL_UID',			$this->Email_Uid);
		$this->xml_data .= make_param('EMAIL_TYPE',			$this->EMAIL_TYPE);
		// In test db, all NON-EMPTY email_address are changed to PTS-IT-Emails@email.arizona.edu
		$this->xml_data .= make_param('EMAIL_ADDRESS',		$this->EMAIL_ADDRESS);
		$this->xml_data .= make_param('ADDRESS_PRIORITY',	$this->ADDRESS_PRIORITY);
		$this->xml_data .= make_param('START_DATE',			$this->START_DATE);
		$this->xml_data .= make_param('END_DATE',				$this->END_DATE);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

	protected function set_callback()
	{
		//Called from config.php - to pricess return data
		if ($_SESSION['entity']['Email_Uid'])
		{
			$_SESSION['entity']['Email_Uid'] = $this->Email_Uid;
			$_SESSION['entity']['EMAIL_ADDRESS'] = $this->EMAIL_ADDRESS;
		}
	}
}



class InsertUpdateAddress extends Flex_Entities
{
	/**********************************
	If updating address using the Address_Uid: If an address priority is passed then the method will reprioritize all addresses
	for the customer in order to place the address at the specified priority
	position. If a priority greater than that of any existing address is passed, then the address should be moved to the last position.
	***********************************/

	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';
	public $return_xml		= ''; // massaged return_page

	//--------------------- Input XML Parameters ----------------------
	// ENT_UID - parent - Required if inserting, No if updating. (Do not pass this parameter for an update!!!)  (ENTITY.ENT_UID)

	//-------------------- Natural XML Output
	// Returns $Address_Uid if inserting new address.

	public $flex_function	= 'InsertUpdateAddress';

	public function __construct($EID='', $corUID='', $street='', $apt='', $city='', $state='', $zip='', $country='United States of America', $priority=1, $atype=2008, $stDate='now')
	{
		Debug_Trace::traceFunc();
		parent::__construct();

		if (trim($EID)) {
			// INSERTING customer address.
			$this->ENT_UID = strtoupper(trim($EID));
		} else {

			$errCode = 3322;
			$errY = 'ERROR: Could not update or insert address!!'; // friendly err message.

			// ($event_type_text, $event_info_text, $ENT_UID, $PAYMENT, $TRANSACTION_ID, $INFO1, $INFO2=null)
			new event_log('login_transaction', 'error', @$_SESSION['entity']['ENT_UID'], '0', '', makeErrorCode($errCode,$errY));

			exitWithBottom('<b>CODE ' . getErrorCode($errCode) . '</b>, ' . CONTACT_CR);
		}

		if ($corUID)
		{
			// UPDATING existing customer address. i.e. $corUID = $_SESSION['entity']['Address_Uid']
			$this->Address_Uid =			$corUID;
		}

		$this->COR_PRIMARY_STREET =	$street;
		$this->COR_SUITE_APARTMENT =	$apt;

		$this->COR_CITY =					$city;
		$this->STL_UID_STATE =			$state;
		$this->COR_POSTAL_CODE =		$zip;
		$this->COR_COUNTRY =				$country;
		$this->ADDRESS_PRIORITY =		$priority; // Probably always will be class' default value.
		$this->ADDRESS_TYPE =			$atype; // default is 2008, web type

		if ($stDate == 'now')
		{
			$this->START_DATE =			date('m/d/Y');
		}
		else if ($stDate)
		{
			$this->START_DATE =			$stDate;
		}
		else
		{

			$errCode = 3344;
			$errY = 'ERROR: No Date Set'; // friendly err message.

			// ($event_type_text, $event_info_text, $ENT_UID, $PAYMENT, $TRANSACTION_ID, $INFO1, $INFO2=null)
			new event_log('login_transaction', 'error', @$_SESSION['entity']['ENT_UID'], '0', '', makeErrorCode($errCode,$errY));

			exitWithBottom('<b>CODE ' . getErrorCode($errCode) . '</b>, ' . CONTACT_CR);
		}

		$this->send_xml();
	}

	protected function get_xml()
	{
		Debug_Trace::traceFunc();
		/****  This function returns xml data.  ***/
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('ENTITY_UID',			$this->ENT_UID);
		$this->xml_data .= make_param('ADDRESS_UID',			$this->Address_Uid);
		$this->xml_data .= make_param('PRIMARY_STREET',		$this->COR_PRIMARY_STREET);
		$this->xml_data .= make_param('SUITE_APT',			$this->COR_SUITE_APARTMENT);
		$this->xml_data .= make_param('CITY',					$this->COR_CITY);
		$this->xml_data .= make_param('STATE_UID',			$this->STL_UID_STATE);
		$this->xml_data .= make_param('POSTAL_CODE',			$this->COR_POSTAL_CODE);
		$this->xml_data .= make_param('COUNTRY',				$this->COR_COUNTRY);
		$this->xml_data .= make_param('ADDRESS_PRIORITY',	$this->ADDRESS_PRIORITY);
		$this->xml_data .= make_param('START_DATE',			$this->START_DATE);
		$this->xml_data .= make_param('ADDRESS_TYPE',		$this->ADDRESS_TYPE);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

	protected function set_callback()
	{
		if ($_SESSION['entity']['ENT_UID'] && $_SESSION['entity']['ENT_UID'] == $this->ENT_UID) // probably don't need this
		{
			if ($this->COR_PRIMARY_STREET && $this->COR_POSTAL_CODE)
			{
				$_SESSION['entity']['Address_Uid'] = $this->Address_Uid;
				$_SESSION['entity']['COR_PRIMARY_STREET'] = $this->COR_PRIMARY_STREET;
				$_SESSION['entity']['COR_SECONDARY_STREET'] = $this->COR_SECONDARY_STREET;
				$_SESSION['entity']['COR_SUITE_APARTMENT'] = $this->COR_SUITE_APARTMENT;
				$_SESSION['entity']['COR_CITY'] = $this->COR_CITY;
				$_SESSION['entity']['STL_UID_STATE'] = $this->STL_UID_STATE;
				$_SESSION['entity']['COR_POSTAL_CODE'] = $this->COR_POSTAL_CODE;
				$_SESSION['entity']['ADDRESS_TYPE_IS_UA'] = $this->ADDRESS_TYPE_IS_UA;
				$_SESSION['entity']['ADDRESS_PRIORITY'] = $this->ADDRESS_PRIORITY;
			}
			else
			{
				// Probably calling from from address .php (where $corUID is the only parameter set, for sole purpose of moving address up to priority 1 in the address list).
				$tmpAdr = new GetAddressEmail($_SESSION['entity']['ENT_UID']);
				if ($GLOBALS['DEBUG_DEBUG']) echo $tmpAdr;

				$_SESSION['entity']['Address_Uid'] = $tmpAdr->allInfoAddresses['Address_Uid'][0];
				$_SESSION['entity']['COR_PRIMARY_STREET'] = $tmpAdr->allInfoAddresses['COR_PRIMARY_STREET'][0];
				$_SESSION['entity']['COR_SECONDARY_STREET'] = $tmpAdr->allInfoAddresses['COR_SECONDARY_STREET'][0];
				$_SESSION['entity']['COR_SUITE_APARTMENT'] = $tmpAdr->allInfoAddresses['COR_SUITE_APARTMENT'][0];
				$_SESSION['entity']['COR_CITY'] = $tmpAdr->allInfoAddresses['COR_CITY'][0];
				$_SESSION['entity']['STL_UID_STATE'] = $tmpAdr->allInfoAddresses['STL_UID_STATE'][0];
				$_SESSION['entity']['COR_POSTAL_CODE'] = $tmpAdr->allInfoAddresses['COR_POSTAL_CODE'][0];
				$_SESSION['entity']['ADDRESS_TYPE_IS_UA'] = $tmpAdr->allInfoAddresses['ADDRESS_TYPE_IS_UA'][0];
				$_SESSION['entity']['ADDRESS_PRIORITY'] = $tmpAdr->allInfoAddresses['ADDRESS_PRIORITY'][0];
			}
		}
		//if ($GLOBALS['jody']) {
		//	echo '<pre>$_SESSION[entity]<br>';
		//	var_dump($_SESSION['entity']);
		//	echo '$tmpAdr<br>';
		//	var_dump($tmpAdr);
		//	echoNow('--------</pre>');
		//}
	}
}


class GetAddressEmail extends Flex_Entities
{
	/**********************************************
	 * This class queries the DB to get customer's HIGHEST PRIORITY address and/OR email
	 * (this is assuming $corUID and $coe_UID are the already the highest - built for class GetEntity.
	***********************************************/

	//--------------------- Input Parameters ----------------------
	/*---- Parent Class vars:
	Address_Uid			// COR_UID
	ADDRESS_PRIORITY	// COR_EFFECTIVE_RANK
	Email_Uid			// COE_UID
	EMAIL_PRIORITY		// COE_EFFECTIVE_RANK
	------*/

	//--------------------- Output ----------------------
	/*
	-Same as above, and:
	EMAIL_ADDRESS			//
	COR_PRIMARY_STREET	//
	COR_SUITE_APARTMENT	//
	SECONDARY_STREET		//
	COR_CITY					//
	STL_UID_STATE			//
	COR_COUNTRY				//
	COR_POSTAL_CODE		//
	ADDRESS_TYPE			// is CAL_UID_TYPE: 2008 is web-inserted address type, 7016 is UA address type, 7018 is Grandfathered type.  See COR_ADD_TYPE_LKP table.
	ADDRESS_TYPE_IS_UA	// false if ADDRESS_TYPE (CAL_UID_TYPE) is 2008; true if 7016
	------*/

	public function __construct($entUID, $corUID='', $coe_UID='')
	{
		/***
		 * If $corUID and $coe_UID are both NOT set, then get all addresses AND emails for the entUID.
		 * If $corUID and/or $coe_UID set, get highest priority of address and/or email.
		 */
		Debug_Trace::traceFunc();

		if ($entUID && !$corUID && !$coe_UID)
		{
			//------------------- Get ALL addresses and email addresses via ONLY ent_uid -------------------------
			$qVars = array('entUID'=>$entUID);

			//-------- all addresses
			$t2Key	= 'Q_GetAddressEmail_cor_all';
			$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $qVars, $t2Key, get_class($this));
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			$tmpResults1	= $exeResults->results_custom;
			// $exeResults->setMoreCustom('COR_MODIFY_DATE', 'date_1'); // set dates to YYYY-mm-dd hh:mm:ss (military time)

			//-------- all emails
			$t2Key	= 'Q_GetAddressEmail_coe_all';
			$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $qVars, $t2Key, get_class($this));
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			$tmpResults2	= $exeResults->results_custom;

			if (sizeof($tmpResults1['COR_UID']))
			{
				$this->allInfoAddresses = $tmpResults1;
				foreach ($tmpResults1['CAL_UID_TYPE'] as $ak => $aCalUid)
				{
					if ($aCalUid == 7016 || $aCalUid == 7018)
					{
						// 7018 is Grandfathered type.
						$this->allInfoAddresses['ADDRESS_TYPE_IS_UA'][$ak] = 1;
						break;
					}
					else
					{
						$this->allInfoAddresses['ADDRESS_TYPE_IS_UA'][$ak] = 0;
					}
				}
			}
			if (sizeof($tmpResults2['COE_UID']))
			{
				$this->allInfoEmails = $tmpResults2;
			}

		}
		else
		{
			//--------------------- Set addresses and email addresses via $coe_UID and $corUID respectively -----------------------
			if ($corUID)
			{
				if (@$_SESSION['entity']['NETID'] && @$_SESSION['entity']['COR_PRIMARY_STREET'] && @$_SESSION['entity']['EMAIL_ADDRESS'])
				{
					$this->Address_Uid			= $corUID;
					$this->ADDRESS_PRIORITY		= $_SESSION['entity']['ADDRESS_PRIORITY'];
					$this->COR_PRIMARY_STREET	= $_SESSION['entity']['COR_PRIMARY_STREET'];
					$this->COR_SUITE_APARTMENT = $_SESSION['entity']['COR_SUITE_APARTMENT'];
					$this->COR_CITY				= $_SESSION['entity']['COR_CITY'];
					$this->STL_UID_STATE			= $_SESSION['entity']['STL_UID_STATE'];
					$this->COR_POSTAL_CODE		= $_SESSION['entity']['COR_POSTAL_CODE'];
					$this->ADDRESS_TYPE			= $_SESSION['entity']['CAL_UID_TYPE'];
					$this->ADDRESS_TYPE_IS_UA	= $_SESSION['entity']['ADDRESS_TYPE_IS_UA'];
					//$this->COR_COUNTRY			= $_SESSION['entity']['COR_COUNTRY'];
				}
				else
				{
					//----------- Get highest priority ADDRESS
					$qVars = array('entUID'=>$entUID, 'corUID'=>$corUID);
					$t2Key	= 'Q_GetAddressEmail_cor';
					$param_ary = array($entUID, $corUID);
					$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $qVars, $t2Key, get_class($this));
					if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
					$tmpResults1	= $exeResults->results_custom;
					// $exeResults->setMoreCustom('COR_MODIFY_DATE', 'date_1'); // set dates to YYYY-mm-dd hh:mm:ss (military time)
					if ($tmpResults1['COR_UID'][0])
					{
						$this->Address_Uid			= $tmpResults1['COR_UID'][0];
						$this->ADDRESS_PRIORITY		= $tmpResults1['COR_EFFECTIVE_RANK'][0];
						$this->COR_PRIMARY_STREET	= $tmpResults1['COR_PRIMARY_STREET'][0];
						$this->COR_SUITE_APARTMENT = $tmpResults1['COR_SUITE_APARTMENT'][0];
						$this->COR_CITY				= $tmpResults1['COR_CITY'][0];
						$this->STL_UID_STATE			= $tmpResults1['STL_UID_STATE'][0];
						$this->COR_POSTAL_CODE		= $tmpResults1['COR_POSTAL_CODE'][0];
						$this->ADDRESS_TYPE			= $tmpResults1['CAL_UID_TYPE'][0];
						$this->ADDRESS_TYPE_IS_UA	= ($this->ADDRESS_TYPE==7016 || $this->ADDRESS_TYPE==7018) ? 1 : 0; // from CAL_UID_TYPE
						//$this->COR_COUNTRY			= $tmpResults1['COR_COUNTRY'][0];
					}
				}
			}

			if ($coe_UID)
			{
				//----------------------------------- Get highest priority EMAIL address ---------------------------
				if (@$_SESSION['entity']['EMAIL_ADDRESS'] && @$_SESSION['entity']['Email_Uid'])
				{
					if ($GLOBALS['jody']) @$_SESSION['email_sess_ct']++;
					$this->Email_Uid		= $_SESSION['entity']['Email_Uid'];
					$this->EMAIL_PRIORITY	= $_SESSION['entity']['EMAIL_PRIORITY'];
					$this->EMAIL_ADDRESS	= $_SESSION['entity']['EMAIL_ADDRESS'];
				}
				else
				{
					$qVars = array('entUID'=>$entUID, 'coeUID'=>$coe_UID);
					$t2Key	= 'Q_GetAddressEmail_coe';
					$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $qVars, $t2Key, get_class($this));
					if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
					$tmpResults1	= $exeResults->results_custom;
					if ($tmpResults1['COE_UID'][0])
					{
						// 'COE_UID', 'COE_EFFECTIVE_RANK', 'COE_EMAIL_ADDRESS'
						$this->Email_Uid				= $tmpResults1['COE_UID'][0];
						$this->EMAIL_PRIORITY		= $tmpResults1['COE_EFFECTIVE_RANK'][0];
						if ($this->is_email($tmpResults1['COE_EMAIL_ADDRESS'][0]))
							$this->EMAIL_ADDRESS			= $tmpResults1['COE_EMAIL_ADDRESS'][0];
					}
				}
			}
		}

		if (!$entUID) {
			// No parameter passed!
			$fileErrStr = __FILE__.':'.__LINE__ . "\nNO PARAMETERS PASSED\n------------\n" . $this->__toString() . "\n";
			logError ('flexRelated.txt', $fileErrStr);
		}
	}
}



class GetEntity extends Flex_Entities
{
	/**********************************************
	 * Get customer (entity) in these ways:
		1. EDS: emplid AND netid AND dbkey - person logs in through webauth/EDS and his uaid (DBKEY), emplid AND netid are used to find his T2 acct (ENTITY_VIEW).
			* If $update_T2 is true: Use Uaid, emplid, and netid.
			* First search on all three and if no results: if two of these are correct, then can update the third:
				-- Search on two: (Uaid, emplid), (emplid,netid), (Uaid,netid):
					If two found then update third ONLY if third is empty, except if the third is the netid then netid can be overwritten.
		2. ent_uid only - no ldap or eds involved, just query t2, must NOT pass parameters UA_DBKEY, EMPLID or NETID
		3. OLD LDAP: emplid AND netid.
			* If $T2_netid_update true then: If T2 emplid matches ldap (emplidmatch) and T2 netid is EMPTY, then T2 netid will be set to the LDAP netid.

	 * $_SESSION['entity'][x] is set to $this->entity array via setEntSession().
	 * Vars names with 'pp_' means old PowerPark vars.
	***********************************************/

	/**** --------------------- Input Parameters  Parent Class vars: ------------------
	Address_Uid			// Not Required if inserting, Yes if updating. (COR_ADDRESS.COR_UID)
	START_DATE			// Required if inserting. Date address became/becomes active. Format: MM/DD/YY
	ADDRESS_PRIORITY	// Integer, with 1 as highest priority relative to other addresses for this entity.
	ADDRESS_TYPE		// Default: 2008 is "Address from the web". Required if inserting (COR_ADD_TYPE_LKP.CAL_UID).
	STL_UID_STATE		// (STATE_MLKP.STL_UID) -- For <select>, use writeStates() function in customer_functions.php (and see citation_functions.php)
	COR_COUNTRY			// TEXT 128 Chars max. (NOT USING COUNTRY_LKP).
	*/

	// entity array will contain input and output
	public $entity	= array();

	//----------- Output --------------
	public $entitySession		= false;
	//xxx public $pp_cuid		= ''; // Old PowerPark cuid (WAS thrown in T2 ENT_TERTIARY_ID)


	// Innocent until proven guilty! See if ldap crap matches T2, if so, set to true (or ent_uid)
	public $dbkeymatch			= false; // or set to ent_uid
	public $emplidmatch			= false; // or set to ent_uid
	public $netidmatch			= false; // or set to ent_uid
	public $subclassmatch		= false;	// or set to ent_uid

	// All active customer data:
	public $getAddrEmail			= true;	// set to false to ONLY get basic Entity info.
	public $allInfoAddresses	= array(); // all addresses
	public $allInfoEmails		= array(); // all emails

	public $allInfoPhones		= array(); // all phones -

	public $flex_function = 'GetEntity';

	public function __construct($ent_uid='', $dbkey='', $emplid='', $netid='', $uid_class_string='', $catcardid='', $update_T2=true, $T2_netid_update=true, $getAddrEmail=true)
	{
		Debug_Trace::traceFunc();

		$this->esl_def = $this->setEslDefs();
		// debug may change below
		$this->entity['esl_subclass']	= $this->getEslDefUid($uid_class_string, $this->esl_def);
		$this->entity['classificationStr'] = $uid_class_string;

		// If $ent_uid is set, then $emplid and $netid should be empty.
		// (NOTE: T2 uses upper-case, not webauth)
		$this->entity['UA_DBKEY']	= $this->UA_DBKEY	= strtoupper(trim($dbkey)); // same as uaid
		$this->entity['ENT_UID']	= $this->ENT_UID	= strtoupper(trim($ent_uid));
		$this->entity['EMPLID']		= $this->EMPLID	= strtoupper(trim($emplid));	// PRIMARY_UID (ENTITY_VIEW.ENT_PRIMARY_ID)
		$this->entity['NETID']		= $this->NETID		= strtoupper(trim($netid));	// SECONDARY_UID (ENTITY_VIEW.ENT_SECONDARY_ID)
		$this->entity['CATCARD_ID']= $this->CATCARD_ID	= trim($catcardid); // isonumber - used for garage-permits only.
		$this->getAddrEmail	= $getAddrEmail;

		$sqlAppendOr	= '';
		$sqlAppendAnd	= '';
		// TODO: use $log_msg_err instead of $errZ1.
		$errZ1 = $errCode = $log_msg = $log_msg_err = '';

		$update_dbkey = $update_emplid = $update_netid = '';

		if ($this->ENT_UID)
		{
			//---------- Get customer via ENT_UID (primary key of ENTITY_VIEW), so assume everything else matches. -----------------

			$qVars = array('ent_uid'=>$this->ENT_UID);

			$t2Key	= 'Q_GetEntity_ent'; // TODO: Use Q_GetEntity_all.
			$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $qVars, $t2Key, get_class($this));
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			$tmpResults1	= $exeResults->results_custom;

			if (sizeof($tmpResults1['ENT_UID']) == 1)
			{
				if ($this->UA_DBKEY || $this->EMPLID || $this->NETID)
					exitWithBottom('ERROR: If searching on Entity UID, do not include UA_DBKEY (UAID) or EMPLID or NETID');
				else
					$this->dbkeymatch = $this->emplidmatch = $this->netidmatch = $tmpResults1['ENT_UID'][0];
			}

		}
		else if ($this->UA_DBKEY) // uaid
		{
			//---------------------------- NEW EDS / Webauth -------------------------
			// Find out what does or does not match (EDS vs T2).

			$qVars = array('UA_DBKEY'=>$this->UA_DBKEY);

			$t2Key	= 'Q_GetEntity_uaid';
			$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $qVars, $t2Key, get_class($this));
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			$tmpResults1	= $exeResults->results_custom;

			if (sizeof($tmpResults1['ENT_UID']) == 1)
			{
				$this->ENT_UID		= $tmpResults1['ENT_UID'][0];
				$this->dbkeymatch = $tmpResults1['ENT_UID'][0];

				if ($this->EMPLID && trim($tmpResults1['ENT_PRIMARY_ID'][0]))
				{
					if ($this->EMPLID == trim($tmpResults1['ENT_PRIMARY_ID'][0]))
					{
						$this->emplidmatch = $tmpResults1['ENT_UID'][0];
					}
					else
					{
						$errCode = 2200;
						$errZ1 = 'EMPLID does not match ('.$this->EMPLID.' does not match t2 '.trim($tmpResults1['ENT_PRIMARY_ID'][0]).'). ' . "<br>\n";
					}
				}

				if ($this->NETID && trim($tmpResults1['ENT_SECONDARY_ID'][0]))
				{
					if ($this->NETID == trim($tmpResults1['ENT_SECONDARY_ID'][0]))
					{
						$this->netidmatch = $tmpResults1['ENT_UID'][0];
					}
					else
					{
						// net id can [possibly] be overwritten so no error here
					}
				}

				// First see if current T2 account subclass ESL_UID_SUBCLASS matches 2016 which is 'No Classification'.
				// Then, if webauth (classificationStr) is 'Employee', 'Student', or 'GradAsst', then
				//		update T2's subclass to the numeric value of string $this->entity['classificationStr'] - see $update_subclass.
				// Fun note: If coming from login_webauth .php, $this->entity['classificationStr'] = $_SESSION['eds_data']['class'].
				//if ($GLOBALS['DEBUG_DEBUG'])	{
				//				$this->subclassmatch = $tmpResults1['ENT_UID'][0];
				//				if ($tmpResults1['ESL_UID_SUBCLASS'][0] == $this->esl_def['esl_NoClass']) // is 2016?
				//				{
				//					if ($this->entity['classificationStr'] == 'Employee' || $this->entity['classificationStr'] == 'Student' || $this->entity['classificationStr'] == 'GradAsst')
				//						$this->subclassmatch = false;
				//				}
				//} //////////////////////////

			}
			else if (sizeof($tmpResults1['ENT_UID']) > 1)
			{
				$errCode = 2200;
				$errZ1 = 'More than one ENT_TERTIARY_ID found ('.$this->NETID.'). ' . "<br>\n";
			}
		}


		if (!$this->dbkeymatch && $this->NETID && $this->EMPLID)
		{
			//---------------------- Look for netid and emplid in t2 ----------------------------

			// dbkey is empty, so up date it if netid and emplid exisist in t2.
			$qVars = array('netid'=>$this->NETID, 'emplid'=>$this->EMPLID);


			$t2Key	= 'Q_GetEntity_net_or_emp';
			$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $qVars, $t2Key, get_class($this));
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			$tmpResults1	= $exeResults->results_custom;

			if (sizeof($tmpResults1['ENT_UID']))
			{
				if ($tmpResults1['ENT_PRIMARY_ID'][0] && !$tmpResults1['ENT_SECONDARY_ID'][0])
				{
					// only update netid and/or dbkey
					$this->entity['esl_subclass'] = $this->getEslDefUid($_SESSION['eds_data']['class'], $this->esl_def);
					// $this->insertSubclass = $this->esl_subclass;
					//	 ($entUID, $uid_class_string, $fName, $lName, $emplid, $netid, $phone1, $phone2, $phone3, $dbkey)
					// could also use $this->NETID and $this->UA_DBKEY here. 
					$updateEntity = new InsertUpdateEntity($tmpResults1['ENT_UID'][0], $this->entity['classificationStr'], '', '', '', $this->entity['NETID'], '', '', '', $this->entity['UA_DBKEY']);
					if ($GLOBALS['DEBUG_DEBUG']) echo $updateEntity;
					//	if ($GLOBALS['jody']) {
					//		echo '<pre>';
					//		var_dump($this->entity['esl_subclass']);
					//		echo '<hr>';
					//		var_dump($_SESSION['eds_data']['class']);
					//		echo '<hr>';
					//		var_dump($_SESSION['eds_data']);
					//		echo '<hr><hr>';
					//		var_dump($this);
					//		exitWithBottom('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
					//	}
				}
				else if ($tmpResults1['ENT_PRIMARY_ID'][0] && $tmpResults1['ENT_SECONDARY_ID'][0])
				{
					if (!$tmpResults1['ESL_UID_SUBCLASS'][0] && $this->entity['classificationStr'] && $this->getEslString($this->entity['classificationStr'])) 
					{
						$updateEntity = new InsertUpdateEntity($tmpResults1['ENT_UID'][0], $this->entity['classificationStr'], '', '', '', '', '', '', '', '');
					}

				}
			}

/***
if ($GLOBALS['jody']) {
echo 'aaaa '.$tmpResults1['ESL_UID_SUBCLASS'][0].'---'.$this->entity['classificationStr'].'---';  
echo $this->getEslString($this->entity['classificationStr']).'<br>';      
	echo '<pre>';
var_dump($this->entity);
echo '<hr>';
var_dump($tmpResults1);
} ***/

			$t2Key	= 'Q_GetEntity_net_and_emp';
			$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $qVars, $t2Key, get_class($this));
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			$tmpResults1	= $exeResults->results_custom;

			if (sizeof($tmpResults1['ENT_UID']) == 1)
			{
				$this->ENT_UID		= $tmpResults1['ENT_UID'][0];
				$this->netidmatch = $this->emplidmatch = $tmpResults1['ENT_UID'][0];
				if ($this->UA_DBKEY && trim($tmpResults1['ENT_TERTIARY_ID'][0]))
				{
					if ($this->UA_DBKEY == trim($tmpResults1['ENT_TERTIARY_ID'][0]))
					{
						$this->dbkeymatch = $tmpResults1['ENT_UID'][0];
					}
					else
					{
						$errCode = 2200;
						$errZ1 = 'UAID does not match ('.$this->UA_DBKEY.' does not match t2 '.trim($tmpResults1['ENT_TERTIARY_ID'][0]).'). ' . "<br>\n";
					}
				}
			}
			else if (sizeof($tmpResults1['ENT_UID']) > 1)
			{
				$errCode = 2200;
				$errZ1 = 'More than one ENTITY found ('.$this->NETID.'). ' . "<br>\n";
			}
			else
			{
				$errCode = 2200;
				$errZ1 = 'T2 Account not found. Searched on:  UAID: '.$this->UA_DBKEY.', NETID: '.$this->NETID.', EMPLID: '.$this->EMPLID.'. ' . "<br>\n";
			}
		}


		if ($update_T2)
		{
			//--------------------- If stuff does not match, try to update it (mark it for update) ----------------------
			if ($this->dbkeymatch)
			{
				if (!$this->netidmatch && $this->NETID && $this->emplidmatch)
				{
					$update_netid = $this->NETID;
				}
				else if (!$this->emplidmatch && $this->EMPLID && $this->netidmatch)
				{
					$update_emplid = $this->EMPLID;
				}
			}
			else if ($this->netidmatch && $this->emplidmatch && $this->UA_DBKEY)
			{
				$update_dbkey = $this->UA_DBKEY;
			}
			else if (!$this->netidmatch && !$this->emplidmatch && !$this->dbkeymatch)
			{
				// Added this for new customers to create account in T2!!!
				// First make sure no netid match OR emplid match - if nothing found,
				//		then erase any $errCode, so as to allow new acct creation.
				// Above we searched for netid AND emplid.

				$qVars = array('netid'=>$this->NETID, 'emplid'=>$this->EMPLID);

				$t2Key	= 'Q_GetEntity_net_or_emp';
				$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $qVars, $t2Key, get_class($this));
				if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
				$tmpResults1	= $exeResults->results_custom;

				if (sizeof($tmpResults1['ENT_UID']))
				{
					// Way above we searched for netid AND emplid.  But, just above we searched for netid OR emplid.
					if ($errCode && $errZ1)
						$errZ1 .= "<b>*** CHECK T2 TO MAKE SURE THERE ARE NO SPACES AFTER NETID OR EMPLID ***<br>REFINED: netid OR emplid mismatch.</b><br>\n";
				}
				else
				{
					if ($errCode)
					{
						if ($GLOBALS['DEBUG_DEBUG']) {
							echo "<div style='padding:1px; border:1px solid blue;'>
									Debug Data Note: Attempt to create New Account for customer, so ignore \$errCode: $errCode<br>Ignore \$errZ1: $errZ1</div>";
						}
						$errCode = '';
						$errZ1 = '';
					}
				}
			}
		}


		if ($errZ1)
		{
			// ($event_type_text, $event_info_text, $ENT_UID, $PAYMENT, $TRANSACTION_ID, $INFO1, $INFO2=null)
			new event_log('login_transaction', 'error', @$_SESSION['entity']['ENT_UID'], '0', '', makeErrorCode($errCode,$errZ1));
			exitWithBottom('<b>CODE ' . getErrorCode($errCode) . '</b>, ' . CONTACT_CR);
		}

		else if ($this->ENT_UID)
		{
			//------------------------- Update anything which has been marked for update above -------------------------
			if ($update_dbkey)
			{
				//-------------- Update T2 dbkey with LDAP's dbkey. If success then log and contine, else error.
				if ($GLOBALS['DEBUG_DEBUG']) echo "<div style='padding:1px; border:1px solid blue;'>Updateing uaid (dbkey)</div>";
				$tmp_msg = 'Update t2 uaid (dbkey) with the LDAP uaid (' . $update_dbkey . ').' . "\n";
				//	 ($entUID, $uid_class_string, $fName, $lName, $emplid, $netid, $phone1, $phone2, $phone3, $dbkey)
				$updateEntity = new InsertUpdateEntity($this->ENT_UID, '', '', '', '', '', '', '', '', $update_dbkey);
				$this->dbkeymatch = $this->ENT_UID;
			}
			else if ($update_netid)
			{
				//-------------- Update T2 netid with LDAP's netid. If success then log and contine, else error.
				if ($GLOBALS['DEBUG_DEBUG']) echo "<div style='padding:1px; border:1px solid blue;'>Updateing netid</div>";
				$tmp_msg = 'Update t2 netid with the LDAP netid (' . $update_netid . ').' . "\n";
				//	 ($entUID, $uid_class_string, $fName, $lName, $emplid, $netid, $phone1, $phone2, $phone3, $dbkey)
				$updateEntity = new InsertUpdateEntity($this->ENT_UID, '', '', '', '', $update_netid);
				$this->netidmatch = $this->ENT_UID;
			}
			else if ($update_emplid)
			{
				if ($GLOBALS['DEBUG_DEBUG']) echo "<div style='padding:1px; border:1px solid blue;'>Updateing emplid</div>";
				$tmp_msg = 'Update ldap emplid ('.$update_emplid.') using dbkey and netid (' . $this->UA_DBKEY . ', ' . $this->NETID . ")\n";
				//	 ($entUID, $uid_class_string, $fName, $lName, $emplid, $netid, $phone1, $phone2, $phone3, $dbkey)
				$updateEntity = new InsertUpdateEntity($this->ENT_UID, '', '', '', $update_emplid, '', '', '', '', '');
				$this->emplidmatch = $this->ENT_UID;
			}

			//	if ($update_subclass) {
			//		if ($GLOBALS['DEBUG_DEBUG']) echo "<div style='padding:1px; border:1px solid blue;'>Updateing subclass</div>";
			//		$tmp_msg = 'Update ldap subclass ('.$update_subclass.') for netid ' . $this->NETID . ")\n";
			//		//	 ($entUID, $uid_class_string, $fName, $lName, $emplid, $netid, $phone1, $phone2, $phone3, $dbkey)
			//		$updateEntity = new InsertUpdateEntity($this->ENT_UID, $update_subclass, '', '', '', '', '', '', '', '');
			//		$this->subclassmatch = $this->ENT_UID;
			//	}

			if (@$tmp_msg)
			{
				if ($GLOBALS['DEBUG_DEBUG']) echo $updateEntity;
				$qVars = array('ent_uid'=>$this->ENT_UID);

				if ($updateEntity->ENT_UID)
				{
					$log_msg .= $tmp_msg;
					// something was updated, so get the stuff man!
					$t2Key	= 'Q_GetEntity_ent'; // TODO: Use Q_GetEntity_all.
					$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $qVars, $t2Key, get_class($this));
					if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
					$tmpResults1	= $exeResults->results_custom;
				}
				else
				{
					$log_msg_err .= 'ERROR WHILE TRYING: ' . $tmp_msg . "\n";
					$errCode = 2278;
				}
			}
		}


		if (sizeof($tmpResults1['ENT_UID'])==1 && $this->emplidmatch && $this->netidmatch)
		{
			//include when EDS live: $this->dbkeymatch

			if ($log_msg)
			{
				new ws_log(30, $tmpResults1['ENT_UID'][0], 0, 0, '', $log_msg);
				//debugEchos(__FILE__.':'.__LINE__, $log_msg, $tmpResults1, $queryEnt, $qVars);
			}
			if ($log_msg_err)
			{
				// (30, $evntInfo, $userid, $amount, $receipt_id, $info, $info2)
				new event_log('ws_log', 'error', @$_SESSION['entity']['ENT_UID'], '0', '', makeErrorCode($errCode,$log_msg_err));
				debugEchos(__FILE__.':'.__LINE__, makeErrorCode($errCode,$log_msg_err), $tmpResults1, $queryEnt, $qVars);
			}

			if ($log_msg || $log_msg_err)
			{
				unset ($log_msg);
				unset ($log_msg_err);
			}
			else
			{
				new ws_log(30, $tmpResults1['ENT_UID'][0], 0, 0, '', 'Logging in');
			}

			$this->entity['ESL_UID_SUBCLASS']		= $tmpResults1['ESL_UID_SUBCLASS'][0];
			$this->entity['classificationStr_t2']	= $this->getEslString($this->entity['ESL_UID_SUBCLASS']);


			$this->entity['ENT_UID']				= $tmpResults1['ENT_UID'][0];
			$this->entity['EMPLID']					= $tmpResults1['ENT_PRIMARY_ID'][0];
			$this->entity['NETID']					= $tmpResults1['ENT_SECONDARY_ID'][0];
			$this->entity['UA_DBKEY']				= $tmpResults1['ENT_TERTIARY_ID'][0];
			//xxx $this->entity['pp_cuid']		= $tmpResults1['ENT_TERTIARY_ID'][0];
			$this->entity['PHONE_ONE']				= $tmpResults1['ENT_PHONE_1'][0];
			$this->entity['PHONE_TWO']				= $tmpResults1['ENT_PHONE_2'][0];
			$this->entity['PHONE_THREE']			= $tmpResults1['ENT_PHONE_3'][0];
			$this->entity['LAST_NAME']				= $tmpResults1['ENT_LAST_NAME'][0];
			$this->entity['FIRST_NAME']			= $tmpResults1['ENT_FIRST_NAME'][0];
			$this->entity['MIDDLE_NAME']			= $tmpResults1['ENT_MIDDLE_NAME'][0];
			if (!@$this->entity['LAST_NAME'] && !@$this->entity['FIRST_NAME'])
				$this->entity['IMPORTED_NAME']	= $tmpResults1['ENT_IMPORTED_NAME'][0]; // From PP classic.
			$this->entity['Address_Uid']			= $tmpResults1['COR_UID_HIGHEST_RANKED_ADDRESS'][0];
			$this->entity['Email_Uid']				= $tmpResults1['COE_UID_HIGHEST_RANKED_EMAIL'][0];

			if ($this->getAddrEmail)
			{
				$addrInfo = new GetAddressEmail($this->entity['ENT_UID'], $this->entity['Address_Uid'], $this->entity['Email_Uid']);
				if ($GLOBALS['DEBUG_DEBUG']) echo $addrInfo;

				$this->entity['COR_PRIMARY_STREET']	= $addrInfo->COR_PRIMARY_STREET;
				$this->entity['COR_SUITE_APARTMENT']= $addrInfo->COR_SUITE_APARTMENT;
				$this->entity['COR_CITY']				= $addrInfo->COR_CITY;
				$this->entity['STL_UID_STATE']		= $addrInfo->STL_UID_STATE;
				$this->entity['COR_POSTAL_CODE']		= $addrInfo->COR_POSTAL_CODE;
				$this->entity['ADDRESS_TYPE_IS_UA']	= $addrInfo->ADDRESS_TYPE_IS_UA;
				$this->entity['ADDRESS_PRIORITY']	= $addrInfo->ADDRESS_PRIORITY;
				// In test db, all NON-EMPTY email_address are changed to PTS-IT-Emails@email.arizona.edu
				$this->entity['EMAIL_ADDRESS']		= $addrInfo->EMAIL_ADDRESS;
				$this->entity['EMAIL_PRIORITY']		= $addrInfo->EMAIL_PRIORITY;

				// Default netid email.
				if (!$this->entity['EMAIL_ADDRESS']) {
					//jody aug 17 2015 - some new customers were beign assigned pts email: $this->entity['EMAIL_ADDRESS'] = $this->EMAIL_DEFAULT, so added lots more back-falls.
					if ($_SESSION['entity']['EMAIL_ADDRESS'])
					{
						$this->entity['EMAIL_ADDRESS'] = $_SESSION['entity']['EMAIL_ADDRESS'];
					}
					else if ($this->entity['NETID'])
					{
						$this->entity['EMAIL_ADDRESS'] = $this->entity['NETID'] . '@EMAIL.ARIZONA.EDU';
					}
					else
					{
						$errCode = 2279;
						$errZ2 = 'No customer email created!!';
						// ($event_type_text, $event_info_text, $ENT_UID, $PAYMENT, $TRANSACTION_ID, $INFO1, $INFO2=null)
						new event_log('login_transaction', 'error', @$_SESSION['entity']['ENT_UID'], '0', '', makeErrorCode($errCode,$errZ2));
						if ($GLOBALS['DEBUG_DEBUG'])
							$this->entity['EMAIL_ADDRESS'] = $this->EMAIL_DEFAULT; // PTS-PermitServices@email.arizona.edu
					}
				}


				//--------------- Get all addresses, and emails, and also the three entity phone numbers. --------------

				// sets allInfoAddresses and allInfoEmails arrays.
				$addrInfoAll = new GetAddressEmail($this->entity['ENT_UID']);
				if ($GLOBALS['DEBUG_DEBUG']) echo $addrInfoAll;
				$this->allInfoAddresses	= $addrInfoAll->allInfoAddresses;
				$this->allInfoEmails		= $addrInfoAll->allInfoEmails;

				//-------- all phones
				$this->allInfoPhones['Cell']		= $this->entity['PHONE_ONE']; // Cell Phone
				$this->allInfoPhones['Home']		= $this->entity['PHONE_TWO'];
				$this->allInfoPhones['Business']	= $this->entity['PHONE_THREE'];

				//--------------------------------------------------------------
			}
		}
		else if (sizeof($tmpResults1['ENT_UID']) > 1)
		{
			$errCode = 2277;
			$errZ2 = 'More than one Customer found!!';
			// ($event_type_text, $event_info_text, $ENT_UID, $PAYMENT, $TRANSACTION_ID, $INFO1, $INFO2=null)
			new event_log('login_transaction', 'error', @$_SESSION['entity']['ENT_UID'], '0', '', makeErrorCode($errCode,$errZ2));
			exitWithBottom('<b>CODE ' . getErrorCode($errCode) . '</b>, ' . CONTACT_CR);
		}
	}


	public function setEntSession()
	{
		Debug_Trace::traceFunc();
		/****************************************
		 * Puts the login data from this class (this->entity) into _SESSION['entity'][] vars.
		 *	Returns $this->entitySession as true if existing T2 account, otherwise false.
		*****************************************/

		if ($this->entitySession)
			unset($_SESSION['entity']);

		if ($this->entity['ENT_UID'])
			$this->entitySession =	true;
		else
			$this->entitySession =	false;

		/***
		 * This is the big array transfer.
		 */
		$_SESSION['entity'] = $this->entity;
		// make sure some are lower-case - T2 deals in upper-case.
		$_SESSION['entity']['NETID'] = @strtolower($_SESSION['entity']['NETID']);

		$_SESSION['flexid_netid'] = $_SESSION['entity']['ENT_UID'];
		if (@$_SESSION['entity']['NETID'])
			$_SESSION['flexid_netid'] .= '/' . $_SESSION['entity']['NETID'];

		return $this->entitySession;
	}
}



class OptInTextMessage extends Flex_Entities
{
	public $opt_in_tm		= 0; // 0 or 1 - does customer want to get text messages?
	public $cell_phone	= ''; // (same as allInfoPhones['Cell']) 109106543384
	public $testStr		= '';

	public function __construct($entUID, $opt_in_tm='', $cell_phone='')
	{
		Debug_Trace::traceFunc();
		parent::__construct();
		if (!$entUID)
		{
			if ($GLOBALS['DEBUG_DEBUG']) echo '<div style="border:1px solid red; padding 2px; font-weight:bold;">ERROR: No Account found<div>';
			return '';
		}

		//	if ($GLOBALS['database_test_db']) {
		//		$entUID = '164416';
		//		$this->testStr .= '<div style="border:1px solid red; padding:1px; margin:0; font-weight:bold;">Testing db optIn for '
		//		. '<a target="_blank" href="https://arizona.t2flex.com/POWERPARK/entity/view.aspx?id=164416">Jody live account</a></div>';
		//		$GLOBALS['database_test_db'] = false;
		//	}

		$this->ENT_UID			= $entUID;
		$this->opt_in_tm		= $opt_in_tm;
		$this->cell_phone		= $cell_phone;

		if ($this->opt_in_tm=='' || $this->cell_phone=='')
			$this->getOptInData();
		$this->cell_phone = $this->phoneFriendly($this->cell_phone);
	}

	private function getOptInData()
	{
		/***
		 * Set opt_in_tm -- 1 or 0
		 * Set cell_phone
		 */
		$param_ary	= array('Entity UID' => $this->ENT_UID);
		$t2Key		= 'Q_getOptInData';
		$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
		$this->opt_in_tm	= @$exeResults->results_custom['ENT_OPTIN'][0];
		$this->cell_phone = @$exeResults->results_custom['ENT_PHONE_1'][0];
	}

	public function setOptInText($opt_in_tm, $cell_phone = '')
	{
		/***
		 * $opt_in_tm is 0 or 1
		 * If $cell_phone set, replace Customer (entity) PHONE_ONE with that new cell number.
		 *		(same as this->allInfoPhones['Cell'])
		 */
		//if ($GLOBALS['DEBUG_DEBUG']) echo $this->opt_in_tm . ' , ' .$_POST['cell_phone'];
		$this->opt_in_tm	= $opt_in_tm ? 1 : 0;
		$this->cell_phone	= $this->phoneDigitsOnly($cell_phone);
		$custKey = 'OPTIN';
		//													($web_service, $cust_field_name, $uid_value, $cust_field_value)
		$CF = new InsertUpdateCustomFlds(self::$cust_fld_svc[$custKey], $custKey, $this->ENT_UID, $this->opt_in_tm);
		// if ($GLOBALS['DEBUG_DEBUG']) echo $CF;
		if ($CF->ErrorDescription && $GLOBALS['DEBUG_DEBUG'])
			echo '<div style="border:1px solid red; padding 2px; font-weight:bold;">ERROR: '.$CF->ErrorDescription.'<div>';
		$uuu = new InsertUpdateEntity($this->ENT_UID, '', '', '', '', '', $this->cell_phone);
		if ($GLOBALS['DEBUG_DEBUG']) echo $uuu;
		$this->cell_phone	= $this->phoneFriendly($this->cell_phone);
		//if ($GLOBALS['jody']) {
		//	$this->testStr .= '<div style="border:1px solid red; padding:1px; margin:0;">Back to Test db mode</div>';
		//	$GLOBALS['database_test_db'] = true;
		//}
	}

	public function phoneFriendly($phone)
	{
		$phone = $this->phoneDigitsOnly($phone);
		$phone = preg_replace('/^(\d{3})(\d{3})(\d{4})$/si', '($1)$2-$3', $phone);
		return $phone;
	}

	public function phoneDigitsOnly($phone)
	{
		/***
		 * Srip off non-digits and return number
		 * If only 7-digit number, prepend "520"
		 */
		$phone = preg_replace('/[^\d]/si', '', $phone);
		$phone = preg_replace('/^(\d{7})$/si', '520$1', $phone);
		$phone =	$phone ? $phone : '000';
		$phone =	($phone=='520') ? '000' : $phone;
		return $phone;
	}
}


class InsertUpdateEntity extends Flex_Entities
{
	/**********************************
	This method is used to insert a new customer record or update an existing customer record.
	 If a customer UID is not passed in then the method will attempt to insert a new customer using the information passed into the method.
	 If a customer UID is passed in then method will attempt to update an existing customer using the information passed into the method.
	 It is possible, although not advisable, to insert a customer having no identifying information, as the various name and UID fields are not required for an insert.
	 A Related Group may be removed on an edit by supplying a space (― ―) as the value of the parameter.
	***********************************/

	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';
	public $return_xml		= ''; // massaged return_page


	//--------------------- Input XML Parameters ----------------------
	// ENT_UID			- parent			- [Input AND Output var]  Required ONLY if updating.  (ENTITY.ENT_UID).
	// EMPLID			- parent			-  Not Required. REAL parameter is PRIMARY_UID. (ENTITY_VIEW.ENT_PRIMARY_ID)
	// NETID				- parent			- REAL parameter is SECONDARY_UID. (ENTITY_VIEW.ENT_SECONDARY_ID)
	// esl_subclass	- parent			- SUB_CLASSIFICATION. Required if inserting.  [Default esl_NoClass].  ENT_SUBCLASSIFICATION_MLKP.ESL_UID or ENTITY.ESL_UID_SUBCLASS).
	protected $insertSubclass			= ''; // Will be set to esl_subclass if inserting new customer.
	protected $ENTITY_TYPE				= 1;  // Required if inserting. [Default: 1 is Individual].  (ENT_TYPE_LKP.ETL_UID)
	protected $ENT_TERTIARY_ID			= ''; //
	protected $FIRST_NAME				= ''; //
	protected $MIDDLE_NAME				= ''; //
	protected $LAST_NAME					= ''; //
	protected $NAME_PREFIX_UID			= ''; // Prefix for the entity‘s name (ENT_NAME_PREFIX_LKP.ENP_UID)
	protected $NAME_SUFFIX_UID			= ''; // Suffix for the entity‘s name (ENT_NAME_SUFFIX_LKP.ENS_UID)
	protected $PHONE_ONE					= ''; // Cell Phone
	protected $PHONE_TWO					= ''; // Home Phone
	protected $PHONE_THREE				= ''; // Business Phone
	protected $ENT_UID_RELATED_GROUP	= ''; // The UID corresponding to the customer to set as the Allotment Group
	// protected $GROUP_NAME			= ''; // Not Required, unless entity_type is Group

	protected $A_CUST_FIELD_NAME		= ''; // Custom Field Name (Ex: Cust_1)

	//-------------------- Natural XML Output
	// ENT_UID - ENT_UID of customer that was successfully inserted or updated. (ENTITY.ENT_UID).


	public $flex_function = 'InsertUpdateEntity'; //($entUID, $uid_class_string, $fName, $lName, $emplid, $netid, $phone1, $phone2, $phone3, $dbkey)

	public function __construct($entUID='', $uid_class_string='', $fName='', $lName='', $emplid='', $netid='', $phone1='', $phone2='', $phone3='', $dbkey='')
	{
		Debug_Trace::traceFunc();
		parent::__construct();

		$this->esl_def = $this->setEslDefs();
		$this->esl_subclass = $this->getEslDefUid($uid_class_string, $this->esl_def);

		// 20140703: added $uid_class_string
		//There are only two instances of where this class is called where $uid_class_string is set.
		if (!$entUID || $uid_class_string) // Inserting new customer, so insert subclass.
			$this->insertSubclass = $this->esl_subclass;

		$this->ENT_UID	= strtoupper(trim($entUID)); // If entUID has a value, then updating.

		$this->UA_DBKEY		= $dbkey;
		$this->NETID			= strtoupper($netid); // SECONDARY_UID (ENTITY_VIEW.ENT_SECONDARY_ID)
		$this->EMPLID			= $emplid; // (ENTITY_VIEW.ENT_PRIMARY_ID)

		$this->FIRST_NAME		= $fName;
		$this->LAST_NAME		= $lName;
		$this->PHONE_ONE		= $this->limitString($phone1, 20); // cell phone
		$this->PHONE_TWO		= $this->limitString($phone2, 20);
		$this->PHONE_THREE	= $this->limitString($phone3, 20);
		$this->send_xml();
	}

	protected function limitString($string, $maxSize)
	{
		// Phone numbers can only be 20 chars long in t2, so try and trim some crap off.
		// Probably don't need this cuz I can just limit the form field.
		if (strlen($string) > $maxSize)
			$string = preg_replace('/[^\w\d ]/si', '', $string);
		if (strlen($string) > $maxSize)
			$string = preg_replace('/[^\d ]/si', '', $string);
		if (strlen($string) > $maxSize)
			$string = preg_replace('/[^\d]/si', '', $string);
		if (strlen($string) > $maxSize)
			$string = preg_replace('/[^\d]/si', '', $string);
		if (strlen($string) > $maxSize)
			$string = truncate_text($string, $maxSize);
		return $string;
	}

	protected function get_xml()
	{
		Debug_Trace::traceFunc();
		/****  This function returns xml data.  ***/
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('ENTITY_UID',				$this->ENT_UID);
		$this->xml_data .= make_param('ENTITY_TYPE',				$this->ENTITY_TYPE);
		$this->xml_data .= make_param('PRIMARY_UID',				$this->EMPLID);
		$this->xml_data .= make_param('SECONDARY_UID',			$this->NETID);
		$this->xml_data .= make_param('SUB_CLASSIFICATION',	$this->insertSubclass);
		$this->xml_data .= make_param('FIRST_NAME',				$this->FIRST_NAME);
		$this->xml_data .= make_param('LAST_NAME',				$this->LAST_NAME);
		$this->xml_data .= make_param('PHONE_ONE',				$this->PHONE_ONE);
		$this->xml_data .= make_param('PHONE_TWO',				$this->PHONE_TWO);
		$this->xml_data .= make_param('PHONE_THREE',				$this->PHONE_THREE);
		if ($this->UA_DBKEY)
			$this->xml_data .= make_param('TERTIARY_UID',			$this->UA_DBKEY);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

	protected function set_callback()
	{
		// Called from config.php - to pricess return data
		// Update if Customer was already logged in before instanciating this class
		if ($_SESSION['entity']['ENT_UID'])
		{
			$_SESSION['entity']['UA_DBKEY'] = $this->UA_DBKEY ? $this->UA_DBKEY : $_SESSION['entity']['UA_DBKEY'];
			$_SESSION['entity']['NETID'] = strtolower($this->NETID) ? $this->NETID : $_SESSION['entity']['NETID'];
			$_SESSION['entity']['EMPLID'] = $this->EMPLID ? $this->EMPLID : $_SESSION['entity']['EMPLID'];
			$_SESSION['entity']['PHONE_ONE'] = $this->PHONE_ONE ? $this->PHONE_ONE : $_SESSION['entity']['PHONE_ONE'];
			$_SESSION['entity']['PHONE_TWO'] = $this->PHONE_TWO ? $this->PHONE_TWO : $_SESSION['entity']['PHONE_TWO'];
			$_SESSION['entity']['PHONE_THREE'] = $this->PHONE_THREE ? $this->PHONE_THREE : $_SESSION['entity']['PHONE_THREE'];
			$_SESSION['entity']['LAST_NAME'] = $this->LAST_NAME ? $this->LAST_NAME : $_SESSION['entity']['LAST_NAME'];
			$_SESSION['entity']['FIRST_NAME'] = $this->FIRST_NAME ? $this->FIRST_NAME : $_SESSION['entity']['FIRST_NAME'];
			$_SESSION['entity']['MIDDLE_NAME'] = $this->MIDDLE_NAME ? $this->MIDDLE_NAME : $_SESSION['entity']['MIDDLE_NAME'];
		}
	}
}



class PayOutAccount extends Flex_Entities
{
	/**********************************
	 * NOT BEING USED RIGHT NOW
	***********************************/

	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';
	public $return_xml		= ''; // massaged return_page


	//--------------------- Input XML Parameters ----------------------
	// ENT_UID								- parent -
	public $ACCOUNT_CREDIT_LIST			= ''; // Required? Yes if Account payment method used (PAYMENT_TYPE)

	public $CASH_DRAWER_UID					= '';
	public $AMOUNT_PAID						= ''; // Required if MCI (Manage Credits Individually (MCI) is set to false)

	// Required Joann: 1 is "No Charge", 14 is "External Payment Plan", 2000 is "Credit Card"
	public $PAYMENT_TYPE						= ''; // Required  (PAYMENT_METHOD_MLKP.PMM_UID) (PAYMENT_TYPE_MLKP.PAY_UID is also "External Payment Plan")
	public $REFERENCE_NOTE					= '';
	public $PAYPLAN_TRANSFER_AGENT_UID	= '';
	public $PAYPLAN_TAX_TYPE				= '';

	//---------- OUTPUT --------------
	public $RECEIPT_UID						= '';


	public $flex_function = 'PayOutAccount';

	public function __construct($entUID='', $amtPaid='', $payTypeUID='')
	{
		Debug_Trace::traceFunc();

		parent::__construct();
		$this->ENT_UID				= $entUID;
		$this->AMOUNT_PAID		= $amtPaid;
		$this->PAYMENT_TYPE		= $payTypeUID ? $payTypeUID : 2000;

		// $this->PAYPLAN_TRANSFER_AGENT_UID	= 2008; // (required because we use "External Payment Plan"???).  Joann put in BPP (TRANSFER_AGENCY_MLKP table)
		// $this->PAYPLAN_TAX_TYPE					= 1;   // (because we use "External Payment Plan" AND required by transfer agent????). 1-No Tax, 2-Pre-Tax, 3-Post-Tax

		$this->send_xml();
	}

	protected function get_xml()
	{
		Debug_Trace::traceFunc();
		/****  This function returns xml data.  ***/
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('ENTITY_UID',					$this->ENT_UID);
		$this->xml_data .= make_param('AMOUNT_PAID',					$this->AMOUNT_PAID);
		$this->xml_data .= make_param('PAYMENT_TYPE',				$this->PAYMENT_TYPE);
//		$this->xml_data .= make_param('PAYPLAN_TRANSFER_AGENT_UID',	$this->PAYPLAN_TRANSFER_AGENT_UID);
//		$this->xml_data .= make_param('PAYPLAN_TAX_TYPE',			$this->PAYPLAN_TAX_TYPE);
		$this->xml_data .= make_param('CASH_DRAWER_UID',			$this->WS_CONN['WS_cashUID']);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

	protected function set_callback()
	{
		// Called from config.php - to pricess return data
	}
}

?>
