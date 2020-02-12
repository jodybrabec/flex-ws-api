<?php

require_once '/var/www2/include/flex_ws/config.php';


abstract class Flex_Contraventions extends Flex_Funcs
{
	// Documentation at PAGE=21: http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=21

	public $flex_group	= 'T2_FLEX_CONTRAVENTIONS';
	public $xml_version	= '1.2';

	protected function set_callback()
	{
		//Called from config.php  - usually to set more vars besides those set by Flex_Funcs::setVars
	}
}



class PayContraventions extends Flex_Contraventions
{
	/**********************************
	 * Pay one or more citations.
	***********************************/

	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';
	public $return_xml		= ''; // massaged return_page

	//--------------------- Input XML Parameters ----------------------
	protected $con_ticket_ids			= ''; // A comma separated list of citation numbers - each is a CONTRAVENTION.CON_TICKET_ID.	REQUIRED.
	protected $AMOUNT_PAID				= ''; // Total amount paid, including tax - should match total of $con_ticket_ids amounts.	REQUIRED.
	protected $PAYMENT_TYPE				= ''; //	PAYMENT_METHOD_MLKP.PMM_UID.  REQUIRED.   2000; // cyberToPmmUid($cardType);
	protected $REFERENCE_NOTE 			= ''; // REQUIRED.
	protected $CUSTOM_FIELD_NAME		= ''; // Custom Field Name (Ex: Cust_1)
	protected $PAYPLAN_TRANSFER_AGENT_UID	= '';
	protected $PAYPLAN_TEMPLATE_UID	= '';
	protected $PAYPLAN_START_DATE		= '';
	protected $PAYPLAN_TIME_PERIOD_UID		= '';
	protected $PAYPLAN_TAX_TYPE 		= '';
	protected $PAYPLAN_NUM_PAYMENTS 	= '';
	protected $PAYPLAN_AMOUNT_PER_PAYMENT	= '';
	protected $ENTITY_UID				= ''; // Entity UID or Flex ID (ENTITY.ENT_UID) if the citations are paid by a payment plan or UID of the customer associated with the Account credit.
	protected $ACCOUNT_CREDIT_LIST 	= '';

	//----------------- Natural XML Output
	public $CONTRAVENTION_LIST	= ''; // Should be exact same as $con_ticket_ids
	public $RECEIPT_UID			= '';
	public $CONTRAVENTION_LIST_PARTIAL_PAYMENT	= '';
	public $CONTRAVENTION_LIST_PARTIAL_PAYMENT_AMOUNT	= '';
	public $CONTRAVENTION_LIST_NO_PAYMENT			= '';


	public $flex_function	= 'PayContraventions';

	public function __construct($citeList, $totals, $refNote='', $paymentType=2000)
	{
		parent::__construct();
		$this->con_ticket_ids		= $citeList;
		$this->AMOUNT_PAID			= $totals;
		$this->PAYMENT_TYPE			= $paymentType;
		if (strlen($refNote)>31) // 32 max, but make 31 just in case.
			$refNote = substr($refNote, 0, 29) . '...';
		$this->REFERENCE_NOTE		= $refNote;
		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('CONTRAVENTION_LIST',$this->con_ticket_ids);
		$this->xml_data .= make_param('AMOUNT_PAID',			$this->AMOUNT_PAID);
		$this->xml_data .= make_param('PAYMENT_TYPE',		$this->PAYMENT_TYPE);
		$this->xml_data .= make_param('REFERENCE_NOTE',		$this->REFERENCE_NOTE);
		$this->xml_data .= make_param('CASH_DRAWER_UID',	$this->WS_CONN['WS_cashUID']);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

}
?>