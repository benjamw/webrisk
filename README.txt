WebRisk v0.9.9
2009-06-23

Benjam Welker (http://iohelix.net)

INSTALLATION
----------------------------------
Copy the /includes/config.php.sample file and rename to /inludes/config.php

Edit the file to your specifications

Upload all files to your server

Run install.sql on your MySQL server (via phpMyAdmin or any other method)
This will create the tables and insert some basic settings

Register your admin account

Get into your MySQL server, and edit the account you just created in the
"players" table, and set both `is_admin` and `is_approved` to 1

That's it, you're done


UPGRADING
----------------------------------
I apologize, but there is no upgrade script, you will have to manually compare
the given install.sql file with your own tables and make any adjustments needed

Copy the /includes/config.php.sample file and rename to /inludes/config.php

Edit the file to your specifications

Delete all your old files (including the config file)

Upload all new files to your server

That's it, you're done



If you find any bugs, have any feature requests or suggestions, or have
any questions, please contact me at http://iohelix.net


