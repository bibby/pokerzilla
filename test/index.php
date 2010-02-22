<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/

define('BZ_PATH','/home/bibby/botzilla/');
require(BZ_PATH."cls/Color.class.php");
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

	
function pots($table)
{
	foreach($table->pots as $i=>$pot)
	{
		echo "<hr />\n";
		foreach( array
		(
			"size" => $pot->size,
			"limit" => $pot->limit,
			"contributors" => count(array_keys($pot->contribs))
		) as $k=>$v)
			echo "<h2>Pot ".(1+$i)." $k = $v</h2>\n";
			
		print_r($pot->contribs);
	}
	echo "<h1>".count($table->pots)." POTS</h1>";
	
	echo "\n\n";
	var_dump($table->pots);
}	

$table = new Table();
$table->addPlayer("A");
$table->addPlayer("B");
$table->addPlayer("C");
$table->start();

$table->newHand();

$table->players["A"]->cash = 15;
#$table->players["B"]->cash = 150;

$table->allin( $table->hotSeat() );
$table->progressPhase();

$table->nextSeat();	$table->turnMsg();
$table->allin( $table->hotSeat() );
$table->progressPhase();

$table->nextSeat();	$table->turnMsg();
$table->allin( $table->hotSeat() );
$table->progressPhase();
 //*/
pots($table);exit;

/*
$table->players["C"]->cash = 15;
$table->call( $table->hotSeat() );
$table->progressPhase();

$table->nextSeat();	$table->turnMsg();
$table->call( $table->hotSeat() );
$table->progressPhase();

$table->nextSeat();	$table->turnMsg();
$table->call( $table->hotSeat() );
$table->progressPhase();

$i = 0;
do
{
	$i++;
	$table->nextSeat();	$table->turnMsg();
	$table->call( $table->hotSeat());
	$prog = $table->progressPhase();
} while( $table->activity->state != END_HAND && $i<50);
// */
$msg = $table->getMsgs();

foreach($msg as $a)
{
	if(!is_array($a))
		echo "<h5>$a</h5>\n";
	else
	{
		list($a,$b)=$a;
		echo "<h3>$b: <strong>$a</strong></h3>\n";
	}
}

foreach($table->seats as $seat)
{
	if($seat->filled)
		echo $seat->getName() . " : " . Player::dollar( $seat->player->cash ) . " <br />\n";
}

var_dump
(
	$table->activity->state,
	$table->activity->allResponded(),
	$table->activity->potsPaid()
);


?>
