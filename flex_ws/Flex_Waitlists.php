<?php

require_once '/var/www2/include/flex_ws/config.php';


/*********
 *
 * TODO: Maybe est again: Foce Expire==1 and Expire Date<=now in WLT_ATTEMPT
 *
**********/

abstract class Flex_Waitlists extends Flex_Funcs
{
	// Documentation at http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf
	// See also top of files:  renewal_functions.php  and  permit_renewal.php

	public $flex_group = 'T2_Flex_Waitlists';

	//==== Custom control group dad_uid's (DATA_DICTIONARY table) - copy these vars in: Flex_Permissions .php and Flex_waitlists .php
	// Two checkboxes within T2's "Insert/Edit Permit Control Group" area.
	// "SHOW UofA Web Sales Internal (IT Only)" - allows CR folks able to set checkbox dad_web_sellable_ -  http://www.pts.arizona.edu/park/
	const dad_web_showable_true		= "web_showable.DAD_UID = 200040 and web_showable.CUD_VALUE = '1'";
	// "ALLOW UofA Web Sales External" - allow to sell on web.  The "_unknown" query means we want to get all permits where web_sellable.CUD_VALUE is 1 or 0.
	const dad_web_sellable_true		= "web_sellable.DAD_UID = 200041 and web_sellable.CUD_VALUE = '1'";
	const dad_web_sellable_unknown	= "web_sellable.DAD_UID = 200041";

	// Searches against WLT_ATTEMPT.WLA_REQUEST_DATE
	//	Date to search to find Payroll folks - so they can renew.
	public $payroll_searchDate =	'2016-02-01'; // can NOT be empty
	// Date to search to find Current Permit holders - so they can renew.
	public $current_searchDate =	'2016-02-15'; // can NOT be empty

	// When a customer confirms a renewal, then these dates will be used to set WLT_ATTEMPT.WLA_REQUEST_DATE
	public $payroll_setDate =		'2016-02-02 00:00:00'; // can NOT be empty
	public $current_setDate =		'2016-02-16 00:00:00'; // can NOT be empty

	public $renew_setDate =			''; // This will be set to either payroll_setDate or current_setDate:
	public $showable_pec_list =			array();
	public $showable_pec_is_sellable =	array();
	public $sqlHist =							array();

	/***
	 * $t2_query[Query Name] = Query UID (QUERY_DEF.QDE_NAME => QUERY_DEF.QDE_UID)
	 * For use with API ExecuteQuery class (in Flex_Misc .php)
	 * Query UIDs and Query Names found here: http://128.196.6.197/PowerPark/qm/default.aspx
	 */
	protected static $t2_query = array( //---- LIVE DB
			 'Q_GetCurrentWaitlists_cast'		=> 9847,
			 'Q_GetCurrentWaitlists'		=> 7340,
			 'Q_GetAwardedPermits'			=> 7467,
			 'Q_getWebShowablePEC'			=>	7346,
			 'Q_getAwardedFaqUid'			=> 7347,
	  );

	protected function set_callback()
	{
		// to be overriden
	}


	protected function getWebShowablePEC()
	{
		/*************
		 * Puts all showable pecs in array $showable_pec_is_sellable[a PEC UID]; the value of each is = web_sellable.CUD_VALUE (1 or 0).
		 * web_sellable.CUD_VALUE of 1 means can sell permit.
		 * uses dad_web_showable_true to see if its possible for admin to make permit web sellable (http://www.pts.arizona.edu/park/).
		 * (163, 162, 159, 158, 161, 160)
		**************/
		$t2Key = 'Q_getWebShowablePEC';
		$param_ary = array();
		$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;

		$tmpResults1 = $exeResults->results_custom;

		foreach($exeResults->results_custom['PEC_UID'] as $k => $aPec)
		{
			$a_pec_uid = $exeResults->results_custom['PEC_UID'][$k];
			$this->showable_pec_list[] = $a_pec_uid;
			$this->showable_pec_is_sellable[$a_pec_uid] = $exeResults->results_custom['CUD_VALUE'][$k];
		}
	}
}



class UpdateWaitlistRequest extends Flex_Waitlists
{
	/*************************
	 * TODO: To use api (complicated way) you would have to use the two classes to Remove and then Insert,
	 * but this would change the Waitlist Attempt UID WLA_UID.
	***************************/

	//------- Input Params:
	// $currentWaitlists->wla_uid_renew - Required. Waitlist attempt uid.
	// $currentWaitlists->renew_setDate - Required. This will be obtained from an instanciated class' renew_setDate
	// $entUid - Required. Customer UID (ENTITY.ENT_UID)

	//------- Output
	// public $conf_wla	= ''; // confirmation number WLA_UID.
	public $WLA_Updated	= false; // true or false


	//	$currentWaitlists->wla_uid_renew, $currentWaitlists->wlt_uid_renew, $currentWaitlists->renew_setDate
	public function __construct($entUid, $currentWaitlists, $force_expire=false)
	{
		//################ THIS CODE COPIED FROM permit_renewal .php ##############
		// @@@@@@@@@ DELETE / INSERT METHOD  (creates new waitlist attempt uid WLA_UID, because deletes first and THEN inserts) @@@@
		// First remove the waitlist attempt and then re-insert - wla_uid_renew will change but NOT wlt_uid_renew
		// wla is waitlist-attempt id.
		$requestRemoved = new RemoveWaitlistRequest($entUid, $currentWaitlists->wla_uid_renew);
		if ($GLOBALS['DEBUG_DEBUG']) echo $requestRemoved;
		if ($requestRemoved->REQUEST_REMOVED)
		{
			// now insert
			$wla_reinsert = new InsertWaitlistRequest($entUid, $currentWaitlists->wlt_uid_renew, 1, $currentWaitlists->renew_setDate);
			if ($GLOBALS['DEBUG_DEBUG']) echo $wla_reinsert;
			if ($wla_reinsert->WAITLIST_REQUEST_UID)
			{
				$oldWla = $currentWaitlists->wla_uid_renew;
				// Refresh it ($wla_reinsert will also be same as $currentWaitlists->wla_uid_renew)
				unset($currentWaitlists);
				$currentWaitlists = new GetCurrentWaitlists($entUid);
				if ($GLOBALS['DEBUG_DEBUG']) echo $currentWaitlists;
				$tmpMsg = "old wla: " . $oldWla . "\nnew wla " . $currentWaitlists->wla_uid_renew
						. "\nSET renew_setDate:" . $currentWaitlists->renew_setDate;
				new event_log('permit_transaction', 'renewed', $entUid, '0', '0', $tmpMsg);

				$this->WLA_Updated = ($currentWaitlists->REQUEST_DATE[$wla_reinsert->WAITLIST_REQUEST_UID] == $currentWaitlists->renew_setDate) ? true : false;
			}
			else
			{
				$friendlyErr .= 'ERROR: Could not change or renew permit';
				$fileErrStr = __FILE__.':'.__LINE__ . "\n" . $friendlyErr . " - InsertWaitlistRequest function failed.\n"
					. $currentWaitlists . "\n" . $wla_reinsert . "\n";
				new event_log('permit_transaction', 'error', $entUid, '0', '0', $fileErrStr);
				exitWithBottom($friendlyErr, 'flexRelated.txt', $fileErrStr);
			}
		}
		else
		{
			$friendlyErr .= 'ERROR: Could not renew or change permit';
			$fileErrStr = __FILE__.':'.__LINE__ . "\n" . $friendlyErr
				. " - the old permit waitlist could not be removed via RemoveWaitlistRequest function.\n" . $currentWaitlists . "\n";
			new event_log('permit_transaction', 'error', $entUid, '0', '0', $fileErrStr);
			exitWithBottom($friendlyErr, 'flexRelated.txt', $fileErrStr);
		}

		if ($this->WLA_Updated) {
			new event_log('permit_transaction', 'renewed', $entUid, '0', '0', $tmpMsg);
		} else {
			$friendlyErr .= 'ERROR: Could not update waitlist!';
			$fileErrStr = __FILE__.':'.__LINE__ . "\nclass Update Waitlist Request\n" . $friendlyErr . "\n" . $this . "\n";
			new event_log('permit_transaction', 'error', $entUid, '0', '0', $fileErrStr);
			exitWithBottom($friendlyErr, 'flexRelated.txt', $fileErrStr);
		}
	}
}



