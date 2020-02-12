<?php

require_once '/var/www2/include/flex_ws/config.php';

abstract class Flex_Misc extends Flex_Funcs
{
	// Documentation at PAGE=42: http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=42

	public $flex_group = 'T2_Flex_Misc';
	public $xml_data = '';
	public $post_data = '';
	public $return_page = '';

	/*	 * *
	 * $t2_query[Query Name] = Query UID (QUERY_DEF.QDE_NAME => QUERY_DEF.QDE_UID)
	 * For use with API ExecuteQuery class (in Flex_Misc .php)
	 * Query UIDs and Query Names found here: http://128.196.6.197/PowerPark/qm/default.aspx
	 */
	protected static $t2_query = array(
		'Q_get_document_note'		=> 7493,
		'Q_lotFullIncidents'			=> 7628,
	);

	protected function set_callback()
	{
		//Called from config.php  - usually to set more vars besides those set by Flex_Funcs::setVars
	}
}


class get_document_note extends Flex_Misc
{
	//--------- output
	public $img_ary = array(); // key is filename, and value is base64 binary string.

	public function __construct($doc_name, $regExpBegin = '^', $regExpImg = '.*\.(png|jpg|jpeg|gif)$')
	{
		Debug_Trace::traceFunc();
		/*		 * *******************************************
		 * Hack to get document from DOCUMENT table.
		 * In citation_functions, $doc_name is the cite number
		 * ********************************************* */
		if ($doc_name) {

			// $regExpBegin - Regular Expressions are much faster when searching from beginning of string.
			$searchStr = $regExpBegin . $doc_name . $regExpImg;

			$tmpResults1 = array();

			if ($GLOBALS['jody'] && 0)
			{
				/*				 * *
				 * Not working because can't figure out how to convert blob to bimary, here is the query Q_get_document_note:
				 * select DOC_UID, DOC_FILE_NAME, DOC_FILE_SIZE, TO_BLOB(DOC_FILE) as DOC_FILE
				 * from FLEXADMIN.DOCUMENT where regexp_like(DOC_FILE_NAME,:paramOne)
				 * Maybe look for php the functionbase64_encode_image.
				 */
				//	$t2Key = 'Q_get_document_note';
				//	$param_ary = array('reg exp search' => $doc_name);
				//	$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
				//	if ($GLOBALS['DEBUG_DEBUG'])
				//		echo $exeResults."<br/>\n";
				//	$tmpResults1 = $exeResults->results_custom;
			}
			else
			{
				//=================== OLD SCHOOL
				$localConn1 = new database();
				$searchQuery = "select DOC_UID, DOC_FILE_NAME, DOC_FILE_SIZE, DOC_FILE from FLEXADMIN.DOCUMENT
					where regexp_like(DOC_FILE_NAME,:searchStr)";
				$qVars = array('searchStr' => $searchStr);
				$localConn1->sQuery($searchQuery, $qVars, false, false); // Last param is for returning binary data.
				$tmpResults1 = $localConn1->results;
			}

			if (sizeof($tmpResults1['DOC_FILE_NAME'])) {
				for ($i = 0; $i < sizeof($tmpResults1['DOC_FILE_NAME']); $i++) {
					$filename = $tmpResults1['DOC_FILE_NAME'][$i];
					$this->img_ary[$filename] = $tmpResults1['DOC_FILE'][$i];
				}
			}
		}
	}

}

class InsertUpdateNotes extends Flex_Misc {
	/*	 * ****
	  This method is used to insert a Note associated with an entity, vehicle, permission, contravention, or adjudication record.
	  Example: InsertAdjudication function returns Adjudication_Uid, which is passed in here as OBJECT_REFERENCE_UID.
	  If a Note UID (note_uid_num) is NOT passed in then insert a new note.
	  If a Note UID (note_uid_num) IS passed in then update existing.
	 * ***** */

