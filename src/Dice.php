<?php
/*
	Random number generator
*/

class Dice {
	const SIDES = 6;

	public function roll() : int {
		return mt_rand(1, self::SIDES);
	}

}