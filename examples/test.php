<?php
include '../vendor/autoload.php';

use Hhxsv5\RMA\AnalyzeRedis;

$analyze = new AnalyzeRedis('127.0.0.1', 6379);

//Scan the keys which can be split by '#' '*' '|'
$analyze->start(['#', '*', '|']);

//Find the csv file in default target folder: ./reports
//CSV file name format: redis-analysis-{host}-{port}-{db}.csv
//The keys order by count desc
$analyze->saveReport();