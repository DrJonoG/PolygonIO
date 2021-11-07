<?php
/**
* @author = DrJonoG  # Jonathon Gibbs
* Copyright 2016-2020 - https://www.jonathongibbs.com / @DrJonoG
* Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the
* License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
* Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an
* "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and limitations under the License.
**/

/*
Functionality for retrieving data on a minute by minute basis from polygon IO
Stores data inside database table
*/

$config = json_decode(include(dirname(__FILE__) . '/conf/config.php'));

# Fetch data from polygon
$link = "https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers?apiKey=" . $config->apiKey;
$json = json_decode(file_get_contents($link));

# Check if data returned
if(count($json->tickers) == 0) { return "Error: No data in file"; }

# Open connection to database and update
require_once(dirname(__FILE__) .'/conf/db_connection.php');
$mysqli = OpenCon();
updateDatabase($mysqli, 5);
CloseCon($mysqli);

function updateDatabase($mysqli, $unseen, $xMinute=0) {
    # Change default timezone
    date_default_timezone_set('America/New_York');
    # Get list of symbols and casereate lookup dictionary
    $symbolArr = array();
    $symbolResult = $mysqli->query("SELECT `id`, `symbol` FROM `symbols`");
    while($row = $symbolResult->fetch_assoc()) {
    	$symbolArr[$row['symbol']] = $row['id'];
    }

    # Filter and find last updated
    for($i=0; $i<count($json->tickers); $i++) {
    	$symbol = $json->tickers[$i]->ticker;
        # Check if polygon symbol exists in database of symbols, ignore if not
    	if (array_key_exists($symbol, $symbolArr)) {
    		# Convert to date and time, remove excess numbers (> 10)
            # Polygon returns the timestamp in miliseconds
    		$timestamp = substr($json->tickers[$i]->updated, 0, 10);
    		$datetime = date('Y-m-d H:i:00', $timestamp);
    		$json->tickers[$i]->updated = $datetime;

    		# Store data
    		$jsonData = $json->tickers[$i];
            $minuteData = $jsonData->min;

            # Sometimes returns zeros, skip these
            if($minuteData->o == 0) { continue; }

            # Ticker id
            $id = $symbolArr[$symbol];

            # Check to see if any new data is available, skip if not
            $query = $mysqli->query("SELECT `id`, `updated` FROM `latest` WHERE `symbol_id` = ". $id);
            $result = $query->fetch_assoc();
            if($result->num_rows() == 0) {
                # First time adding to database, insert into updated
                # We use a separate table to track updated due to the size of the minute table
                $insert = "INSERT INTO `latest` (`symbol_id`, `updated`) VALUES (".$id.", '".$jsonData->updated."')";
                if(!$mysqli->query($insert)) {
                    printf("Error message: %s\n", $mysqli->error);
                }

        		# Insert minute data
        		marketMinuteInsert($mysqli, $id, $minuteData, $type, $update);
                if($xMinute != 0) { marketXMinuteInsert($mysqli, $id, $type, $xMinute, $update); }

            }
            elseif($result['updated'] < $jsonData->updated) {
                # New data is available, and symbol already exists in market_latest
                $update = "UPDATE `latest` SET `updated` = '".$jsonData->updated."' WHERE `symbol_id` = ".$id;
                if(!$mysqli->query($update)) {
                    printf("Error message: %s\n", $mysqli->error);
                }

                # Insert minute data
        		marketMinuteInsert($mysqli, $id, $minuteData, $type, $update);
                if($xMinute != 0) { marketXMinuteInsert($mysqli, $id, $type, $xMinute, $update); }
            }
            # Else no new data
    	} else {
            # Handle data that is available, but not in your symbol list.
            # Could add to symbol list, or ignore.
        }
    }
}


function marketMinuteInsert($mysqli, $sym_id, $minuteData, $date) {
    $time = date('H:i:00', strtotime($date));
    # 0 pre-market, 1 market, 2 post-market
    $type = 0;
    if($time > '09:30:00' and $time <= '16:00:00') {
    	$type = 1;
    } elseif($time > '16:00:00' and $time <= '23:59:00') {
    	$type = 2;
    }

	$insert = "INSERT INTO `minute` (`symbol_id`, `o`, `c`, `h`, `l`, `v`, `type`, `date`)
    	VALUES (".$sym_id.", ".$minuteData->o.", ".$minuteData->c.", ".$minuteData->h.", ".$minuteData->l.", ".$minuteData->v.", ".$type.", '".$date."')";
    if(!$mysqli->query($insert)) {
        printf("Error message: %s\n", $mysqli->error);
    }
}

function marketXMinuteInsert($mysqli, $sym_id, $type, $minute, $datetime) {
	$currentMinute = date('i', strtotime($datetime));
	if($currentMinute % $minute == 0) {
        $startDT = date("Y-m-d H:i:00", strtotime($date) - ($minute*60)); # minus x minutes
		$endDT = $date;

		# Select data
		$query = "SELECT
			(SELECT `c` FROM `minute` WHERE `date` > '".$startDT."' AND `date` <= '".$endDT."' AND `symbol_id` = ".$sym_id." ORDER BY `date` DESC LIMIT 1) AS close,
			(SELECT `o` FROM `minute` WHERE `date` > '".$startDT."' AND `date` <= '".$endDT."' AND `symbol_id` = ".$sym_id." ORDER BY `date` ASC LIMIT 1) AS open,
			MIN(l) AS low,
			MAX(h) AS high,
			SUM(v) AS volume
		FROM minute
		WHERE `date` >= '".$startDT."' AND `date` < '".$endDT."' AND `symbol_id` = ".$sym_id;
		$result = $mysqli->query($query);
		if($result) {
			# Inserrt
			$data=mysqli_fetch_assoc($result);
			$insert = "INSERT INTO `minute_".$minute."` (`symbol_id`, `o`, `c`, `h`, `l`, `v`, `type`, `date`)
				VALUES (".$sym_id.", ".$data['open'].", ".$data['close'].", ".$data['high'].", ".$data['low'].", ".$data['volume'].", ".$type.", '".$startDT."')";
            # Error checking
            if(!$mysqli->query($insert)) {
                printf("Error message: %s\n", $mysqli->error);
            }
		}
    }
}


?>
