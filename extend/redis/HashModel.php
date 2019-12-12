<?php
namespace redis;

class HashModel extends RedisModel
{
    public $key = 'hash:{table}:{id}';

    public $type = 'hash';

    protected $database = 11;
}