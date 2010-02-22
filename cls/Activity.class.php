<?php
/**
* A betting round.
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/

# status & action
define('FOLD','_fold_');
define('ALL_IN','_allin_');
define('CHECK','_check_');
define('BET','_bet_');
define('CALL','_call_');
define('BET_ERR','_bet_err_');
define('RESUME_ACTIVITY','_resume_');

class Activity
{
	var $pots,
		$seats,
		$responses,
		$state = RESUME_ACTIVITY;
	
	function __construct( &$pots, &$seats )
	{
		if(!count($pots))
			$pots[] = new Pot();
		$this->pots =& $pots;
		$this->seats =& $seats;
		foreach($seats as $seat)
			if($seat->getStatus() === STATUS_ACTIVE)
				$this->responses[$seat->num] = null;
	}
	
	/*** check ***
	@access	public
	@param	Seat
	@return	const
	*/
	function check($seat)
	{
		$deficit = $this->toCall($seat);
		if($deficit === 0)
			return $this->bet( $seat, CHECK );
		return false;
	}
	
	/*** fold ***
	@access	public
	@param	Seat
	@return bool
	*/
	function fold( $seat )
	{
		$seat->setStatus( STATUS_FOLD );
		$this->responses[ $seat->num ] = FOLD;
		return true;
	}
	
	/*** toCall ***
	given the current hotseat, what is the amount to call?
	@access	public
	@param	Seat
	@param	bool	apply dollar format
	@return	int
	*/
	function toCall( $seat, $dollar=false )
	{
		$mine = $max = 0;
		foreach($this->pots as $p)
		{
			$mine += $p->getContrib( $seat );
			$max += $p->getMaxContrib();
		}
		
		$deficit = intval( $max - $mine );
		return $dollar
		?	Player::dollar($deficit)
		:	$deficit;
	}
	
	/*** postBlind ***
	@access	public
	@param	Seat
	@param	int	big blind amount
	@param	const	small or large
	@return	array	msgs
	*/
	function postBlind( $seat, $amount, $size )
	{
		if($size === SMALL_BLIND)
			$amount /= 2;
		
		if($seat->player->cash <= $amount)
			$amount = $seat->player->cash;
		
		list($cash,$signal) = $seat->player->subCash( $amount );
		
		$pot =& $this->pots[0];
		$pot->add( $seat->num, $cash );
		
		if( $signal === ALL_IN)
			return array($seat->getName() ." can't meet the blind. ALL IN " . Player::dollar($cash));
		else
			return array("You post the ".Player::dollar($amount)." $size blind.", $seat->getName());
	}
	
	/*** bet ***
	@access	public
	@param	Seat
	@param	int	cash bet
	@param	const	all in ?
	@return	void
	*/
	function bet( $seat, $bet, $allin=false )
	{
		$this->potAdd( $seat, $bet );
		if($allin === ALL_IN)
		{
			/* 
			echo "<h1>".get_class($this->currentPot())."</h1>\n";
			$pot = $this->currentPot();
			$sidepot = $pot->createSidePot($seat, $this->makePot() );
			 */
		}
		$this->state = $this->allResponded() && $this->potsPaid()
		?	END_PHASE
		:	RESUME_ACTIVITY;
		return $this->state;
	}
	
	/*** potAdd ***
	seat adds cash to the pot. But equally as important,
	it tells the activity that the player has acting this round.
	Meaning that this is not for the blinds.
	@access	public
	@param	Seat
	@param	int cash bet
	@return	const
	*/
	function potAdd( $seat, $bet )
	{
		$this->responses[ $seat->num ] = $bet;
		foreach($this->pots as $pi=>$pot)
		{
			if($bet<=0)
				break; # nothing to do
			
			if( $pot->isOpen() )
			{
				if($pot->limit === NO_LIMIT)
				{
					$pot->add( $seat->num, $bet);
					$bet=0;
					break;
				}
				else
				{
					$deficit = $pot->getDeficit( $seat );
					if($deficit)
					{
						$bet -= $deficit;
						$pot->add( $seat->num, $deficit );
					}
				}
			}
		}
		
		# left over cash!
		if($bet>0)
		{
			$pot = new Pot();
			$this->pots[] = $pot;
			$pot->add( $seat->num, $bet);
		}
	}
	
	/*** allResponded ***
	have all the players spoken up this round?
	@access	public
	@return	bool
	*/
	function allResponded()
	{
		$live = $this->getLiveHands();
		$in = array_keys(array_filter($this->responses));
		return count(array_intersect($live,$in)) == count($live);
	}
	
	/*** potsPaid ***
	are all the pots even?
	@access	public
	@return	bool
	*/
	function potsPaid()
	{
		foreach($this->pots as $pot)
		{
			$paid = $pot->isPaid();
			if(!$paid)
				return false;
		}
		return true;
	}
	
	/*** getLiveHands ***
	@access	public
	@param	array	optional acceptable statuses
	@return	array	seatnums with live hands
	*/
	function getLiveHands( $statuses = null)
	{
		$r = array();
		foreach($this->seats as $n=>$s)
		{
			if(is_array($statuses) && in_array($s->getStatus(),$statuses) )
				$r[] = $n;
			else
			if( $s->getStatus() === STATUS_ACTIVE )
				$r[] = $n;
		}
		return $r;
	}
	
	/*** finished ***
	if the activity done?
	@access	public
	@return	bool
	*/
	function finished()
	{
		return $this->state === END_PHASE;
	}
	
	/*** currentPot ***
	@access	public
	@return	Pot
	*/
	function currentPot()
	{
		foreach($this->pots as $pot)
			if($pot->isOpen())
				return $pot;
			
		# if we're here, currentPot did nothing.
	}
	
	/*** makePot ***
	create a pot, or recycle an empty one
	@access	public
	@return	Pot
	*/
	function makePot()
	{
		$p = $this->currentPot();
		return is_null($p)
		?	new Pot()
		:	$p;
		
		foreach( $this->pots as $pot )
		{
			if($pot->size == 0)
				return $pot;
		}
		
		$pot = new Pot();
		array_unshift($this->pots, $pot);
		return $pot;
	}
}
?>
