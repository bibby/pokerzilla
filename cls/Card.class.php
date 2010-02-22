<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/

define('CHARSET_REAL','_real_');
define('CHARSET_TRILLIAN','_trillian_');
define('CHARSET_TEXT','_boooring_');

define('HEART','&hearts;');
define('SPADE','&spades;');
define('CLUB','&clubs;');
define('DIAMOND','&diams;');

class Card
{
	static $suits = array
	(
		CHARSET_REAL => array
		(
			1=>SPADE,
			2=>CLUB,
			3=>HEART,
			4=>DIAMOND,
		),
		CHARSET_TRILLIAN => array
		(
			1=>"8-)",
			2=>"%%",
			3=>"(L)",
			4=>"(*)"
		),
		CHARSET_TEXT => array
		(
			1=>'_s',
			2=>'_c',
			3=>'_h',
			4=>'_d'
		)
	);
	
	static $faces = array
	(
		10=>"T",
		11=>"J",
		12=>"Q",
		13=>"K",
		14=>"A"
	);
	
	function __construct( $val, $suit)
	{
		$this->val = $val;
		$this->suit = $suit;
		$this->face = self::$faces[$val]?self::$faces[$val]:$val;
	}
	
	function color()
	{
		return $this->suit > 2? 'red' : 'black';
	}
	
	/*** printCard ***
	print card
	@access	public
	@param	Player_Config
	@return	string
	*/
	function printCard( $cfg )
	{
		$suit = self::$suits[ $cfg->charset ][ $this->suit ];
		if($cfg->charset == CHARSET_REAL)
			$suit = html_entity_decode($suit, ENT_COMPAT,'UTF-8' );
			
		$val = $this->face;
		
		$ret = " $val$suit";
		
		if( $this->suit > 2 )
			$col = 'red';
		
		if($cfg->color && $col)
		{
			$c = new Color();
			$ret = $c->red($ret);
		}
		
		return $ret;
	}
}

?>
