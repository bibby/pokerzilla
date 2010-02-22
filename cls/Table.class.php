<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/
define('MAX_SEATS',9);
define('MIN_PLAYERS',2);
define('CASH_GAME_BLIND',20);

define('PLAYER_EXISTS','_already_there_');
define('PLAYER_ADDED','_added_ok_');
define('NO_SEATS','_no_seats_');
define('TOURNEY_STARTED','_started_');
define('NOT_ENOUGH_PLAYERS','_not_enough_players_');
define('GAME_STARTED','_start_ok_');

define('DEAL','_deal_');
define('FLOP','_flop_');
define('TURN','_turn_');
define('RIVER','_river_');

# action returns
define('PLAY_CONTINUES', '_carry_on' );
define('END_PHASE' , '_end_phase_');
define('END_HAND' , '_end_hand_');
define('END_GAME' , '_end_game_');
define('RESUME_GAME' , '_resume_game_');

class Table
{
	var $active = false,
		$seats = array(),
		$players = array(),
		$activity = null,
		$hotSeat = 0,
		$gamePhase = null,
		$bigblind = 0,
		$handCount = 0,
		$cashgame = false,
		$msgs = array(),
		$pots = array(),
		$deck,
		$board;
		
	function __construct()
	{
		foreach(range(1,MAX_SEATS) as $seatNum)
			$this->seats[$seatNum] = new Seat($seatNum);
	}
	
	/*** start ***
	starts the game play
	@access	public
	@return	const
	*/
	function start()
	{
		$c = $this->countPlayers();
		if( $c < MIN_PLAYERS )
			return NOT_ENOUGH_PLAYERS;
		$this->active = true;
		return GAME_STARTED;
	}
	
	/*** stop ***
	stops the game play
	@access	public
	@return	void
	*/
	function stop()
	{
		$this->active = false;
	}
	
	
	/*** addPlayer ***
	@access	public
	@param	Player
	@return	int seat number or false
	*/
	function addPlayer( $nick )
	{
		if($this->cashgame == false && $this->started)
			return TOURNEY_STARTED;
		
		$seat = $this->getPlayerSeat($nick);
		if( is_a($seat,'Seat') )
			return PLAYER_EXISTS;
		
		$seat = $this->findEmptySeat();
		if(!$seat)
			return NO_SEATS;
		
		$Player =
		$this->players[ $nick ] = 
			new Player($nick);
		$seat->addPlayer( $Player );
		
		if(count($this->players) == 1)
			$seat->button=true;
		return PLAYER_ADDED;
	}
	
	/*** findEmptySeat ***
	@access	public
	@return	Seat or false
	*/
	function findEmptySeat()
	{
		$empty = array();
		foreach( $this->seats as $s )
			if( !$s->filled )
				$empty[] = $s;
		if(!count($empty))
			return false;
		
		foreach(range(1,3) as $i)
			shuffle($empty);
		return array_pop($empty);
	}
	
	/*** nextSeat ***
	get the next seat with a warm body 
	@access	public
	@return Seat
	*/
	function nextSeat()
	{
		do
		{
			$this->hotSeat = 1 + ( $this->hotSeat % 9);
			$Seat = $this->hotSeat();
		} while ( $Seat->getStatus() !== STATUS_ACTIVE );
		return $Seat;
	}
	
	/*** hotSeat ***
	return the hot seat
	@access	public
	@return	Seat
	*/
	function hotSeat()
	{
		return $this->seats[ $this->hotSeat ];
	}
	
	
	/*** getButton ***
	get the seat number with the button
	@access	public
	@param	bool	return Seat
	@return	int	seat with the button
	*/
	function getButton($getSeat=false)
	{
		foreach($this->seats as $seat)
			if($seat->button)
				return $getSeat? $seat : $seat->num;
	}
	