	public $return_xml = ''; // massaged return_page
	//----- Input params
	protected $note_uid_num = ''; // required? No if inserting Yes if updating
	protected $NOTE_TYPE_UID = 2004; // required? Yes for insert. FLEXADMIN.NOTE_TYPE_MLKP table: 6=other, 2004=Additional Notes, 2005=Web Appeal FOTO.
	protected $OBJECT_TYPE = 14; // required? Yes if inserting.  Poss vals: 1 (for Entities), 3 (Vehicles), 10 (Permissions), 13 (Contraventions), or 14 (Adjudications).
	protected $NOTE_TEXT = ''; // required? Yes if inserting No if updating
	protected $NOTE_END_DATE = ''; // Note end date. Format: MM/DD/YY
	protected $OBJECT_REFERENCE_UID = ''; // required? Yes if inserting.  UID of the object to which the Note is attached. (i.e. Adjudication_Uid)
	//------ Natural XML Output (case-sensitive):
	public $Note_Uid = ''; // UID of Note that was successfully inserted, or updated. (same as $note_uid_num if updating.)
	public $flex_function = 'InsertUpdateNotes';

	public function __construct($NOTEUID, $TEXT, $REF_UID, $OBJ_TYPE = '', $TYPE_UID = '') {
		parent::__construct();
		$this->note_uid_num = $NOTEUID;
		$this->NOTE_TEXT = $TEXT;
		$this->OBJECT_REFERENCE_UID = $REF_UID;
		if ($OBJ_TYPE)
			$this->OBJECT_TYPE = $OBJ_TYPE; // class-defaulted above.
		if ($TYPE_UID)
			$this->NOTE_TYPE_UID = $TYPE_UID; // class-defaulted above.

		$this->NOTE_END_DATE = date('m/d/Y');
		$this->send_xml();
	}

	protected function get_xml() {
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('NOTE_UID', $this->note_uid_num);
		$this->xml_data .= make_param('NOTE_TYPE_UID', $this->NOTE_TYPE_UID);
		$this->xml_data .= make_param('NOTE_TEXT', $this->NOTE_TEXT);
		$this->xml_data .= make_param('NOTE_END_DATE', $this->NOTE_END_DATE);
		$this->xml_data .= make_param('OBJECT_TYPE', $this->OBJECT_TYPE);
		$this->xml_data .= make_param('OBJECT_REFERENCE_UID', $this->OBJECT_REFERENCE_UID);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

}

$GLOBALS['wsAvailable'] = false;

class IsFlexAvailable extends Flex_Misc
{
	/*	 * *
	 * This method is used for diagnostic purposes, to determine whether the T2 Flex Web Services can be called.
	 * Exit if Flex WS is NOT available and if $exit is true.
	 * Set $GLOBALS['wsAvailable'] to 1 if method was successfully called.
	 */

	public $return_xml = ''; // massaged return_page
	//----- Return params
	// Using global var so as to optimize: $GLOBALS['wsAvailable'] -- initialized above

	public $flex_function = 'IsFlexAvailable';

	public function __construct($exit = true)
	{
		if (!$GLOBALS['wsAvailable'])
		{
			parent::__construct();
			// $this->NOTE_END_DATE			= date('m/d/Y');
			$this->send_xml();
			$GLOBALS['wsAvailable'] = $this->return_xml[0];
		}

		if (!$GLOBALS['wsAvailable']) {
			if ($exit) {
				exitWithBottom('<div align="center" style="border:3px solid #cc3300; padding:4px; margin:28px 8px 28px 8px; font-family:Courier; color:#cc3300; font-weight:bold; font-size:1.2em;">Database connection error, please try again later.</div>');
			}
		}
	}

	protected function get_xml() {
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';
		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

}


class ExecuteQuery extends Flex_Misc
{
	/*	 * *
	 * T2 API way to do sql queries.
	 * Log into T2 as "webquery" to create queries.
	 * Various functions use this if checkEnabled('T2 API ...') is true.
	 * Sql Parameters - see https://www.pts.arizona.edu/T2%20Flex%20Web%20Services%20Reference%20v7.5.pdf#page=11
	 * $sql_query_params -- array keys may have a descriptive name but will not be used - only the array
	  values are needed.  The order of the array items is important, so for example, the first value you
	  assign to the array, like $sql_query_params['Cust. Entity UID']='33333', will be converted
	  to $this->paramX['paramOne']='33333'.
	 * This is how the T2 Query mgr takes in the xml parameter names: 'paramOne', 'paramTwo', 'paramThree', ...
	 *
	 * $num_params - size of $sql_query_params
	 * A query in the query manager can have default values for each parameter 'paramOne', 'paramTwo', ...
	 * so therefore we may need to set any empty parameters to a double-blank space '  ' or something.  If the
	 *
	 * See ExecuteQuery examples below.
	 * See also InsertUpdateCustomFlds in config .php
	 */

