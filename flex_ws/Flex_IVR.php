<?php

require_once '/var/www2/include/flex_ws/config.php';

abstract class Flex_IVR extends Flex_Funcs
{
	/****************
	 * Documentation at PAGE=42: http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=42
	******************/
	public $flex_group			= 'T2_Flex_IVR';

	// for cite appels and payments. (Maybe make sql query from VIOLATION_ESCALATION.VIE_ISSUE_OFFSET table.)
	// for debugging, see $extendCiteAppeal
	private static $appealDays = 15;

	// Non payable and non appealable citations.
	// array non_pay_appeal[Description] => VIC_CODE...  Array keys are the description; values are the vic_code.
	public $non_pay_appeal		= array(
											'Boot Fee - 1QA'			=> '1QA',
											'Boot Tampering'			=> '1VA',
											// 'Non Return of RFID'		=> '1RF',
											// 'Loss Bike Key/U Lock'	=> '2R',
											// 'Bike Replacement Fee'	=> '2S',
											// 'Bike Return Late Fee'	=> '2U',
											);

	//-------- Output [ARRAYS] (hack) from set_callback.
	public $CITATION_NUMBER			= array(); // The citation number (UID) being appealed. (CONTRAVENTION.CON_TICKET_ID).
	public $CONTRAVENTION_STATUS	= array(); // CSL_UID_STATUS
	public $ENT_UID_RESPONSIBLE	= array(); // ENT_UID_RESPONSIBLE_ENT.  Flex ID.  Responsible party.  (ENTITY.ENT_UID)
	public $VEH_UID_VEHICLE			= array();
	public $CON_COMMENT_PUBLIC		= array(); // PHOTO info.
	public $VIC_BASE_AMOUNT			= array(); // Think this is the origional amount.
	public $CON_IS_UNDER_APPEAL	= array(); //
	public $CON_HAS_NOTE				= array(); //
	public $VIC_LEGAL_DESCRIPTION	= array();
	//jody 20151215 - removed CON_LAST_ESCALATION from query because T2 would give a "Cast" error if CON_LAST_ESCALATION was NULL.
	// public $CON_LAST_ESCALATION	= array(); // DATE yyyy-mm-dd hh:ii:ss (this should be $CON_ISSUE_DATE + $appealDays)

	//-------- XML Output [ARRAYS]:
	public $CON_UID					= array();
	public $CON_AMOUNT_DUE			= array();
	public $CON_SNAP_VEH_PLATE_LICENSE	= array();
	public $CON_SNAP_VEH_PLATE_REG_MONTH= array();
	public $CON_SNAP_VEH_PLATE_REG_YEAR	= array();
	public $VIC_CODE					= array();
	public $VIC_DESCRIPTION			= array();
	public $CON_ISSUE_DATE			= array(); // DATE yyyy-mm-dd hh:ii:ss (same date ALWAYS found in as CON_APPLY_FEE_DATE)
	public $TAL_UID_TRANSFER_AGENCY		= array(); // Transfer agency (2001 is Bursar) - note: CON_TRANSFER_DATE is NOT available in API.
	public $VPL_UID_SNAP_VEH_PLATE_TYPE	= array();
	public $VPL_DESCRIPTION			= array();
	public $STL_UID_SNAP_VEH_STATE= array();
	public $STL_DESCRIPTION			= array();
	public $CLM_UID_LOCATION		= array();
	public $CLM_DESCRIPTION			= array();
	public $VIC_UID_VIOLATION_CODE= array();
	public $VIC_LONG_DESCRIPTION	= array();

	/***
	 * $t2_query[Query Name] = Query UID (QUERY_DEF.QDE_NAME => QUERY_DEF.QDE_UID)
	 * For use with API ExecuteQuery class (in Flex_Misc .php)
	 * Query UIDs and Query Names found here: http://128.196.6.197/PowerPark/qm/default.aspx
	 */
	protected static $t2_query = array( //---- LIVE DB
			 'Q_get_con_status'		=>	7469,
			 'Q_Flex_IVR_cb'			=>	7470,
			 'Q_GetEntCitations'		=>	7471,
	  );


