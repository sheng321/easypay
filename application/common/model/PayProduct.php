<?php

namespace app\common\model;

use app\common\service\ModelService;

/**
 * 支付产品
 */
class PayProduct extends ModelService {


    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_pay_product';


    /**
     * redis
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 3360 ,
        'key'=> "String:table:PayProduct:title:{title}:code:{code}:id:{id}",
        'keyArr'=> ['id','title','code'],
    ];


    /**
     * 用户分组获取列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function aList($page = 1, $limit = 10, $search = []) {
        $where = [
            ['cli','=',0]
        ];

        //搜索条件
        $searchField['like'] = ['remark','title'];

        $where = search($search,$searchField,$where);

        $field = ['id','update_at','remark','title','status','sort','verson','cli','code','p_rate'];

        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['sort'=>'desc'])->select();
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



    /**
     * 获取列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function payList($page = 1, $limit = 10, $search = []) {
        $where = [];

        //搜索条件
        $searchField['eq'] = ['status'];
        $searchField['like'] = ['remark','title'];

        $where = search($search,$searchField,$where);


        $field = ['id','update_at','remark','title','status','sort','verson','cli','code','p_rate','min_amount','max_amount','f_amount','ex_amount','f_multiple','f_num'];

        $count = $this->where($where)->count();

        $data = $this->where($where)->field($field)->page($page, $limit)->order(['sort'=>'desc'])->select();
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

    /**
     * ID与支付名称数组
     * @param array $modules
     */
    public static function idArr() {

        \think\facade\Cache::remember('productIdArr', function () {
            return self::column('id,title');
        },3600);
        \think\facade\Cache::tag('PayProduct',['productIdArr']);
        return \think\facade\Cache::get('productIdArr');
    }


    /**
     * ID与支付编码数组
     * @param array $modules
     */
    public static function idCode() {
        \think\facade\Cache::remember('idCode', function () {
            return self::column('id,code');
        },3600);
        \think\facade\Cache::tag('PayProduct',['idCode']);
        return \think\facade\Cache::get('idCode');
    }
    public static function idCode1() {
        \think\facade\Cache::remember('idCode1', function () {
            return self::column('id,code,status','code');
        },3600);
        \think\facade\Cache::tag('PayProduct',['idCode1']);
        return \think\facade\Cache::get('idCode1');
    }



    /**
     * ID与费率数组
     * @param array $modules
     */
    public static function idRate() {
        \think\facade\Cache::remember('idRate', function () {
            return self::column('id,status,p_rate','id');
        },3600);
        \think\facade\Cache::tag('PayProduct',['idRate']);
        return \think\facade\Cache::get('idRate');
    }


    /**
     * 商户端显示
     * @param array $modules
     */
    public static function codeTitle(){
        \think\facade\Cache::remember('codeTitle', function () {
            return self::where(['cli'=>0])->column('id,code,title,status','code');
        },3600);
        \think\facade\Cache::tag('PayProduct',['codeTitle']);
        return \think\facade\Cache::get('codeTitle');
    }

}