<?php

$GLOBALS['__EMAIL_DATA'] = [
	'invite' => [
		'subject' => 'Game Invitation',
		'message' => '
You have been invited to play in a [[[GAME_NAME]]] game ([[[game_name]]]) at [[[site_name]]].

If you wish to join this game, please log in and do so

==== Message ===========================================

[[[extra_text]]]'
	],



	'start' => [
		'subject' => 'Game Started',
		'message' => '
The [[[GAME_NAME]]] game ([[[game_name]]]) you are playing at [[[site_name]]] has begun.

Please log in and place your armies. Good Luck!'
	],



	'turn' => [
		'subject' => 'Your Turn',
		'message' => '
It is now your turn in the [[[GAME_NAME]]] game ([[[game_name]]]) you are playing at [[[site_name]]].

Please log in and take your turn. Good Luck!'
	],



	'nudge' => [
		'subject' => 'Your Turn',
		'message' => '
[[[sender]]] sent you a nudge to remind you of your turn in the [[[GAME_NAME]]] game ([[[game_name]]]) you are playing at [[[site_name]]].

Please log in and take your turn. Good Luck!'
	],



	'defeated' => [
		'subject' => 'Defeated',
		'message' => '
You have been pwninated by [[[sender]]] in the [[[GAME_NAME]]] game ([[[game_name]]]) you are playing at [[[site_name]]].

Better luck next time, it was probably the fault of the dice anyways.
You can always log on and create a new game if you still wish to play.'
	],



	'finished' => [
		'subject' => 'Game Finished',
		'message' => '
The [[[GAME_NAME]]] game ([[[game_name]]]) you played at [[[site_name]]] is now finished.

[[[winner]]] has won! Thanks for playing!
You can always log on and create a new game if you still wish to play.'
	],




	'register' => [
		'subject' => 'Player Registered',
		'message' => '
A player has registered:
[[[export_data]]]'
	],



	'approved' => [
		'subject' => 'Registration Approved',
		'message' => '
Your registration has been approved!
You can now log in and play.'
	],
];