class GetCurrentWaitlists extends Flex_Waitlists
{
	/****************
	 * Gets waitlist(s) the customer is on. Basically, gets anything found in FLEXADMIN.WLT_ATTEMPT for person.
	 * For Permit Renewal - Joann to set date of waitlist - $payroll_searchDate, etc
	 * OLD WLID is now WLT_UID
	*****************/

	// static notes (to display on web during debug)
	protected $notes = '
		----------------------------------------------------------------
		If customer has an Awarded permit, then only capture that data.
		T2 Waiting List color codes: <big>
			<img src="/images/flex/sm_Yellow.gif" padding="0" />- assigned
			<img src="/images/flex/sm_Black.gif" padding="0" />- gone
			<img src="/images/flex/sm_Green.gif" padding="0" />- sold
			<img src="/images/flex/sm_White.gif" padding="0" />- on waitlist</big>
		----------------------------------------------------------------';
	private $wlStatusCodes	=	array(  // to be assigned to $wlStatus
		 'awarded_right'		=> 'Awarded right to purchase permit',				/* WLA_EXPIRY_DATE not null */
		 'expired_right'		=> 'Awarded, but purchase right expired',			/* WLA_EXPIRY_DATE not null */
		 'expired_forced'		=> 'Awarded, but purchase right force-expired',	/* WLA_EXPIRY_DATE not null */
		 'renewable_payroll'	=> 'Payroll permit',										/* Customer did not renew, but on Payroll */
		 'renewed_payroll'	=> 'Payroll permit renewed on web',
		 'renewable_current'	=> 'Current permit is/was renewable',
		 'renewed_current'	=> 'Renewed Current permit on web',
		 'on_waitlist'			=> 'On Waitlist',		/* for open reg - not tested for current or payroll */
	);

	//--------------------------------- Input ---------------------------
	public $EntityUid	= ''; // Required.

	//---------------------------------- Output -------------------------

	//------------- These variables are not arrays and so are NOT permit specific (as in the array variables just below).
	// Open Reg ('wlt_open') is set to true (in constructor) IF 'Open Registration' is true in top.inc.php.
	public $wlt_open	= false;

	// If "_searchDate" or "_setDate" found in DB, then $renewal_status will be set to one of these four $wlStatusCodes:
	//		renewable_payroll, renewed_payroll, renewable_current, renewed_current.
	// If payroll_setDate or current_setDate is found in DB (FOR ANY PERMIT), then will be set to renewed_current or renewed_payroll, and then
	//		we know this person already signed up for a permit!
	public $renewal_status = ''; // subset of $wlStatus array

	// wla_uid_renew/ and /wlt_uid_renew is the current permit the person has in his hands.
	public $wla_uid_renew =	''; // WLA - (waitlist-attempt id) is the attempt to join a waitlist (a wlt_uid)
	public $wlt_uid_renew =	''; // WLT - WAITLIST.WLT_UID


	//------- Now use arrays, where each element contains data for a single waitlisted permit.
	public $wlStatus		=		array(); // $wlaaDesc[WLA_UID] =

	// Open Reg uses these arrays
	public $pecGroupUids	=		array(); // pecGroupUids[WLA_UID] =			PEC_UID (ie. [2034] = null)
	public $pnaUids		=		array(); // pnaUids[WLA_UID] =				PNA_UID
	public $pnaPrefixes	=		array(); // pnaPrefixes[WLA_UID]		=		PNA_SERIES_PREFIX (example 13SSX)
	public $expireDates	=		array(); // expireDates[WLA_UID]		=		PERMISSION_EXPIRATION_DATE (how long the permit can be used)
	//public $atmptWaitlists =	array(); // atmptWaitlists[WLA_UID] =		WLT_UID. - connection between WAITLIST_ATTEMPT and WAITLIST tables.

	public $wlaaDesc =			array(); // $wlaaDesc[WLA_UID] =				WLT_DESCRIPTION (waitlist description)
	public $wlttDesc =			array(); // $wlttDesc[WLT_UID] =				WLT_DESCRIPTION (waitlist description)
	public $wlaExpiry =			array(); //
	public $WLA_FORCE_EXPIRED =array(); //
	public $wlaPriority =		array(); //
	public $PER_UID_PURCHASED_PERMISSION =	array(); // PER_UID_PURCHASED_PERMISSION[WLA_UID] = ...
	public $WLT_UID =				array(); //
	public $REQUEST_DATE =		array(); // WLA_REQUEST_DATE

	//	public $permPrices	=		array();
	//	public $pecPrices	=			array(); // pecPrices[PEC_UID] =				PFE_RATE_AMOUNT_FIXED


	public $flex_function	= 'GetCurrentWaitlists';

