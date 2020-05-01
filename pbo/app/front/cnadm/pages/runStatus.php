<?php

require_once('lib/bfocore/utils/db/class.PDOTools.php');
require_once('lib/bfocore/general/class.BookieHandler.php');


$query = 'SELECT bookie_id, MAX(date), AVG(matched_matchups) as average_matched FROM logs_parseruns WHERE date >= NOW() - INTERVAL 1 DAY GROUP BY bookie_id;';

$rows = PDOTools::findMany($query);

	echo '<b>Average matched in the last 24 hours:</b>: <br /><div style="font-size: 12px; display: inline"><tr>';
	$count = 0;
	foreach ($rows as $row)
	{
		$bookie = BookieHandler::getBookieByID($row['bookie_id']);

		echo '<td>' . $bookie->getName() . ': <div style="font-weight: bold; display: inline; color: ' . ($row['average_matched'] <= 0 ? 'red;' : 'green;') . '">' . $row['average_matched'] . '</div></td>';
		if (++$count == 4)
		{
			echo '</tr><tr>';
			$count = 0;
		}
	}
	echo '</tr></div>';


?>