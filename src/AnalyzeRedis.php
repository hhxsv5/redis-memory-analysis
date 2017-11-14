<?php

namespace Hhxsv5\RMA;


use Predis\Client;

class AnalyzeRedis
{
    private $host;

    private $port;
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var Client
     */
    private $predis;

    /**
     * @var array
     */
    private $report = [];

    public function __construct($host, $port, $password = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->redis = new \Redis();
        $this->redis->connect($host, $port);
        if ($password) {
            $this->redis->auth($password);
        }

        //For command: debug object <key>
        $this->predis = new Client([
            'scheme' => 'tcp',
            'host'   => $host,
            'port'   => $port,
        ], [
            'parameters' => [
                'password' => $password,
            ],
        ]);
    }

    public function getDatabases()
    {
        $keyspace = $this->redis->info('keyspace');
        $databases = [];
        foreach ($keyspace as $db => $space) {
            $databases[(int)substr($db, 2)] = $space;
        }
        return $databases;
    }

    public function start(array $delimiters = [':', '#'], $limit = 1000)
    {
        $databases = $this->getDatabases();

        foreach ($databases as $db => $keyspace) {
            $this->redis->select($db);
            $this->predis->select($db);

            $this->report[$db] = [];

            $it = null;
            do {
                $keys = $this->redis->scan($it, '*[' . implode('', $delimiters) . ']*', $limit);
                if ($keys) {
                    foreach ($keys as $key) {
                        $sortDelimiters = [];
                        foreach ($delimiters as $delimiter) {
                            $pos = strpos($key, $delimiter);
                            if ($pos !== false) {
                                $sortDelimiters[$pos] = $delimiter;
                            }
                        }
                        ksort($sortDelimiters, SORT_NUMERIC);

                        $countKey = null;
                        foreach ($sortDelimiters as $delimiter) {
                            $pieces = explode($delimiter, $key);
                            $countKey = $pieces[0] . $delimiter . '*';
                            break;
                        }

                        if (!$countKey) {
                            continue;
                        }

                        if (!isset($this->report[$db][$countKey])) {
                            $this->report[$db][$countKey] = [
                                'count'       => 0,//total count
                                'size'        => 0,//total size
                                'neverExpire' => 0,//the count of never expired keys
                                'avgTtl'      => 0,//the average ttl of the going to be expired keys
                            ];
                        }

                        $ttl = $this->redis->ttl($key);
                        if ($ttl) {
                            if ($ttl != -2) {//-2: expired or not exist
                                if ($ttl == -1) {//-1: never expire
                                    ++$this->report[$db][$countKey]['neverExpire'];
                                } else {
                                    if ($this->report[$db][$countKey]['count'] > 0) {
                                        $avgCount = $this->report[$db][$countKey]['count'] - $this->report[$db][$countKey]['neverExpire'];
                                        $totalTtl = $this->report[$db][$countKey]['avgTtl'] * $avgCount + $ttl;
                                        $this->report[$db][$countKey]['avgTtl'] = $totalTtl / ($avgCount + 1);
                                    } else {
                                        $this->report[$db][$countKey]['avgTtl'] = $ttl;
                                    }
                                }

                                ++$this->report[$db][$countKey]['count'];
                                //$debug = $this->redis->rawCommand('debug object', $key);//phpredis does not support this command
                                $debug = $this->predis->executeRaw(['debug', 'object', $key]);
                                if ($debug) {
                                    $debug = explode(' ', $debug);
                                    $lens = explode(':', $debug[4]);
                                    $this->report[$db][$countKey]['size'] += $lens[1];//approximate memory usage by serializedlength
                                }
                            }
                        }
                    }
                    usleep(50);
                }
            } while ($it > 0);

            uasort($this->report[$db], function ($a, $b) {
                if ($a['size'] > $b['size']) {
                    return -1;
                } elseif ($a['size'] < $b['size']) {
                    return 1;
                } else {
                    if ($a['count'] > $b['count']) {
                        return -1;
                    } elseif ($a['count'] < $b['count']) {
                        return 1;
                    } else {
                        return 0;
                    }
                }
            });
        }
    }

    public function getReport()
    {
        return $this->report;
    }


    public function saveReport($folder = null)
    {
        $folder = $folder ? (rtrim($folder, '/') . '/') : './reports';

        if (!is_dir($folder)) {
            if (!mkdir($folder, 0777, true)) {
                throw new \Exception('mkdir failed: ' . $folder);
            }
        }
        foreach ($this->report as $db => $report) {
            $filename = sprintf('redis-analysis-%s-%d-%d.csv', $this->host, $this->port, $db);
            $filename = $folder . '/' . $filename;

            $fp = fopen($filename, 'w');
            fwrite($fp, 'Key,Count,Size,NeverExpire,AvgTtl(excluded never expire)' . PHP_EOL);
            foreach ($report as $key => $keyStat) {
                $humanSize = $this->toHumanSize($keyStat['size']);
                fwrite($fp, sprintf('%s,%d,%s,%d,%s',
                    $key,
                    $keyStat['count'],
                    implode(' ', $humanSize),
                    round($keyStat['neverExpire']),
                    round($keyStat['avgTtl']) . PHP_EOL
                ));
            }
            fclose($fp);
        }
    }

    protected function toHumanSize($bytes)
    {
        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
        for ($i = 0; $bytes >= 1024; $i++) {
            $bytes /= 1024;
        }
        return [round($bytes, 3), $units[$i]];
    }
}