	public static function get_appeal_days()
	{ Debug_Trace::traceFunc();
		/*****************
		 * Normally this function returns $appealDays (number of days allowed to appeal a citation).
		*****/
		$num_days = Flex_IVR::$appealDays;

		if ($GLOBALS['DEBUG_DEBUG'])
		{
			$extendCiteAppeal = 190;
			if ($extendCiteAppeal)
			{
				?>
				<div align="center" style="border:1px solid orange; padding:2px; margin-bottom:22px; font-weight:bold; color:red;">
				Debug Mode: Flex_IVR .php -- Extending citation appeal days from <?php echo $num_days;?> to <?php echo $extendCiteAppeal;?> days.
				</div>
				<?php
				$num_days = $extendCiteAppeal;
			}
		}
		return $num_days;
	}


	public static function allow_con_status($isPayment)
	{ Debug_Trace::traceFunc();
		/*******************************
		 * If $isPayment is true then customer is making cite payment (payments.php), else appeal (appeals.php)
		 * Returns array of allowed status numbers (CONTRAVENTION_STATUS~CSL_UID_STATUS)
		 * FLEXADMIN.CON_STATUS_LKP:
			CSL_UID	CSL_DESCRIPTION	CSL_CODE
			1			"Unpaid"				"UNP"
			2			"Zero Balance"		"ZER"
			3			"Appeal No Balance Due"	"ANB"
			4			"Appeal Balance Due"	"ABD"
			5			"Transfer"			"TRA"
			6			"Inactive"			"INA"
		**********************************/
		if ($isPayment) // paying citation
			return array(1,4); // todo: probably make just 1,4
		else // appeal a citation
			return array(1,2);
	}

	// ---------- Two related functions, above and below ------------