	public function __construct($EntityUid, $webSellables=false, $tempCastFix=false)
	{
		Debug_Trace::traceFunc();
		/**********************************
		 * $webSellables is false for permit renewal - don't care if web sellable or not; But when RENEWING permits
		 *		then don't care if web sellable or not.
		 *	If they have a payment plan they are payroll. Query to find Payroll folks.
		 * Joann will use this to figure out who is on payroll so...
		 * she can then set $payroll_searchDate (WLT_ATTEMPT.WLA_REQUEST_DATE), and, I could also use it to
		 *		figure out stuff like: how many paymenst are left (maybe later I can do this.)
		 * Transfer agency of 2000: Payroll.  If they are on payroll, then FLEXADMIN.WLT_ATTEMPT.WLA_EXPIRY_DATE
		//Important cols: PAP_UID, ENT_UID_RESPONSIBLE_ENT, PPI_SOURCE_OBJ_UID
		$query = 'select *
			from FLEXADMIN.PAYMENT_PLAN_VIEW PPV inner join FLEXADMIN.PAYMENT_PLAN_ITEM PPI on PPI.PAP_UID_PAYMENT_PLAN = PPV.PAP_UID
			where TAB_UID_SOURCE_OBJ_TYPE = 10 and TAL_UID_TRANSFER_AGENCY=2000 and (PAP_STATUS = 1 or PAP_STATUS = 2)';
		// This will get a speciffic entity:
		$query .= 'and ENT_UID_RESPONSIBLE_ENT=:entUID';
		 * See README.txt for price stuff.
		****************************************/

		if (checkEnabled('Open Registration'))
			$this->wlt_open = true;

		$this->EntityUid = $EntityUid;

		$this->EntityUid = ctype_digit($this->EntityUid) ? $this->EntityUid : '';

		if (!$this->EntityUid) {
			$friendlyErr = 'ERROR: NO EntityUid in found!!!';
			new ws_log(9, 0, 0, 0, $friendlyErr, $this); // type is 8, The parameter '9' is error.
			exitWithBottom($friendlyErr);
		}

		$this->webSellables = $webSellables;

		/********* Query determines if Payroll (instead of searching on date)
		$searchQuery = "SELECT PAYMENT_PLAN_VIEW.PAP_UID, PAYMENT_PLAN_VIEW.ENT_UID_RESPONSIBLE_ENT, ENTITY.ENT_LAST_NAME,
			ENTITY.ENT_FIRST_NAME, ENTITY.ENT_MIDDLE_NAME, ENTITY.ENT_GROUP_NAME, ENTITY.ENT_PRIMARY_ID, ENTITY.ENT_SECONDARY_ID,
			ENTITY.ENT_TERTIARY_ID, PAYMENT_PLAN_ITEM.PPI_SOURCE_OBJ_UID, PERMISSION_VIEW.PER_NUMBER, ENTITY.ENT_UID
		FROM ((( FLEXADMIN.PAYMENT_PLAN_VIEW INNER JOIN FLEXADMIN.PAYMENT_PLAN_ITEM
				  ON FLEXADMIN.PAYMENT_PLAN_VIEW.PAP_UID = FLEXADMIN.PAYMENT_PLAN_ITEM.PAP_UID_PAYMENT_PLAN
				) INNER JOIN FLEXADMIN.ENTITY ON FLEXADMIN.PAYMENT_PLAN_VIEW.ENT_UID_RESPONSIBLE_ENT = FLEXADMIN.ENTITY.ENT_UID
			) INNER JOIN FLEXADMIN.PERMISSION_VIEW ON FLEXADMIN.PAYMENT_PLAN_ITEM.PPI_SOURCE_OBJ_UID = FLEXADMIN.PERMISSION_VIEW.PER_UID
		) INNER JOIN FLEXADMIN.PAY_PLAN_STATUS_LKP ON FLEXADMIN.PAYMENT_PLAN_VIEW.PAP_STATUS = FLEXADMIN.PAY_PLAN_STATUS_LKP.PAS_UID
		WHERE (((PAYMENT_PLAN_VIEW.TAL_UID_TRANSFER_AGENCY)=2000)	AND ((PAY_PLAN_STATUS_LKP.PAS_IS_ACTIVE)=1)
			AND ((PAYMENT_PLAN_ITEM.TAB_UID_SOURCE_OBJ_TYPE)=10)	AND ((PERMISSION_VIEW.PSL_UID_STATUS)=5) )
			AND = ENTITY.ENT_UID = " . $this->EntityUid . "
		ORDER BY ENTITY.ENT_LAST_NAME, ENTITY.ENT_FIRST_NAME";
		**************/
		/*************
		if (!$this->payroll_searchDate) {
			$friendlyErr = 'ERROR: NO Waitlist Date found!!!';
			$fileErrStr = __FILE__.':'.__LINE__ . "\n" . $friendlyErr . "\nVariable payroll_searchDate is not set \n";
			exitWithBottom($friendlyErr, 'flexRelated.txt', $fileErrStr);	}
		**************/
		/*************
		 * WLA_REQUEST_DATE, WLA_UID, WLT_UID, PEC_UID_CONTROL_GROUP,
		 * WLT_DESCRIPTION is NOT the FACILITY.FAC_DESCRIPTION "facility description".
		*******************/

		$tmpResults1 = array();

		$param_ary = array('entity uid' => $this->EntityUid);
		$t2Key = 'Q_GetCurrentWaitlists';
		// 20160113 - added 3'rd parameter $tempCastFix, and if TRUE, then it ignores expiry_date's which are NULL - cast issue.
		if ($tempCastFix)
			$t2Key = 'Q_GetCurrentWaitlists_cast';
		$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
		// set WLA_REQUEST_DATE array to YYYY-mm-dd hh:mm:ss (military time)
		$exeResults->setMoreCustom('WLA_REQUEST_DATE', 'date_1');
		$exeResults->setMoreCustom('WLA_EXPIRY_DATE', 'date_1');
		// This will wind up going back to origional oracle format in EditPermitDates class (ie: 2013-12-02T09:00:00).
		$exeResults->setMoreCustom('PEC_EXPIRATION_DATE', 'date_1');

		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;

		$tmpResults1 = $exeResults->results_custom;

		//	foreach($exeResults->results_custom['WLA_UID'] as $k => $aPec)
		//	if(in_array($aPec, $pcg_uid)) $tmpResults1['PNA_SERIES_PREFIX'][]= $exeResults->results_custom['PNA_SERIES_PREFIX'][$k];
		//if ($GLOBALS['DEBUG_DEBUG']) echo '<pre>'.print_r($tmpResults1,true).'<hr>';

		if ($this->webSellables) // When renewing permits don't care if web sellable or not.
			$this->getWebShowablePEC(); // Sets showable_pec_is_sellable and showable_pec_list


		for ($i=0; $i<sizeof($tmpResults1['WLA_UID']); $i++)
		{
			$tempPecUid = $tmpResults1['PEC_UID_CONTROL_GROUP'][$i] ? $tmpResults1['PEC_UID_CONTROL_GROUP'][$i] : -1111111;

			if ($this->webSellables && !$this->showable_pec_is_sellable[$tempPecUid])
			{
				if ($GLOBALS['DEBUG_DEBUG']) {
					echo '<div style="border:2px solid red; font-size:14px; padding:1px;">Debug Data: "' . $tmpResults1['WLT_DESCRIPTION'][$i] .
						  '" is NOT sellable on the web.</div><br>';
				}
				continue;
			}

			if (!$tmpResults1['WLA_UID'][$i])
			{
				echo 'Seems you are not on the Payroll Deduction program. Please contact the <a href="/about/office.php" style="color:#330099;">PTS Main Office</a> if this is an error.<br><br><br>';
			}

			$tmpWLA = $tmpResults1['WLA_UID'][$i]; // waitlist attempt id.
			$this->WLT_UID[$tmpWLA]			= $tmpResults1['WLT_UID'][$i];
			$this->REQUEST_DATE[$tmpWLA]	= $tmpResults1['WLA_REQUEST_DATE'][$i];

			//=====================================================================================================
			// If WLA_REQUEST_DATE (WLA_REQUEST_DATE) matchis a '_searchDate' or '_setDate' then set renewal_status.
			// If not, then permit is probably on waitlist.
			if ($tmpResults1['WLA_REQUEST_DATE'][$i])
			{
				// JODY 2015-03-23 No longer factoring in HH:II:SS     if ($tmpResults1['WLA_REQUEST_DATE'][$i] == $this->payroll_searchDate)
				if (preg_match('/'.preg_quote($this->payroll_searchDate).'/si', $tmpResults1['WLA_REQUEST_DATE'][$i]) && !$tmpResults1['WLA_EXPIRY_DATE'][$i])
				{
					$this->renew_setDate = $this->payroll_setDate;
					$this->renewal_status = $this->wlStatus[$tmpWLA] = 'renewable_payroll'; // array is permit-sepeciffic
				}
				//----------------------------- See if already on waitlist for payroll.
				// JODY 2015-03-29 No longer factoring in HH:II:SS     elseif ($tmpResults1['WLA_REQUEST_DATE'][$i] == $this->payroll_setDate)
				elseif (preg_match('/'.preg_quote($this->payroll_setDate).'/si', $tmpResults1['WLA_REQUEST_DATE'][$i]) && !$tmpResults1['WLA_EXPIRY_DATE'][$i])
				{
					$this->renew_setDate = $this->payroll_setDate;
					$this->renewal_status = $this->wlStatus[$tmpWLA] = 'renewed_payroll'; // array is permit-sepeciffic
				}
				//----------------------------- See if person is renewing for Current
				// JODY 2015-03-29 No longer factoring in HH:II:SS     elseif ($tmpResults1['WLA_REQUEST_DATE'][$i] == $this->current_searchDate)
				elseif (preg_match('/'.preg_quote($this->current_searchDate).'/si', $tmpResults1['WLA_REQUEST_DATE'][$i]) && !$tmpResults1['WLA_EXPIRY_DATE'][$i])
				{
					$this->renew_setDate = $this->current_setDate;
					$this->renewal_status = $this->wlStatus[$tmpWLA] = 'renewable_current'; // array is permit-sepeciffic

				}
				//----------------------------- See if already on waitlist for Current
				// JODY 2015-03-29 No longer factoring in HH:II:SS    elseif ($tmpResults1['WLA_REQUEST_DATE'][$i] == $this->current_setDate)
				elseif (preg_match('/'.preg_quote($this->current_setDate).'/si', $tmpResults1['WLA_REQUEST_DATE'][$i]) && !$tmpResults1['WLA_EXPIRY_DATE'][$i])
				{
					$this->renew_setDate = $this->current_setDate;
					$this->renewal_status = $this->wlStatus[$tmpWLA] = 'renewed_current'; // array is permit-sepeciffic
				}
				//----------------------------- See if already on waitlist for Open Reg
				else if ($this->wlt_open && !$tmpResults1['WLA_FORCE_EXPIRED'][$i] && !$tmpResults1['WLA_EXPIRY_DATE'][$i])
				{
					// Open Registration - this is new, so not tested for Payroll and Current folks
					//	If forced expire is 0 AND expiry date IS null, then they have already signed up (not awarded), so they can't register again.
					$this->wlStatus[$tmpWLA] = 'on_waitlist'; // array is permit-sepeciffic
				}
				else
				{
					$this->wlStatus[$tmpWLA] = ''; // array is permit-sepeciffic
				}
			}

			if ($this->renewal_status)
			{
				// This "if" condition will only be called a max of ONE time.
				// Note: if renewed_payroll or renewed_current is set above, then priority is 2.
				$this->wla_uid_renew =	$tmpWLA; // waitlist attempt id.
				$this->wlt_uid_renew =	$this->WLT_UID[$tmpWLA];
				//echo "<pre>~~~~~~~~~~~~~~~~~~~~$searchQuery<br>";
				//echo print_r($this, true) . '<br><br></pre>';
			}

			//$this->atmptWaitlists[$tmpWLA] =	$this->WLT_UID[$tmpWLA];
			$this->pecGroupUids[$tmpWLA] =		$tempPecUid;
			$this->pnaUids[$tmpWLA] =				$tmpResults1['PNA_UID_PER_NUM_RANGE'][$i];
			$this->pnaPrefixes[$tmpWLA] =			$tmpResults1['PNA_SERIES_PREFIX'][$i];
			$this->expireDates[$tmpWLA] =			$tmpResults1['PEC_EXPIRATION_DATE'][$i];
			// Values are the same:
			$this->wlaPriority[$tmpWLA] =			$tmpResults1['WLA_ORDER'][$i];
			$this->wlaaDesc[$tmpWLA] =				$tmpResults1['WLT_DESCRIPTION'][$i];
			$this->wlttDesc[$this->WLT_UID[$tmpWLA]] =	$tmpResults1['WLT_DESCRIPTION'][$i];
			$this->wlaExpiry[$tmpWLA] =			$tmpResults1['WLA_EXPIRY_DATE'][$i];
			$this->WLA_FORCE_EXPIRED[$tmpWLA] =	$tmpResults1['WLA_FORCE_EXPIRED'][$i];
			$this->PER_UID_PURCHASED_PERMISSION[$tmpWLA] = $tmpResults1['PER_UID_PURCHASED_PERMISSION'][$i];

			if ($this->WLA_FORCE_EXPIRED[$tmpWLA])
				$this->wlStatus[$tmpWLA] = 'expired_forced'; // permit-sepeciffic

			if ($this->wlaExpiry[$tmpWLA])
			{	// The person was awarded right to purchase (till expire date).
				// So overwrite any pervious wlaExpiry from above.
				if (strtotime($this->wlaExpiry[$tmpWLA]) >= time()) // || $this->wlaExpiry[$tmpWLA]==$this->payroll_searchDate)
				{ // see if force expired
					if (!$this->WLA_FORCE_EXPIRED[$tmpWLA])
						$this->wlStatus[$tmpWLA] = 'awarded_right'; // permit-sepeciffic
				}
				else
				{ // date expired haha.
					$this->wlStatus[$tmpWLA] = 'expired_right'; // permit-sepeciffic
				}
			}
		}
		//		$this->permPrices = new GetPermissionPriceFixed($this->pecGroupUids);
		//		$this->pecPrices = $this->permPrices->groupPrices;
	}
}


