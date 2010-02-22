<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/

define('STRAIGHT_FLUSH',9);
define('FOUR_KIND',8);
define('FULL_HOUSE',7);
define('FLUSH',6);
define('STRAIGHT',5);
define('THREE_KIND',4);
define('TWO_PAIR',3);
define('PAIR',2);
define('HIGH_CARD',1);

define('ACE',14);
define('KING',13);
define('QUEEN',12);
define('JACK',11);

class Hand
{
	var $cards = array();
	static $names = array
	(
		STRAIGHT_FLUSH=>'Straight Flush',
		FOUR_KIND=>'Four of a Kind',
		FULL_HOUSE=>'Full House',
		FLUSH=>'Flush',
		STRAIGHT=>'Straight',
		THREE_KIND=>'Three of a Kind',
		TWO_PAIR=>'Two Pair',
		PAIR => 'One Pair',
		HIGH_CARD =>'High Card'
	);
	
	function addCard($card)
	{
		$this->cards[] = $card;
		$this->rank = '';
		$this->run = '';
	}
	
	function rank()
	{
		$strflush_run=array();
		$strflush = $this->straightFlush($strflush_run);
		
		# is a flush ?
		$flush_run=array();
		$flush= $this->flush($flush_run);
		
		# is a straight ?
		$str8_run=array();
		$str8 = $this->straight($str8_run);
		
		$run=array();
		$counts = $this->counts($run);
		
		if( $strflush )
		{
			$this->rank = STRAIGHT_FLUSH;
			$this->run = $strflush_run;
			return $this->rank;
		}
		
		if( $counts == FOUR_KIND )
		{
			$this->rank = FOUR_KIND;
			$this->run = $run;
			return $this->rank;
		}
		
		
		if( $counts == FULL_HOUSE )
		{
			$this->rank = FULL_HOUSE;
			$this->run = $run;
			return $this->rank;
		}
			
		if( $flush )
		{
			$this->rank = FLUSH;
			$this->run = $flush_run;
			return $this->rank;
		}
		
		
		if( $str8 )
		{
			$this->rank = STRAIGHT;
			$this->run = $str8_run;
			return $this->rank;
		}
			
		if( $counts == THREE_KIND )
		{
			$this->rank = THREE_KIND;
			$this->run = $run;
			return $this->rank;
		}
		
		if( $counts == TWO_PAIR )
		{
			$this->rank = TWO_PAIR;
			$this->run = $run;
			return $this->rank;
		}
		
		if( $counts == PAIR )
		{
			$this->rank = PAIR;
			$this->run = $run;
			return $this->rank;
		}
		
		if( $counts == HIGH_CARD )
		{
			$this->rank = HIGH_CARD;
			$this->run = $run;
			return $this->rank;
		}
	}
	
	
	function printRun($full=true)
	{
		if($full)
		{
			$str=array();
			foreach($this->run as $card)
				$str[] = $card->printCard( new Player_Config() );
			return join(' ',$str);
		}
		else
		{ #short version
			$str='';
			foreach($this->run as $card)
				$str.= $card->face;
			return $str ? "<strong>$str</strong><br />" : "";
		}
	}
	
	
	function straightFlush(&$run)
	{
		$cards = $this->cards;
		$suits = array
		(
			SPADE => array(),
			CLUB =>	array(),
			HEART => array(),
			DIAMOND => array()
		);
		
		usort($cards,'HandSort_sortVal');
		$flushSuit = null;
		foreach($cards as $c)
		{
			$suits[ $c->suit ][] = $c;
			if(count($suits[ $c->suit ]) >= 5)
				$flushSuit=$c->suit;
		}
		
		if(!$flushSuit)
			return false;
			
		$str = $this->straight( $run, $suits[$flushSuit]);
		return $str == STRAIGHT
		?	STRAIGHT_FLUSH
		:	false;
	}
	
	function straight(&$run, $subset=false)
	{
		$cards = $subset? $subset:$this->cards;
		usort($cards,'HandSort_sortVal');
		$ace=false;
		foreach($cards as $c)
		{
			if($c->val == ACE)
				$ace=$c;
			if(is_null($top))
			{
				$top = $c->val;
				$run[]=$c;
			}
			
			if( $c->val == $top )
				continue;
			
			if( ($c->val == ($top-1)) )
			{
				$run[]=$c;
				if(count($run) == 5)
					return STRAIGHT;
				$top=$c->val;
				
				if($top==2 && $ace)
				{
					$run[] = $ace;
					if(count($run) == 5)
						return STRAIGHT;
				}
				continue;
			}
			
			$top = $c->val;
			while(count($run))
				array_pop($run);
			array_push($run,$c);
		}
		while(count($run))
			array_pop($run);
		return false;
	}
	
