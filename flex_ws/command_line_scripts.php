<?php

/**************
 * TODO: Integrate everything to Internal/powertools/ - Everything should be referenced in Internal/powertools/index.php
 *		- Move stuff from include/flex_ws/ ("Cron" and "Command-Line" scripts).
 *
 * command_line_scripts.php - called from Internal/powertools/cron_jobs.php
 * Most the processes can also be run via commandline. Example command-line call with two parameters:
 *		#>php /var/www2/include/flex_ws/command_line_scripts.php param1 param2 ...
 *			To see the Usage, type this at the command-line: #>php /var/www2/include/flex_ws/command_line_scripts.php
 * If a query returns NO results, then try and reduce the size of $rows_min and $rows_max
 * Every onece in a while check file $outfilelog for "ErrorDescription"
 ******************/
set_time_limit(0);

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING); // Report all errors except E_NOTICE AND E_WARNINGS.

$GLOBALS['DEBUG_DEBUG']			= true;
$GLOBALS['TEST_RUN']				= false;

$GLOBALS['database_test_db']	= false;

// Log script execution data - DB edits.
$outfilelog = '/var/www2/include/flex_ws/command_line_scripts_log.txt';
$log_data = "\n\n=================== BEGIN Script: $cron_script_name -- " . date('Y-m-d H:i:s') . " =========================\n";

$log_data .= "######### Using " . ($GLOBALS['database_test_db'] ? 'TEST' : 'LIVE') . " database.\n";
if ($GLOBALS['TEST_RUN'])
	$log_data .= "######### You are in 'TEST_RUN' mode - no T2 updates.\n";
else
	$log_data .= "######### You are NOT in 'TEST_RUN' mode - this IS THE REAL THING!\n";


//var_dump($GLOBALS);
if (!isset($cron_script_name))
	exitWithBottom("\n\n***********\nThis file needs to be called from here: /var/www2/html/Internal/powertools/cron_jobs.php\n***********\n\n");

/***
 * $cron_script_name (set in powertools/cron_jobs .php) will be set to one of these $command_line_keys KEY values.
 * The first PHP command-line argument must be one of the array key values (left side of the array):
 * If the value is NOT a digit, then this means it's NOT a query from T2 query manager, but the value will
 * probably be the first parameter in InsertUpdateCustomFlds class.
 */
static $command_line_keys = array(
	/*** KEY  **************************   VALUE   ***********/
	'PER_EFFECTIVE_DATES'					=>	'PERMISSION',		// PARKING table.
	'RFID_CATCARD_UPDATEII'					=>	'PERMISSION',		// PARKING table.
	'RFID_CATCARD_UPDATE'					=>	'PERMISSION',		// PARKING table.
	'Q_Expired_Reserved_permits'			=>	8588,					// T2
	'Q_Expired_One_Shot_Value_Passes'	=>	7791,					// T2
	'Q_RFID_EXPIRED'							=>	7789,					// T2
	'Q_CATCARD_EXPIRED'						=>	7802,					// T2
);
/*********** OLD keys **************
 * 'rfid_batch_step_1'						=>	'rfid_batch_step_1',
 * 'rfid_batch_step_2'						=>	'rfid_batch_step_2',
 */

if (!$cron_script_name || !in_array($cron_script_name, array_keys($command_line_keys))) {
	// show command-line usage for all scripts, and exit.
	showUsage();
} else {
	/***
	 * Run the query!!!
	 */
	runJob($cron_script_name, '0', '10000');
}



