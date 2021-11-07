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

$config = json_decode(include(dirname(__FILE__) .'/conf/config.php'));
//create connection
$mysqli = new mysqli($config->host, $config->user, $config->pass) or die("Connect failed: %s\n". $conn -> error);

// Create database
if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS ". $config->db)) {
    printf("Error message: %s\n", $mysqli->error);
    return;
}

# Select database
$mysqli->select_db($config->db);

# Create symbols table
$symbolTable = "CREATE TABLE IF NOT EXISTS `symbols` (
    `id` int(11) AUTO_INCREMENT,
    `Symbol` varchar(5) DEFAULT NULL,
    `Name` varchar(109) DEFAULT NULL,
    `Description` varchar(341) DEFAULT NULL,
    `Exchange` varchar(309) DEFAULT NULL,
    `Sector` varchar(283) DEFAULT NULL,
    `Industry` varchar(262) DEFAULT NULL ,
    `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY (`Sector`),
    KEY (`Industry`)
);";

if (!$mysqli->query($symbolTable)) {
    printf("Error message: %s\n", $mysqli->error);
} else {
    print("Table `symbols` created.\n");
    # Empty table
    $mysqli->query("TRUNCATE TABLE `symbols`");
    # Popular table
    $handle = fopen(dirname(__FILE__) . "/data/symbols.csv", "r");
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            // process the line read.
            $values = "'".implode("','", explode(",", trim($mysqli->real_escape_string($line))))."'";
            $insert = "INSERT INTO `symbols`(`Symbol`, `Name`, `Description`, `Exchange`, `Sector`, `Industry`, `last_updated`) VALUES (".$values.")";
            if (!$mysqli->query($insert)) {
                printf("Error message: %s\n", $mysqli->error);
            }
        }
        fclose($handle);
        print("Table `symbols` populated.\n");
    } else {
        print("Error message: Error opening symbol.csv\n");
    }

}

# Create minute table
$minuteTable = "CREATE TABLE IF NOT EXISTS `minute`
    (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `symbol_id` int(5) NOT NULL,
      `o` float NOT NULL,
      `c` float NOT NULL,
      `h` float NOT NULL,
      `l` float NOT NULL,
      `v` int(11) NOT NULL,
      `type` int(1) NOT NULL,
      `date` datetime NOT NULL,
      PRIMARY KEY (`id`),
      KEY (`symbol_id`),
      KEY (`date`)
    )";

if (!$mysqli->query($minuteTable)) {
    printf("Error message: %s\n", $mysqli->error);
} else {
    print("Table `minute` created.\n");
}

# Create 5 minute table
$minuteXTable = "CREATE TABLE IF NOT EXISTS `minute_5`
    (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `symbol_id` int(5) NOT NULL,
      `o` float NOT NULL,
      `c` float NOT NULL,
      `h` float NOT NULL,
      `l` float NOT NULL,
      `v` int(11) NOT NULL,
      `type` int(1) NOT NULL,
      `date` datetime NOT NULL,
      PRIMARY KEY (`id`),
      KEY (`symbol_id`),
      KEY (`date`)
    )";

if (!$mysqli->query($minuteXTable)) {
    printf("Error message: %s\n", $mysqli->error);
} else {
    print("Table `minute_5` created.\n");
}


# Create latest table
$latestTable = "CREATE TABLE IF NOT EXISTS `latest`
    (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `symbol_id` int(5) NOT NULL,
      `updated` datetime NOT NULL,
      PRIMARY KEY (`id`),
      KEY (`symbol_id`)
    )";

if (!$mysqli->query($latestTable)) {
    printf("Error message: %s\n", $mysqli->error);
} else {
    print("Table `latest` created.\n");
}

# News
$newsTable = "CREATE TABLE `news` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` text NOT NULL,
    `summary` text NOT NULL,
    `source` varchar(100) NOT NULL,
    `link` varchar(255) NOT NULL,
    `date` datetime NOT NULL,
    PRIMARY KEY (`id`),
    KEY `date` (`date`)
);";

if (!$mysqli->query($newsTable)) {
    printf("Error message: %s\n", $mysqli->error);
} else {
    print("Table `news` created.\n");
}


# News link
$newsLink = "CREATE TABLE `news_link` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `news_id` int(11) NOT NULL,
    `symbol_id` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `news_id` (`news_id`),
    KEY `symbol_id` (`symbol_id`)
);";

if (!$mysqli->query($newsLink)) {
    printf("Error message: %s\n", $mysqli->error);
} else {
    print("Table `news_link` created.\n");
}

$mysqli->close();

echo "Complete"

?>
