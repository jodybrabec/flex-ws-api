<?php
require_once '/var/www2/include/flex_ws/config.php';

abstract class Flex_Permissions extends Flex_Funcs
{ // Documentation at PAGE=49: http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=49

	// For BPP - so we don't get red stop-sign under the permit.
	// const paymentPlanStart		= '08/09/2013'; // friday
	// const paymentPlanStart		= '08/15/2014'; // friday
	// const paymentPlanStart		= '08/14/2015'; // friday
	// const paymentPlanStart		= '08/12/2016'; // friday
	const paymentPlanStart			= '12/30/2015'; // friday


	////=== Custom control group dad_uid's (DATA_DICTIONARY table) - copy these vars in: Flex_Permissions .php and Flex_waitlists .php
	//// Two checkboxes within T2's "Insert/Edit Permit Control Group" area.
	//// "SHOW UofA Web Sales Internal (IT Only)" - allows CR folks able to set checkbox dad_web_sellable_ -  http://www.pts.arizona.edu/park/
	//// DAD_UID = 200040: ("PEC".UAPTSSHOWWEBSALES) - SHOW UofA Web Sales Internal (IT Only)
	// old school: const dad_web_showable_true		= "web_showable.DAD_UID = 200040 and web_showable.CUD_VALUE = '1'";
	//// "ALLOW UofA Web Sales External" - allow to sell on web.  The "_unknown" query means we want to get all permits where web_sellable.CUD_VALUE is 1 or 0.
	//// DAD_UID = 200041: ("PEC".UAPTSALLOWWEBSALES) - ALLOW UofA Web Sales External (IT Only)
	// old school: const dad_web_sellable_true		= "web_sellable.DAD_UID = 200041 and web_sellable.CUD_VALUE = '1'";
	// old school: const dad_web_sellable_unknown	= "web_sellable.DAD_UID = 200041";

	// DAD_UID = 200039: ("PEC".UAPTSWAITLIST) - Allow Waitlist Price to Show on Web (IT Only)
	// OLD SCHOOL:
	// const dad_uid_waitlist		= "CUSTOM_DATA.DAD_UID = 200039"; // sql query - "Allow Waitlist Price to Show on Web"
	// const dad_uid_pick_up_per	= 200026;
	// const dad_uid_catcard		= 200047; // catcard id (isonumber) in CUSTOM_DATA.CUD_VALUE

	// OLD way to be replaced by $t2_query below.
	// dad_uid_sungo vars also used in account/permit_functions .php
	const dad_uid_sungo_ent		= 200050; // SunTran SunGO (Upass) Pass id (for an entity who purchased permit)
	const dad_uid_sungo			= 200049; // SunTran SunGO Pass id (for a purchased permit)

	/***
	 * $t2_query[Query Name] = Query UID (QUERY_DEF.QDE_NAME => QUERY_DEF.QDE_UID)
	 * For use with API ExecuteQuery (in Flex_Misc .php)
	 * Query UIDs and Query Names found here: http://128.196.6.197/PowerPark/qm/default.aspx
	 */
	protected static $t2_query = array(
		'Q_GetPermissionPriceFixed_3'		=> 10509,
		'Q_GetPermissionPriceFixed_2'		=> 10473,
		'Q_GetPermissionPriceFixed'		=> 7326,
		'Q_GetPermissionPriceFixed_bus'	=> 7317,
		'Q_GetPermissions_5'					=> 9662,
		'Q_GetPermissions'					=> 7335,
		'Q_getCustomData'						=> 7670,
		'Q_GetAvailableIPermit'				=> 7342, // old one
		'Q_GetAvailableIPermit_2'			=> 8881,
		'Q_GetAvailableBicycle'				=> 7337,
		'Q_GetAvailableBus'					=> 7338,
		'Q_GetAvailableMotorcycle'			=> 8198,
		'Q_getFacilityType'					=> 7341,
		'Q_PerNumToPerUID'					=> 7343,
		'Q_PerNumToPecUID'					=> 9200,
		// 'Q_GetRFID'							=> 7344,
		'Q_GetRFID_IPermit'					=> 8458,
		'Q_GetSuntran'							=> 8399,
		'Q_getGarages'							=> 7468,
		'Q_getPerInfoFromPec'				=> 8686,
	);

	/***
	 * $cust_fld_svc.  DATA_DICTIONARY.DAD_FIELD => CONFIG_TABLE.TAB_NAME  (points to DATA_DICTIONARY.TAB_UID)
	 * For use in InsertUpdateCustomFlds (in config .php)
	 */
	protected static $cust_fld_svc = array(
		'SUNGOE'							=> 'ENTITY',		// (dad_uid=200050)  "E" is for Entity, say it with me!
		'SUNGO'								=> 'PERMISSION',	// (dad_uid=200049)  For Permit   ### USED TO SAY "SUNGOP" - HOPOE IT WORKS!!!!!! #####

		'SUNGOEC'							=> 'ENTITY',		// (dad_uid=205058)  "C" U-Cell phone
		'SUNGOPC'							=> 'PERMISSION',	// (dad_uid=205059)	"C" U-Cell phone, "P" Permit.

		'ENT_PICK_UP_PERMIT'			=> 'ENTITY',		// (dad_uid=200026)
		'SWIPE_CARD'						=> 'PERMISSION',	// (dad_uid=200047)
		'RFID_NUMBER'						=> 'PERMISSION',	// (dad_uid=200033)
	);

	public $flex_group						= 'T2_Flex_Permissions';
	public $PERMISSION_EFFECTIVE_DATE	= '';
	public $PERMISSION_EXPIRATION_DATE	= '';

	public $PERMISSION_UID					= '';
	public $PERMISSION_NUM_RANGE_UID		= ''; // (PNA_UID)
	public $CONTROL_GROUP_UID				= ''; // (PEC_UID)
	public $FAC_UID_FACILITY				= '';

	public $ENT_UID							= ''; // (ENTITY_UID)
	public $CASH_DRAWER_UID					= '';

	public $xml_data							= '';
	public $post_data							= '';
	public $return_page						= '';

	// public $sqlHist						= array();


	protected function set_callback()
	{
		//Called from config.php  - usually to set more vars besides those set by Flex_Funcs::setVars
		if (@$this->temp_return_vals['PERMISSION_UID'][0])
			$this->is_authorized = true;
		else
			$this->is_authorized = false;
		//PERMISSION_NUMBER = @$this->temp_return_vals['PERMISSION_UID'][0];
	}


	static function getFacilityType($fac_UID, $fac_desc)
	{
		/***
		 * Returns "garage" or "other" (other is assumed to be a parking "lot" for now)
		 * $facility_type = "garage": If FLEXADMIN.FACILITY.FAC_IS_ACCESS_CONTROLLED==1 , else "other".
		 * Used in cyberpay_sa .php when somebody purchases permit; used in select_permits .php for Payroll logging Catcard number.
		 */

		$facility_type = 'other'; // default.

		// In the T2 query this will be: FAC_UID = $fac_UID
		$param_ary	= array('Facility UID' => $fac_UID);
		$t2Key		= 'Q_getFacilityType';
		$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
		if ($GLOBALS['database_test_db']) {
			// Since in test DB, FAC_IS_ACCESS_CONTROLLED may not be "1" even if garage, so search "garage" or "Historical Society" in facility description.
			$facility_type	= preg_match('/.*(Garage|Historical Society)$/si', $exeResults->results_custom['FAC_DESCRIPTION'][0]) ? 'garage' : 'other';
		} else {
			$facility_type	= $exeResults->results_custom['FAC_IS_ACCESS_CONTROLLED'][0] ? 'garage' : 'other';
		}
		return $facility_type;
	}


	static function getGarages()
	{
		/***
		 * Returns all garages in array: $garages[Fac_Uid] = 'garage name'
		 */
		$garages = array();
		$param_ary	= array();
		$t2Key		= 'Q_getGarages';
		$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, $t2Key);
		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
		foreach ($exeResults->results_custom['FAC_UID'] as $k => $aFacUid) {
			$garages[$aFacUid] = $exeResults->results_custom['FAC_DESCRIPTION'][$k];
		}
		return $garages;
	}


	public function getCustomData($dad_uid, $cud_record_uid)
	{
		/***
		 * get data from FLEXADMIN.CUSTOM_DATA table via dad_uid and cud_uid
		 *	Returns CUD_VALUE
		 * Example:
		 *		select CUD_VALUE from FLEXADMIN.CUSTOM_DATA CD
		 *		where CD.DAD_UID = {a $dad_uid number from DATA_DICTIONARY table}
		 *		and CD.CUD_RECORD_UID = {a $cud_record_uid number from PERMISSION_VIEW.PEC_UID_PERM_CONTROL_GROUP}
		 */

		$t2Key		= 'Q_getCustomData';
		$param_ary	= array($dad_uid, $cud_record_uid);
		$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, 'getCustomData');
		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
		$tmpResults1 = $exeResults->results_custom;
		$cud_value = @$exeResults->results_custom['CUD_VALUE'][0];

		return $cud_value;
	}



	static function makeTempPermit($permit_number, $per_uid, $desc, $ent_uid, $per_sold_date = '', $plateStr = '') // SOLD DATE; YYYY-MM-DD
	{
		/***
		 * Create PDF temporary permits for lots ONLY
		 *	In cyberpay .php, $per_sold_date will be empty (defaults to current date below).
		 * Dont forget to call this function using  checkEnabled('Temporary Permits')
		 * Returns url link to temp permit.

		 * RULES:
				Anything sold 8/1 thru 8/20  – temps expire on 9/2 at 11:59:59 pm
				Permits sold up until 8/23 will have a 9/3 expiration; up until Expire Date is 14 days AFTER PER_SOLD_DATE
				Anything sold 8/21 thru x/xx  – temps expire two weeks from sold date.
				time-limit the PDF url to print.
				FEATURES: Watermark, Flex ID, Facility, Permit Number, License Plate,
		 *
		 *

			The [generated] temp permit URL will be recorded in the Notes field of the permit, within in T2.

			Example of a temp permit for printing:
			https://parking.arizona.edu/account/tempPermits/4355992_238247.pdf

			FORMAT OF THE TEMP PERMIT:
			The PDF temp permit has watermark to discourage people from trying to change the text or use a copy machine.
			In the example pdf, you see that the permit Expires 9/02. The temp permit number (14Z170918302238247) is packed with secrets:
		 * The first five characters contains part of the permit of the permit number "14Z17"
		 * the sixth and seventh characters is the expiration month "09";
		 * followed by the rest of the permit number "183"
		 * followed by expiration day "02"
		 * followed by T2 Customer ID "238247"

			CUSTOMER RELATIONS CAN GENERATE TEMP PERMITS (let me know if you want a link on Internal CR home page):
			https://www.pts.arizona.edu/customer_relations/print_permit.php

			CUSTOMERS EXPERIENCE:
			Here is what a customer will see on the web-page after they purchase their permit online (including $30 non-refundable Permit Transfer Agreement)
			 Please print or save the following temporary permit:
			 https://parking.arizona.edu/account/tempPermits/4355992_238247.pdf
		 */

		$err = '';

		// print_permit .php uses this same $pdfDir, $pdfFileName and $pdf_URL, to look for existing temp permits.
		$pdfDir = '/var/www2/html/account/tempPermits/';
		if ($per_uid == '0000000') // payroll.
			$pdfFileName = date('Y-m') . '_' . $ent_uid . '.pdf';
		else
			$pdfFileName = $per_uid . '_' . $ent_uid . '.pdf';
		$pdf_URL = 'https://parking.arizona.edu/account/tempPermits/' . $pdfFileName;

		// Convert YYYY-MM-DD to timestamp (adding 23:59:59)
		$per_sold_date = $per_sold_date ? $per_sold_date : date('Y-m-d'); // YYYY-MM-DD

		// if ($GLOBALS['jody']){
		//$per_sold_date = date('Y-m-d',strtotime('2016-08-01'));
		//	echo '<pre>New $per_sold_date: (test) '.date('Y-m-d',$per_sold_date).'<br>';
		// echo date('Y-m-d',$expire_date)." ........... $sold_year ............. ".date('Y-m-d',strtotime($sold_year.'-08-31 23:59:59'))."<Br>";
		//var_dump($GLOBALS);
		// }

		$sold_date = strtotime($per_sold_date . ' 23:59:59');
		$sold_year = date('Y', $sold_date);

		//	if ($sold_date >= strtotime($sold_year.'-08-01 00:00:00') && $sold_date <= strtotime($sold_year.'-08-20 23:59:59'))
		//		$expire_date = strtotime($sold_year.'-09-01 23:59:59'); // Best if this is Monday
		//	else	$expire_date = strtotime('+14 days', $sold_date); // print_permit .php also has this 14 day thing.

		if ($sold_date <= strtotime($sold_year . '-08-05 23:59:59')) {
			$expire_date = strtotime($sold_year . '-08-26 23:59:59'); // Best if this is Monday
		} else
		if ($sold_date >= strtotime($sold_year . '-08-06 00:00:00') && $sold_date <= strtotime($sold_year . '-08-14 23:59:59')) {
			$expire_date = strtotime($sold_year . '-08-31 23:59:59'); // Best if this is Monday
		} else
		if ($sold_date >= strtotime($sold_year . '-08-15 00:00:00') && $sold_date <= strtotime($sold_year . '-08-22 23:59:59')) {
			$expire_date = strtotime($sold_year . '-09-06 23:59:59'); // Best if this is Monday
		} else {
			$expire_date = strtotime('+14 days', $sold_date); // print_permit .php also has this 14 day thing.
		}
		if ($sold_date < strtotime($sold_year . '-08-15 23:59:59')) {
			$validFromText = "\nNot Valid until 08/15/2016";
		}


		//echo '<pre><b>New $expire_date: ('.$expire_date.') '.date('Y-m-d',$expire_date).'</b><br>';
		//echo '<pre>New $sold_date: ('.$sold_date.') '.date('Y-m-d',$sold_date).'<br>';
		//echo '<pre>New $per_sold_date: '.$per_sold_date.'<br>';


		if (!$plateStr) {
			$curVehicles = new GetVehicleAssociation($ent_uid);
			if ($GLOBALS['DEBUG_DEBUG']) echo $curVehicles;
			if ($curVehicles->VEH_PLATE_LICENSE[$curVehicles->primary_assUID]) {
				//----- create licence plate string (also in print_permit .php)
				$plateStr = $curVehicles->STL_CODE[$curVehicles->primary_assUID] . '-'
					. $curVehicles->VEH_PLATE_LICENSE[$curVehicles->primary_assUID] . '-'
					. $curVehicles->VPL_DESCRIPTION[$curVehicles->primary_assUID];
			}
		}

		if ($plateStr) {
			include_once '/var/www2/include/pdf/class.ezpdf.php';

			// Scramble the temp permit # a bit: Put expire-month in middle of permit number, and expire-day at end of permit number.
			$per_1 = preg_replace('/^(.{5})(.*)$/si', '$1', $permit_number);
			$per_2 = preg_replace('/^(.{5})(.*)$/si', '$2', $permit_number);
			$scrambled_eggs = $per_1 . date('m', $expire_date) . $per_2 . date('d', $expire_date);
			// Scramble a bit more by sticking ent_uid at end.
			$scrambled_eggs = $scrambled_eggs . $ent_uid;
			$txt_num = "\n\n";
			$txt_num .= 'Valid for License Plate: ' . $plateStr . "\n\n";
			if ($GLOBALS['DEBUG_DEBUG']) {
				echo "<hr><pre>debug data:<br>";
				echo '$permit_number:	' . $permit_number . '<br>';
				echo '$pdfFileName:		' . $pdfFileName . '<br>';
				echo '$per_sold_date:	' . $per_sold_date . '<br>';
				echo 'expire date:		' . date('Y-m-d H:i:s', $expire_date) . '<br>';
				echo '$scrambled_eggs:	' . $scrambled_eggs . '<br>';
				echo '$txt_num:			' . $txt_num . '<br>';
				echo "</pre><hr>";
			}

			$pdf = new Cezpdf();
			$pdf->selectFont('/var/www2/include/pdf/fonts/Helvetica.afm');

			//						  ($file,$x,$y,$w=0,$h=0)
			$pdf->addPngFromFile('/var/www2/html/images/logos/watermark_ua.png', 30, 320, 499);

			$txt_title = "\nTemporary Permit\n" . $desc . "\nPermit #: " . $permit_number . "\n" . $validFromText . "\nExpires " . date('m/d/Y', $expire_date) . "\n\n";
			$pdf->ezText($txt_title, 21, array("right" => 100, "justification" => "center"));

			$txt_num .= 'Temporary Permit #: ' . $scrambled_eggs . "\n\n";

			$pdf->ezText($txt_num, 18, array("left" => 30));

			// $pdf->ezText("\n\n\n\nThe vehicle with license plate listed above must display\nthis permit on the inside driver's side windshield.", 12, array("justification"=>"center"));
			$pdf->ezText("\n\n\n\nPlace your temporary permit on the driver side of the dashboard so that the permit number and vehicle information are visible through your windshield.  Temporary permit is only valid for dates listed on the PDF.  Upon receipt of your permanent permit, discard your temporary permit.", 12, array("justification" => "center"));
			$pdf->ezText("- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - \n", 10, array("justification" => "center"));


			//						  ($file,$x,$y,$w=0,$h=0)
			$pdf->addPngFromFile('/var/www2/html/images/logos/pts_logo.png',	 470, 735, 90);
			$pdf->addPngFromFile('/var/www2/html/images/logos/pts_address.png', 450, 620, 130);
			$pdf->selectFont('/var/www2/include/pdf/fonts/Times-BoldItalic.afm');
			$pdf->saveState();
			$pdf->setColor(0.9, 0.9, 0.9);
			file_put_contents($pdfDir . $pdfFileName, $pdf->ezOutput());

			// Put url in permit notes.
			if ($per_uid) {
				$noteText = "====== Temporary PDF Permit Generated " . date('Y-m-d H:i:s') . " \n "
					. " [[ URL: " . $pdf_URL . " ]]"
					. " [[ " . $txt_title . " ]]  \n  [[ " . $txt_num . " ]]  \n"
					. " [[ IP:" . $_SERVER['REMOTE_ADDR'] . " ]]";
				//if ($GLOBALS['DEBUG_DEBUG']) echo '----$noteText: '.$noteText."<br>";
				//			($NOTEUID, $TEXT, $REF_UID, $OBJ_TYPE = '', $TYPE_UID = '')
				if (!@$_SESSION['fake_per']['OBJECT_TYPE']) {
					// If $_SESSION['fake_per']['OBJECT_TYPE'] set then this is a payroll permit;  so set $object_type=1 which is Entity (10 is Permit)
					// If $_SESSION['fake_per']['OBJECT_TYPE'] set then should be '1' (not using right now though, if set)
					$object_type = @$_SESSION['fake_per']['OBJECT_TYPE'] ? $_SESSION['fake_per']['OBJECT_TYPE'] : 10;
					$aNote = new InsertUpdateNotes('', $noteText, $per_uid, $object_type);
					if ($GLOBALS['DEBUG_DEBUG']) echo $aNote;
					if (!$aNote->Note_Uid)
						$err = 'Error: Could not create permit note.<br/>';
				}
			}
		} else {
			$err = 'Error: No vehicle license plate found.<br/>';
		}

		if ($err) {
			if ($GLOBALS['DEBUG_DEBUG'] || $_POST['pernumber']) // pernumber is from print_permint .php, for CR.
				echo '<div style="font-weight:bold; color:red; padding:4px; border:1px solid red;">Debug Note: ' . $err . '</div>';
			return '';
		} else {
			return $pdf_URL;
		}
	}
}



