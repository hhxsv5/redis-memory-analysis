Redis memory analysis
======

Analyzing memory of redis is to find the keys which used a lot of memory.

## Requirements

* PHP 5.4 or later
* ext-redis >=2.8.0
* predis/predis ~1.1.0

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
