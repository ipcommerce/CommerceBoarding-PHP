<?php

/*
This function is used to merge service key-specific values into a standard request.
*/

function array_merge_if_defined($array1, $array2, $array2key)
{
    if (@is_array($array2[$array2key]))
    {
        return array_merge_overwrite($array1, $array2[$array2key]);
    }
    return $array1;
}

?>
