<?php

require_once 'config.php';


abstract class Flex_Reporting extends Flex_Funcs
{
	// Documentation at PAGE=42: http://www.pts.arizona.edu/T2_Flex_Web_Services_7_2_Reference.pdf#page=42

	public $flex_group	= 'T2_Flex_Reporting';

	protected function set_callback()
	{
		//Called from config.php  - usually to set more vars besides those set by Flex_Funcs::setVars
	}
}


class InsertDocument extends Flex_Reporting
{
	/******
	Uses a Note_UID
	*******/

	public $flex_function	= 'InsertDocument';
	public $xml_data			= '';
	public $post_data			= '';
	public $return_page		= '';
	public $return_xml		= ''; // massaged return_page

	//----- Input params
	protected $BINARY_DOCUMENT			= ''; // Required. Base64-encoded string of Document contents
	protected $DOCUMENT_NAME			= ''; // Required. Name of the document to be stored.
	protected $NOTE_UID					= ''; // Note to which the resulting document will be attached
	protected $NOTE_TYPE					= '';//2004; // Required if TARGET_OBJ_TYPE and TARGET_OBJ_UID is specified.  UID of Note Type Lookup. FLEXADMIN.NOTE_TYPE_MLKP table, where 2004 is Additional Notes, and 2005 is Web Appeal FOTO.
	protected $TARGET_OBJ_UID			= ''; // UID of object to which the resulting document will be attached.   (i.e. Adjudication_Uid)
	protected $TARGET_OBJ_TYPE			= '';//14; // Type of object to which the resulting document will be attached.  Poss vals: 1 (for Entities), 3 (Vehicles), 10 (Permissions), 13 (Contraventions), or 14 (Adjudications).

	//------ Natural XML Output (case-sensitive):
	public $DOCUMENT_UID					= ''; // UID of new Document.
	//public $Note_Uid					= ''; // UID of Note to which the Document was attached.

	public function __construct($p_1, $p_2, $p_3, $p_4='', $p_5='', $p_6='')
	{
		parent::__construct();
		$this->BINARY_DOCUMENT		= $p_1; // Base64-encoded string
		$this->DOCUMENT_NAME			= $p_2;
		$this->NOTE_UID				= $p_3;
		$this->TARGET_OBJ_UID		= $p_4;
		if ($p_5)
			$this->NOTE_TYPE			= $p_5; // class-defaulted above.
		if ($p_6)
			$this->TARGET_OBJ_TYPE	= $p_6; // class-defaulted above.
		$this->send_xml();
	}

	protected function get_xml()
	{
		$this->xml_data = '';
		$this->xml_data .= "\n" . '<' . $this->flex_function . '>';
		$this->xml_data .= make_param('BINARY_DOCUMENT',		$this->BINARY_DOCUMENT);
		$this->xml_data .= make_param('DOCUMENT_NAME',			$this->DOCUMENT_NAME);
		$this->xml_data .= make_param('NOTE_TYPE',				$this->NOTE_TYPE);
		$this->xml_data .= make_param('NOTE_UID',					$this->NOTE_UID);
		$this->xml_data .= make_param('TARGET_OBJ_TYPE',		$this->TARGET_OBJ_TYPE);
		$this->xml_data .= make_param('TARGET_OBJ_UID',			$this->TARGET_OBJ_UID);
		$this->xml_data .= "\n" . '</' . $this->flex_function . '>';

		$this->post_data = $this->createPost($this->xml_data);
		return $this->post_data;
	}
}

?>