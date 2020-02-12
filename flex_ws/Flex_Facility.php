<?php

require_once '/var/www2/include/flex_ws/config.php';


abstract class Flex_Facility extends Flex_Funcs
{
	// Documentation http://128.196.6.222/PowerParkWS_N7/T2_Flex_Facility.asmx?op=ChangeFacilityCapacity

	public $flex_group = 'T2_FLEX_FACILITY';

	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';

	protected function set_callback()
	{
		//Called from config.php  - usually to set more vars besides those set by Flex_Funcs::setVars
	}
}


class ChangeFacilityCapacity extends Flex_Facility
{
	/***
	 * ChangeFacilityCapacity Parameter
			Complex type* collection of the following parameters used to apply the given results:
			ƒÞ FacilityUid (integer)
			ƒÞ AddOccupancy (integer)
			ƒÞ occupancyType (int, required)
			ƒÞ capacity (int, required)
			ƒÞ threshold (int, required)
			ƒÞ release (int, required)
			ƒÞ allow (bool, required)
			ƒÞ mixedMode (bool)
	 */

	public $xml_request	= 'SOAP 1.1'; // Default in config .php is HTTP POST
	public $return_xml	= ''; // massaged return_page
	public $flex_function	= 'ChangeFacilityCapacity';

	//-------------- Input
	public $FacilityUid		= '';	// int
	public $AddOccupancy		= array(
		'occupancyType'	=> '',	// int
		'capacity'	=> '',			// int
		'threshold'	=> '',			// int
		'release'	=> '',			// int
		'allow'	=> '',				// bool
		'mixedMode'	=> '',			// bool, unused, for future
	);

	//-------------- Output

	public function __construct($FacilityUid, $occupancyType, $capacity, $threshold, $release, $allow)
	{
		Debug_Trace::traceFunc();
		parent::__construct();

		// --------------------------------------------------------- Table is FLEXADMIN.FACILITY_CAPACITY
		$this->FacilityUid	= $FacilityUid; // FAC_UID_FACILITY
		$this->AddOccupancy['occupancyType']	= $occupancyType; // OTL_UID_OCCUPANCY_TYPE (1 = transient)
		$this->AddOccupancy['capacity']			= $capacity;		// FCM_DEFAULT_CAPACITY Read existing capacity and add/delete alter amount.
		$this->AddOccupancy['threshold']			= $threshold;		// FCM_DEFAULT_FULL_THRESHOLD
		$this->AddOccupancy['release']			= $release;			// FCM_DEFAULT_FULL_RELEASE
		$this->AddOccupancy['allow']				= $allow;			// Just read FCM_ALLOW_ENTRY_WHEN_FULL and send same value back in to 'allow' param.
		$this->AddOccupancy['mixedMode']			= ''; // 0;		//

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
		 <FacilityUid>'.$this->FacilityUid.'</FacilityUid>
			<Capacities>
				<CapacityRec>
					<OccupancyTypeUid>'.$this->AddOccupancy['occupancyType'].'</OccupancyTypeUid>
					<Capacity>'.$this->AddOccupancy['capacity'].'</Capacity>
					<Threshold>'.$this->AddOccupancy['threshold'].'</Threshold>
					<Release>'.$this->AddOccupancy['release'].'</Release>
					<IsEntryAllowedWhenFull>'.$this->AddOccupancy['allow'].'</IsEntryAllowedWhenFull>
				</CapacityRec>
			</Capacities>
		</parameter>
		</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);

		return $this->post_data;
	}
}

?>