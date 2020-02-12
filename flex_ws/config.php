<?php

abstract class Flex_Funcs
{
	//========= Default var values, many of which are overriden by children classes.
	public $path_base = '';
	public $function_path = '';
	public $ErrorNumber = '';
	public $ErrorDescription = ''; // don't display exact T2 errors.
	//public static $stopWatch		= '';
	protected $temp_return_vals = array(); // 2-dim array ALWAYS
	protected $WS_CONN = array(); // web service connection - see t2webservice below and 'WS_CONN' in database .php
	public $xml_version = '1.0';
	// Request type has three possibilities: 'HTTP POST', 'SOAP 1.1', or 'SOAP 1.2'
	// For the three types of requests, see http://128.196.6.197/PowerParkWS_N7/T2_Flex_Waitlists.asmx?op=InsertWaitlistRequest
	public $xml_request = 'HTTP POST';

	function __construct()
	{
		$t2ws		= new t2webservice();
		$this->WS_CONN	= $t2ws->get_WS_CONN();

		$this->path_base = $this->WS_CONN['WS_base'] . $this->flex_group . '.asmx';
		$this->function_path = $this->path_base . '/' . $this->flex_function;
		$this->name_of_class = get_class($this); // The name of the actual sub-class.
		Debug_Trace::traceFunc(__FILE__.':'.__LINE__ . ' | ' . 'May Need to change WS_cashUID, currently: ' . $this->WS_CONN['WS_cashUID']);

		// This only records SOME of the class instanciations.
		//$bt = debug_backtrace();// gets the info of the file which called this function (i.e. echo $myInstance)
		//$callingFile = pathinfo($bt[0]['file']);
		//$callingLine = $bt[0]['line'];
		//@$_SESSION['trax']['REQUEST_URI_TIME'][$_SERVER['REQUEST_URI']] .= "\n\t{".date('H:i:s') . ' - ' . $this->name_of_class
		//		. ' - ' . $callingFile['basename'] . ':' . $callingLine.'}';
	}

	function __toString()
	{
		/*******
		 * CAN NOW CALL STATICLY:
				echoNow(Flex_Funcs::makeDivDataBar('--- function XXXXXXXX', $query."<br>\n".print_r($qVars,true), 'div_id_111'));
		 * In the main program, simply echo the name of the class instance - i.e. echo $myInstance;
		 * Below all the class' data show by: print_r($this, true)
		 */

		//if ($GLOBALS['jody']) return '';

		if (@!$this->name_of_class) // Sometimes the __construct is never called
			$this->name_of_class = get_class($this); // The name of the actual sub-class.

		// Get rid of the body, the evidence!
		@$pass_save = $this->WS_CONN['WS_pass'];
		@$this->WS_CONN['WS_pass'] = 'PPPP****';
		if (@$this->password)
			$this->password = 'PP**';

		$bt = debug_backtrace(); // gets the info of the file which called this function (i.e. echo $myInstance)
		$callingFile = pathinfo($bt[0]['file']);
		$callingLine = $bt[0]['line'];

		// $dvID = 'cls_' . preg_replace('/[^\w\d]/si', '_', $callingFile) . $callingLine;
		$dvID = 'c_' . (@ ++$GLOBALS['class_ct']) . '_' . $callingLine;

		// $objThis = htmlentities(html_entity_decode(urldecode(print_r($this, true))));
		$objThis = print_r($this, true);


		// **************************** BEGIN SUB-BARS ************************************
		/***
		 * Within each expandable DIV bar, for arrays (columns) larger than three, make expandable data. Single click only, no double click to retract.
		 */
		$objThis = preg_replace('/'.'(\[\w[\w\d_]*\] => Array\n\s+\(\n[^\(\n]+?\n[^\(\n]+?\n[^\(\n]+?)'.'(\n[^\)]+?\s\)\n)'.'/si', '<!-- _EXPLOSIVE_BAR_START_ --><!-- _start_ -->$1<!-- _EXPLOSIVE_BAR_MID_ --><!-- _mid_ -->$2<!-- _EXPLOSIVE_BAR_END_ --><!-- _end_ -->', $objThis);
		$objAry = explode('<!-- _EXPLOSIVE_BAR_START_ -->', $objThis);
		if (sizeof($objAry)>1)
		{
			$objThis = '';
			foreach ($objAry as $k => $v)
			{
				$dvID_sub = $dvID.'_' . $k;
				$v_t = explode('<!-- _EXPLOSIVE_BAR_MID_ -->', $v);
				$v_t_END = explode('<!-- _EXPLOSIVE_BAR_END_ -->', @$v_t[1]);
				if (sizeof($v_t)>1 && !preg_match('/ => Array/si', $v_t_END[0]))
				{
					$et_ret = '';
					$et_ret .= $v_t[0];
					$et_ret .= "<br/><span onclick='showOrHide(\"".$dvID_sub."\");' style='padding-left:80px; cursor:pointer; color:green; font-weight:bold;'>Expand &gt;&gt;&gt;</span>";
					$et_ret .= "<span id='" . $dvID_sub . "' style='display:none; background: #F5F5F5;'>" . $v_t_END[0] . "</span>";
					$et_ret .= $v_t_END[1];
					$objThis .= $et_ret;
				}
				else
				{
					$objThis .= $v;
				}
			}
		}
		// **************************** END SUB-BARS ************************************ //


		$objThis = urldecode(html_entity_decode($objThis));

		// replace passwords of the form: "<password>pppppp" and password=ppppppp.
		$objThis = preg_replace('/(<{0,1}password\s*[\=>]\s*)\w+/si', '$1pPpP****', $objThis);

		$eReturn = '';
		if ($GLOBALS['DEBUG_CLASSES']) {
			$errNote = preg_match('/Error:/si', $this->return_page) ? '<span style="color:red;">Error found!</span>' : '';

			// The string "~~~ CLASS:" is searched for in Internal/logs/index.php
			$divBarTitle = "~~~ CLASS: " . $this->name_of_class . ". " . $errNote . " &nbsp; from: " . $callingFile['basename'] . ' : ' . $callingLine;

			$eReturn = Flex_Funcs::makeDivDataBar($divBarTitle, $objThis, $dvID);
		}

		@$this->WS_CONN['WS_pass'] = $pass_save;

		return $eReturn;
	}

