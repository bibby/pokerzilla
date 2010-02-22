<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/

class Poker_Eval
{
	/**
	@param	array	5 public cards
	@param	array	of players. each with 2 hole cards
	@return mixed	int: playerID with winning hand | array: playerIDs of chops
	*/
	function rankHands( &$table, &$holeCards, &$full)
	{
		$ranks = array();
		foreach($holeCards as $k=>$h)
		{
			$full[$k] = $h->merge($table);
			$ranks[$k] = $full[$k]->rank();
		}
		
		arsort($ranks);
		$winner = array();
		
		foreach($ranks as $k=>$r)
		{
			if(!count($winner))
			{
				$winner[] = $k;
				$topRank = $r;
				continue;
			}
			
			if($r == $topRank)
				$winner[] = $k;
		}
		
		if(count($winner) == 1)
			return $winner;
			
		# card battles
		$hands = array();
		foreach($winner as $p)
			$hands[$p] = $full[$p]->run;
			
		foreach( range(0,4) as $ci )
		{
			$col=array();
			foreach($hands as $p=>$h)
			{
				if(!in_array($p,$winner))
					continue;
				$col[$p]=$h[$ci]->val;
			}
			
			arsort($col);
			$top=null;
			foreach($col as $p=>$v)
			{
				if(is_null($top))
				{
					$top = $v;
					continue;
				}
				
				if($v < $top)
					$winner = array_filter($winner, filter_out($p) );
			}
			
			if(count($winner) == 1)
				return $winner;
		}
		
		return $winner;
	}
}

function filter_out($val)
{
	return create_function('$v','return $v!="'.$val.'";');
}
?>