class GetAwardedPermits extends Flex_Waitlists
{
	/****************
	 * Gets any awarded permits.
	 * Also gets any purchased permit found in wlt_attempt table - should only be one.
	 * $webSellables can be set to false for status checking - when permits are awarded but not yet sellable
	 * An object $waitlists (GetCurrentWaitlists) is passed in to constructor.
	 *	Make sure award date not expired.
	 *		Awarded permit? (wla_expiry_date > sysdate) and (wla_force_expired = 0) and (permit sell permission is null - means NOT already purchased)
	*****************/

	//------------ Input --------------
	// $waitlists Object - Type: GetCurrentWaitlists;

	//------------- Output ---------------
	public $firstAwarded_pecUID		= '';			// First value in pecGroupUidsAwarded.
	public $firstAwarded_wlaUID		= '';			// First key in pecGroupUidsAwarded.
	public $firstAwarded_pnaUID		= '';			// First value in pnaUidsAwarded.
	public $firstAwarded_pnaPrefix	= '';			// First value in pnaPrefixesAwarded (example 13SSX)
	public $firstAwarded_facUID		= '';			// First value in facUidsAwarded.
	public $firstAwarded_expire_date	= '';			// PERMISSION_EXPIRATION_DATE

	// perUidsPurchased_wlaUID = A purchased waitlisted permit - we know it's purchased if value
	// is in WLT_ATTEMPT_VIEW.PER_UID_PURCHASED_PERMISSION
	public $perUidsPurchased_wlaUID	= ''; // Purchased permit wlaUID.
	public $wlaDescAwarded			= array();	// Same as waitlist parameter - $waitlists->wlttDesc[]
	public $pecGroupUidsAwarded	= array();	// pecGroupUidsAwarded[WLA_UID] = PEC_UID (ie. [2034] = null) (Same array format as $pecGroupUids.)
	public $pnaUidsAwarded			= array();	// pnaUidsAwarded[WLA_UID] = PNA_UID
	public $pnaPrefixesAwarded		= array();	// pnaPrefixesAwarded[WLA_UID] = PNA_SERIES_PREFIX
	public $facUidsAwarded			= array();	// facUidsAwarded[WLA_UID] = FAC_UID
	public $awardFailReasons		= array();	// gives reasons for failed awarded permit(s) - customer does NOT see, but sees the next var.
	public $customFailText			= array();	// Browser output which gives reasons for failed purchase.