	public function makeDivDataBar($divBarTitle, $divBarData, $divID) {
		/*		 * *
		 * Creates those wonderful, expandable <div> bars on the web for debugging purposes.
		 * $divBarTitle is the data which shows up on the clickable <div> bar.
		 * $divBarData can be any time - it is the data - expandable <div> bar clicked.
		 */

		//self::$stopWatch

		$divData = (is_object($divBarData) || is_array($divBarData)) ? var_export($divBarData, true) : $divBarData;

		$eStyle1 = $eStyle2 = '';

		if ($GLOBALS['DEBUG_DEBUG']) {
			/*			 * *****
			 * Manny classes have methods which call the ExecuteQuery class - so try to match the dotted border color
			 * of the ExecuteQuery class with it's calling class.
			 */
			if (@!is_array($GLOBALS['exe_match'])) {
				$GLOBALS['exe_match'] = array('trash');
				$GLOBALS['exe_color'] = array();
				$GLOBALS['exe_pop_colors'] = array('#EFEAFF', '#EFFFF4', '#EDFBFF', '#FFF5D6', '#EEA9F2', '#A9F2EC');
			}

			$name_of_class	= is_object($this) ? @$this->name_of_class	: 'Flex_Funcs::makeDivDataBar';
			$callingClass	= is_object($this) ? @$this->callingClass		: 'N/A';
			$QIDname			= is_object($this) ? @$this->QIDname			: 'N/A';

			if ($name_of_class == 'ExecuteQuery') {
				if (@$callingClass || @$QIDname) {
					$divBarTitle .= ' <span style="float:right; padding-right:2px;">calling Class: ' . $callingClass
							  . ' - query name: ' . $QIDname . '</span>';
				}
				if (@!$GLOBALS['exe_match'][$callingClass]) {
					$GLOBALS['exe_match'][$callingClass] = $callingClass;
					$GLOBALS['exe_color'][$callingClass] = array_shift($GLOBALS['exe_pop_colors']);
				}
				//xxx if (@$GLOBALS['exe_match'][$callingClass]){
				$eStyle1 = 'border:0 solid ' . $GLOBALS['exe_color'][$callingClass] . ';';
				$eStyle2 = 'background: ' . $GLOBALS['exe_color'][$callingClass] . '; font-size:9px; margin:0; font-weight:normal;';
			} else if (in_array($name_of_class, $GLOBALS['exe_match'])) {
				unset($GLOBALS['exe_match'][$name_of_class]);
				$eStyle1 = 'border:1px solid ' . $GLOBALS['exe_color'][$name_of_class] . ';';
				$eStyle2 = 'border:2px dashed ' . $GLOBALS['exe_color'][$name_of_class] . '; background: ' . $GLOBALS['exe_color'][$name_of_class] . '; font-size:11px; margin:1px; font-weight:bold;';
			} else {
				$eStyle1 = 'border:1px solid #CEEAFF;';
				$eStyle2 = 'border:1px dashed #CEEAFF; background: #E5E5E5; font-size:11px; margin:1px; font-weight:bold;';
			}
		}

		$e_ret = "<div style='padding:0; " . $eStyle1 . "'>";
		$e_ret .= "<div style='" . $eStyle2 . "'>";
		$e_ret .= "<div onclick='showOrHide(\"" . $divID . "\");' style='padding:0; cursor:pointer; color:#888;'>";
		$e_ret .= $divBarTitle . "</div></div>";
		$e_ret .= "<div id='" . $divID . "' style='display:none; font-size:10px; color:#444;' ondblclick='showOrHide(\"" . $divID . "\");'><pre>"
				  . $divData . "</pre></div>";
		$e_ret .= "</div>";
		return $e_ret;
	}

	protected function checkErrors($ignoreErrNumber = '') {
		/*********************
		 * Sets ErrorNumber, and loggs error if it is indeed an error.  Return value not currently used.
		 ************/

		$eReturn = '';
		if (($this->return_xml->T2ErrorList))
		{
			$ignoreErr = false;
			foreach ($this->return_xml->T2ErrorList as $k_e => $v_e)
			{
				switch (trim($v_e->Error->ErrorNumber))
				{
					/*					 * ************* Don't log these errors - they are not errors really. *************** */
					case 9267: // no citations found.
						$ignoreErr = true;
						break;
					case 2261: // Permit purchase count exceeded.
						$ignoreErr = true; // Definately need this here, because there can be multiple errors.
						break;
					case 9418: // FATAL Error: no permissions allocated
						break;
				}

				// $ignoreErrNumber might be comma-delimited numbers - see Flex_Permissions php
				if (!$ignoreErr && preg_match('/\d\d\d+/si', trim($v_e->Error->ErrorNumber)))
				{
					if (preg_match('/' . trim($v_e->Error->ErrorNumber) . '/si', $ignoreErrNumber))
						$ignoreErr = true;
				}

				$this->ErrorNumber = $v_e->Error->ErrorNumber;
				$this->ErrorDescription = preg_replace('/^(.*)\s\.\s?$/si', '$1.', $v_e->Error->ErrorDescription); // Get rid of space before period.
				$eReturn .= "ERROR: #" . $this->ErrorNumber . ": " . $this->ErrorDescription . "\n" . $this->flex_group . ", FUNCTION: " . $this->flex_function;
				if ($this->ErrorDescription)
					$this->ErrorDescription = '<div style="border:1px solid red; padding:1px; margin:0; font-weight:bold;">'.$this->ErrorDescription.'</div>';
			}
		}

		if ($ignoreErr)
		{
			$eReturn = '';
		}
		else if ($eReturn)
		{
			$fileErrStr = __FILE__ . ':' . __LINE__ . ' return vals: <REDUCED_HTML_START>' . $eReturn . "<REDUCED_HTML_END>" .
					  "\n------------\n" . $this->__toString() . "\n";
			logError('flexRelated.txt', $fileErrStr);
		}

		return $eReturn;
	}

	public function send_xml($ignoreErrNumber = '') {
		Debug_Trace::traceFunc(__FILE__.':'.__LINE__);
		/********************
		 * Process:
		 * 		- Creates the xml data via get_xml() function.
		 * 		- xml data is sent via HTTPD-POST (or SOAP) to the T2 function (function_path).  (See class PayMiscSaleItems for example of SOAP)
		 * 		- The returned xml data is then put into class-speciffic vars.
		 * Output:
		 * 		- Returned data from T2 function call are put into 2-dim array: $this->temp_return_vals[A_FIELD_NAME][0],  $this->temp_return_vals[A_FIELD_NAME][1], ...
		 * 		- Also returns debug output, including entire T2 output ($this->return_page) as well as $this->temp_return_vals
		 ***/


		/**************
		 * The get_xml (subclass-function, which calls this class' make_param function) will return something like this:
		  <GetAllCitations>
		  <Parameter>
		  <name>PLATE_NUMBER</name>
		  <value>516VPP</value>
		  </Parameter>
		  <Parameter>
		  <name>PLATE_STATE_UID</name>
		  <value>4</value>
		  </Parameter>
		  </GetAllCitations>
		 * get_xml then calls createPost function, which will put more crap onto the string.
		 ***/
		$this->post_data = $this->get_xml();

		// sock_er function already uses < ?xml version="' . $this->xml_version . '" encoding="utf-8"  ? >
		//if ($GLOBALS['jody'])
		//		$this->return_page = $this->curl_er(); // Returns string.
		//else
		$this->return_page = $this->sock_er(); // Returns string.

		if ($this->xml_request == 'SOAP 1.1')
		{
			$this->temp_return_vals = $this->fill_vals($this->return_page);
			// $this->return_xml = htmlentities(preg_replace('/</si', "\n&lt;", $this->return_page));
			if ($GLOBALS['DEBUG_DEBUG'])
			{
				// Can't use checkErrors function, so just do this:
				if (preg_match('/^.*<ErrorDescription>([^<][^<]+)<\/.*$/si', $this->return_page))
				{
					$this->ErrorDescription = preg_replace('/^.*<ErrorDescription>([^<][^<]+)<\/.*$/si', '$1', $this->return_page);
					$this->ErrorDescription = '<span style="font-weight:bold; font-size:1.2em; color:white; background-color:#c04; padding:0 2px 0 2px;">'.$this->ErrorDescription.'</span>';
				}
			}
		}
		else
		{
			$this->return_xml = $this->makeXML($this->return_page); // Returns an object.

			if (is_object($this->return_xml)) {
				// sets ErrorNumber
				$this->checkErrors($ignoreErrNumber);

				$this->temp_return_vals = $this->fill_vals($this->return_xml);
			} else {
				$ret_pg = $this->searchHtmlErrors($this->return_page, 'Error 503');
				if ($GLOBALS['DEBUG_DEBUG']) {
					$fileErrStr = __FILE__ . ':' . __LINE__ . "\n~~~~~ DEBUG_DEBUG log: return_xml not an object ~~~~~~~\n\$ret_pg: " .
							  $ret_pg . "\n\$this: " . print_r($this, true) . "\n~~~~~~~~~~~~~~~~~~~~\n";
					logError('flexRelated.txt', $fileErrStr);
				}
			}
		}

		Flex_Funcs::setVars($this->temp_return_vals);

		// Class-speciffic - set_callback is usually used to set more vars besides those set by Flex_Funcs::setVars,
		// or even to bypass Flex_Funcs::setVars alltogther.
		$this->set_callback();

		//$this->debug_stuff();
	}

