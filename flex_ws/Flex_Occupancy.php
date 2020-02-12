<?php

require_once '/var/www2/include/flex_ws/config.php';




/*********************************************************************************************
 * *******************************************************************************************
 *
 *            class "ChangeFacilityCapacity" is in Flex_Facility .php.
 *
 * *******************************************************************************************
 * *******************************************************************************************/



abstract class Flex_Occupancy extends Flex_Funcs
{
	// Documentation at http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf

	public $flex_group = 'T2_Flex_Occupancy';
}



class GetFacilityList extends Flex_Occupancy
{
	/************************************
	 * Outputs 2-dim array of facility UID (key) and Description
	************************************/
	// http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=61

	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';

	//------- Input
	// NOTHING

	//------- Output
	// 2-dim array of facility UID (key) and Description (value) - for each facility.
	//   Facility UID can be seen also in in WAITLIST.FAC_UID_FACILITY.
	//   theFacilityList[FAC_UID] = Description
	public $theFacilityList	= array();
	public $sqlHist			= array();


	public $flex_function	= 'GetFacilityList';

	public function __construct()
	{
		/**********************************
		***********************************/
		parent::__construct();
		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

	protected function set_callback()
	{
		//Called from config.php  - usually to set more vars besides those set by Flex_Funcs::setVars
		//######## php sort() function seems to have a bug!
		// ksort($this->theFacilityList);
	}
}


class GetOccupancyData extends Flex_Occupancy
{

	/*****################################################################################
	 *
	 * Use Oracle table instead -- FLEXADMIN.FAC_HIST_OCCUPANCY_COUNTS or FLEXADMIN.FAC_HIST_OCCUPANCY (which is updated 15 mins via task scheduler)
	 * http://128.196.6.197/PowerParkWS_N7/T2_Flex_Occupancy.asmx?op=GetOccupancyData says: "This test form is only available for requests from the local machine"
	 *
	####################################################################################*/

	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';

	//------- Input
	protected $facilityUid		= '0';	// NOT Required. The Facility UID for which you are retrieving occupancy data. Set to 0 to return results for every facility in the system
	protected $customFieldList	= '';	// NOT Required. Complex type - collection of the following parameters used to indicate any custom field(s) on the facility: String (Field Value).

	/****------- Output
	OccupancyDataResult - the results string includes a facility UID (FAC_UID) and Description for each
	facility returned as well as a data set for each occupancy type that includes the following:
		 Capacity (total number of spaces)
		 Occupied (number of filled spaces)
		 Available (number of available spaces)
		 Timestamp (date/time for the occupancy snapshot)
	Information for the Parking Operation is also returned, including the site name and T2 Flex version.
	*****/
	public $OccupancyDataResult	= ''; // Includes a facility UID (FAC_UID) and Description for each facility


	public $flex_function	= 'GetOccupancyData';

	public function __construct($p_1='', $p_2='')
	{
		/**********************************
		***********************************/
		parent::__construct();
		if ($p_1 != '') {
			// facilityUid default is 0, which means returning all data for all facilities.
			$this->facilityUid	= $p_1;
		}
		if ($p_2 != '') {
			$this->customFieldList	= $p_2;
		}
		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		//$this->xml_data .= make_param('facilityUid', $this->facilityUid);
		//$this->xml_data .= make_param('customFieldList', $this->customFieldList);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

	protected function set_callback()
	{
		//Called from config.php  - usually to set more vars besides those set by Flex_Funcs::setVars
	}
}


?>