	public static function get_con_status()
	{ Debug_Trace::traceFunc();
		/**********************
		 * Simply returns array of all possible Citation status':
		 *		array[CONTRAVENTION_STATUS][attribute]
		 * Status number found in Flex_IVR.php as CONTRAVENTION_STATUS.
		 * FLEXADMIN.CON_STATUS_LKP:
			CSL_UID	CSL_DESCRIPTION	CSL_CODE
			1			"Unpaid"				"UNP"
			2			"Zero Balance"		"ZER"
			3			"Appeal No Balance Due"	"ANB"
			4			"Appeal Balance Due"	"ABD"
			5			"Transfer"			"TRA"
			6			"Inactive"			"INA"
		**********************/

		try {
			$t2Key = 'Q_get_con_status';
			$param_ary = array();
			$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, 'get_con_status');
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			$tmpResults1 = $exeResults->results_custom;

			$tary = array();
			for ($i=0; $i<sizeof($tmpResults1['CSL_UID']); $i++) {
				$CSL_UID = $tmpResults1['CSL_UID'][$i];
				$tary[$CSL_UID]['CSL_UID']				= $tmpResults1['CSL_UID'][$i];
				$tary[$CSL_UID]['CSL_DESCRIPTION']	= $tmpResults1['CSL_DESCRIPTION'][$i];
				$tary[$CSL_UID]['CSL_CODE']			= $tmpResults1['CSL_CODE'][$i];
			}
			if (!sizeof($tary)) {
				$err = 'Error: Could not find Citation Status!';
				$fileErrStr = __FILE__.':'.__LINE__ . "\n" . $err . "\nQUERY in get_con_status function.\n------------\n";
				logError ('flexRelated.txt', $fileErrStr);
				throw new customException($err);

			} else {
				return $tary;
			}

		} catch (customException $e){echo $e->errorMessage(); }
	}


	protected function set_callback()
	{ Debug_Trace::traceFunc();
		/*********************************************
		 * Called from config.php.
		 * Fetches various variables obtained from FLEXADMIN.CONTRAVENTION_VIEW - using the CON_UID's
		 * See also get_con_status() - array of all possible Citation status
		 * Maybe use ADJUDICATION_VIEW or something -- see Flex_IVR callback function.
		***********************************************/

		if (sizeof($this->CON_UID)) {

			$tmp_ary = array();
			foreach ($this->CON_UID as $k => $aCID)
			{
				// Create temp var arrays.
				$t2Key = 'Q_Flex_IVR_cb';
				$param_ary = array($aCID);
				$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, 'Flex_IVR_cb');
				if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
				$tmpResults1 = $exeResults->results_custom;

//if ($GLOBALS['DEBUG_DEBUG']){
//	echo '====================================Q_Flex_IVR_cb , $aCID: '.$aCID.'<br>';
//}
//if ($GLOBALS['DEBUG_DEBUG']){
//	echo '====================================<pre>$exeResults: '.print_r($exeResults,true).'</pre><br>';
//}

				//------------ These vars are declaired above (and are set here).
				$tmp_ary['CITATION_NUMBER'][]				= $tmpResults1['CON_TICKET_ID'][0];
				$tmp_ary['CONTRAVENTION_STATUS'][]		= $tmpResults1['CSL_UID_STATUS'][0]; // get_con_status has ALL con stats.
				$tmp_ary['ENT_UID_RESPONSIBLE'][]		= @$tmpResults1['ENT_UID_RESPONSIBLE_ENT'][0];
				$tmp_ary['VEH_UID_VEHICLE'][]				= $tmpResults1['VEH_UID_VEHICLE'][0];
				$tmp_ary['CON_COMMENT_PUBLIC'][]			= $tmpResults1['CON_COMMENT_PUBLIC'][0];
				// $tmp_ary['CON_LAST_ESCALATION'][]		= $tmpResults1['CON_LAST_ESCALATION'][0];
				$tmp_ary['VIC_BASE_AMOUNT'][]				= $tmpResults1['VIC_BASE_AMOUNT'][0];
				$tmp_ary['CON_IS_UNDER_APPEAL'][]		= $tmpResults1['CON_IS_UNDER_APPEAL'][0];
				$tmp_ary['CON_HAS_NOTE'][]					= $tmpResults1['CON_HAS_NOTE'][0];
				$tmp_ary['VIC_LEGAL_DESCRIPTION'][]		= $tmpResults1['VIC_LEGAL_DESCRIPTION'][0]; // CON_VIOLATION_CODE_MLKP.VIC_LEGAL_DESCRIPTION
				$tmp_ary['TAL_UID_TRANSFER_AGENCY'][]	= $tmpResults1['TAL_UID_TRANSFER_AGENCY'][0];

				//$tmp_ary['CON_HAS_LETTER'][]				= $tmpResults1['CON_HAS_LETTER'][0];
				//$tmp_ary['CON_HAS_PENDING_LETTER'][]	= $tmpResults1['CON_HAS_PENDING_LETTER'][0];
				//$tmp_ary['CON_AMOUNT_DUE'][]				= $tmpResults1['CON_AMOUNT_DUE'][0];
				//$tmp_ary['PRO_UID_PROPERTY'][]			= $tmpResults1['PRO_UID_PROPERTY'][0];
				//$tmp_ary['STF_UID_OFFICER'][]				= $tmpResults1['STF_UID_OFFICER'][0];
				//$tmp_ary['CON_ISSUE_DATE'][]				= $tmpResults1['CON_ISSUE_DATE'][0];
				//$tmp_ary['VIC_UID_VIOLATION_CODE'][]	= $tmpResults1['VIC_UID_VIOLATION_CODE'][0];
				//$tmp_ary['CON_PERMISSION_NUMBER'][]		= $tmpResults1['CON_PERMISSION_NUMBER'][0];
				//$tmp_ary['CLM_UID_LOCATION'][]			= $tmpResults1['CLM_UID_LOCATION'][0];
				//$tmp_ary['CON_BLOCK_NUMBER'][]			= $tmpResults1['CON_BLOCK_NUMBER'][0];
				//$tmp_ary['CON_METER_NUMBER'][]			= $tmpResults1['CON_METER_NUMBER'][0];
				//$tmp_ary['CON_CHALK_DATE'][]				= $tmpResults1['CON_CHALK_DATE'][0];
				//$tmp_ary['CON_COURT_DATE'][]				= $tmpResults1['CON_COURT_DATE'][0];
				//$tmp_ary['CON_COMMENT_PRIVATE'][]		= $tmpResults1['CON_COMMENT_PRIVATE'][0];
				//$tmp_ary['CON_COMMENT_LOCATION'][]		= $tmpResults1['CON_COMMENT_LOCATION'][0];
				//$tmp_ary['CON_IS_SOURCE_MANUAL'][]		= $tmpResults1['CON_IS_SOURCE_MANUAL'][0];
				//$tmp_ary['CON_IS_VOID'][]					= $tmpResults1['CON_IS_VOID'][0];
				//$tmp_ary['CVL_UID_VOID_REASON'][]		= $tmpResults1['CVL_UID_VOID_REASON'][0];
				//$tmp_ary['CON_IS_WARNING'][]				= $tmpResults1['CON_IS_WARNING'][0];
				//$tmp_ary['CON_IS_NO_CONTEST'][]			= $tmpResults1['CON_IS_NO_CONTEST'][0];
				//$tmp_ary['CON_IS_WRITEOFF'][]				= $tmpResults1['CON_IS_WRITEOFF'][0];
				//$tmp_ary['CON_IS_UNCOLLECTABLE'][]		= $tmpResults1['CON_IS_UNCOLLECTABLE'][0];
				//$tmp_ary['CON_TRANSFER_DATE'][]			= $tmpResults1['CON_TRANSFER_DATE'][0];
				//$tmp_ary['CON_PENDING_TRANSFER_DATE'][]= $tmpResults1['CON_PENDING_TRANSFER_DATE'][0];
				//$tmp_ary['CON_REINSTATEMENT_DATE'][]	= $tmpResults1['CON_REINSTATEMENT_DATE'][0];
				//$tmp_ary['CON_MOD_DIGIT'][]				= $tmpResults1['CON_MOD_DIGIT'][0];
				//$tmp_ary['VML_UID_SNAP_VEH_MODEL'][]	= $tmpResults1['VML_UID_SNAP_VEH_MODEL'][0];
				//$tmp_ary['VKL_UID_SNAP_VEH_MAKE'][]		= $tmpResults1['VKL_UID_SNAP_VEH_MAKE'][0];
				//$tmp_ary['VCL_UID_SNAP_VEH_COLOR'][]	= $tmpResults1['VCL_UID_SNAP_VEH_COLOR'][0];
				//$tmp_ary['VSL_UID_SNAP_VEH_TYPE'][]		= $tmpResults1['VSL_UID_SNAP_VEH_TYPE'][0];
				//$tmp_ary['STL_UID_SNAP_VEH_STATE'][]	= $tmpResults1['STL_UID_SNAP_VEH_STATE'][0];
				//$tmp_ary['VPL_UID_SNAP_VEH_PLATE_TYPE'][]		= $tmpResults1['VPL_UID_SNAP_VEH_PLATE_TYPE'][0];
				//$tmp_ary['CON_SNAP_VEH_VIN'][]			= $tmpResults1['CON_SNAP_VEH_VIN'][0];
				//$tmp_ary['CON_SNAP_VEH_PLATE_LICENSE'][]		= $tmpResults1['CON_SNAP_VEH_PLATE_LICENSE'][0];
				//$tmp_ary['CON_SNAP_VEH_PLATE_REG_MONTH'][]	= $tmpResults1['CON_SNAP_VEH_PLATE_REG_MONTH'][0];
				//$tmp_ary['CON_SNAP_VEH_PLATE_REG_YEAR'][]		= $tmpResults1['CON_SNAP_VEH_PLATE_REG_YEAR'][0];
				//$tmp_ary['CON_IS_HISTORICAL'][]			= $tmpResults1['CON_IS_HISTORICAL'][0];
				//$tmp_ary['CON_MODIFY_DATE'][]				= $tmpResults1['CON_MODIFY_DATE'][0];
				//$tmp_ary['CDS_UID_CA_DMV_CURRENT_STATUS'][]	= $tmpResults1['CDS_UID_CA_DMV_CURRENT_STATUS'][0];
				//$tmp_ary['CDS_UID_CA_DMV_PREVIOUS_STATUS'][]	= $tmpResults1['CDS_UID_CA_DMV_PREVIOUS_STATUS'][0];
				//$tmp_ary['CON_IS_ON_ADMIN_HOLD'][]		= $tmpResults1['CON_IS_ON_ADMIN_HOLD'][0];
				//$tmp_ary['CON_IS_PREENTERED'][]			= $tmpResults1['CON_IS_PREENTERED'][0];
				//$tmp_ary['CON_IS_SPECIAL_STATUS'][]		= $tmpResults1['CON_IS_SPECIAL_STATUS'][0];
				//$tmp_ary['CON_OPEN_BLOCK_DOCKET'][]		= $tmpResults1['CON_OPEN_BLOCK_DOCKET'][0];
				//$tmp_ary['GAL_UID_GL_ACCOUNT'][]			= $tmpResults1['GAL_UID_GL_ACCOUNT'][0];
				//$tmp_ary['VFL_UID_FINE_TYPE'][]			= $tmpResults1['VFL_UID_FINE_TYPE'][0];
				//$tmp_ary['VIC_APPLY_TAX_1'][]				= $tmpResults1['VIC_APPLY_TAX_1'][0];
				//$tmp_ary['VIC_APPLY_TAX_2'][]				= $tmpResults1['VIC_APPLY_TAX_2'][0];
				//$tmp_ary['VIC_APPLY_TAX_3'][]				= $tmpResults1['VIC_APPLY_TAX_3'][0];
				//$tmp_ary['VIC_IS_ACTIVE'][]				= $tmpResults1['VIC_IS_ACTIVE'][0];
				//$tmp_ary['VIC_CODE'][]						= $tmpResults1['VIC_CODE'][0];
				//$tmp_ary['VIC_DESCRIPTION'][]				= $tmpResults1['VIC_DESCRIPTION'][0];
				//$tmp_ary['VIC_IS_ACCUM_CODE_BASED'][]	= $tmpResults1['VIC_IS_ACCUM_CODE_BASED'][0];
				//$tmp_ary['VIC_IS_METER_VIOLATION'][]	= $tmpResults1['VIC_IS_METER_VIOLATION'][0];
				//$tmp_ary['VTL_DESCRIPTION'][]				= $tmpResults1['VTL_DESCRIPTION'][0];
				//$tmp_ary['VTL_UID_VIOLATION_TYPE'][]	= $tmpResults1['VTL_UID_VIOLATION_TYPE'][0];
				//$tmp_ary['CZL_UID_ZONE'][]					= $tmpResults1['CZL_UID_ZONE'][0];
				//$tmp_ary['CON_APPLY_FEE_DATE'][]			= $tmpResults1['CON_APPLY_FEE_DATE'][0];
				//$tmp_ary['CON_IS_ESCALATION_CANDIDATE'][]		= $tmpResults1['CON_IS_ESCALATION_CANDIDATE'][0];

			}


			if (!isset($debug_dave_q))
				$debug_dave_q = 'NO CON_UID';

			if (!sizeof($tmp_ary)) {
				$err = 'ERROR: Could not find citation info!!!';
				$fileErrStr = __FILE__.':'.__LINE__ . "\n" . $err . "\nQUERY: " . $debug_dave_q . "\n------------\n" . $this->__toString() . "\n";
				logError ('flexRelated.txt', $fileErrStr);
				throw new customException($err);

			} else {
				// Assign the class array vars to the temporary vars. (Temp vars needed for classes, just don't ask why!)
				// Example: will set $this->CITATION_NUMBER (an array) like so:
				//			$this->CITATION_NUMBER = $tmp_ary['CITATION_NUMBER'];
				foreach ($tmp_ary as $varName => $valAry)
					$this->$varName = $valAry;
			}
		}
		else
		{
			// No Citations Found
		}
	}
}


