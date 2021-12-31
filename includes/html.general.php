<?php

/** function get_header
 *		Generate the HTML header portion of the page
 *
 * @param array [optional] meta variables
 *   @option string 'title' the page title
 *   @option string 'head_data' any HTML to be inserted in the head tag
 *   @option array 'menu_data' the data for the counts in the menu
 *   @option array 'game_data' the game data for my game list under the menu
 *   @option bool 'show_menu' show the menu
 *   @option string 'file_name' becomes the body id with _page appended
 * @return string HTML header for page
 */
function get_header($meta = null) {
	if ( ! defined('GAME_NAME')) {
		define('GAME_NAME', 'Game');
	}

	$title = ( ! empty($meta['title'])) ? GAME_NAME.' :: '.$meta['title'] : GAME_NAME;
	$show_menu = $meta['show_menu'] ?? true;
	$show_nav_links = $meta['show_nav_links'] ?? true;
	$menu_data = $meta['menu_data'] ?? false;
	$head_data = $meta['head_data'] ?? '';
	$file_name = $meta['file_name'] ?? basename($_SERVER['SCRIPT_NAME']);
	$file_name = substr($file_name, 0, strrpos($file_name, '.'));

	// make sure we have these
	$GLOBALS['_&_DEBUG_QUERY'] = (isset($GLOBALS['_&_DEBUG_QUERY'])) ? $GLOBALS['_&_DEBUG_QUERY'] : '';
	$GLOBALS['_?_DEBUG_QUERY'] = (isset($GLOBALS['_?_DEBUG_QUERY'])) ? $GLOBALS['_?_DEBUG_QUERY'] : '';

	$flash = '';
	if (class_exists('Flash')) {
		$flash = Flash::retrieve( );
	}

	if ($show_menu) {
		if ( ! $menu_data) {
			$menu_data = [
				'my_turn' => 0,
				'my_games' => 0,
				'games' => 0,
				'new_msgs' => 0,
				'msgs' => 0,
			];

			list($menu_data['games'], , $menu_data['my_games'], $menu_data['my_turn']) = Game::get_count($_SESSION['player_id']);
			$menu_data['archive'] = Archive::get_count( );

			$messages = Message::get_count($_SESSION['player_id']);
			$menu_data['msgs'] = (int) $messages[0] ?? 0;
			$menu_data['new_msgs'] = (int) $messages[1] ?? 0;

			$allow_blink = ('index.php' == basename($_SERVER['PHP_SELF']));
		}

		// highlight the important menu values
		foreach ($menu_data as $key => $value) {
			switch ($key) {
				case 'my_turn' :
				case 'new_msgs' :
					if (0 < $value) {
						$menu_data[$key] = '<span class="notice">'.$value.'</span>';
					}
					break;

				default :
					// do nothing
					break;
			}
		}

		$game_data = (isset($meta['game_data'])) ? $meta['game_data'] : Game::get_my_list($_SESSION['player_id']);
	}

	// if we are admin logged in as someone else, let us know
	$admin_css = $admin_div = '';
	if (isset($_SESSION['admin_id']) && isset($_SESSION['player_id']) && ($_SESSION['player_id'] != $_SESSION['admin_id'])) {
		$admin_css = '
			<style>
				html { border: 5px solid red; }
				#admin_username {
					background: red;
					color: black;
					position: fixed;
					top: 0;
					left: 50%;
					z-index: 99999;
					width: 200px;
					margin-left: -100px;
					text-align: center;
					font-weight: bold;
					font-size: larger;
					padding: 3px;
				}
			</style>';
		$admin_div = '<div id="admin_username">'.$GLOBALS['Player']->username.' [ '.$GLOBALS['Player']->id.' ]</div>';
	}

	$query_strings = 'var debug_query_ = "'.$GLOBALS['_&_DEBUG_QUERY'].'"; var debug_query = "'.$GLOBALS['_?_DEBUG_QUERY'].'";';
	$debug_string = (defined('DEBUG') && DEBUG) ? 'var debug = true;' : 'var debug = false;';

	$nav_links = '';
	if ($show_nav_links && class_exists('Settings') && Settings::test( )) {
		$nav_links = Settings::read('nav_links');
	}

	$GAME_NAME = GAME_NAME;

	$html = <<< EOF
<!DOCTYPE html>
<html lang="en-us">
<head>

	<title>{$title}</title>

	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

	<script>
		{$debug_string}
		{$query_strings}
	</script>

	<script src="//code.jquery.com/jquery-1.11.2.js"></script>
	<script src="//code.jquery.com/jquery-migrate-1.2.1.js"></script>
	<script src="scripts/jquery.tablesorter.js"></script>

	<!-- fancybox -->
	<link rel="stylesheet" type="text/css" media="screen" href="scripts/jquery.fancybox/jquery.fancybox.css" />
	<script src="scripts/jquery.fancybox/jquery.fancybox.js"></script>
	<script>
		$(document).ready( function( ) {
			// set fancybox defaults
//			$.fn.fancybox.defaults['overlayColor'] = '#000';

			$('a.help').fancybox({
				type: 'ajax',
				autoSize: false,
				width: 500,
				padding: 10
			});
		});
	</script>
	<!-- hide the fancybox titles -->
	<style>
		#fancy_title { display: none !important; }
	</style>

	{$head_data}
	{$flash}

	<link rel="stylesheet" type="text/css" media="screen" href="css/layout.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="css/c_{$GLOBALS['_DEFAULT_COLOR']}.css" />

	{$admin_css}

</head>

