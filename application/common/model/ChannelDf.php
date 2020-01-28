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
        'is_open'=> true,
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

        //搜索条件
        $searchField['like'] = ['title'];

        $where = search($search,$searchField,$where);

        $field = ['id','title','fee','min_pay','max_pay','inner'];

        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['update_at'=>'desc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';

        $Umoney =    model('app\common\model\Umoney')->get_amount(0,0,'all');

        foreach ($data as $k => $val){
            $data[$k]['balance'] = $Umoney[$val['id']]["balance"];
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
      $list =  \think\facade\Cache::remember('ChannelDfInfo', function () {
            $data = self::column('id,title,inner','id');
            \think\facade\Cache::tag('ChannelDf')->set('ChannelDfInfo',$data,60);
            return \think\facade\Cache::get('ChannelDfInfo');
        });
        return $list;
    }


}