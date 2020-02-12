<?php

/*******************************************************
 *	Configure local PARKING schema (live or test) - for T2 WS see flex_ws/config.php
 */

class database
{
	var $results;
	var $rows;
	var $nrows;
	var $error;
	var $production = true;
	var $web_down = false;
	public $connID;
	public $callingFile = '';
	public $connection_name = '';
	public $serviceName = '';
	public $dbHost = '';
	public $WS_CONN = array();
	public $t_start = ''; // debugging - time class starts.
	public $t_end = ''; // debugging - time class ends.
	public $t_diff = ''; //
	private $persistant_pooled = false;
	private $identifier; // will be set to oci_connect, or oci_pconnect with POOLED (if $persistant_pooled is true)
	public $cForm;
	private $uuu;
	private $ppp;

	function __construct($is_live_db = true, $connectionName = 'n8_live')
	{
		/****
		 * A connection is NOT made to Oracle in the constructor here - only when a query is made - like sQuery.
		 */

		// If database_test_db is true then force into non-production (TEST DB) mode.
		$this->production = $GLOBALS['database_test_db'] ? false : $is_live_db;

		if ($this->production) {
			$this->connection_name = $connectionName ? $connectionName : 'n8_live';
		} else {
			// Non-production TEST db -- see flex_ws/config.php
			$this->connection_name = $GLOBALS['test_db_conn_name'];
		}

		$db_conn = $this->selectDb($this->connection_name);

		$this->dbHost = $db_conn['dbHost'];
		$this->serviceName = $db_conn['serviceName'];
		$this->uuu = $db_conn['uuu'];
		$this->ppp = $db_conn['ppp'];

		// sets the 'WS_CONN' two-dim array. (re-checks if using test db - i.e. $this->connection_name)
		$t2ws = new t2webservice();
		$this->WS_CONN = $t2ws->get_WS_CONN();

		if ($GLOBALS['DEBUG_ERROR']) {
			//echo '<hr>################## oh uh, .......<br>';
			// var_dump($this);
		}

		unset($db_conn);

		if ($GLOBALS['database_test_db'])
			$this->persistant_pooled = false;
		$p_str = $this->persistant_pooled ? '(SERVER=POOLED)' : '';

		$this->identifier = '
			(
			DESCRIPTION =
				(ADDRESS_LIST =
					(ADDRESS = (PROTOCOL=TCP) (HOST=' . $this->dbHost . ') (PORT=1521))
				)
				(CONNECT_DATA = (SERVICE_NAME=' . $this->serviceName . ') ' . $p_str . ')
			)';

		if ($GLOBALS['DEBUG_ERROR']) {
			// Record calling file and line nubmer.
			$call = debug_backtrace(false);
			$this->callingFile = preg_replace('/^.*\/html\/(.*)$/si', '$1', $call[0]["file"]) . '(' . $call[0]["line"] . ')';
		}
	}

	private function selectDb($conn_name)
	{
		/*******************************************
		 * Returns DB connection data for PARKING schema - no connection or instance made here.
		 * (T2 FLEXADMIN schema is configured in flex_ws/config.php)
		 * ######### TO DO: Merge gr/database_pts.php and database.php
		 ******************************************* */

		$d_conns = array();

		switch ($conn_name) {
			case 'n8_live':
				/***
				 * ################### Only for local LIVE PARKING schema.
				 * (T2 FLEXADMIN schema is configured in flex_ws/config.php)
				 */
				$d_conns['dbHost'] = '128.196.6.211';
				$d_conns['serviceName'] = 'node11o.node11.pts.arizona.edu';
				$d_conns['uuu'] = 'parking'; // parking    flexadmin
				$d_conns['ppp'] = 'mis4pts'; // mis4pts    flexadmin4pts23
				// T2 Web service access 'WS_CONN' vars are now in flex_ws/config .php
				break;

			case 'n7_test':
			case 'n7_test_local':
				/***
				 * ################### Only for local testing PARKING schema.
				 * (T2 FLEXADMIN schema is configured in flex_ws/config.php)
				 */
				$d_conns['dbHost'] = '128.196.6.216';
				$d_conns['serviceName'] = 'node16o.node16.pts.arizona.edu';
				$d_conns['uuu'] = 'flexadmin'; // parking    flexadmin
				$d_conns['ppp'] = 'flexadmin4pts'; // mis4pts    flexadmin4pts23
				// T2 Web service access 'WS_CONN' vars are now in flex_ws/config .php
				break;

				//case 't2_staging':
				//	$d_conns['dbHost'] = 'orastaging-UAZSA1.t2hosted.com';
				//	$d_conns['serviceName'] = 'UAZSA1';
				//	$d_conns['uuu'] = 'jbrabec';
				//	$d_conns['ppp'] = 'sssssssss';
				//	// T2 Web service access (now in config .php):
				//	break;
		}
		return $d_conns;
	}