	protected function searchHtmlErrors($returnPg, $errSearchStr) {
		/****
		 * Search returned web page for errors. Example: if $errSearchStr='Error 503', means web Service Unavailable.
		 * Shrink and echo $returnPg to just the error text (i.e. "Service Unavailable"), and also returns $returnPg (shrunk or not);
		 */
		if (preg_match('/' . preg_quote($errSearchStr) . '/si', $returnPg)) {
			$returnPg = preg_replace('/^.*?(' . preg_quote($errSearchStr) . '[^<]{1,50}).*$/si', '$1', $returnPg);
			$returnPg = strip_tags($returnPg);
			echo '<div align="center" style="padding: 6px; border: 2px dotted red; font-weight:bold; color:red; font-size:18px;">' .
			$returnPg . '<div style="font-size:16px;">Please try again later.</div></div><br>';
		}
		return $returnPg;
	}


	private function fill_vals($returnedXML)
	{
		Debug_Trace::traceFunc(__FILE__.':'.__LINE__);
		/*		 * ***********
		 * The RETURNED xml data is different for each class.
		 * Among other things, converts xml object to 2-dim array and returns it.
		 * set_callback functions may also do more to set class vars.
		 * ******* */

		$returnVals = array();

		switch ($this->name_of_class) {
			case 'PayMiscSaleItems': // Flex_MiscSaleItems.php
				$Field = 'RECEIPT_UID';
				$Value = (string) preg_replace('/^.*?<ReturnField>\s*' . $Field . '\s*<\/ReturnField>\s*<ReturnValue>\s*(\d+)\s*<\/ReturnValue>.*$/si', '$1', $returnedXML);
				if ($Value)
					$returnVals[$Field][] = $Value;
				break;

			case 'GetFacilityList':  // Flex_Occupancy.php
				/*				 * *****
				  $v looks something like this:
				  object(SimpleXMLElement)#70 (1) {
				  ["@attributes"]=>
				  array(2) {
				  ["UID"]=>
				  string(4) "2047"
				  ["Description"]=>
				  string(23) "1000 Block E 4th Street"
				  }
				  }
				 * ******* */
				foreach ($returnedXML->FacilityList->Facility as $k => $v) {
					$anAry = get_object_vars($v);
					$Field = (string) $anAry['@attributes']['UID'];
					$Value = (string) $anAry['@attributes']['Description'];
					$returnVals['theFacilityList'][$Field] = $Value;
				}
				break;

			case 'GetEligibleWaitlists': // Flex_Waitlists.php
				/*				 * *****
				  $v looks something like this:
				  object(SimpleXMLElement)#5 (1) {
				  [0]=>
				  string(4) "2036"
				  }
				 * ******* */
				$val_str = '';
				foreach ($returnedXML as $k => $v) {
					if ($val_str != '')
						$val_str .= ',';
					$val_str .= (string) $v;
				}
				$returnVals['theEligibleWaitlists'] = $this->fill_vars_hack($val_str);
				break;

			default:  // This is for most classes.
				// MAYBE ALSO TRY is_array($returnedXML->T2WsReturn->Return) || is_object($returnedXML->T2WsReturn->Return) || sizeof($returnedXML->T2WsReturn->Return)
				if (isset($returnedXML->T2WsReturn->Return))
				{
					foreach (@$returnedXML->T2WsReturn->Return as $k => $v)
					{
						$Field = (string) $v->ReturnField;
						$Value = (string) $v->ReturnValue;
						$returnVals[$Field][] = $Value;
					}
				}
				else if (is_string($returnedXML))
				{
					// NOT SURE IF THIS IS NEEDED.
					// Currently this is used only In Flex_IVR .php in the callback function
					//xxx if ($this->name_of_class=='ExecuteQueryX') $returnVals = $this->xml_parse_into_array($returnedXML);
				}
				break;
		}
		return $returnVals;
	}

	private function xml_parse_into_array($xml) {
		Debug_Trace::traceFunc(__FILE__.':'.__LINE__);
		/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		  DESCRIPCION: Convert XML string to array
		  -------------------------------------------------------------------- */
		$retorno = array();

		$p = xml_parser_create();
		xml_parser_set_option($p, XML_OPTION_TARGET_ENCODING, "ISO-8859-1");
		xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($p, $xml, $elementos, $llaves);
		xml_parser_free($p);

		$Qprocesados = 0;
		$retorno = $this->xml_sub_parse_into_array($elementos, $Qprocesados);

		return array($retorno, $Qprocesados);
	}

	private function xml_sub_parse_into_array($xml, &$i, $abierto = null) {
		$retorno = array();

		//----------------------------------------
		// Trivial case: TAG OUT
		//----------------------------------------
		if ($xml[$i]['type'] == 'complete' && isset($xml[$i]['value'])) {
			$retorno[$xml[$i]['tag']] = trim($xml[$i]['value']);
			$i ++;
			return $retorno;
			//----------------------------------------
			// Trivial case: TAG OUT
			//----------------------------------------
		} else if ($xml[$i]['type'] == 'cdata' && isset($xml[$i]['value'])) {
			$retorno = trim($xml[$i]['value']);
			$i ++;
			return $retorno;
			//----------------------------------------
			// Trivial case: TAG empty
			//----------------------------------------
		} else if ($xml[$i]['type'] == 'complete') {
			// $retorno = "<".$xml[$i]['tag']." />";
			$retorno[$xml[$i]['tag']] = null;
			$i ++;
			return $retorno;
			//----------------------------------------
			// Case recursive TAG WITH TAGS inside
			//----------------------------------------
		} else if ($xml[$i]['type'] == 'open') {
			$abierto = $xml[$i]['tag'];
			$nivel = $xml[$i]['level'];

			if (!isset($retorno[$abierto]))
				$retorno[$abierto] = array();

			if (isset($xml[$i]['value'])) {
				$retorno[$abierto][] = trim($xml[$i]['value']);
			}

			$i ++;
			$termino = false;
			while (!$termino && $i < count($xml)) {
				if ($xml[$i]['tag'] == $abierto &&
						  $xml[$i]['type'] == 'close' &&
						  $xml[$i]['level'] == $nivel) {
					$termino = true;
				} else {
					$retorno[$abierto][] = $this->xml_sub_parse_into_array($xml, $i, $abierto);
					if ($i < count($xml))
						if ($xml[$i]['type'] == 'close') {
							$i ++;
							return $retorno;
						}
				}
			}
			return $retorno;
		}
	}

