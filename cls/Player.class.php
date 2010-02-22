<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/
define('DEFAULT_STARTING_CASH' , 1500);
class Player
{
	var $cash,
		$nick,
		$cfg;
	
	function __construct( $nick )
	{
		$this->nick = $nick;
		$this->cfg = new Player_Config();
		$this->cash = DEFAULT_STARTING_CASH;
	}
	
	/*** subCash ***
	@access	public
	@param	int	amount
	@return	const	
	*/
	function subCash( $amt )
	{
		if($amt === ALL_IN)
		{
			$c = $this->cash;
			$this->cash = 0;
			return array($c,ALL_IN);
		}
		
		if($amt >= $this->cash)
		{
			$amt=$this->cash;
			$this->cash=0;
			return array($amt,ALL_IN);
		}
		
		$this->cash -= $amt;
		return array( $amt, BET);
	}
	
	/*** dollar ***
	number format a dollar function
	@access	public
	@param	int	cash. use's player cash when omitted
	@return string 
	*/
	function dollar( $cash=null )
	{
		if(is_null($cash))
			$cash = $this->cash;
		return '$' . number_format($cash,0,'.',',');
	}
}
?>