class AddPermissionFee extends Flex_Permissions
{
	/**********************************
	 * This method is used to add a fee to a permit.
	 * Should use class below to make sure zero balance
	 * The MISC_ITEM.MIS_UID (Permit Fee UID) must be of type “Permit”, which is designated as having an MFC_UID_MISC_FEE_CATEGORY value of 4.
	 * Doc: https://www.pts.arizona.edu/mis/T2_Flex_WebServices.php
	 ***********************************/

	//----------------------- Input Parameters
	// PERMISSION_UID						// Required
	public $PERMISSION_FEE_UID	= ''; // Required  MISC_ITEM.MIS_UID

	//----------------------- Output
	// PERMISSION_UID
	public $PERMISSION_BALANCE	= '';

	public $flex_function	= 'AddPermissionFee';

	public function __construct($perUID, $feeUID)
	{
		Debug_Trace::traceFunc();
		parent::__construct();
		$this->PERMISSION_UID		= $perUID;
		$this->PERMISSION_FEE_UID	= $feeUID;
		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('PERMISSION_UID',			$this->PERMISSION_UID);
		$this->xml_data .= make_param('PERMISSION_FEE_UID',	$this->PERMISSION_FEE_UID);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';
		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}



class GetPermissionBalance extends Flex_Permissions
{
	/**********************************
	 * Doc: https://www.pts.arizona.edu/mis/T2_Flex_WebServices.php
	 ***********************************/

	//----------------------- Input Parameters
	// PERMISSION_UID		// Required

	//----------------------- Output
	// PERMISSION_UID
	public $PERMISSION_BALANCE	= '';

	public $flex_function	= 'GetPermissionBalance';

	public function __construct($perUID)
	{
		Debug_Trace::traceFunc();
		parent::__construct();
		$this->PERMISSION_UID	= $perUID;
		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('PERMISSION_UID',			$this->PERMISSION_UID);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';
		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}



class AuthPermissionSale extends Flex_Permissions
{
	/**********************************
	 * The permit must be reserved first.
	http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=52
	 ***********************************/

	//----------------------- Input Parameters
	//PERMISSION_UID				// Required
	//CONTROL_GROUP_UID			// Required
	//FAC_UID_FACILITY			// Required for VALID_LOCATIONS - strange thing this xml is
	//ENT_UID						// Required

	//------------------------- Output
	public $is_authorized			= false;
	// PERMISSION_UID
	public $CONTRAVENTION_BALANCE	= ''; // Citation balance due for this entity.
	public $CONTRAVENTION_COUNT	= ''; // Number of citations attached to this customer that are unpaid; that have not been transferred or voided; and that are not currently under appeal.


	public $flex_function	= 'AuthPermissionSale';

	public function __construct($perUID, $pcgUID, $facUID, $entUID)
	{
		Debug_Trace::traceFunc();
		parent::__construct();
		$this->PERMISSION_UID		= $perUID;
		$this->CONTROL_GROUP_UID	= $pcgUID;
		$this->FAC_UID_FACILITY		= trim($facUID);
		$this->ENT_UID					= $entUID;
		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('PERMISSION_UID',				$this->PERMISSION_UID);
		$this->xml_data .= make_param('CONTROL_GROUP_UID',			$this->CONTROL_GROUP_UID);
		if ($this->FAC_UID_FACILITY) {
			$this->xml_data .= "<Parameter><name>VALID_LOCATIONS</name><value><ValidLocations>
			<Location>
				<facility_UID>" . $this->FAC_UID_FACILITY . "</facility_UID>
				<area_UID></area_UID>
				<stallType_UID></stallType_UID>
				<stall_UID></stall_UID>
			</Location>
			</ValidLocations></value></Parameter>";
		}
		$this->xml_data .= make_param('ENTITY_UID',	$this->ENT_UID);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';
		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}



class ReservePermission extends Flex_Permissions
{
	/**********************************
	 * See PERMISSION table.
	 * If a permit UID (PERMISSION_UID) is sent, then the method will attempt to reserve that specific permit.
	 * However, if a permit number range (PERMISSION_NUM_RANGE_UID) is passed, then the next available permit
		will be reserved, and the PERMISSION_UID generated.
	 * The reservation will be till midnight of current day. "END_DATE 23:59:59" (PERMISSION.PER_RESERVE_END_DATE)
	http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=61
	 ***********************************/

	// Input Parameters
	//PERMISSION_UID				// Required if no Number Range Uid.
	//PERMISSION_NUM_RANGE_UID	// Required if no Permission Uid. (PNA_UID)
	//CONTROL_GROUP_UID			// Required (PEC_UID)
	//ENT_UID						// Required (ENTITY_UID)
	//CASH_DRAWER_UID				// Required
	public $END_DATE		= ''; // Required - should default to end of today "MM/DD/YYYY 23:59:59" (PERMISSION.PER_RESERVE_END_DATE)
	public $START_DATE	= ''; // Default will be now - MM/DD/YYYY

	// Output
	// PERMISSION_UID
	// PERMISSION_PRICE


	public $flex_function	= 'ReservePermission';

	public function __construct($perUID, $pnaUID, $pcgUID, $entUID, $endDate = 'tonight')
	{
		Debug_Trace::traceFunc();
		parent::__construct();
		$this->PERMISSION_UID				= $perUID;
		$this->PERMISSION_NUM_RANGE_UID	= $pnaUID;
		$this->CONTROL_GROUP_UID			= $pcgUID;
		$this->ENT_UID							= $entUID;
		if ($endDate == 'tonight')
			$this->END_DATE					= date('m/d/Y') . ' 00:00:00';
		else
			$this->END_DATE					= $endDate;
		if ($this->END_DATE == '04/07/2013 00:00:00') $this->END_DATE = '04/08/2013 00:00:00';
		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('PERMISSION_UID',				$this->PERMISSION_UID);
		$this->xml_data .= make_param('PERMISSION_NUM_RANGE_UID', $this->PERMISSION_NUM_RANGE_UID);
		$this->xml_data .= make_param('CONTROL_GROUP_UID',			$this->CONTROL_GROUP_UID);
		$this->xml_data .= make_param('ENTITY_UID',					$this->ENT_UID);
		$this->xml_data .= make_param('END_DATE',						$this->END_DATE);
		$this->xml_data .= make_param('CASH_DRAWER_UID',			$this->WS_CONN['WS_cashUID']);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}



class UnreservePermission extends Flex_Permissions
{
	/**********************************
	 * See PERMISSION table.
	 * This method is used to set the status of an active permit to Inactive. If a deactivation fee is required for a status change it will be added
	 * to the permission. If the generate letter parameter is set to true, an address UID or email address UID is required input. The method will
	 * look up the customer currently related to the permission, and return an error if the specified address or email address is not related to that entity.
	 ***********************************/

	// Input Parameters
	// permission uid to un-reserve

	// Output
	// permission uid


	public $flex_function	= 'UnreservePermission';

	public function __construct($perUid)
	{
		Debug_Trace::traceFunc();
		parent::__construct();
		if ($perUid) {
			$this->PERMISSION_UID	= $perUid;
			$this->send_xml();
		}
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('PERMISSION_UID',		$this->PERMISSION_UID);
		$this->xml_data .= make_param('CASH_DRAWER_UID',	$this->WS_CONN['WS_cashUID']);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}



class GetPermissionPriceFixed extends Flex_Permissions
{
	/**********************************
	 * Only to show FIXED price for drop-down to show prices.
	 * ######### DON'T USE THIS PRICE FOR PERMIT SALES -- for real sale price see class's Reserver Permit to get price #################
	 * See also README .txt
	 ***********************************/

	//------------- Output
	public $groupPrices =	array(); // groupPrices[WAITLIST.WLT_UID] =			PFE_RATE_AMOUNT_FIXED
	public $prorateDate =	array(); // prorateDate[WAITLIST.WLT_UID] =			PFE_FIXED_BEGIN_PROR_DATE
	public $nowProrated =	array(); // nowProrated[WAITLIST.WLT_UID] =			true if current date is on or after PFE_FIXED_BEGIN_PROR_DATE.
	public $isMultiWait =	array(); // isMultiWait[WAITLIST.WLT_UID] =			WAITLIST.WLT_IS_MULTIPLE_CHOICE
	public $waitlistDesc =	array(); // waitlistDesc[WAITLIST.WLT_UID] =			WLT_DESCRIPTION
	public $groupWaitlist =	array(); // groupWaitlist[WAITLIST.WLT_UID] =		PERMISSION_FEE_VIEW.PEC_UID_PERM_CONTROL_GROUP (the group)
	public $pnaWaitlist =	array(); // pnaWaitlist[WAITLIST.WLT_UID] =			PERMISSION_FEE_VIEW.PNA_UID_PER_NUM_RANGE

	public function __construct($pcg_uid = array())
	{
		Debug_Trace::traceFunc();

		if (sizeof($pcg_uid))
			exitWithBottom('ERROR: Permit Prices - Array!');

		$pcg_uid = array(); // $pcg_uid is NEVER USED.

		$tmp_groupPrices = $tmp_prorateDate = $tmp_nowProrated = $tmp_isMultiWait = $tmp_waitlistDesc = $tmp_groupWaitlist = array();

		// In the T2 query this will be: WLT_START_DATE >= $param_ary
		$param_ary	= array('greater than start date' => '02/01/2013 01:00:00');
		$t2Key		= 'Q_GetPermissionPriceFixed_3';
		$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
		//$exeResults->setMoreCustom('PFE_FIXED_BEGIN_PROR_DATE', 'date_1');
		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;

		// Filter out records that contain PEC_UID in array $pcg_uid.
		$tmpResults2 = array();
		foreach ($exeResults->results_custom['PEC_UID_PERM_CONTROL_GROUP'] as $k => $aPec) {
			if (!sizeof($pcg_uid) || in_array($aPec, $pcg_uid)) {
				$a_wlt_uid = $exeResults->results_custom['WLT_UID'][$k];
				$tmp_groupPrices[$a_wlt_uid]		= $exeResults->results_custom['PFE_RATE_AMOUNT_FIXED'][$k];
				$tmp_prorateDate[$a_wlt_uid]		= $exeResults->results_custom['PFE_FIXED_BEGIN_PROR_DATE'][$k];
				$tmp_nowProrated[$a_wlt_uid]		= withinTimeframe($exeResults->results_custom['PFE_FIXED_BEGIN_PROR_DATE'][$k], '2099-01-01') ? true : false;
				$tmp_isMultiWait[$a_wlt_uid]		= $exeResults->results_custom['WLT_IS_MULTIPLE_CHOICE'][$k];
				$tmp_waitlistDesc[$a_wlt_uid]		= $exeResults->results_custom['WLT_DESCRIPTION'][$k];
				$tmp_groupWaitlist[$a_wlt_uid]	= $exeResults->results_custom['PEC_UID_PERM_CONTROL_GROUP'][$k];
				$tmp_pnaWaitlist[$a_wlt_uid]		= $exeResults->results_custom['PNA_UID_PER_NUM_RANGE'][$k];
			}
		}
		$this->groupPrices =			$tmp_groupPrices;
		$this->prorateDate =			$tmp_prorateDate;
		$this->nowProrated =			$tmp_nowProrated;
		$this->isMultiWait =			$tmp_isMultiWait;
		$this->waitlistDesc =		$tmp_waitlistDesc;
		$this->groupWaitlist =		$tmp_groupWaitlist;
		$this->pnaWaitlist =			$tmp_pnaWaitlist;
	}
}



class PayPermissionBalance extends Flex_Permissions
{
	/***************************
	http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=66
	 *****************************/

