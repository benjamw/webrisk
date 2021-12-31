<?php

require_once 'includes/inc.global.php';

if (isset($_POST['submit'])) {
	test_token( );

	try {
		$GLOBALS['Player']->update( );

		Flash::store('Profile Updated Successfully !', false);
	}
	catch (MyException $e) {
		Flash::store('Profile Update FAILED !', false);
	}
}

$zones = timezone_identifiers_list( );

$tz_select = '<option value="">Use Game Default ('.$GLOBALS['_DEFAULT_TIMEZONE'].')</option>';
foreach ($zones as $zone) {
	$parts = explode('/', $zone); // 0 => Continent, 1 => City --OR-- 0 => Continent, 1 => Country/State, 2 => City

	// Only use "friendly" continent names (for PHP < 5.3)
	if (5 < PHP_MAJOR_VERSION || 3 <= PHP_MINOR_VERSION
		|| 'Africa' === $parts[0] || 'America' === $parts[0] || 'Antarctica' === $parts[0]
		|| 'Arctic' === $parts[0] || 'Asia' === $parts[0] || 'Atlantic' === $parts[0]
		|| 'Australia' === $parts[0] || 'Europe' === $parts[0] || 'Indian' === $parts[0]
		|| 'Pacific' === $parts[0] || 'UTC' === $parts[0]
	) {
		$tz_select .= '<option'.get_selected($GLOBALS['Player']->timezone, $zone, true).'>'.$zone.'</option>';
	}
}

$meta['title'] = 'Update Profile';
$meta['head_data'] = '
	<script>//<![CDATA[
		var profile = 1;
	//]]></script>
	<script src="scripts/register.js"></script>
';

$hints = [
	'<span class="notice">GLOBAL SETTINGS</span><br />These setting affect ALL iohelix games that also display the GLOBAL SETTINGS text.' ,
	'Here you can update your name, email address, and password.' ,
];

$contents = <<< EOF
	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv">
		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="errors" id="errors" />
		<ul>
			<li><label>Username</label><span class="input">{$GLOBALS['Player']->username}</span></li>

			<li><label for="first_name">First Name</label><input type="text" id="first_name" name="first_name" maxlength="20" value="{$GLOBALS['Player']->firstname}" tabindex="1" /></li>
			<li><label for="last_name">Last Name</label><input type="text" id="last_name" name="last_name" maxlength="20" value="{$GLOBALS['Player']->lastname}" tabindex="2" /></li>

			<li><label for="email" class="req">Email</label><input type="text" id="email" name="email" maxlength="100" value="{$GLOBALS['Player']->email}" tabindex="3" /><span id="email_check" class="test"></span></li>

			<li><label for="timezone">Timezone</label>
				<select name="timezone" tabindex="4">
					{$tz_select}
				</select>
			</li>

			<li class="info">Leave password fields blank to keep current password.</li>
			<li><label for="curpass">Current Password</label><input type="password" id="curpass" name="curpass" tabindex="4" /></li>
			<li><label for="password">New Password</label><input type="password" id="password" name="password" tabindex="5" /></li>
			<li><label for="passworda">Confirmation</label><input type="password" id="passworda" name="passworda" tabindex="6" /></li>

			<li><input type="submit" id="submit" name="submit" value="Update Profile" /></li>
		</ul>
	</div></form>

EOF;

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

