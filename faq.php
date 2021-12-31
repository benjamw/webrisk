<?php

require_once 'includes/inc.global.php';


$meta['title'] = 'Frequently Asked Questions';
$meta['head_data'] = '
	<script src="scripts/messages.js"></script>
';

$contents = '
	<div>
		<strong>What happens when eliminating an opponent?</strong><br>		
		If during your turn you eliminate an opponent by defeating his or her last army on the game board, you win any cards that player has collected.  If winning them gives you 6 or more cards, you must immediately trade in enough sets to reduce your hand to 4 or fewer cards, but once your hand is reduced to 4,3, or 2 cards, you stop trading (unless Warmonger is enabled, then you must continue trading, if you can).  But if winning them gives you fewer than 6, you must wait until the beginning of your next turn to trade in a set.  Note: When you draw a card from the deck at the end of your turn (for having won a battle), if this brings your total to 6, you must wait until your next turn to trade in.<br>
		<br>
		<strong>What does "kamikaze" mean?:</strong><br>
		If you CAN attack, you MUST attack!<br>
		<br>
		<strong>What does "connected" fortification mean?</strong><br>	
		If the game configuration allows connected fortifications, the fortifying group may travel as far as possible with the one caveat, that it must travel through friendly territories!<br>
		<br>
		<strong>What does "warmonger" mean?</strong><br>	
		If you CAN trade, you MUST trade!<br>
		<br>
		<strong>What is a "placement" limit?</strong><br>	
		If the game configuration defines a placement limit, this number is the maximum number of armies that can be placed on a territory during the INITIAL army placement.<br>
		Note: Once the game state switches to playing, there is no limit on army placement.<br>
		<br>
		<strong>How do I earn cards?</strong><br>
		At the end of any turn in which you have captured at least one territory, you will earn one card. You are trying to collect sets of 3 cards in any of the following combinations:<br>
		3 cards of same design (Infantry, Cavalry, or Artillery)<br>
		or 1 each of 3 designs<br>
		or any 2 plus a wild card<br>
		<br>
		<strong>What do I do with the cards?</strong><br>
		If you have collected a set of 3 cards, you may turn them in at the beginning of your next turn, or you may wait. But if you have 5 or 6 cards at the beginning of your turn, you must trade in at least one set, and may trade in a second set if you have one.<br> 
		Note: If Warmonger is enabled, you must trade a matching set, and continue to trade sets until you have no more to trade.<br>
		<br>
		<strong>What is the "trade card bonus" for occupied territories?</strong><br>
		If any of the 3 cards you trade in shows a territory you occupy, you may receive extra armies. You must place those armies onto that particular territory.<br>
		Note: The extra army bonus is 2 by default, but can be set to any whole number including zero, when the game is created. Check the Game Info to see the bonus value for a particular game.<br>
		When your trade includes more than one occupied territory, you pick which territory receives the bonus.  You will only receive one bonus per trade.<br>
		<br>
		<strong>What does "nuclear war" mean?</strong><br>
		In nuclear war, the traded bonus card is used to REDUCE an ENEMY territory by the bonus value. 
		The default (2 armies) can be changed in the custom trades section of the form by the game creator.<br>
		Note: Where bonus card value is high, and/or land army count is low, a nuke strike will leave the enemy land with a one army minimum.<br>
		<br>
		<strong>What does "turncoat" mean?</strong><br>
		In the turncoat game variation, the traded bonus card is used to switch your enemy armies to your army. 
		All of the enemy armies on the bonus land become yours.  However, you may not use a turncoat card against an opponent that has only one territory left.<br>
		Note: With this feature, armies will not be added to the bonus land, however, the army count on the bonus land may be reduced by the bonus card value if the nuke feature is used.
	</div>
';

$hints = [
	'<span class="highlight">Have a question?</span>',
	'Contact the administrator or ask your question in the game chat or main lobby',
];

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer();