	private function bindVars($O_BBN_st, $O_BBN_vars)
	{
		// $O_BBN_vars could be $qVars.
		//	Example: $qVars('ENT_UID'=>$entuid) -- so then $obbn_var_name will be the array key, and ${$O_BBN_varName} will be the value.
		//	If $obbn_var_name exists and ${$O_BBN_varName} does not, then continue.
		// Give nice long variable names ($O_BBN_) so no conflict with php/oracle variable binding.
		$O_BBN_i = 0;
		foreach ($O_BBN_vars as $O_BBN_oraVar => $O_BBN_val) {
			$obbn_var_name = ':' . $O_BBN_oraVar;
			$O_BBN_varName = $O_BBN_val . $O_BBN_i;
			${$O_BBN_varName} = str_replace("'", "''", stripslashes($O_BBN_val));

			if (is_array(${$O_BBN_varName}))
				$O_BBN_size = sizeof(${$O_BBN_varName});
			else
				$O_BBN_size = strlen(${$O_BBN_varName});

			//jjj June 27, 2012 - made continue so as to avoid WARNINGS in error log: PHP Warning: oci_bind_by_name(): ORA-01036:...
			// On second thought, maybe it would be good to record warnings.
			// if ($obbn_var_name != ':' && ${$O_BBN_varName} == '')  continue;

			@oci_bind_by_name($O_BBN_st, $obbn_var_name, ${$O_BBN_varName}, $O_BBN_size);
			$O_BBN_i++;
		}
	}


	private function bindSizeVars($O_BBN_st, $O_BBN_sizeVars)
	{
		foreach ($O_BBN_sizeVars as $O_BBN_name => $O_BBN_size)
			oci_bind_by_name($O_BBN_st, ":$O_BBN_name", $this->results[$O_BBN_name], $O_BBN_size);
	}


	private function showQuery($query, $qVars = array(), $sVars = array())
	{
		//TODO: USE setOciError function somehow instead of this function
		$GLOBALS['debugEchos_data'] .= '<pre>%%%%%%%%%<br>';
		if ($this->production)
			$GLOBALS['debugEchos_data'] .= "--------PRODUCTION DB - (" . $this->connID . ") ------<br>";
		else
			$GLOBALS['debugEchos_data'] .= "--------test DB - (" . $this->connID . ") ------<br>";
		$GLOBALS['debugEchos_data'] .= $query . '<br>';
		if (sizeof($qVars))
			$GLOBALS['debugEchos_data'] .= print_r($qVars, true);
		if (sizeof($sVars))
			$GLOBALS['debugEchos_data'] .= print_r($sVars, true);
		$GLOBALS['debugEchos_data'] .= '%%%%%%%%%<br></pre>';
	}


	function query($query)
	{
		// old-school query function -
		// MUST MAKE SURE $query is safe BEFORE calling the old query function!!!!!!!!!!
		return $this->sQuery($query, array(), true);
	}


