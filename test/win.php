<html>
<head>
<title></title>
<link rel="stylesheet" media="screen" type="text/css" href="poker.css" />
</head>

<body>
<?php
require_once('Poker.class.php');
$D = new Deck();
$Table = new Hand();

foreach(range(1,5) as $i)
	$Table->add( $D->draw() );
	
$players = range(1,5);
foreach($players as $k=>$v)
{
	$hole = new Hand();
	$hole->add( $D->draw() );
	$hole->add( $D->draw() );
	$players[$k] = $hole;
}


$center= new HTMLTag("center", $Table->show() );
echo $center->parse();
echo "\n<hr />\n";

$full = array();
$WIN = Poker::rankHands( $Table, $players, $full);

echo "<h2>".intval(count($WIN))." winner(s)</h2>\n\n";
foreach($players as $i=>$h)
{
	$out = Tpl::string('<div class="container{WINNER}">{HAND}<br />{EVAL}</div>');
	$win = in_array($i,$WIN);
	$out->setAttribs(array
	(
		'WINNER' => $win ? ' winner':'',
		'HAND'=>$h->show(),
		'EVAL'=>$full[$i]->rankName()."<br />".$full[$i]->printRun()
	));
	
	echo $out->parse();
}
?>
</body>
</html>