	public function __construct($waitlists, $webSellables=true, $registration=false) // 20130614 added $webSellables
	{
		Debug_Trace::traceFunc();

		// SEE IF AWARDED PERMIT IS WEB SELLABLE, so can put in awardFailReasons -- USE getWebShowablePEC
		// Also, in permit_renewal.php , when people try to register and if they have a permit, it uses 'on waitlist' - not good enough

		$this->getWebShowablePEC(); // Sets showable_pec_is_sellable and showable_pec_list
		// and wl.PEC_UID_CONTROL_GROUP in (".implode(',',$this->showable_pec_list).")";

		$skipExpired = false; // for debug purposes - to speed things up.

		$this->webSellables = $webSellables;
		$this->registration = $registration;

		// for each user's waitlist pec's
		foreach ($waitlists->pecGroupUids AS $aWlaUid => $aPecUid)
		{
			if ($skipExpired && $GLOBALS['DEBUG_DEBUG'] && $waitlists->wlStatus[$aWlaUid]=='expired_right')
				continue;
			$t2Key = 'Q_GetAwardedPermits';
			$param_ary = array();
			$param_ary['Waitlist Attempt UID'] = $aWlaUid;
			$param_ary['Allow UA Web Sales External A'] = $this->webSellables ? 1 : 0;
			// This third param will always equal 1, so that if the above param two is 0 then it will be OR'ed with that 1.
			$param_ary['Allow UA Web Sales External B'] = 1;
			$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
			// set WLA_REQUEST_DATE array to YYYY-mm-dd hh:mm:ss (military time)
			$exeResults->setMoreCustom('WLA_REQUEST_DATE', 'date_1');
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;

			$tmpResults1	= $exeResults->results_custom;
			$WLA_UID_T		= $exeResults->results_custom['WLA_UID'][0];
			//	$WLA_REQUEST_DATE_T	= $exeResults->results_custom['WLA_REQUEST_DATE'][0];
			$WLA_REQUEST_DATE_T	= preg_replace('/^([^\s]+).*$/si', '$1', trim($exeResults->results_custom['WLA_REQUEST_DATE'][0]));

			$wla_unix	= strtotime($WLA_REQUEST_DATE_T);

			if ($this->webSellables)
			{
				// Don't let payroll folks pay.
				if ($wla_unix == strtotime($this->payroll_searchDate) || $wla_unix == strtotime($this->payroll_setDate))
					$auto_perm_cust = true;
				else
					$auto_perm_cust = false;
			}

			$this->awardFailReasons[$aWlaUid] = '';

			if (@$auto_perm_cust)
			{
				$this->awardFailReasons[$aWlaUid] .= 'Account is enrolled in Automatic Payroll Deduction.<br>';
			}
			else if ($this->webSellables && !$this->showable_pec_is_sellable[$aPecUid])
			{
				$this->awardFailReasons[$aWlaUid] .= 'ERROR: Not Web Sellable! (' . $WLA_REQUEST_DATE_T . ') ' . CONTACT_CR;
			}
			else if ($WLA_UID_T) // Same as $aWlaUid (if set)
			{
				//----------------------------------- Awarded, able to purchase permits.
				// There should only be one awarded permit!!!
				$this->wlaDescAwarded[$aWlaUid]			= $waitlists->wlaaDesc[$aWlaUid];
				$this->pecGroupUidsAwarded[$aWlaUid]	= $aPecUid; // same as $waitlists->pecGroupUids[$aWlaUid]
				$this->pnaPrefixesAwarded[$aWlaUid]		= $waitlists->pnaPrefixes[$aWlaUid];
				if (!$this->firstAwarded_pecUID)
				{
					$this->firstAwarded_pecUID			= $this->pecGroupUidsAwarded[$aWlaUid];
					$this->firstAwarded_pnaPrefix		= $this->pnaPrefixesAwarded[$aWlaUid];
					$this->firstAwarded_wlaUID			= $aWlaUid;
					$this->firstAwarded_expire_date	= $waitlists->expireDates[$aWlaUid];
				}

				$this->pnaUidsAwarded[$aWlaUid]		= $waitlists->pnaUids[$aWlaUid];
				if (!$this->firstAwarded_pnaUID)
					$this->firstAwarded_pnaUID		= $this->pnaUidsAwarded[$aWlaUid];

				$this->getAwardedFaqUid($aPecUid, $aWlaUid);
			}
			else
			{
				//--------------------------------- Not able to purchase, so now give reasons!
				// Use $waitlists data - because DB results may be empty if web_sellable.DAD_UID != dadUID_sellable

				//echo '<pre><pre><hr><pre>'.var_dump($this,true).'<hr>';
				//echo '<hr><pre>'.var_dump($waitlists,true).'<hr>';

				if ($aWlaUid)
				{
					// Permit already purchased. There could be more than one error, but use only one.???
					if ($waitlists->PER_UID_PURCHASED_PERMISSION[$aWlaUid])
					{
						// should only be one purchased permit found in the waitlist attempt table.
						// So this if condition will only be called once!
						if ($this->perUidsPurchased_wlaUID)
						{
							if ($GLOBALS['DEBUG_DEBUG'])
							{
								echo '<div style="color:red; font-weight:bold;">DEBUG DATA: MORE THAN ONE PURCHASED
									PERMIT FOUND IN WLT_ATTEMPT TABLE! wlaUID: ' .$this->perUidsPurchased_wlaUID.'.<br>
									...skipping any further "expired_right" waitlists.</div>';
								$skipExpired = true;
							}
						}
						if (!$this->registration)
						{
							// ignore if registering - see waitlist .php
							$this->perUidsPurchased_wlaUID		= $aWlaUid;
							$this->awardFailReasons[$aWlaUid]	.= 'Account has already purchased this permit.<br>' . CONTACT_CR . '<br>';
							// Notification when customer tries to purchase 2nd permit online:
							$this->customFailText					= 'You are receiving this message because you may only purchase one permit per year
								<em><b>online</b></em>.  Our records show that you have already purchased a permit.
								<br/> If you would like to learn how to exchange your permit, or are interested in purchasing additional permits, then
								' . CONTACT_CR . '.';
						}
					}

					/****** 2013-01-30
					//else if (!$this->waitlistDadUidData[$aWlaUid]['WLA_EXPIRY_DATE'])
					//	$this->awardFailReasons[$aWlaUid] .= 'A permit has not been awarded to this account.<br>';
					//else if (strtotime($this->waitlistDadUidData[$aWlaUid]['WLA_EXPIRY_DATE']) < time())
					//	$this->awardFailReasons[$aWlaUid] .= 'The permit award for this account has expired.<br>';
					//else if ($this->waitlistDadUidData[$aWlaUid]['WLA_FORCE_EXPIRED'] != '0')
					//	$this->awardFailReasons[$aWlaUid] .= 'The permit award for this account has expired.<br>';
					//else	$this->awardFailReasons[$aWlaUid] .= 'Unknown.<br>';
					********/
				}
				else
				{
					// WLA_UID empty if web_sellable.DAD_UID != dadUID_sellable
					$this->awardFailReasons[$aWlaUid] .= 'ERROR: Permit awarded to this account is not available for sale online at this time.<br>' . CONTACT_CR . '<br>';
				}
			}

			if ($this->awardFailReasons[$aWlaUid])
			{
				$this->awardFailReasons[$aWlaUid] = 'Facility: ' . $waitlists->wlaaDesc[$aWlaUid] . '<br>' .
																'Reason: ' . $this->awardFailReasons[$aWlaUid];
			}
		}
	}