	private function setVars($ary)
	{
		Debug_Trace::traceFunc(__FILE__.':'.__LINE__);
		/***
		 * Called above when class is instantiated. $ary is the 2-dim xml-retruned vars and values.
		 * The keys of $ary are should match the variable names within this class - THESE CLASS VARS CAN BE ARRAY OR NOT.
		 * 		#### CLASS VAR ARRAY EXAMPLE: a class var array CLAS_VAR is set here like so: $this->CLAS_VAR[0] = $ary[CLAS_VAR][0] = '159349';
		 */

		foreach ($ary as $varNames => $varVals)
		{
			$varNamesT = trim($varNames); // T2 don't trim some vars, like the space in [CONTRAVENTION_LIST_PARTIAL_PAYMENT_AMOUNT ]

			//	if ($GLOBALS['DEBUG_WARN'] && !is_array($this->{$varNamesT}) && !isset($this->{$varNamesT})) {
			//		// Check to make sure the class variable is declaired in this class.
			//		echo '<div style="font-weight:normal; color:grey;">The Var ##' . $varNamesT . '## not found - maybe in <strong>Return params</strong> just ' .
			//		'below, or maybe is NULL??  You might want to run function makeVars() once, during class createion, to create all the public vars.' .
			//		'Here are all the <strong>Return params</strong>: <!-- ' . print_r($ary, true) . ' -->';
			//		echo '</div>';
			//	}

			$tmpAry = array();

			if (is_array($varVals))
			{
				foreach ($varVals as $vvKey => $vvVal)
					$tmpAry[$varNamesT][$vvKey] = $vvVal;
			}

			foreach ($tmpAry as $aVarName => $aValAry)
			{
				if (@is_array($this->{$varNamesT}))
				{
					// Yep, have to do it this way. If $this->{$varNamesT} is an array,
					//		then it needs to be set to an array - can't do $this->{$varNamesT}[]
					$this->{$varNamesT} = $aValAry;
				} else {
					if ($GLOBALS['DEBUG_WARN'] && sizeof($aValAry) > 1) {
						echo '<pre><div style="font-weight:bold; color:red;">The Var ' . $varNamesT . ' is NOT an array, but the xml returned vlaue has more than 1 value!' .
						'Here is the xml return values: ' . print_r($aValAry, true) . '</div></pre>';
					}
					$this->{$varNamesT} = @$aValAry[0];
				}
			}
		}
	}

	//function makeVars($ary) {
	//	// To be called ONCE, during class creation. Echos public var declarations to be pasted into each class (not this class, but the classes that use it).
	//	echo '<br><b>@@@@@@@@@@@@ Var declarations @@@@@@@@@@@@</b><br>';
	//	foreach ($ary as $k => $v)	echo 'public $' . $k . "\t= '';\n";
	//	echo '<b>@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@</b><br>';
	//}

	protected function createPost($xml_data) {
		/*		 * *******************
		  $this->xml_request has three possibilities: 'HTTP POST', 'SOAP 1.1', or 'SOAP 1.2'
		  Prepare xml data for sock_er function.
		  For POST type, concat $xml_data, something like:
		  "xmldata=" . $xml_data . "&version=1.0&username=salesapiws&password=ppppppppppp"
		  Then return the whole string.
		 * ******************* */

		if ($this->xml_request == 'SOAP 1.1') { //-------- SOAP
			// sock_er function already uses utf-8 for SOAP: < ?xml version="' . $this->xml_version . '" encoding="utf-8"  ? >
			$poststring = '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
			  <soap:Body>
			  ' . trim($xml_data) . '
			  </soap:Body>
			</soap:Envelope>';
		} else if ($this->xml_request == 'SOAP 1.2') { //-------- SOAP.
		} else { //-------- 'HTTP POST'
			$poststring = '';
			$poststring .= 'xmldata=' . urlencode($xml_data) . '&';
			$poststring .= 'version=' . urlencode($this->xml_version) . '&';
			$poststring .= 'username=' . urlencode($this->WS_CONN['WS_user']) . '&';
			$poststring .= 'password=' . urlencode($this->WS_CONN['WS_pass']);
		}

		return $poststring;
	}


