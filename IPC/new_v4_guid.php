<?php

/*
This function generates a GUID that follows the form:
XXXXXXXX-XXXX-4XXX-YXXX-XXXXXXXXXXXX

Where Y is 9...B (inclusive)
Where X is 0...F (inclusive)


For example:

B3DF4719-173C-483A-B22B-0A71AC14869D

Note:         ^    ^

*/

function new_v4_guid () {
	$ch = '0123456789ABCDEF';
	$sc = '';
	
	for ($p = 0; $p < 30; $p++) {
		$sc .= $ch[mt_rand(0,15)];
	}
	$sd = $ch[mt_rand(0, 3)+8];
	
	return join("-", array(
		substr($sc,  0, 8),
		substr($sc, 8, 4),
		'4'.substr($sc,  12, 3),
		$sd.substr($sc, 15, 3),
		substr($sc, 18, 12)));	
}

?>