	public function pretty_print_query($query = '', $qVars = array(), $file_line = '')
	{
		/***********
		 * Finally, a way for debuggers to output a query with qVars injected in the query.
		 * Returns query with vars stuffed in it (vars from qVars)
		 * $file_line param should be sent in like this: __FILE__.':'.__LINE__
		 * uses selectText function in js\base.js
		 */

		$pString = '';

		// just get the file name
		$file_line = preg_replace('/^.*\/(.*)$/si', '$1', $file_line);
		$file_line .= $file_line ? ' ' : '';

		if ($query) {
			$css = 'color:#6600cc; font-weight:bold; padding-left:2px; padding-right:2px;';
			$randDiv = rand(1, 999999);
			if (sizeof($qVars)) {
				foreach ($qVars as $var_name => $var_value)
					$query = preg_replace('/\:' . $var_name . '/si', "<span style='$css' title='$var_name'>'" . htmlentities($var_value) . "'</span>", $query);
			}
			// consolodate tabs
			$query = preg_replace('/\t\t+/si', "\t", $query);
			// consolodate spaces
			$query = preg_replace('/ +/si', " ", $query);
			$pString .= "<pre><div style='border:1px solid #6600cc; border-right:0; padding-left:3px; margin-top:3px;'>"
				. $file_line . "-- PRETTY-PRINT:<div id='q_" . $randDiv . "' "
				. "onclick='selectText(\"q_" . $randDiv . "\");' onmouseover='selectText(\"q_" . $randDiv . "\");'>" . $query . "</div></div></pre>";
		}
		echo $pString;
		return '';
	}


	function sQuery($query, $vars = array(), $oldQuery = false, $makeHTML = true)
	{
		/***
		 * SAFELY runs queries against oracle, sets global rows and results var, return bool true on success
		 */
		if ($this->web_down)
			return false;

		$retVal = true;

		$this->t_start = microtime(true); // for debugging errors.

		if (!$this->connID)
			$this->connect();

		if (!$query || $this->connID === false) {
			$retVal = false;
		} else {
			$this->results = false;
			$this->error = false;
			$this->rows = 0;

			if ($oldQuery) {
				// It is assumed that $query contains only safe inserts.
				if (preg_match('/^\s*UPDATE.*/si', $query) || preg_match('/^\s*INSERT.*/si', $query) || preg_match('/^\s*DELETE.*/si', $query))
					$query = unmake_htmlentities($query);
			}

			//------------------------------------------------ Parse ---------------------------------------------------------
			$st = oci_parse($this->connID, $query);

			if (!$st) {
				$this->setOciError('Parse', $this->connID, $vars, $query);
				$retVal = false;
			} else {
				if (sizeof($vars)) {
					if (preg_match('/^\s*UPDATE.*/si', $query) || preg_match('/^\s*INSERT.*/si', $query) || preg_match('/^\s*DELETE.*/si', $query)) {
						// UNDO make_htmlentities
						foreach ($vars as $k => $val)
							$vars[$k] = unmake_htmlentities($val);
					}
					$this->bindVars($st, $vars);
				}

				//------------------------------------------------ Execute --------------------------------------------------------- 235051 235044
				$rex = oci_execute($st);

				if (!$rex) {
					$this->setOciError('Execute', $st, $vars, $query);
					$retVal = false;
				} else if (preg_match('/^\s*SELECT.*/si', $query)) {
					// Get rusultant query's SELECT records.
					//------------------------------------------------ Fetch --------------------------------------------------


					if (1) //  && !@$GLOBALS['jody']
					{
						/**************************
						20160303 - new way, one row at a time
						 */
						$this->rows = $this->my_oci_fetch_all($st, $this->results);
					} else {
						/**************************
						20160302 - old way - broken
						 */
						$this->rows = oci_fetch_all($st, $this->results);
					}

					if (is_array($this->results) || is_object($this->results)) {
						foreach ($this->results as $col => $val) {
							if ($makeHTML) {
								// For blob data (binary), we don't want to do this.
								$val = str_replace("''", "'", $val);
								make_htmlentities($val, false);
							}
							$this->results[$col] = $val;
						}
					}
					oci_free_statement($st);
				}
			}
		}
		if (@$_SESSION['output_sql_debug'])
			$this->outQuery($query, $vars);

		return $retVal;
	}


