# Roll Fighter

## Overview
Do battle with your opponent through random dice rolls!

This game is implemented using a pub/sub message pattern to prevent the player, game, and client(s) from needing to modify or inspect each other's state directly. This also allows easy addition of turn by turn tracking for replays without the core game logic needing to know about the replay feature.

## Project Structure
* main.php is the entrypoint for the project
* test.php contains some bare-bones unit tests
* autoloader.php handles class loading
* src/ contains class definitions for the game classes
* src/PubSub contains the publish-subscribe pattern classes

