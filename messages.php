<?php

require_once 'includes/inc.global.php';

if (isset($_POST['action'])) {
	test_token( );

	try {
		switch ($_POST['action']) {
			case 'read' :
				$Message->set_message_read($_POST['ids']);
				break;

			case 'unread' :
				$Message->set_message_unread($_POST['ids']);
				break;

			case 'delete' :
				$Message->delete_message($_POST['ids']);
				break;

			default :
				break;
		}
	}
	catch (MyException $e) {
		Flash::store('Message Action FAILED !', true);
	}
}

$meta['title'] = 'Message Center';
$meta['head_data'] = '
	<script type="text/javascript" src="scripts/messages.js"></script>
';

$contents = '
		<form method="post" action="'.$_SERVER['REQUEST_URI'].'"><div>
			<input type="button" name="send" id="send" value="Send Message" />
		</div></form>';


// INBOX
$messages = $Message->get_inbox_list( );
$table_format = array(
	array('SPECIAL_CLASS', 'my_empty(\'[[[view_date]]]\')', 'highlight') ,
	array('SPECIAL_HTML', 'true', 'id="msg[[[message_id]]]"') ,

	array('Id', 'message_id') ,
	array('Subject', '###@htmlentities(strmaxlen(html_entity_decode(\'[[[subject]]]\', ENT_QUOTES), 25), ENT_QUOTES, \'ISO-8859-1\', false)') ,
	array('From', '###\'[[[sender]]]\'.(([[[global]]]) ? \' <span class="highlight">(<abbr title="GLOBAL">G</abbr>)</span>\' : \'\')') ,
	array('Date Sent', '###@ifdateor(Settings::read(\'long_date\'), strtotime(\'[[[send_date]]]\'), strtotime(\'[[[create_date]]]\'))') ,
	array('Date Read', '###@ifdateor(Settings::read(\'long_date\'), strtotime(\'[[[view_date]]]\'), \'Never\')') ,
	array('Date Expires', '###@ifdateor(Settings::read(\'long_date\'), strtotime(\'[[[expire_date]]]\'), \'Never\')') ,
	array('<input type="checkbox" id="in_all" />', '<input type="checkbox" name="ids[]" value="[[[message_id]]]" class="in_box" />', 'false', 'class="edit"') ,
);
$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no messages in your inbox.</p><!-- NO_INBOX -->' ,
	'caption' => 'Inbox' ,
);
$table = get_table($table_format, $messages, $table_meta);

// add the message edit form if we have messages shown
if (false === strpos($table, 'NO_INBOX')) {
	$contents .= '
		<form method="post" action="'.$_SERVER['REQUEST_URI'].'"><div class="action">
			<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
			'.$table.'
			<select name="action" id="in_action" style="float:right;">
				<option value="">With Selected:</option>
				<option value="read">Mark as Read</option>
				<option value="unread">Mark as Unread</option>
				<option value="delete">Delete</option>
			</select>
		</div></form>';
}
else {
	$contents .= $table;
}


// OUTBOX
$result = $Message->get_outbox_list( );
$table_format = array(
	array('SPECIAL_CLASS', ' ! [[[sent]]]', 'unsent') ,
	array('SPECIAL_HTML', 'true', 'id="msg[[[message_id]]]"') ,

	array('Id', 'message_id') ,
	array('Subject', '###@htmlentities(strmaxlen(html_entity_decode(\'[[[subject]]]\'), 25), ENT_QUOTES, \'ISO-8859-1\', false)') ,
	array('To', 'recipients') ,
	array('Date Sent', '###@ifdateor(Settings::read(\'long_date\'), strtotime(\'[[[send_date]]]\'), strtotime(\'[[[create_date]]]\'))') ,
	array('Date Expires', '###@ifdateor(Settings::read(\'long_date\'), strtotime(\'[[[expire_date]]]\'), \'Never\')') ,
	array('<input type="checkbox" id="out_all" />', '<input type="checkbox" name="ids[]" value="[[[message_id]]]" class="out_box" />', 'false', 'class="edit"') ,
);
$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no messages in your outbox.</p><!-- NO_OUTBOX -->' ,
	'caption' => 'Outbox' ,
);
$table = get_table($table_format, $result, $table_meta);

// add the message edit form if we have messages shown
if (false === strpos($table, 'NO_OUTBOX')) {
	$contents .= '
		<form method="post" action="'.$_SERVER['REQUEST_URI'].'"><div class="action">
			<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
			'.$table.'
			<select name="action" id="out_action" style="float:right;">
				<option value="">With Selected:</option>
				<option value="delete">Delete</option>
			</select>
		</div></form>';
}
else {
	$contents .= $table;
}


// ADMIN LIST
if (false && $GLOBALS['Player']->is_admin) {
	$result = $Message->get_admin_list( );
	$table_format = array(
		array('SPECIAL_CLASS', ' ! [[[sent]]]', 'unsent') ,
		array('SPECIAL_HTML', 'true', 'id="msg[[[message_id]]]"') ,

		array('Id', 'message_id') ,
		array('Subject', '###@htmlentities(strmaxlen(html_entity_decode(\'[[[subject]]]\'), 25), ENT_QUOTES, \'ISO-8859-1\', false)') ,
		array('From', 'sender') ,
		array('To', 'recipients') ,
		array('Date Sent', '###@ifdateor(Settings::read(\'long_date\'), strtotime(\'[[[send_date]]]\'), strtotime(\'[[[create_date]]]\'))') ,
		array('Date Expires', '###@ifdateor(Settings::read(\'long_date\'), strtotime(\'[[[expire_date]]]\'), \'Never\')') ,
	);
	$table_meta = array(
		'sortable' => true ,
		'no_data' => '<p>There are no messages in the admin list.</p><!-- NO_ADMIN -->' ,
		'caption' => 'Admin List' ,
	);
	$table = get_table($table_format, $result, $table_meta);

	// no form
	$contents .= $table;
}


$hints = array(
	'Click anywhere on a row to read your messages.' ,
	'<span class="highlight">Colored inbox entries</span> indicate messages that have not been read.' ,
	'<span class="highlight">(<abbr title="GLOBAL">G</abbr>)</span> indicates a GLOBAL message sent by an administrator.',
	'<span class="highlight">Colored outbox entries</span> indicate messages that have not been sent.' ,
	'Colored outbox <span class="highlight">recipient</span> entries indicate messages that have not been read.' ,
	'Colored outbox <span class="highlight">sent dates</span> indicate messages that have not been sent.' ,
);

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