	/*** moveButton ***
	"button button, who's got the button?"
	@access	public
	@return	int	seat with the button
	*/
	function moveButton()
	{
		$button = $this->getButton();
		$this->hotSeat = $button;
		$this->seats[$button]->button = false;
		$next = $this->nextSeat(); # moves hotSeat
		$next->button = true;
	}
	
	
	/*** findPlayer ***
	@access	public
	@param	string	nick
	@return	Player
	*/
	function findPlayer( $nick )
	{
		return $this->players[ $nick ];
	}
	
	/*** newActivity ***
	@access	public
	@return	void
	*/
	function newActivity()
	{
		$this->activity = new Activity( $this->pots, $this->seats );
	}
	
	/*** newHand ***
	deals a new hand
	@access	public
	@param	int	big blind
	@return	void
	*/
	function newHand()
	{
		$this->pots = array();
		$this->newActivity();
		$big = $this->getBlind();
		$this->resetMsgs();
		$this->resetSeatStatus();
		$this->moveButton();
		
		$this->deck = new Deck();
		$this->deal(); # adds msgs
		
		$blind = $this->getBlind();
		$this->addMsg("You're on the button this hand.", $this->getButton(TRUE)->getName() );
		$this->addMsg( $this->activity->postBlind( $this->nextSeat(), $blind, SMALL_BLIND ) );
		$this->addMsg( $this->activity->postBlind( $this->nextSeat(), $blind, BIG_BLIND ) );
		$this->nextSeat(); # next
		
		#	alert the hot seat ..
		$this->turnMsg();
		$hotname = $this->hotSeat()->getName();
		$this->addMsg("Cards are dealt. $hotname's turn.");
	}
	
	/*** getBlind ***
	get the blind based on the number of hands
	@access	public
	@return	
	*/
	function getBlind()
	{
		if($this->cashgame)
			return CASH_GAME_BLIND;
			
		$blinds = array(20,30,50,100,200,400,800,1600,2000,3000,5000,10000,20000);
		
		return $blinds[ floor( $this->handCount / 10 ) ];
	}
	
	/*** getPlayerSeat ***
	@access	public
	@param	string	nick
	@return	Seat
	*/
	function getPlayerSeat( $nick )
	{
		foreach($this->seats as $seat)
			if($seat->player && $seat->player->nick == $nick)
				return $seat;
		return false;
	}
	
	/*** countPlayers ***
	get the number of seats with warm bodies
	@access	public
	@param	bool	only that can act
	@return	int 
	*/
	function countPlayers( $active=false )
	{
		$c = 0;
		foreach($this->seats as $s)
		{
			if(!$s->filled)
				continue;
			
			if($active && $s->status != STATUS_ACTIVE )
				continue;
			
			$c++;
		}
		
		return $c;
	}
	
	/*** resetMsgs ***
	@access	public
	@return	void
	*/
	function resetMsgs()
	{
		$this->msgs = array();
	}
	
	/*** addMsg ***
	@access	public
	@param	string msg
	@param	string nick
	@return	void
	*/
	function addMsg($msg, $to="#pokerzilla")
	{
		if(is_array($msg))
		{
			list($what,$who) = $msg;
			$this->addMsg($what,$who);
			return;
		}
		
		$this->msgs[] = array($msg,$to);
	}
	
	/*** getMsgs ***
	@access public
	@return	array
	*/
	function getMsgs()
	{
		return $this->msgs;
	}
	
	/*** setPhase ***
	@access	public
	@param	const	one of the game phases
	@return	void
	*/
	function setPhase( $phase )
	{
		$this->gamePhase = $phase;
	}
	
	
	/*** deal ***
	deal players their hole cards
	@access public
	@return	void
	*/
	function deal()
	{
		$msgs=array();
		$this->setPhase( DEAL );
		 
		// this should be the small blind
		$hotSeat = $this->hotSeat;
		$dealer = $this->getButton();
		
		foreach( $this->seats as $seat)
		{
			if( $seat->filled )
				$seat->newHand();
		}
		
		do
		{
			$this->nextSeat()->addCard( $this->deck->draw() );
		} while( count($this->seats[$dealer]->hand->cards) < 2 );
		// should be back to the small
		
		foreach( $this->seats as $seat)
		{
			if( !$seat->filled )
				continue;
			$this->addMsg( $seat->printCards(), $seat->player->nick );
		}
	}
	