	private function sock_er()
	{
		/*************
		 * Send data using fsockopen, and return the return page string (less the $trim_ stuff)
		 ***/

		//------------- Return page - things to remove from return data
		// Dealing with multiple encodings: '< ? xml version="1.0" encoding="utf-8" ? >' and '< ? xml version="1.0" encoding="utf-16" ? >'
		$trim_top = '^[^<]*(<.*)$'; // Use $1. Removes header info on return page.
		$trim_xml = '<\?xml[^>]*>'; // Gets rid of ALL <\?xml [^>]+>
		// $trim_xml = '^(\s*[^\s]+.*)<\?xml[^>]*>(.*)$'; // using $1 and $2. Gets rid of <\?xml [^>]+>\s* which is NOT the first <tag> -- sometimes xml tags appears twice!
		$trim_fat = '~~~~~this trim_ thang is defined below~~~~~~~';

		//------------ Format data before sending.
		if ($this->xml_request == 'SOAP 1.1')
		{
			Debug_Trace::traceFunc(__FILE__.':'.__LINE__ . ' | ' . $this->xml_request . ' | ' . $this->flex_function);
			//-------- SOAP.
			$post_path = $this->path_base . " HTTP/1.1";
			$conType = "text/xml; charset=utf-8"; // text/xml; charset=ISO-8859-1  or  application/soap+xml; charset=utf-8
			$head_other = 'SOAPAction: "http://www.t2systems.com/' . $this->flex_function . '"' . "\r\n";
			// Olny return the MEAT!  only this part: <$this->flex_function> -- to -- </$this->flex_function>
			$trim_fat = '^.*?(<' . $this->flex_function . '>.*<\/' . $this->flex_function . '>).*$'; // Will replace with $1
		}
		else
		{
			//-------- 'HTTP POST'
			Debug_Trace::traceFunc(__FILE__.':'.__LINE__ . ' | ' . $this->xml_request . ' | ' . $this->flex_function);
			//-------- 'HTTP POST'
			$post_path = $this->function_path . " HTTP/1.1";
			$this->post_data = preg_replace('/\r?\n/si', '', $this->post_data);
			$conType = "application/x-www-form-urlencoded";
			$head_other = "";
			// Olny return the MEAT!  get rid of <string xmlns=xxx>...</string> enclosure
			// WRONG: $trim_fat = '^.*?<string xmlns=[^>]*>(.*)<\/string>\s*$'; // Will replace with $1
			$trim_fat = '';
		}

		$sslProto = '';
		if ($this->WS_CONN['WS_port'] == 443 || $this->WS_CONN['WS_port'] == 22)
			$sslProto = 'ssl://';

		$fp = fsockopen($sslProto . $this->WS_CONN['WS_domain'], $this->WS_CONN['WS_port'], $errno, $errstr, $timeout = 25);
		Debug_Trace::traceFunc(__FILE__.':'.__LINE__);

		if (!$fp)
			die("FTP ERROR: " . $errno . ":" . $errstr . "\n");

		//Build server request to be sent.
		$dat_out = '';
		$dat_out .= "POST " . $post_path . "\r\n";
		$dat_out .= "Host: " . $this->WS_CONN['WS_domain'] . "\r\n";
		$dat_out .= "Content-Type: " . $conType . "\r\n";
		$dat_out .= "Content-Length: " . strlen($this->post_data) . "\r\n";
		// Close, so feof doesn't hang on every fgets (no keep-alive)
		$dat_out .= "Connection: close" . "\r\n";
		$dat_out .= $head_other;
		$dat_out .= "\r\n";
		$dat_out .= $this->post_data . "\r\n";
		$dat_out .= "\r\n";


		// if ($GLOBALS['DEBUG_DEBUG']) debugEchos(__FILE__.':'.__LINE__, 'POST OUT (data SENT to '.$this->WS_CONN['WS_domain'].'): '.htmlentities(html_entity_decode(urldecode($dat_out))));

		//------------------------------- send request SOAP or regular
		fputs($fp, $dat_out, strlen($dat_out));

		Debug_Trace::traceFunc(__FILE__.':'.__LINE__ . '. fputs data to ' . $this->WS_CONN['WS_domain'] . '. See "POST OUT" debug data for flex_function ' . $this->flex_function);

		$dat_in_raw = '';
		while (!feof($fp)) {
			$dat_in_raw .= fgets($fp, 1024);
		}
		fclose($fp);
		Debug_Trace::traceFunc(__FILE__.':'.__LINE__);

		$dat_in_trim = $dat_in_raw;
		$dat_in_trim = unmake_htmlentities($dat_in_trim);

		$dat_in_trim = preg_replace('/' . $trim_top . '/si', '$1', $dat_in_trim);
		$dat_in_trim = preg_replace('/' . $trim_xml . '/si', '$1$2', $dat_in_trim);
		if ($trim_fat)
			$dat_in_trim = preg_replace('/' . $trim_fat . '/si', '$1', $dat_in_trim);

		// maybe don't need this, but I'm seeing some "utf-16" within xml tag - AFTER <$this->flex_function>
		$dat_in_trim = utf16_to_utf8($dat_in_trim);

		//if ($GLOBALS['jody']) {
		//	$pot_str = '@@@@@@@@@ Returned data from ' . $this->WS_CONN['WS_domain']
		//		. ' (flex_function '.$this->flex_function.') @@@@@@@@@@@@@' . "\n<br>" .
		//	'<strong>------@@@@@@@@@@ RAW version:</strong> ' .		htmlentities(html_entity_decode(urldecode($dat_in_raw))) . "\n<br>" .
		//	'<strong>------@@@@@@@@@@ trimmed version:</strong> ' .htmlentities(html_entity_decode(urldecode($dat_in_trim))) . "\n<br>" .
		//	'@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@' . "\n<br>";
		//	debugEchos(__FILE__.':'.__LINE__, $pot_str);
		//}

		return $dat_in_trim;
	}


	private function curl_er()
	{
		/*************************
		 * Send data using curl, and return the return page string (less the $trim_ stuff)
		 ******/

		//------------- Return page - things to remove from return data
		// Dealing with multiple encodings: '< ? xml version="1.0" encoding="utf-8" ? >' and '< ? xml version="1.0" encoding="utf-16" ? >'
		$trim_top = '^[^<]*(<.*)$'; // Use $1. Removes header info on return page.
		$trim_xml = '<\?xml[^>]*>'; // Gets rid of ALL <\?xml [^>]+>
		// $trim_xml = '^(\s*[^\s]+.*)<\?xml[^>]*>(.*)$'; // using $1 and $2. Gets rid of <\?xml [^>]+>\s* which is NOT the first <tag> -- sometimes xml tags appears twice!
		$trim_fat = '~~~~~this trim_ thang is defined below~~~~~~~';

		//------------ Format data before sending.
		if ($this->xml_request == 'SOAP 1.1')
		{
			//-------- SOAP.
			Debug_Trace::traceFunc(__FILE__.':'.__LINE__ . ' | ' . $this->xml_request . ' | ' . $this->flex_function);
			$post_path = $this->path_base;
			$conType = "text/xml; charset=\"utf-8\""; // text/xml; charset=ISO-8859-1  or  application/soap+xml; charset=utf-8
			$head_other = 1;
			// Olny return the MEAT!  only this part: <$this->flex_function> -- to -- </$this->flex_function>
			$trim_fat = '^.*?(<' . $this->flex_function . '>.*<\/' . $this->flex_function . '>).*$'; // Will replace with $1
		}
		else
		{
			//-------- 'HTTP POST'
			Debug_Trace::traceFunc(__FILE__.':'.__LINE__ . ' | ' . $this->xml_request . ' | ' . $this->flex_function);
			$post_path = $this->function_path;
			$this->post_data = preg_replace('/\r?\n/si', '', $this->post_data);
			$conType = "application/x-www-form-urlencoded";
			$head_other = 0;
			// Olny return the MEAT!  get rid of <string xmlns=xxx>...</string> enclosure
			// WRONG: $trim_fat = '^.*?<string xmlns=[^>]*>(.*)<\/string>\s*$'; // Will replace with $1
			$trim_fat = '';
		}

		//if ($GLOBALS['jody']) {
			//include_once 'stopwatch.php';
			//$GLOBALS['stopwatch'] = new StopWatch();
		//}

		$headers = array();
		//	$headers[] = "Host: ".$this->WS_CONN['WS_domain'];
		//	$headers[] = "POST " . $post_path . " HTTP/1.1";
		$headers[] = "Content-Type: " . $conType;
		$headers[] = "Accept: text/xml";
		$headers[] = "Cache-Control: no-cache";
		$headers[] = "Pragma: no-cache";
		if ($head_other)
			$headers[] = "SOAPAction: \"http://www.t2systems.com/" . $this->flex_function . "\"";
		$headers[] = "Content-Length: " . strlen($this->post_data);


		/*********************************************************************
		 * TO INVESTSIGATE:
		 * File cache!!!
				$fh = fopen('/var/www2/html/logs/cccuuu.txt', 'w');
				curl_setopt($ch_pg, CURLOPT_FILE, $fh);
		 *
		 * command-line, where t2soap.txt contains the soap envelope (the post_data):
				 curl --header "Content-Type: text/xml;charset=UTF-8" --header "SOAPAction:http://www.t2systems.com/ExecuteQuery" --data @t2soap.txt https://ARIZONA.t2flex.com/powerparkws/T2_Flex_Misc.asmx
		 */

		$ch_pg = curl_init();

		//	if ($GLOBALS['jody']) $GLOBALS['stopwatch']->show();

		// for debugging, sets values for curl_getinfo
		curl_setopt($ch_pg, CURLOPT_FILETIME, 1);
		curl_setopt($ch_pg, CURLOPT_VERBOSE, 1);
		curl_setopt($ch_pg, CURLINFO_HEADER_OUT, 1);

		//curl_setopt($ch_pg, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 ); // prevent cURL from trying IPv6 first.
		curl_setopt($ch_pg, CURLOPT_URL, 'https://'.$this->WS_CONN['WS_domain'] . $post_path);
		curl_setopt($ch_pg, CURLOPT_PORT , 443);
		curl_setopt($ch_pg, CURLOPT_POST, 1);
		curl_setopt($ch_pg, CURLOPT_RETURNTRANSFER, 1); // Makes curl_exec return result, instead of browser output
		curl_setopt($ch_pg, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch_pg, CURLOPT_POSTFIELDS, $this->post_data);
		curl_setopt($ch_pg, CURLOPT_HTTPHEADER, $headers);

		// curl_setopt($ch_pg, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		//curl_setopt($ch_pg, CURLOPT_HEADER, 0);
		//curl_setopt($ch_pg, CURLOPT_SSLVERSION, 3);
		//curl_setopt($ch_pg, CURLOPT_SSLCERT, getcwd() . "/client.pem");
		//curl_setopt($ch_pg, CURLOPT_SSLKEY, getcwd() . "/keyout.pem");
		//curl_setopt($ch_pg, CURLOPT_CAINFO, getcwd() . "/ca.pem");

		//if ($GLOBALS['jody']) $GLOBALS['stopwatch']->show();

		$dat_in_raw = curl_exec($ch_pg);

		//	if ($GLOBALS['jody']) {	//getinfo gets the data for the request
		//		$GLOBALS['stopwatch']->show();
		//		echo '<hr>$headers: ';	print_r($headers);	echo '<br>'."\n\n";
		//		echo '<hr>$this->post_data: ';	print_r($this->post_data);	echo '<br>'."\n\n";
		//		echo '<hr>$dat_in_raw: ';	var_dump($dat_in_raw);	echo '<br>'."\n\n";
		//		$info = curl_getinfo($ch_pg);
		//		echo '<hr>$info: ';	print_r($info); echo '<br>'."\n\n";
		//	}

		// if ($GLOBALS['jody']) $GLOBALS['stopwatch']->show();

		curl_close($ch_pg);
		//if ($GLOBALS['jody']) $GLOBALS['stopwatch']->show();


		$dat_in_trim = $dat_in_raw;
		$dat_in_trim = unmake_htmlentities($dat_in_trim);

		$dat_in_trim = preg_replace('/' . $trim_top . '/si', '$1', $dat_in_trim);
		$dat_in_trim = preg_replace('/' . $trim_xml . '/si', '$1$2', $dat_in_trim);
		if ($trim_fat)
			$dat_in_trim = preg_replace('/' . $trim_fat . '/si', '$1', $dat_in_trim);

		// maybe don't need this, but I'm seeing some "utf-16" within xml tag - AFTER <$this->flex_function>
		$dat_in_trim = utf16_to_utf8($dat_in_trim);

		//	if ($GLOBALS['jody']) {
		//		$pot_str = '@@@@@@@@@ Returned data from ' . $this->WS_CONN['WS_domain']
		//			. ' (flex_function '.$this->flex_function.') @@@@@@@@@@@@@' . "\n<br>" .
		//		'<strong>------@@@@@@@@@@ RAW version:</strong> '		. htmlentities(html_entity_decode(urldecode($dat_in_raw))) . "\n<br>" .
		//		'<strong>------@@@@@@@@@@ trimmed version:</strong> ' . htmlentities(html_entity_decode(urldecode($dat_in_trim))) . "\n<br>" .
		//		'@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@' . "\n<br>";
		//		debugEchos(__FILE__.':'.__LINE__, $pot_str);
		//	}
		return $dat_in_trim;
	}