	//-------------- Input params
	// An html link to T2, with $queryUID as GET param.
	public $queryUIDLink = '';
	// Query UID in Flex's Query mgr. (In Flex_Permissions .php, this param is set to $t2_query array.)
	public $queryUID = '';
	// The next two are used for debugging
	public $callingClass = ''; // NOT required. The class (or function name) which calls this class - Example: GetCurrentWaitlists.
	public $QIDname = ''; // NOT required. The T2 query name (the name of the QID) - Example: Q_GetCurrentWaitlists.
	public $sql_query_params = array();
	public $ownerName = 'webquery'; // informational purposes only, for now.
	//--------------- Output
	public $results_custom = array(); // trimmed-down version of $results_min - set by setCustomResults
	public $results_min = array(); // this is the trimmed-down version of $temp_return_vals.
	public $num_params = 0; // sizeof($sql_query_params)
	public $paramX = array(); // Like $sql_query_params but keys replaced with paramOne, paramTwo...
	public $xml_request = 'SOAP 1.1'; // Default in config .php is HTTP POST
	public $return_xml = ''; // massaged return_page
	public $flex_function = 'ExecuteQuery';

	public function __construct($query_uid, $sql_query_params = array(), $QIDname = '', $callingClass = '')
	{
		Debug_Trace::traceFunc();
		parent::__construct();

		//if (!is_array($sql_query_params))
		//	exitWithBottom('ERROR: Execute Array Not Found!');

		if (ctype_alnum($query_uid))
			$this->queryUIDLink = '<a href="'.$this->WS_CONN['PP_URL'].'qm/qbwizard.aspx?id='.$query_uid.'" target="_blank" style="border:1px solid blue; text-decoration:none; padding:0 1px 0 1px; margin:0;">T2 QID '.$query_uid.'</a>';
		$this->queryUID = $query_uid;
		$this->QIDname = $QIDname;
		$this->callingClass = $callingClass;
		$this->sql_query_params = $sql_query_params; //========== will be converted to paramOne, paramTwo...
		$this->num_params = sizeof($this->sql_query_params);


		if ($GLOBALS['DEBUG_DEBUG']) {
			$tmpErr = '';
			if (!$this->queryUID)
				$tmpErr .= 'DEBUG: WARNING: class ExecuteQuery has empty parameters: queryUID'."<br/>\n";
			if ($tmpErr)
				echo '<div align="left" style="font-size:14px; font-weight:normal; border:2px solid #c03; padding:2px; margin:20px 8px 20px 8px; color:#c03;">'.$tmpErr.'</div>';
		}

		if ($this->num_params) {
			foreach ($this->sql_query_params as $qk => $qv) {
				//========= will be converted below to paramOne, paramTwo...
				if ($this->sql_query_params[$qk] == '')
					$this->sql_query_params[$qk] = '  ';
			}
		}

		$paramCt = 0;
		foreach ($this->sql_query_params as $qk => $qv) {
			$paramCt++;
			switch ($paramCt) {
				case '1':
					$this->paramX['paramOne'] = $qv;
					break;
				case '2':
					$this->paramX['paramTwo'] = $qv;
					break;
				case '3':
					$this->paramX['paramThree'] = $qv;
					break;
				case '4':
					$this->paramX['paramFour'] = $qv;
					break;
				case '5':
					$this->paramX['paramFive'] = $qv;
					break;
				case '6':
					$this->paramX['paramSix'] = $qv;
					break;
				case '7':
					$this->paramX['paramSeven'] = $qv;
					break;
				case '8':
					$this->paramX['paramEight'] = $qv;
					break;
				case '9':
					$this->paramX['paramNine'] = $qv;
					break;
				case '10':
					$this->paramX['paramTen'] = $qv;
					break;
				default:
					exitWithBottom('Error in calling class ' . $callingClass . ': Too many parameters for "ExecuteQuery" - max is Ten');
					break;
			}
		}

		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';

		// Note how this is a SOAP xml:  $this->xml_request == 'SOAP 1.1'
		$this->xml_data .= "\n" . '<' . $this->flex_function . ' xmlns="http://www.t2systems.com/">
			  <version>' . $this->xml_version . '</version>
			  <username>' . $this->WS_CONN['WS_user'] . '</username>
			  <password>' . $this->WS_CONN['WS_pass'] . '</password>
			  <queryUID>' . $this->queryUID . '</queryUID>';

		$xml_sql_params = '';
		foreach (@$this->paramX as $p_name => $p_value) {
			if ($p_value != '') {
				$xml_sql_params .= "
					<QueryParameter>
						<Field>" . $p_name . "</Field>
						<Value>" . $p_value . "</Value>
					</QueryParameter>";
			}
		}
		if ($xml_sql_params) {
			// Note strange case-sensitivity: queryParameters vs QueryParameter
			$this->xml_data .= "
			<queryParameters>" . $xml_sql_params . "
			</queryParameters>";
		}

		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);

