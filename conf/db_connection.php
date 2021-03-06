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

function OpenCon() {
    # Include config
    $config = json_decode(include(dirname(__FILE__) .'/config.php'));

    //create connection
    $mysqli = new mysqli($config->host, $config->user, $config->pass, $config->db) or die("Connect failed: %s\n". $conn -> error);

    return $mysqli;
}

function CloseCon($conn) {
    $conn -> close();
}

?>
