<?php

namespace app\common\model;

use app\common\service\ModelService;

/**
 * 支付通道
 */
class Channel extends ModelService {


    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_channel';


    /**
     * redis
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 3360 ,
        'key'=> "String:table:Channel:id:{id}:title:{title}",
        'keyArr'=> ['id','title'],
    ];

    /**
     * 获取列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function cList($page = 1, $limit = 10, $search = []) {
        $where = [];

        //搜索条件
        $searchField['eq'] = ['status'];
        $searchField['like'] = ['remark','title','code'];


        $where = search($search,$searchField,$where);


        $field = ['id','pid','p_id','visit','update_at','remark','title','status','sort','verson','code','c_rate','min_amount','max_amount','f_amount','ex_amount','f_multiple','f_num','limit_money'];

        $count = $this->where($where)->count();

        $data = $this->where($where)->field($field)->page($page, $limit)->order(['status'=>'desc','sort'=>'desc','update_at'=>'desc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';


        //产品列表
        $product = \app\common\model\PayProduct::idArr();
        foreach ($data as $k => $val){
           if($val['pid'] == 0){
               $data[$k]['title'] = $data[$k]['title'].'【通道】';
           }else{
              $p_id = json_decode($val['p_id'],true)[0];
               $data[$k]['title'] = $data[$k]['title']."【".$product[$p_id]."】";
           }
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
     * 获取支付产品列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function pList($page = 1, $limit = 10, $search = []) {
        $where = [
            ['pid','>',0],
            ['status','=',1],
        ];

       if(!empty($search['p_id'])) $where[] = ['p_id','=',json_encode([$search['p_id']])];

        $field = ['id','pid','p_id','visit','update_at','remark','title','status','sort','verson','code','c_rate','min_amount','max_amount','f_amount','ex_amount','f_multiple','f_num','limit_money'];

        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['sort'=>'desc','update_at'=>'desc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';


        //查找所需修改通道分组
        $find = model('app\common\model\ChannelGroup')->where('id', $search['g_id'])->field(['mode','weight','concurrent'])->find();

        $mode = [];
        $weight = [];
        $concurrent = [];
        if(!empty($find)){
            $mode1 = json_decode($find['mode'],true);
            $weight1 = json_decode($find['weight'],true);
            $concurrent1 = json_decode($find['concurrent'],true);

            !empty($mode1) && $mode = $mode1;
            !empty($weight1) && $weight = $weight1;
            !empty($concurrent1) && $concurrent = $concurrent1;

        }


        foreach ($data as $k => $val){

            $data[$k]['LAY_CHECKED'] = false;
            if(in_array($val['id'], $mode)){
                $data[$k]['LAY_CHECKED'] = true;
            }
            $data[$k]['weight'] = 5;
            if(isset($weight[$val['id']]))   $data[$k]['weight'] = $weight[$val['id']];
            $data[$k]['concurrent'] = 0;
            if(isset($concurrent[$val['id']])) $data[$k]['concurrent'] = $concurrent[$val['id']];
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




}