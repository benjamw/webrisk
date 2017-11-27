-- phpMyAdmin SQL Dump
-- version 4.4.0-dev
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 24, 2015 at 10:38 AM
-- Server version: 5.6.20-log
-- PHP Version: 5.6.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

-- --------------------------------------------------------

--
-- Table structure for table `player`
--

CREATE TABLE IF NOT EXISTS `player` (
	`player_id` int(11) unsigned NOT NULL,
	`username` varchar(20) COLLATE utf8_general_ci NOT NULL DEFAULT '',
	`first_name` varchar(20) COLLATE utf8_general_ci DEFAULT NULL,
	`last_name` varchar(20) COLLATE utf8_general_ci DEFAULT NULL,
	`email` varchar(100) COLLATE utf8_general_ci NOT NULL DEFAULT '',
	`timezone` varchar(255) COLLATE utf8_general_ci NOT NULL DEFAULT 'UTC',
	`is_admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`password` varchar(32) COLLATE utf8_general_ci NOT NULL DEFAULT '',
	`alt_pass` varchar(32) COLLATE utf8_general_ci NOT NULL DEFAULT '',
	`ident` varchar(32) COLLATE utf8_general_ci DEFAULT NULL,
	`token` varchar(32) COLLATE utf8_general_ci DEFAULT NULL,
	`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`is_approved` tinyint(1) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wr_chat`
--

DROP TABLE IF EXISTS `wr_chat`;
CREATE TABLE IF NOT EXISTS `wr_chat` (
	`chat_id` int(10) unsigned NOT NULL,
	`message` text COLLATE utf8_general_ci NOT NULL,
	`from_id` int(10) unsigned NOT NULL DEFAULT '0',
	`game_id` int(10) unsigned NOT NULL DEFAULT '0',
	`private` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wr_game`
--

DROP TABLE IF EXISTS `wr_game`;
CREATE TABLE IF NOT EXISTS `wr_game` (
	`game_id` int(11) unsigned NOT NULL,
	`host_id` int(11) unsigned NOT NULL DEFAULT '0',
	`name` varchar(255) COLLATE utf8_general_ci NOT NULL DEFAULT '',
	`password` varchar(32) COLLATE utf8_general_ci DEFAULT NULL,
	`capacity` tinyint(1) unsigned NOT NULL DEFAULT '2',
	`time_limit` tinyint(2) unsigned DEFAULT NULL,
	`allow_kibitz` tinyint(1) NOT NULL DEFAULT '0',
	`game_type` varchar(255) COLLATE utf8_general_ci DEFAULT 'Original',
	`next_bonus` tinyint(3) unsigned NOT NULL DEFAULT '4',
	`state` enum('Waiting','Placing','Playing','Finished') COLLATE utf8_general_ci NOT NULL DEFAULT 'Waiting',
	`extra_info` text COLLATE utf8_general_ci,
	`game_settings` text COLLATE utf8_general_ci DEFAULT NULL,
	`paused` tinyint(1) NOT NULL DEFAULT '0',
	`create_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	`modify_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wr_game_land`
--

DROP TABLE IF EXISTS `wr_game_land`;
CREATE TABLE IF NOT EXISTS `wr_game_land` (
	`game_id` int(10) unsigned NOT NULL DEFAULT '0',
	`land_id` int(10) unsigned NOT NULL DEFAULT '0',
	`player_id` int(10) unsigned NOT NULL DEFAULT '0',
	`armies` smallint(5) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `wr_game_log`
--

DROP TABLE IF EXISTS `wr_game_log`;
CREATE TABLE IF NOT EXISTS `wr_game_log` (
	`game_id` int(11) unsigned NOT NULL DEFAULT '0',
	`data` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
	`create_date` datetime(6) NOT NULL DEFAULT '0000-00-00 00:00:00.000000',
	`microsecond` decimal(18,8) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wr_game_nudge`
--

DROP TABLE IF EXISTS `wr_game_nudge`;
CREATE TABLE IF NOT EXISTS `wr_game_nudge` (
	`game_id` int(10) unsigned NOT NULL DEFAULT '0',
	`player_id` int(10) unsigned NOT NULL DEFAULT '0',
	`nudged` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `wr_game_player`
--

DROP TABLE IF EXISTS `wr_game_player`;
CREATE TABLE IF NOT EXISTS `wr_game_player` (
	`game_id` int(11) unsigned NOT NULL DEFAULT '0',
	`player_id` int(11) unsigned NOT NULL DEFAULT '0',
	`order_num` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`color` varchar(10) COLLATE utf8_general_ci NOT NULL DEFAULT '',
	`cards` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
	`armies` smallint(5) unsigned NOT NULL DEFAULT '0',
	`state` enum('Waiting','Trading','Placing','Attacking','Occupying','Fortifying','Resigned','Dead') COLLATE utf8_general_ci NOT NULL DEFAULT 'Waiting',
	`get_card` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`forced` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`extra_info` text COLLATE utf8_general_ci,
	`move_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wr_message`
--

DROP TABLE IF EXISTS `wr_message`;
CREATE TABLE IF NOT EXISTS `wr_message` (
	`message_id` int(10) unsigned NOT NULL,
	`subject` varchar(255) COLLATE utf8_general_ci NOT NULL DEFAULT '',
	`message` text COLLATE utf8_general_ci NOT NULL,
	`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wr_message_glue`
--

DROP TABLE IF EXISTS `wr_message_glue`;
CREATE TABLE IF NOT EXISTS `wr_message_glue` (
	`message_glue_id` int(10) unsigned NOT NULL,
	`message_id` int(10) unsigned NOT NULL DEFAULT '0',
	`from_id` int(10) unsigned NOT NULL DEFAULT '0',
	`to_id` int(10) unsigned NOT NULL DEFAULT '0',
	`send_date` datetime DEFAULT NULL,
	`expire_date` datetime DEFAULT NULL,
	`view_date` datetime DEFAULT NULL,
	`create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`deleted` tinyint(1) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wr_roll_log`
--

DROP TABLE IF EXISTS `wr_roll_log`;
CREATE TABLE IF NOT EXISTS `wr_roll_log` (
	`id` int(11) unsigned NOT NULL,
	`attack_1` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`attack_2` tinyint(1) unsigned DEFAULT NULL,
	`attack_3` tinyint(1) unsigned DEFAULT NULL,
	`defend_1` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`defend_2` tinyint(1) unsigned DEFAULT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `wr_settings`
--

DROP TABLE IF EXISTS `wr_settings`;
CREATE TABLE IF NOT EXISTS `wr_settings` (
	`setting` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
	`value` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
	`notes` text CHARACTER SET utf8 COLLATE utf8_general_ci,
	`sort` smallint(5) unsigned NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Dumping data for table `wr_settings`
--

INSERT INTO `wr_settings` (`setting`, `value`, `notes`, `sort`) VALUES
	('site_name', 'Your Site Name', 'The name of your site', 10),
	('default_color', 'c_yellow_black.css', 'The default theme color for the game pages', 20),
	('nav_links', '<!-- your links here -->', 'HTML code for your site''s navigation links to display on the game pages', 30),
	('from_email', 'your.mail@yoursite.com', 'The email address used to send game emails', 40),
	('to_email', 'you@yoursite.com', 'The email address to send admin notices to (comma separated)', 50),
	('new_users', '1', '(1/0) Allow new users to register (0 = off)', 60),
	('approve_users', '0', '(1/0) Require admin approval for new users (0 = off)', 70),
	('confirm_email', '0', '(1/0) Require email confirmation for new users (0 = off)', 80),
	('max_users', '0', 'Max users allowed to register (0 = off)', 90),
	('default_pass', 'default', 'The password to use when resetting a user''s password', 100),
	('expire_users', '45', 'Number of days until untouched user accounts are deleted (0 = off)', 110),
	('save_games', '0', '(1/0) Save games in the ''games'' directory on the server (0 = off)', 120),
	('expire_finished_games', '7', 'Number of days until finished games are deleted (0 = off)', 128),
	('expire_games', '30', 'Number of days until untouched games are deleted (0 = off)', 130),
	('nudge_flood_control', '24', 'Number of hours between nudges. (-1 = no nudging, 0 = no flood control)', 135),
	('timezone', 'UTC', 'The timezone to use for dates (<a href="http://www.php.net/manual/en/timezones.php">List of Timezones</a>)', 140),
	('long_date', 'M j, Y g:i a', 'The long format for dates (<a href="http://www.php.net/manual/en/function.date.php">Date Format Codes</a>)', 150),
	('short_date', 'Y.m.d H:i', 'The short format for dates (<a href="http://www.php.net/manual/en/function.date.php">Date Format Codes</a>)', 160),
	('debug_pass', '', 'The DEBUG password to use to set temporary DEBUG status for the script (empty = off)', 170),
	('DB_error_log', '0', '(1/0) Log database errors to the ''logs'' directory on the server (0 = off)', 180),
	('DB_error_email', '0', '(1/0) Email database errors to the admin email addresses given (0 = off)', 190);

-- --------------------------------------------------------

--
-- Table structure for table `wr_wr_player`
--

DROP TABLE IF EXISTS `wr_wr_player`;
CREATE TABLE IF NOT EXISTS `wr_wr_player` (
	`player_id` int(11) unsigned NOT NULL DEFAULT '0',
	`game_settings` text COLLATE utf8_general_ci DEFAULT NULL,
	`is_admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`allow_email` tinyint(1) unsigned NOT NULL DEFAULT '1',
	`color` varchar(25) COLLATE utf8_general_ci NOT NULL DEFAULT 'yellow_black',
	`invite_opt_out` tinyint(1) NOT NULL DEFAULT '0',
	`max_games` tinyint(3) unsigned NOT NULL DEFAULT '0',
	`wins` smallint(5) unsigned NOT NULL DEFAULT '0',
	`kills` smallint(5) unsigned NOT NULL DEFAULT '0',
	`losses` smallint(5) unsigned NOT NULL DEFAULT '0',
	`last_online` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `player`
--
ALTER TABLE `player`
	ADD PRIMARY KEY (`player_id`),
	ADD UNIQUE KEY `username` (`username`),
	ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wr_chat`
--
ALTER TABLE `wr_chat`
	ADD PRIMARY KEY (`chat_id`),
	ADD KEY `game_id` (`game_id`),
	ADD KEY `private` (`private`),
	ADD KEY `from_id` (`from_id`);

--
-- Indexes for table `wr_game`
--
ALTER TABLE `wr_game`
	ADD PRIMARY KEY (`game_id`);

--
-- Indexes for table `wr_game_land`
--
ALTER TABLE `wr_game_land`
	ADD UNIQUE KEY `game_land` (`game_id`,`land_id`);

--
-- Indexes for table `wr_game_log`
--
ALTER TABLE `wr_game_log`
	ADD UNIQUE KEY `game_id` (`game_id`,`create_date`,`microsecond`);

--
-- Indexes for table `wr_game_nudge`
--
ALTER TABLE `wr_game_nudge`
	ADD UNIQUE KEY `game_player` (`game_id`,`player_id`);

--
-- Indexes for table `wr_game_player`
--
ALTER TABLE `wr_game_player`
	ADD UNIQUE KEY `game_player` (`game_id`,`player_id`);

--
-- Indexes for table `wr_message`
--
ALTER TABLE `wr_message`
	ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `wr_message_glue`
--
ALTER TABLE `wr_message_glue`
	ADD PRIMARY KEY (`message_glue_id`),
	ADD KEY `outbox` (`from_id`,`message_id`),
	ADD KEY `created` (`create_date`),
	ADD KEY `expire_date` (`expire_date`),
	ADD KEY `inbox` (`to_id`,`from_id`,`send_date`,`deleted`),
	ADD KEY `message_id` (`message_id`,`to_id`);

--
-- Indexes for table `wr_roll_log`
--
ALTER TABLE `wr_roll_log`
	ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wr_settings`
--
ALTER TABLE `wr_settings`
	ADD UNIQUE KEY `setting` (`setting`),
	ADD KEY `sort` (`sort`);

--
-- Indexes for table `wr_wr_player`
--
ALTER TABLE `wr_wr_player`
	ADD UNIQUE KEY `id` (`player_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `player`
--
ALTER TABLE `player`
	MODIFY `player_id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `wr_chat`
--
ALTER TABLE `wr_chat`
	MODIFY `chat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `wr_game`
--
ALTER TABLE `wr_game`
	MODIFY `game_id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `wr_message`
--
ALTER TABLE `wr_message`
	MODIFY `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `wr_message_glue`
--
ALTER TABLE `wr_message_glue`
	MODIFY `message_glue_id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `wr_roll_log`
--
ALTER TABLE `wr_roll_log`
	MODIFY `id` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
