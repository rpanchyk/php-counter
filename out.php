<?php

include_once dirname(__FILE__) . '/counter.php';
$ftc = new FireTrotCounter();
//$ftc->GetCounter();

$ftc->GetCounter(
	'counter.png',
	array(255, 255, 255),
	true,
	array('font'=>'3', 'left'=>'19', 'top'=>'2'),
	null,
	array('font'=>'3', 'left'=>'72', 'top'=>'2'),
	null
);

?>