	//------------- INPUT ---------------
	// PERMISSION_UID				// Required for inventoried number ranges. Optional for non-inventoried ranges.
	// ENT_UID						// Required. Entity UID or Flex ID (ENTITY.ENT_UID)
	// CASH_DRAWER_UID			// Required
	public $AMOUNT_PAID	= ''; // Required

	// Required Joann: 1 is "No Charge", 14 is "External Payment Plan", 2000 is "Credit Card"
	public $PAYMENT_TYPE_UID		= ''; // (PAYMENT_METHOD_MLKP.PMM_UID) (PAYMENT_TYPE_MLKP.PAY_UID is also "External Payment Plan")

	public $REFERENCE_NOTE			= ''; // Reference note on payment
	public $ACCOUNT_CREDIT_LIST	= ''; // comma separated list of Account Credits(ACCOUNT_CREDITS.ACR_UID) Required if account payment method used.


	//---------- OUTPUT --------------
	// PERMISSION_UID        -- parent
	public $RECEIPT_UID				= '';


	public $flex_function	= 'PayPermissionBalance';

	public function __construct($perUID, $entUID, $amtPaid = '', $payTypeUID = '')
	{
		parent::__construct();
		$this->PERMISSION_UID			= $perUID;
		$this->ENT_UID						= $entUID;

		$this->PAYMENT_TYPE_UID			= $payTypeUID ? $payTypeUID : 2000;

		$this->AMOUNT_PAID				= $amtPaid;

		$this->send_xml($ignoreErrNumber);
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('PERMISSION_UID',				$this->PERMISSION_UID);
		$this->xml_data .= make_param('ENTITY_UID',					$this->ENT_UID);
		$this->xml_data .= make_param('AMOUNT_PAID',					$this->AMOUNT_PAID);
		$this->xml_data .= make_param('PAYMENT_TYPE_UID',			$this->PAYMENT_TYPE_UID);
		$this->xml_data .= make_param('CASH_DRAWER_UID',			$this->WS_CONN['WS_cashUID']);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}



class SellPermission extends Flex_Permissions
{
	/***************************
	This method is used to sell a permit AFTER it has first been reserved. It uses the
	fee schedule of type value credential for a value credential permit passed in to
	determine the permit‘s price.
	http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=66
	 *****************************/

	//------------- INPUT ---------------
	// CONTROL_GROUP_UID			// Required
	// FAC_UID_FACILITY			// Required (ARRAY OR STRING) for VALID_LOCATIONS- strange thing this xml is
	// PERMISSION_UID				// Required for inventoried number ranges. Optional for non-inventoried ranges.
	// ENT_UID						// Required. Entity UID or Flex ID (ENTITY.ENT_UID)
	// CASH_DRAWER_UID			// Required
	// ALLOC_FACILITY_GROUP_UID and ALLOC_FACILITY_UID -- Dont use these.

	public $AMOUNT_PAID						= ''; // Required

	// Required Joann: 1 is "No Charge", 14 is "External Payment Plan", 2000 is "Credit Card"
	public $PAYMENT_TYPE_UID				= ''; // (PAYMENT_METHOD_MLKP.PMM_UID) (PAYMENT_TYPE_MLKP.PAY_UID is also "External Payment Plan")

	// Payment plan tax type UID (PAY_PLAN_TAX_STATUS_LKP.PAT_UID). Values: 2- Pre-Tax, 3- Post-Tax
	// REQUIRED, if an external pay plan used and required by transfer agent.
	public $PAYPLAN_TAX_TYPE				= '';

	/****
	 * For external payment plans, if a payment plan template uid IS specified here, then the payment plan time period and number of payments or amount
		per payment are not required.
	 * However, if a payment plan template IS specified, then any values passed for payment plan time period, number of payments, or the amount per
		payment will override the values found in the template. Therefore, To use the values on a payment plan template do not
		send the PAYPLAN_TIME_PERIOD_UID, PAYPLAN_NUM_PAYMENTS, or the PAYPLAN_AMOUNT_PER_PAYMENT parameters.
	 * If a valid payment plan template is NOT specified, the payment plan time period and either the number of payment or the amount per payment are
		required. If both the number of payments and the amount per payment are included an error will be returned.
	 ****/
	public $PAYPLAN_TEMPLATE_UID			= ''; // PAYMENT_SCHEDULE_TEMP_MLKP.PSE_UID: 2001=Bursar, 2000=Payroll
	public $PAYPLAN_TRANSFER_AGENT_UID	= ''; // PAYMENT_PLAN.TAL_UID_TRANSFER_AGENCY ~ TRANSFER_AGENCY_MLKP.TAL_UID: 2008=Bursars Payment Plan, 2001=Bursar, 2000=Payroll - OLD is 2008
	public $LINKED_VEHICLE_UID				= ''; // Vehicle UID (VEHICLE.VEH_UID) to link to permit
	public $REFERENCE_NOTE					= ''; // Reference note on payment
	public $FORCE_OVERRIDE_EXPIRATION_DATE	= ''; // also requires PERMISSION_EXPIRATION_DATE

	// Not using mailing stuff here, using dad_uid_pick_up_per stuff, so default MAILING_REQUIRED to '0'
	public $MAILING_REQUIRED				= '0'; // NOT USED
	public $SHIPPING_METHOD_UID			= '';  // Required if MAILING_REQUIRED
	public $MAILING_ADDRESS_UID			= '';  // Required if MAILING_REQUIRED.  Address UID (COR_ADDRESS.COR_UID) of where to mail a permit

	/****
	 * Permit sale price. Use if the sale price differs from the PERMISSION_PRICE returned by ReservePermission or GetPermissionPrice method.
	 * If a positive value is included for the PERMISSION_SALE_PRICE parameter, that value will override the value generated by Flex. If this
		parameter is not passed, the default price will be used. If a value of zero is passed, a payment method of ―No Charge must be used.
	 ****/
	public $PERMISSION_SALE_PRICE			= '';

	public $PAYPLAN_START_DATE				= ''; // NOT required if pay plan used. defaults to current day. MM/DD/YYYY

	// Required if pay plan used and template not specified. 1-Weekly, 2-Semi-Monthly, 3-Monthly, 4-Bi-Weekly. (PAYMENT_PLAN_PERIOD_LKP.PPP_UID
	public $PAYPLAN_TIME_PERIOD_UID		= ''; // payplan period. Payplan template should make this 4

	public $PAYPLAN_NUM_PAYMENTS			= ''; // Payplan template should take care of this.
	public $PAYPLAN_AMOUNT_PER_PAYMENT	= ''; // Payplan template should take care of this.

	public $Email_Uid							= ''; // for shipmint notification


	//---------- OUTPUT --------------
	// PERMISSION_UID
	public $RECEIPT_UID						= '';


	public $flex_function	= 'SellPermission';

	public function __construct($perUID, $pcgUID, $facUID, $entUID, $vehUID, $pick_up_permit, $amtPaid = '', $pay_plan_tmpl_uid = 0, $numPay = '', $amtPerPay = '', $payPlanStart = '', $testSell = false, $payTypeUID = '', $note = '')
	{
		/**********
		 */
		parent::__construct();
		$this->PERMISSION_UID			= $perUID;
		$this->CONTROL_GROUP_UID		= $pcgUID;
		$this->ENT_UID						= $entUID;

		$this->FAC_UID_FACILITY = array();
		if (is_array($facUID)) {
			foreach ($facUID as $f_k => $f_val)
				$this->FAC_UID_FACILITY[]	= trim($f_val);
		} else if (trim($facUID)) // old way - non-array.
		{
			$this->FAC_UID_FACILITY[]		= trim($facUID);
		}

		if ($pay_plan_tmpl_uid) {
			if ($pay_plan_tmpl_uid == 2000) {
				// PAYROLL deduction
				$this->PAYPLAN_TEMPLATE_UID			= 2000; // 2001=Bursar, 2000=Payroll
				$this->PAYPLAN_TRANSFER_AGENT_UID	= 2000; // 2008=Bursars Payment Plan, 2001=Bursar, 2000=Payroll
				// Payment plan tax type UID (PAY_PLAN_TAX_STATUS_LKP.PAT_UID). Values: 2- Pre-Tax, 3- Post-Tax
				// REQUIRED, if an external pay plan used and required by transfer agent.
				$this->PAYPLAN_TAX_TYPE					= 2;
			} else {
				// Bursar BPP
				$this->PAYPLAN_TEMPLATE_UID			= 2001; // 2001=Bursar, 2000=Payroll
				/***
				 * Jody - June 2, 2016 - changed to 2001 from 2008.
				 * Jody - July 25, 2016 10am - changed back to 2008 from 2001. (16-237974)
				 * July 21, 2016 2:43pm - PTS logs show the change was made 2016-07-21 between 2:43pm and 2:53pm --
				 * https://www.pts.arizona.edu/logs/index.php?line_clicked=389&yyyymmdd=20160721&type=3
				 */
				$this->PAYPLAN_TRANSFER_AGENT_UID	= 2008; // 2008=Bursars Payment Plan, 2001=Bursar, 2000=Payroll - OLD is 2008
				// (because we use "External Payment Plan" AND required by transfer agent????). 1-No Tax, 2-Pre-Tax, 3-Post-Tax
				$this->PAYPLAN_TAX_TYPE					= 1;
			}
			// 1 - "No Charge", 14 - "External Payment Plan", 2000 - "Credit Card"
			$this->PAYMENT_TYPE_UID					= 14;
			if (!$payPlanStart)
				$this->PAYPLAN_START_DATE			= parent::paymentPlanStart;
			$this->PAYPLAN_NUM_PAYMENTS			= $numPay;
			$this->PAYPLAN_AMOUNT_PER_PAYMENT	= $amtPerPay;
		} else {
			// 1 - "No Charge", 14 - "External Payment Plan", 2000 - "Credit Card"
			$this->PAYMENT_TYPE_UID					= $payTypeUID ? $payTypeUID : 2000;
		}

		/*****************************************************************************************
		 * AuthPermissionSale is not doing it's job - can't determine Permit Purchase Count Exceeded - CODE 2261.
		 * Workaround: Since SellPermission DOES determine 2261 errors, then run it early here (before cyberpay php). But
		 * to make sure SellPermission ALWAYS fails, then sell with a rediculously low price (MUST BE > $0).
		 * Might as well set pick_up_permit, because this is what customer wants anywho - it will be re-done in cyberpay php
		 ***********************/
		if ($testSell) {
			$amtPaid = 0.02;
			// Ignore t2 errors. Cant remember first number but #4508 is "The payment amount must be greater than or equal to the Grand Total".
			$ignoreErrNumber	= '9531,4508';
		} else {
			$ignoreErrNumber	= '';
		}
		$this->AMOUNT_PAID	= $amtPaid;

		//	$per_price = GetPermissionPrice($this->PERMISSION_UID, $this->CONTROL_GROUP_UID);
		//	$this->PERMISSION_SALE_PRICE = $per_price->PERMISSION_PRICE;

		$this->LINKED_VEHICLE_UID	= $vehUID;

		$this->REFERENCE_NOTE		= preg_replace('/^(.{12}).*$/si', '$1', $note); // Max chars is less than 40.
		$this->send_xml($ignoreErrNumber);

		$this->setPickupPer($pick_up_permit);
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('PERMISSION_UID',				$this->PERMISSION_UID);
		$this->xml_data .= make_param('CONTROL_GROUP_UID',			$this->CONTROL_GROUP_UID);
		if (sizeof($this->FAC_UID_FACILITY)) {
			$this->xml_data .= "<Parameter><name>VALID_LOCATIONS</name>
				<value>
				<ValidLocations>";
			foreach ($this->FAC_UID_FACILITY as $f_k => $f_val) {
				$this->xml_data .= "
				 <Location>
					<facility_UID>" . $f_val . "</facility_UID>
					<area_UID></area_UID>
					<stallType_UID></stallType_UID>
					<stall_UID></stall_UID>
				 </Location>";
			}
			$this->xml_data .= "
				</ValidLocations>
				</value></Parameter>";
		}
		$this->xml_data .= make_param('ENTITY_UID',					$this->ENT_UID);
		$this->xml_data .= make_param('MAILING_REQUIRED',			$this->MAILING_REQUIRED); // should always be '0' default.
		$this->xml_data .= make_param('LINKED_VEHICLE_UID',		$this->LINKED_VEHICLE_UID);
		$this->xml_data .= make_param('REFERENCE_NOTE',				$this->REFERENCE_NOTE);
		$this->xml_data .= make_param('AMOUNT_PAID',					$this->AMOUNT_PAID);
		$this->xml_data .= make_param('PAYMENT_TYPE_UID',			$this->PAYMENT_TYPE_UID);
		$this->xml_data .= make_param('PAYPLAN_TRANSFER_AGENT_UID',	$this->PAYPLAN_TRANSFER_AGENT_UID);
		$this->xml_data .= make_param('PAYPLAN_TEMPLATE_UID',		$this->PAYPLAN_TEMPLATE_UID);
		$this->xml_data .= make_param('PAYPLAN_NUM_PAYMENTS',		$this->PAYPLAN_NUM_PAYMENTS);
		$this->xml_data .= make_param('PAYPLAN_TAX_TYPE',			$this->PAYPLAN_TAX_TYPE);
		$this->xml_data .= make_param('PAYPLAN_TIME_PERIOD_UID',	$this->PAYPLAN_TIME_PERIOD_UID);
		$this->xml_data .= make_param('PAYPLAN_START_DATE',		$this->PAYPLAN_START_DATE);
		$this->xml_data .= make_param('CASH_DRAWER_UID',			$this->WS_CONN['WS_cashUID']);
		$this->xml_data .= make_param('PERMISSION_SALE_PRICE',	$this->PERMISSION_SALE_PRICE);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}


	public function setRFID($rfidNum, $perUID)
	{
		Debug_Trace::traceFunc();
		/***
		 * Set the RFID [custom] field of a garage permit.
		 * Called from cyberpay_sa .php -- $rfidNum has 'X' prepended.
		 * $rfidNum is usually an IPermit, but with the 'I' trimmed off PERMISSION_UID
		 * Returns error if any.
		 */
		$custKey = 'RFID_NUMBER';
		//											($web_service, $cust_field_name, $uid_value, $cust_field_value)
		$CF = new InsertUpdateCustomFlds(self::$cust_fld_svc[$custKey], $custKey, $perUID, $rfidNum);
		if ($GLOBALS['DEBUG_DEBUG']) echo $CF;
		return $CF->ErrorDescription;
	}