		if ($GLOBALS['DEBUG_DEBUG']) {
			//	echo "<div><pre>xml_data:".htmlentities(preg_replace('/'.$this->WS_CONN['WS_pass'].'/si', '****', $this->xml_data))."<br/>";
			//	echo '</pre></div>';
		}
		return $this->post_data;
	}

	protected function set_callback()
	{
		/***
		 * Called from config.php -- may need to tweak function xml_parse_into_array.
		 */

		preg_match_all('/<RECORD>(.*?)<\/RECORD>/si', $this->return_page, $r_matches, PREG_PATTERN_ORDER);


		if ($GLOBALS['DEBUG_DEBUG'] && !sizeof($r_matches[0]))
		{
			// No records found, look for errors.
			if (preg_match('/does not exist in the database/si', $this->return_page)) {
				echo '<div align="left" style="font-size:12px; border:2px solid #c03; padding:1px; margin:4px; color:#c03;">Debug data: Class ExecuteQuery: variable return_page: ' . $this->return_page . '</div>';
			}
		}

		$rownum = -1;
		foreach ($r_matches[0] as $k1 => $aRecord)
		{
			//##### todo: use [$rownum] below on: $this->results_custom[$aColumn][], so as to be $this->results_custom[$aColumn][$rownum]
			$rownum++;
			$r_results = simplexml_load_string($aRecord);

			foreach ($r_results as $colName => $colVal)
				$this->results_custom[$colName][$rownum] = @$r_results->{$colName} ? (string) $r_results->{$colName} : '';

			//xxxxxxxxxxxxxxxxxxxx
			//// Make sure all the column names found in current record are a subset of $this->select_cols
			//foreach ($r_results as $colName => $colVal) {
			//	if ($colName && !in_array($colName, $this->select_cols)) {
			//		$errMsg = 'Error: Columns do not match.';
			//		if ($GLOBALS['DEBUG_DEBUG']) {
			//			$errMsg = 'Debug Note, Error: Some column names (' . $colName . ') found in the T2 query* not found in PHP array*.';
			//			foreach ($r_results as $colName => $colVal) @$tmpCols[] = $colName;
			//			$errMsg .= '(Just<br>search for "' . $this->QIDname . '", which is used in a function call to ExecuteQuery)<br>'
			//					  . '<pre>* T2 query cols: ' . print_r($tmpCols, true) . '<br>'
			//					  . '* PHP array t2_cols[\'' . $this->QIDname . '\']:' . print_r($this->select_cols, true) . '</pre>'
			//					  . 'PHP calling class: ' . $this->callingClass . '<br>';
			//		}
			//		exitWithBottom('<div align="left" style="font-size:14px; font-weight:normal; border:2px solid #c03; padding:2px; margin:20px 8px 20px 8px; color:#c03;">' . $errMsg . '</div>');
			//	}
			//}
			//foreach ($this->select_cols as $k2 => $aColumn) //##### todo: use [$rownum] here:
			//	$this->results_custom[$aColumn][] = @$r_results->{$aColumn} ? (string) $r_results->{$aColumn} : '';
		}
	}

