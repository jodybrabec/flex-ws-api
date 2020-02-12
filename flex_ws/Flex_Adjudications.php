<?php

require_once '/var/www2/include/flex_ws/config.php';

abstract class Flex_Adjudications extends Flex_Funcs
{
	/*****************************
	 * If citation is NOT linked to individual (See "MAN MUCUS" in PP-test9):
	 *		Create account during appeal - doesn't matter if they are logged in or not.
	 *		Assign the appealer's account to the Hearing.
	 *		Put appealer's info in Note field of Hearing:
	 *			Submitted by: Mucus Man
	 *			Email response to: jcorso5@cox.net
	 *			Mucus Man need no stincking reason
	 *		DO NOT make appealer Responsible for this Parking Citation.
	 *
	 * If citation IS linked to individual, in other words there IS a Responsible Person for this Parking Citation (see cite 113001657 in PP-test9):
	 *		Do NOT create account - doesn't matter if they are alogged in or not.
	 *		Do NOT assign the appealer's account to the Hearing...
	 *		But instead assign the citation's Responsible Person to the Hearing.
	 *		Put appealer's info in Note field of Hearing (same as above).
	***************************/
	// Documentation: http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf
	// ############# OLD Documentation too: http://www.pts.arizona.edu/T2_Flex_Web_Services_7_1_Reference.pdf

	public $flex_group	= 'T2_FLEX_ADJUDICATIONS';

	/***
	 * $t2_query[Query Name] = Query UID (QUERY_DEF.QDE_NAME => QUERY_DEF.QDE_UID)
	 * For use with API ExecuteQuery class (in Flex_Misc .php)
	 * Query UIDs and Query Names found here: http://128.196.6.197/PowerPark/qm/default.aspx
	 */
	protected static $t2_query = array( //---- LIVE DB
			 'Q_get_adj_uid'		=>	7472,
	  );

	protected function set_callback()
	{
		//Called from config.php  - usually to set more vars besides those set by Flex_Funcs::setVars
	}
}



class get_adj_uid extends Flex_Adjudications
{
	/**********************************
	 * Sets existing_ADJ_UID (ADJ_UID_ADJUDICATION) if there are any existing appeals for citation (CON_UID).
	 * Customers were able to make multiple web appeals using class InsertAdjudication.
	***********************************/

	public $existing_ADJ_UID	= '';

	public function __construct($aConUid)
	{
		$t2Key = 'Q_get_adj_uid';
		$param_ary = array($aConUid);
		$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
		$tmpResults1 = $exeResults->results_custom;

		//------------- OLD SCHOOL
		//	$searchQuery = "select ADJ_UID_ADJUDICATION from FLEXADMIN.CON_ADJ_REL where CON_UID_CONTRAVENTION = :aConUid";

		if (@sizeof($tmpResults1['ADJ_UID_ADJUDICATION']))
			$this->existing_ADJ_UID = $tmpResults1['ADJ_UID_ADJUDICATION'][0];
	}
}


class InsertAdjudication extends Flex_Adjudications
{
	/**********************************
	 * (An older method) ############# OLD Documentation: http://www.pts.arizona.edu/T2_Flex_Web_Services_7_1_Reference.pdf
	 * This method is used to insert a new adjudication record (i.e., a citation appeal).
	 * Only one value may be passed in the CONTRAVENTION_NUMBER
	 * To insert an entity’s reason for appealing the contravention, use the InsertUpdateNotes method in Flex_Misc.php
	 * of the T2_FLEX_MISC Web service, passing to that method the Adjudication_Uid returned by this method.
	 * If an adjudication Note code UID is passed, the UID will be verified and the Note code UID will be added to the adjudication. If this
	 * parameter is not passed, then an adjudication Note code UID of 0 will be used.
	***********************************/

	public $flex_function	= 'InsertAdjudication';
	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';
	public $return_xml		= ''; // massaged return_page

	//--------------------- Input XML Parameters ----------------------
	protected $CONTRAVENTION_NUMBER	= '';	// NOT REQUIRED.  The citation number (UID) being appealed. (CONTRAVENTION.CON_TICKET_ID).
	protected $ADJUDICATION_TYPE		= 2001;// REQUIRED. Adjudication type code. FLEXADMIN.ADJ_TYPE_MLKP.ATM_UID (2001 is Web Appeal)
	protected $ADJUDICATION_DATE		= '';	// REQUIRED.  Adjudication date. This is the date the appeal of a citation is requested. Format: MM/DD/YYYY
	// protected $ALTERNATE_ID			= ''; // Alternate ID. This is a field for passing an appeal number to identify the adjudication, other than the number automatically assigned by Flex.
	// protected $HEARING_REQUIRED	= 0;  // Adjudication hearing required. 1 = Yes, 0 = No (default)
	protected $ENT_UID					= ''; // REQUIRED. Entity UID or Flex ID (ENTITY.ENT_UID).
	// protected $ADJUDICATION_NOTE_CODE_UID	= ''; // Adjudication Note code UID (NOTE_CODE_LKP.NCL_UID)
	protected $A_CUST_FIELD_NAME		= ''; // Custom Field Name (Ex: Cust_1)

