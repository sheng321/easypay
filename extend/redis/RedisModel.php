<?php


namespace redis;

use Limen\Redisun\Model;

/**
 * Class BaseModel
 * @package Limen\Redisun\Examples
 *
 * @author LI Mengxiang <limengxiang876@gmail.com>
 */
class RedisModel extends Model
{
    static protected $instance;

    //默认过期时间
    public $ttl = 120;

    public $database = 0;


    protected  function redisConfig($parameters){

        if (!isset($parameters['host'])) {
            $parameters['host'] = \think\facade\Config::get('redis.host');
        }

        if (!isset($parameters['port'])) {
            $parameters['port'] = \think\facade\Config::get('redis.port');
        }

        if (!isset($parameters['password'])) {
            $parameters['password'] = \think\facade\Config::get('redis.password');
        }

        if ($this->database > 0) {
            $parameters['database'] = $this->database?$this->database:\think\facade\Config::get('redis.database');
        }

        return  $parameters;
    }


    protected function initRedisClient($parameters, $options)
    {
        $parameters =  $this->redisConfig($parameters);

        parent::initRedisClient($parameters, $options);
    }

    public static function instance($parameters=[], $options=[])
    {
        $parameters = (new  static())->redisConfig($parameters);

        if(!(self::$instance instanceof self)){
            self::$instance = new \Predis\Client($parameters, $options);
        }
        return self::$instance;
    }


    //不能直接删除redis,统一设重置过期时间
    public static function clearTime($match = '',$cursor = 0, $count = 500 ){

        $pattern_arr['COUNT'] = $count;
        if($match){
            $pattern_arr['MATCH'] = $match;
        }
        self::instance()->scan($cursor, $pattern_arr);

        $cursor = '0';
        while($cursor !== 0){
            $info = self::instance()->scan($cursor, $pattern_arr);
            $cursor = intval($info[0]);
            $list = !empty($info[1])?$info[1]:[];

            foreach($list as $field=>$v){
                self::instance()->expire($v, 5);  //设置有效期为 秒
            }
        }

    }


    public function create($id, $value, $ttl = null, $exists = null)
    {
        if($ttl == null) $ttl = $this->ttl;
        parent::create($id, $value, $ttl, $exists);
    }

    public function createNotExists($id, $value, $ttl = null)
    {
        if($ttl == null) $ttl = $this->ttl;
        parent::createNotExists($id, $value, $ttl);
    }
    public function createExists($id, $value, $ttl = null)
    {
        if($ttl == null) $ttl = $this->ttl;
        parent::create($id, $value, $ttl);
    }
    public function insert(array $bindings, $value, $ttl = null, $exists = null)
    {
        if($ttl == null) $ttl = $this->ttl;
        parent::insert($bindings, $value, $ttl, $exists);
    }


    public function insertExists(array $bindings, $value, $ttl = null)
    {
        if($ttl == null) $ttl = $this->ttl;
        parent::insertExists($bindings, $value, $ttl);
    }

    public function insertNotExists(array $bindings, $value, $ttl = null)
    {
        if($ttl == null) $ttl = $this->ttl;
        parent::insertNotExists( $bindings, $value, $ttl);
    }
    public function update($value, $ttl = null)
    {
        if($ttl == null) $ttl = $this->ttl;
        parent::update($value, $ttl);
    }

    public function updateBatch(array $ids, $value, $ttl = null)
    {
        if($ttl == null) $ttl = $this->ttl;
        parent::updateBatch($ids, $value, $ttl);
    }

    public function getAndSet($value, $ttl = null)
    {
        if($ttl == null) $ttl = $this->ttl;
        parent::getAndSet($value, $ttl);
    }
    protected function insertProxy($key, $value, $ttl = null, $exists = null)
    {
        if($ttl == null) $ttl = $this->ttl;
        parent::insertProxy($key, $value, $ttl, $exists);
    }
    protected function updateBatchProxy($keys, $value, $ttl = null)
    {
        if($ttl == null) $ttl = $this->ttl;
        parent::updateBatchProxy($keys, $value, $ttl);
    }
}