class GetEntCitations extends Flex_IVR
{
	/************************
	 * get payable citations via ent_uid.
	 *************************/

	//----------------------------------- input
	public $ent_uid_responsible = '';

	//----------------------------------- output


	public $flex_function	= 'GetEntCitations';

	public function __construct($entUid)
	{
		Debug_Trace::traceFunc();

		$this->ent_uid_responsible = $entUid;

		$t2Key = 'Q_GetEntCitations';
		$param_ary = array($this->ent_uid_responsible);
		$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
		$tmpResults1 = $exeResults->results_custom;

		for ($i=0; $i<sizeof($tmpResults1['CON_UID']); $i++)
		{
			$this->CON_UID[$i] = $tmpResults1['CON_UID'][$i];
			$this->CITATION_NUMBER[$i] = $tmpResults1['CON_TICKET_ID'][$i];

			$tmpGet = new GetCitation($tmpResults1['CON_TICKET_ID'][$i]);
			if ($GLOBALS['DEBUG_DEBUG']) echo $tmpGet;

			// all will be same as ent_uid_responsible
			$this->ENT_UID_RESPONSIBLE[$i] = $tmpGet->ENT_UID_RESPONSIBLE[0];

			$this->CON_AMOUNT_DUE[$i] = $tmpGet->CON_AMOUNT_DUE[0];
			$this->CONTRAVENTION_STATUS[$i] = $tmpGet->CONTRAVENTION_STATUS[0];
			$this->VIC_DESCRIPTION[$i] = $tmpGet->VIC_DESCRIPTION[0];
			$this->VIC_LONG_DESCRIPTION[$i] = $tmpGet->VIC_LONG_DESCRIPTION[0];
			$this->CON_ISSUE_DATE[$i] = $tmpGet->CON_ISSUE_DATE[0];
			$this->TAL_UID_TRANSFER_AGENCY[$i] = $tmpGet->TAL_UID_TRANSFER_AGENCY[0];
			$this->CON_SNAP_VEH_PLATE_LICENSE[$i] = $tmpGet->CON_SNAP_VEH_PLATE_LICENSE[0];
			$this->STL_UID_SNAP_VEH_STATE[$i] = $tmpGet->STL_UID_SNAP_VEH_STATE[0];
			$this->VIC_CODE[$i] = $tmpGet->VIC_CODE[0];
			$this->VIC_UID_VIOLATION_CODE[$i] = $tmpGet->VIC_UID_VIOLATION_CODE[0];
			//$this->xxxxx[$i] = $tmpGet->xxxxx[0];
			//$this->xxxxx[$i] = $tmpGet->xxxxx[0];
		}
	}
}





