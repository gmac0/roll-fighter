<?php
/*
	Handles the game replay feature by capturing pub/sub messages and holding them in memory
*/

use PubSub\Subscriber;
use PubSub\Message;
use PubSub\Broker;

class Replay implements Subscriber {
	use Log;

	private $currentSession = [];
	private $lastSession = [];

	public function __construct(Broker $broker) {
		$this->broker = $broker;
		$this->broker->subscribe('game.init', $this);
		$this->broker->subscribe('game.roundOver', $this);
		$this->broker->subscribe('game.complete', $this);
	}	

	public function receiveMessage(Message $message) {
		$this->log("Message: " . json_encode($message), 'info');
		switch ($message->name) {
			case 'game.init':
				$this->currentSession = [];
				$this->currentSession[] = $message;
				break;
			case 'game.roundOver':
				$this->currentSession[] = $message;
				break;
			case 'game.complete':
				$this->currentSession[] = $message;
				$this->lastSession = $this->currentSession;
				break;
		}
	}

	public function runReplay() {
		if (empty($this->lastSession)) {
			$this->log("Nothing saved to replay");
			return;
		}
		foreach ($this->lastSession as $message) {
			$this->broker->publish($message);
		}
	}
}