function runJob($cron_script_name, $rows_min, $rows_max)
{
	/***
	 * $rows_min and $rows_max are for T2 query manager queries (i.e. where ROWNUMBER >= :paramOne and ROWNUMBER < :paramTwo)
	 */
	global $command_line_keys, $outfilelog, $log_data;

	$log_data .= "MAIN function call: runJob(query_name:$cron_script_name, rows_min:$rows_min, rows_max:$rows_max)\n";

	switch ($cron_script_name) {

		case 'PER_EFFECTIVE_DATES':

			$mysqli21	= new database();

			/**********************************************************************************************************
			 * Change start and end date of some per-uid's.
			 * Read data from table PARKING.PER_EFFECTIVE_DATES, and this data will be sent to EditPermitDates class.
			 * This be good for Marriott stuff.
			 */
			$query	= "select PER_UID, TO_CHAR(PER_EFFECTIVE_START_DATE,'MM/DD/YYYY HH:MI:SS AM') as PER_EFFECTIVE_START_DATE,
								TO_CHAR(PER_EFFECTIVE_END_DATE,'MM/DD/YYYY HH:MI:SS AM') as PER_EFFECTIVE_END_DATE
							from PARKING.PER_EFFECTIVE_DATES";
			$mysqli21->sQuery($query);
			$rowCt1 = $mysqli21->rows;
			$tmpResults1 = $mysqli21->results;

			$t2Key	= $cron_script_name;
			for ($i = 0; $i < $rowCt1; $i++) {
				$logTmp = '';
				if ($tmpResults1['PER_EFFECTIVE_START_DATE'][$i] != '' || $tmpResults1['PER_EFFECTIVE_END_DATE'][$i] != '') {
					// time is truncated by t2, but use time, just in case, for future enhancements
					if (!$GLOBALS['TEST_RUN']) {
						$setExpireDate	= new EditPermitDates($tmpResults1['PER_UID'][$i], $tmpResults1['PER_EFFECTIVE_START_DATE'][$i], $tmpResults1['PER_EFFECTIVE_END_DATE'][$i]);
						$log_data .= check4errors($setExpireDate->ErrorDescription, 'EditPermitDates');
					}
					$logTmp .= "\n\t\t" . "EditPermitDates(PER_UID:" . $tmpResults1['PER_UID'][$i] . ", PER_EFFECTIVE_START_DATE:" . $tmpResults1['PER_EFFECTIVE_START_DATE'][$i] . ", PER_EFFECTIVE_END_DATE:" . $tmpResults1['PER_EFFECTIVE_END_DATE'][$i] . ")";
				}
				$log_data .= "T2 EDIT (" . ($i + 1) . "): " . $logTmp . "\n";
				//if ($i > 1) {
				//	echo "\n\nblaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa\n";
				//	echo "last per_uid: ".$tmpResults1['PER_UID'][$i]."\n\n";
				//	break;
				//}
			}
			break; //##############################

		case 'RFID_CATCARD_UPDATEII':

			$mysqli21	= new database();

			/**********************************************************************************************************
			 * Read data from table PARKING.RFID_CATCARD_UPDATEII -
			 * this data is custom data which will be sent to InsertUpdateCustomFlds class below.
			 * uses EditPermitDates class to change dates.
			 */
			$query	= "select * from PARKING.RFID_CATCARD_UPDATEII";
			$mysqli21->sQuery($query);
			$rowCt1 = $mysqli21->rows;
			$tmpResults1 = $mysqli21->results;
			$t2Key	= $cron_script_name;
			for ($i = 0; $i < $rowCt1; $i++) {
				$logTmp = '';
				//----------------- Only update fields if they contain something.
				if ($tmpResults1['DAD_FIELD'][$i] != '' && $tmpResults1['CUD_VALUE'][$i] != '') {
					if (!$GLOBALS['TEST_RUN']) {
						//										  ($web_service, $cust_field_name, $uid_value, $cust_field_value)
						$CF = new InsertUpdateCustomFlds($command_line_keys[$t2Key], $tmpResults1['DAD_FIELD'][$i], $tmpResults1['PER_UID'][$i], $tmpResults1['CUD_VALUE'][$i]);
						$log_data .= check4errors($CF->ErrorDescription, 'InsertUpdateCustomFlds');
					}
					$logTmp .= "\n\t\t" . "InsertUpdateCustomFlds('" . $command_line_keys[$t2Key] . "', '" . $tmpResults1['DAD_FIELD'][$i] . "', '" . $tmpResults1['PER_UID'][$i] . "', '" . $tmpResults1['CUD_VALUE'][$i] . "')";
				}
				if ($tmpResults1['PER_EFFECTIVE_START_DATE'][$i] != '' || $tmpResults1['PER_EFFECTIVE_END_DATE'][$i] != '') {
					// time is truncated by t2, but use time, just in case, for future enhancements
					if (!$GLOBALS['TEST_RUN']) {
						$setExpireDate	= new EditPermitDates($tmpResults1['PER_UID'][$i], $tmpResults1['PER_EFFECTIVE_START_DATE'][$i], $tmpResults1['PER_EFFECTIVE_END_DATE'][$i]);
						$log_data .= check4errors($setExpireDate->ErrorDescription, 'EditPermitDates');
					}
					$logTmp .= "\n\t\t" . "EditPermitDates(PER_UID:" . $tmpResults1['PER_UID'][$i] . ", PER_EFFECTIVE_START_DATE:" . $tmpResults1['PER_EFFECTIVE_START_DATE'][$i] . ", PER_EFFECTIVE_END_DATE:" . $tmpResults1['PER_EFFECTIVE_END_DATE'][$i] . ")";
				}
				$log_data .= "T2 EDIT (" . ($i + 1) . "): " . $logTmp . "\n";
				//if ($i > 1) {
				//	echo "\n\nblaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa\n";
				//	echo "last per_uid: ".$tmpResults1['PER_UID'][$i]."\n\n";
				//	break;
				//}
			}
			break; //##############################

		case 'RFID_CATCARD_UPDATE':
			/**********************************************************************************************************
			 * Just reads (not update) from table PARKING.RFID_CATCARD_UPDATE
			 * The data is custom data which will be sent to InsertUpdateCustomFlds class.
			 */

			$mysqli21	= new database();
			$query	= "select * from PARKING.RFID_CATCARD_UPDATE";
			$mysqli21->sQuery($query);
			$rowCt1 = $mysqli21->rows;
			$tmpResults1 = $mysqli21->results;
			$t2Key	= $cron_script_name;
			for ($i = 0; $i < $rowCt1; $i++) {
				// delete each record from PARKING.RFID_CATCARD_UPDATE during loop iteration.
				if (!$GLOBALS['TEST_RUN']) {
					//										  ($web_service, $cust_field_name, $uid_value, $cust_field_value)
					$CF = new InsertUpdateCustomFlds($command_line_keys[$t2Key], $tmpResults1['DAD_FIELD'][$i], $tmpResults1['PER_UID'][$i], $tmpResults1['CUD_VALUE'][$i]);
					$log_data .= check4errors($CF->ErrorDescription, 'InsertUpdateCustomFlds');
				}
				$log_data .= "T2 EDIT (" . ($i + 1) . "): InsertUpdateCustomFlds('" . $command_line_keys[$t2Key] . "', '" . $tmpResults1['DAD_FIELD'][$i] . "', '" . $tmpResults1['PER_UID'][$i] . "', '" . $tmpResults1['CUD_VALUE'][$i] . "')\n";
				//if ($i > 1) {
				//	echo "\n\nblaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa\n";
				//	echo "last per_uid: ".$tmpResults1['PER_UID'][$i]."\n\n";
				//	break;
				//}
			}
			break; //##############################

		case 'Q_Expired_One_Shot_Value_Passes':
			/***
			 * Value credential, with one day, one use setting, that is still active after one use.
			 * Set expiration date PERMISSION_VIEW.PER_EFFECTIVE_END_DATE to PARKING_TRANSACTION.PTX_DATE_ENTRY - just the date, cuz T2 will put 23:59:59.
			 */
			$param_ary = array($rows_min, $rows_max);
			$t2Key = $cron_script_name;
			$exeResults = new ExecuteQuery($command_line_keys[$cron_script_name], $param_ary, $cron_script_name, $cron_script_name);
			$tmpResults1 = $exeResults->results_custom;
			// if ($GLOBALS['DEBUG_DEBUG']) echo $tmpResults1;
			for ($i = 0; $i < sizeof($tmpResults1['PER_UID']); $i++) {
				// time is truncated by t2, but use time, just in case, for future enhancements
				if (!$GLOBALS['TEST_RUN']) {
					$setExpireDate	= new EditPermitDates($tmpResults1['PER_UID'][$i], $tmpResults1['PER_EFFECTIVE_START_DATE'][$i], $tmpResults1['PTX_DATE_ENTRY'][$i]);
					$log_data .= check4errors($setExpireDate->ErrorDescription, 'EditPermitDates');
				}
				// if ($GLOBALS['DEBUG_DEBUG']) echo $setExpireDate;
				$log_data .= "T2 EDIT (" . ($i + 1) . "): EditPermitDates(PER_UID:" . $tmpResults1['PER_UID'][$i] . ", PER_EFFECTIVE_START_DATE:" . $tmpResults1['PER_EFFECTIVE_START_DATE'][$i] . ", PTX_DATE_ENTRY:" . $tmpResults1['PTX_DATE_ENTRY'][$i] . ")\n";
			}
			break; //##############################


		case 'Q_Expired_Reserved_permits':
			/***
			 * get all the reserved permits that have expired prior to current date, and unreserve then so can sell the dang things.
			 */
			$param_ary = array();
			$t2Key = $cron_script_name;
			$exeResults = new ExecuteQuery($command_line_keys[$cron_script_name], $param_ary, $cron_script_name, $cron_script_name);
			$tmpResults1 = $exeResults->results_custom;
			// if ($GLOBALS['DEBUG_DEBUG']) echo $tmpResults1;
			for ($i = 0; $i < sizeof($tmpResults1['PER_UID']); $i++) {
				//if ($i>3)
				//break;
				$log_data .= "T2 EDIT (" . ($i + 1) . "): UnreservePermission(PER_UID:" . $tmpResults1['PER_UID'][$i] . ")";
				if (!$GLOBALS['TEST_RUN']) {
					$theunreserved	= new UnreservePermission($tmpResults1['PER_UID'][$i]);
					$log_data .= " --- return per_uid from execution: " . $tmpResults1['PER_UID'][$i];
				}
				$log_data .= "\n";
			}
			break; //##############################

		case 'Q_RFID_EXPIRED':
			/***
			 * Put an X at the end of expired RFID's
			 */
			include_once '/var/www2/include/flex_ws/config.php';
			$param_ary	= array($rows_min, $rows_max);
			$t2Key		= $cron_script_name;
			$exeResults	= new ExecuteQuery($command_line_keys[$cron_script_name], $param_ary, $cron_script_name, $cron_script_name);
			$tmpResults1 = $exeResults->results_custom;
			// if ($GLOBALS['DEBUG_DEBUG']) echo $tmpResults1;
			$log_data .= check4errors($exeResults->ErrorDescription, 'ExecuteQuery');
			for ($i = 0; $i < sizeof($tmpResults1['PER_UID']); $i++) {
				/**************************** JIC **************************************
				// These are bad per uid's that can't be removed from T2 because they were returned and placed back in inventory and can no longer be updated AT ALL.
				if ($tmpResults1['PER_UID'][$i]=='126177' || $tmpResults1['PER_UID'][$i]=='6448348' || $tmpResults1['PER_UID'][$i]=='6471122')
					continue;
				 **********************************************************************/
				$RFID_num_X	= $tmpResults1['CUD_VALUE'][$i] . 'X';
				if (!$GLOBALS['TEST_RUN']) {
					SellPermission::setRFID($RFID_num_X, $tmpResults1['PER_UID'][$i]);
					$log_data .= check4errors($setExpireDate->ErrorDescription, 'setRFID');
				}
				$log_data .= "T2 EDIT (" . ($i + 1) . "): setRFID(RFID_num_X:$RFID_num_X, PER_UID:" . $tmpResults1['PER_UID'][$i] . ")\n";
			}
			break; //##############################

		case 'Q_CATCARD_EXPIRED':
			/***
			 * Put an X at the end of expired catcard's
			 */
			include_once '/var/www2/include/flex_ws/config.php';
			$param_ary	= array($rows_min, $rows_max);
			$t2Key		= $cron_script_name;
			$exeResults	= new ExecuteQuery($command_line_keys[$cron_script_name], $param_ary, $cron_script_name, $cron_script_name);
			$tmpResults1 = $exeResults->results_custom;
			// if ($GLOBALS['DEBUG_DEBUG']) echo $tmpResults1;
			$log_data .= check4errors($exeResults->ErrorDescription, 'ExecuteQuery');
			for ($i = 0; $i < sizeof($tmpResults1['PER_UID']); $i++) {
				$CatcardID_X	= $tmpResults1['CUD_VALUE'][$i] . 'X';
				if (!$GLOBALS['TEST_RUN']) {
					//TODO: RUN LIKE THIS INSTEAD:  $CF = new InsertUpdateCustomFlds('PERMISSION', 'RFID_NUMBER', $tmpResults1['PER_UID'][$i], $CatcardID_X);
					SellPermission::setCatcardID($CatcardID_X, $tmpResults1['PER_UID'][$i]);
				}
				$log_data .= "T2 EDIT (" . ($i + 1) . "): setCatcardID(CatcardID_X:$CatcardID_X, PER_UID:" . $tmpResults1['PER_UID'][$i] . ")\n";
			}
			break; //##############################

			/***
		case 'rfid_batch_step_1':
		case 'rfid_batch_step_2':
			//This was only ran once, probably don't need anymore - if use again, then make sure works!
			RFID_X($cron_script_name);
			break; //##############################
			 ***/
	}
}


