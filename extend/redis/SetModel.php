<?php
/**
 * @author LI Mengxiang
 * @email lmx@yiban.cn
 * @since 2017/3/29 16:14
 */

namespace redis;


class SetModel extends RedisModel
{
    protected $type = 'set';

    protected $key = 'redisun:set:{id}:members';
}