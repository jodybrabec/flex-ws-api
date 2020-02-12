<?php
/*~~~~~~~~~~~~~~~~~~ GLOBAL VARS ~~~~~~~~~~~~~~~~~~~~~~
 *
 * For run-time flexability, use "define" instead of "const"
 */

define('G_YYYY',		'2016');
define('G_YEAR',		'2016-2017');
define('G_YEAR_SM',	'16/17');

//========= Default Values
define('G_EMAIL_FROM',	'PTS Permit Services <PTS-PermitServices@email.arizona.edu>');
define('G_EMAIL_BCC',	'PTS IT Emails <PTS-IT-Emails@email.arizona.edu>');
define('CONTACT_CR',		'Please contact Customer Relations at PTS-PermitServices@email.arizona.edu or call (520) 626-7275.');
define('CONTACT_CR_2',	'contact Customer Relations at 520-626-7275');
define('PTS_OFFICE',		' <a href="http://parking.arizona.edu/about/office.php">Parking & Transportation Services, 1117 E Sixth St, Tucson, AZ 85721</a>');


function getEmailHeaders($from='', $bcc='')
{
	/***
	 * Set email headers for mail() function calls.
	 * You can set any of the two parameters $from and $bcc, which will override the respective defaults of G_EMAIL_FROM and G_EMAIL_BCC
	 *		$bcc can be set to NONE, for no BCC.
	 *		Note 1: The $from and $bcc parameters (if set), is to be of the form 'friendly text<somebodys_email@some_domain.edu>'
	 *		Note 2: The $from and $bcc parameters, if they have spaces then <br> tags may show up!
	 */

	$from	= $from ? $from : G_EMAIL_FROM;
	if ($bcc == 'NONE')
		$bcc	= '';
	else
		$bcc	= $bcc  ? $bcc  : G_EMAIL_BCC;

	$headers = '';
	$headers .= 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
	$headers .= 'From:' . $from . "\r\n";
	if ($bcc)
		$headers .= 'Bcc:'  . $bcc  . "\r\n";
	$headers .= "\r\n";

	return $headers;
}


function visitorGarageAccess($override=false)
{
	/**********************************************************
	 THIS CLASS FINALLY GIVES THE WELL-DESERVED Power to the Pinkie Toe.
	 * Mon thru Fri: If after midnight garages are closed to visitors. (during fall and spring semesters).
		* Exceptions:
			* Spring Break, Holidays, Sat, Sun
			* footbal games
			* special events
	 * Uses table PARKING.GR_HOLIDAY
	*********************************************************/
	echo '<div style="font-size:9px;">Tech Note: See visitorGarageAccess<br/>function in include/global_vars.php</div>';
}

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
?>
