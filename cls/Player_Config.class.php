<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/


class Player_Config
{
	var $charset = CHARSET_TEXT,
		$color = false;

	function __construct( $presets = false )
	{
		if(is_array($presets))
			$this->set( $presets );
	}
	
	function setColor($to)
	{
		$this->color = !!$to;
	}
	
	function setCharset($to)
	{
		$this->charset = $to;
	}
}
?>
