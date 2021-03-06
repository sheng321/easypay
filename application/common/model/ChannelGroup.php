<?php

namespace app\common\model;

use app\common\service\ModelService;
use think\Db;
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

        'ttl'=> 60 ,
        'key'=> "String:table:ChannelGroup:title:{title}:id:{id}",
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


        $field = ['id','update_at','remark','title','status','sort','verson','p_id','c_rate','cli'];

        $count = $this->where($where)->count(1);

        $data = $this->where($where)->field($field)->page($page, $limit)->order(['p_id'=>'desc','sort'=>'desc'])->select()->toArray();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';

        //产品列表
        $product = \app\common\model\PayProduct::idArr();
        $ChannelProduct = model('app\common\model\ChannelProduct');
        foreach ($data as $k => $v){

            $data[$k]['min_amount'] = 0;
            $data[$k]['max_amount'] = 0;
            $data[$k]['f_amount'] = '';
            $data[$k]['ex_amount'] = '';
            $data[$k]['rate'] = 0;

            $data[$k]['mode'] =  $ChannelProduct->where(['p_id'=>$v['p_id'],'group_id'=>$v['id']])->count();
            if($data[$k]['mode'] > 0){
                $select = $ChannelProduct->alias('a')->join('channel w','w.id = a.channel_id','LEFT')->where([
                    ['a.p_id','=',$v['p_id']],
                    ['a.group_id','=',$v['id']]])->field('a.*,w.min_amount,w.max_amount,w.f_amount,w.ex_amount,w.c_rate as rate')->select()->toArray();

                foreach ($select as $k1 => $v1){
                    $data[$k]['min_amount'] = $data[$k]['min_amount'] == 0?$v1['min_amount']:min($v1['min_amount'],$data[$k]['min_amount']);
                    $data[$k]['max_amount'] = max($v1['max_amount'],$data[$k]['max_amount']);
                    $data[$k]['f_amount'] =  $data[$k]['f_amount'].'|'.$v1['f_amount'];
                    $data[$k]['ex_amount'] =  $data[$k]['ex_amount'].'|'.$v1['ex_amount'];
                    $data[$k]['rate'] = max($data[$k]['rate'],$v1['rate']);
                }

                if(!empty($data[$k]['f_amount'])){
                    $filter = array_filter(explode('|',$data[$k]['f_amount']));
                    $data[$k]['f_amount'] = implode('|',array_unique($filter));
                }

                if(!empty($data[$k]['ex_amount'])){
                    $filter = array_filter(explode('|',$data[$k]['ex_amount']));
                    $data[$k]['ex_amount'] = implode('|',array_unique($filter));
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
     * 代理分组获取列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function bList($page = 1, $limit = 10, $search = []) {
        $where = [
          //['cli','=',1]
        ];

        //搜索条件
        $searchField['eq'] = ['status'];
        $searchField['like'] = ['remark','title'];

        $where = search($search,$searchField,$where);

        $field = ['id','remark','title','status','verson','p_id','c_rate','cli'];

        $count = $this->where($where)->count();

        $data = $this->where($where)->field($field)->page($page, $limit)->order(['p_id'=>'desc','sort'=>'desc'])->select()->toArray();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';

        //产品列表
        $product = \app\common\model\PayProduct::idArr();
        $code = \app\common\model\PayProduct::idCode();

        foreach ($data as $k => $v){
            $data[$k]['product'] = $product[$v['p_id']];
            $data[$k]['code'] = $code[$v['p_id']];
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
     * 商户获取通道分组列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function uList($page = 1, $limit = 10, $search = []) {
        $where = [
            ['status','=',1],
        ];

        $field = ['id','update_at','remark','title','status','sort','verson','p_id','cli'];
        $count = $this->where($where)->count(1);
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['p_id'=>'desc','sort'=>'desc','update_at'=>'desc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';

        if(!empty($search['channel'])){
            $channel = $search['channel'];
        }else{
            $channel = [];
        }
        if(empty($search['uid'])) $search['uid'] = 0;

        //支付产品
        $product = \app\common\model\PayProduct::idArr();
        $code = \app\common\model\PayProduct::idCode();

        foreach ($data as $k => $val){
            //当前通道分组的状态
            if($search['uid']>0){
                //当为代理分组的时候
                $GroupStatus =  \app\common\service\RateService::getAgentStatus($search['uid'],$val['id']);
                $data[$k]['status'] = 1;
                if(!empty($GroupStatus)){
                    $data[$k]['status'] = $GroupStatus['status'];
                    $data[$k]['rate'] = $GroupStatus['rate'];
                    if($data[$k]['status'] == 0) $data[$k]['rate'] = '未设置';
                }
            }


            //已选中的状态
           $data[$k]['LAY_CHECKED'] = false;
            if(isset($channel[$val['p_id']]) &&  in_array($val['id'], $channel[$val['p_id']])){
               if($data[$k]['status'] == 1) $data[$k]['LAY_CHECKED'] = true;//通道开启的时候才为选中状态
            }else{
                //当为客户端隐藏的时候 不显示；如果后台设为选中，就显示
               if( $val['cli'] == 0){
                   unset($data[$k]);
                   continue;
               }
            }

            $data[$k]['product'] = $product[$val['p_id']]; //支付产品
            $data[$k]['code'] = $code[$val['p_id']]; //支付产品
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

        \think\facade\Cache::remember('ChannelGroupIdArr', function () {
            $value = self::column('id,title');
            \think\facade\Cache::tag('ChannelGroup')->set('ChannelGroupIdArr',$value,60);
            return $value;
        },3600);
        return \think\facade\Cache::get('ChannelGroupIdArr');
    }


    /**
     * ID与成本费率和运营费率
     * @param array $modules
     */
    public static function idRate(){
        \think\facade\Cache::remember('ChannelGroupidRate', function () {
            $value = self::column('id,c_rate,status','id');
            \think\facade\Cache::tag('ChannelGroup')->set('ChannelGroupidRate',$value,60);
            return $value;
        },60);

        return \think\facade\Cache::get('ChannelGroupidRate');
    }




}