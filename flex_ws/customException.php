<?php

class customException extends Exception
{
	/*************************************************
	 * Put this at the top of every function:  Debug_Trace::traceFunc();
	 * At bottom.php, Debug_Trace::echo_trace();
	 *
	 *
	 *
	 * 
	 * TODO: GET RID OF THIS, see "new ws_log" in permit_purchase.php
	 *
	 *
	 *
	 **********************************************/
	public function errorMessage()
	{
		Debug_Trace::traceFunc();
		//error message
		$errorMsg = "<span style='color:red; font-size:14px; font-weight:bold;'>" .
				'<pre>' . htmlentities($this->getMessage()) . '.</pre>';

		$errorMsg .= "FILE: " . $this->getFile() . ":" . $this->getLine() . '</pre>' .
				"<pre>CALL STACK:".$this->getTraceAsString()."</pre></span><br />";


		if ($GLOBALS['DEBUG_DEBUG'] || $GLOBALS['DEBUG_ERROR']) {
			echo $errorMsg;
			echo $this->__toString();
		}

		new ws_log(9, 0, 0, 0, $errorMsg, $this->__toString()); // type is 8, The parameter '9' is error.

		// This should probably be at bottom.php.
		// Debug_Trace::echo_trace();

		/* ###################### TO DO ##########################
		Unreesrve permit (if any), using UnreservePermission() (only if passed_in_per_uid is set, and get_perm_uid() has a value).
		Maybe need to un-sell a permit, if sold.
		Kill DB connections
		########################################################### */

	}
}

?>