	private function getAwardedFaqUid($aPecUid, $aWlaUid)
	{
		//---------- Now get the FAC_UID for this PEC_UID.

		$tmpRes = $this->getPecFacilities($aPecUid, 'GetAwardedPermits');

		$friendlyErr = '';

		// There must only be one here, and only one FAC_UID per PEC_UID.
		if (sizeof($tmpRes) == 1)
		{
			// maybe some day do this: $waitlists->facUids[$aWlaUid];
			$this->facUidsAwarded[$aWlaUid] = $tmpRes[0];
			if (!$this->firstAwarded_facUID)
				$this->firstAwarded_facUID = $this->facUidsAwarded[$aWlaUid];
		}
		elseif (sizeof($tmpRes) > 1)
		{
			$friendlyErr = 'ERROR: Multiple Facilities found! ' . CONTACT_CR;
		}
		else
		{
			$friendlyErr = 'ERROR: No Facilities found! ' . CONTACT_CR;
		}

		if ($friendlyErr)
		{
			new ws_log(9, 0, 0, 0, $friendlyErr, $this); // type is 8, The parameter '9' is error.
			exitWithBottom($friendlyErr);
		}
	}


	static public function getPecFacilities($aPecUid)
	{
		/***
		 * Given a PEC_UID, returns array $results[1..n] = FAC_UID.
		 */
		$t2Key = 'Q_getAwardedFaqUid';
		$param_ary = array('pec uid' => $aPecUid);
		$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, 'GetAwardedPermits');
		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
		return $exeResults->results_custom['FAC_UID_FACILITY'];
	}


	public function verifyAwarded($currentWltDesc, $displayErrors=true)
	{
		/***********************
		 * HACK - this function will change in the future, cuz we want to be able to sell MORE than just one permit on the web.
		 * There should only be ONE awarded permit.
		 * If fail, then output any reasons and return FALSE.
		 * If success, simply return true.
		 * Don't be a kill-joy. If the person already has an award, don't show him all his problems with other
			waitlisted permits (like second-choice permit for example).
		 * If an awarded permet ($a_wla) has already been sold for some reason, then awardFailReasons[$a_wla] will reflect this????
		 * If $displayErrors is true then display the errors, else just return false (if errors that is) without display.
		***********************/


		// note: $failReasons ALSO SHOWS DEBUG MESSAGES ON BROWSER.

		if ($this->firstAwarded_wlaUID && sizeof($this->pecGroupUidsAwarded)==1)
		{
			//----------------------- GOOD TO GO, ONE AWARDED PERMIT!!!!!!!!
			$failReasons = '';
		}
		else if (sizeof($this->pecGroupUidsAwarded)>1)
		{
			//----------------------- GOOD TO GO, AWARDED PERMITs !!!!!!!!
			// 2014-06-27 - got rid of error, cuz people CAN have more than one awarded.
			$failReasons = ''; // ERROR: Too many awarded permits
		}
		else
		{
			foreach ($currentWltDesc as $a_wla => $a_desc)
			{
				if ($this->awardFailReasons[$a_wla])
				{
					//---------- No worries, there could still be an awarded permit out there, somewhere.
					// (This will probably be the person's secon'd choice permit - where his first choice was awarded.
					$failReasons .= $this->awardFailReasons[$a_wla] . '<br />';
				}
			}

			if (!$failReasons)
			{
				if (!sizeof($this->pecGroupUidsAwarded))
				{
					//------ Nothing on the waitlist for this customer., so get all Eligible Waitlist permits, and make a dropdown?
					//$eligible_waitlists = new GetEligibleWaitlists(false,false,true);
					//if ($GLOBALS['DEBUG_DEBUG']) echo $eligible_waitlists;
					// makePermitSelect('WLT_UID_choice', $eligible_waitlists); // make <select> tag
					$failReasons = 'No permits awarded. Not Web Sellable, or customer needs to register for a permit.';
				}
				else
				{
					$failReasons = 'ERROR: Account does not have any permits awarded to be purchased.';
				}
			}
		}


		if ($failReasons)
		{
			if ($displayErrors)
			{
				?>
				<div style="color:black; padding:5px; border:1px solid #c03; padding:5px; font-weight:normal; font-size:14px;">
				<?php if ($this->customFailText) { ?>
					<?php echo $this->customFailText;?>
				<?php } else { ?>
					If you experience a problem while trying to purchase your permit, &nbsp;
					<?php echo CONTACT_CR .' during regular business hours.<br />'; ?>
				<?php } ?>

				<?php if (!sizeof($currentWltDesc)) { ?>
					<br/>Please first
					<a href="/account/regnotice.php?per_reg=openregistration">register for a permit</a>, if you have not already done so.
				<?php } ?>

				<?php
				//----------------- Display any active permits.
				$activePermits = new GetPermissions($_SESSION['entity']['ENT_UID']); // still need to refine, to find active - below.
				if ($GLOBALS['DEBUG_DEBUG']) echo $activePermits;
				$active_perms = '';
				foreach ($activePermits->per_numbers as $k => $aPerNumber)
				{
					// Display permit to customer only if NOT bicycle, RFID, Garage Daily Pass
					if (!preg_match('/bicycle/si', $activePermits->pnaDesc[$k]) && !preg_match('/RFID/si', $activePermits->pnaDesc[$k]) && !preg_match('/^Garage Daily Pass/si', $activePermits->pnaDesc[$k]))
						$active_perms .= '<div style="font-weight:bold; padding:2px;">Active Permit: ' . $aPerNumber . '<br/></div>';
				}
				if ($active_perms)
					echo '<br /><span style="color:black;">Here are your active permits:<br />' . $active_perms.'</span>';

				if (sizeof($currentWltDesc))
				{
					/********
					?>
					<br/> <a href="/account/waitlist.php?show_waitlist=1">Check your permit registration status here</a>.
					<?php
					***/
				}

				?>
				</div>
				<br/>
				<?php
			}
			return false;
		}
		else
		{
			return true;
		}
	}

}




