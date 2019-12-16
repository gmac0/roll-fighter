# Roll Fighter

## Overview
Do battle with your opponent through random dice rolls! Generally for projects approaching this complexity I'd start using Composer to handle dependencies and utilize existing libraries for a Pub/Sub solution. However since this is a coding exercise I wrote it all from scratch.

## Game Mechanics
Each player has a set amount of attack and health points. A game starts with both players at 20 health points and 2 attack points. Each turn both player roll a six sided die. On a 1-2 the player attacks; 3-4 the player defends; 5-6 the player rests. Attack attempts to do the attack points worth of damage to the opponent. Defend prevents any damage from a possible attack. Rest heals the player 1 health point. If both players attack, then each rolls the die for initiative, and only the player with the higher roll does damage.

## How to play
To play the game run `php main.php` to start up the command line interface client and play against an AI. Your options are to:
	a) replay your last game (must have played at least one game this session to do so)
	b) start a new game
Once in a game you can 
	c) roll the dice on your turn
	d) auto-roll the dice on all your turns until the game is over (in case rolling the dice over and over manually isn't your thing)

## Implementation
This game is implemented using a pub/sub message pattern to prevent the player, game, and client(s) from needing to modify or inspect each other's state directly. This also allows easy addition of turn by turn tracking for replays without the core game logic needing to know about the replay feature.

## Project Structure
* main.php is the entrypoint for the project
* test.php contains a bare-bones unit test; run with `php test.php`
* autoloader.php handles class loading
* src/ contains class definitions for the game classes
* src/PubSub contains the publish-subscribe pattern classes

## Next Steps
After this point the next features would be to add multi-player capability and a DB layer. A DB schema is proposed below: ![Image of DB Schema](https://raw.githubusercontent.com/gmac0/roll-fighter/master/proposed-db-schema.png)
Some examples of using the schema follow:
* The Players and Games tables would match the Player and Game classes and be able to save their state data.
* Players would be loaded from the DB when the client was instantiated to preserve state across play sessions. 
* When a player started a game the server would look for an existing game with only 1 player assigned to perform matchmaking.
* If a game has existed for a certain wait time (determined by checking the `created_on` field) with only 1 player, then an AI would be assigned to play against the waiting player.
* Once a game is complete the Replay class would save all the messages it had in memory to the Games table, allowing that game to be replayed by any client in the future.
* The `xp` column in the Players table would be incremented for each match to add player progression mechanics. This could also include increasing `attack_points` or otherwise boosting the player's stats over time.
* The `PlayerMetrics` table would be used to record things like wins, losses, etc. For example, when a player won you'd update the counter with `UPDATE PlayerMetrics INCREMENT value WHERE player_id = %d AND field = 'won'`;. Indexes on `field` and `player_id` would let you query for all of a particular player's stats, or sum all wins/losses to get global stats.