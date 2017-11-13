<?php
include '../vendor/autoload.php';

use Hhxsv5\RMA\AnalyzeRedis;

$analyze = new AnalyzeRedis('127.0.0.1', 6379, '123456');

//Scan the keys which can be split by '#' '*' '|'
$analyze->start(['#', '*', '|']);

//Find the csv file in default target folder: ./reports
$analyze->saveReport();