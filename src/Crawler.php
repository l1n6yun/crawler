<?php

namespace Crawler;

use Swoole\Coroutine\Channel;
use Throwable;
use function Swoole\Coroutine\run;
use function Swoole\Coroutine\go;

class Crawler
{
    private MongoDB $client;

    public function __construct($uri, $dbName, $collectionName)
    {
        $this->client = new MongoDB($uri, $dbName, $collectionName);
    }

    public function list($threads, $function, $end = 100, $start = 1): void
    {
        $channel = new Channel($threads);
        run(function () use ($start, $end, $function, $channel) {
            for ($page = $start; $page <= $end; $page++) {
                $channel->push(1);
                go(function () use ($function, $channel, $page) {
                    $result = $function($page);
                    foreach ($result as $item) {
                        $this->client->enqueue($item);
                    }
                    $channel->pop();
                });
            }
        });
    }

    public function detail($threads, $function): void
    {
        $this->reset();
        $channel = new Channel($threads);
        run(function () use ($function, $channel) {
            $retryTimes = 3;
            while (true) {
                $doc = $this->client->dequeue();
                if (!$doc) {
                    if ($retryTimes) {
                        $retryTimes--;
                        sleep(1);
                        echo "retry $retryTimes times\n";
                        continue;
                    }
                    break;
                }
                $channel->push(1);
                go(function () use ($function, $channel, $doc) {
                    try {
                        $content = $function($doc);
                        $this->client->completeJob($doc['_id'], $content);
                    } catch (Throwable) {
                        $this->client->failJob($doc['_id']);
                    } finally {
                        $channel->pop();
                    }
                });
            }
        });
    }

    public function status(): string
    {
        return $this->client->status();
    }

    public function reset(): void
    {
        $this->client->reset();
    }
}
