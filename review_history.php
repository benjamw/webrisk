			}

            {
				$log['message'] = str_replace('">', ' attack">', $log['message']);
				$log['message'] = str_replace('nuked', '<span class="trade">nuked</span>', $log['message']);
				$log['message'] = str_replace('turned', '<span class="trade">turned</span>', $log['message']);
			}
			
			// test the data or the message and add a class to the message
			$class = '';
			switch ($log['data'][0]) {
#				case 'A' : $class = 'attack'; break;
#				case 'C' : $class = 'card'; break;
				case 'D' : $class = 'winner'; break;
				case 'E' : $class = 'killed'; break;
#				case 'F' : $class = 'fortify'; break;
				case 'I' : $class = 'init'; break;
				case 'N' : $class = 'next'; break;
#				case 'O' : $class = 'occupy'; break;
#				case 'P' : $class = 'place'; break;
				case 'Q' : $class = 'resign'; break;
#				case 'R' : $class = 'reinforce'; break;
#				case 'T' : $class = 'trade'; break;
#				case 'V' : $class = 'value'; break;
				default :
					break;
			}

			$log['class'] = $class;

			// wrap the player name in a class of the players color
			foreach ($colors as $color => $player) {
				if (false !== strpos($log['message'], $player)) {
					$log['message'] = str_replace($player, '<span class="'.substr($color, 0, 3).'">'.$player.'</span>', $log['message']);
				}
			}
		}
		unset($log); // kill the reference

		$history = get_table($table_format, $logs, $table_meta);
	}
}
catch (MyExecption $e) {
	$history = 'ERROR: '.$e->outputMessage( );
}

echo $history;


function make_class($matches) {
	return $matches[1].'<span class="'.strtolower($matches[2]).'">'.$matches[2].'</span>';
}
