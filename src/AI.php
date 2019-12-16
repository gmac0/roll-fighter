<?php
/*
	AI version of a fighter in the game. Always rolls, cleans itself up after a game ends.
*/

use PubSub\Message;

class AI extends Player {

	public function getOption() : string {
		// always roll the dice
		return self::OPTIONS['roll'];
	}

	// AI is disposable, so make sure we clean ourselves
	// out of the pubsub broker when the game ends
	public function receiveMessage(Message $message) {
		parent::receiveMessage($message);
		switch ($message->name) {
			case 'game.over':
				$this->broker->cleanup($this);
				break;
		}
	}
}