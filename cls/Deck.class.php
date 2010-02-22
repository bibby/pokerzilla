<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/

class Deck
{
	var $cards = array(),
		$discard = array();
	
	function __construct()
	{
		foreach( range(1,4) as $suit)
			foreach ( range(2,14) as $value)
				$this->cards[] = new Card($value,$suit);
				
		$this->shuffle();
	}
	
	/*** draw ***
	Draw a card from the deck
	@access	public
	@return	Card
	*/
	function draw( $burn=false)
	{
		if($burn)
			array_shift($this->cards);
		# and turn
		return array_shift($this->cards);
	}
	
	/*** shuffle ***
	shuffles the deck
	Knuth-Fisher-Yates, or should be
	@access	public
	@return	void
	*/
	function shuffle()
	{
		$l = count($this->cards);
		while($l--)
		{
			$j = rand(0,$l);
			$tmp = $this->cards[$l];
			$this->cards[$l] = $this->cards[$j];
			$this->cards[$j] = $tmp;
		}
	}
}

?>
