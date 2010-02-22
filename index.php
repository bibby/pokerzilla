<html>
<head>
<title></title>
<link rel="stylesheet" media="screen" type="text/css" href="poker.css" />
</head>

<body>
<?php
/**
*
*@author bibby <bibby@surfmerchants.com>
$Id$
** //*/

require_once('Poker.class.php');

$quota = 4;
while($quota--)
{
	//**
	$D = new Deck();
	$Hand = new Hand();
	foreach(range(1,7) as $i)
		$Hand->add( $D->draw() );
	//*/
	
	/**
	$Hand = new Hand();
	$Hand->add( new Card(2,1) );
	$Hand->add( new Card(3,1) );
	$Hand->add( new Card(4,1) );
	$Hand->add( new Card(5,1) );
	$Hand->add( new Card(6,1) );
	$Hand->add( new Card(7,2) );
	$Hand->add( new Card(8,1) );
	//*/
	
	$div = new HTMLTag("div",$Hand->rank(),array("class"=>"row"));
	$col = new HTMLTag_Collection();
	foreach( $Hand->cards as $c)
	{
		$tpl = $c->show();
		$col->append($tpl);
	}
	#$div->append($col);
	
	echo $div->parse();
	echo $col->parse();
}
?>
</body>
</html>