	protected function makeXML($xmlStr)
	{
		Debug_Trace::traceFunc(__FILE__.':'.__LINE__);
		/****************
		 * INPUT: an xml string - a return page from the T2 $this->WS_CONN['WS_domain']
		 * Convert returned page into xml data, and return that xml data.
		 * Returns an object.
		 **********/

		$tmpXmlStr = $xmlStr;

		$tmpXmlStr = urldecode($tmpXmlStr); // just in case.
		// Just so error reporting is better, put some newlines in.
		$tmpXmlStr = preg_replace('/<Return>/si', "<Return>\n", $tmpXmlStr);

		// Got to replace '&' with a single '&amp;' for simplexml_load_string to work!
		$tmpXmlStr = preg_replace('/&amp;/si', "&", $tmpXmlStr); // first convert any '&amp;' to single '&'
		$tmpXmlStr = preg_replace('/&/si', "&amp;", $tmpXmlStr);

		// This function also gets rid of the FIRST usless unique tag enclosure - example <soap:bla>...</soap:bla>
		$returnObject = simplexml_load_string($tmpXmlStr);

		$tmpreturnObject = trim(htmlentities(html_entity_decode(urldecode(print_r($returnObject, true)))));
		// Trim off empty object stuff: only if $tmpreturnObject = "SimpleXMLElement Object ( )"
		$tmpreturnObject = preg_replace('/^SimpleXMLElement Object\s*\(\s*\)\s*$/si', '', $tmpreturnObject);


		if ($GLOBALS['DEBUG_DEBUG']) {
			//#################################### DEBUG_DEBUG errors only #############################
			$tmpStr = '';

			if (!is_object($returnObject))
				$tmpStr .= ' -- Hmmmmm, NON OBJECT for simplexml_load_string()<br>';
			if (!$tmpreturnObject)
				$tmpStr .= ' -- OBJECT APPEARS TO BE EMPTY!<br>';

			if ($tmpStr) {
				$tmpStr = '<pre>|||||||||||||||| DEBUG_DEBUG --- makeXML method in config.php |||||||||||||||||<br>' .
						  $tmpStr .
						  '<b>$returnObject object:</b>' . var_dump($returnObject, true) . '<br>' .
						  '<b>$returnObject</b> in string format (<b>$tmpreturnObject</b>):<br>' . $tmpreturnObject . '<br>' .
						  '<b>$tmpXmlStr</b> string parammeter which was passed to simplexml_load_string:<br>' . htmlentities($tmpXmlStr) . '<br><br>' .
						  '<small><b>And here my friend we have the $xmlStr</b> string (before massaging):<br>' . htmlentities($xmlStr) . '</small><br>' .
						  '<br>|||||||||||||||||||||||||<br></pre>';
				logError('flexRelated.txt', $tmpStr);
			}
		}

		return $returnObject;
	}

	private function debug_stuff()
	{
		if ($GLOBALS['DEBUG_DEBUG'])
		{
			$debugOut = '<br><pre>############################ function debug_stuff ##########################################' . '<small>' . "\n";
			if (!sizeof($this->temp_return_vals)) {
				$debugOut .= '<div style="font-weight:normal; color:#777700;">######### Nothing in return_xml object #########' . "\n" .
						  'Probably nothing though. If it is then might need to use set_callback function.' .
						  '</div>' . "\n\n";
			}
			$debugOut .= "###### XML POST DATA SENT, via get_xml() #####\n" .
					  html_entity_decode(print_r($this->post_data, true)) . "\n\n" .
					  '######### HTTP POST RETURN DATA ($this->temp_return_vals) #########' . "\n" .
					  print_r($this->temp_return_vals, true) . "\n\n" .
					  '######### RAW HTTP POST RETURN DATA ($this->return_page) #########' . "\n" .
					  html_entity_decode(print_r($this->return_page, true)) . "\n\n" .
					  '#### $this->return_xml #####' . "\n" .
					  print_r($this->return_xml, true) . "\n" .
					  '</small>###################################################################################</pre>';

			$GLOBALS['debugEchos_data'] .= $debugOut;
		}
	}

}

