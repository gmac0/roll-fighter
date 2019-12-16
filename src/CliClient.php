<?php

use PubSub\Subscriber;
use PubSub\Broker;
use PubSub\Message;

class CliClient implements Subscriber{
	use Log;

	private $player;
	private $game;
	private $broker;

	private $actionDescriptions = [
		Player::ACTION_ATTACK => 'attacked',
		Player::ACTION_DEFENSE => 'defended',
		Player::ACTION_REST => 'rested',
	];

	public function __construct(Broker $broker) {
		echo implode(PHP_EOL, [
			"=================================",
			"== WELCOME TO ROLL FIGHTER !!! ==",
			"=================================",
			"",
			"Press ctrl+c to quit at any time.",
			"",
		]) . PHP_EOL;
		$this->broker = $broker;
		$this->broker->subscribe('game.init', $this);
		$this->broker->subscribe('game.roundOver', $this);
		$this->broker->subscribe('game.complete', $this);
		$this->broker->subscribe('game.awaitPlayerAction', $this);
	}

	public function receiveMessage(Message $message) {
		$this->log("Message: " . json_encode($message), 'info');
		switch ($message->name) {
			case 'game.init':
				echo "FIGHT!" . PHP_EOL;
				break;
			case 'game.roundOver':
				$this->updateUser($message->data);
				break;
			case 'game.complete':
				$this->outputWinner();
				break;
			case 'game.awaitPlayerAction':
				$this->awaitPlayerAction($message->data['player']);
				break;
		}
	}

	public function setPlayer(Player $player) {
		$this->player = $player;
	}

	public function awaitPlayerAction($player = null) {
		if ($player !== null && $player !== $this->player) {
			$this->log('wrong player, returning', 'debug');
			return;
		}
		$prompt = "Choose an option:" . PHP_EOL;
		foreach ($this->player->getAvailableOptions() as $name => $hotkey) {
			$prompt .= "$hotkey) $name" . PHP_EOL;
		}
		do {
			echo $prompt;
			$input = readline();
		} while (!in_array($input, $this->player::OPTIONS));
		$this->player->setOption($input);
	}

	public function updateUser($summary) {
		foreach ($summary['players'] as $position => $player) {
			$actionName = $this->actionDescriptions[$player['action']];
			echo "Player$position $actionName, {$player['healthPoints']} health left" . PHP_EOL;
		}
		if (   Player::ACTION_ATTACK == $summary['players'][Game::PLAYER_1]['action']
			&& Player::ACTION_ATTACK == $summary['players'][Game::PLAYER_2]['action']
		) {
			echo "Double attack! Initiative: " . PHP_EOL;
			foreach ($summary['initiative'] as $position => $roll) {
				echo "  Player$position: $roll" . PHP_EOL;
			}
		}
		echo PHP_EOL;
	}

	public function outputWinner() {
		if ($this->player->getHealthPoints() <= 0) {
				echo implode(PHP_EOL, [
					"=================================",
					"==          You Lost :(        ==",
					"=================================",
				]) . PHP_EOL;
		} else {
							echo implode(PHP_EOL, [
					"=================================",
					"==          You Won !!!        ==",
					"=================================",
				]) . PHP_EOL;
		}
	}
}