<?php

$input = @file_get_contents("php://input");
$event_json = json_decode($input);

$file = dirname(__FILE__) . '/../tmp/quaderno/'. date('m-d-y_G:i:s') .'_log';
file_put_contents($file, date('c')."\n", FILE_APPEND);
file_put_contents($file, $_SERVER['REQUEST_URI']."\n", FILE_APPEND);
file_put_contents($file, "\n-----DATA---------\n", FILE_APPEND);
file_put_contents($file, print_r($event_json, true)."\n", FILE_APPEND);
file_put_contents($file, "\n-----REQUEST---------\n", FILE_APPEND);
file_put_contents($file, print_r($_REQUEST, true)."\n", FILE_APPEND);
file_put_contents($file, "\n-----SERVER----------\n", FILE_APPEND);
file_put_contents($file, print_r($_SERVER, true)."\n", FILE_APPEND);
