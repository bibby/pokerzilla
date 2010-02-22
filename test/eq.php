<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/

define('ALL_IN',"_all_in_");
$test = array( ALL_IN, true);

foreach($test as $t)
{
	var_dump(array
	(
		$t,
		$t == ALL_IN,
		$t === ALL_IN
	));
}
?>
