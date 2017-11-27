# WebRisk v0.9.15
Last Updated: 2017-11-25

REQUIREMENTS
----------------------------------
This script requires the [BC Math](https://secure.php.net/manual/en/book.bc.php) extension. 
If you are using PHP v7+ on linux (Ubuntu), BC Math is not automatically included with PHP, so you'll 
need to run: `sudo apt-get install php-bcmath`.

This script requires your MySQL (or equivalent) Server to be able to handle fractional seconds to 
6 decimal place precision (microseconds). This can be tested by creating a field of type `DATETIME(6)`,
and if this is successful, your server supports it. MySQL v5.6+ supports fractional seconds.

INSTALLATION
----------------------------------
- Copy the `/includes/config.php.sample` file and rename to `/includes/config.php`.
- Edit the file to your specifications.
- Upload all files to your server.
- Run `install.sql` on your MySQL server (via phpMyAdmin or any other method).
This will create the tables and insert some basic settings.
- Register your admin account.
- Get into your MySQL server, and edit the account you just created in the
`players` table, and set both `is_admin` and `is_approved` to `1`. 
- Copy the username of your admin account to the `$GLOBALS['_ROOT_ADMIN']` portion of the `/includes/config.php` file if you haven't already done so.
- That's it, you're done.


UPGRADING
----------------------------------
I apologize, but there is no upgrade script, you will have to manually compare
the given `install.sql` file with your own tables and make any adjustments needed.

- Copy the `/includes/config.php.sample` file and rename to `/includes/config.php` replacing your original file.
- Edit the file to your specifications.
- Delete all your old files (including the config file).
- Upload all new files to your server.
- That's it, you're done.

If you find any bugs, have any feature requests or suggestions, or have
any questions, please contact me at http://iohelix.net/contact, or submit them here on github.