class GetAllCitations extends Flex_IVR
{
	/**********************************
	 * This method retrieves information about multiple citations by license plate. Citations that have a $0.00 amount due will also be returned by this web method.
	 * PLATE_NUMBER and PLATE_STATE_UID required.
	 * If the Plate Number and Plate State do not provide a unique vehicle record, inputting the Plate Type and/or Plate Series is required.
	***********************************/
	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';
	public $return_xml		= ''; // massaged return_page


	//--------------------- Input XML Parameters ----------------------
	// see CONTRAVENTION TABLE
	// The plate number for the vehicle related to the citation.		REQUIRED.
	protected $PLATE_NUMBER			= '';
	// STATE_MLKP.STL_UID -- see wrieFunctions(). The plate state UID for the vehicle related to the citation.	REQUIRED.
	protected $PLATE_STATE_UID		= '';
	// The plate type UID related to the vehicle plate.					NOT REQUIRED.
	protected $PLATE_TYPE_UID		= '';
	// The plate series UID related to the vehicle plate.					NOT REQUIRED.
	protected $PLATE_SERIES_UID	= '';


	public $flex_function	= 'GetAllCitations';
	public function __construct($p_1, $p_2, $p_3='', $p_4='')
	{
		Debug_Trace::traceFunc();
		parent::__construct();
		$this->PLATE_NUMBER			= $p_1;
		$this->PLATE_STATE_UID		= $p_2;
		$this->PLATE_TYPE_UID		= $p_3;
		$this->PLATE_SERIES_UID		= $p_4;
		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('PLATE_NUMBER',		$this->PLATE_NUMBER);
		$this->xml_data .= make_param('PLATE_STATE_UID',	$this->PLATE_STATE_UID);
		$this->xml_data .= make_param('PLATE_TYPE_UID',		$this->PLATE_TYPE_UID);
		$this->xml_data .= make_param('PLATE_SERIES_UID',	$this->PLATE_SERIES_UID);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

}



class GetCitation extends Flex_IVR
{
	public $flex_function	= 'GetCitation';
	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';
	public $return_xml		= ''; // massaged return_page