	public function setCatcardID($catcard, $perUID)
	{
		Debug_Trace::traceFunc();
		/***
		 * Sets custom data - for Permit.
		 * Returns error if any.
		 */
		$custKey = 'SWIPE_CARD';
		//											($web_service, $cust_field_name, $uid_value, $cust_field_value)
		$CF = new InsertUpdateCustomFlds(self::$cust_fld_svc[$custKey], $custKey, $perUID, $catcard);
		if ($GLOBALS['DEBUG_DEBUG']) echo $CF;
		if ($GLOBALS['DEBUG_DEBUG']) echo "\n\nInsertUpdateCustomFlds(" . self::$cust_fld_svc[$custKey] . ", $custKey, $perUID, $catcard)\n\n";
		return $CF->ErrorDescription;
	}


	public function sellUcell($pecUID, $uCellNumber, $notes, $sess_entity = array())
	{
		/****
		 * In the PARKING.UCELL_SUNTRAN table (not T2), reserve and then sell (Distribute) a u-cell activation code, using
		 *		the customer's UAID (dbkey) - NOT the ent_uid.  Also add customer's info to to PARKING.UCELL_SUNTRAN.
		 * $pecUID = '1999' -- for the 14-day opt in free pass.
		 * $pecUID = the pec_uid of the purchased bus pass --
		 * This function called here and in U-CellPass/optform .php.
		 *		and, if $sess_entity is set, then CR is manually geting customer a uCell - Internal/customer_relations/ucell_code .php
		 * returns ACTIVATION_CODE
		 * TODO:
		 * Create cron script to run every night to search for any rows in PARKING.UCELL_SUNTRAN where
		 		  STATUS = 2 (reserved) and where MODIFY_DATE is yesterday, and change that STATUS to 1.
		 */
		global $mysqli2;
		if (!isset($mysqli2)) $mysqli2 = new database();

		if (sizeof($sess_entity)) {
			if (!trim(@$sess_entity['UA_DBKEY'])) {
				include_once 'login_eds.php';
				$searchFilter	= '(uid=' . $sess_entity['NETID'] . ')';
				$ldapDATA = searchLDAP($searchFilter);
				$sess_entity['UA_DBKEY'] = $ldapDATA['uaid'][0];
				if ($GLOBALS['DEBUG_DEBUG']) {
					echo '<div style="border:1px solid grey; padding:2px; margin:3px;">'
						. 'DEBUG NOTE: The DBKEY (AKA: UAID, TERTIARY_ID) not found, so searching EDS via NETID..... DBKEY: ' . $sess_entity['UA_DBKEY'] . '</div>';
				}
			}
			$s_dbkey = $sess_entity['UA_DBKEY'];

			$s_netid = $sess_entity['NETID'];
			$s_emplid = $sess_entity['EMPLID'];
			$s_sn = $sess_entity['LAST_NAME'];
			$s_givenname_fn = $sess_entity['FIRST_NAME'];
			$s_givenname_mi = $sess_entity['MIDDLE_NAME'];

			$s_ESL_UID_SUBCLASS = $sess_entity['ESL_UID_SUBCLASS'];
			$s_COR_PRIMARY_STREET = $sess_entity['COR_PRIMARY_STREET'];
			$s_COR_SUITE_APARTMENT = $sess_entity['COR_SUITE_APARTMENT'];
			$s_COR_CITY = $sess_entity['COR_CITY'];
			$s_STL_UID_STATE = $sess_entity['STL_UID_STATE'];
			$s_COR_POSTAL_CODE = $sess_entity['COR_POSTAL_CODE'];
			$s_EMAIL_ADDRESS = $sess_entity['EMAIL_ADDRESS'];
		} else {
			$s_dbkey = $_SESSION['eds_data']['dbkey'];
			$s_netid = $_SESSION['eds_data']['netid'];
			$s_emplid = $_SESSION['eds_data']['emplid'];
			$s_sn = $_SESSION['eds_data']['sn'];
			$s_givenname_fn = $_SESSION['eds_data']['givenname_fn'];
			$s_givenname_mi = $_SESSION['eds_data']['givenname_mi'];

			$s_ESL_UID_SUBCLASS = $_SESSION['entity']['ESL_UID_SUBCLASS'];
			$s_COR_PRIMARY_STREET = $_SESSION['entity']['COR_PRIMARY_STREET'];
			$s_COR_SUITE_APARTMENT = $_SESSION['entity']['COR_SUITE_APARTMENT'];
			$s_COR_CITY = $_SESSION['entity']['COR_CITY'];
			$s_STL_UID_STATE = $_SESSION['entity']['STL_UID_STATE'];
			$s_COR_POSTAL_CODE = $_SESSION['entity']['COR_POSTAL_CODE'];
			$s_EMAIL_ADDRESS = $_SESSION['entity']['EMAIL_ADDRESS'];
		}

		$uCellNumber = preg_replace('/[^\d]/si', '', $uCellNumber);

		$err = '';

		if (!$uCellNumber)
			$err = 'Note: We could not insert your U-Cell phone number. ' . CONTACT_CR;

		if ($err) {
			echo "<div class='warning' align='center' style='padding:9px;'>" . $err . "</div>";
		} else {
			/***
			 * First Find a free activation code in table UCELL_SUNTRAN (where STATUS is NULL or STATUS = 1) and update
			 * that record's STATUS to code 2 (Reserved), and only record customer's ENTITY_ENT_TERTIARY_ID_UAID into this table.
			 * Returns the newly reserved reserved activation code.
			 */
			$status_code = 2;

			$qVars = array(
				'ENTITY_ENT_TERTIARY_ID_UAID' => $s_dbkey,
				'ENTITY_ENT_SECONDARY_ID_UID' => $s_netid, 'ENTITY_ENT_PRIMARY_ID_EMPLID' => $s_emplid,
				'ENTITY_ENT_LAST_NAME_SN' => $s_sn, 'ENTITY_ENT_FIRST_NAME_GIVNAME' => $s_givenname_fn,
				'ENTITY_ENT_MIDDLE_NAME_GIVNAME' => $s_givenname_mi, 'ENTITY_ENT_ESL_UID_SUBCLASS' => $s_ESL_UID_SUBCLASS,
				'COR_ADDRESS_COR_PRIMARY_STREET' => @$s_COR_PRIMARY_STREET, 'COR_ADDRESS_COR_SUITE_APARTMNT' => @$s_COR_SUITE_APARTMENT,
				'COR_ADDRESS_COR_CITY' => @$s_COR_CITY, 'COR_ADDRESS_STL_UID_STATE' => @$s_STL_UID_STATE,
				'COR_ADDRESS_COR_POSTAL_CODE' => @$s_COR_POSTAL_CODE, 'COR_EMAIL_COE_EMAIL_ADDRESS' => $s_EMAIL_ADDRESS,
				'ELIGIBLE' => 1, 'UCELL' => $uCellNumber, 'PROCESSED' => 2, 'NOTES' => $notes, 'PCG_PEC_UID' => $pecUID
			);

			$query1 = "update PARKING.UCELL_SUNTRAN set ENTITY_ENT_TERTIARY_ID_UAID = :ENTITY_ENT_TERTIARY_ID_UAID,
							STATUS = " . $status_code . ", MODIFY_DATE = SYSDATE
						 where ACTIVATION_CODE =
							(select ACTIVATION_CODE from PARKING.UCELL_SUNTRAN where ENTITY_ENT_TERTIARY_ID_UAID is NULL
								and (STATUS is NULL or STATUS = 1) and PCG_PEC_UID=:PCG_PEC_UID and ROWNUM = 1)";
			$mysqli2->sQuery($query1, $qVars);
			// Get the Reserved activation_code.
			$query2 = "select ACTIVATION_CODE from PARKING.UCELL_SUNTRAN
						where ENTITY_ENT_TERTIARY_ID_UAID = :ENTITY_ENT_TERTIARY_ID_UAID and STATUS = 2 and PCG_PEC_UID=:PCG_PEC_UID";
			$mysqli2->sQuery($query2, $qVars);
			$ACTIVATION_CODE_res = $mysqli2->results['ACTIVATION_CODE'][0];

			if ($ACTIVATION_CODE_res) {
				$qVars['activation_code'] = $ACTIVATION_CODE_res;
				//------------------ copy to log table.
				if (is_object($this))
					$this->insertLog($qVars, $status_code);
				else
					SellPermission::insertLog($qVars, $status_code);
			} else {
				if ($GLOBALS['DEBUG_DEBUG']) {
					echo $mysqli2->pretty_print_query($query1, $qVars, __FILE__ . ':' . __LINE__);
					echo $mysqli2->pretty_print_query($query2, $qVars, __FILE__ . ':' . __LINE__);
				}
				// Warning only
				$err = "Warning, could not reserve activation code. " . CONTACT_CR;
				// If notes set then this is from ucell_code .php - cust relations employee.
				if ($notes)
					exitWithBottom('ERROR: could not reserve activation code. Please ask IT to "Check PARKING.UCELL_SUNTRAN table for PEC ' . $pecUID . '".');
			}

			/***
			 * In table UCELL_SUNTRAN set STATUS = 3 (Distributed).  The status was set to 2 just above.
			 * Also update this table with customer's personal info.
			 * And record customer's info into table UCELL_OPT_IN (and UCELL_OPT_IN_LOG), to show he oped in.
			 */
			$status_code = 3;

			//--------------- set status code to 3 (from 2 which means reserved).  We could update NOTES above, but probably better to do it here.
			$query = "update PARKING.UCELL_SUNTRAN set STATUS = " . $status_code . ", ENTITY_ENT_TERTIARY_ID_UAID = :ENTITY_ENT_TERTIARY_ID_UAID,
							ENTITY_ENT_SECONDARY_ID_UID = :ENTITY_ENT_SECONDARY_ID_UID, ENTITY_ENT_PRIMARY_ID_EMPLID = :ENTITY_ENT_PRIMARY_ID_EMPLID,
							UCELL = :UCELL, MODIFY_DATE = SYSDATE, NOTES = :NOTES
						 where ENTITY_ENT_TERTIARY_ID_UAID = :ENTITY_ENT_TERTIARY_ID_UAID and STATUS = 2 and ACTIVATION_CODE = :activation_code";
			$mysqli2->sQuery($query, $qVars);

			//------------------ copy to log table.
			if (is_object($this))
				$this->insertLog($qVars, $status_code);
			else
				SellPermission::insertLog($qVars, $status_code);

			// Get the Distributed activation_code.
			$query = "select ACTIVATION_CODE from PARKING.UCELL_SUNTRAN
						where ENTITY_ENT_TERTIARY_ID_UAID = :ENTITY_ENT_TERTIARY_ID_UAID and STATUS = 3 and PCG_PEC_UID=:PCG_PEC_UID";
			$mysqli2->sQuery($query, $qVars);
			$ACTIVATION_CODE_dist = $mysqli2->results['ACTIVATION_CODE'][0];
			if (!$ACTIVATION_CODE_dist)
				$err = "Warning, could not distribute activation code" . CONTACT_CR;

			if ($err)
				echo "<div class='warning' align='center' style='padding:9px;'>" . $err . "</div>";

			return $ACTIVATION_CODE_dist;
		}
	}


	public function insertLog($qVars, $status_code)
	{
		/*****
		 * Insert a record into table UCELL_SUNTRAN_LOG, for every insert or update made to table UCELL_SUNTRAN.
		 */
		global $mysqli2;

		$qVars['STATUS'] = $status_code;

		$mysqli2->query("SELECT PARKING.UCELL_SUNTRAN_LOG_ID.NEXTVAL AS SUNTRAN_LOG_ID FROM DUAL");
		$SUNTRAN_LOG_ID = $mysqli2->rows ? $mysqli2->results['SUNTRAN_LOG_ID'][0] : exitWithBottom('ERROR: Could not create SUNTRAN_LOG_ID.');

		$query = "insert into PARKING.UCELL_SUNTRAN_LOG (ACTIVATION_CODE, ENTITY_ENT_UID, ENTITY_ENT_TERTIARY_ID_UAID, ENTITY_ENT_SECONDARY_ID_UID,
						ENTITY_ENT_PRIMARY_ID_EMPLID, UCELL, MODIFY_DATE, STATUS, UCELL_SUNTRAN_LOG_UID, NOTES)
					 VALUES (:activation_code, '', :ENTITY_ENT_TERTIARY_ID_UAID, :ENTITY_ENT_SECONDARY_ID_UID, :ENTITY_ENT_PRIMARY_ID_EMPLID, :UCELL,
						SYSDATE, :STATUS, " . $SUNTRAN_LOG_ID . ", :NOTES)";
		$dbSuc = $mysqli2->sQuery($query, $qVars);
		if (!$dbSuc)
			echo "<div class='warning' align='center' style='padding:9px;'>Warning:could not insert into log file.</div>";
	}



	public function getActivationCodes($UAID, $pecUID = '', $phone = '', $netid = '', $emplid = '')
	{
		Debug_Trace::traceFunc();
		/***
		 * INPUT:
		 *		If ($UAID OR $phone) AND $pecUID have values, then look for single, active, activation_code for customer (STATUS 3 = distributed)
		 *		If only $UAID is set, then get ALL activation codes for customer, regardless of status, etc.
		 * OUTPUT:
		 *		$ucell_records array (each n is a record):
		 *			$ucell_records[n]['activation_code'] = xxxxx
		 *			$ucell_records[n]['status'] = 1, 2, or 3.
		 *			$ucell_records[n]['pec_uid'] = xxxxx
		 * Note: $pecUID = '1999' -- for the 14-day opt in free pass.
		 */
		global $mysqli2;

		$ucell_records = array();

		if (!isset($mysqli2)) $mysqli2 = new database();

		// note upper and lower case: ENTITY_ENT_SECONDARY_ID_UID_uc and ENTITY_ENT_SECONDARY_ID_UID_lc
		$qVars = array(
			'ENTITY_ENT_TERTIARY_ID_UAID' => $UAID, 'PCG_PEC_UID' => $pecUID, 'UCELL' => $phone,
			'ENTITY_ENT_SECONDARY_ID_UID_lc' => strtolower($netid), 'ENTITY_ENT_SECONDARY_ID_UID_uc' => strtoupper($netid), 'ENTITY_ENT_PRIMARY_ID_EMPLID' => $emplid
		);

		if (($UAID || $phone) && $pecUID) {
			// Look for single, active, activation code.  there will only be one record, or none at all.
			$query = "select * from PARKING.UCELL_SUNTRAN
						 where (ENTITY_ENT_TERTIARY_ID_UAID = :ENTITY_ENT_TERTIARY_ID_UAID or UCELL = :UCELL) and PCG_PEC_UID = :PCG_PEC_UID and STATUS = 3";
		} else if ($UAID) {
			$query = "select * from PARKING.UCELL_SUNTRAN
						 where ENTITY_ENT_TERTIARY_ID_UAID = :ENTITY_ENT_TERTIARY_ID_UAID";
		} else if ($phone) {
			$query = "select * from PARKING.UCELL_SUNTRAN
						 where UCELL = :UCELL";
		} else if ($netid) {
			$query = "select * from PARKING.UCELL_SUNTRAN
						 where ENTITY_ENT_SECONDARY_ID_UID = :ENTITY_ENT_SECONDARY_ID_UID_lc or ENTITY_ENT_SECONDARY_ID_UID = :ENTITY_ENT_SECONDARY_ID_UID_uc";
		} else if ($emplid) {
			$query = "select * from PARKING.UCELL_SUNTRAN
						 where ENTITY_ENT_PRIMARY_ID_EMPLID = :ENTITY_ENT_PRIMARY_ID_EMPLID";
		} else {
			exitWithBottom('Error, not enough data.');
		}
		$mysqli2->sQuery($query, $qVars);

		for ($i = 0; $i < $mysqli2->rows; $i++) {
			$ucell_records[$i]['activation_code']	= $mysqli2->results['ACTIVATION_CODE'][$i];
			$ucell_records[$i]['status']				= $mysqli2->results['STATUS'][$i];
			$ucell_records[$i]['pec_uid']				= $mysqli2->results['PCG_PEC_UID'][$i];
			$ucell_records[$i]['UCELL']				= $mysqli2->results['UCELL'][$i];
			$ucell_records[$i]['netid']				= $mysqli2->results['ENTITY_ENT_SECONDARY_ID_UID'][$i];
			$ucell_records[$i]['ent_uid']				= $mysqli2->results['ENTITY_ENT_UID'][$i];
			$ucell_records[$i]['emplid']				= $mysqli2->results['ENTITY_ENT_PRIMARY_ID_EMPLID'][$i];
		}

		return $ucell_records;
	}


