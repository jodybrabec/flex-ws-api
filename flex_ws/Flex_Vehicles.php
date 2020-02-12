<?php

require_once '/var/www2/include/flex_ws/config.php';

// Note: 12345 replaces checkEnabled('T2 API Vehicles')

abstract class Flex_Vehicles extends Flex_Funcs
{
	/**************************
	 * Documentation: http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=32
	 ***************************/

	public $flex_group		= 'T2_FLEX_VEHICLES';

	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';

	public $veh_or_bike		= ''; // are we dealing with "vehicle" or "bicycle"

	/***
	 * $t2_query[Query Name] = Query UID (QUERY_DEF.QDE_NAME => QUERY_DEF.QDE_UID)
	 * For use with API ExecuteQuery class (in Flex_Misc .php)
	 * Query UIDs and Query Names found here: http://128.196.6.197/PowerPark/qm/default.aspx
	 */
	public static $t2_query = array(
		'Q_GetVehicleAssociation'		=> 7486,
		'Q_GetVehicleInfo'				=> 7487,
		'Q_GetVehicleInfoFromPlate'	=> 10905,
		'Q_GetVehStyles'					=> 7488,
		'Q_GetVehColors'					=> 7489,
		'Q_getVehMakes'					=> 7490,
		'Q_getVehModels'					=> 7491,
	);

	protected function set_callback()
	{
		// Called from config .php  -- usually to set more vars besides those set by Flex_Funcs::setVars
	}
}


class InsertUpdateVehicle extends Flex_Vehicles
{
	/**********************************
	 * This method is used to insert a new vehicle record or to modify an existing vehicle record.
	 * If a vehicle UID is not passed, then the method will attempt to insert a new vehicle using the parameters passed into the method.
	 * If a vehicle UID is passed, then method will attempt to update an existing vehicle with the parameters passed into the method.
		During an update the vehicle license plate will not be modified If a value is passed in parameter LICENSE_PLATE for an
		update, it will be ignored, and the existing plate number will be retained on the vehicle. (If a new license plate number has been
		assigned to an existing vehicle, use this method to insert a record for the new record for the vehicle rather than attempting to
		update the existing record; the two records may be merged manually by a T2 Flex user).
	 ***********************************/

	//--------------------- Input XML Parameters ----------------------
	public $VEH_UID				= '';	// NOT Required if inserting.
	public $STATE					= ''; // Required for inserting. Vehicle license plate state (STATE_MLKP.STL_UID).
	public $LICENSE_PLATE		= ''; // Required for inserting. Vehicle license plate number. ignored if an existing vehicle is being updated.
	public $PLATE_TYPE			= ''; // Default 1 (which is NA). 2008 is Bicycle. Required for inserting. Vehicle license plate type code (VEH_PLATE_TYPE_LKP.VPL_UID)
	//public $VPL_DESCRIPTION		= ''; // VPL_DESCRIPTION
	public $PLATE_REG_EXP_MONTH = ''; // Not Required. format MM.
	public $PLATE_REG_EXP_YEAR	= ''; // Not Required. format YYYY.
	public $VEH_VIN				= ''; // Not Required. Vehicle VIN number
	public $VEHICLE_MAKE			= ''; // Not Required. Vehicle make code  (VEH_MAKE_LKP.VKL_UID)
	public $VEHICLE_MODEL		= ''; // Not Required. Vehicle model code (VEH_MODEL_MLKP.VML_UID)
	public $VEHICLE_STYLE		= ''; // Not Required. Vehicle style code (VEH_STYLE_LKP.VSL_UID)
	public $VEHICLE_COLOR		= ''; // Not Required. Vehicle color code (VEH_COLOR_LKP.VCL_UID)
	public $VEHICLE_YEAR			= ''; // Not Required. Vehicle Year of Manufacture. Format: YYYY
	// (FIELD_NAME) Custom Field Name

	//-------------------- Natural XML Output
	public $Vehicle_Uid			= ''; // Case-sensitive T2 CRAP!!!!!!!!  if inserting new vehicle.


	public $flex_function = 'InsertUpdateVehicle';

	public function __construct($vehUID = '', $stateUID = '', $plateNum = '', $exp_MM = '', $exp_YYYY = '', $vin = '', $make = '', $model = '', $style = '', $color = '', $year = '', $plateTypUID = 1)
	{
		Debug_Trace::traceFunc();
		parent::__construct();

		if (trim($vehUID)) {
			// updating vehicle, so unset plate (if not already unset during class instanciation).
			// (T2 doc says plate will be ignored for updates, but we know better don't we)
			$this->VEH_UID = strtoupper(trim($vehUID));
			$plateNum		= '';
			$stateUID		= '';
		}

		$this->STATE					= $stateUID;
		$this->LICENSE_PLATE			= $plateNum;
		$this->PLATE_TYPE				= $plateTypUID;
		$this->PLATE_REG_EXP_MONTH	= $exp_MM; // MM.
		$this->PLATE_REG_EXP_YEAR	= $exp_YYYY; // YYYY.
		$this->VEH_VIN					= $vin;
		$this->VEHICLE_MAKE			= $make;  // uid
		$this->VEHICLE_MODEL			= $model; // uid
		$this->VEHICLE_STYLE			= $style; // uid
		$this->VEHICLE_COLOR			= $color; // uid
		$this->VEHICLE_YEAR			= $year;  // YYYY

		$this->send_xml();
	}