	public function setMoreCustom($column_name, $custom_type) {
		/*		 * *
		 * Modify the data in the array results_custom[$column_name][n] = data.
		 * $custom_type can be set to the following:
		 * 		'date_1'   - remove oracle's time 'T' char - will set all data values to YYYY-mm-dd hh:mm:ss (military time)
		 * 		'date_ora' - converts dates to oracle (i.e.: 2013-12-02T09:00:00)
		 */
		if ($this->results_custom[$column_name]) {
		foreach ($this->results_custom[$column_name] as &$columnVal) {
			if ($columnVal != '') {
				switch ($custom_type) {
					case 'date_1':
						// JODY 2015-03-23  added IF condition to remove 'T' and the "-nn:00" thing (i.e. -05:00)
						if (preg_match('/^([^T]+)T([^\-]+)\-.\d.*$/s', $columnVal, $matches))
						{
							//echo '~~~~~~~~~~~~~~~~~~~$columnVal: ' . $columnVal . '<br>';
							//echo '~~~~~~~~~~~~~~~~~~~$matches[1]: ' . $matches[1] . '<br>';
							//echo '~~~~~~~~~~~~~~~~~~~$matches[2]: ' . $matches[2] . '<br>';
							//echo '~~~~~~~~~~~~~~~~~~~$columnVal AAA: ' . $columnVal . '<br>';
							$columnVal = date('Y-m-d H:i:s', strtotime($matches[1].' '.$matches[2]));
							// echo '~~~~~~~~~~~~~~~~~~~$columnVal BBB: ' . $columnVal . '<br>';
						}
						else
						{
							$columnVal = date('Y-m-d H:i:s', strtotime($columnVal));
						}
						break;

					//	case 'date_ora':
					//		$columnVal = date('Y-m-d\TH:i:s', strtotime($columnVal));
					//		break;
				}
			}
		}
		}
	}

	/*	 * ****
	  public function setCustomResults($results_min, $iterations) {
	 * Refine $this->results_min even more, into $this->results_custom.
	 * Try to make $this->results_custom look like $localConn->results
	 * $iterations is the number of times to recursively call this function - it should match the
	 * 		array dimension of $results_min
	 * EXAMPLE of a non-recursive way of accomplishing this - 4 iterations:
	 * 		foreach ($exeResults->results_min as $k1 => $v1)
	 * 			foreach ($v1 as $k2 => $v2)
	 * 				foreach ($v2 as $k3 => $v3)
	 * 					foreach ($v3 as $k4 => $v4)
	 * 						$exeResults->results_custom[$k4][] = $v4;
	  $iterations--;
	  foreach ($results_min as $k => $v) {
	  if ($iterations && is_array($results_min[$k])) {
	  $this->setCustomResults($results_min[$k],$iterations);
	  } else {
	  $this->results_custom[$k][] = $v;
	  //if ($GLOBALS['DEBUG_DEBUG']) echo '<pre>===='.$iterations.':..:..:<b>'.$k.'</b><br>'.print_r($v,true);
	  } } }	***** */

}




class getLotFullIncidents extends Flex_Misc
{
	/***
	 * This is a new class, and will replace pretty much all "getMaxIncident" stuff in park_classes/garage_status.class .php.
	 * First this class constructor will look for data in the PARKING schema (put into $allIncidents):
	 *		If the PARKING schema data is less thaon one minute then use it's data.
	 *		If it's more than one minute old, then the Q_lotFullIncidents t2 query runs, and re-populates the PARKING schema table (then update $allIncidents).
	 * NOTE: 2058 is AZ historical society.
	 */

	public $requery_seconds		= 60;			// How many seconds till we query T2 and update PARKING scheme with fresh pipen' hot data.
	public $allIncidents			= array(); // ALL records from PARKING schema - will be updated with live data, if new T2 data found and parking schema updated.
	public $maxLocalIncUid		= 0;		  // Get the max INC_UID_INCIDENT from local PARKING SCHEMA.
	public $newT2Incidents		= array(); // To contain fresh records from T2 (where INC_UID > $this->maxLocalIncUid), so as to update PARKING schema.
	public $maxT2Incidents		= array(); // see getAllMaxRecords function
	public $allLocalUpdates		= array(); // just for debugging - shows all the Update queries made to parking schema.

