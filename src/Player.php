<?php
/*
	Holds state of player in a fight, rolls for fight actions.
	Also holds user options for interacting with the game (start, replay, etc.)
*/

use PubSub\Subscriber;
use PubSub\Message;
use PubSub\Broker;

class Player implements Subscriber {
	use Log;

	const STARTING_ATTACK_POINTS = 2;
	const STARTING_HEALTH_POINTS = 20;
	const REST_HEALTH_POINTS = 1;

	const OPTIONS = [
		'replay' => 'a',
		'start' => 'b',
		'roll' => 'c',
		'auto-roll' => 'd',
	];

	const ACTION_ATTACK = 0;
	const ACTION_DEFENSE = 1;
	const ACTION_REST = 2;

	private $state;

	public function __construct(Broker $broker) {
		$this->state = [
			'attackPoints' => self::STARTING_ATTACK_POINTS,
			'healthPoints' => self::STARTING_HEALTH_POINTS,
			'chosenOption' => null,
			'action' => null,
			'inGame' => false,
			'name' => '',
		];
		$this->broker = $broker;
		$this->broker->subscribe('game.init', $this);
		$this->broker->subscribe('game.roundOver', $this);
		$this->broker->subscribe('game.over', $this);
	}

	public function receiveMessage(Message $message) {
		$this->log("Message: " . json_encode($message), 'info');
		switch ($message->name) {
			case 'game.init':
				$this->state['healthPoints'] = self::STARTING_HEALTH_POINTS;
				break;
			case 'game.roundOver':
				if ($this->state['healthPoints'] <= 0) {
					$this->broker->publish(new Message(
						'player.outOfHealth',
						['player' => $this]
					));
				}
				break;
			case 'game.over':
				$this->state['inGame'] = false;
				break;
		}
	}

	public function setName($name) {
		$this->state['name'] = $name;
	}

	public function getName() {
		return $this->state['name'];
	}

	public function setInGame($inGame) {
		$this->state['inGame'] = $inGame;
	}

	public function isInGame() {
		return $this->state['inGame'];
	}

	public function getAvailableOptions() {
		$options = self::OPTIONS;
		if ($this->state['inGame']) {
			unset($options['replay']);
			unset($options['start']);
			return $options;
		} else {
			unset($options['roll']);
			unset($options['auto-roll']);
			return $options;
		}
	}

	public function setOption($option) {
		if (!in_array($option, $this->getAvailableOptions())) {
			throw new Exception("Unexpected option $option");
		}
		$this->state['chosenOption'] = $option;
	}

	public function getOption() : string {
		return $this->state['chosenOption'];
	}

	public function takeDamage($healthPoints) {
		if ($this->getAction() === self::ACTION_DEFENSE) {
			return;
		}
		$this->state['healthPoints'] -= $healthPoints;
	}

	public function getAttackPoints() {
		return $this->state['attackPoints'];
	}

	public function getHealthPoints() {
		return $this->state['healthPoints'];
	}

	public function rollAction(Dice $dice) {
		$this->state['action'] = $this->getActionForRoll($dice->roll());
	}

	public function getAction() : int {
		return $this->state['action'];
	}

	public function executeAction(Player $target) {
		switch($this->getAction()) {
			case self::ACTION_ATTACK:
				$target->takeDamage($this->getAttackPoints());
				break;
			case self::ACTION_REST:
				$this->state['healthPoints'] += self::REST_HEALTH_POINTS;
				$this->state['healthPoints'] = min($this->state['healthPoints'], self::STARTING_HEALTH_POINTS);
				break;
			// ACTION_DEFENSE applied on takeDamage()
		}
	}

	private function getActionForRoll($roll) {
		switch ($roll) {
			case 1:
			case 2:
				return Player::ACTION_ATTACK;
			case 3:
			case 4:
				return Player::ACTION_DEFENSE;
			case 5:
			case 6:
				return Player::ACTION_REST;
		};
	}

}