	function flush(&$run,$subset=false)
	{
		$cards = $subset? $subset:$this->cards;
		$suits = array
		(
			SPADE => array(),
			CLUB =>	array(),
			HEART => array(),
			DIAMOND => array()
		);
		
		usort($cards,'HandSort_sortVal');
		foreach($cards as $c)
		{
			$suits[ $c->suit ][] = $c;
			if(count($suits[ $c->suit ]) == 5)
			{
				$run = $suits[ $c->suit ];
				return FLUSH;
			}
		}
		
		return false;
	}
	
	function counts(&$run)
	{
		$v=array();
		foreach($this->cards as $c)
			$v[$c->val]++;
			
		arsort($v);
		
		if( in_array(2, $v) && in_array(3, $v) )
		{
			$tre = array_search(3, $v);
			$dues = array_search(2, $v);
			
			foreach($this->cards as $c)
				if( $c->val == $tre)
					$run[]=$c;
			foreach($this->cards as $c)
				if( $c->val == $dues)
					$run[]=$c;
					
			do{
				$high = $this->highest($i);
				$i++;
			}while( !in_array($high->val, array($dues,$tre) ) );
			return FULL_HOUSE;
		}
		
		foreach($v as $val=>$count)
		{
			$i=0;
			if($count == 4)
			{
				do{ $high = $this->highest($i); $i++;}
				while( $high->val == $val && $high);
				
				foreach($this->cards as $c)
					if($c->val == $val)
						$run[] = $c;
				
				$run[] = $high;
				return FOUR_KIND;
			}
			
			if($count == 3)
			{
				foreach($this->cards as $c)
					if($c->val == $val)
						$run[] = $c;
				
				do{ 
					$high = $this->highest($i); 
					$i++;
					if($high->val != $val)
						$run[]=$high;
				}
				while( count($run)<5 );
				return THREE_KIND;
			}
			
			if($count == 2)
			{
				$pair_vals = array();
				
				foreach($v as $val => $num)
					if($num == 2)
						$pair_vals[]=$val;
						
				rsort($pair_vals);
				while(count($pair_vals)>2)
					array_pop($pair_vals);
				
				$T = count($pair_vals) >= 2
				?	TWO_PAIR
				:	PAIR;
				
				$pair_cards = array();
				foreach( $this->cards as $c)
					if( in_array( $c->val, $pair_vals) )
						$pair_cards[] = $c;
				
				if(count($pair_cards)>2)
					usort( $pair_cards, 'HandSort_sortVal');
				
				foreach($pair_cards as $c)
					$run[]=$c;
				
				do{
					$high = $this->highest($i);
					$i++;
					if( !in_array( $high->val, $pair_vals) )
						$run[]=$high;
				} while( (count($run) < 5));
				
				return $T;
			}
			
			
			foreach($this->cards as $c)
				$run[]=$c;
			usort( $run, 'HandSort_sortVal');
			while(count($run)>5)
				array_pop($run);
			return HIGH_CARD;
		}
	}
	
	function highest($offset=0)
	{
		$cards = $this->cards;
		usort($cards,'HandSort_sortVal');
		foreach( $cards as $k=>$c)
		{
			if($k==$offset)
				return $c;
		}
	}
	
	/*** printHand ***
	print the cards in the hand
	@access	public
	@param	Player_Config
	@return	string
	*/
	function printHand( $cfg=false )
	{
		if(!$cfg)
			$cfg = new Player_Config();
		
		$cards = array();
		foreach( $this->cards as $card )
			$cards[] = $card->printCard( $cfg );
		
		return join(' ', $cards);
	}
	
	function rankName()
	{
		return self::$names[$this->rank];
	}
	
	
	function merge($hand)
	{
		$Hand = new Hand();
		foreach(array($this, $hand) as $H)
			foreach($H->cards as $c)
				$Hand->addCard($c);
		return $Hand;
	}
}


function HandSort_sortVal($a,$b)
{
	return $a->val > $b->val ? -1 : 1;
}
?>