	public function __construct()
	{
		$localConn2 = new database(); // PARKING SCHEMA ONLY

		$this->allIncidents		= $this->getAllIncidents($localConn2); // all 8 rows from PARKING.FLEX_LOT_FULL_MAX_INCIDENT
		// maxLocalIncUid Should be same as MAX $this->allIncidents
		$this->maxLocalIncUid	= $editMaxNewIncUid ? $editMaxNewIncUid : $this->getMaxIncUid($localConn2);

		$editFacUid = $editMaxNewIncUid = '';

		/***
		if ($GLOBALS['jody'])
		{
			$editFacUid			= ''; // Leave empty to do all facilities, otherwise use a fac_uid like 2038 (AZ Historical)
			//* TODO, MAYBE: put this info in showTestCaseMessage function
			//* PARKING.FLEX_LOT_FULL_MAX_INCIDENT, the column INC_UID_INCIDENT must have
			//* a max value (all rows) less than $maxNewIncUid (make all of them like $maxNewIncUid - 2)
			//* In T2 Documentation, see "How to Find Out Sign State and Resynch a Sign" --
			//* http://help.t2systems.com:8080/robohelp/robo/server/general/projects/FOLH/Monitoring_System/Enable_Disable_Lot_Full_Signs.htm
			$editMaxNewIncUid	= 34475000; // 0 is default. current live max incident uid's are well over 34116380 (in PARKING.FLEX_LOT_FULL_MAX_INCIDENT)
			echo '<div style="border:1px solid orange; font-weight:bold; padding:2px; margin:11px;">Debug Note: ';
			if ($this->maxLocalIncUid > $editMaxNewIncUid)
			{
				echo 'The maxLocalIncUid changed from '.$this->maxLocalIncUid.' to '.$editMaxNewIncUid;
				$this->maxLocalIncUid = $editMaxNewIncUid;
			}
			else
			{
				echo 'PROBLEM: Set $editMaxNewIncUid to a value lower than the maxLocalIncUid ('.$this->maxLocalIncUid.')';
			}
			echo '</div>';
		}
		***/

		// Check if data in local PARKING schema is more than one minute old; if so then update with fresh hot T2 data.
		if ($this->dataIsOld($localConn2) || $editMaxNewIncUid)
		{
			$this->newT2Incidents = $this->getNewIncidents($this->maxLocalIncUid);

			if (sizeof($this->newT2Incidents['INC_UID']))
			{
				// NOTE: the Q_lotFullIncidents query has "ORDER BY FAC_UID_FACILITY, INC_UID DESC", but it is not even needed because getAllMaxRecords finds the max.
				$this->maxT2Incidents = $this->getAllMaxRecords($this->newT2Incidents);

				for ($i=0; $i<sizeof($this->allIncidents['INC_UID_INCIDENT']); $i++)
				{
					// Check to see if local facility's INC_UID_INCIDENT ($facUid) is < maximum INC_UID.
					// If so, then update local PARKING schema with fresh hot t2 data - for current facility $facUid.
					$facUid = $this->allIncidents['FAC_UID_FACILITY'][$i];
					if (@$this->maxT2Incidents['INC_UID'][$facUid] && $this->allIncidents['INC_UID_INCIDENT'][$i] < $this->maxT2Incidents['INC_UID'][$facUid])
					{
						// LOT_FULL, CONTROL
						// 2015-03-11 Jody: fixed the following due to daylight savings time mismatch, also got rid of 'T':
						//	to_date('" . $this->maxT2Incidents['INC_DATE'][$facUid]."','YYYY-MM-DD\"T\"HH24:MI:SS\"-05:00\"')
						$INC_DATE = preg_replace('/T/', ' ', $this->maxT2Incidents['INC_DATE'][$facUid]);
						$timeDiff = preg_replace('/^.+(\-\d\d:\d\d)$/', '$1', $INC_DATE);
						$uQuery = "update PARKING.FLEX_LOT_FULL_MAX_INCIDENT
							set INC_UID_INCIDENT = " . $this->maxT2Incidents['INC_UID'][$facUid] . ",
								INC_DATE_INCIDENT = to_date('".$INC_DATE."', 'YYYY-MM-DD HH24:MI:SS\"".$timeDiff."\"'),
								INC_INFO_INCIDENT = '" . $this->maxT2Incidents['INC_INFO'][$facUid] . "', DT_CHECK = SYSDATE, LOT_IS_FULL = '" . $this->maxT2Incidents['LOT_IS_FULL'][$facUid] . "',
								SET_BY_CONTROL = '" . $this->maxT2Incidents['SET_BY_CONTROL'][$facUid] . "'
							where FAC_UID_FACILITY = " . $facUid;
						$localConn2->sQuery($uQuery);
						if ($GLOBALS['DEBUG_DEBUG']) echo "<hr>Debug data: $uQuery<hr>";
						$this->allLocalUpdates[] = $uQuery; // for debugging.
					}
				}
			}
		}
		if (sizeof($this->allLocalUpdates))
			$this->allIncidents = $this->getAllIncidents($localConn2);
	}