	/*** resetSeatStatus ***
	sets any player with cash to status active.
	@access	public
	@return	void
	*/
	function resetSeatStatus()
	{
		foreach( $this->seats as $seat)
		{
			if( $seat->filled && $seat->player->cash <= 0)
				$seat->vacate();
			
			$seat->setStatus( STATUS_ACTIVE );
		}
	}
	
	/*** turnMsg ***
	tells the hot seat what is needed of them
	@access	public
	@return	void (adds msgs)
	*/
	function turnMsg()
	{
		$seat = $this->hotSeat();
		$deficit = $this->activity->toCall( $seat );
		$action = $deficit == 0
		?	"bet or check"
		:	Player::dollar($deficit) ." to call";
		$this->addMsg( "It's your turn. $action.", $seat->getName());
	}
	
	/*** fold ***
	@access	public
	@param	Seat
	@return	const
	*/
	function fold( $seat )
	{
		$ok = $this->activity->fold( $seat );
		$this->addMsg( $seat->getName() . " folds." );
		$c = $this->countPlayers( $active=true );
		$seat->setStatus( STATUS_FOLD );
		return $c > 1
		?	FOLD
		:	END_HAND;
	}
	
	/*** check ***
	@access	public
	@param	Seat
	@return	void
	*/
	function check( $seat )
	{
		$status = $this->activity->check( $seat );
		if($status===false)
		{
			$this->addMsg("You can't check!");
			return;
		}
		
		$this->addMsg( $seat->getName() . " checks.");
		return CHECK;
	}
	
	/*** allin ***
	ALL IN!
	@access	public
	@param	Seat
	@return	const
	*/
	function allin( $seat )
	{
		list($cash, $signal) = $seat->player->subCash( ALL_IN );
		$seat->setStatus( STATUS_ALL_IN );
		$this->activity->bet($seat,$cash, ALL_IN);
		$this->addMsg( $seat->getName() . " goes ALL IN! (".Player::dollar($cash).")");
		return $signal; 
	}
	
	/*** call ***
	@access	public
	@param	Seat
	@return	void
	*/
	function call( $seat )
	{
		$deficit = $this->activity->toCall( $seat );
		if($deficit === 0)
			return $this->check( $seat );
		
		if($deficit >= $seat->player->cash)
			return $this->allin( $seat );
		
		list($cash, $signal) = $seat->player->subCash( $deficit );
		$this->activity->bet($seat,$cash);
		$this->addMsg( $seat->getName() . " calls. (".Player::dollar($cash).")");
		return $signal;
	}
	
	/*** bet ***
	plays your get
	@access	public
	@param	Seat
	@param	int	bet amount
	@return	void
	*/
	function bet( $seat, $bet )
	{
		$bet = intval($bet);
		$deficit = $this->activity->toCall( $seat );
		
		if($deficit>0 && $bet < $deficit)
			return BET_ERR;
			
		if($deficit >= $seat->player->cash)
			return $this->allin( $seat );
		
		list($cash, $signal) = $seat->player->subCash( $bet );
		$this->activity->bet($seat,$cash);
		$this->addMsg( $seat->getName() . " bets. (".Player::dollar($cash).")");
		return $signal;
	}
	
