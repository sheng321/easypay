<?php
// +----------------------------------------------------------------------
// | 99PHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018~2020 https://www.99php.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Mr.Chung <chung@99php.cn >
// +----------------------------------------------------------------------

namespace app\common\model;

use app\common\service\ModelService;

/**
 * 商户金额模型
 * Class Auth
 * @package app\common\model
 */
class Umoney extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_money';

    /**
     * redis (复制的时候不要少数组参数)
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 3,
        'key'=> "String:table:Umoney:channel_id:{channel_id}:uid:{uid}:id:{id}",
        'keyArr'=> ['id','uid','channel_id'],
    ];

    /**
     * 获取列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function aList($page = 1, $limit = 10, $search = [],$type = 0) {
        $where = [];

        if($type == 0){
            //会员
            $where[] = ['channel_id', '=', 0];
            $where[] = ['uid', '>', 0];
        }else{
            //通道
            $where[] = ['uid', '=', 0];
            $where[] = ['channel_id', '>', 0];
            $Channel = Channel::idRate();
            if(!empty($search['channel'])){
                foreach ($Channel as $k =>$v){
                    if($v['pid'] == 0 && $v['title'] == $search['channel']){
                        $search['channel_id'] = $k;
                        break;
                    }
                }
            }
        }



        //搜索条件
        $searchField['eq'] = ['uid','channel_id'];
        $searchField['time'] = ['update_at'];
        $where = search($search,$searchField,$where);
        $field = ['id','uid','update_at','balance','total_money','frozen_amount','frozen_amount_t1','artificial','channel_id'];

        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['total_money'=>'desc','update_at'=>'desc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';

        if($type == 1) {
            foreach ($data as $K=>$v) {
                $data[$K]['channel'] = $Channel[$v['channel_id']]['title'];
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
     * Undocumented 获取余额
     *
     * @param [type] $mch
     * @return void
     */
    public static function get_amount($mch){
        return self::where("uid",$mch)->value("balance");
    }

    /**
     * 处理金额 1.金额操作
     * @param $data  会员金额
     * @param $change 变动金额
     * @return mixed
     */
    public static function dispose($data,$change){
        $res['msg'] = true;
        $change['change'] =  number_format($change['change'],2,'.','');
        $change['uid'] = $data['uid'];
        $change['channel_id'] = $data['channel_id'];
        $change['before_balance'] = $data['balance'];//变动前金额

        switch (true){
            case ($data['uid'] == 0 && $data['channel_id'] > 0):
                $temp = $data['channel_id'];
                $change['type1'] = 1;//通道
                break;
            case ($data['channel_id'] == 0  && $data['uid'] > 0 ):
                $temp = $data['uid'];
                $change['type1'] = 0;//会员
                break;
            default:
                $temp = '平台';
                $change['type1'] = 2;//平台
                break;
        }

        //修改平台金额的情况 (会员)
        if(in_array($change['type'],[3,4]) &&  $change['type1'] == 0){
            $p = self::where(['id'=>0,'uid'=>0,'channel_id'=>0])->field('id,total_money,balance')->find()->toArray();//平台金额
            $change1['change'] = $change['change'];
            $change1['before_balance'] = $p['balance'];//变动前金额
            $change1['relate'] = $temp;//关联
            $change1['type1'] = 2;
            $change1['type'] = $change['type'];
            $change1['remark'] = $change['remark'];
        }

        switch ($change['type']){
            case 3:
                $res['log'] = $temp.'添加金额'.$change['change'];

                $data['balance'] = $data['balance'] + $change['change'];
                $data['total_money'] = $data['total_money'] + $change['change'];
                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1']);
                $change['balance'] = $data['balance'];//变动后的金额

                $data['balance'] =  Db::raw('balance+'.$change['change']);
                $data['total_money'] = Db::raw('total_money+'.$change['change']);

                if($change['type1'] != 2){ //不是平台的情况
                    $p['balance'] = Db::raw('balance-'.$change['change']);
                    $p['total_money'] = Db::raw('total_money-'.$change['change']);
                    $change1['type'] = 4;
                }

                $change['relate'] = '平台';

                break;
            case 4:
                $res['log'] = $temp.'扣除金额'.$change['change'];

                $data['balance'] = $data['balance'] - $change['change'];
                if($data['balance'] < 0)   $res['msg'] = '变动金额大于可用金额';
                $data['total_money'] = $data['total_money'] - $change['change'];
                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1']);
                $change['balance'] = $data['balance'];//变动后的金额

                $data['balance'] =  Db::raw('balance-'.$change['change']);
                $data['total_money'] = Db::raw('total_money-'.$change['change']);


                if($change['type1'] != 2){
                    $p['balance'] =  Db::raw('balance+'.$change['change']);
                    $p['total_money'] =  Db::raw('total_money+'.$change['change']);
                    $change1['type'] = 3;
                }

                $change['relate'] = '平台';

                break;
            case 5: //提现冻结
                $res['log'] = $temp.'提现冻结'.$change['change'];

                $data['balance'] = $data['balance'] - $change['change'];
                if($data['balance'] < 0)   $res['msg'] = '变动金额大于可用金额';
                $data['frozen_amount'] = $data['frozen_amount'] + $change['change'];
                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1']);
                $change['balance'] = $data['balance'];//变动后的金额

                $data['balance'] =  Db::raw('balance-'.$change['change']);
                $data['frozen_amount'] = Db::raw('frozen_amount+'.$change['change']);

                break;
            case 10:
                $res['log'] = $temp.'人工解冻金额'.$change['change'];

                $data['artificial'] = $data['artificial'] - $change['change'];
                if($data['artificial'] < 0)   $res['msg'] = '变动金额大于人工冻结金额';
                $data['balance'] = $data['balance'] + $change['change'];
                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1']);
                $change['balance'] = $data['balance'];//变动后的金额

                $data['balance'] =  Db::raw('balance+'.$change['change']);
                $data['artificial'] = Db::raw('artificial-'.$change['change']);

                break;
            case 9:
                $res['log'] = $temp.'人工冻结金额'.$change['change'];

                $data['balance'] = $data['balance'] - $change['change'];
                if($data['balance'] < 0)   $res['msg'] = '变动金额大于可用余额:'.$data['balance'];
                $data['artificial'] = $data['artificial'] +  $change['change'];
                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1']);
                $change['balance'] = $data['balance'];//变动后的金额

                $data['balance'] =  Db::raw('balance-'.$change['change']);
                $data['artificial'] = Db::raw('artificial+'.$change['change']);

                break;
            default:
                $total_money = false;
                $res['msg'] = '资金异常!';
                break;
        }

        if($total_money != 0)  $res['msg'] = '资金异常!';

        $res['data'][] = $data;

        switch (true){
            case (session('admin_info.username')):
                $username = '系统操作人:'.session('admin_info.username').'-';
                break;
            case (session('user_info.username')):
                $username = '商户操作人:'.session('admin_info.username').'-';
                break;
            case (session('agent_info.username')):
                $username = '代理操作人:'.session('admin_info.username').'-';
                break;
            default:
                $username = '';
                break;
        }

        $change['remark'] = $username.$res['log'];
        $res['change'][] = $change;

        if(!empty($p)){
            $res['data'][] = $p;
            $change1['balance'] = $p['balance']; //平台变动后的金额
            $res['change'][] = $change1;
        }

        return $res;
    }


}