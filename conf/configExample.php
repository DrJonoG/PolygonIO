<?php
header('Content-type: application/json');
/*********

IMPORTANT: move outside of public directory, rename to config.php

**********/


$array = array(
    'host' => 'localhost',
    'user' => 'root',
    'pass' => 'password',
    'db' => 'stocks',
    'apiKey' => '',
);

return json_encode($array);

?>