	private function dataIsOld($localConn2)
	{
		/***
		 * PARKING schema: See if the data in FLEX_LOT_FULL_MAX_INCIDENT table is more than one minute old.
		 * OUTPUT: TRUE if data is > than one minute old; else FALSE.
		 */
		$oneMinuteAgo = time() - $this->requery_seconds; // unix time stamp
		$searchQuery = "select TO_CHAR(max(DT_CHECK),'MM/DD/YYYY HH:MI:SS AM') as DT_CHECK_MAX from PARKING.FLEX_LOT_FULL_MAX_INCIDENT";
		$localConn2->sQuery($searchQuery);
		$is_old = (strtotime($localConn2->results['DT_CHECK_MAX'][0]) < $oneMinuteAgo);
		//	if ($GLOBALS['DEBUG_DEBUG']) {
		//		echo '@@@@@@@@@@ Line ' . __LINE__ . ': $is_old=' . $is_old . ' ; COMPARE local ('.date('m/d/Y H:i:s',strtotime($localConn2->results['DT_CHECK_MAX'][0])).') with one min ago: '
		//		. '('.date('m/d/Y H:i:s',strtotime($localConn2->results['DT_CHECK_MAX'][0])).') ..... IS_OLD=' . $is_old."<br/>\n";
		//	}
		return $is_old;
	}


	private function getAllMaxRecords($newT2Incs)
	{
		/***
		 * Reduce the $newT2Incs array into $maxAry: reduce to only records with highest INC_UID for EACH facility.
		 * Also finds out wether the record was a Manual or Automatic insert.
		 * OUTPUT:  i.e. $maxAry[col name][distinct facility uid] = value
		 *				$maxAry will also calculate LOT_IS_FULL and SET_BY_CONTROL:
		 *					If $maxAry[LOT_IS_FULL][distinct facility uid] is TRUE: means "LOT FULL"; FALSE means "OPEN".
		 *					If $maxAry[SET_BY_CONTROL][distinct facility uid] is TRUE: means change was made by T2 (was NOT manually set).
		 */

		global $editFacUid, $editMaxNewIncUid;

		// Find the max INC_UID's of each garage facility from $newT2Incs.
		$tmpMaxNewIncs = $tmpAry = array();
		for ($i=0; $i<sizeof($newT2Incs['INC_UID']); $i++)
		{
			// this gets all rows - useing "max" below for each $tmpAry[$facUid]
			$facUid = $newT2Incs['FAC_UID_FACILITY'][$i];
			$tmpAry[$facUid][] = $newT2Incs['INC_UID'][$i];
		}
		// now widdle down $tmpAry to only max inc uids.
		foreach($tmpAry as $aFacUid => $facIncAry)
		{
			$tmpMaxNewIncs[$aFacUid] = max($facIncAry);
			if ($editMaxNewIncUid)
			{
				if ($editFacUid=='' || $aFacUid==$editFacUid)
				{
					$tmpMaxNewIncs[$aFacUid] = $editMaxNewIncUid;
				}
			}
		}
		if ($editMaxNewIncUid)
		{
			foreach($tmpMaxNewIncs as $aFacUid => $facIncAry)
			{
				$this->showTestCaseMessage($aFacUid, $editMaxNewIncUid);
			}
		}

		$maxAry = array();
		for ($i=0; $i<sizeof($newT2Incs['INC_UID']); $i++)
		{
			if (in_array($newT2Incs['INC_UID'][$i], $tmpMaxNewIncs))
			{
				$aFacUid = $newT2Incs['FAC_UID_FACILITY'][$i];

				$maxAry['INC_UID'][$aFacUid]				= $newT2Incs['INC_UID'][$i];
				$maxAry['FAC_UID_FACILITY'][$aFacUid]	= $newT2Incs['FAC_UID_FACILITY'][$i];
				$maxAry['FAC_DESCRIPTION'][$aFacUid]	= $newT2Incs['FAC_DESCRIPTION'][$i];
				$maxAry['INC_DATE'][$aFacUid]				= $newT2Incs['INC_DATE'][$i];
				$maxAry['INC_INFO'][$aFacUid]				= $newT2Incs['INC_INFO'][$i];

				/****
				 Possible values of FLEXADMIN.FACILITY.INC_INFO (see Q_lotFullIncidents query):
					Facility is manually set to full and will ignore count data. Hit Disable Lot Full to return to count mode
					Facility is manually set to not full and will ignore count data. Hit Enable Lot Full to return to count mode
					Facility manual set to not full has been removed, now using count data for full status
					Facility manually set to full has been removed, now using count data for full status
					Setting facility to full because facility enable lot full was sent from Flex
					Setting facility to full because facility is full according to Flex, but the magnetic firmware is reporting as not full
					Setting facility to NOT full because facility disable lot full was sent from Flex
					Setting facility to NOT full because facility is NOT full according to Flex, but the magnetic firmware is reporting full
				 */
				$maxAry['LOT_IS_FULL'][$aFacUid]		= preg_match('/to full/si', $maxAry['INC_INFO'][$aFacUid])							? 1 : 0;
				$maxAry['LOT_IS_FULL'][$aFacUid]		= preg_match('/to full has been removed/si', $maxAry['INC_INFO'][$aFacUid])	? 0 : $maxAry['LOT_IS_FULL'][$aFacUid];
				$maxAry['SET_BY_CONTROL'][$aFacUid]	= preg_match('/according to Flex/si', $maxAry['INC_INFO'][$aFacUid])				? 1 : 0;
			}
		}
		return $maxAry;
	}