<body id="{$file_name}_page">
	{$admin_div}

	<div id="links">{$nav_links}</div>
	<h1><a href="index.php">{$GAME_NAME}</a></h1>
	<div id="wrapper">
EOF;

	if ($show_menu) {
		$html .= '
		<div id="menuholder">';

		if ($menu_data) {
			$html .= '
		<div id="menu">
			<ul>
				<li'.get_active('index').'><a href="index.php'.$GLOBALS['_?_DEBUG_QUERY'].'" title="(Your Turn | Your Games | Total Games)"'.(($allow_blink && $menu_data['my_turn']) ? ' class="blink"' : '').'>Games ('.$menu_data['my_turn'].'|'.$menu_data['my_games'].'|'.$menu_data['games'].')</a></li>
				<li'.get_active('create').'><a href="create.php'.$GLOBALS['_?_DEBUG_QUERY'].'">Create Game</a></li>
				<li'.get_active('faq').'><a href="faq.php'.$GLOBALS['_?_DEBUG_QUERY'].'">Frequently Asked Questions</a></li>
				<li'.get_active('archive').'><a href="archive.php'.$GLOBALS['_?_DEBUG_QUERY'].'" title="(Total Games)">Game Archive ('.$menu_data['archive'].')</a></li>
				<li'.get_active('messages').'><a href="messages.php'.$GLOBALS['_?_DEBUG_QUERY'].'" title="(New Messages | Total Messages)"'.(($allow_blink && $menu_data['new_msgs']) ? ' class="blink"' : '').'>Messages ('.$menu_data['new_msgs'].'|'.$menu_data['msgs'].')</a></li>
				<li'.get_active('stats').'><a href="stats.php'.$GLOBALS['_?_DEBUG_QUERY'].'">Statistics</a></li>
				<li'.get_active('prefs').'><a href="prefs.php'.$GLOBALS['_?_DEBUG_QUERY'].'">Preferences</a></li>
				<li'.get_active('profile').'><a href="profile.php'.$GLOBALS['_?_DEBUG_QUERY'].'">Profile</a></li>
				';

				if (true == $GLOBALS['Player']->is_admin) {
					$html .= '<li'.get_active('admin').'><a href="admin.php'.$GLOBALS['_?_DEBUG_QUERY'].'">Admin</a></li>';
				}

			$html .= '
				<li><a href="login.php'.$GLOBALS['_?_DEBUG_QUERY'].'">Logout</a></li>
			</ul>
		</div>';
		}

		if ($game_data) {
			$html .= '
		<div id="mygames_title"><strong>My Games</strong></div>
		<div id="mygames">
			<ul>';

			foreach ($game_data as $game) {

				$html .= '
				<li class="'.strtolower($game['my_state']).'"><a href="game.php?id='.$game['game_id'].$GLOBALS['_&_DEBUG_QUERY'].'">'.htmlentities($game['name'], ENT_QUOTES, 'UTF-8', false).'</a></li>';
			}

			$html .= '
			</ul>
		</div>';
		}

		$html .= '
		</div>';
	}

	return $html;
}


/** function get_footer
 *		Generate the HTML footer portion of the page
 *
 * @param array option meta info
 * @return string HTML footer for page
 */
function get_footer($meta = []) {
	$foot_data = isset($meta['foot_data']) ? $meta['foot_data'] : '';

	$players = GamePlayer::get_count( );
	list($cur_games, $total_games) = Game::get_count( );

	$Mysql = Mysql::get_instance( );

	$html = '
		<div id="footerspacer">&nbsp;</div>
		<div id="footer">
			<span>Total Players - '.$players.'</span>
			<span>Active Games - '.$cur_games.'</span>
			<span>Games Played - '.$total_games.'</span>
		</div>
	</div>

	'.$foot_data.'

	<!-- Queries = '.$Mysql->query_count.' -->
</body>
</html>';

	return $html;
}


/** function get_item
 *		Generate the HTML content portion of the page
 *
 * @param string contents
 * @param string instructions for page
 * @param string [optional] title for page
 * @return string HTML content for page
 */
function get_item($contents, $hint, $title = '', $extra_html = '') {
	$hint_html = "\n\t\t\t<p><strong>Welcome";
	if ( ! empty($GLOBALS['Player']) && ! empty($_SESSION['player_id'])) {
		$hint_html .= ", {$GLOBALS['Player']->username}";
	}
	$hint_html .= '</strong></p>';

	if (is_array($hint)) {
		foreach ($hint as $line) {
			$hint_html .= "\n\t\t\t<p>{$line}</p>";
		}
	}
	else {
		$hint_html .= "\n\t\t\t<p>{$hint}</p>";
	}

	if ('' != $title) {
		$title = '<h2>'.$title.'</h2>';
	}

	$long_date = (class_exists('Settings') && Settings::test( )) ? Settings::read('long_date') : 'M j, Y g:i a';

	$html = '
		<div id="sidebar">
			<div id="notes">
				<div id="date">'.ldate($long_date).'</div>
				'.$hint_html.'
			</div>
			'.$extra_html.'
		</div>
		<div id="content">
			'.$title.'
			'.$contents.'
		</div>
	';

	return $html;
}


/** function get_active
 *		Returns an active class string based on
 *		our current location
 *
 * @param string link URL to test against
 * @return string HTML active class attribute (or empty string)
 */
function get_active( ) {
	$values = func_get_args( );

	$self = substr(basename($_SERVER['SCRIPT_NAME']), 0, -4);

	foreach ($values as $value) {
		if ($value == $self) {
			return ' class="active"';
		}
	}

	return '';
}