	//------- Input:  Citation number issued to the parker

	//-------- XML Output [ARRAYS]:
	//				See parent output. [ARRAYS]

	public function __construct($p_1)
	{
		/*********
		*/
		Debug_Trace::traceFunc();
		parent::__construct();
		$this->CITATION_NUMBER = $p_1;
		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('CITATION_NUMBER', $this->CITATION_NUMBER);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

}



class GetViolationCodes extends Flex_IVR
{
	/**********************************
	***********************************/
	public $flex_function	= 'GetViolationCodes';
	public function __construct($default='')
	{
		/***** to be continued...
		$dbC = new database();
		$tmp_ary = array();
		foreach ($this->CON_UID as $k => $aCID) {
			// Create temp var arrays.
			$searchQuery = "select * from FLEXADMIN.CON_VIOLATION_CODE_MLKP where 1 = 1";
			$dbC->sQuery($searchQuery);
			$tmpResults1 = $dbC->results;
			//------------ These vars are declaired above (and are set here).
			$tmp_ary['CITATION_NUMBER'][]				= $tmpResults1['CON_TICKET_ID'][0];
			$tmp_ary['CONTRAVENTION_STATUS'][]		= $tmpResults1['CSL_UID_STATUS'][0]; // get_con_status has ALL con stats.
			$tmp_ary['ENT_UID_RESPONSIBLE'][]		= $tmpResults1['ENT_UID_RESPONSIBLE_ENT'][0];
			$tmp_ary['VEH_UID_VEHICLE'][]				= $tmpResults1['VEH_UID_VEHICLE'][0];
			$tmp_ary['CON_COMMENT_PUBLIC'][]			= $tmpResults1['CON_COMMENT_PUBLIC'][0];
			$tmp_ary['CON_LAST_ESCALATION'][]		= $tmpResults1['CON_LAST_ESCALATION'][0];
			$tmp_ary['VIC_BASE_AMOUNT'][]				= $tmpResults1['VIC_BASE_AMOUNT'][0];
			$tmp_ary['CON_IS_UNDER_APPEAL'][]		= $tmpResults1['CON_IS_UNDER_APPEAL'][0];
			$tmp_ary['CON_HAS_NOTE'][]					= $tmpResults1['CON_HAS_NOTE'][0];
			$tmp_ary['VIC_LEGAL_DESCRIPTION'][]		= $tmpResults1['VIC_LEGAL_DESCRIPTION'][0]; // CON_VIOLATION_CODE_MLKP.VIC_LEGAL_DESCRIPTION
		}
		if (!sizeof($tmp_ary)) {
			$err = 'ERROR: Could not find any codes.';
			$fileErrStr = __FILE__.':'.__LINE__ . "\n" . $err . "\nQUERY: \n------------\n" . $this->__toString() . "\n";
			logError ('flexRelated.txt', $fileErrStr);
			throw new customException($err);
		} else {
			// Assign the class array vars to the temporary vars. (Temp vars needed for classes, just don't ask why!)
			// Example: will set $this->CITATION_NUMBER (an array) like so:
			//			$this->CITATION_NUMBER = $tmp_ary['CITATION_NUMBER'];
			foreach ($tmp_ary as $varName => $valAry)
				$this->$varName = $valAry;
		}
		******/
	}
}

?>