	protected function get_xml()
	{
		Debug_Trace::traceFunc();
		/****  This function returns xml data.  ***/
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('VEHICLE_UID',				$this->VEH_UID);
		$this->xml_data .= make_param('STATE',						$this->STATE);
		$this->xml_data .= make_param('LICENSE_PLATE',			$this->LICENSE_PLATE);
		$this->xml_data .= make_param('PLATE_TYPE',				$this->PLATE_TYPE);
		$this->xml_data .= make_param('PLATE_REG_EXP_MONTH',	$this->PLATE_REG_EXP_MONTH);
		$this->xml_data .= make_param('PLATE_REG_EXP_YEAR',	$this->PLATE_REG_EXP_YEAR);
		$this->xml_data .= make_param('VIN',						$this->VEH_VIN);
		$this->xml_data .= make_param('VEHICLE_MAKE',			$this->VEHICLE_MAKE);
		$this->xml_data .= make_param('VEHICLE_MODEL',			$this->VEHICLE_MODEL);
		$this->xml_data .= make_param('VEHICLE_STYLE',			$this->VEHICLE_STYLE);
		$this->xml_data .= make_param('VEHICLE_COLOR',			$this->VEHICLE_COLOR);
		$this->xml_data .= make_param('VEHICLE_YEAR',			$this->VEHICLE_YEAR);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}



class InsertUpdateVehicleAssociation extends Flex_Vehicles
{
	/**********************************\
	 * This method is used to insert a new vehicle association record or update an
		existing vehicle association record. A vehicle association is the record in Flex
		that defines the relationship between a vehicle and an entity.
	 * If a vehicle association UID is not passed then the method will attempt to insert
		a new vehicle association using the information passed into the method.
	 * If a vehicle association UID is passed then method will attempt to update an
		existing vehicle association using information passed into the method. If a
		value is passed in parameter ENTITY_UID for an update it will be ignored. (If a
		new license plate number has been assigned to an existing vehicle, use this
		method to insert a record for the new record for the vehicle rather than
		attempting to update the existing record).
	 * If an association priority is passed then the method will reprioritize all
		associations for the vehicle in order to place the association at the specified
		priority position; the association previously occupying that priority and all
		lower priority associations for the vehicle will have their priorities lowered. If a
		priority greater than that of any existing customer vehicle association is
		passed, then the association should be moved to the last position.
	 * If no association priority is passed on an insert, the vehicle association will be
		inserted at priority position 1. If no customer vehicle association priority is
		passed on an update, the priority of the association will be unchanged.
	 ***********************************/

	//--------------------- Input XML Parameters ----------------------
	public $ASS_UID					= ''; // NOT Required if inserting. Vehicle Association UID (ENT_VEH_REL.EVR_UID)
	public $VEHICLE_UID				= '';	// Required for inserting vehicle association.
	public $ENTITY_UID				= ''; // Required for inserting. (ENT_UID is ignored when updating)
	public $ASSOC_TYPE				= '';	// Default 2 Driver (was Owner). Required for inserting. (VEH_ASSOCIATION_LKP.VAL_UID)
	public $ASSOC_START_DATE		= ''; // Required for inserting. MM/DD/YY.  Default is 'now' (MM/DD/YY)
	public $ASSOC_END_DATE			= ''; // MM/DD/YY
	public $ASSIGN_CONTRAVENTIONS	= ''; // Assign unassigned contraventions. Format: 1 for yes or 0 for no. If 1 is sent then the customer
	// associated with this relationship will be made the responsible customer for all citations for this vehicle.
	public $ASSOC_PRIORITY			= ''; // empty when inserting - puts at priority 1.

	//-------------------- Natural XML Output
	public $Association_Uid; // Case-sensitive T2 return var crap!


	public $flex_function = 'InsertUpdateVehicleAssociation';

	public function __construct($vehUID = '', $assUID = '', $entUID = '', $assCons = '', $assPriority = '', $assEnd = '', $assStart = 'now', $assType = 2)
	{
		Debug_Trace::traceFunc();
		parent::__construct();

		if (trim($assUID)) {
			// Updating
			$this->ASS_UID	= strtoupper(trim($assUID));
		}
		$this->VEHICLE_UID			= strtoupper(trim($vehUID));
		$this->ENTITY_UID				= strtoupper(trim($entUID));
		$this->ASSOC_TYPE				= strtoupper(trim($assType));

		if ($assStart == 'now')
			$this->ASSOC_START_DATE =		date('m/d/y');
		else
			$this->ASSOC_START_DATE =		strtoupper(trim($assStart)); // MM/DD/YY

		$this->ASSOC_END_DATE			= strtoupper(trim($assEnd)); // MM/DD/YY
		$this->ASSIGN_CONTRAVENTIONS	= strtoupper(trim($assCons));
		$this->ASSOC_PRIORITY			= strtoupper(trim($assPriority));

		$this->send_xml();
	}

	protected function get_xml()
	{
		Debug_Trace::traceFunc();
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('ASSOCIATION_UID',		$this->ASS_UID);
		$this->xml_data .= make_param('VEHICLE_UID',				$this->VEHICLE_UID);
		$this->xml_data .= make_param('ENTITY_UID',				$this->ENTITY_UID);
		$this->xml_data .= make_param('ASSOC_TYPE',				$this->ASSOC_TYPE);
		$this->xml_data .= make_param('ASSOC_START_DATE',		$this->ASSOC_START_DATE);
		$this->xml_data .= make_param('ASSOC_END_DATE',			$this->ASSOC_END_DATE);
		$this->xml_data .= make_param('ASSIGN_CONTRAVENTIONS', $this->ASSIGN_CONTRAVENTIONS);
		$this->xml_data .= make_param('ASSOC_PRIORITY',			$this->ASSOC_PRIORITY);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}



class GetVehicleAssociation extends Flex_Vehicles
{
	/**********************************\
	 * Get customer's vehicles based on ent_uid - put vehicles into arrays (see Output section)
	 * Sinc T2 has no ranking, then the highest VEH_UID number will be used as the main vehicle ($this->VEH_UID[$this->primary_assUID])
	 ***********************************/

	//--------------------- Input ----------------------
	public $ENTITY_UID				= ''; // Required if no EVR_UID (association uid)
	public $EvrUid						= ''; // Required if no ENTITY_UID (still need to develope this functionality)
	public $EVR_EFFECTIVE_RANK		= ''; // A rank of 1 is the main owner of vehicle (if more than one ent is tied to it).