	public function getPerInfoFromPec($pecUid)
	{
		/**********
		 * Get Permission Name from control group, using Pec_uid (parameter)
		 ****/
		if ($pecUid) {
			$param_ary	= array('pec-uid' => $pecUid);
			$t2Key		= 'Q_getPerInfoFromPec';
			$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, $t2Key);
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			return $exeResults->results_custom['PEC_NAME'][0];
		}
	}


	public function setUCellNumber($uCellNumber, $entUID, $pecUID = '', $perUID = '', $notes = '', $sess_entity = array())
	{
		Debug_Trace::traceFunc();
		/***
		 * Sets T2 custom data - SunTran U-Cell field, for both Entity and Permit.
		 * If $perUID set, then update the actual permission's custom U-Cell field.
		 * Also calls sellUcell function - gets an activation code from PARKING schema.
		 * Called from cyberpay_sa .php, and from Internal CR ucell_code .php
		 * Returns distributed $ACTIVATION_CODE, if poss.
		 */

		//---------------- set U-cell custom data field for ENTITY ---------------------
		$custKey = 'SUNGOEC'; // 'E' is for Entity, 'C' is for Cell phone, say it with me!
		//											($web_service, $cust_field_name, $uid_value, $cust_field_value)
		$CF = new InsertUpdateCustomFlds(self::$cust_fld_svc[$custKey], $custKey, $entUID, $uCellNumber);
		if ($GLOBALS['DEBUG_DEBUG']) echo $CF;
		$errCF .= $CF->ErrorDescription . "\n";

		if ($perUID) {
			//-------------------------- Now set U-cell custom data field for permit --------------------------
			// checkEnabled('T2 API Permission'):
			$custKey = 'SUNGOPC'; // 'P' is for Permit, 'C' is for Cell phone, c'mon ye'all know the words!
			//											($web_service, $cust_field_name, $uid_value, $cust_field_value)
			$CF = new InsertUpdateCustomFlds(self::$cust_fld_svc[$custKey], $custKey, $perUID, $uCellNumber);
			if ($GLOBALS['DEBUG_DEBUG']) echo $CF;
			if ($GLOBALS['DEBUG_DEBUG']) echo "\n\nInsertUpdateCustomFlds(" . self::$cust_fld_svc[$custKey] . ", $custKey, $perUID, $uCellNumber)\n\n";
			$errCF .= $CF->ErrorDescription . "\n";
		}

		if ($errCF && $GLOBALS['debug_debug'])
			echo '<div class="warning">Debug error: If you see this, check out Flex_permissions .php - error: ' . $errCF . '</div>';

		$ACTIVATION_CODE = '';
		if ($pecUID) {
			if ($GLOBALS['DEBUG_DEBUG']) {
				echo '<pre>' . __FILE__ . ':' . __LINE__ . ': sellUcell($pecUID, $uCellNumber, $notes, $sess_entity):<br>' . "sellUcell($pecUID, $uCellNumber, $notes, $sess_entity)";
				//	var_dump($ACTIVATION_CODE);
				echo '</pre>';
			}

			// Sell it in PARKING schema here:
			if (is_object($this))
				$ACTIVATION_CODE = $this->sellUcell($pecUID, $uCellNumber, $notes, $sess_entity);
			else
				$ACTIVATION_CODE = SellPermission::sellUcell($pecUID, $uCellNumber, $notes, $sess_entity);
		}
		return $ACTIVATION_CODE;
	}


	public function setSungoID($sungo, $entUID, $perUID = '', $free30day_uaid = '')
	{
		/***
		 * Set Sungo (U-pass) card number (custom data) for:
		 *		Entity (customer) in T2 and for the purchased permit.  'SUNGOE'
		 *		### NOT DOING THIS: And tie entUID to existing $sungo card number in PARKING.STREETCAR_UPASS.
		 * Called from cyberpay_sa .php (or from optform .php if $free30day_uaid set) upon successful Upass permit purchase.
		 * Called from select_permits .php when somebody wants payroll deduction on a U-Pass.
		 * If $entUID = -1, then ignore anything T2, and just update PARKING.STREETCAR_ stuff with dbkey ($free30day_uaid).
		 * returns $errCF error, if any
		 *
		 * See also getSungoID
		 */

		$sungo = preg_replace('/[^\d]/si', '', $sungo);

		$err = $errCF = '';

		if (!$sungo)
			$err = 'Note: We could not insert your Upass Card number. ' . CONTACT_CR;

		if ($entUID != -1) {
			//-------------------------- set sungo custom data for ENTITY -------------------------------
			// (We know that no other entity owns this $sungo (ID) because of function verifySungo in account/permit_functions .php)
			// checkEnabled('T2 API Permission'):
			$custKey = 'SUNGOE';
			//											($web_service, $cust_field_name, $uid_value, $cust_field_value)
			$CF = new InsertUpdateCustomFlds(self::$cust_fld_svc[$custKey], $custKey, $entUID, $sungo);
			if ($GLOBALS['DEBUG_DEBUG']) echo $CF;
			$errCF .= $CF->ErrorDescription . "\n";

			if ($perUID) {
				//---------------------------- Now set for permit ----------------------------------------
				// checkEnabled('T2 API Permission'):
				$custKey = 'SUNGO'; // Normally would have a 'P' at the end for Permit.
				//											($web_service, $cust_field_name, $uid_value, $cust_field_value)
				$CF = new InsertUpdateCustomFlds(self::$cust_fld_svc[$custKey], $custKey, $perUID, $sungo);
				if ($GLOBALS['DEBUG_DEBUG']) echo $CF;
				$errCF .= $CF->ErrorDescription . "\n";
			}
		}

		$tmp_uaid = $free30day_uaid ? $free30day_uaid : $_SESSION['entity']['UA_DBKEY'];


		$ent_or_dbkey = ($entUID == -1) ? $tmp_uaid : $entUID;


		//----------------------------------------- Now update PARKING DB.
		//************************************************************************************
		//	*  We are currently NOT letting customers enter a card number - they can only CONFIRM any existing card number within their T2 Entity.
		//  if (@!$dbP) $dbP = new database();  $qVars = array('upass_value'=>$sungo, 'entUID'=>$ent_or_dbkey);
		//  $sql_1 = "update PARKING.STREETCAR_UPASS set UPASS_ENT = :entUID where UPASS = :upass_value and UPASS_ENT is NULL";
		//  $dbP->sQuery($sql_1, $qVars);					  // Make sure record exists:
		//  $sql_2= "select UPASS_ENT from PARKING.STREETCAR_UPASS	where UPASS = :upass_value and UPASS_ENT = :entUID";	  $dbP->sQuery($sql_2, $qVars);
		//  if (!$dbP->results['UPASS_ENT'][0])  $err = 'Note: we could not insert your Upass Card number. ' . CONTACT_CR;
		//  include_once 'login_functions.php';		  // login_functions .php contains functions getEligible, OptInStatus, and debugNote.
		//  $opt_in_stat = OptInStatus($tmp_uaid);
		//  if ($GLOBALS['DEBUG_DEBUG']) { //	echo '$sql_1:'.$sql_1.'<br>'; //echo '$sql_2: '.$sql_2.'<br>'; //echo " 'upass_value'=>$sungo, 'entUID'=>$entUID<br>";
		//	  //	echo '$opt_in_stat:<pre>'.print_r($opt_in_stat,true).'</pre></div>';  }
		//  if (@$opt_in_stat['ID_CONF'])  { // This person has opted in for free 30-day pass; now lets see if U-pass NOT processed:
		//	  if (@!$opt_in_stat['PROCESSED'] && $tmp_uaid)	  {  // U-pass not 'processed' yet, so force as Processed in DB.
		//		  // (need to have the "ELIGIBLE = 1" part because there may be multiple records
		//		  // for this person where "ELIGIBLE = 0"	  $qVars = array('uaid'=>$tmp_uaid);
		//		  $query = "update PARKING.STREETCAR_OPT_IN set PROCESSED = 1   where ENTITY_ENT_TERTIARY_ID_UAID = :uaid and ELIGIBLE = 1";	  $dbP->sQuery($query, $qVars);	  }	  }

		if ($err)	echo "<div class='warning' align='center' style='padding:9px;'>" . $err . "</div>";
		return $errCF;
	}


	private function setPickupPer($pick_up_permit)
	{
		/***
		 * Private function, set custom data to know if customer is to pick up permit or not.
		 * return error if any
		 */
		if ($pick_up_permit) $pick_up_permit = '1';
		else $pick_up_permit = '0';
		$custKey = 'ENT_PICK_UP_PERMIT';
		//			($web_service, $cust_field_name, $uid_value, $cust_field_value)
		$CF = new InsertUpdateCustomFlds(self::$cust_fld_svc[$custKey], $custKey, $this->ENT_UID, $pick_up_permit);
		if ($GLOBALS['DEBUG_DEBUG']) echo $CF;
		return $CF->ErrorDescription;
	}
}



class GetPermissions extends Flex_Permissions
{
	/**********************************
	 * Get all owned active permits.
	 * PSL_UID_STATUS: 4 is "issued", 5 is "active" permit. ("issued" means they purchased it, and usually instantly goes into "active" status.)
	 * PNA_IS_IN_USE ( and pnr.PNA_IS_IN_USE = 1 ): is a checkbox in T2 titled "Is In Use" - located in Permit Number Range editing web page.
	 *		The T2 documentation for "Is In Use": Select 'Is In Use' to activate the permit number range. The permit number range must be activated
	 *		before permits in the range can be sold.
	 * Query Does not use the new rfidNumber and rfidReturn vars
	 ***/

	//------------- Input
	// ent_uid - REQUIRED
	// For busses - pec_uid's array (usually from GetAvailableBus->pec_uids) to further limit query - see bus_purchase.php
	public $limit_pcg_uids	= array();

	//------------- Output
	public $per_uids		= array(); // per_uids[n]			= PER_UID_PERMISSION
	public $pna_uids		= array(); // pna_uids[n]			= PNA_UID
	public $pec_uids		= array(); // pec_uids[n]			= PEC_UID
	public $per_numbers	= array(); // per_numbers[n]		= PER_NUMBER
	public $per_sold_date = array();

	// When you wipe out the payment plan, then the balance goes into PER_AMOUNT_DUE "Permit Amount Due" in t2.
	public $perAmountDue	= array(); // perAmountDue[n]	= PER_AMOUNT_DUE
	public $pnaDesc		= array(); // description
	public $pecName		= array(); // Control Group name (more speciffic than pnaDesc)  pecName[pec_uid]	= description
	public $pnaPrefix		= array(); // not used in code I don't think.

	public $web_showable	= array(); // cud_value
	public $web_sellable	= array(); // cud_value

	//public $pnaUids		= array(); // pnaUids[pec_uid]	= PNA_UID
	// public $rfidNumber	= array(); //
	// public $rfidReturn	= array(); // not used in code I don't think.

	public $flex_function	= 'GetPermissions';

	public function __construct($entUID, $limit_pcg_uids = array(), $get_only_active = true)
	{
		Debug_Trace::traceFunc();

		$this->ENT_UID				= $entUID;
		$this->limit_pcg_uids	= $limit_pcg_uids; // Only get these pcg_uids (ignore all others)

		if ($get_only_active) // ######## ALWAYS TRUE
		{
			// See nots at beginning of this class.
			$param_ary	= array('entity uid' => $this->ENT_UID);

			//##########################################
			$t2Key		= 'Q_GetPermissions_5';
			$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			$tmpResults1 = $exeResults->results_custom;
			//##########################################
			//	$t2Key		= 'Q_GetPermissions';
			//	$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));

			foreach ($tmpResults1['PEC_UID'] as $k => $aPec) {
				if (sizeof($this->limit_pcg_uids) && !in_array($aPec, $this->limit_pcg_uids))
					continue;
				$this->pnaPrefix[]		= $tmpResults1['PNA_SERIES_PREFIX'][$k];
				$this->pnaDesc[]			= $tmpResults1['PNA_SHORT_DESCRIPTION'][$k];
				$this->pecName[]			= $tmpResults1['PEC_NAME'][$k];
				$this->pna_uids[]			= $tmpResults1['PNA_UID_PER_NUM_RANGE'][$k];
				$this->per_uids[]			= $tmpResults1['PER_UID'][$k];
				$this->per_numbers[]		= $tmpResults1['PER_NUMBER'][$k];
				$this->pec_uids[]			= $tmpResults1['PEC_UID'][$k];
				$this->per_sold_date[]	= date('Ymd', strtotime($tmpResults1['PER_SOLD_DATE'][$k])); // YYYYMMDD
				$this->perAmountDue[]	= $tmpResults1['PER_AMOUNT_DUE'][$k];
				//$this->PSL_UID_STATUS[]		= $tmpResults1['PSL_UID_STATUS'][$k];
				//$this->rfidNumber[]		= $tmpResults1['PER_RFID_NUMBER'][$k];
				//$this->rfidReturn[]		= $tmpResults1['PER_RFIDRETURN'][$k];

				// Remember, to make it in this loop means this permit is active: PSL_UID_STATUS: 4 "issued" or 5 "active" - ???
			}
		} else {
			/*** $get_only_active will ALWAYS be true, so this is useless.
			$searchQuery = "select pnr.PNA_SERIES_PREFIX, pnr.PNA_SHORT_DESCRIPTION,
					pv.PNA_UID_PER_NUM_RANGE, per.ENT_UID_ENTITY, per.PER_UID_PERMISSION, pv.PER_NUMBER,
					pv.PEC_UID_PERM_CONTROL_GROUP, PER_EFFECTIVE_START_DATE, PER_EFFECTIVE_END_DATE, PER_SOLD_DATE, pv.PER_AMOUNT_DUE
				from FLEXADMIN.PER_ENT_REL per inner join FLEXADMIN.PERMISSION_VIEW pv on per.PER_UID_PERMISSION = pv.PER_UID
		        inner join FLEXADMIN.PERMISSION_NUMBER_RANGE pnr on pv.PNA_UID_PER_NUM_RANGE = pnr.PNA_UID
				where per.ENT_UID_ENTITY=:entUID and pnr.PNA_IS_IN_USE = 1 " . $sqlAnd . " order by per.PER_UID_PERMISSION"; ***/
		}
	}
}


class GetRFID extends Flex_Permissions
{
	/**********
	 * Gets any owned I-Permits (RFID) via EntUid.
	 * Get the status':
	 *		PSL_UID_STATUS of i-permit: 4 is "issued", 5 is "active" - this would be renewable, see $renewable_Ipermit.
	 *		PSL_UID_STATUS of 6 "expired" - TODO: Need to make sure the status of the Garage Permit itself which owns the
	 *			RFID number, that it too must be expired (see GetRFID_OLD).  If so then cyber_pay .php is to prefix an 'X'
	 *			in the old garage permit's RFID field - using setRFID function.
	 * See also flex_ws/general_scripts .php
	 ****/