// end class

class t2webservice extends Flex_Funcs
{
	/***
	 * Genereate the credentials (array WS_CONN) in order to use T2 Web Services.
	 * Configure remote or local T2 FLEXADMIN schema.
	 * (In database.php, the PARKING schema is configured.)
	 */

	public function __construct() {
		// T2 Web service info (cont.) - common to both live and test:
		$this->conn_name = $GLOBALS['database_test_db'] ? $GLOBALS['test_db_conn_name'] : 'n8_live';
		$this->WS_CONN['conn_name'] = $this->conn_name;
		$this->WS_CONN['WS_user'] = 'salesapiws';
		$this->WS_CONN['WS_pass'] = 'salesws4';
		$this->WS_CONN['WS_cashUID'] = 2065; // Session ID, Code WS_SLAPI, same for both PowerParkWS and PowerParkWS_N7

		switch ($this->conn_name)
		{
			case 'n8_live':
				/***
				 * Configure remote or local T2 FLEXADMIN LIVE schema.
				 * (In database.php, the PARKING schema is configured.)
				 */
				$this->WS_CONN['WS_domain'] = 'ARIZONA.t2flex.com';
				$this->WS_CONN['PP_base'] = '/powerpark/'; // PowerPark
				$this->WS_CONN['WS_base'] = '/powerparkws/'; // PowerParkWS
				$this->WS_CONN['PP_URL'] = 'https://' . $this->WS_CONN['WS_domain'] . $this->WS_CONN['PP_base'];
				$this->WS_CONN['WS_port'] = 443;
				break;

			case 'n7_test':
				/***
				 * Configure remote or local T2 FLEXADMIN TEST schema.
				 * (In database.php, the PARKING schema is configured.)
				 */

				//	$this->WS_CONN['WS_domain'] = 'staging-arizona.t2flex.com';
				//	$this->WS_CONN['PP_base'] = '/UOASA1/';
				//	$this->WS_CONN['WS_base'] = '/UOASA1WS/';
				$this->WS_CONN['WS_domain'] = 'permtest-arizona.t2flex.com';
				$this->WS_CONN['PP_base'] = '/powerpark/'; // PowerPark
				$this->WS_CONN['WS_base'] = '/powerparkws/'; // PowerParkWS

				$this->WS_CONN['PP_URL'] = 'https://' . $this->WS_CONN['WS_domain'] . $this->WS_CONN['PP_base'];
				$this->WS_CONN['WS_port'] = 443;
				break;

			//	case 'n7_test':
			//	 // In database .php, the PARKING schema is 128.196.6.216 (node16o.node16.pts.arizona.edu)
			//		$this->WS_CONN['WS_domain'] = '128.196.6.222';
			//		$this->WS_CONN['PP_base'] = '/PowerPark/';
			//		$this->WS_CONN['WS_base'] = '/PowerParkWS/';
			//		$this->WS_CONN['PP_URL'] = 'http://' . $this->WS_CONN['WS_domain'] . $this->WS_CONN['PP_base'];
			//		$this->WS_CONN['WS_port'] = 80;
			//		break;

			//case 't2_staging':
			//	$this->WS_CONN['WS_domain'] = 'staging-1222.t2flex.com';
			//	$this->WS_CONN['PP_base'] = '/UAZSA1/';
			//	$this->WS_CONN['WS_base'] = '/UAZSA1WS/';
			//	$this->WS_CONN['PP_URL'] = 'https://' . $this->WS_CONN['WS_domain'] . $this->WS_CONN['PP_base'];
			//	$this->WS_CONN['WS_port'] = 443;
			//	break;
		}
	}

	public function get_WS_CONN() {
		return $this->WS_CONN;
	}

}

class InsertUpdateCustomFlds extends Flex_Funcs
{
	/*	 * *
	 * In the T2 documentation, the following web services have this class:
	 * 		'PERMISSION', 'ENTITY', 'CONTRAVENTION', 'ADJUDICATION', 'VEHICLE'
	 * In short, this inserts/updates/deletes value $cust_field_value into CUSTOM_DATA.CUD_VALUE.
	 * 		See also the notes below under INPUT XML Parameters.
	 * See also ExecuteQuery in Flex_Misc .php
	 *
	 * Custom Field. Example of a permit's "RFID NUMBER" in T2:
	 * 		In T2, see Configuration / Data Field Definitions / Table Name {PERMISSION/RFID_NUMBER}
	 * 		In FLEXADMIN DB, column DATA_DICTIONARY.DAD_FIELD = $cust_field_name ~~ RFID_NUMBER
	 */

	//--------------------- Input XML Parameters ALL REQUIRED ----------------------
	public $web_service = ''; // TAB_NAME. Must be a key in $web_service_names
	// Custom Field Name: This is the Query Name in the Query manager.
	// In Flex_Permissions .php, this param is set to $t2_query array.
	// (This is in col. DATA_DICTIONARY.DAD_FIELD) (The non-api way used DATA_DICTIONARY.DAD_UID)
	public $cust_field_name = ''; // DATA_DICTIONARY.DAD_FIELD
	public $uid_value = ''; // Will be the actual uid of a WEB SERVICE (i.e. $a_per_uid, $an_ent_uid, etc.)
	/*	 * *
	 * $cust_field_value - CUSTOM_DATA.CUD_VALUE (example: RFID # OR SUNGO # (assume $web_service='PERMISSION))
	 * If there is already a record in CUSTOM_DATA table, then same record will be updated with new CUD_VALUE.
	 * If updating a record, a blank space ' ' must be sent in order to delete the existing value for a parameter -
	 * 		the code here will actually convert it to "' '", because the xml needs the actual single-quotes
	 */
	public $cust_field_value = '';
	// is PERMISSION_UID related to CUSTOM_DATA.CUD_RECORD_UID ?????
	//* Example of a permit's "RFID NUMBER" in T2:
	//*		In T2, see Configuration / Data Field Definitions / Table Name {PERMISSION/RFID_NUMBER}
	//*		In FLEXADMIN DB, column DATA_DICTIONARY.DAD_FIELD = $cust_field_name ~~ RFID_NUMBER
	//public $cud_val				= ''; // CUSTOM_DATA.CUD_VALUE
	//----------------------------------------------------------------------------

	public $xml_data = '';
	public $post_data = '';

	//---------------------------- OUTPUT -------------------------------------
	public $return_page = '';
	public $return_xml = ''; // massaged return_page

	/*	 * *
	 * web_service_names. CONFIG_TABLE.TAB_NAME => flex_group
	 * In the T2 API, there are five web services which have InsertUpdateCustomFlds class.
	 * 		https://www.pts.arizona.edu/mis/T2_Flex_WebServices.php
	 */
	private $web_service_names = array(
		 'PERMISSION' => 'T2_FLEX_PERMISSIONS', // TAB_UID 10
		 'ENTITY' => 'T2_FLEX_ENTITIES', // TAB_UID 1
		 'CONTRAVENTION' => 'T2_FLEX_CONTRAVENTIONS', // TAB_UID 13
		 'ADJUDICATION' => 'T2_FLEX_ADJUDICATIONS', // TAB_UID 14
		 'VEHICLE' => 'T2_FLEX_VEHICLES'  // TAB_UID 3
	);
	public $ws_uid_name = ''; // Will be $web_service (TAB_NAME) with '_UID' appended (example: 'PERMISSION_UID')
	public $flex_group = ''; // A $web_service_names (example: 'T2_Flex_Permissions')
	public $flex_function = 'InsertUpdateCustomFlds';

