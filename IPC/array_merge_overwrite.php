<?php

/* 
The included array_merge and array_merge_recursive function does not fit the functional requirements.
This adds the functionality of merging recursively while overwriting existant non-array (string\bool\etc.) values. 
*/

function array_merge_overwrite($array1, $array2)
{
	foreach($array2 as $key => $value)
	{
		if(@is_array($array1[$key]) && is_array($value))
			$array1[$key] = array_merge_overwrite($array1[$key], $value);
		else if ($value != null)
			$array1[$key] = $value;
	}
	
	return $array1;
}

?>
