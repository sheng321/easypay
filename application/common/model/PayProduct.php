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
        'key'=> "String:table:PayProduct:id:{id}:title:{title}:code:{code}",
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
            $data = self::column('id,title');
            \think\facade\Cache::tag('PayProduct')->set('productIdArr',$data,3600);
            return \think\facade\Cache::get('productIdArr');
        });

        return \think\facade\Cache::get('productIdArr');
    }


    /**
     * ID与支付编码数组
     * @param array $modules
     */
    public static function idCode() {

        \think\facade\Cache::remember('idCode', function () {
            $data = self::column('id,code');
            \think\facade\Cache::tag('PayProduct')->set('idCode',$data,3600);
            return \think\facade\Cache::get('idCode');
        });

        return \think\facade\Cache::get('idCode');
    }


    /**
     * ID与支付编码数组
     * @param array $modules
     */
    public static function idRate() {
        \think\facade\Cache::remember('idRate', function () {
            $data = self::column('id,status,p_rate','id');
            \think\facade\Cache::tag('PayProduct')->set('idRate',$data,3600);
            return \think\facade\Cache::get('idRate');
        });
        return \think\facade\Cache::get('idRate');
    }

}