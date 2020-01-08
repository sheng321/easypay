<?php
namespace redis;

class StringModel extends RedisModel
{
    public $key = 'redisun:{id}:string:{name}';
    protected $type = 'string';
    protected $sortable = true;

    public $database = 11;

    public function __construct($database = null)
    {
        if(!empty($database)) $this->database = $database;
        parent::__construct();

    }


    protected function compare($a, $b)
    {
        if ($a > $b) {
            return 1;
        } elseif ($a < $b) {
            return -1;
        } else {
            return 0;
        }
    }
}