class GetEligibleWaitlists extends Flex_Waitlists
{
	/**************************
	 * This method returns a list of all eligible waitlists - can customize for a given customer.
	 * Eventually this data is used to create <select> dropdowns for Choice 1/2 permits. One way to get rid of old
	 *		permits to NOT show up in <select> list is to set CUSTOM_DATA.CUD_VALUE = 1 where DAD_UID = 200039 = this is
	 *		the "Allow Waitlist Price to Show on Web (IT Only)" checkbox in T2.
	 *	see also http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=61
	****************************/

	/* smiliar to GetCurrentWaitlists class */

	//------- Input
	public $use_xml		= false;
	public $EntityUid		= ''; // Required if use_xml is true.

	//------- Output
	/**************************
	 * theEligiblePrices[WLT_UID] =			PFE_RATE_AMOUNT_FIXED (the price)
	 * allPrices[WLT_UID] =						PFE_RATE_AMOUNT_FIXED (the price) // includes non-multi-choice waitlists (WLT_IS_MULTIPLE_CHOICE is false)
	 * If using PEC_UID-bsed (group based):
	 *		theEligibleDesc[WLT_UID] =			WLT_DESCRIPTION (waitlist description)
	 *		theEligibleGroups[WLT_UID] =		PEC_UID	  -- keys are WAITLIST.WLT_UID
	 * If using FAC_UID-bsed (facility based):
	 * 	theEligibleWaitlists[FAC_UID] =	WLT_UID  -- Keys are WAITLIST.FAC_UID_FACILITY (the facility), and Valus are WAITLIST.WLT_UID.
	 *		all_facilityList[FAC_UID] =		facility description.
	****************************/

	// Proration begins ~ Sept 2, in which case we hide $theEligiblePrices from dropdowns by setting this to false.
	public $showTheEligiblePrices	= false; // ALWAYS FALSE NOW YEA!!!!
	public $theEligiblePrices		= array();

	public $theEligibleWaitlists	= array();
	public $theEligibleDesc			= array();
	public $allPrices					= array();
	public $theEligibleGroups		= array();
	public $theEligiblePNAs			= array();
	public $all_facilityList		= array();

	public $eligibilityTable		= ''; // TEMPORARY: just a big html table showing crap


	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';

	public $flex_function	= 'GetEligibleWaitlists';

	public function __construct($entUID='', $use_xml=false, $webSellables=false)
	{
		Debug_Trace::traceFunc();
		/**********************************
		***********************************/

		if ($this->use_xml) // don't think this is ever true.
			$this->xml_request = 'SOAP 1.1';
		//$this->showTheEligiblePrices = (withinTimeframe(date('Y').'-09-01 00:00:00', (date('Y')+1).'-03-20 00:00:00')) ? false : true;
		parent::__construct();
		$this->use_xml =		$use_xml;
		$this->EntityUid =	$entUID;
		$this->webSellables = $webSellables;

		if ($this->use_xml) // again, don't think this is ever true.
		{
			$this->send_xml();
		}
		else
		{
			// May as well just use GetPermissionPriceFixed in Flex_Permissions
			$perm_prices =	new GetPermissionPriceFixed();
			if ($GLOBALS['DEBUG_DEBUG']) echo $perm_prices;
			foreach ($perm_prices->isMultiWait as $aWlt => $isMultiWait)
			{
				$this->allPrices[$aWlt] = $perm_prices->groupPrices[$aWlt];
				if ($isMultiWait)
				{
					// array keys are all WLT_UID
					$this->theEligiblePrices[$aWlt] =	$perm_prices->groupPrices[$aWlt];
					$this->theEligibleDesc[$aWlt] =		$perm_prices->waitlistDesc[$aWlt];
					$this->theEligibleGroups[$aWlt] =	$perm_prices->groupWaitlist[$aWlt];
					$this->theEligiblePNAs[$aWlt] =		$perm_prices->pnaWaitlist[$aWlt];
				}
			}
			unset($perm_prices);
		}

		if ($this->webSellables)
		{
			$this->getWebShowablePEC(); // showable_pec_is_sellable
			$tmpGrps = $this->theEligibleGroups;

			foreach ($tmpGrps as $aWltUid => $aPecUid)
			{
				if (!$this->showable_pec_is_sellable[$aPecUid])
				{
					unset($this->theEligibleGroups[$aWltUid]);
					unset($this->theEligiblePNAs[$aWltUid]);
					unset($this->theEligibleDesc[$aWltUid]);
					unset($this->theEligiblePrices[$aWltUid]);
				}
			}
		}
		//if ($_SERVER['REMOTE_ADDR']=='128.196.6.46') {
		//	echo '<pre>';
		//	echo '<hr>$this->theEligibleDesc 2222222222222222222222222222::::::::::: ';
		//	print_r($this->theEligibleDesc);
		//	echo '<hr></pre>';
		//}
	}


