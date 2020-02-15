<?php

namespace app\common\model;

use app\common\service\AdminService;

class SysConfig extends AdminService {

    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_system_config';


    /**
     * redis (复制的时候不要少数组参数)
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> false,
        'ttl'=> 3360 ,
        'key'=> "String:table:SysConfig:id:{id}:name:{name}",
        'keyArr'=> ['id','name'],
    ];


    /**
     * 获取配置信息
     * @return array
     */
   static public function getSysConfig() {

       \think\facade\Cache::remember('SysInfo', function () {
           $config1 = self::where('group', 'basic')->column('name,value');
           $config2 = self::where('group', 'admin')->column('name,value');
           $config = array_merge($config1,$config2);
           \think\facade\Cache::tag('SysConfig')->set('SysInfo',$config,3600);
           return $config;
       },3600);

       return \think\facade\Cache::get('SysInfo');
    }

    /**
     * 获取配置信息
     * @return array
     */
    static public function getBicConfig() {

        \think\facade\Cache::remember('BicInfo', function () {
            $config = self::where('group', 'basic')->column('name,value');
            \think\facade\Cache::tag('SysConfig')->set('BicInfo',$config,3600);
            return $config;
        },3600);
        return \think\facade\Cache::get('BicInfo');
    }




    /**
     * 获取配置信息
     * @return array
     */
    static public function getUserConfig() {

        \think\facade\Cache::remember('UserInfo', function () {
            $config1 = self::where('group', 'basic')->column('name,value');
            $config2 = self::where('group', 'user')->column('name,value');
            $config = array_merge($config1,$config2);
            \think\facade\Cache::tag('SysConfig')->set('UserInfo',$config,3600);
            return $config;
        },3600);
        return \think\facade\Cache::get('UserInfo');
    }

    static public function getAgentConfig() {
        \think\facade\Cache::remember('AgentInfo', function () {
            $config1 = self::where('group', 'basic')->column('name,value');
            $config2 = self::where('group', 'agent')->column('name,value');
            $config = array_merge($config1,$config2);
            \think\facade\Cache::tag('SysConfig')->set('AgentInfo',$config,3600);
            return $config;
        },3600);
        return \think\facade\Cache::get('AgentInfo');
    }



    /**
     * 获取系统邮件配置信息
     */
    static  public function getMailConfig() {
        $mail = self::where('group', 'mail')->column('name,value');
        return $mail;
    }

    /**
     * 获取系统配置列表
     * @param int   $page
     * @param int   $limit
     * @param array $search
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function configList($page = 1, $limit = 500, $search = [], $where = []) {

        //搜索条件
        foreach ($search as $key => $value) {
            !empty($value) && $where[] = [$key, 'LIKE', '%' . $value . '%'];
        }

        $field = 'id, group , name, value, remark, sort, create_at';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->order(['group asc','sort asc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';
        $info = [
            'limit'        => $limit,
            'page_current' => $page,
            'page_sum'     => ceil($count / $limit),
        ];
        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => $count,
            'info'  => $info,
            'data'  => $data,
        ];
        return $list;
    }

}