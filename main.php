<?php
/*
	Main entrypoint for the Roll Fighter game
*/

include 'autoloader.php';

use PubSub\Broker;
use PubSub\Message;

$broker = new Broker();
$replay = new Replay($broker);
$cliClient = new CliClient($broker);
$player = new Player($broker);
$player->setName('Your Fighter');
$cliClient->setPlayer($player);

// main game loop
while (true) {
	$cliClient->awaitPlayerAction();
	switch($player->getOption()) {
		case Player::OPTIONS['start']:
			start($broker, $player);
			break;
		case Player::OPTIONS['replay']:
			replay($replay);
			break;
		default:
			throw new Exception("Unexpected player option " . $player->getOption());
	}
}

function start(Broker $broker, Player $player) {
	$game = new Game($broker);
	$broker->publish(new Message(
		'game.init', 
		['game' => $game]
	));
	$game->addPlayer($player);
	$game->start();
	do {
		$game->fight();
	} while (!$game->isComplete());
	$broker->cleanup($game);
}

function replay(Replay $replay) {
	$replay->runReplay();
}

