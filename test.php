<?php
/*
	Bare-bones unit testing
*/

include 'autoloader.php';

diceCheck();
echo "All tests passed" . PHP_EOL;

// make sure our die isn't loaded
function diceCheck() {
	$rolls = [];
	$dice = new Dice();
	$countRolls = 10000;
	for ($i = 0; $i < $countRolls; $i++) {
		$roll = $dice->roll();
		if (!isset($rolls[$roll])) {
			$rolls[$roll] = 0;
		}
		$rolls[$roll]++;
	}
	foreach ($rolls as $hits) {
		$hitRate = $hits / $countRolls;
		$expectedHitRate = 1 / Dice::SIDES;
		if (abs($hitRate - $expectedHitRate) > 0.01) {
			throw new Exception ('Dice roll distribution unacceptable!');
		}
	}
}