	//-------------------- Output ---------------------
	public $primary_assUID			= '';
	// Arrays are in format of [EVR_UID] = "........".
	public $VEH_UID					= array(); // [EVR_UID] = VEH_UID (VEH_UID from VEHICLE table).
	public $VKL_DESCRIPTION			= array(); // make
	public $VML_DESCRIPTION			= array(); // model
	public $VCL_DESCRIPTION			= array(); // color
	public $VEH_PLATE_LICENSE		= array(); // plate number
	public $VEH_VIN					= array();
	public $STL_UID					= array(); // plate state uid
	public $STL_CODE					= array(); // plate state (i.e. 'AZ')
	public $PLATE_TYPE				= array(); // plate type code VPL_UID
	public $VPL_DESCRIPTION			= array(); // VPL_DESCRIPTION
	public $ASSOC_PRIORITY			= array();
	public $EVR_START_DATE			= array();
	public $EVR_END_DATE				= array();

	public $flex_function = 'GetVehicleAssociation';

	public function __construct($entUID, $veh_or_bike = 'vehicle')
	{
		Debug_Trace::traceFunc();

		$this->veh_or_bike	= $veh_or_bike;

		$this->ENTITY_UID		= strtoupper(trim($entUID));

		// Don’t show on web if end date (EVR_END_DATE) is equal or less than today.
		$evr_end = date('Ymd') . ' 23:59:59'; // YYYYMMDD HH24:MI:SS


		$t2Key = 'Q_GetVehicleAssociation';

		if ($this->veh_or_bike == 'bicycle') {
			//BICYCLE: t2 query will look like this (the fragment of paramThree with paramFour):  ... and VSL_CODE like '%BK'" and VSL_CODE not like 'blaaaaaaaa' ...
			$paramThree	= "%BK";
			$paramFour	= "blaaaaaa";
		} else {
			//VEHICLE: t2 query will look like this (the fragment of paramThree with paramFour):  ... and VSL_CODE like '%'" and VSL_CODE not like '%BK' ...
			$paramThree	= "%";
			$paramFour	= "%BK";
		}
		$param_ary = array($this->ENTITY_UID, $evr_end, $paramThree, $paramFour);
		$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
		if ($GLOBALS['DEBUG_DEBUG']) {
			$this->debug_data = "$t2Key param_ary: $this->ENTITY_UID, $evr_end, $paramThree, $paramFour)";
			echo $exeResults;
		}

		$tmpResults1 = $exeResults->results_custom;


		for ($i = 0; $i < sizeof($tmpResults1['EVR_UID']); $i++) {
			$assUID											= $tmpResults1['EVR_UID'][$i];
			$this->VEH_UID[$assUID]						= $tmpResults1['VEH_UID'][$i];;
			$this->VKL_DESCRIPTION[$assUID]			= $tmpResults1['VKL_DESCRIPTION'][$i];
			$this->VML_DESCRIPTION[$assUID]			= $tmpResults1['VML_DESCRIPTION'][$i];
			$this->VCL_DESCRIPTION[$assUID]			= $tmpResults1['VCL_DESCRIPTION'][$i];
			$this->VEH_PLATE_LICENSE[$assUID]		= $tmpResults1['VEH_PLATE_LICENSE'][$i];
			$this->VEH_VIN[$assUID]						= $tmpResults1['VEH_VIN'][$i];
			$this->STL_UID[$assUID]						= $tmpResults1['STL_UID'][$i];
			$this->STL_CODE[$assUID]					= $tmpResults1['STL_CODE'][$i];
			$this->PLATE_TYPE[$assUID]					= $tmpResults1['VPL_UID'][$i];
			$this->VPL_DESCRIPTION[$assUID]			= $tmpResults1['VPL_DESCRIPTION'][$i];
			$this->ASSOC_PRIORITY[$assUID]			= $tmpResults1['ASSOC_PRIORITY'][$i];
			$this->EVR_EFFECTIVE_RANK[$assUID]		= $tmpResults1['EVR_EFFECTIVE_RANK'][$i];
			$this->EVR_START_DATE[$assUID]			= $tmpResults1['EVR_START_DATE'][$i];
			$this->EVR_END_DATE[$assUID]				= $tmpResults1['EVR_END_DATE'][$i];

			if (!$this->primary_assUID) {
				$this->primary_assUID	= $assUID;
			}
		}
	}
}




class GetVehicleInfo extends Flex_Vehicles
{
	/**********************************\
	 * Get a vehicle info by plate, and/or state, or VIN for bicycle.
	 * Normally use GetVehicleAssociation.
	 ***********************************/

	//--------------------- Input ----------------------
	public $VEH_PLATE_LICENSE		= ''; // plate number0
	public $STL_UID					= ''; // plate state - i.e. Arizona is 4 (NOTE: stl_code would be like 'AZ' for arizona)
	public $VEH_VIN					= '';

	//-------------------- Output ---------------------
	public $VEH_UID					= ''; //
	public $EVR_EFFECTIVE_RANK		= ''; // 1 is the main owner of vehicle (if more than one ent is tied to it).
	public $EVR_UID					= ''; // Vehicle Association UID (ENT_VEH_REL.EVR_UID)
	public $VKL_DESCRIPTION			= ''; // make
	public $VML_DESCRIPTION			= ''; // model
	public $PLATE_TYPE				= ''; // plate type code VPL_UID
	//public $VPL_DESCRIPTION			= ''; // VPL_DESCRIPTION
	public $ASSOC_PRIORITY			= '';
	public $ENT_UID_ENTITY			= '';
	public $EVR_IS_ACTIVE			= ''; // 1 if active.
	public $end_date_future			= ''; // 1 if end date in future
	public $STL_CODE					= ''; // 'AZ' for arizona

	public $flex_function = 'GetVehicleInfo';