if (!$GLOBALS['TEST_RUN']) {
	$log_data .= "\n=================== END Script: $cron_script_name -- " . date('Y-m-d H:i:s') . " =========================\n";
	$OUTFILE = fopen($outfilelog, 'a');
	fwrite($OUTFILE, $log_data);
}
echo $log_data;

echo (!$GLOBALS['TEST_RUN']) ? "\nTEST_RUN false, so output sent" : "\nTEST_RUN true, so NOT redirecting output";
echo ' to log file: ' . $outfilelog . "\n\n";



function check4errors($err, $className)
{
	/***
	 * Returns any standard superclass error in variable "ErrorDescription" - from flex_ws/config .php
	 */
	if ($err)
		return "\n!!!!!!!!!!!!!!!!!!!!!!!!! ErrorDescription found in " . $className . ": " . $err . " !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\n\n";
	else
		return '';
}


function showUsage($script_to_use = 'show_all')
{
	/***
	 * Show command-line usage for $script_to_use, and quit.
	 * For command-line php, the first parameter must always be one of the scripts (or functions) here.
	 */
	global $command_line_keys, $outfilelog;

	$output = '';
	$output .= "\n*****************************************************************************************\n";
	$output .= "\nNormally run via cron.  COMMAND-LINE USAGE EXAMPLE:\n";
	$output .= "    #> php /var/www2/include/flex_ws/command_line_scripts.php PARAMETER-ONE\n\n";

	switch ($script_to_use) {
		case 'show_all':
			$output .= "PARAMETER-ONE must be one of the following (each corresponds to a script to run):\n";
			$output .= implode("    \n",  array_keys($command_line_keys));
			$output .= "\n\n";
			break;
		default:
			break;
	}
	$output .= 'If a query returns NO results, then try and reduce the size of $rows_min and $rows_max' . "\n";
	$output .= "For more info see the php script.\n";
	$output .= "\n*****************************************************************************************\n";
	echo $output;
	exit;
}




