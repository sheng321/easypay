<?php
namespace redis;

class ListModel extends RedisModel
{
    protected $type = 'list';

    protected $key = 'redisun:{id}:list';
}