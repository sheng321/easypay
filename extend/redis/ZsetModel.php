<?php
namespace redis;

class ZsetModel extends RedisModel
{
    protected $type = 'zset';

    protected $key = 'redisun:{id}:zset';
}