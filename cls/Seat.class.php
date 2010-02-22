<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/

define('BIG_BLIND','big');
define('SMALL_BLIND','small');

define('STATUS_ACTIVE','_alive_');
define('STATUS_FOLD','_fold_');
define('STATUS_ALL_IN','_allin_');
define('SEAT_EMPTY','_nobody_here');

class Seat
{
	var $player,
		$num,
		$filled = false,
		$button = false,
		$blind = 0,
		$hand,
		$status = STATUS_ACTIVE;
	
	function __construct( $seatNum )
	{
		$this->num = $seatNum;
	}
	
	/*** addPlayer ***
	@access	public
	@param	Player
	@return	bool
	*/
	function addPlayer( $Player )
	{
		if($this->filled)
			return false;
		$this->player = $Player;
		$this->filled = true;
		return true;
	}
	
	/*** vacate ***
	release the player from the seat
	@access public
	@return void
	*/
	function vacate()
	{
		$this->player = null;
		$this->filled = false;
	}
	
	/*** newHand ***
	resets the player's hole cards
	@access	public
	@return	void
	*/
	function newHand()
	{
		$this->hand = new Hand();
	}
	
	/*** addCard ***
	@access	public
	@param	Card
	@return	void
	*/
	function addCard( $Card )
	{
		$this->hand->addCard( $Card );
	}
	
	/*** printCards ***
	shows the player the hole cards
	@access	public
	@return	string
	*/
	function printCards()
	{
		return "Hole cards: " . $this->hand->printHand( $this->player->cfg );
	}
	
	/*** setStatus ***
	@access	public
	@param	const	an active status
	@return	void
	*/
	function setStatus( $to )
	{
		$this->status = $to;
	}
	
	/*** getStatus ***
	@access	public
	@return	const
	*/
	function getStatus()
	{
		return $this->filled
		?	$this->status
		:	SEAT_EMPTY;
	}
	
	/*** getName ***
	player's nick
	@access	public
	@return	string
	*/
	function getName()
	{
		return $this->player->nick;
	}
}
?>
