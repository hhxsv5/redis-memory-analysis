Redis memory analysis
======

Analyzing memory of redis is to find the keys(prefix) which used a lot of memory, export the analysis result into csv file.

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

use Hhxsv5\RMA\AnalyzeRedis;

$analyze = new AnalyzeRedis('127.0.0.1', 6379, '123456');

//Scan the keys which can be split by '#' '*' '|'
$analyze->start(['#', '*', '|']);

//Find the csv file in default target folder: ./reports
//CSV file name format: redis-analysis-{host}-{port}-{db}.csv
//The keys order by count desc
$analyze->saveReport();
```

![CSV](https://raw.githubusercontent.com/hhxsv5/redis-memory-analysis/master/examples/demo.png)


## License

[MIT](https://github.com/hhxsv5/redis-memory-analysis/blob/master/LICENSE)