	private function my_oci_fetch_all($statement, &$output)
	{
		/***
		 * Workaround for oci_fetch_all - very similar to http://php.net/manual/en/function.oci-fetch-all.php
		 * Uses oci_fetch_array iteratively (instead of oci_fetch_all which currently has problems whith 2+ query result set)
		 * Fills &$output.  Returns the number of rows in &$output.
		 * ##### Needs More Testing - let us know if problems #####
		 * 2016-03-04 jodybrabec@gmail.com
		 */
		$rows = 0;
		$allCols = oci_num_fields($statement);
		$colNames = array();
		for ($i_c = 1; $i_c <= $allCols; $i_c++) {
			$aColName  = oci_field_name($statement, $i_c);
			$colNames[$aColName] = $aColName; // force unique via key
		}
		while (($row = oci_fetch_array($statement, OCI_RETURN_LOBS)) != false) {
			$rows++;
			foreach ($colNames as $i_c => $aColName)
				$output[$aColName][] = @$row[$aColName]; // $row may contain NULL value
		}
		return $rows;
	}


	private function outQuery($sql_query, $bind_vars)
	{
		/***
		 * output_sql_debug is set in top.inc .php (or on error), and so all queries:
		 * 		If output_sql_debug is 1, then all queries will be sent to txt file here.
		 * 		If output_sql_debug is 2, then all queries will be displayed in html for debug mode.
		 */

		$var_str = '';
		foreach ($bind_vars as $k => $v) {
			$var_str .= $var_str ? ', ' : '';
			$var_str .= "$k=>$v";
		}

		$this->t_end = microtime(true);
		$this->t_diff = round(($this->t_end - $this->t_start), 5) . '00000';
		$this->t_diff = preg_replace('/^.*(\d\.\d{5}).*$/', '$1', $this->t_diff);

		// Q_TAB_Q will be replaced with quote-tab-quote.
		$outTxt = date('m-d_H:i:s') . "Q_TAB_Q" . $this->t_diff . "Q_TAB_Q" . $_SERVER['REMOTE_ADDR'] . "Q_TAB_Q";

		if (@$_SESSION['output_sql_debug'] == 2) {
			//------------------ display querys in html table.
			//----- Get calling file(s)
			$call = debug_backtrace();  // $call[0]["file"]  $call[0]["line"]
			$file_line = 'FILE(s): ';
			foreach ($call as $k1 => $v1) {
				$useNext = false;
				if (!is_array($v1))
					continue;
				foreach ($v1 as $k2 => $v2) {
					if (is_array($v2) || is_object($k2) || is_object($v2))
						continue;
					if ($k2 == 'file' && !preg_match('/database.php/si', $v2)) {
						$file_line .= $_SERVER['REQUEST_URI'];
						$useNext = true;
					} else if ($useNext) {
						$useNext = false;
						$file_line .= ' : ' . $v2 . "<br/>";
					}
				}
			}


			$colVal = '';
			foreach ($this->results as $col => $val) {
				$colVal .= "<strong>$col =></strong> " . print_r($val, true) . "<br>";
			}
			$td = '<td style="border-bottom:1px solid #B56AB1; border-right:1px solid #B56AB1;">';
			$outTxt = preg_replace("/Q_TAB_Q/si", '</td>' . $td, $outTxt) . '<small>' . $file_line . '</small></td>' . $td;
			$outTxt = "
				<table style='color:#B56AB1; font-size:0.8em; padding:0; border-top:1px solid #B56AB1; border-left:1px solid #B56AB1;'><tr>
				$td $outTxt <strong>$sql_query</strong></td>
				$td $var_str</td>
				$td $colVal</td>
			</tr></table>";

			$GLOBALS['debugEchos_data'] .= $outTxt;
		} else {
			//------------------ save to .txt file
			$outTxt = $outTxt . $sql_query . "Q_TAB_Q" . $var_str;
			// convert quotes to two single quotes.
			$outTxt = preg_replace("/\"/si", "''", $outTxt);
			// Remove tabs.
			$outTxt = preg_replace("/\t/si", "   ", $outTxt);
			// make sure newlines are "\r\n"
			$outTxt = preg_replace("/\r?\n/si", "\r\n", $outTxt);
			// Replace delimiter string Q_TAB_Q with real delimiters: quote-tab-quote
			$outTxt = '"' . preg_replace("/Q_TAB_Q/si", "\"\t\"", $outTxt) . '"' . "\n";
			$OUT_FILE = fopen(dirname(__FILE__) . '/database.csv.txt', 'a');
			//fwrite($OUT_FILE, $outTxt);
			//fclose($OUT_FILE);
			//if ($GLOBALS['DEBUG_DEBUG'])
		}
	}


