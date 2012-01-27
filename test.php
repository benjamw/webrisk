<pre><?php

require_once 'classes/mysql.class.php';
require_once 'classes/settings.class.php';

var_dump(class_exists('Settings') && Settings::test( ));

?></pre>