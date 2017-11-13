Redis memory analysis
======

Analyzing memory of redis is to find the big keys, then reduce memory usage.

## Requirements

* PHP 5.4 or later

## Installation via Composer([packagist](https://packagist.org/packages/hhxsv5/redis-memory-analysis))

```BASH
composer require "hhxsv5/redis-memory-analysis:~1.0" -vvv
```

## Usage
### Run demo

```PHP
include '../vendor/autoload.php';

$analyze = new \RMA\AnalyzeRedis('127.0.0.1', 6379, '123456');

//Scan the keys which can be split by '#' '*' '|'
$analyze->start(['#', '*', '|']);

//Find the csv file in default target folder: ./reports
$analyze->saveReport();
```

## License

[MIT](https://github.com/hhxsv5/redis-memory-analysis/blob/master/LICENSE)