	public function __construct($web_service, $cust_field_name, $uid_value, $cust_field_value) {
		Debug_Trace::traceFunc(__FILE__.':'.__LINE__);

		$this->web_service = $web_service;
		$this->flex_group = $this->web_service_names[$this->web_service];
		$this->ws_uid_name = $this->web_service . '_UID';
		$this->cust_field_name = $cust_field_name;
		$this->uid_value = $uid_value;
		// A value of ' ' means to delete existing value.
		$this->cust_field_value = ($cust_field_value == ' ') ? "' '" : $cust_field_value;
		if (ctype_digit($this->cust_field_value))
			$this->cust_field_value = (int) $this->cust_field_value;

		parent::__construct();

		if (!$this->flex_group || !$cust_field_name || !isset($uid_value) || !isset($cust_field_value)) {
			$debugStr = '';
			if ($GLOBALS['DEBUG_DEBUG'])
				$debugStr = "<br>debug data: "
						  . "InsertUpdateCustomFlds($this->web_service, $this->cust_field_name, $this->uid_value, $this->cust_field_value)";
			exitWithBottom('ERROR: MISSING WEB SERVICE VALUE.' . $debugStr);
		}

		//if ($GLOBALS['DEBUG_DEBUG']) {
		//	  // show this here because we get strange errors from this class.
		//	  echo '<small>########******* InsertUpdateCustomFlds - before calling send_xml function ********########<pre>';
		//	  var_dump($this);
		//	  echo '##############******************************************************#################<br></pre></small>';
		//}

		$this->send_xml();
	}

	protected function get_xml() {
		Debug_Trace::traceFunc(__FILE__.':'.__LINE__);
		/*		 * **  This function returns xml data.  ** */
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param($this->ws_uid_name, $this->uid_value);
		$this->xml_data .= make_param($this->cust_field_name, $this->cust_field_value);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}

	protected function set_callback() {
		// Called from config.php  - usually to set more vars besides those set by Flex_Funcs::setVars
	}

}

// ############################ Support Functions ##########################################


function make_param($p_name, $p_val)
{
	/*	 * **********
	 * Most subclasses use this.
	 * Returns something like this:
	  <Parameter>
	  <name>PLATE_NUMBER</name>
	  <value>516VPP</value>
	  </Parameter>
	 * ********** */
	$p_val = unmake_htmlentities($p_val);
	$p_val = preg_replace('/</si', '&lt;', $p_val);
	$p_val = preg_replace('/>/si', '&gt;', $p_val);

	$return_p = "<Parameter>
	 <name>" . trim($p_name) . "</name>
	 <value>" . $p_val . "</value>
	</Parameter>";
	return $return_p;
}

/**
 * Will output in a similar form to print_r, but the nodes are xml so can be collapsed in browsers
 * @param mixed $mixed
 * */
function print_r_xml($mixed, $version = '1.1')
{
	// capture the output of print_r
	$out = print_r($mixed, true);

	// Replace the root item with a struct
	// MATCH : '<start>element<newline> ('
	$root_pattern = '/[ \t]*([a-z0-9 \t_]+)\n[ \t]*\(/i';
	$root_replace_pattern = '<struct name="root" type="\\1">';
	$out = preg_replace($root_pattern, $root_replace_pattern, $out, 1);

	// Replace array and object items structs
	// MATCH : '[element] => <newline> ('
	$struct_pattern = '/[ \t]*\[([^\]]+)\][ \t]*\=\>[ \t]*([a-z0-9 \t_]+)\n[ \t]*\(/miU';
	$struct_replace_pattern = '<struct name="\\1" type="\\2">';
	$out = preg_replace($struct_pattern, $struct_replace_pattern, $out);
	// replace ')' on its own on a new line (surrounded by whitespace is ok) with '</var>
	$out = preg_replace('/^\s*\)\s*$/m', '</struct>', $out);

	// Replace simple key=>values with vars
	// MATCH : '[element] => value<newline>'
	$var_pattern = '/[ \t]*\[([^\]]+)\][ \t]*\=\>[ \t]*([a-z0-9 \t_\S]+)/i';
	$var_replace_pattern = '<var name="\\1">\\2</var>';
	$out = preg_replace($var_pattern, $var_replace_pattern, $out);

	$out = trim($out);
	$out = '<data>' . $out . '</data>';

	return $out;
}


function utf16_to_utf8($str)
{
	$c0 = ord($str[0]);
	$c1 = ord($str[1]);

	if ($c0 == 0xFE && $c1 == 0xFF) {
		$be = true;
	} else if ($c0 == 0xFF && $c1 == 0xFE) {
		$be = false;
	} else {
		return $str;
	}

	$str = substr($str, 2);
	$len = strlen($str);
	$dec = '';
	for ($i = 0; $i < $len; $i += 2) {
		$c = ($be) ? ord($str[$i]) << 8 | ord($str[$i + 1]) :
				  ord($str[$i + 1]) << 8 | ord($str[$i]);
		if ($c >= 0x0001 && $c <= 0x007F) {
			$dec .= chr($c);
		} else if ($c > 0x07FF) {
			$dec .= chr(0xE0 | (($c >> 12) & 0x0F));
			$dec .= chr(0x80 | (($c >> 6) & 0x3F));
			$dec .= chr(0x80 | (($c >> 0) & 0x3F));
		} else {
			$dec .= chr(0xC0 | (($c >> 6) & 0x1F));
			$dec .= chr(0x80 | (($c >> 0) & 0x3F));
		}
	}
	return $dec;
}


class cacheT2 extends Flex_Funcs
{
	public $dirLoc		= '/var/www2/cacheT2/';
	public $uaid		= '';
	public $fileName	= '';
	public $serialObj	= '';

	public function __construct($uaid)
	{
		$this->uaid	= $uaid;
//		$this->testDbStr = $GLOBALS['datab']
	}

	public function seralizeObj($objName, $obj)
	{
		if ($this->uaid && is_object($obj) && $objName)
		{
			$this->fileName	= $this->dirLoc . $this->uaid . '_' . $objName . '.ser';
			$this->serialObj	= serialize($obj);
			$fp	= fopen($this->fileName, "w");
			fwrite($fp, $this->serialObj);
			fclose($fp);
		}
	}

	public function unseralizeObj($objName)
	{
		$this->fileName	= $this->dirLoc . $this->uaid . '_' . $objName . '.ser';
		if (file_exists($this->fileName))
		{
			$this->serialObj	= implode("", @file($this->fileName));
		}
		else
		{
			$this->serialObj	= '';
		}
		return unserialize($this->serialObj);
	}

	public function removeSerialFile($obj, $objName)
	{
		$this->fileName	= $this->dirLoc . $this->uaid . '_' . $objName . '.ser';
		if (file_exists($this->fileName))
			unlink ($this->fileName);
	}
}


?>