	function sSeqInsert($query, $pk, $vars, $fieldCheck = false)
	{
		/***
		 * performs a SAFE sql insert for tables with sequences and returns the new pk
		 */
		if (!$this->connID)
			$this->connect();
		if (!$this->connID || !$query || !$pk)
			return false;

		$check = $this->sQuery($query, $vars);
		$getQuery = "SELECT $pk.CURRVAL AS SEQVAL FROM DUAL";
		$check = $this->query($getQuery);
		if ($check && $this->rows)
			return $this->results['SEQVAL'][0];
	}


	// formats the value for the database, before going into sQuery or sExecute - and NOT functions query or execute!
	function sFormat($val, $str, $nulls, $length = 0)
	{
		if ($nulls && (($str && ($val == "" || $val == "0")) || (!$str && $val == 0) || is_null($val) || $val == NULL || $val == "NULL" || $val == "null"))
			return NULL;
		elseif (!strlen($val) && !$str)
			return "0";
		elseif (!$str)
			return $val;
		else {
			if ($length) {
				$val = str_replace("'", "''", stripslashes($val));
				$val = substr($val, 0, $length);
				// After truncating to the proper length with TWO single-quotes, then convert back to ONE single-quote because sExecute and sQuery convert them to TWO single-quotes:
				$val = str_replace("''", "'", stripslashes($val));
			}
			return $val;
		}
	}


	// executes oci statements, sets globals results var, return bool true on success
	function execute($sql, $sizeVars)
	{
		if ($GLOBALS['DEBUG_DEBUG'])
			$this->showQuery($sql, $sizeVars);
		$this->t_start = microtime(true);

		if (!$this->connID)
			$this->connect();
		if (!$sql || !is_array($sizeVars) || !$this->connID)
			return false;
		$this->results = false;
		$this->error = false;
		$st = oci_parse($this->connID, $sql);
		if (!$st)
			$this->setOciError('Parse', $this->connID);

		$this->bindSizeVars($st, $sizeVars);

		$rex = oci_execute($st);

		if (!$rex) {
			$this->setOciError('Execute', $st);
			if (!$st)
				return false;
		}

		oci_free_statement($st);
		return true;
	}


	// performs an sql insert for tables with sequences and returns the new pk
	function seqInsert($query, $pk, $fieldCheck = false)
	{
		if (!$this->connID)
			$this->connect();
		if (!$this->connID || !$query || !$pk)
			return false;
		$check = $this->query($query);
		/* $regexp = "/INSERT INTO (.*?) \((.*?)\) VALUES\((.*?)\)/";
		  $table = preg_replace($regexp,"\\1",$query);
		  $fields = explode(",",preg_replace($regexp,"\\2",$query));
		  $values = explode(",",preg_replace($regexp,"\\3",$query));
		  if (count($fields)!=count($values) || !$check) return false; */
		$getQuery = "SELECT $pk.CURRVAL AS SEQVAL FROM DUAL";
		/* for ($i=0; $i<count($fieldCheck); $i++) {
		  $getQuery .= " AND ".$fields[$i]."=".$values[$i];
		  } */
		$check = $this->query($getQuery);
		if ($check && $this->rows)
			return $this->results['SEQVAL'][0];
	}


	// t2 login
	function test_login($login, $password)
	{
		if ($this->web_down)
			return false;

		$this->t_start = microtime(true);

		if (!$this->connID)
			$this->connect();

		if (!$this->connID || !$login || !$password) {
			if ($GLOBALS['DEBUG_DEBUG'])
				$GLOBALS['debugEchos_data'] .= '<big>########## No connnection!!!!!!!!!!!!!!!!!!!!!</big><br>';
			return false;
		}

		$testConn = @oci_connect($login, $password, $this->identifier);
		if ($GLOBALS['DEBUG_DEBUG']) {
			$GLOBALS['debugEchos_data'] .= $this->echoDebug($testConn, 'LOGIN DB CONNECTION - test_login', 'blue');
		}

		//because of debug output
		$login = 'login_trash';
		$password = 'password_trash';
		if ($testConn !== false) {
			$this->setOciError('Connect'); // For oci_connect errors do not pass a handle
			if ($GLOBALS['DEBUG_DEBUG'])
				$GLOBALS['debugEchos_data'] .= $this->echoDebug($testConn, 'LOGIN DB Disconnect', 'grey');
			oci_close($testConn);
			return true;
		} else {
			if ($GLOBALS['DEBUG_DEBUG'])
				$GLOBALS['debugEchos_data'] .= '<big>########## No LOGIN connnection!!!!!!!!!!!!!!!!!!!!!</big><br>';
			return false;
		}
	}