	/*** progressPhase ***
	if the activity is complete, turn some cards, declare winners, etc
	@access	public
	@return	void adds msgs
	*/
	function progressPhase()
	{
		if(!$this->activity->finished())
			return;
		
		$this->newActivity();
		switch($this->gamePhase)
		{
			case DEAL:
				$this->addMsg("Here comes the flop!");
				$this->board = new Hand();
				foreach(range(1,3) as $i)
					$this->board->addCard( $this->deck->draw() );
				$this->setPhase( FLOP );
			break;
			
			case FLOP:
				$this->addMsg("the turn..");
				$this->board->addCard( $this->deck->draw($burn=true) );
				$this->setPhase( TURN );
			break;
			
			case TURN:
				$this->addMsg("the river..");
				$this->board->addCard( $this->deck->draw($burn=true) );
				$this->setPhase( RIVER );
			break;
			
			case RIVER:
				$this->activity->state = END_HAND;
				$this->addMsg("EVAL!");
				foreach($this->pots as $i=>$p)
					$this->addMsg("Pot ".(1+$i)."/".count($this->pots)." " . Player::dollar($p->size));
			break;
		}
		
		if( $this->activity->state !== END_HAND)
		{
			$this->addMsg( $this->board->printHand() );
			return;
		}
		
		$this->endHand();
		
		return $this->countPlayers() === 1
		?	END_GAME
		:	RESUME_GAME;
	}
	
	/*** endHand ***
	A hand is over. Eval and pay the winners
	@access	public
	@return	const	continue or end game
	*/
	function endHand()
	{
		# show hands
		$live = $this->activity->getLiveHands( array(STATUS_ACTIVE,STATUS_ALL_IN) );
		if(count($live)>1)
		{
			foreach($live as $num)
			{
				$seat = $this->seats[$num];
				$this->addMsg( $seat->getName() . " has " . $seat->hand->printHand() );
			}
		}
		
		while( count($this->pots) )
		{
			$pot = array_pop( $this->pots);
			$in = $pot->getEligblePlayers();
			if(!$pot->size)
				continue;
			
			# only person to bet this pot
			if( count($in) === 1 )
			{
				$solo = $this->seats[current($in)];
				$this->addMsg(Player::dollar($pot->size) . " is returned to " .$solo->getName());
				$solo->player->cash += $pot->size;
				continue;
			}
			
			# remove folds from eligibility
			foreach( $in as $i=>$num)
			{
				if($this->seats[$num]->getStatus() === STATUS_FOLD)
					unset($in[$i]);
			}
			
			# last unfolded player
			if( count($in) === 1 )
			{
				$solo = $this->seats[current($in)];
				$this->addMsg($solo->getName() . " wins a " . Player::dollar($pot->size) . " pot.");
				$solo->player->cash += $pot->size;
				continue;
			}
			
			## looks like we have a showdown!
			list($winner,$fullhands) = $this->showdown( $in );
			
			if(count($winner)===1)
			{
				$solo = $this->seats[current($winner)];
				$hand = $fullhands[$solo->num];
				$this->addMsg($solo->getName() . " wins a " . Player::dollar($pot->size) . " pot. " . $hand->rankName() . " " .$hand->printRun() );
				$solo->player->cash += $pot->size;
			}
			else
			{
				$names = array();
				foreach($winner as $num)
				{
					$seat = $this->seats[$num];
					$names[] = $seat->getName();
					$seat->player->cash += round($pot->size / count($winner) );
				}
				$this->addMsg( join(', ',$names) . " split a pot worth " . Player::dollar($pot->size));
			}
		}
		
		foreach($this->seats as $num=>$seat)
		{
			if( $seat->filled && $seat->player->cash <= 0 )
			{
				$place = $this->countPlayers();
				switch(substr($place,-1))
				{
					case "1":
						$place = $place."st";
					break;
					case "2":
						$place = $place."nd";
					break;
					case "3":
						$place = $place."rd";
					break;
					default:
						$place = $place."th";
					break;
				}
				$this->addMsg( $seat->getName() . " finishes in $place place.");
				$seat->vacate();
			}
		}
	}
	
	/*** showdown ***
	@access	public
	@param	array	seat numbers
	@return	array	mixed winner	array fullhands 
	*/
	function showdown( $seatnums )
	{
		$hands = array();
		foreach( $seatnums as $num)
			$hands[$num] = $this->seats[$num]->hand;
		
		$winner = Poker_Eval::rankHands( $this->board, $hands, $fullhands );
		return array($winner, $fullhands);
	}
}
?>
