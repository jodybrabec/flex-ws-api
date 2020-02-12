<?php


require_once '/var/www2/include/flex_ws/config.php';


abstract class Flex_MiscSaleItems extends Flex_Funcs
{
	// Documentation at PAGE=42: http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf

	public $flex_group = 'T2_Flex_MiscSaleItems';

	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';


	protected function set_callback()
	{
		//Called from config.php  - usually to set more vars besides those set by Flex_Funcs::setVars
		if (!$this->RECEIPT_UID)
		{
			if ($GLOBALS['DEBUG_DEBUG'])
				echo '<div style="font-weight:bold; color:red; padding:4px; border:1px solid orange; font-size:14px">Error: PayMiscSaleItems did not return a RECEIPT</div>';
		}
	}
}



class PayMiscSaleItems extends Flex_MiscSaleItems
{
	/******
	 * Used for BPP and payroll.
	*******/

	public $xml_request		= 'SOAP 1.1'; // Default in config .php is HTTP POST

	//----- Input params
	// Contains a MiscellaneousItemListParam complex type parameter set for each Misc item to be paid (see next item). Yes (one for each misc. item)
	const BPP_fee								= '20';
	public $MiscellaneousItems				= ''; // MISC_ITEM_VIEW.MIS_UID???
	public $EntityUID							= '';
	public $AmountPaid						= ''; // The amount paid for the misc. item (allows two decimals).	REQUIRED.
	public $PaymentMethodLookupUID		= ''; //	2000 default PAYMENT_METHOD_MLKP.PMM_UID.  REQUIRED.   2000; // cyberToPmmUid($cardType);
	public $ReferenceNote					= ''; // A reference note for the payment.
	public $CashDrawerUID					= '';
	public $TransferAgencyLookupUID		= 2008; // Required (because External Pay Plan).  TRANSFER_AGENCY_MLKP.TAL_UID: 2008=Bursars Payment Plan, 2001=Bursar
	public $PayPlanTaxStatusLookupUID   = 1;    // Required (because we use "External Payment Plan" AND required by transfer agent). 1-No Tax, 2-Pre-Tax, 3-Post-Tax
	public $PaymentScheduleTemplateLookupUID	= ''; // PAYMENT_SCHEDULE_TEMP_MLKP.PSE_UID: 2001=Bursar, 2000=Payroll
	//PAYMENT_TYPE_UID = 14; // BPP?

	//------ Output:
	public $RECEIPT_UID						= '';
	//public $PayMiscSaleItemsResult		= ''; // Indicates in the results string whether or not the item was sold by including the MISCSALEITEM_LIST (with respective UIDs) and the RECEIPTUID.

	public $flex_function	= 'PayMiscSaleItems';

	public function __construct($entUID='', $refNote='', $amt=0, $payTemplate='', $pmm='2000', $misc='8321') // was 2772
	{
		parent::__construct();
		$this->MiscellaneousItems		= $misc; // 8038 - testing sale item
		$this->AmountPaid					= $amt;
		$this->EntityUID					= $entUID;
		$this->PaymentMethodLookupUID	= $pmm;
		$this->ReferenceNote				= $refNote;
		//	$this->PaymentScheduleTemplateLookupUID = $payTemplate; // 2001-Bursar, 2000-Payroll

		//	if ($_SERVER['REMOTE_ADDR']=='128.196.6.35') {
		//	// see also permit_purchase .php
		//		$this->MiscellaneousItems		= $misc; // 8038 - testing sale item
		//		echo '<div style="border:3px solid red; padding:2px;">testing bppppppp and MiscellaneousItems</div>';
		//	}

		$this->send_xml();
	}


	protected function get_xml()
	{
		if ($this->xml_request == 'SOAP 1.1')
		{
			$this->xml_data =
			'<' . $this->flex_function . ' xmlns="http://www.t2systems.com/">
				<loginInfo>
				  <UserName>'	. $this->WS_CONN['WS_user']	. '</UserName>
				  <Password>'	. $this->WS_CONN['WS_pass']	. '</Password>
				  <Version>'	. $this->xml_version				. '</Version>
				</loginInfo>
			<input>
			  <MiscellaneousItems>
				 <MiscellaneousItemListParam>
					<MiscellaneousItemUID>'.$this->MiscellaneousItems.'</MiscellaneousItemUID>
				 </MiscellaneousItemListParam>
			  </MiscellaneousItems>
			  <EntityUID>'.$this->EntityUID.'</EntityUID>
			  <PaymentMethodLookupUID>'.$this->PaymentMethodLookupUID.'</PaymentMethodLookupUID>
			  <AmountPaid>'.$this->AmountPaid.'</AmountPaid>
			  <CashDrawerUID>'.$this->WS_CONN['WS_cashUID'].'</CashDrawerUID>
			  <ReferenceNote>'.$this->ReferenceNote.'</ReferenceNote>
			  <TransferAgencyLookupUID>'.$this->TransferAgencyLookupUID.'</TransferAgencyLookupUID>
			  <PayPlanTaxStatusLookupUID>'.$this->PayPlanTaxStatusLookupUID.'</PayPlanTaxStatusLookupUID>
			</input>
			</' . $this->flex_function . '>';

			//  <PaymentScheduleTemplateLookupUID>'.$this->PaymentScheduleTemplateLookupUID.'</PaymentScheduleTemplateLookupUID>

			$this->post_data = $this->createPost($this->xml_data);

		}
		else
		{
			$this->xml_data = '';
			$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
			$this->xml_data .= "
			<MiscellaneousItems>
			 <MiscellaneousItemListParam>
				<MiscellaneousItemUID>".$this->MiscellaneousItems."</MiscellaneousItemUID>
			 </MiscellaneousItemListParam>
		  </MiscellaneousItems>";
			$this->xml_data .= make_param('EntityUID',$this->EntityUID);
			$this->xml_data .= make_param('PaymentMethodLookupUID',$this->PaymentMethodLookupUID);
			$this->xml_data .= make_param('AmountPaid',$this->AmountPaid);
			$this->xml_data .= make_param('CashDrawerUID',	$this->WS_CONN['WS_cashUID']);
			$this->xml_data .= make_param('ReferenceNote',$this->ReferenceNote);
			$this->xml_data .= make_param('TransferAgencyLookupUID',$this->TransferAgencyLookupUID);
			$this->xml_data .= make_param('PayPlanTaxStatusLookupUID',$this->PayPlanTaxStatusLookupUID);
			// $this->xml_data .= make_param('PaymentScheduleTemplateLookupUID',$this->PaymentScheduleTemplateLookupUID);
			$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

			$this->post_data = $this->createPost($this->xml_data);

		}
		return $this->post_data;
	}
}

?>
