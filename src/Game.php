<?php

use PubSub\Subscriber;
use PubSub\Message;
use PubSub\Broker;

class Game implements Subscriber {
	use Log;

	const PLAYER_1 = 0;
	const PLAYER_2 = 1;

	private $state;
	private $dice;
	private $broker;

	public function __construct(Broker $broker) {
		$this->state = [
			'players' => [
				self::PLAYER_1 => null,
				self::PLAYER_2 => null,
			],
			'isOver' => false,		// indicates a player has lost
			'isComplete' => false,	// indicates all end of game cleanup is complete
		];
		$this->dice = new Dice();
		$this->broker = $broker;
		$this->broker->subscribe('player.outOfHealth', $this);
	}

	public function receiveMessage(Message $message) {
		$this->log("Message: " . json_encode($message), 'info');
		switch ($message->name) {
			case 'player.outOfHealth':
				$this->state['isOver'] = true;
				break;
		}
	}

	public function addPlayer(Player $player) {
		foreach ($this->state['players'] as $position => $existingPlayer) {
			if ($existingPlayer === null) {
				$this->state['players'][$position] = $player;
				$player->setInGame(true);
				return;
			}
		}
		throw new Exception("Attempting to add player to full game");
	}

	public function start() {
		foreach ($this->state['players'] as &$player) {
			if ($player === null) {
				$player = new AI($this->broker);
			}
		}
	}

	public function isComplete() {
		return $this->state['isComplete'];
	}

	/*
		1. roll for initiative
		2. roll for player action
		3. run actions:
			* defense: block attack (if no attack, do nothing)
			* rest: heal 1 health point
			* attack: deal attack points (if both attack, higher initiative does damage)
	*/
	public function fight() {
		$this->state['initiative'] = $this->rollForInitiative();
		$player1 = $this->state['players'][self::PLAYER_1];
		$player2 = $this->state['players'][self::PLAYER_2];

		$this->broker->publish(new Message('game.awaitPlayerAction', ['player' => $player1]));
		$this->broker->publish(new Message('game.awaitPlayerAction', ['player' => $player2]));

		$player1->rollAction($this->dice);
		$player2->rollAction($this->dice);

		// both attack, need to handle initiative
		if (   $player1->getAction() === Player::ACTION_ATTACK
		    && $player2->getAction() === Player::ACTION_ATTACK
		) {
			$this->attackWithInitiative();
		} else {
			// otherwise each player applies their action
			$player1->executeAction($player2);
			$player2->executeAction($player1);
		}

		$this->broker->publish(new Message('game.roundOver', $this->getFightSummary()));
		if ($this->state['isOver']) {
			$this->broker->publish(new Message('game.over'));
			$this->broker->publish(new Message('game.complete'));
			$this->state['isComplete'] = true;
		}
	}

	private function getFightSummary() {
		$summary = [];
		foreach ($this->state['players'] as $position => $player) {
			$summary['players'][$position] = [
				'action' => $player->getAction(),
				'healthPoints' => $player->getHealthPoints(),
			];
		}
		$summary['initiative'] = $this->state['initiative'];
		return $summary;
	}

	private function attackWithInitiative() {
		$lowInitiatvePlayer = $this->state['players'][$this->getLowerInitiativePosition()];
		$highInitiatvePlayer = $this->state['players'][$this->getHigherInitiativePosition()];
		$highInitiatvePlayer->executeAction($lowInitiatvePlayer);
	}

	private function rollForInitiative() : array {
		$initiative = [
			self::PLAYER_1 => 0,
			self::PLAYER_2 => 0,
		];
		while ($initiative[self::PLAYER_1] === $initiative[self::PLAYER_2]) {
			$initiative = [
				self::PLAYER_1 => $this->dice->roll(),
				self::PLAYER_2 => $this->dice->roll(),
			];
		}
		return $initiative;
	}

	private function getLowerInitiativePosition() {
		if ($this->state['initiative'][self::PLAYER_1] < $this->state['initiative'][self::PLAYER_2]) {
			return self::PLAYER_1;
		}
		return self::PLAYER_2;
	}

	private function getHigherInitiativePosition() {
		if ($this->state['initiative'][self::PLAYER_1] > $this->state['initiative'][self::PLAYER_2]) {
			return self::PLAYER_1;
		}
		return self::PLAYER_2;
	}

}