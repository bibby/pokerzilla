<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/

define('POT_OPEN','_pot_open_');
define('POT_CLOSED','_pot_closed_');
define('POT_PRECLOSE','_pot_preclose_');
define('NO_LIMIT','_yeehaw_');

class Pot
{
	var $contribs = array(),
		$status = POT_OPEN,
		$maxBet = 0,
		$size = 0,
		$limit = NO_LIMIT;
	
	function __construct()
	{
	}
	
	/*** add ***
	@access	public
	@param	int	seat number
	@param	int	cash
	@return	void
	*/
	function add( $seatNum, $cash )
	{
		#echo "add $seatNum +$cash\n";
		$this->contribs[$seatNum][] = $cash;
		if($this->maxBet < $cash)
			$this->maxBet = $cash;
		if($this->status === POT_PRECLOSE && $this->isPaid())
			$this->close();
		$size = 0;
		foreach($this->contribs as $cash)
			$size += array_sum($cash);
		$this->size = $size;
	}
	
	/*** getEligblePlayers ***
	Players that are "in" on the pot.
	Some of these players may have since folded, however
	@access	public
	@return	array (seatnums)
	*/
	function getEligblePlayers()
	{
		return array_keys($this->contribs);
	}
	
	/*** getContrib ***
	@access	public
	@param	Seat or Seatnum
	@return	int	cash put in
	*/
	function getContrib( $seat )
	{
		$num = $seat->num;
		return $this->contribs[ $num ]
		?	array_sum( $this->contribs[ $num ] )
		:	0;
	}
	
	/*** getMaxContrib ***
	@access	public
	@return	int
	*/
	function getMaxContrib()
	{
		$in = array(0);
		foreach( $this->contribs as $seatnum => $tribs)
		{
			if(in_array(FOLD,$tribs))
				continue;
			$in[ $seatnum ] = array_sum($tribs);
		}
		
		arsort($in);
		return current($in);
	}
	
	/*** getDefiicit ***
	@access	public
	@param	Seat
	@return	int
	*/
	function getDeficit( $seat )
	{
		return $this->maxBet - $this->getContrib( $seat );
	}
	
	/*** close ***
	@access public
	@return void
	*/
	function close()
	{
		$this->status = POT_CLOSED;
	}
	
	/*** isOpen ***
	@access	public
	@return	bool
	*/
	function isOpen()
	{
		return $this->status != POT_CLOSED;
	}
	
	/*** createSidePot ***
	@access	public
	@param	Seat   that went all in
	@param	Pot
	@return	Pot
	*/
	function createSidePot( $seat, $sidepot )
	{
		$cap = $this->getContrib($seat);
		$sidepot->limit = $cap;
		
		$excess = array();
		# put bets equal to $cap into the side pot. retain the excess
		foreach($this->contribs as $num=>$contribs)
		{
			$cash = array_sum($contribs);
			if($cash > $cap)
			{
				if($cash > 1500)
					var_dump(array($num=> $contribs ));
				$excess[$num] = $cash-$cap;
				$cash = $cap;
				
			}
			
			$sidepot->add($num, $cash);
		}
		
		if($sidepot->isPaid())
			$sidepot->close();
		else
			$sidepot->status = POT_PRECLOSE;
		
		$this->reset();
		foreach($excess as $num=>$cash)
			$this->add($num,$cash);
		return $sidepot;
	}
	
	/*** isPaid ***
	is the pot even across players?
	@access	public
	@return	bool
	*/
	function isPaid()
	{
		if( $this->status === POT_CLOSED )
			return true;
		if(!count($this->contribs))
			return false;
		
		$tmp = null;
		foreach($this->contribs as $seatnum=>$contrib)
		{
			$sum = array_sum($contrib);
			if(is_null($tmp))
				$tmp = $sum;
			if($sum != $tmp)
				return false;
		}
		
		return true;
	}
	
	/*** reset ***
	this happens during a sidepot split
	@access	public
	@return	void
	*/
	function reset()
	{
		$this->contribs = array();
		$this->size = 0;
	}
}
?>
