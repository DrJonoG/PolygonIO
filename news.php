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

function insertNews($mysqli, $symbolList, $headline, $summary, $source, $link, $date) {
    if(count($symbolList) > 0)
    {
        # Ignore news without content
        if($summary == 'None' AND $headline == 'None') { return; }
        # Check if post already insert
        $result = $mysqli->query("SELECT * FROM `news` WHERE `title` = '".$headline."' OR `link` = '".$link."'");
        if ($result) {
            if (mysqli_num_rows($result) < 1) {
                $insert = "INSERT INTO `news` (`title`,`summary`,`source`,`link`,`date`)
                    VALUES ('".$headline."','".$summary."','".$source."','".$link."', '". $date ."')";
                if(!$mysqli->query($insert)) {
                    printf("Error message (news): %s\n", $mysqli->error);
                }
                $id = $mysqli->insert_id;
                # Add link in news table
                foreach($symbolList as $sym) //loop over values
                {
                    $symbolQuery = "INSERT INTO `news_link` (`news_id`,`symbol_id`) SELECT ".$id.", `id` FROM `symbols`  WHERE `symbols`.Symbol LIKE '".$sym."'";
                    if(!$mysqli->query($symbolQuery)) {
                        printf("Error message (news_link): %s\n", $mysqli->error);
                    }
                }

            }
        } else {
            printf("Error message: %s\n", $mysqli->error);
        }
    }
}

function polygonNews($mysqli, $apiKey) {
	$link = "https://api.polygon.io/v2/reference/news?order=desc&limit=500&sort=published_utc&apiKey=" . $apiKey;
	$news = json_decode(file_get_contents($link), true);

	foreach($news['results'] as $article) {
        # Replace any non alphanumeric characters before inserting
		$headline = $article['title'];
		$headline = preg_replace("/[^A-Za-z0-9 ]/", '', $headline);

        # Get the description
		if(empty($article['description'])) {
			$summary = 'None';
		} else {
			$summary = $article['description'];
			$summary = preg_replace("/[^A-Za-z0-9 ]/", '', $summary);
		}

        # Publisher
		$source = $article['publisher']['name'];

        # Filter URL
		$link = $article['article_url'];
		$link = filter_var($link, FILTER_SANITIZE_URL);

		if (filter_var($link, FILTER_VALIDATE_URL) == false) {
			continue;
		}

		# Format date
		$date = new DateTime($article['published_utc'], new DateTimeZone('UTC'));
		$date->setTimezone(new DateTimeZone('America/New_York'));
		$date = $date->format('Y-m-d H:i:s');

		# Check if date is in the future, then error with post content:
		$currentTime = (new DateTime('America/New_York'))->format('Y-m-d H:i:s');
		if($date > $currentTime ) {
			continue;
		}

		# Insert
        insertNews($mysqli, $article['tickers'], $headline, $summary, 'PolygonIO', $link, $date);
	}
}


# Load configuration
$config = json_decode(include(dirname(__FILE__) . '/conf/config.php'));
# Include databse connection
require_once(dirname(__FILE__) .'/conf/db_connection.php');
# Connect to database
$mysqli = OpenCon();
# Fetch news from polygon
polygonNews($mysqli, $config->apiKey);
# Close connection
CloseCon($mysqli);

?>
