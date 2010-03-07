<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/
if(!defined('POKER_PATH'))
{
	define('POKER_PATH', BZ_PATH."poker/");
	define('POKER_CLS', POKER_PATH."cls");
	define('POKER_DATA', POKER_DATA."data");
}

foreach( array_filter(
	scandir(POKER_CLS),
	create_function('$f','return substr($f,-9)=="class.php";')
) as $clsFile)
	require_once( POKER_CLS."/$clsFile" );


class poker extends ziggi
{
	function __construct()
	{
		$this->table = new Table();
	}
	
	function parseBuffer()
	{
		if($this->isEmpty())
			return;
		
		$user = $this->getUser();
		$text = trim(strtolower( $this->getInput() ));
		
		if( $text == 'play')
		{
			$add = $this->table->addPlayer($user);
			switch($add)
			{
				case TOURNEY_STARTED:
					$msg = "Tournament has started already.";
				break;
				
				case PLAYER_EXISTS:
					$msg = "$user is already at this table";
				break;
				
				case NO_SEATS:
					$msg = "all seats at this table are filled";
				break;
				
				case PLAYER_ADDED:
					$seat = $this->table->getPlayerSeat($user);
					$seatNum = $seat->num;
					$msg = "Welcome to the game, $user. You're in seat #$seatNum";
				break;
			}
		}
		
		if($msg)
		{
			$this->printMsg($msg);
			return;
		}
		
		## from here below, require a seat at the table
		$seat = $this->table->getPlayerSeat( $user );
		if(!$seat)
			return;
		
		if( $text == "start game" )
		{
			$start = $this->table->start();
			
			switch($start)
			{
				case NOT_ENOUGH_PLAYERS:
					$msg = "There aren't enough players to start.";
				break;
				
				case GAME_STARTED:
					$this->table->newHand();
					$msg = $this->table->getMsgs();
				break;
			}
		}
		
		## debug
		if($text == "hotseat")
			$msg = $this->table->hotSeat()->getName() . "'s turn";
		if($text == "tocall")
			$msg = $this->table->activity->toCall( $this->table->hotSeat(), true );
		
		if($msg)
		{
			$this->printMsg($msg);
			return;
		}
		
		## from here below, require the hotSeat
		if( $this->table->hotSeat() != $seat )
			return;
		
		if( $text == "all in")
			$signal = $this->table->allin( $seat );
		
		if( $text == "check")
			$signal = $this->table->check( $seat );
		
		if( $text == "fold")
			$signal = $this->table->fold( $seat );
		
		if( $text == "call")
			$signal = $this->table->call( $seat );
		
		if( $this->getArg(0) == "bet")
			$signal = $this->table->bet( $seat, $this->getArg(1) );
		
		$this->table->progressPhase();
		
		switch($signal)
		{
			case false:
			break;
			case BET_ERR:
				$this->addMsg("Invalid bet: " . $this->getArg(1) . "?", $seat->getName() );
			break;
			case BET:
				$this->table->nextSeat();
				$this->table->turnMsg();
			break;
		}
		
		$msg = $this->table->getMsgs();
		if($msg)
			$this->printMsg($msg);
	}
	
	/*** printMsg ***
	talk to players
	@access	public
	@param	mixed
	@return	void
	*/
	function printMsg( $msg )
	{
		if(!is_array($msg))
			$this->pm($msg);
		else
		{
			foreach($msg as $m)
			{
				list( $what, $who ) = $m;
				$this->pm( $what, $who );
			}
		}
		
		$this->table->resetMsgs();
	}
}
?>