	public function __construct($plate, $stateUID = '', $vin = '', $entUID = '', $excludeInactive = true, $excludePastEndDate = true, $plateOnly = false)
	{
		Debug_Trace::traceFunc();

		$this->ENT_UID_ENTITY		= $entUID;
		$this->VEH_PLATE_LICENSE	= strtoupper(trim($plate));
		$this->STL_UID					= $stateUID;
		$this->VEH_VIN					= strtoupper(trim($vin));

		$sqlAppend = '';
		$qVars = array();

		// Don’t show on web if end date (EVR_END_DATE) is equal or less than today.
		$evr_end = date('Ymd') . ' 23:59:59'; // YYYYMMDD HH24:MI:SS


		// order by EVR_UID desc so as to get the latest one.

		if ($plateOnly && $this->VEH_PLATE_LICENSE) {
			$t2Key = 'Q_GetVehicleInfoFromPlate';
			$paramOne = $this->VEH_PLATE_LICENSE;
			$param_ary = array($paramOne);
		} else {
			$t2Key = 'Q_GetVehicleInfo';
			// setting paramOne to 0 nullifies this T2 query fragment: "and (EVR_IS_ACTIVE = 1 or EVR_IS_ACTIVE = :paramOne)"
			$paramOne = $excludeInactive				? '1'								: '0';
			// setting paramTwo to 19700101 nullifies this T2 query fragment: "and (EVR_END_DATE is null or EVR_END_DATE > TO_DATE(:paramTwo, 'YYYYMMDD HH24:MI:SS')"
			$paramTwo = $excludePastEndDate			? $evr_end						: '19700101 23:59:59';
			// setting paramThree to '%' nullifies this T2 query fragment: "and ENT_UID_ENTITY like :paramThree"
			$paramThree = $this->ENT_UID_ENTITY		? $this->ENT_UID_ENTITY		: '%';
			// setting paramFour to '%' nullifies this T2 query fragment: "and VEH_PLATE_LICENSE like :paramFour"
			$paramFour = $this->VEH_PLATE_LICENSE	? $this->VEH_PLATE_LICENSE : '%';
			// setting paramFive to '%' nullifies this T2 query fragment: "and STL_UID_STATE like :paramFive"
			$paramFive = ($this->VEH_PLATE_LICENSE && $this->STL_UID)	? $this->STL_UID	: '%';
			// setting paramSix to '%' nullifies this T2 query fragment: "and REGEXP_REPLACE(VEH_VIN, '( )', '') like :paramSix"
			// Bicycle uses vin (so useing REGEXP_REPLACE to ignore white spaces.
			$paramSix = (!$this->VEH_PLATE_LICENSE && $this->VEH_VIN)	? preg_replace("/\s/si", "", $this->VEH_VIN)	: '%';
			$param_ary = array($paramOne, $paramTwo, $paramThree, $paramFour, $paramFive, $paramSix);
		}

		$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
		if ($GLOBALS['DEBUG_DEBUG']) {
			$this->debug_data = "$t2Key param_ary: " . $paramOne . ", " . @$paramTwo . ", " . @$paramThree . ", " . @$paramFour . ", " . @$paramFive . ", " . @$paramSix . ")";
			echo $exeResults;
		}

		$tmpResults2 = $exeResults->results_custom;

		if ($tmpResults2['VEH_UID'][0]) {
			$this->VEH_UID						= $tmpResults2['VEH_UID'][0];;
			$this->EVR_UID						= @$tmpResults2['EVR_UID'][0];;
			$this->VKL_DESCRIPTION			= $tmpResults2['VKL_DESCRIPTION'][0];
			$this->VML_DESCRIPTION			= $tmpResults2['VML_DESCRIPTION'][0];
			$this->VEH_PLATE_LICENSE		= $tmpResults2['VEH_PLATE_LICENSE'][0];
			$this->VEH_VIN						= $tmpResults2['VEH_VIN'][0];
			$this->STL_CODE					= $tmpResults2['STL_CODE'][0];
			$this->STL_UID						= $tmpResults2['STL_UID'][0];
			$this->PLATE_TYPE					= $tmpResults2['VPL_UID'][0];
			$this->ENT_UID_ENTITY			= @$tmpResults2['ENT_UID_ENTITY'][0];;
			$this->ASSOC_PRIORITY			= @$tmpResults2['ASSOC_PRIORITY'][0];
			$this->EVR_EFFECTIVE_RANK		= @$tmpResults2['EVR_EFFECTIVE_RANK'][0];
			$this->EVR_IS_ACTIVE				= @$tmpResults2['EVR_IS_ACTIVE'][0];
			$this->end_date_future			= @$tmpResults2['end_date_future'][0];
		}
	}
}




function GetVehStyles($veh_or_bike = 'vehicle', $arrayOnly = false)
{
	/**********************************\
	 * If $arrayOnly is false, returns <select> and <option> html tags of all possible vehicle styles.
	 * If $arrayOnly is true, then does NOT return <option> tags but an array: vehStyles[VSL_UID] = VSL_DESCRIPTION
	 ***********************************/

	$vehStyles = array(); // vehStyles[VSL_UID] = VSL_DESCRIPTION

	if (12345) {
		$t2Key = 'Q_GetVehStyles';

		if ($veh_or_bike == 'bicycle') {
			//BICYCLE: t2 query will look like this (the fragment of paramOne with paramTwo):  ... and VSL_CODE like '%BK'" and VSL_CODE not like 'blaaaaaaaa' ...
			$paramOne	= "%BK";
			$paramTwo	= "blaaaaaa";
		} else {
			//VEHICLE: t2 query will look like this (the fragment of paramOne with paramTwo):  ... and VSL_CODE like '%'" and VSL_CODE not like '%BK' ...
			$paramOne	= "%";
			$paramTwo	= "%BK";
		}
		$param_ary = array($paramOne, $paramTwo);
		$exeResults = new ExecuteQuery(Flex_Vehicles::$t2_query[$t2Key], $param_ary, $t2Key, 'GetVehStyles');
		if ($GLOBALS['DEBUG_DEBUG']) {
			echo $exeResults;
		}

		$tmpResults2 = $exeResults->results_custom;
	} else {
		//------------- OLD SCHOOL
		//	$dbC = new database();
		//	if ($veh_or_bike == 'bicycle')	$sqlWhere = "VSL_CODE like '%BK'";
		//	else												$sqlWhere = "VSL_CODE not like '%BK'";
		//	$queryCheck = "select VSL_UID, VSL_CODE, VSL_DESCRIPTION
		//		from FLEXADMIN.VEH_STYLE_LKP where " . $sqlWhere . " and VSL_IS_ACTIVE = 1
		//		order by VSL_DESCRIPTION asc";
		//	$dbC->sQuery($queryCheck);
		//	$tmpResults2 = $dbC->results;
	}

	for ($i = 0; $i < sizeof($tmpResults2['VSL_UID']); $i++)
		$vehStyles[$tmpResults2['VSL_UID'][$i]] = trim($tmpResults2['VSL_DESCRIPTION'][$i]);

	if ($arrayOnly) {
		return $vehStyles;
	} else {
		$tempOpts = $tempBottomOpts = '';

		// need onclick in case the select has only one option, namely "Style not found".
		// hidden _text are for debugging only - log data table.
		$returnOptions = '<input type="hidden" name="veh_style_text" value="' . @$_POST['veh_style_text'] . '">
		<select name="veh_style" id="veh_style"
		onchange="hideUnhide(\'style_field\',this.value); '
			. 'veh_frm.veh_style_text.value=veh_frm.veh_style.options[veh_frm.veh_style.selectedIndex].text; '
			. 'if(veh_frm.veh_style_text.value==\'Motorcycle\' || veh_frm.veh_style_text.value==\'Scooter\' || veh_frm.veh_style_text.value==\'Moped\'){ document.getElementById(\'motorcycle_note\').innerHTML = \'&nbsp;Note: \' + veh_frm.veh_style_text.value + \'s must be parked in a designated&nbsp;<br>&nbsp;MOTORCYCLE area with a valid Motorcycle permit.&nbsp;\';}else{document.getElementById(\'motorcycle_note\').innerHTML = \'\';}"
		onclick="hideUnhide(\'style_field\',this.value); veh_frm.veh_style_text.value=veh_frm.veh_style.options[veh_frm.veh_style.selectedIndex].text;">
		<option value="">------- Select a Style -------</option>';

		// Return <option> tags.
		foreach ($vehStyles as $aCode => $aDesc) {
			if (!$aDesc || $aDesc == 'Golf Cart' || $aDesc == 'Motor Home')
				continue;
			$returnOptions .= "<option value='" . $aCode . "'";
			if (@$_POST['veh_style'] && $aCode == $_POST['veh_style'])
				$returnOptions .= ' selected';
			$returnOptions .= ">" . $aDesc . "</option>\n";
		}
		//xxx$returnOptions .= '<option value="new_veh_bk_styl">======   Enter new style   ======</option>' . "\n";

		$returnOptions .= "</select>\n";

		return $returnOptions;
	}
}



function GetVehColors($arrayOnly = false)
{
	/**********************************\
	 * If $arrayOnly is false, returns <select> and <option> html tags of all possible vehicle colors.
	 * If $arrayOnly is true, then does NOT return <option> tags but an array: vehColors
	 ***********************************/

	$vehColors = array(); // vehColors[VCL_UID] = VCL_DESCRIPTION

	//if ($veh_or_bike == 'bicycle')	$sqlWhere = "VCL_UID like '%BK'";
	//else	$sqlWhere = "VCL_UID not like '%BK'";

	if (12345) {
		$t2Key = 'Q_GetVehColors';

		$param_ary = array();
		$exeResults = new ExecuteQuery(Flex_Vehicles::$t2_query[$t2Key], $param_ary, $t2Key, 'GetVehColors');
		if ($GLOBALS['DEBUG_DEBUG']) {
			echo $exeResults;
		}

		$tmpResults3 = $exeResults->results_custom;
	} else {
		//------------- OLD SCHOOL
		//	$dbC = new database();
		//	$queryCheck = "select VCL_UID, VCL_DESCRIPTION from FLEXADMIN.VEH_COLOR_LKP where VCL_IS_ACTIVE = 1 order by VCL_DESCRIPTION asc";
		//	$dbC->sQuery($queryCheck);
		//	$tmpResults3 = $dbC->results;
	}

	for ($i = 0; $i < sizeof($tmpResults3['VCL_UID']); $i++) {
		$vehColors[$tmpResults3['VCL_UID'][$i]] = trim($tmpResults3['VCL_DESCRIPTION'][$i]);
	}

	if ($arrayOnly) {
		return $vehColors;
	} else {
		// hidden _text are for debugging only - log data table.
		$returnOptions = '<input type="hidden" name="veh_color_text" value="' . @$_POST['veh_color_text'] . '">
		<select name="veh_color" id="veh_color"
		onchange="veh_frm.veh_color_text.value=veh_frm.veh_color.options[veh_frm.veh_color.selectedIndex].text;">
		<option value=""></option>' . "\n";
		// Return <option> tags.
		foreach ($vehColors as $aCode => $aDesc) {
			if (!$aDesc)
				continue;
			$returnOptions .= "<option value='" . $aCode . "'";
			if (@$_POST['veh_color'] && $aCode == $_POST['veh_color'])
				$returnOptions .= ' selected';
			$returnOptions .= ">" . $aDesc . "</option>\n";
		}
		$returnOptions .= "</select>\n";

		return $returnOptions;
	}
}



function getVehMakes($vklUid = '', $arrayOnly = false, $veh_or_bike = 'vehicle')
{
	/************** note: this used to be in customer_functions.php
	 * In T2 We will enter vehicle makes as i.e. BMW (four-wheel auto), and/or BMWB (bicycle), and/or BMWM (motorcycle);
	 * and there will also be a custom data checkbox(s) to tag vehicle makes in one of three values:
	 * 1 (four-wheel auto), 2(motorcycle), 3(bicycle);
	 * We also considered using four more values: 12(four-wheel auto+bicycle), 13(four-wheel auto+motorcycle), ...
	 *
	 * If $arrayOnly is false, returns <select> and <option> html tags of all possible vehicle makes - <ptions value="VKL_UID">VKL_DESCRIPTION</option>
	 * If $arrayOnly is true, then does NOT return <option> tags but an array: makes[VKL_UID] = VKL_DESCRIPTION
	 * $vklUid (vehicle make uid) can be used two ways:
	 *		1.  Where $arrayOnly is empty: To set a default value -  <option value="$vklUid" selected>
	 *		2.  Where $arrayOnly is set:  To return a single vehicle make - returns a single-value array makes[$vklUid] = VKL_DESCRIPTION
	 *
	 * Uses custom data field to join makes with make TYPE (we created these):
	 *		1. motorized 4-wheel vehicle, 2. motorized 2-wheel vehicle, 3. bicycle.
	 * For creating new veh make/model, see Internal/powertools/vehicle_new .php
	 *		which will insert: set all the VKL_UID's (Vehicle Makes) into CUSTOM_DATA.CUD_RECORD_UID field,
	 *		CUSTOM_DATA.DAD_UID to $DAD_UID_VEHSTYLE, and CUSTOM_DATA.CUD_VALUE to TYPE (1, 2, or 3).
	 **************/

	$DAD_UID_VEHSTYLE = 200043;

	$makes = array(); // makes[VKL_UID] = VKL_DESCRIPTION

	if (12345) {
		$t2Key = 'Q_getVehMakes';

		// see "vslCodes" comments.
		if ($veh_or_bike == 'bicycle') {
			$paramOne = '3';
			$paramTwo = 'blaaaaa-aaa'; // gee Wally, I hope nobody decides to name a bicycle this.
		} else {
			$paramOne = '1'; // four-wheel auto
			$paramTwo = '2'; // motorcycle
		}

		$param_ary = array($paramOne, $paramTwo, $DAD_UID_VEHSTYLE);
		$exeResults = new ExecuteQuery(Flex_Vehicles::$t2_query[$t2Key], $param_ary, $t2Key, 'getVehMakes');
		if ($GLOBALS['DEBUG_DEBUG']) {
			echo $exeResults;
		}

		$tmpResults4 = $exeResults->results_custom;
	} else {
		//------------- OLD SCHOOL
		//	$dbC = new database();
		//	$qVars = array();
		//	$vehType = ($veh_or_bike == 'bicycle') ? '3' : '1,2'; // see "vslCodes" comments.
		//	$queryVkl = "select VKL_UID, VKL_DESCRIPTION	 from FLEXADMIN.VEH_MAKE_LKP vmak inner join FLEXADMIN.CUSTOM_DATA cData on cData.CUD_RECORD_UID = vmak.VKL_UID
		//						inner join FLEXADMIN.VEH_STYLE_LKP vsl on vsl.VSL_UID = cData.CUD_VALUE	 where cData.CUD_VALUE in (".$vehType.")
		//						and VKL_IS_ACTIVE = 1 and cData.DAD_UID = " . $DAD_UID_VEHSTYLE . "  order by VKL_DESCRIPTION";
		//	$dbC->sQuery($queryVkl, $qVars);
		//	$tmpResults4 = $dbC->results;
	}

	for ($i = 0; $i < sizeof($tmpResults4['VKL_UID']); $i++) {
		$makes[$tmpResults4['VKL_UID'][$i]] = trim($tmpResults4['VKL_DESCRIPTION'][$i]);
	}

	if ($arrayOnly) {
		return $makes;
	} else {
		$returnOptions = '<input type="hidden" name="veh_make_text" value="' . @$_POST['veh_make_text'] . '">
		<select name="veh_make" id="veh_make"
		onchange="hideUnhide(\'make_field\',this.value); veh_frm.veh_make_text.value=veh_frm.veh_make.options[veh_frm.veh_make.selectedIndex].text;">
		<option value="">------- Select a Make -------</option>' . "\n";

		// Return <option> tags.
		foreach ($makes as $aVklUid => $aDesc) {
			if (!$aDesc)
				continue;
			$returnOptions .= "<option value='" . $aVklUid . "'";
			if ($aVklUid == $vklUid)
				$returnOptions .= ' selected';
			$returnOptions .= ">" . $aDesc . "</option>\n";
		}
		$returnOptions .= '<option value="new_veh_bk_styl">=====  Enter ' . $veh_or_bike . ' make  =====</option>' . "\n";
		$returnOptions .= "</select>\n";

		return $returnOptions;
	}
}



function getVehModels($vmlUid = '', $vklUid = '', $arrayOnly = false)
{
	/************** note: this used to be in customer_functions.php
	 * If $arrayOnly is false, returns <select> and <option> html tags of all possible vehicle models - <ptions value="VML_UID">VML_DESCRIPTION</option>
	 * If $arrayOnly is true, then does NOT return <option> tags but an array: models[VML_UID] = VML_DESCRIPTION
	 * $vmlUid can be used two ways:
	 *		1.  Where $arrayOnly is false: To set a default value -  <option value="$vmlUid" selected>
	 *		2.  Where $arrayOnly is true:  To return a single vehicle model - returns a single-value array models[$vmlUid] = VML_DESCRIPTION
	 * To limit all models by a single vehicle model, use $vmlUid.
	 * Ajax calls this in vehicle_form.php when a dropdown (from getVehMakes) is selected
	 *************/

	$models = array(); // models[VML_UID] = VML_DESCRIPTION

	if (12345) {
		$t2Key = 'Q_getVehModels';
		// Limit models to a single make.
		$paramOne = $vklUid ? $vklUid : '%'; // t2 query: "and vmodel.VKL_UID_MAKE like :paramOne"
		$paramTwo = $vmlUid ? $vmlUid : '%'; // t2 query: "and vmodel.VML_UID like :paramTwo"

		$param_ary = array($paramOne, $paramTwo);
		$exeResults = new ExecuteQuery(Flex_Vehicles::$t2_query[$t2Key], $param_ary, $t2Key, 'getVehModels');
		if ($GLOBALS['DEBUG_DEBUG']) {
			echo $exeResults;
		}

		$tmpResults5 = $exeResults->results_custom;
	} else {
		//------------- OLD SCHOOL
		//	$dbC = new database();
		//	$sqlAppend = '';
		//	$qVars = array();
		//	if ($vklUid) { // Limit models to a single make.
		//		$sqlAppend .= ' and vmodel.VKL_UID_MAKE = :vklUid ';
		//		$qVars['vklUid'] = $vklUid;
		//	}
		//	if ($vmlUid) { // Limit models to a single maodel.
		//		$sqlAppend .= ' and vmodel.VML_UID = :vmlUid ';
		//		$qVars['vmlUid'] = $vmlUid;
		//	}
		//	$queryVml = 'select VML_UID, VML_DESCRIPTION
		//				 from FLEXADMIN.VEH_MODEL_MLKP vmodel inner join FLEXADMIN.VEH_MAKE_LKP vmake on vmake.VKL_UID = vmodel.VKL_UID_MAKE
		//				 where vmodel.VML_IS_ACTIVE = 1 and vmake.VKL_IS_ACTIVE = 1 ' . $sqlAppend . '  order by VML_DESCRIPTION';
		//	$dbC->sQuery($queryVml, $qVars);
		//	$tmpResults5 = $dbC->results;
	}

	for ($i = 0; $i < sizeof($tmpResults5['VML_UID']); $i++) {
		$models[$tmpResults5['VML_UID'][$i]] = trim($tmpResults5['VML_DESCRIPTION'][$i]);
	}

	if ($arrayOnly) {
		return $models;
	} else {
		// need onclick in case the select has only one option, namely "Enter new model".
		// hidden _text are for debugging only - log data table.
		$returnOptions = '<input type="hidden" name="veh_model_text" value="' . @$_POST['veh_model_text'] . '">
		<select name="veh_model" id="veh_model"
		onchange="hideUnhide(\'model_field\',this.value); veh_frm.veh_model_text.value=veh_frm.veh_model.options[veh_frm.veh_model.selectedIndex].text;"
		onclick="hideUnhide(\'model_field\',this.value); veh_frm.veh_model_text.value=veh_frm.veh_model.options[veh_frm.veh_model.selectedIndex].text;">' . "\n";

		$modelsFound = false;
		$tempOpts = $tempBottomOpts = '';

		foreach ($models as $aVmlUid => $aDesc) {
			// If bicycle .php, don't include 'Bicycle' in dropdown.
			if (!$aDesc || preg_match('/\s*Bicycle\s*/si', $aDesc) || preg_match('/\s*Not Available\s*/si', $aDesc))
				continue;

			if (!$modelsFound) {
				// First time in loop here.
				$modelsFound = true;
				$tempOpts .= '<option value="">------ Select a Model ------</option>' . "\n";
			}

			if ($aVmlUid == $vmlUid)	$selected .= ' selected';
			else							$selected = '';

			if (strtolower($aDesc) == 'bicycle' || strtolower($aDesc) == 'vehicle' || strtolower($aDesc) == 'other' || strtolower($aDesc) == 'car')
				$tempBottomOpts .= "<option value='" . $aVmlUid . "'" . $selected . ">" . $aDesc . "</option>\n";
			else
				$tempOpts .= "<option value='" . $aVmlUid . "'" . $selected . ">" . $aDesc . "</option>\n";
		}

		$returnOptions .= $tempOpts . $tempBottomOpts;

		$returnOptions .= '<option value="new_veh_bk_styl">======   Enter new model   ======</option>' . "\n";

		$returnOptions .= "</select>\n";

		return $returnOptions;
	}
}



function logVehicle($reason, $veh_desc, $veh_uid = 0)
{
	/***
	 * Vehicle conflict cleanup (CRS), and new Make/Model Insertions (IT)
	 * Data to go into PARKING.FLEXADMIN_VEHICLE table, to be handled by I.T. and CR:
	 *		I.T. url: https://www.pts.arizona.edu/powertools/vehicle_new.php
	 *		CR url: https://www.pts.arizona.edu/powertools/vehicle_fix.php
	 * Returns ID of the inserted log record.
	 * Used in vehicle_insert .php and bike_insert .php
	 */

	global $mysqli2, $newEID;

	if (!isset($mysqli2)) $mysqli2 = new database();

	$tmpEntUid = $_SESSION['entity']['ENT_UID'] ? $_SESSION['entity']['ENT_UID'] : @$newEID->ENTITY_UID;

	$tmp_VEH_UID		= '';
	$tmp_CUD_UID		= ('vehicle' == 'bicycle') ? 3 : 1; // see CUD_UID_VEHSTYLE in powertools/vehicle_new .php.
	$tmp_color_uid		= $_POST['veh_color'] ? $_POST['veh_color'] : 0;
	$tmp_style_uid		= $_POST['veh_style'] ? $_POST['veh_style'] : 0;
	$tmp_exp_month		= ''; // see $m_vehicle->PLATE_REG_EXP_MONTH
	$tmp_exp_year		= ''; // see $m_vehicle->PLATE_REG_EXP_YEAY
	$tmp_make_uid		= $_POST['veh_make'] ? $_POST['veh_make'] : 0; // 0 for new make
	$tmp_model_uid		= $_POST['veh_model'] ? $_POST['veh_model'] : 0; // 0 for now model
	$tmp_plate			= $_POST['VEH_PLATE_LICENSE'] ? $_POST['VEH_PLATE_LICENSE'] : @$veh_desc['bike_plate_peruid'];
	$tmp_plate_type	= 0;

	$query = "insert into PARKING.FLEXADMIN_VEHICLE (VEH_LOG_ID, VEH_UID_TEMP, REASON, VEH_PLATE_LICENSE,
			VPL_UID_PLATE_TYPE, STL_UID_STATE, STL_DESCRIPTION, VEH_YEAR_OF_MANUF,
			MOL_UID_PLATE_REG_EXP_MONTH, VEH_PLATE_REG_EXP_YEAR, VKL_UID_MAKE, VKL_DESCRIPTION,
			VML_UID_MODEL, VML_DESCRIPTION, CUD_UID_VEHSTYLE, VCL_UID_COLOR, VSL_UID_STYLE, ENT_UID_SUBMITTER,
			VCL_DESCRIPTION, VSL_DESCRIPTION,
			VPS_UID_PLATE_SERIES, VEH_ROVR_ELIGIBLE, VEH_ROVR_SELECTED, VEH_IS_SCOFFLAW_CAC,
			VEH_HAS_NOTIFICATION_CAC, VEH_AMOUNT_DUE_CAC)
		values (PARKING.VEH_LOG_ID_NUMBER.NEXTVAL, :VEH_UID_TEMP, :REASON, :VEH_PLATE_LICENSE, :VPL_UID_PLATE_TYPE,
			:STL_UID_STATE, :STL_DESCRIPTION, :VEH_YEAR_OF_MANUF, :MOL_UID_PLATE_REG_EXP_MONTH, :VEH_PLATE_REG_EXP_YEAR,
			:VKL_UID_MAKE, :VKL_DESCRIPTION, :VML_UID_MODEL, :VML_DESCRIPTION, :CUD_UID_VEHSTYLE, :VCL_UID_COLOR,
			:VSL_UID_STYLE, :ENT_UID_SUBMITTER, :VCL_DESCRIPTION, :VSL_DESCRIPTION,
			0, 1, 0, 0, 0, 0)";
	$qVars = array(
		'VEH_UID_TEMP' => $veh_uid, 'REASON' => $reason, 'VEH_PLATE_LICENSE' => $tmp_plate,
		'VPL_UID_PLATE_TYPE' => $tmp_plate_type, 'STL_UID_STATE' => $_POST['STATE_UID'], 'STL_DESCRIPTION' => $veh_desc['state'],
		'VEH_YEAR_OF_MANUF' => @$_POST['year'], 'MOL_UID_PLATE_REG_EXP_MONTH' => $tmp_exp_month,
		'VEH_PLATE_REG_EXP_YEAR' => $tmp_exp_year, 'VKL_UID_MAKE' => $tmp_make_uid,
		'VKL_DESCRIPTION' => $veh_desc['make'], 'VML_UID_MODEL' => $tmp_model_uid,
		'VML_DESCRIPTION' => $veh_desc['model'], 'CUD_UID_VEHSTYLE' => $tmp_CUD_UID, 'VCL_UID_COLOR' => $tmp_color_uid,
		'VSL_UID_STYLE' => $tmp_style_uid, 'ENT_UID_SUBMITTER' => $tmpEntUid,
		'VCL_DESCRIPTION' => $veh_desc['color'], 'VSL_DESCRIPTION' => $veh_desc['style']
	);
	if ($GLOBALS['DEBUG_DEBUG']) echoNow(Flex_Funcs::makeDivDataBar('--- function logVehicle', $query . "<br>\n" . print_r($qVars, true), 'div_id_logVeh'));
	$mysqli2->sQuery($query, $qVars);