	//-------- Input
	public $ent_uid	= '';

	//-------- Output
	// Data for a single, renewable i-permit - where the end date will be extended in cyberpay_sa .php
	public $renewable_Ipermit	= array('rfid' => '', 'per_num' => '', 'per_uid' => '', 'status' => '', 'start_date' => '');
	// All owned I-permits:
	public $rfids			= array();	// same as $per_numbers.
	public $per_numbers	= array();
	public $per_uids		= array();
	public $per_status	= array();
	public $start_dates	= array();
	//public $pna_uids		= array();
	//public $pec_uids		= array();

	public function __construct($entUID)
	{
		Debug_Trace::traceFunc();
		if ($entUID) {
			$this->ent_uid = $entUID;
			$param_ary	= array('Entity UID' => $this->ent_uid);
			$t2Key		= 'Q_GetRFID_IPermit'; // In this query, Permit Type is "RFID Inventory "
			$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
			$eResults	= $exeResults->results_custom;
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			foreach ($eResults['PER_UID'] as $k => $aPec) {
				$this->rfids[]				= $eResults['PER_NUMBER'][$k];
				$this->per_numbers[]		= $eResults['PER_NUMBER'][$k];
				$this->per_uids[]			= $eResults['PER_UID'][$k];
				$this->per_status[]		= $eResults['PSL_UID_STATUS'][$k];
				$this->start_dates[]		= $eResults['PER_EFFECTIVE_START_DATE'][$k];
				if ($eResults['PSL_UID_STATUS'][$k] == 4 || $eResults['PSL_UID_STATUS'][$k] == 5) {
					$this->renewable_Ipermit['rfid']			= $eResults['PER_NUMBER'][$k];
					$this->renewable_Ipermit['per_num']		= $eResults['PER_NUMBER'][$k];
					$this->renewable_Ipermit['per_uid']		= $eResults['PER_UID'][$k];
					$this->renewable_Ipermit['status']		= $eResults['PSL_UID_STATUS'][$k];
					$this->renewable_Ipermit['start_date']	= $eResults['PER_EFFECTIVE_START_DATE'][$k];
				}
			}
		}
	}


	public function IsActiveRFID()
	{
		/***
		 * See if there is an Active RFID.
		 * See if there is a 5 (Active) in $this->psl_uid_gar array, and make sure it's a recently purchased one.
		 * Probably will never need this because a person can't buy two garage permits.
		 * returns true or false.
		 */
	}

	private function RenewableRFID()
	{
		/***
		 * Find RFID which may be renewable, and put data into renewable_Ipermit array - see notes above.
		 */
		$k_n = -100;
		$issued_or_active = false;
		foreach ($this->rfid_gar as $k => $v) {
			if (preg_match('/X$/si', $this->rfid_gar[$k])) {
				continue;
			} else if ($this->amt_due_gar[$k] == 0 || !$this->amt_due_gar[$k]) {
				// 4 = issued, 5 = active
				if ($this->psl_uid_gar[$k] == 4 || $this->psl_uid_gar[$k] == 5) {
					$k_n = $k;
					$issued_or_active = true;
					break;
				} else if ($this->psl_uid_gar[$k] == 6) {
					//	if (preg_match('/^'.preg_quote($this->search_exp_date).'.*$/si', $this->exp_date_gar[$k]))
					//	{
					//		$k_n = $k;
					//		break;
					//	}
				}
			}
		}
		if ($k_n >= 0) {
			// found a renewable I-Permit (note: garage permit data also present).
			$IPer_info = new PerNumToPerUID('I' . $this->rfid_gar[$k_n]);
			if ($GLOBALS['DEBUG_DEBUG']) echo $IPer_info;
			$this->renewable_Ipermit['per_uid']		= $IPer_info->per_uid;
			$this->renewable_Ipermit['start_date']	= $IPer_info->date_start;
			// The following is the same data for the garage permit AND Ipermit
			$this->renewable_Ipermit['rfid']			= $this->rfid_gar[$k_n];
			$this->renewable_Ipermit['per_uid_gar'] = $this->per_uid_gar[$k_n];
			$this->renewable_Ipermit['issued_or_active'] = $issued_or_active;
		}
	}
}



class sellAnIPermit
{
	/***
	 * Sell (OR EXTEND) an i-permit - FREE.
	 * On success returns empty string, on fail sets $this->logNoteErr.
	 * Set the CatCard cutom field (within the actual sold permit)
	 */

	public $logNoteErr = '';

	public function __construct($ENT_UID, $CATCARD_ID, $soldPer, $perAwarded)
	{
		Debug_Trace::traceFunc();
		// no permit custom field: too slow in t2

		if (checkEnabled('I-Permits In Permit RFID')) {
			// Set the CatCard cutom field within the actual sold permit - returns an error if any
			$tmpError = $soldPer->setCatcardID($CATCARD_ID, $soldPer->PERMISSION_UID);
			if ($tmpError) {
				if ($GLOBALS['DEBUG_DEBUG'])
					echo "<div style='font-size:1.2em; border:1px solid orange; padding:1px; margin:4px;'>@@@@@@@@@@@@@@@ WARNING: $tmpError</div>";
			}
		}

		if (checkEnabled('I-Permits')) {
			/***
			 * SET The RFID (sell I-Permit first then get RFID).
			 * See if already has an RFID.  If so then just extend the expiration date of the I-Permit to
			 *		match that of the sold permit.
			 * To see if already has an rfid: PER_STATUS_LKP.PSL_UID valuse 4, 5, or 6.
			 *		Problem with 6. Must be 6 AND the expiration date for the I-Permit (permit year 13/14)
			 *		has to be a specific date of '8/15/2014', and the Permit Amount Due must be $0.00.
			 *		(For permit year 14/15, the expire date will be '8/14/2015'
			 * See also flex_ws/general_scripts .php
			 */

			$ownedRFIDs = new GetRFID($ENT_UID);
			if ($GLOBALS['DEBUG_DEBUG']) echo $ownedRFIDs;
			//OLD: if ($GLOBALS['DEBUG_DEBUG']) echo '**** Search date for owned RFID: GetRFID::search_exp_date = '.$ownedRFIDs->search_exp_date.'<br>';

			if ($ownedRFIDs->renewable_Ipermit['per_num']) {
				/***
				 * Customer Has a renewable RFID (I-permit)
				 * and extend the expire date of the existing I-Permit ($ownedRFIDs->renewable_Ipermit['per_uid'])
				 * to match the currently purchased garage permit: $perAwarded['expire_date'].
				 */
				// Get rid of the 'I' in front of the permission number.
				$RFID_num	= preg_replace('/[^\d]/si', '', $ownedRFIDs->renewable_Ipermit['per_num']);

				//############# currently this will never be 6. ##############
				if (!$ownedRFIDs->renewable_Ipermit['status'] == 6) {
					// no permit custom field: too slow in T2
					if (checkEnabled('I-Permits In Permit RFID')) {
						// Put an X in the Garage Permit's RFID field - note the 'per_uid_gar'
						$RFID_num_X	= $RFID_num . 'X';
						$this->logNoteErr .= $soldPer->setRFID($RFID_num_X, $ownedRFIDs->renewable_Ipermit['per_uid_gar']);
					}
				}
				// Extend end_date -- no change in start date, its just a required field.
				$date_start		= $ownedRFIDs->renewable_Ipermit['start_date'];
				$extend_date	= $perAwarded['expire_date'];
				$extendIPermit	= new EditPermitDates($ownedRFIDs->renewable_Ipermit['per_uid'], $date_start, $extend_date);
				if ($GLOBALS['DEBUG_DEBUG']) echo $extendIPermit;
			} else if (checkEnabled('New-I-Permits')) {
				// Sell (assign) a free I-permit (RFID permit) and assign that RFID to the RFID field within the actual garage permit.
				$rfids = new GetAvailableIPermit();
				if ($GLOBALS['DEBUG_DEBUG']) echo $rfids;
				foreach ($rfids->pna_uids as $tk => $tv) {
					// ######## SINGLE LOOP - MAY NEED TO TRY TO RESERVE THE NEXT IF THIS ONE FAILS, FOR NOW JUST BREAK.
					$IPermit = new ReservePermission('', $rfids->pna_uids[$tk], $rfids->pec_uids[$tk], $ENT_UID);
					if ($GLOBALS['DEBUG_DEBUG']) echo $IPermit;
					if (!$IPermit->PERMISSION_NUMBER)
						$this->logNoteErr .= "IPermit ERROR: ReservePermission('', " . $rfids->pna_uids[$tk] . ", " . $rfids->pec_uids[$tk] . ", $ENT_UID) ";

					// ($perUID, $pcgUID, $facUID, $entUID, $vehUID, $pick_up_permit, $amtPaid='', $pay_plan_tmpl_uid=0, $numPay='', $amtPerPay='', $payPlanStart='', $testSell=false, $payTypeUID='', $note='')
					$sell_IPermit = new SellPermission($IPermit->PERMISSION_UID, $IPermit->CONTROL_GROUP_UID, $IPermit->facUID, $ENT_UID, '', @$perAwarded['pick_up_permit'], '0.00', 0, '', '', '', false, 1);
					if (!$sell_IPermit->PERMISSION_UID)
						$this->logNoteErr .= "sell_IPermit ERROR: SellPermission(" . $IPermit->PERMISSION_UID . ", " . $IPermit->CONTROL_GROUP_UID . ", " . $IPermit->facUID . ", " . $ENT_UID . ", '', @" . $perAwarded['pick_up_permit'] . ", '0.00', '', '', '', '', false, 1) ";

					// Get rid of the 'I' in front of the permission number.
					$RFID_num	= preg_replace('/[^\d]/si', '', $IPermit->PERMISSION_NUMBER);
					if ($GLOBALS['DEBUG_DEBUG']) {
						echo $sell_IPermit;
						echo '<small>$RFID_num:' . $RFID_num . '<br>';
						echo 'Not using Permit Number in sellPermission class (above): here is the reserved per num: ';
						echo $IPermit->PERMISSION_NUMBER . '<br>';
						echo '</small>';
					}
					break;
				}
			} else {
				$RFID_num = 'no_new';
			}

			if ($RFID_num) {
				// no permit custom field: too slow in T2
				if (checkEnabled('I-Permits In Permit RFID')) {
					// Now assign the sold RFID (permit number less the 'I') to
					// the custom RFID field within the actual sold garge permit
					$this->logNoteErr .= $soldPer->setRFID($RFID_num, $soldPer->PERMISSION_UID);
				}
			} else if ($RFID_num != 'no_new') {
				$this->logNoteErr .= 'NO I-PERMIT SOLD OR EXTENDED: ' . $perAwarded['perNumber'] . '. PER_UID: ' . $perAwarded['perUID'];
				new event_log('permit_transaction', 'error', $ENT_UID, '', $perAwarded['perNumber'], $this->logNoteErr);
			} else if ($RFID_num == 'no_new') {
				$RFID_num = '';
			}
		}
	}
}



class GetSuntran extends Flex_Permissions
{
	/**********
	 * Get the ucell # AND sungo ID from custom fields in Entity:
	 *		SunTran SunGO Pass and SunTran U-Cell
	 ****/

	//-------- Input
	public $ent_uid	= ''; // constructor parameter

	//-------- Output
	public $sungoID		= ''; // Custom field SUNGOE -  Entity. regular sun tran card number.
	public $uCellNumber	= ''; // Custom field SUNGOEC - Entity. U-Cell (SUNGOPC is Permission U-Cell)

	public function __construct($entUID)
	{
		Debug_Trace::traceFunc();

		if ($entUID) {
			$this->ent_uid = $entUID;
			$param_ary	= array('Entity UID' => $this->ent_uid);
			$t2Key		= 'Q_GetSuntran';
			$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			// That's right, this sets two PHP vars to two different [Entity] custom data values - sungo card and u-cell.
			$this->sungoID			= @$exeResults->results_custom['ENT_SUNGOE'][0];
			$this->uCellNumber	= @$exeResults->results_custom['ENT_SUNGOEC'][0];
		}
	}
}



class GetAvailableIPermit extends Flex_Permissions
{
	/**********************************
	 * Make sure we have I-Permit (RFID Permit) available.
	 * Returns the PNA_UID via the PPL_UID.
	 ***/

	//------------- Input
	// IPermit Passes - for live db only. Should always be 'xxx', year by year. (see Insert/Edit Permit Number Ranges, Permit Type)
	public $ipermit_ppl_uid	= 7016; // Regular Expression '|' means OR.  (see T2 - Insert/Edit Permit Number Ranges / Permit Type dropdown)
	// for 'Q_GetAvailableIPermit_multi': public $ipermit_pna_uid = '7893|7897'; // Regular Expression '|' means OR.  '7893|7891|7892|7893|7894|7895|7896|7897|7898|7899|7900|7901'

	//------------- Output
	public $pnaDesc	= array(); // pna_uids[n] = PNA_SHORT_DESCRIPTION
	public $pec_uids	= array(); // pec_uids[n] = PEC_UID
	public $pna_uids	= array(); // pna_uids[n] = PNA_UID


	public function __construct()
	{
		Debug_Trace::traceFunc();
		$param_ary	= array('IPermit ppl uid' => $this->ipermit_ppl_uid); // 'number range' => $this->ipermit_pna_uid
		$t2Key		= 'Q_GetAvailableIPermit_2';
		$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;

		foreach ($exeResults->results_custom['PEC_UID'] as $k => $aPec) {
			$this->pnaDesc[]	= $exeResults->results_custom['PNA_SHORT_DESCRIPTION'][$k];
			$this->pec_uids[]	= $exeResults->results_custom['PEC_UID'][$k];
			$this->pna_uids[]	= $exeResults->results_custom['PNA_UID'][$k];
		}
	}
}



class GetAvailableBicycle extends Flex_Permissions
{
	/**********************************
	 * Make sure we have bicycle permits available.
	 * Returns the PNA_UID via the PPL_UID.
	 ***/

	//------------- Input
	// Bicycle Passes - for live db only. Should always be 2003, year by year. (see T2: Insert/Edit Permit Number Ranges, Permit Type)
	public $bicycle_ppl_uid	= 2003; // (see T2 - Insert/Edit Permit Number Ranges / Permit Type dropdown)