	// formats the value for the database.  NOTE: If using sQuery or sExecute, then you must use sFormat instead of this function.
	function format($val, $str, $nulls, $length = 0)
	{
		if ($nulls && (($str && ($val == "" || $val == "0")) || (!$str && $val == 0) || is_null($val) || $val == NULL || $val == "NULL" || $val == "null"))
			return NULL;
		elseif (!strlen($val) && !$str)
			return "0";
		elseif (!$str)
			return $val;
		else {
			$val = str_replace("'", "''", stripslashes($val));
			if ($length)
				$val = substr($val, 0, $length);
			return "'$val'";
		}
	}


	/*	 * ***********************************  END NEW FUNCTIONALITY  ********************************* */


	function disConnect()
	{
		if ($this->connID) {
			if ($GLOBALS['DEBUG_DEBUG'])
				$tmpCn = $this->connID;
			oci_close($this->connID);
			$this->ppp = '*';
			if ($GLOBALS['DEBUG_DEBUG'])
				$GLOBALS['debugEchos_data'] .= $this->echoDebug($tmpCn, 'DB Disconnect', 'grey');
		}
	}


	private function setOciError($type, $rid = '', $vars = array(), $query_pp = '')
	{
		global $is_www2; // If true then External site.

		$this->error = oci_error($rid);

		if (!$is_www2 && @!$GLOBALS['alreadyInternalMsg']) {
			$GLOBALS['alreadyInternalMsg'] = true;
			echo '<div align="center" style="border:3px solid #cc3300; padding:4px; margin:28px 8px 28px 8px; font-family:Courier; color:#cc3300; font-weight:bold; font-size:1.1em;">';
			echo 'Internal Error Message: Probable heavy database congestion, please try again in a few seconds.' . "</div>\n";
		}

		$this->ppp = '*****';
		$errMsg = '';

		$errMsg .= '<pre><div style="border-top:2px solid #cc3300; border-bottom:2px solid #cc3300; border-left:2px solid #cc3300; '
			. 'padding:1px; font-family:Courier; color:#cc3300; font-weight:bold; font-size:12px;">';
		$errMsg .= '<div style="text-align:center;">~~~~~~ OCI ERROR ' . date('m/d/Y H:i:s') . " ~~~~~~ IP: " . $_SERVER['REMOTE_ADDR']
			. " ~~~~~~ <a href='https://www.pts.arizona.edu/logs/database_errors.php' target='_blank'>all errors</a> ~~~~~~</div>";

		if ($this->production)
			$errMsg .= $this->echoDebug($this->connID, '', '#cc3300');
		else
			$errMsg .= $this->echoDebug($this->connID, '', '#cc3300');

		if (isset($this->error['code']))
			$errMsg .= 'Err code ' . $this->error['code'] . ': ';
		// Just shows the error number and description:
		$errMsg .= htmlentities($this->error['message']) . "\n";

		// These next two are for oci_execute errors;
		if ($this->error['sqltext']) {
			$errMsg .= "<small>";
			$tmpEtxt = preg_replace('/[\r]/si', '', $this->error['sqltext']);
			$tmpEtxt = preg_replace('/[\n\t]/si', ' ', $tmpEtxt);
			$errMsg .= htmlentities($tmpEtxt) . "\n";
			// Use "`" as spaces, to show where the error is, where "^" will be at the end of all the "`".
			$carrotSpaces = '';
			for ($iset = 1; $iset < $this->error['offset']; $iset++)
				$carrotSpaces .= '`';
			$errMsg .= "<span style='background-color:#FC6A41;'>" . $carrotSpaces . "</span>";
			$errMsg .= "<span style='background-color:#ff0000; color:white;'><big>.^.</big></span>";
		}

		if (sizeof($vars)) {
			$errMsg .= "\nBind Vars:\n";
			foreach ($vars as $k => $v)
				$errMsg .= "    $k => $v\n";
		}
		$errMsg .= "</small>";

		if (@$GLOBALS['DEBUG_ERROR'] && $query_pp)
			$errMsg .= $this->pretty_print_query($query_pp, $vars);
		$errMsg .= "</div></pre>\n";

		if (@$GLOBALS['DEBUG_ERROR'])
			echo $errMsg;

		$outFname = dirname(__FILE__) . '/database_error_log.htm';
		$OUT_FILE = fopen($outFname, 'a');
		fwrite($OUT_FILE, $errMsg);
		fclose($OUT_FILE);

		$errMsg = preg_replace('/&nbsp;/', ' ', strip_tags($errMsg));

		$this->outQuery($errMsg, $vars);
	}


