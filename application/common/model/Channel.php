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
        'key'=> "String:table:Channel:id:{id}",
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


        $field = ['id','pid','p_id','visit','update_at','remark','title','status','sort','verson','code','c_rate','s_rate','min_amount','max_amount','f_amount','ex_amount','f_multiple','f_num','limit_money'];

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

        $field = ['id','pid','p_id','visit','update_at','remark','title','status','sort','verson','code','c_rate','s_rate','min_amount','max_amount','f_amount','ex_amount','f_multiple','f_num','limit_money'];

        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['sort'=>'desc','update_at'=>'desc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';

        //查找该通道分组 的通道
        $select = model('app\common\model\ChannelProduct')->where('group_id', $search['g_id'])->select()->toArray();
        $find = array_column($select, null, 'channel_id');

        foreach ($data as $k => $val){
            $data[$k]['LAY_CHECKED'] = false;
            $data[$k]['weight'] = 5;
            $data[$k]['concurrent'] = 0;

            if(isset($find[$val['id']])){
                $data[$k]['LAY_CHECKED'] = true;
                $data[$k]['weight'] =  $find[$val['id']]['weight'];
                $data[$k]['concurrent'] = $find[$val['id']]['concurrent'];
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
     * ID与成本费率和运营费率
     * @param array $modules
     */
    public static function idRate(){
        \think\facade\Cache::remember('ChannelIdRate', function () {
            $data = self::column('id,c_rate,s_rate,title,pid','id');
            \think\facade\Cache::tag('Channel')->set('ChannelIdRate',$data,60);
            return \think\facade\Cache::get('ChannelIdRate');
        });
        return \think\facade\Cache::get('ChannelIdRate');
    }


    /**
     * 获取通道编码
     * @param array $modules
     */
    public static function get_code($id){
       $data =  self::quickGet($id);
      if(empty($data)) return false;
      if($data['pid'] != 0){
          $Channel =  self::quickGet($data['pid']);
          if(empty($Channel)) return false;
          return $Channel['code'];
      }
      return $data['code'];
    }


    /**
     * 获取通道ID
     * @param array $modules
     */
    public static function get_id($id){
        $data =  self::quickGet($id);
        if(empty($data)) return false;
        if($data['pid'] != 0){
            $Channel =  self::quickGet($data['pid']);
            if(empty($Channel)) return false;
            return $Channel['id'];
        }
        return $id;
    }


    /**
     * 获取通道配置信息
     * @param $id
     */
    public static function get_config($code){
        $config = self::where(['code'=>$code])->cache('channel_config_'.$code,2)->find();
        return  $config;
    }



}