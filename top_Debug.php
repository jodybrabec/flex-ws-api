<?php

if ($GLOBALS['DEBUG_ERROR']) {
	if (!isset($mysqli2)) $mysqli2 = new database();

	// not sure if using this js
	//echo '<script src="/js/javaScripts/prettyprint.js"></script>';

	$db_str_f = $GLOBALS['database_test_db'] ? 'TEST MODE' : 'LIVE MODE';

	$tblBgClr = $GLOBALS['database_test_db'] ? 'green' : '#c03';
	$tblBgClr = @$_SESSION['netidMorph'] ? '#f0f' : $tblBgClr;
	echo '<table width="100%" style="padding:1px; margin:1px; border:1px solid yellow; background-color:' . $tblBgClr . ';"><tr>';

	echo '<td style="border:0; margin:0 0 0 9px; padding:0; white-space:nowrap; width:20%;">';
	echo '<span style="font-size:18px; font-weight:bold; text-shadow: 2px 1px 2px white;">' . $db_str_f . '</span> &nbsp;';

	//------------- Button to toggle LIVE/TEST mode.
	$t_parm = $_SERVER['PHP_SELF'];
	$t_parm .= preg_match('/php\?/si', $_SERVER['PHP_SELF']) ? '&' : '?';
	if (@$_SESSION['live_db_mode']) {
		$tmp_style	= 'background:#FFF3E5;' . 'cursor:pointer; margin-right:22px;';
		$t_link_switch = '<input type="button" value="Switch to TEST MODE" onclick="document.location.href=\'' . $t_parm . 'live_db_mode=is_false\';" style="' . $tmp_style . '" />';
	} else {
		$tmp_style	= 'background:#E5FFD1;' . 'cursor:pointer; margin-right:22px;';
		$t_link_switch = '<input type="button" value="Switch to LIVE MODE" onclick="document.location.href=\'' . $t_parm . 'live_db_mode=is_true\';"  style="' . $tmp_style . '" />';
	}
	echo '<span style="padding:1px 11px 1px 5px;">' . $t_link_switch . '</span>';
	echo "</td>\n";

	if ($GLOBALS['database_test_db'] || $GLOBALS['jody']) {
		//----------------------------------------------------- Morph into somebody ----------------------------------
		echo '<form method="POST" name="formMorph">' . "\n";
		echo '<td style="border:0; margin:0 9px; 0 9px; padding:0;"><div>';
		if (@$_SESSION['netidMorph']) {
			// We are posing as somebody else! Make "un-morph" button - log out.
			echo '<input type="button" value="un-morph ' . $_SESSION['netidMorph'] . '" onclick="document.location.href=\'/index.php?logout=321\';"'
				. ' style="background:red; font-size:1.1em; color:white;" />' . "\n";
		} else if (sizeof(@$_SESSION['entity'])) {
			// logged in as self, so offer morph mode - log out.
			$tmp_style	= 'background:#E5FFD1;';
			echo '<input type="button" value="go to morph mode" onclick="document.location.href=\'/index.php?logout=987\';" style="' . $tmp_style . '" />' . "\n";
		} else {
			// not logged in, so allow to enter netid.
			$tmp_style	= 'background:#E5FFD1;';
			echo '<input type="text" name="netidMorph" value="' . @$_SESSION['netidMorph'] . '" style="font-weight:bold; width:75px; padding:0 1px 0 1px; margin:0 2px 0;" />';
			echo '<input type="submit" value="netid morph" style="' . $tmp_style . '" />' . "\n";
		}
		echo "</div></td>\n";
		echo '</form>' . "\n";
	} else {
		unset($_SESSION['netidMorph']);
		echo '<td style="border:0; margin:0 82px; 0 0; padding:0;"> &nbsp; </td>';
	}

	//------------- Button to open T2.
	echo '<td style="border:0; margin:0 40px 0 9px; padding:0; white-space:nowrap;">';
	echo '<input type="button" value="OPEN T2 Flex - ' . $db_str_f . '" onclick="NewWindow(\'' . $mysqli2->WS_CONN['PP_URL'] . '\', \'win_t2_' . $mysqli2->WS_CONN['conn_name'] . '\');"
			style="background:#E5FFD1; cursor:pointer; margin-right:1px;" />';
	echo ' <span style="background-color:#eee; border:1px solid white; font-size:12px; padding: 1px;">PARKING schema: ' . $mysqli2->dbHost . '</span>';
	echo '</td> ';

	$req_uri = preg_replace('/(output_debug|output_sql_debug)/si', 'void_output_param', $_SERVER['REQUEST_URI']);
	//------------- Button to toggle debug output.
	if (@$_SESSION['output_debug']) {
		$get_param_d	= $req_uri . (preg_match('/\?/si', $req_uri) ? '&' : '?') . 'output_debug=0';
		$tmp_st			= 'Hide Debug';
		$tmp_style		= 'background:#FFF3E5;';
	} else {
		$get_param_d	= $req_uri . (preg_match('/\?/si', $req_uri) ? '&' : '?') . 'output_debug=1';
		$tmp_st			= 'Show Debug';
		$tmp_style		= 'background:#E5FFD1;';
	}
	$t_link_d = '<input type="button" value="' . $tmp_st . '" onclick="document.location.href=\'' . $get_param_d . '\';" style="cursor:pointer; margin-right:22px; ' . $tmp_style . '" />';


	//------------- Button to toggle SQL query output.
	if (@$_SESSION['output_sql_debug']) {
		$get_param_q = $req_uri . (preg_match('/\?/si', $req_uri) ? '&' : '?') . 'output_sql_debug=0';
		$tmp_st  = 'Hide SQL';
		$tmp_style		= 'background:#FFF3E5;';
	} else {
		$get_param_q = $req_uri . (preg_match('/\?/si', $req_uri) ? '&' : '?') . 'output_sql_debug=2';
		$tmp_st  = 'Show SQL';
		$tmp_style		= 'background:#E5FFD1;';
	}
	$t_link_q = '<input type="button" value="' . $tmp_st . '" onclick="document.location.href=\'' . $get_param_q . '\';" style="cursor:pointer; margin-right:22px; ' . $tmp_style . '" />';

	echo '<td style="border:0; margin:0; padding:0 15px 0 0;">' . $t_link_d . $t_link_q . '</td>';

	echo '</tr></table>';
}
