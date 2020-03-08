<?php

namespace app\common\model;

use app\common\service\ModelService;

/**
 * 支付通道
 */
class ChannelDf extends ModelService {

    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_channel_df';


    /**
     * redis
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [

        'ttl'=> 3360 ,
        'key'=> "String:table:ChannelDf:code:{code}:id:{id}",
        'keyArr'=> ['id'],
    ];

    /**
     * 获取列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function cList($page = 1, $limit = 10, $search = []) {
        $where = [];

        if(!isset($search['status'])) $where[]=['status','in',[1,2]];

        //搜索条件
        $searchField['eq'] = ['status'];
        $searchField['like'] = ['remark','title','code'];

        $where = search($search,$searchField,$where);

        $field = '*';

        $count = $this->where($where)->count();

        $data = $this->where($where)->field($field)->page($page, $limit)->order(['status'=>'desc','sort'=>'desc','update_at'=>'desc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';

        foreach ($data as $k => $v){
            $data[$k]['money'] = Umoney::quickGet(['uid'=>0,'df_id'=>$v['id']]);
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
     * 出款代付通道列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function wList($page = 1, $limit = 10, $search = [],$where = []) {

        $where[]=['status','=',1];
        //搜索条件
        $searchField['like'] = ['title'];

        $where = search($search,$searchField,$where);

        $field = ['id','title','fee','min_pay','max_pay','inner','c_rate','total_balance','balance'];

        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['update_at'=>'desc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';

        $Umoney =    model('app\common\model\Umoney')->get_amount(0,0,'all');

        foreach ($data as $k => $val){
            $data[$k]['balance1'] = $Umoney[$val['id']]["balance"];
            $data[$k]['frozen_amount'] = $Umoney[$val['id']]["frozen_amount"];

            $data[$k]['LAY_CHECKED'] = false;
            if($val['id'] ==  $search['channel_id']){
                $data[$k]['LAY_CHECKED'] = true;
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
     * ID与详情
     * @param array $modules
     */
    public static function info(){
     \think\facade\Cache::remember('ChannelDfInfo', function () {
         $value =  self::column('id,title,inner','id');
         \think\facade\Cache::tag('ChannelDf')->set('ChannelDfInfo',$value,60);
         return $value;
        },60);
        return \think\facade\Cache::get('ChannelDfInfo');
    }


    /**
     * 获取通道配置信息
     * @param $id
     */
    public static function get_config($code){
        $config = self::where(['code'=>$code])->cache('df_config_'.$code,2)->find();
        //自定义秘钥
        if(!empty($config['secretkey']))  $config['secretkey'] = json_decode($config['secretkey'],true);
        return  $config;
    }


}