function RFID_X($step_num)
{
	/**********************************************************************************************************
	 * THIS WAS ONLY USED ONCE, AND THIS FUNCTION NEEDS WORK IF YOU WANTS TO RUN AGAIN.
	 * RFID I-Permit batch sales
	 * PHP Command line param (converted to $cron_script_name below) is 'rfid_batch_step_1' or 'rfid_batch_step_2'.
	 * For testing, set $limitSize to an integer so as to limit the number of I-Permits to sell.  Set to -1 to sell all.
	 * If running via cron or from command-line, be sure to set at the $argv parameter to step_1 or step_2.  Example command-line:
	 *		> php /var/www2/include/flex_ws/command_line_scripts.php step_2 > command_line_scripts_out.live.2.txt
	 */

	$mysqli21	= new database();
	$mysqli22	= new database();

	// Snap: I don't think this is even needed:
	// Put an x at the end of these RFID's   Swipe cards 9-long instead of 8
	$query = "SELECT DISTINCT FLEXADMIN.PERMISSION_VIEW.PER_UID, FLEXADMIN.PERMISSION_VIEW.PEC_UID_PERM_CONTROL_GROUP, FLEXADMIN.PERMISSION_VIEW.PNA_UID_PER_NUM_RANGE, FLEXADMIN.PERMISSION_VIEW.PER_NUMBER, FLEXADMIN.PERMISSION_VIEW.PER_EFFECTIVE_END_DATE, FLEXADMIN.CUSTOM_DATA.CUD_UID, FLEXADMIN.CUSTOM_DATA.DAD_UID, FLEXADMIN.CUSTOM_DATA.CUD_RECORD_UID, FLEXADMIN.CUSTOM_DATA.CUD_VALUE, FLEXADMIN.PERMISSION_VIEW.PSL_UID_STATUS, LENGTH(FLEXADMIN.CUSTOM_DATA.CUD_VALUE) AS CUD_VALUE_L, FLEXADMIN.PERMISSION_CONTROL_GROUP.OTL_UID_OCCUPANCY_TYPE, SUBSTR(FLEXADMIN.CUSTOM_DATA.CUD_VALUE,1,3) AS CUD_VALUE_prefix
	FROM (FLEXADMIN.PERMISSION_VIEW INNER JOIN FLEXADMIN.CUSTOM_DATA ON FLEXADMIN.PERMISSION_VIEW.PER_UID = FLEXADMIN.CUSTOM_DATA.CUD_RECORD_UID) LEFT JOIN FLEXADMIN.PERMISSION_CONTROL_GROUP ON FLEXADMIN.PERMISSION_VIEW.PEC_UID_PERM_CONTROL_GROUP = FLEXADMIN.PERMISSION_CONTROL_GROUP.PEC_UID
	WHERE (
		FLEXADMIN.CUSTOM_DATA.DAD_UID In (200017,200033) AND FLEXADMIN.PERMISSION_VIEW.PSL_UID_STATUS Not In (4,5) AND LENGTH(FLEXADMIN.CUSTOM_DATA.CUD_VALUE) = 8
	AND SUBSTR(FLEXADMIN.CUSTOM_DATA.CUD_VALUE,1,3) In ('136','128','140','171','177','191','211','233','101','213','255')
		) OR (
			FLEXADMIN.CUSTOM_DATA.DAD_UID In (200017,200033) AND FLEXADMIN.PERMISSION_VIEW.PSL_UID_STATUS Not In (4,5) AND LENGTH(FLEXADMIN.CUSTOM_DATA.CUD_VALUE) = 9 AND SUBSTR(FLEXADMIN.CUSTOM_DATA.CUD_VALUE,1,3) In ('100')
		)";

	echo "\n\n===================== Ran on " . date('Ymd H:i:s') . " | IS LIVE DB: " . ($GLOBALS['database_test_db'] ? 'no' : 'yes') . " | STEP #: $step_num ==========================\n\n\n";

	//$rfids = new GetAvailableIPermit();
	//echo $rfids;
	//echo "\n"."<b><div>Using Ipermit PNA ".$rfids->pna_uids[0]." - ".$rfids->pnaDesc[0]."</div></b>"."\n";

	if ($step_num == 1) {
		/************
		 * PART 1
		 * This first part will gather all active RFID holders, and put their info into PARKING.IPERMIT_BATCH_SALE table.
		 ***/
		$query = "SELECT DISTINCT FLEXADMIN.CUSTOM_DATA.CUD_VALUE, FLEXADMIN.PERMISSION_VIEW.ENT_UID_PURCHASING_ENTITY, FLEXADMIN.ENTITY_VIEW.ETL_UID_TYPE, FLEXADMIN.ENTITY_VIEW.ENT_PRIMARY_ID, FLEXADMIN.ENTITY_VIEW.ENT_SECONDARY_ID, FLEXADMIN.ENTITY_VIEW.ENT_TERTIARY_ID, FLEXADMIN.ENTITY_VIEW.ESL_UID_SUBCLASS, FLEXADMIN.ENTITY_VIEW.ENT_DISPLAY_NAME, FLEXADMIN.PERMISSION_VIEW.PER_UID, FLEXADMIN.PERMISSION_VIEW.PEC_UID_PERM_CONTROL_GROUP, FLEXADMIN.PERMISSION_VIEW.PNA_UID_PER_NUM_RANGE, FLEXADMIN.PERMISSION_VIEW.PER_NUMBER, FLEXADMIN.PERMISSION_VIEW.PER_EFFECTIVE_END_DATE, FLEXADMIN.CUSTOM_DATA.CUD_UID, FLEXADMIN.CUSTOM_DATA.DAD_UID, FLEXADMIN.CUSTOM_DATA.CUD_RECORD_UID, FLEXADMIN.CUSTOM_DATA.CUD_VALUE, FLEXADMIN.PERMISSION_VIEW.PSL_UID_STATUS
		FROM FLEXADMIN.PERMISSION_VIEW INNER JOIN FLEXADMIN.CUSTOM_DATA ON FLEXADMIN.PERMISSION_VIEW.PER_UID = FLEXADMIN.CUSTOM_DATA.CUD_RECORD_UID
		INNER JOIN FLEXADMIN.ENTITY_VIEW ON FLEXADMIN.PERMISSION_VIEW.ENT_UID_PURCHASING_ENTITY = FLEXADMIN.ENTITY_VIEW.ENT_UID
		WHERE ETL_UID_TYPE = 1 AND CUSTOM_DATA.DAD_UID = 200033 AND PERMISSION_VIEW.PSL_UID_STATUS IN (4,5)
			AND LENGTH(CUSTOM_DATA.CUD_VALUE) = 8
			AND SUBSTR(CUSTOM_DATA.CUD_VALUE, 1, 3) IN ('136','128','140','171','177','191','211','233','101','213','255')
		ORDER BY CUSTOM_DATA.CUD_VALUE";

		$mysqli21->sQuery($query);

		for ($i = 0; $i < $mysqli21->rows; $i++) {
			// first see if entity is already there BEFORE insert.
			$query2 = "select ENT_UID from PARKING.IPERMIT_BATCH_SALE where ent_uid = '" . $mysqli21->results['ENT_UID_PURCHASING_ENTITY'][$i] . "'";
			$mysqli22->sQuery($query2);
			if ($mysqli22->results['ENT_UID'][0]) {
				$skipEnts .= (@$skipEnts) ? ', ' : '<b>The following ENT_UIDs are already in PARKING.IPERMIT_BATCH_SALE table: </b>' . "\n";
				$skipEnts .= $mysqli21->results['ENT_UID_PURCHASING_ENTITY'][$i];
			} else {
				$insertString[] = "INSERT INTO PARKING.IPERMIT_BATCH_SALE values ('" . $mysqli21->results['PER_UID'][$i] . "', '" . $mysqli21->results['PNA_UID_PER_NUM_RANGE'][$i] . "', '" . $mysqli21->results['PEC_UID_PERM_CONTROL_GROUP'][$i] . "', '" . $mysqli21->results['ENT_UID_PURCHASING_ENTITY'][$i] . "', '" . $mysqli21->results['CUD_VALUE'][$i] . "', '')";
			}
		}
		echo '' . $skipEnts . '' . "\n";
		foreach ($insertString as $v) {
			echo $v . "\n";
			$mysqli21->sQuery($v);
		}
	} else if ($step_num == 2) {
		/***
		 * PART 2
		 * NOW Sell a free i-permit for all those who have a garage permit and rfid - then update the PARKING.IPERMIT_BATCH_SALE
		 * table with the sold i-permit's per_uid number.
		 */
		$limitSize = -1;

		$query = "select * from PARKING.IPERMIT_BATCH_SALE where I_PER_UID_PROCESSED is NULL order by ENT_UID asc";
		$mysqli21->sQuery($query);

		for ($i = 0; $i < $mysqli21->rows; $i++) {
			if (!$mysqli21->results['I_PER_UID_PROCESSED'][$i]) {
				if (@$j++ == $limitSize) {
					echo "\n" . "\n" . 'exiting early' . "\n" . "\n";
					break;
				}
				$perNum = 'I' . $mysqli21->results['CUST_VALUE_RFID'][$i];
				$IPer_inf = new PerNumToPerUID($perNum);
				if ($GLOBALS['DEBUG_DEBUG']) echo $IPer_inf;

				echo "\n" . 'Attempting to sell I-Permit # ' . $perNum . ', per_uid ' . $IPer_inf->per_uid . ', pec_uid ' . $IPer_inf->pec_uid . ';';
				echo "\n" . 'For ent_uid ' . $mysqli21->results['ENT_UID'][$i] . ', garage permit per_uid ' . $mysqli21->results['PER_UID'][$i];
				echo ' with RFID value of ' . $mysqli21->results['CUST_VALUE_RFID'][$i] . "\n";

				if ($IPer_inf->per_uid) {
					// Note: $IPermit will contain some same info as $IPer_inf, but with different bar names.
					$IPermit = new ReservePermission($IPer_inf->per_uid, '', $IPer_inf->pec_uid, $mysqli21->results['ENT_UID'][$i]);
					if ($GLOBALS['DEBUG_DEBUG']) echo $IPermit;

					// a few sanity checks:
					if ($IPermit->CONTROL_GROUP_UID != $IPer_inf->pec_uid)
						echo 'Woah, pec_uid numbers do not match (' . $IPermit->CONTROL_GROUP_UID . ' != ' . $IPer_inf->pec_uid . ')' . "\n";
					if ($IPermit->PERMISSION_UID != $IPer_inf->per_uid)
						echo 'Woah, per_uid numbers do not match (' . $IPermit->PERMISSION_UID . ' != ' . $IPer_inf->per_uid . ')' . "\n";

					if ($IPermit->PERMISSION_EFFECTIVE_DATE && $IPermit->CONTROL_GROUP_UID) {
						//########## TODO: No such thing as $IPermit->facUID - what the?  See cyberpay_sa.php too.
						$sell_IPermit = new SellPermission($IPermit->PERMISSION_UID, $IPermit->CONTROL_GROUP_UID, @$IPermit->facUID, $mysqli21->results['ENT_UID'][$i], '', '', '0.00', 0, '', '', '', false, 1);
						if ($GLOBALS['DEBUG_DEBUG']) echo $sell_IPermit;

						if ($sell_IPermit->PERMISSION_UID) {
							$query2 = "UPDATE PARKING.IPERMIT_BATCH_SALE set I_PER_UID_PROCESSED = '" . $sell_IPermit->PERMISSION_UID . "'
								where PER_UID = '" . $mysqli21->results['PER_UID'][$i] . "'
								and PNA_UID = '" . $mysqli21->results['PNA_UID'][$i] . "'
								and PEC_UID = '" . $mysqli21->results['PEC_UID'][$i] . "'
								and ENT_UID = '" . $mysqli21->results['ENT_UID'][$i] . "'
								and CUST_VALUE_RFID = '" . $mysqli21->results['CUST_VALUE_RFID'][$i] . "'";
							$mysqli22->sQuery($query2);
							echo "\n" . 'Sold I-Permit per_uid ' . $sell_IPermit->PERMISSION_UID . "\n";
						} else {
							echo "\n" . 'Could not Sell I-permit, no per_uid found.' . "\n";
						}
					} else {
						echo 'Could not Reserve the I-permit.' . "\n";
					}
				} else {
					echo 'No I-Permit per_uid found.' . "\n";
				}
			}
		}
	}
}