	// Get the last insert ID (i.e. LAST_INSERT_ID) from above insert.
	$getQuery = "SELECT PARKING.VEH_LOG_ID_NUMBER.CURRVAL AS LAST_INSERT_ID FROM DUAL";
	$mysqli2->sQuery($getQuery);
	return ($mysqli2->rows == 1) ? $mysqli2->results['LAST_INSERT_ID'][0] : '';
}



function unLogVeh($VEH_LOG_ID_NUMBER, $reason)
{
	/***
	 * Customer clicked Remove button after failed attempt to insert/update vehicle.
	 * Updates (not removes) the record previously created in logVehicle function above (which returned $VEH_LOG_ID_NUMBER).
	 *		$reason is the same as that passed to logVehicle, but will concat "Removed By Customer".
	 *		And Compleded date will be set - so we no longer have to wory about it.
	 */

	global $mysqli2, $newEID;

	if (!isset($mysqli2)) $mysqli2 = new database();

	$reason .= ' **Removed By Customer**';

	// Security measure - make sure the person logged-in is making the change.
	$tmpEntUid = $_SESSION['entity']['ENT_UID'] ? $_SESSION['entity']['ENT_UID'] : @$newEID->ENTITY_UID;

	$query = "update PARKING.FLEXADMIN_VEHICLE set REASON = :REASON, COMPLETED_DATE = SYSDATE
				where VEH_LOG_ID = :VEH_LOG_ID and ENT_UID_SUBMITTER = :ENT_UID_SUBMITTER";
	$qVars = array('VEH_LOG_ID' => $VEH_LOG_ID_NUMBER, 'REASON' => $reason, 'ENT_UID_SUBMITTER' => $tmpEntUid);
	if ($GLOBALS['DEBUG_DEBUG']) echoNow(Flex_Funcs::makeDivDataBar('--- function unLogVeh', $query . "<br>\n" . print_r($qVars, true), 'div_id_unLogV'));
	$mysqli2->sQuery($query, $qVars);
}



function getVehDesc($veh_or_bike)
{
	/***
	 * Returns array of friendly decriptions of various POST'ed uid's (i.e. make, model, color, etc)
	 * Used in vehicle_insert .php and bike_insert .php
	 * Note that $veh_desc['bike_plate_peruid'] is set in bike_insert .php
	 */

	global $mysqli2, $newEID;

	$veh_desc = array();


	$veh_desc['state'] = writeStates($_POST['STATE_UID'], true);

	if ($_POST['enter_make']) {
		// new make entered by user
		$veh_desc['make'] = $_POST['enter_make'];
	} else {
		$tmpAry = getVehMakes($_POST['veh_make'], true, $veh_or_bike);
		$veh_desc['make'] = $tmpAry[$_POST['veh_make']];
	}

	if ($_POST['enter_model']) {
		// new model entered by user
		$veh_desc['model'] = $_POST['enter_model'];
	} else {
		$tmpAry = getVehModels($_POST['veh_model'], '', true);
		$veh_desc['model'] = $tmpAry[$_POST['veh_model']];
	}

	/******** Not allowing to create new vehicle styles any longer
	if ($_POST['veh_style'] == 'new_veh_bk_styl') {
	$newInfo .= 'New Style: ' . $_POST['enter_style'] . "<br>\n";
	$_POST['veh_style'] = 2007; // bicycle is 2000
	$veh_desc['style'] = $_POST['enter_style'];  	} else {
	 ***/

	$tmpAry = GetVehStyles($veh_or_bike, true);
	$veh_desc['style'] = $tmpAry[$_POST['veh_style']];

	$tmpAry = GetVehColors($veh_or_bike, true);
	$veh_desc['color'] = $tmpAry[$_POST['veh_color']];

	return $veh_desc;
}