	protected function get_xml()
	{
		/************************
		$this->xml_data =
		'<' . $this->flex_function . ' xmlns="http://www.t2systems.com/">
			<loginInfo>
			  <UserName>'	. $this->WS_CONN['WS_user']	. '</UserName>
			  <Password>'	. $this->WS_CONN['WS_pass']	. '</Password>
			  <Version>'	. $this->xml_version				. '</Version>
			</loginInfo>
			<parameter>
			  <EntityUid>'	. $this->EntityUid	. '</EntityUid>
			</parameter>
		</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
		************************/
	}


	protected function set_callback()
	{
		//Called from config.php
		//######## php sort() and ksort() functions seems to have bugs!
		/************************
		// Get ALL facilities.
		$tmp_facility_list = new GetFacilityList();
		$this->all_facilityList = $tmp_facility_list->theFacilityList;
		;// TRY THIS: $this->all_facilityList = GetFacilityList(')->theFacilityList;
		$this->theEligiblePrices = $this->get_FixedPrices($this->theEligibleGroups);
		************************/

		if ($GLOBALS['DEBUG_DEBUG']) {
			$this->makeEligibleTable();
			echo $this->eligibilityTable;
		}
	}


	protected function makeEligibleTable()
	{	Debug_Trace::traceFunc();
		/********************
		 * Just a hack for now.
		 * Makes an html table of all the entries from all_facilityList that are not in theEligibleWaitlists.
		 * This html table is put in $this->eligibilityTable;
		********************/

		$differs = array_diff(array_keys($this->all_facilityList), array_keys($this->theEligibleWaitlists));

		$this->eligibilityTable = '<table><tr>'.
		'<td valign="top" style="font-size:11px; padding:3px; background-color:#aaffff; border:2px solid green;"><pre>' .
				'!!!! all entries from all_facilityList <br> that are not in <b>theEligibleWaitlists</b>!!<br>'.
		print_r($differs,true) .
		'</pre><br>!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!<br>' .

		'</td><td valign="top" style="font-size:11px; padding:3px; background-color:#aaffff; border:2px solid green;"><pre><br><b>theEligibleWaitlists</b><br>' .
		print_r($this->theEligibleWaitlists,true) .

		'</pre></td><td valign="top" style="font-size:11px; padding:3px; background-color:#aaffff; border:2px solid green;"><pre><br><b>theEligiblePrices</b>[WLT_UID]=>PFE_RATE_AMOUNT_FIXED <br>' .
		print_r($this->theEligiblePrices,true) .

		'</pre></td><td valign="top" style="font-size:11px; padding:3px; background-color:#aaffff; border:2px solid green;"><pre><br><br>all_facilityList[FAC_UID]=>Description <br>' .
		print_r($this->all_facilityList,true) .
		'</pre></td></tr></table>';
	}
}



class InsertWaitlistRequest extends Flex_Waitlists
{
	// http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=61

	/*************************
	This method is used to insert a new waitlist request. Using Joann's payroll_setDate or current_setDate to set REQUEST_DATE.
		(If a request date REQUEST_DATE is not included the current date and time will be used.)
	The priority parameter will observe the following logic:
	--------------------- Value --------------------------------------------------------- Action ------------------------------
	Null																						New request is placed at top of list.
	1																							New request is placed at top of list.
	9999																						New request is placed at bottom of list.
	Any number between first and last request position on list.				New request is placed at position requested.
	Any number larger than last request position.								New request is placed at bottom of list.
	---------------------------------------------------------------------------------------------------------------------------
	After a request is inserted all other request will be reprioritized and renumbered. On the successful completion of this method an
	XML string will be returned containing the waitlist request UID. If the method cannot be completed successfully an error will be returned.

	This is the OLD way of doing it (wlid is now WAITLIST_UID)
	function wlUpdate ($wlid, $priority, $newDate) {
		db Conn->query("UPDATE FWAITLIST SET WLEDITDATE=SYSDATE,WLREQUESTDATE=TO_DATE('$newDate','YYYY-MM-DD HH24:MI:SS'),WLPRIORITY=$priority WHERE WLID=$wlid");
	}
	***************************/

	public $flex_function	= 'InsertWaitlistRequest';
	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';

	//------- Input
	public $ENT_UID				= ''; // Required.  Customer UID (ENTITY.ENT_UID)
	public $WAITLIST_UID			= ''; // Required.  Waitlist UID (WAITLIST.WLT_UID)

	// BUG: Must be NULL; or maybe try and set to exact priority - relative to other waitlists?
	// Should be WLT_ATTEMPT.WLA_ORDER. required? - If no value is submitted the new entry will be placed at the top of the list
	public $WAITLIST_PRIORITY	= NULL;

	public $REQUEST_DATE			= NULL; // required? - If no value is submitted the current time and date will be used.  Waitlist request date. Format is MM/DD/YYYY.

	//------- Output
	public $WAITLIST_REQUEST_UID	= ''; // WLA_UID (attempt - documentation says Waitlist Request UID).


	public function __construct($p_entuid, $p_wltuid, $p_priority=NULL, $p_date=NULL)
	{
		/**********************************
		***********************************/
		parent::__construct();
		$this->ENT_UID			= $p_entuid;
		$this->WAITLIST_UID	= $p_wltuid;
		if ($p_priority) {
			// If priority NOT set, then will insert into first priority.
			$this->WAITLIST_PRIORITY= $p_priority;
		}
		//	if ($p_date) // Joann's payroll_setDate or current_setDate
			$this->REQUEST_DATE		= $p_date;

		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('ENTITY_UID',			$this->ENT_UID);
		$this->xml_data .= make_param('WAITLIST_UID',		$this->WAITLIST_UID);
		$this->xml_data .= make_param('WAITLIST_PRIORITY',	$this->WAITLIST_PRIORITY);
		$this->xml_data .= make_param('REQUEST_DATE',		$this->REQUEST_DATE);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}


class RemoveWaitlistRequest extends Flex_Waitlists
{
	/*************************
	Remove a waitlist attempt - WLA_UID
	***************************/

	public $flex_function	= 'RemoveWaitlistRequest';
	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';

	//------- Input
	public $ENT_UID					= ''; // Required.  Customer UID (ENTITY.ENT_UID)
	public $WAITLIST_REQUEST_UID	= ''; // Required.  WLA_UID (waitlist attempt uid)

	//------- Output
	public $REQUEST_REMOVED	= false;


	public function __construct($p_1, $p_2)
	{
		/**********************************
		***********************************/
		parent::__construct();
		$this->ENT_UID					= $p_1;
		$this->WAITLIST_REQUEST_UID= $p_2; // wla_uid
		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('ENTITY_UID',				$this->ENT_UID);
		$this->xml_data .= make_param('WAITLIST_REQUEST_UID',	$this->WAITLIST_REQUEST_UID);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';
		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}

?>