	private function echoDebug($rid, $txt, $fontClr = '#D15E00')
	{
		$txt .= $txt ? ' | ' : '';
		return '<div style="border-top:1px dashed #D15E00; border-bottom:1px dashed #D15E00; border-left:1px dashed #D15E00; color:' . $fontClr . '; padding:1px; font-family:Courier; color:' . $fontClr . '; font-weight:normal; font-size:10px;">'
			. $txt . 'FILE: ' . $this->callingFile . '  |  ' . 'DB SERVICE: ' . $this->serviceName . ', ' . $this->connection_name
			. ', ' . $this->dbHost . ', ' . $this->uuu . '  |  ' . $rid
			. '</div>';
	}


	function connect()
	{
		if ($this->web_down)
			return false;

		// t_start should already be set, but just in case:
		$this->t_start = $this->t_start ? $this->t_start : microtime(true);

		if (!$this->connID) {
			$this->connID = oci_connect($this->uuu, $this->ppp, $this->identifier);
			$this->ppp = '**';

			if ($GLOBALS['DEBUG_DEBUG']) {
				$GLOBALS['debugEchos_data'] .= $this->echoDebug($this->connID, 'DB CONNECTION');
			}
		}

		if (!$this->connID) {
			$this->setOciError('Connect', $this->connID); // For oci_connect errors do not pass a handle
			$this->connID = false;
		}
	}



	private function checkLogin()
	{
		global $login;

		if (isset($_GET['logout'])) {
			unset($_SESSION['cuinfo']);
			if (isset($_SESSION['cuid']))
				unset($_SESSION['cuid']);
			if (isset($_SESSION['pts_cuinfo']))
				unset($_SESSION['pts_cuinfo']);
		}
		// Both SESSION custcreate and POST custcreate are set in new_cust_gr .php
		if (isset($_SESSION['custcreate']) && (isset($_POST['custcreate']) || isset($_POST['deptcreate']))) {
			//############################### New Customer Class ##################################
			//include_once 'gr_orig/new_cust_gr.php';
			include_once 'gr/new_cust_gr.php';
			$newcust = new customer($this);

			if (isset($_POST['custcreate'])) {
				$newcust->createAccount();
				if ($newcust->error || $this->error) {
					$GLOBALS['debugEchos_data'] .= $newcust->error_out();
					return false;
				}
				$_SESSION['cuinfo'] = array();
				$_SESSION['cuinfo']['userid'] = $newcust->newInfo['userid'];
				$_SESSION['cuinfo']['username'] = $newcust->newInfo['cuname'];
				$_SESSION['cuinfo']['netid'] = $newcust->newInfo['netid'];
				$_SESSION['cuinfo']['phone'] = $newcust->newInfo['phone'];
				$_SESSION['cuinfo']['email'] = $newcust->newInfo['email'];
				$_SESSION['cuinfo']['auth'] = $newcust->newInfo['auth'];
				$_SESSION['cuinfo']['authdesc'] = $newcust->newInfo['authdesc'];
				$_SESSION['cuinfo']['deptno'] = array($newcust->newInfo['deptno']);
				$_SESSION['cuinfo']['deptname'] = array($this->getDeptName($newcust->newInfo['deptno']));


				$GLOBALS['debugEchos_data'] .= $newcust->createMsg;
				return true;
			} elseif (isset($_POST['deptcreate'])) {; //
			}
		} elseif (isset($_POST['login']) && !isset($_GET['logout'])) {
			//################### Login Class #######################

			//include_once 'gr_orig/login_gr.php';
			include_once 'gr/login_gr.php';

			$login = new login($_POST['login'], $_POST['password'], $this);
			$this->cForm = $login->cForm; // contains new customer <form> if needed for garage res system.
			if (!isset($_SESSION['cuinfo']['auth'])) {
				if ($login->error)
					$GLOBALS['debugEchos_data'] .= $login->error_out();
				return false;
			} else {
				return true;
			}
		} else if (isset($_SESSION['cuinfo']['auth'])) {
			return true;
		} else {
			if (isset($_SESSION['custcreate']))
				unset($_SESSION['custcreate']);
			return false;
		}
	}

