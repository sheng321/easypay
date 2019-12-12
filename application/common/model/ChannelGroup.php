<?php

namespace app\common\model;

use app\common\service\ModelService;

/**
 * 支付通道分组
 */
class ChannelGroup extends ModelService {


    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_channel_group';


    /**
     * redis
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 3360 ,
        'key'=> "String:table:ChannelGroup:id:{id}:title:{title}",
        'keyArr'=> ['id','title'],
    ];

    /**
     * 获取列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function aList($page = 1, $limit = 10, $search = []) {
        $where = [];

        //搜索条件
        $searchField['eq'] = ['status'];
        $searchField['like'] = ['remark','title'];

        $where = search($search,$searchField,$where);


        $field = ['id','update_at','remark','title','status','sort','verson','p_id','mode'];

        $count = $this->where($where)->count();

        $data = $this->where($where)->field($field)->page($page, $limit)->order(['sort'=>'desc'])->select()->toArray();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';

        //产品列表
        $product = \app\common\model\PayProduct::idArr();



        //统计通道单笔限额
        $res = model('\app\common\model\Channel')->where([['pid','>',0],
            ['status','=',1]])->field(['id','min_amount','max_amount','f_amount'])->select()->toArray();

        $tempArr = [];
        if(!empty($res)) $tempArr =(array) array_column($res, null, 'id');


        foreach ($data as $k => $v){
            //接口模式
            $mode =  json_decode($v['mode'],true);
            if(!is_array($mode) || empty($mode)){
                $data[$k]['mode'] = 0;
            }else{
                $data[$k]['mode'] = count($mode);

                $data[$k]['min_amount'] = 0;
                $data[$k]['max_amount'] = 0;
                $data[$k]['f_amount'] = '';
                $data[$k]['ex_amount'] = '';

                foreach ($mode as $k1=>$v1){
                    if(!empty($tempArr[$v1]['min_amount']))  $data[$k]['min_amount'] = min($tempArr[$v1]['min_amount'],$data[$k]['min_amount']);
                    if(!empty($tempArr[$v1]['max_amount']))  $data[$k]['max_amount'] = max($tempArr[$v1]['max_amount'],$data[$k]['max_amount']);
                    if(!empty($tempArr[$v1]['f_amount']))  $data[$k]['f_amount'] =  $data[$k]['f_amount'].'|'.$tempArr[$v1]['f_amount'];
                }

                if(!empty($data[$k]['f_amount'])){
                    $filter = array_filter(explode('|',$data[$k]['f_amount']));
                    if(!empty($filter))  $data[$k]['f_amount'] = implode('|',array_diff($filter));

                }
            }
            $data[$k]['product'] = $product[$v['p_id']];
        }



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
            \think\facade\Cache::tag('ChannelGroup')->set('productIdArr',$data,3600);
            return \think\facade\Cache::get('productIdArr');
        });

        return \think\facade\Cache::get('productIdArr');
    }

















}