	//------------- Output
	public $pec_uids	= array(); // pec_uids[n] = PEC_UID
	public $pna_uids	= array(); // pna_uids[n] = PNA_UID


	public function __construct()
	{
		Debug_Trace::traceFunc();
		$param_ary	= array('bicycle ppl uid' => $this->bicycle_ppl_uid);
		$t2Key		= 'Q_GetAvailableBicycle';
		$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;

		foreach ($exeResults->results_custom['PEC_UID'] as $k => $aPec) {
			$this->pec_uids[]	= $exeResults->results_custom['PEC_UID'][$k];
			$this->pna_uids[]	= $exeResults->results_custom['PNA_UID'][$k];
		}
	}
}




class GetAvailableBus extends Flex_Permissions
{
	/**********************************
	 * Get all Bus or Motorcycle pec uids, based on Permit Number Range "Permit Type" of "Bus Pass",
	 * which is PERMISSION_NUMBER_RANGE.PPL_UID_PERMISSION_TYPE = 2002.
	 * Further refined in GetPermissionPriceFixed_bus, to make sure dad_web_sellable_true ("ALLOW UofA Web Sales External")
	 ****/

	//------------- Input
	// Entity UID

	// For PPL uid, see T2 Lookup Table Mgmt / Physical Permit Type
	public $bus_ppl_uid					= 2002; // subsidized ($regular)
	public $bus_ppl_uid_unsub			= 7008; // unsubsidized
	public $bus_ppl_uid_cell			= 7022; // subsidized cell pass ($regular)
	public $bus_ppl_uid_cell_unsub	= 7023; // unsubsidized cell pass
	// public $bus_ppl_uid_phx			= 7009; // Phoenix - future

	public $mc_ppl_uid					= 7006; // MC ($regular)
	public $mc_ppl_uid_discount		= 7020; // DISCOUNTED MC.

	/************* For TEST database only, use series prefix ##############*/
	//	See Permit Number Ranges - http://128.196.6.197/PowerPark/config/permission/numberRangeView.aspx
	//	public $test_pna = array(
	//		  /*############ For TEST database only ##############*/
	//		  '13SM', '13SMX', '12SS', '13SS', '13SSX', '13AN', '13ANX'		 );


	//------------- Output
	public $pec_uids		= array(); // pec_uid[n]				= PEC_UID
	public $isCellPass	= array(); // isCellPass[PEC_UID]	= true if PPL_UID_PERMISSION_TYPE is bus_ppl_uid_cell_unsub or bus_ppl_uid_cell
	public $ppl_uids		= array(); // ppl_uids[PEC_UID]		= PPL_UID_PERMISSION_TYPE
	// available for sale dates
	public $PEC_AVAILABLE_START_DATE	= array();
	public $PEC_AVAILABLE_END_DATE	= array();
	public $PEC_EFFECTIVE_DATE	= array();
	public $PEC_EXPIRATION_DATE	= array();


	public function __construct($regular = true, $is_bus = true)
	{
		Debug_Trace::traceFunc();
		if ($is_bus) {
			// bus pass - card or cell pass, subsidized or non-sub. (if $regular set, then these two will be subsidized.)
			$ppl_uid			= $regular ? $this->bus_ppl_uid			: $this->bus_ppl_uid_unsub;
			$ppl_uid_cell	= $regular ? $this->bus_ppl_uid_cell	: $this->bus_ppl_uid_cell_unsub;
		} else {
			// Motorcycle permit
			$ppl_uid = $regular ? $this->mc_ppl_uid : $this->mc_ppl_uid_discount;
		}

		// Note: The array keys will be translated to Param1, Param2.
		$param_ary	= array('subsidized or non-sub card ppl_uid' => $ppl_uid, 'subsidized or cell pass ppl_uid' => $ppl_uid_cell);
		$t2Key		= 'Q_GetAvailableBus';
		$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));

		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;

		//	if ($GLOBALS['jody']) {
		//		echo '<pre><hr><hr>'; var_dump($exeResults->results_custom);		echo '</pre><hr><hr>';
		//	}

		foreach ($exeResults->results_custom['PEC_UID'] as $k => $aPec) {
			$aPecUid								= $exeResults->results_custom['PEC_UID'][$k];
			$this->pec_uids[]					= $aPecUid;
			$this->ppl_uids[$aPecUid]		= $exeResults->results_custom['PPL_UID_PERMISSION_TYPE'][$k];

			if ($this->ppl_uids[$aPecUid] == $this->bus_ppl_uid_cell)
				$this->isCellPass[$aPecUid] = 1;
			else if ($this->ppl_uids[$aPecUid] == $this->bus_ppl_uid_cell_unsub)
				$this->isCellPass[$aPecUid] = 1;
			else
				$this->isCellPass[$aPecUid] = 0;

			$exeResults->setMoreCustom('PEC_AVAILABLE_START_DATE', 'date_1');
			$exeResults->setMoreCustom('PEC_AVAILABLE_END_DATE', 'date_1');
			$exeResults->setMoreCustom('PEC_EFFECTIVE_DATE', 'date_1');
			$exeResults->setMoreCustom('PEC_EXPIRATION_DATE', 'date_1');
			$this->PEC_AVAILABLE_START_DATE[$aPecUid]	= $exeResults->results_custom['PEC_AVAILABLE_START_DATE'][$k];
			$this->PEC_AVAILABLE_END_DATE[$aPecUid]	= $exeResults->results_custom['PEC_AVAILABLE_END_DATE'][$k];
			$this->PEC_EFFECTIVE_DATE[$aPecUid]			= $exeResults->results_custom['PEC_EFFECTIVE_DATE'][$k];
			$this->PEC_EXPIRATION_DATE[$aPecUid]		= $exeResults->results_custom['PEC_EXPIRATION_DATE'][$k];

			//if ($GLOBALS['jody']) {
			//	echo '$this->isCellPass['.$aPecUid.']: '.$this->isCellPass[$aPecUid]. ' ===== '.$this->ppl_uids[$aPecUid].'<br>';
			//}
		}
	}
}



class GetPermissionPriceFixed_bus extends Flex_Permissions
{
	/************************************************************************************************
	 * @@@@@@@@@@@@@@@@    Temporary, bus AND motorcycle   @@@@@@@@@@@@@@@@@
	 * to find static price for bus passes
	 * Only to show FIXED price for drop-down to show prices.
	 * ####### DON'T USE THIS PRICE FOR PERMIT SALES -- for real sale price see class Reserver Permit to get price ###########
	 ***********************************/

	//------------- Input
	// $pcg_uid - array (usually from GetAvailableBus->pec_uids) for busses - see bus_purchase.php
	// If 'T2 API...' is true, then use T2 API class ExecuteQuery in Flex_Misc .php.

	//------------- Output
	public $pecRatesFixed	= array(); // pecRatesFixed[pec_uid]	= PFE FIXED
	public $pecProrateDate	= array(); // pecProrateDate[pec_uid]	= PFE_FIXED_BEGIN_PROR_DATE
	public $pecNowProrated	= array(); // pecNowProrated[pec_uid]	= TRUE, if current date is on or after PFE_FIXED_BEGIN_PROR_DATE
	public $pecName			= array(); // Control Group name (more speciffic than pnaDesc)  pecName[pec_uid] = Bus description.
	public $pnaUids			= array(); // pnaUids[pec_uid]			= PNA_UID
	public $pnaPrefixes		= array(); // pnaPrefixes[pec_uid]		= PNA_SERIES_PREFIX (example 13SSX)

	public function __construct($pcg_uid = array())
	{
		Debug_Trace::traceFunc();

		if (sizeof($pcg_uid)) {
			$t2Key		= 'Q_GetPermissionPriceFixed_bus';
			$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key]);
			$exeResults->setMoreCustom('PFE_FIXED_BEGIN_PROR_DATE', 'date_1');

			//	if ($GLOBALS['DEBUG_DEBUG']) {
			//		echo $exeResults;
			//		echo '<pre>$pcg_uid:<br>';
			//		var_dump($pcg_uid);
			//		echo '</pre>';
			//		echo '<pre>$exeResults->results_custom[PEC_UID]:<br>';
			//		var_dump($exeResults);
			//		echo '</pre>';
			//	}

			// Filter records that contain PEC_UID in array $pcg_uid.
			$tmpResults2 = array();
			foreach ($exeResults->results_custom['PEC_UID'] as $k => $aPec) {
				if (in_array($aPec, $pcg_uid)) {
					$tmpResults2['PEC_UID'][]							= $exeResults->results_custom['PEC_UID'][$k];
					$tmpResults2['PNA_UID_PER_NUM_RANGE'][]		= $exeResults->results_custom['PNA_UID_PER_NUM_RANGE'][$k];
					$tmpResults2['PEC_NAME'][]							= $exeResults->results_custom['PEC_NAME'][$k];
					$tmpResults2['PFE_RATE_AMOUNT_FIXED'][]		= $exeResults->results_custom['PFE_RATE_AMOUNT_FIXED'][$k];
					$tmpResults2['PFE_FIXED_BEGIN_PROR_DATE'][]	= $exeResults->results_custom['PFE_FIXED_BEGIN_PROR_DATE'][$k];
					$tmpResults2['PNA_SERIES_PREFIX'][]				= $exeResults->results_custom['PNA_SERIES_PREFIX'][$k];
				}
			}
			$this->setPecVars(sizeof($tmpResults2['PEC_UID']), $tmpResults2);
		}
	}

	private function setPecVars($rows, $results)
	{
		$tmp_groupPrices = array();
		for ($i = 0; $i < $rows; $i++) {
			$a_pec_uid = $results['PEC_UID'][$i];
			if ($results['PFE_RATE_AMOUNT_FIXED'][$i]) {
				if (@$tmp_groupPrices[$a_pec_uid]) {
					if ($GLOBALS['DEBUG_DEBUG']) {
						// TODO: make this go into the debug log.
						echo '<div style="color:red; font-size:14px; font-weight:bold;">Oh no, two PEC IDs found: ' . $a_pec_uid . '! Well, then......</div>';
					}
				} else {
					$tmp_groupPrices[$a_pec_uid] = $results['PFE_RATE_AMOUNT_FIXED'][$i];
				}
			}
			$this->pecRatesFixed[$a_pec_uid]	= $results['PFE_RATE_AMOUNT_FIXED'][$i];
			$this->pecProrateDate[$a_pec_uid] = $results['PFE_FIXED_BEGIN_PROR_DATE'][$i];

			$this->pecNowProrated[$a_pec_uid] = '';
			if ($results['PFE_FIXED_BEGIN_PROR_DATE'][$i]) {
				$this->pecNowProrated[$a_pec_uid] = withinTimeframe($results['PFE_FIXED_BEGIN_PROR_DATE'][$i], '2099-01-01') ? true : false;
			}
			$this->pecName[$a_pec_uid]			= $results['PEC_NAME'][$i];
			$this->pnaUids[$a_pec_uid]			= $results['PNA_UID_PER_NUM_RANGE'][$i];
			$this->pnaPrefixes[$a_pec_uid]	= $results['PNA_SERIES_PREFIX'][$i];
		}
	}
}



//********************************************************************************************
class EditPermitDates extends Flex_Permissions
{
	/*
	 * DOES NOT UPDATE TIME! - T2 will fix soon on hosted V7.7
	 * Through Flex you can change the time, just not here, yet.
	 * see http://128.196.6.197/PowerParkWS_N7/T2_FLEX_PERMISSIONS.asmx?op=EditPermitDates
	 * OR  http://128.196.6.222/PowerParkWS_N7/T2_Flex_Permissions.asmx?op=EditPermitDates
	 */
	public $xml_request	= 'SOAP 1.1'; // Default in config .php is HTTP POST

	public $return_xml	= ''; // massaged return_page

	//-------------- Input params
	//	required - any date and time format should do - converts to Oracle style (i.e. 2014-12-22T09:00:00)
	// The time is truncated by t2, but use it just in case of future enhancements
	public $EffectiveStartDate		= '';
	public $EffectiveEndDate		= '';


	//--------------- Output
	//	public $EditPermitDatesResult	= '';

	public $flex_function			= 'EditPermitDates';

	public function __construct($per_uid, $EffectiveStartDate, $EffectiveEndDate)
	{
		Debug_Trace::traceFunc();
		parent::__construct();

		$this->PERMISSION_UID		= $per_uid;

		// Convert dates to oracle-style (i.e. 2014-12-22T09:00:00)
		if ($EffectiveStartDate)
			$this->EffectiveStartDate	= preg_match('/\dT\d/', $EffectiveStartDate)	? $EffectiveStartDate	: date('Y-m-d\TH:i:s', strtotime($EffectiveStartDate));
		else
			$this->EffectiveStartDate	= ' ';
		if ($EffectiveEndDate)
			$this->EffectiveEndDate		= preg_match('/\dT\d/', $EffectiveEndDate)		? $EffectiveEndDate		: date('Y-m-d\TH:i:s', strtotime($EffectiveEndDate));

		$this->send_xml();
	}

	protected function get_xml()
	{
		// Note how this is a SOAP xml:  $this->xml_request == 'SOAP 1.1'
		$this->xml_data =
			'<' . $this->flex_function . ' xmlns="http://www.t2systems.com/">
			<loginInfo>
			  <UserName>'	. $this->WS_CONN['WS_user']	. '</UserName>
			  <Password>'	. $this->WS_CONN['WS_pass']	. '</Password>
			  <Version>'	. $this->xml_version				. '</Version>
			</loginInfo>
		<parameter>
		  <PermissionUid>' . $this->PERMISSION_UID . '</PermissionUid>
		  <EffectiveStartDate>' . $this->EffectiveStartDate . '</EffectiveStartDate>
		  <EffectiveEndDate>' . $this->EffectiveEndDate . '</EffectiveEndDate>
		</parameter>
		</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);

		return $this->post_data;
	}
}
//*****************************************************************************************/




class PerNumToPerUID extends Flex_Permissions
{
	/**********
	 * Gets permission uid AND much of its other data, via a Permit Number ($perNum)
	 ****/

	//-------- Input
	public $per_number = '';

	//-------- Output
	public $per_uid		= '';
	public $pec_uid		= '';
	public $date_start	= ''; // PER_EFFECTIVE_START_DATE
	public $date_expire	= ''; // PER_EFFECTIVE_END_DATE

	public function __construct($perNum)
	{
		Debug_Trace::traceFunc();
		if ($perNum) {
			$this->per_number = $perNum;
			// In the T2 query this will be: PER_NUMBER = $this->per_number
			$param_ary	= array('Permit Num' => $this->per_number);
			$t2Key		= 'Q_PerNumToPerUID';
			$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			$this->per_uid		= $exeResults->results_custom['PER_UID'][0];
			$this->pec_uid		= $exeResults->results_custom['PEC_UID'][0];
			$this->date_start	= $exeResults->results_custom['PER_EFFECTIVE_START_DATE'][0];
			$this->date_expire = $exeResults->results_custom['PER_EFFECTIVE_END_DATE'][0];
		}
	}
}






class PerNumToPecUID extends Flex_Permissions
{
	/**********
	 * Gets pec uid AND much of its other data, via a Permit Number ($perNum).
	 * The permit param ($perNum) pretty much has to be live and web sellable for this to return anything.
	 * This class is more constrictive than PerNumToPerUID
	 ****/