	function protectCheck($type)
	{
		/***
		 * For garage resservations, for logging in via webauth,	and also queries tables of the form PARKING.GR_*
		 * Param $type is access levels: 1, 2, 3, or 4.
		 *		$_SESSION['cuinfo']['auth'] (from PARKING.GR_* schema) must be >= $type to return true.
		 */
		global $mysqli2;

		if (checkEnabled('Webauth Garage')) {
			if (!isset($mysqli2)) $mysqli2 = new database();
			include_once 'gr/login_gr.php';
			if ($_SESSION['eds_data']['netid']) {
				$login = new login($_SESSION['eds_data']['netid'], '', $mysqli2);
				$this->cForm = $login->cForm; // contains new customer <form> if needed for garage res system.
			}

			$_SESSION['cuinfo']['netid'] = $_SESSION['eds_data']['netid'];
			$_SESSION['cuinfo']['emplid'] = $_SESSION['eds_data']['emplid'];
			unset($_SESSION['viewOnly']); // webauth success, so give this person goodness.
			$_SESSION['cuid'] = true; // Set this so that page_render.php will output Signed In As...
		}

		// to fix movement between GR and External login vars
		if (isset($_SESSION['cuinfo']) && !isset($_SESSION['cuinfo']['auth'])) {
			if (isset($_SESSION['cuinfo']['pecid']))
				$_SESSION['pts_cuinfo'] = $_SESSION['cuinfo'];
			unset($_SESSION['cuinfo']);
		}
		if (isset($_SESSION['gr_cuinfo'])) {
			$_SESSION['cuinfo'] = $_SESSION['gr_cuinfo'];
			unset($_SESSION['gr_cuinfo']);
		}

		if (checkEnabled('Webauth Garage')) {
			if (isset($_SESSION['custcreate'])) {
				if (!isset($_SESSION['cuinfo']['auth']) || isset($_POST['login']) || isset($_GET['logout']))
					return $this->checkLogin();
			}
			if ($_SESSION['cuinfo']['auth']) {
				if ($_SESSION['cuinfo']['auth'] < $type)
					return false;
				else
					return true;
			}
		} else {
			// OLD SCHOOL.
			if (!isset($_SESSION['cuinfo']['auth']) || isset($_POST['login']) || isset($_GET['logout'])) {
				return $this->checkLogin();
			}
			if (isset($_SESSION['cuinfo']['auth'])) {
				if ($_SESSION['cuinfo']['auth'] < $type)
					return false;
				else
					return true;
			}
		}
	}

	function getDeptNo($dept)
	{
		if (!$dept)
			return false;
		$this->query("SELECT DEPT_NO FROM PARKING.GR_DEPARTMENT WHERE DEPT_NAME='$dept'");
		if ($this->rows)
			return $this->results['DEPT_NO'][0];
	}

	function getDeptName($dept)
	{
		if (!$dept)
			return false;
		$this->query("SELECT DEPT_NAME FROM PARKING.GR_DEPARTMENT WHERE DEPT_NO='$dept'");
		if ($this->rows)
			return $this->results['DEPT_NAME'][0];
	}
}
