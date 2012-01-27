ALTER TABLE wr_game_player
  CHANGE occupy_info occupy_info TEXT DEFAULT NULL ;

UPDATE wr_game_player
SET occupy_info = CONCAT('a:3:{s:6:"forced";b:', IF(forced, '1', '0'), ';s:8:"get_card";b:', IF(get_card, '1', '0'), ';s:6:"occupy";', IF(occupy_info IS NULL, 'N', CONCAT('s:', CHAR_LENGTH(occupy_info), ':"', occupy_info, '"')), ';}') ;

UPDATE wr_game_player
SET occupy_info = NULL
WHERE occupy_info = 'a:3:{s:6:"forced";b:0;s:8:"get_card";b:0;s:6:"occupy";N;}' ;

ALTER TABLE wr_game_player
  CHANGE occupy_info extra_info TEXT DEFAULT NULL ;

ALTER TABLE wr_game
  ADD extra_info TEXT DEFAULT NULL AFTER `state` ,
  CHANGE game_type game_type VARCHAR( 255 ) DEFAULT 'Original' ;

UPDATE wr_game
SET extra_info = CONCAT('a:4:{s:7:"fortify";b:1;s:16:"multiple_fortify";b:', IF(multiple_fortify, '1', '0'), ';s:17:"connected_fortify";b:', IF(connected_fortify, '1', '0'), ';s:20:"place_initial_armies";b:', IF(place_initial_armies, '1', '0'), ';}') ;

UPDATE wr_game
SET extra_info = NULL
WHERE extra_info = 'a:4:{s:7:"fortify";b:1;s:16:"multiple_fortify";b:0;s:17:"connected_fortify";b:0;s:20:"place_initial_armies";b:0;}' ;

ALTER TABLE `wr_game`
  DROP `multiple_fortify`,
  DROP `connected_fortify`,
  DROP `place_initial_armies`;