	private function getNewIncidents($max_local_IncUid)
	{
		/***
		 * Get any new records (Incidents) in T2 where T2's INC_UID > $max_local_IncUid
		 * INPUT:  $max_local_IncUid
		 * OUTPUT: Array of new T2 records (Incidents).
		 * THE SELECT QUERY:
		 *		... FROM FLEXADMIN.INCIDENT INNER JOIN FLEXADMIN.FACILITY ON FLEXADMIN.INCIDENT.FAC_UID_FACILITY = FLEXADMIN.FACILITY.FAC_UID
WHERE FLEXADMIN.INCIDENT.INC_UID > :paramOne AND (FLEXADMIN.INCIDENT.ITL_UID_INCIDENT_TYPE In (503,504))
ORDER BY FAC_UID_FACILITY, INC_UID DESC
		 */
		$t2Key = 'Q_lotFullIncidents';
		$param_ary  = array($max_local_IncUid);
		$exeResults = new ExecuteQuery(self::$t2_query[$t2Key], $param_ary, $t2Key, get_class($this));
		if ($GLOBALS['DEBUG_DEBUG']) echo $exeResults; // "\nParams: " . print_r($param_ary,true)."<br/>\n";
		return $exeResults->results_custom;
	}


	private function getMaxIncUid($localConn2)
	{
		/***
		 * PARKING schema: Get the max INC_UID_INCIDENT from FLEX_LOT_FULL_MAX_INCIDENT table.
		 * OUTPUT: max INC_UID_INCIDENT
		 */
		$searchQuery = "select max(INC_UID_INCIDENT) as MAX_INC_UID_INCIDENT from PARKING.FLEX_LOT_FULL_MAX_INCIDENT";
		$localConn2->sQuery($searchQuery);
		return $localConn2->results['MAX_INC_UID_INCIDENT'][0];
	}
	private function getAllIncidents($localConn2)
	{
		/***
		 * Return all data from PARKING.FLEX_LOT_FULL_MAX_INCIDENT table.  Organized into array.
		 */
		$searchQuery = "select * from PARKING.FLEX_LOT_FULL_MAX_INCIDENT";
		$localConn2->sQuery($searchQuery);
		return $localConn2->results;
	}


	private function showTestCaseMessage($eFacUid, $maxNewIncUid)
	{
		echo '<div align="left" style="font-size:14px; font-weight:normal; border:2px solid #c03; padding:2px; margin:10px; color:#c03;">'
			. 'For facility UID "'.$eFacUid.'" ($eFacUid);  The max<br>'
			. 'inc_uid changed to: "'.$maxNewIncUid.'" ($maxNewIncUid) - because we are trying<br>'
			. 'to make it so that the latest incident looks something like "Facility is manually <br>set to full...".<br>'
			. 'Also, in table PARKING.FLEX_LOT_FULL_MAX_INCIDENT, the column INC_UID_INCIDENT must have<br>'
			. 'a max value (all rows) less than $maxNewIncUid (make all of them <br>like $maxNewIncUid - 2).</div>';
	}
}

?>