	//-------- Input
	public $per_number = '';

	//-------- Output
	public $pec_uid		= array();
	public $per_uid		= array();
	public $pec_name		= array(); // PEC_NAME
	public $date_start	= array(); // PER_EFFECTIVE_START_DATE
	public $date_expire	= array(); // PER_EFFECTIVE_END_DATE

	public function __construct($perNum)
	{
		Debug_Trace::traceFunc();
		if ($perNum) {
			$this->per_number = $perNum;
			// In the T2 query this will be: PER_NUMBER = $this->per_number
			$param_ary	= array('Permit Num' => $this->per_number);
			$t2Key		= 'Q_PerNumToPecUID';
			$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			foreach ($exeResults->results_custom['PEC_UID'] as $i => $rAry) {
				$this->pec_uid[$i]		= $exeResults->results_custom['PEC_UID'][$i];
				// $this->per_uid[$i]		= $exeResults->results_custom['PER_UID'][$i];
				$this->pec_name[$i]		= $exeResults->results_custom['PEC_NAME'][$i];
				//	$this->date_start[$i]	= $exeResults->results_custom['PER_EFFECTIVE_START_DATE'][$i];
				//	$this->date_expire[$i]	= $exeResults->results_custom['PER_EFFECTIVE_END_DATE'][$i];
			}
		}
	}
}






/******************************************************************************************
 *******************************************************************************************
 *
 *									      OLDIES
 *
 *******************************************************************************************
 *******************************************************************************************/


/********** xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
class GetRFID_OLD extends Flex_Permissions
{
	//	 * THIS FUNCTION NOT ACTIVE RIGHT NOW -- was used in cyberpay_sa .php
	//	 * Gets RFID numbers of customer.
	//	 * To see if already has an rfid (RenewableRFID function):
	//	 * TODO (maybe): NEW WAY: Check if permit is inactive: PER_STATUS_LKP.PSL_UID > 5 .....
	//	 *	PER_STATUS_LKP.PSL_UID valuse 4, 5, or 6.
	//	 *		Problem with 6; must be 6 AND the expiration date for the I-Permit (for permit year 13/14) has
	//	 *		to be a specific date of '8/15/2014' ($search_exp_date), and the Permit Amount Due must be $0.00.
	//	 *		(For permit year 14/15, the expire date will be '8/14/2015'
	//	 * See also flex_ws/general_scripts .php
	// public $search_exp_date		= '2014-08-15';
	public $search_exp_date		= '2015-08-14';
	//-------- Input
	public $ent_uid	= '';
	//-------- Output
	public $renewable_Ipermit	= array('per_uid'=>'', 'rfid'=>'', 'start_date'=>'', 'per_uid_gar'=>'','issued_or_active'=>'');
	// This is the permission info of the Garage permit:
	private $per_uid_gar		= array();
	private $rfid_gar			= array();
	private $psl_uid_gar		= array();
	private $exp_date_gar	= array();
	private $amt_due_gar		= array();
	// private $start_date_gar	= array();
	public function __construct($entUID)
	{
		Debug_Trace::traceFunc();
		if ($entUID)
		{
			$this->ent_uid = $entUID;
			$param_ary	= array('Entity UID' => $this->ent_uid);
			$t2Key		= 'Q_GetRFID';
			$exeResults	= new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
			if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults;
			foreach ($exeResults->results_custom['PER_RFID_NUMBER'] as $k => $aPec)
			{
				$this->per_uid_gar[]		= $exeResults->results_custom['PER_UID'][$k];
				$this->rfid_gar[]			= $exeResults->results_custom['PER_RFID_NUMBER'][$k];
				$this->psl_uid_gar[]		= $exeResults->results_custom['PSL_UID_STATUS'][$k];
				$this->exp_date_gar[]	= $exeResults->results_custom['PER_EFFECTIVE_END_DATE'][$k];
				$this->amt_due_gar[]		= $exeResults->results_custom['PER_AMOUNT_DUE'][$k];
				// $this->start_date_gar[]	= $exeResults->results_custom['PER_EFFECTIVE_START_DATE'][$k];
			}
			if (sizeof($this->rfid_gar))
				$this->RenewableRFID();
		}
	}
	public function IsActiveRFID()
	{
		// See if there is an Active RFID.
		// See if there is a 5 (Active) in $this->psl_uid_gar array, and make sure it's a recently purchased one.
		// Probably will never need this because a person can't buy two garage permits.
		// returns true or false.

	}
	private function RenewableRFID()
	{
		// Find RFID which may be renewable, and put data into renewable_Ipermit array - see class notes above.

		$k_n = -100;
		$issued_or_active = false;
		foreach ($this->rfid_gar as $k => $v)
		{
			if (preg_match('/X$/si', $this->rfid_gar[$k]))
			{
				continue;
			}
			else if ($this->amt_due_gar[$k]==0 || !$this->amt_due_gar[$k])
			{
				// 3 = reserved (this should never be the case)
				if ($this->psl_uid_gar[$k]==3)
				{
					$k_n = $k;
					break;
				}
				// 4 = issued, 5 = active
				else if ($this->psl_uid_gar[$k]==4 || $this->psl_uid_gar[$k]==5)
				{
					$k_n = $k;
					$issued_or_active = true;
					break;
				}
				else if ($this->psl_uid_gar[$k]==6)
				{
					if (preg_match('/^'.preg_quote($this->search_exp_date).'.*$/si', $this->exp_date_gar[$k]))
					{
						$k_n = $k;
						break;
					}
				}
			}
		}
		if ($k_n >= 0)
		{
			// found a renewable I-Permit (note: garage permit data also present).
			$IPer_info = new PerNumToPerUID('I'.$this->rfid_gar[$k_n]);
			if ($GLOBALS['DEBUG_DEBUG']) echo $IPer_info;
			$this->renewable_Ipermit['per_uid']		= $IPer_info->per_uid;
			$this->renewable_Ipermit['start_date']	= $IPer_info->date_start;
			// The following is the same data for the garage permit AND Ipermit
			$this->renewable_Ipermit['rfid']			= $this->rfid_gar[$k_n];
			$this->renewable_Ipermit['per_uid_gar']= $this->per_uid_gar[$k_n];
			$this->renewable_Ipermit['issued_or_active']= $issued_or_active;
		}
	}
}
 ****/




/**********************************
class GetAvailablePBC extends Flex_Permissions {
	//------------- Input
	bus_ppl_uid
		//	PEC_UID	PEC_NAME												PNA_UID_PER_NUM_RANGE
		//	2565		13PBC1 PBC LOT 10001								2463
		//	2566		13PBC2 PBC LOT 10002								2464
		//	2567		13PBCW PBC-DISABLED LVL1 (PROOF REQUIRED)	2465
		// The PNA UID @ end of this url, for this PBC: http://128.196.6.197/PowerPark/config/permission/numberRangeEdit.aspx?id=2463
	//------------- Output
	public $pec_uids	= array(); // pec_uids[n]				= PEC_UID
	public function __construct($entUID, $limit_pcg_uids=array()) {
		Debug_Trace::traceFunc();
		$dbC = new database();
		$searchQuery = "select PEC_UID from FLEXADMIN.PERMISSION_CONTROL_GROUP pcg
				inner join FLEXADMIN.PERMISSION_NUMBER_RANGE pnr on pnr.PNA_UID = pcg.PNA_UID_PER_NUM_RANGE
				where pnr.PPL_UID_PERMISSION_TYPE = ".parent::bus_ppl_uid." and pnr.PNA_IS_IN_USE = 1";
		$dbC->sQuery($searchQuery, array('entUID'=>$this->ENT_UID));
		if ($GLOBALS['DEBUG_DEBUG']) $this->sqlHist['get_avail_bus'][] = $searchQuery;
		for ($i=0; $i<$dbC->rows; $i++) {
			$this->pec_uids[]	= $dbC->results['PEC_UID'][$i];
		}
	}
}
 **********************************/




/*********
class GetPermissionPrice extends Flex_Permissions
{
	// Input:
	// PERMISSION_UID		// Required
	//	CONTROL_GROUP_UID // Required unless reserving an Event permit (then PPQ_UID_PRESOLD_PEC_REL is required)

	// Output
	public $PERMISSION_PRICE = '';

	public $flex_function	= 'GetPermissionPrice';
	public function __construct($p_1, $p_2)
	{
		Debug_Trace::traceFunc();
		parent::__construct();
		$this->PERMISSION_UID		= $p_1;
		$this->CONTROL_GROUP_UID	= $p_2;

		// Call T2 function and set temp_return_vals.
		$this->send_xml();
		$this->PERMISSION_PRICE = $this->temp_return_vals['PERMISSION_PRICE'][0];
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('PERMISSION_UID',			$this->per_uid);
		$this->xml_data .= make_param('CONTROL_GROUP_UID',		$this->CONTROL_GROUP_UID);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}
 **********/



/**************** OLD WAY 2 ****************
$searchQuery = "SELECT DISTINCT
	FLEXADMIN.PERMISSION_FEE_VIEW.PEC_UID_PERM_CONTROL_GROUP,
	FLEXADMIN.PERMISSION_CONTROL_GROUP.PEC_NAME,
	FLEXADMIN.PERMISSION_FEE_VIEW.PFE_UID,
	FLEXADMIN.PERMISSION_FEE_VIEW.PFE_RATE_AMOUNT_FIXED
	FROM
	((FLEXADMIN.PERMISSION_FEE_VIEW INNER JOIN FLEXADMIN.PEC_ESL_ELIGIBILITY ON FLEXADMIN.PERMISSION_FEE_VIEW.PEC_UID_PERM_CONTROL_GROUP = FLEXADMIN.PEC_ESL_ELIGIBILITY.PEC_UID_PERM_CONTROL_GROUP)
	INNER JOIN FLEXADMIN.PERMISSION_CONTROL_GROUP ON FLEXADMIN.PEC_ESL_ELIGIBILITY.PEC_UID_PERM_CONTROL_GROUP = FLEXADMIN.PERMISSION_CONTROL_GROUP.PEC_UID)
	INNER JOIN FLEXADMIN.CUSTOM_DATA ON FLEXADMIN.PERMISSION_CONTROL_GROUP.PEC_UID = FLEXADMIN.CUSTOM_DATA.CUD_RECORD_UID
	WHERE (((FLEXADMIN.PERMISSION_FEE_VIEW.PFR_UID_FIXED_RATE_TYPE)=1) AND (" . parent::dad_uid_waitlist . ") AND ((FLEXADMIN.CUSTOM_DATA.CUD_VALUE)='1'))
	ORDER BY FLEXADMIN.PERMISSION_CONTROL_GROUP.PEC_NAME
	";
 ********************************************/



/********************************************************************************************

class GetUnitCostOfValueCredentialByPCG extends Flex_Permissions
{
	public $flex_function	= 'GetUnitCostOfValueCredentialByPCG';
	public $xml_version	= '1.1';
	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';

	// Input:
	public $Control_Group_UID =	''; // Required. The UID of the value credential control group to get the cost per unit from.
	public $UserName =				''; // Required.
	public $Password =				''; // Required.
	// CASH_DRAWER_UID =		''; // Required-hmmmmmm. Cashier Station UID.
	// ENT_UID =					''; // Required. Customer UID

	// Output
	// The value of the card after the number of units has been added.
	public $CostPerUnit =	'';

	public function __construct($p_1, $p_2)
	{
		Debug_Trace::traceFunc();
		parent::__construct();
		$this->CASH_DRAWER_UID			= $this->WS_CONN['WS_cashUID']; // hmmmmmm, this is nurmally very global.
		$this->UserName					= $this->WS_CONN['WS_user']; // hmmmmmm, this is nurmally very global.
		$this->Password					= $this->WS_CONN['WS_pass']; // hmmmmmm, this is nurmally very global.
		$this->Control_Group_UID		= $p_1;
		$this->ENT_UID						= $p_2;

		//xxx Call T2 function and set temp_return_vals.
		$this->send_xml();
		//xxx $this->CostPerUnit = $this->temp_return_vals['CostPerUnit'][0];
	}
	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function .	'>';
		$this->xml_data .= make_param('CASH_DRAWER_UID',			$this->CASH_DRAWER_UID);
		$this->xml_data .= make_param('UserName',						$this->UserName);
		$this->xml_data .= make_param('Password',						$this->Password);
		$this->xml_data .= make_param('Control_Group_UID',			$this->Control_Group_UID);
		$this->xml_data .= make_param('ENTITY_UID',					$this->ENT_UID);
		$this->xml_data .= "\n" . '</' . $this->flex_function .	'>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}


class GetPermissionPriceByPCG extends Flex_Permissions
{
	public $flex_function	= 'GetPermissionPriceByPCG';
	public $xml_version		= '1.2';
	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';
	// Input:
	//ENT_UID =						''; // Required. Customer UID
	public $PERMISSION_CTL_GRP_LIST =	''; // Required. List of Control Group UIDs
	//CASH_DRAWER_UID =				''; // Required-hmmmmmm. Cashier Station UID.
	public $PERMISSION_EFFECTIVE_DATE =	''; // Effective Date if needed for Control Group
	public $PERMISSION_EXPIRATION_DATE =''; // Expiration Date if needed for control group
	public $NUMBER_OF_UNITS =				'';
	public $PPQ_UID_PRESOLD_PEC_REL_LIST='';
	// Output
	// ($PERMISSION_PRICE) Price associated with Control Group UID, followed by the values for the applied taxes in a comma separated list like the following: PRICE,TAX2,TAX2,TAX3
	public $UID =	'';
	public function __construct($p_1, $p_2)
	{
		Debug_Trace::traceFunc();
		parent::__construct();
		$this->CASH_DRAWER_UID				= $this->WS_CONN['WS_cashUID']; // hmmmmmm, this is nurmally very global.
		$this->ENT_UID						= $p_1;
		$this->PERMISSION_CTL_GRP_LIST	= $p_2;
		$this->send_xml();
	}
	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function .	'>';
		$this->xml_data .= make_param('ENTITY_UID',					$this->ENT_UID);
		$this->xml_data .= make_param('PERMISSION_CTL_GRP_LIST',	$this->PERMISSION_CTL_GRP_LIST);
		$this->xml_data .= make_param('CASH_DRAWER_UID',			$this->CASH_DRAWER_UID);
		$this->xml_data .= "\n" . '</' . $this->flex_function .	'>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}
 *****************************************************************************************/


//public function setCustomData($dad_uid, $cud_value, $cud_record_uid, $debugSqlName='set_cust_data') {
//	/**** ------------------- OLD SCHOOL -----------------------
//	 *
//	 * Inserts or updates custom data into FLEXADMIN.CUSTOM_DATA table.
//	 * If record already exists in FLEXADMIN.CUSTOM_DATA - searching on $dad_uid and $cud_record_uid, then:
//	 *		Update the record with new data - with $cud_value
//	 *		Returns CUD_UID of the record (not the same as $cud_record_uid)		 */
//	if (checkEnabled('T2 API Permission')) {
//		exitWithBottom('ERROR: T2 API.');
//	} else {
//		// ------------------- OLD SCHOOL -----------------------
//	}
//}
