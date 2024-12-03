<?php

namespace Crawler;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\InsertOneResult;
use MongoDB\Operation\FindOneAndUpdate;

class MongoDB
{
    private Collection $collection;

    public function __construct($uri, $dbName, $collectionName)
    {
        $client = new Client($uri);
        $this->collection = $client->selectCollection($dbName, $collectionName);
    }

    /**
     * 入队一个任务
     * @param $job
     * @return bool|InsertOneResult
     */
    public function enqueue($job): bool|InsertOneResult
    {
        // 检查_id是否存在
        $existingJob = $this->collection->findOne(['_id' => $job['_id']]);
        if ($existingJob === null) {
            $job['status'] = 'pending';
            $job['retry_count'] = 0;
            return $this->collection->insertOne($job);
        }
        return false;
    }

    /**
     * 出队一个任务
     * @return object|array|null
     */
    public function dequeue(): object|array|null
    {
        return $this->collection->findOneAndUpdate(
            ['status' => 'pending', 'retry_count' => ['$lt' => 3]],
            [
                '$set' => ['status' => 'processing'],
                '$currentDate' => ['updated_at' => true]
            ],
            ['returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER, 'sort' => ['updated_at' => 1]]
        );
    }

    /**
     * 完成任务
     * @param $jobId
     * @param $content
     * @return void
     */
    public function completeJob($jobId, $content): void
    {
        $this->collection->updateOne(
            ['_id' => $jobId],
            [
                '$set' => array_merge($content, ['status' => 'completed']),
                '$currentDate' => ['updated_at' => true]
            ]
        );
    }

    /**
     * 任务失败，增加重试次数
     * @param $jobId
     * @return void
     */
    public function failJob($jobId): void
    {
        $this->collection->updateOne(
            ['_id' => $jobId],
            [
                '$inc' => ['retry_count' => 1],
                '$set' => ['status' => 'pending'],
                '$currentDate' => ['updated_at' => true]
            ]
        );
    }


    /**
     * 获取任务状态
     * @return string
     */
    public function status(): string
    {
        // 查询 status pending、completed、processing 的数量
        $pending = $this->collection->countDocuments(['status' => 'pending']);
        $processing = $this->collection->countDocuments(['status' => 'processing']);
        $completed = $this->collection->countDocuments(['status' => 'completed']);
        return "pending: $pending, processing: $processing, completed: $completed" . PHP_EOL;
    }

    /**
     * 重置任务
     * @return void
     */
    public function reset(): void
    {
        $this->collection->updateMany(
            ['$or' => [['status' => 'pending', 'retry_count' => ['$gt' => 0]], ['status' => 'processing']]],
            ['$set' => ['status' => 'pending', 'retry_count' => 0]]
        );
    }

    /**
     * 删除集合
     * @return void
     */
    public function drop(): void
    {
        $this->collection->drop();
    }

    /**
     * 查询一个文档
     * @param array $array
     * @return object|array|null
     */
    public function findOne(array $array): object|array|null
    {
        return $this->collection->findOne($array);
    }
}
