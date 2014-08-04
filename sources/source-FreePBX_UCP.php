<?php

/*** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** 
 * Developer Notes:
 * This module developed and tested using PIAF "black" FreePBX 12/Asterisk 12. Needs additional testing\
 * on other distros.
 * 
 * Version History:-
 * 2014-08-04	Initial commit by lgaetz
 * 
 * 
 *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** *** ***/

class FreePBX_UCP extends superfecta_base {
    public $description = "Look up name from list of users for FreePBX UCP";
    public $version_requirement = "2.11";
    public $source_param = array(
		'CNAM_Format' => array(
			'description' => 'Select how returned CNAM is displayed',
			'type' => 'select',
			'option' => array(
				'1' => 'First_name Last_name',
				'2' => 'Last_name, First_name ',
			),
			'default' => '1',
		),
		'Filter_Length' => array(
				'description' => 'The number of rightmost digits to check for a match. Enter zero to disable this setting',
				'type' => 'number',
				'default' => 10
		)
	);

	function get_caller_id($thenumber, $run_param=array()) {

		$this->DebugPrint("Searching FreePBX UCP ... ");

		// Initialize variables
		$caller_id = null;
		$wquery = null;
		$wquery_string = null;
		$wquery_result = null;
		
		// Set defaults in case user hasn't
		if (!$run_param['CNAM_Format']) {
			$run_param['CNAM_Format'] = 1;
		}
		if (!$run_param['Filter_Length']) {
			$run_param['Filter_Length'] = 0;
		}
		//  trim incoming number to specified filter length
		if ($run_param['Filter_Length'] != 0 && strlen($thenumber) > $run_param['Filter_Length']) {
			$thenumber = substr($thenumber, (-1*$run_param['Filter_Length']));
		}
		//  Build regular expression from modified $thenumber to avoid non-digit characters
		$wquery = "'[^0-9]*";
		for( $x=0; $x < ((strlen($thenumber))-1); $x++ ) {
			$wquery .=  substr($thenumber,$x,1)."[^0-9]*" ;
		}
		$wquery = $wquery.(substr($thenumber,-1))."([^0-9]+|$)'";
		
		$wquery_string = 'SELECT `fname`, `lname` FROM `freepbx_users` WHERE (`cell` REGEXP '.$wquery.') OR (`work` REGEXP '.$wquery.') OR (`home` REGEXP '.$wquery.')ORDER BY id DESC';

		// The following lines work, and perhaps may be preferable to search
//		global $db;
//		$results = $db->getAll($wquery_string, DB_FETCHMODE_ASSOC);

		$results = sql($wquery_string, "getAll", DB_FETCHMODE_ASSOC);  //probably need an error handler here
		if (is_array($results)) {
			$last_name = $results[0]['lname'];
			$first_name = $results[0]['fname'];
		}

		if ($run_param['CNAM_Format'] == 1) {
			$caller_id = $first_name." ".$last_name;
		}
		elseif ($run_param['CNAM_Format'] == 2) {
			$caller_id = $last_name.",".$first_name;
		}

		if ($caller_id == "") {
			$this->DebugPrint("Not Found");
		}	
		return(trim($caller_id));
    }
}

