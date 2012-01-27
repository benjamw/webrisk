ALTER TABLE `wr_wr_player` ADD `invite_opt_out` BOOLEAN NOT NULL DEFAULT '0' AFTER `color` ,
ADD `max_games` TINYINT UNSIGNED NOT NULL DEFAULT '0' AFTER `invite_opt_out` ;