	//-------------------- Natural XML Output
	public $Adjudication_Uid			= ''; // UID of adjudication that was successfully inserted.

	public function __construct($p_1, $p_2, $p_3='')
	{
		parent::__construct();
		$this->CONTRAVENTION_NUMBER	= $p_1;
		$this->ENT_UID						= $p_2;
		if ($p_3)
			$this->ADJUDICATION_TYPE	= $p_3; // class-defaulted above.
		$this->ADJUDICATION_DATE		= date('m/d/Y');


		$aCiteAry = new GetCitation($this->CONTRAVENTION_NUMBER);

		$existing_adj = new get_adj_uid($aCiteAry->CON_UID[0]);

		if ($existing_adj->existing_ADJ_UID) {
			echo '<h3 align="center">Error: Citation number ' . $this->CONTRAVENTION_NUMBER . ' already in Level 1 appeal status. </h3>';
		} else {
			$this->send_xml();
		}
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('CONTRAVENTION_NUMBER',	$this->CONTRAVENTION_NUMBER);
		$this->xml_data .= make_param('ADJUDICATION_TYPE',		$this->ADJUDICATION_TYPE);
		$this->xml_data .= make_param('ADJUDICATION_DATE',		$this->ADJUDICATION_DATE);
		$this->xml_data .= make_param('ENTITY_UID',				$this->ENT_UID);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

	protected function get_xml_xx()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('CONTRAVENTION_NUMBER',	$this->CONTRAVENTION_NUMBER);
		$this->xml_data .= make_param('ADJUDICATION_TYPE',		$this->ADJUDICATION_TYPE);
		$this->xml_data .= make_param('ADJUDICATION_DATE',		$this->ADJUDICATION_DATE);
		$this->xml_data .= make_param('ENTITY_UID',				$this->ENT_UID);

		$this->xml_data .= make_param('PLATE_STATE_UID',	$this->PLATE_STATE_UID);
		$this->xml_data .= make_param('PLATE_TYPE_UID',		$this->PLATE_TYPE_UID);
		$this->xml_data .= make_param('PLATE_SERIES_UID',	$this->PLATE_SERIES_UID);

		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

}

/*xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
class InsertAdjudication2 extends Flex_Adjudications
{
	// **********************************
	// This method to appeal a citation.
	// ***********************************
	public $flex_function	= 'InsertAdjudication2';
	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';
	public $return_xml		= ''; // massaged return_page

	//--------------------- Input XML Parameters ----------------------
	protected $ContraventionUid		= '';	// REQUIRED.  The citation UID being appealed.
	protected $BootTowUid				= '';	// Required if a boot/tow applied
	protected $AdjudicationTypeUid	= '';	// Required. The appeal type UID. FLEXADMIN-ADJ_TYPE_MLKP-ATM_UID (2003 is Web Level 1)
	protected $HoldEscalationTypeUid	= '';	// REQUIRED.  How to handle escalations while on appeal.  FLEXADMIN-REINST_FEE_HANDLING_LKP-RFH_UID (1 is Resume)
	protected $AddressUid				= ''; // The address UID where correspondence for the appeal should be sent.
	protected $EmailUid					= ''; // The email address UID where correspondence for the appeal should be sent.
	protected $NoteCodeUid				= ''; // The appeal note code to apply when inserting the appeal.
	protected $AdjudicationDate		= ''; // Date of the appeal in the format YYYY-MM-DD.
	protected $UsePDF						= ''; // Use 1/0 values to indicate yes or no for the email format as PDF or standard HTML.
	protected $ReinstateDaysAfter		= ''; // The number of days after appeal is resolved to reinstate the item(s).
	protected $NumberPrefix				= ''; // A text string for the appeal number prefix.
	protected $AlternateId				= ''; // A text string for any alternate Id for the appeal.
	protected $ParameterCustomFieldItems = ''; // Contains a CustomFieldItem complex type* parameter set for each custom field on the appeal (see next item).
	protected $CustomFieldItem			= ''; // Complex type* collection of the following parameters used to indicate any custom fields on the appeal:  FieldValue (string)   CustomFieldItem (string)

	// Natural XML Output:
	public $InsertAdjudication2Result	= ''; // Returns the inserted appeal‘s UID and workflow step in a string to indicate it was successfully inserted.

xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx*/
?>