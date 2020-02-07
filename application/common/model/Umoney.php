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
use think\Db;

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
        'ttl'=> 1,
        'key'=> "String:table:Umoney:df_id:{df_id}:channel_id:{channel_id}:uid:{uid}:id:{id}",
        'keyArr'=> ['id','uid','channel_id','df_id'],
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
        }elseif($type == 1){
            //支付通道
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
        }else{
            //代付通道
            $where[] = ['uid', '=', 0];
            $where[] = ['df_id', '>', 0];
            $Channel = ChannelDf::info();
            if(!empty($search['channel'])){
                foreach ($Channel as $k =>$v){
                    if( $v['title'] == $search['channel']){
                        $search['df_id'] = $k;
                        break;
                    }
                }
            }
        }



        //搜索条件
        $searchField['eq'] = ['uid','channel_id'];
        $searchField['time'] = ['update_at'];
        $where = search($search,$searchField,$where);
        $field = ['id','uid','update_at','balance','total_money','frozen_amount','frozen_amount_t1','artificial','channel_id,df,df_id'];

        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['total_money'=>'desc','update_at'=>'desc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';


            foreach ($data as $K=>$v) {
                if($type == 1) {
                $data[$K]['channel'] = $Channel[$v['channel_id']]['title'];
                }
                if($type == 2) {
                    $data[$K]['channel'] = $Channel[$v['df_id']]['title'];
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
     *  获取金额信息
     * @param [type] $mch
     */
    public static function get_amount($mch = '0',$channel = '0',$df = '0'){
        $mch = strval($mch);
        $channel = strval($channel);

        if($mch === '0' && $channel === '0'&& $df === '0') return self::where(['uid'=>$mch,'channel_id'=>$channel,'df_id'=>$df])->column(['total_money','frozen_amount','balance'],'id');  //平台账户


        if($mch === '0' && $channel === 'all') return self::where([['uid','=',$mch],['channel_id','>',0]])->column(['id','total_money','frozen_amount','balance'],'channel_id');
        if($mch === '0' && $df === 'all') return self::where([['uid','=',$mch],['df_id','>',0]])->column(['id','total_money','frozen_amount','balance'],'df_id');
        if($mch === 'all' && $channel === '0' && $df === '0') return self::where([['uid','>',$mch],['channel_id','=',0],['df_id','=',0]])->column(['id','total_money','frozen_amount','balance','df'],'id');


        if($mch === '0' && $channel > 0 ) return self::where(['uid'=>$mch,'channel_id'=>$channel])->column(['id','total_money','frozen_amount','balance'],'channel_id');
        if($mch === '0' && $df > 0 ) return self::where(['uid'=>$mch,'df_id'=>$df])->column(['id','total_money','frozen_amount','balance'],'df_id');
        if($channel === '0'  && $df === '0' ) return self::where(['uid'=>$mch,'channel_id'=>$channel,'df_id'=>$df])->column(['id','total_money','frozen_amount','balance','df'],'id');
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
        $change['df_id'] = $data['df_id'];
        $change['before_balance'] = $data['balance'];//变动前金额

        switch (true){
            case ($data['uid'] == 0 && $data['df_id']>0):
                $temp = '代付通道ID:'.$data['df_id'];
                $change['type1'] = 3;//代付通道记录
                break;
            case ($data['uid'] == 0 && $data['channel_id'] > 0):
                $temp = '支付通道ID:'.$data['channel_id'];
                $change['type1'] = 1;//通道记录
                break;
            case ($data['channel_id'] == 0 && $data['df_id'] == 0  && $data['uid'] > 0 ):
                $temp = '会员ID:'.$data['uid'];
                $change['type1'] = 0;//会员记录
                break;
            default:
                $temp = '平台';
                $change['type1'] = 2;//平台记录
                break;
        }

        //操作人类型
        switch (true){
            case (app('request')->module() === 'user'):
                $change['type2'] = 2;
                break;
            case (app('request')->module() === 'agent'):
                $change['type2'] = 3;
                break;
            case (app('request')->module() === 'admin'):
                $change['type2'] = 1;
                break;
            default:
                $change['type2'] = 0;
                break;
        }


        //关联修改平台金额的情况
        if(in_array($change['type'],[3,4]) &&  $change['type1'] == 0){
            $p = self::where(['id'=>1,'uid'=>0,'channel_id'=>0])->field('id,total_money,balance')->find()->toArray();//平台金额
            $change1['change'] = $change['change'];
            $change1['before_balance'] = $p['balance'];//变动前金额
            $change1['relate'] = $temp;//关联
            $change1['type2'] = $change['type2'];
            $change1['type1'] = 2;//平台记录
            $change1['type'] = $change['type'];
            $change1['remark'] = $change['remark'];
        }

        switch ($change['type']){
            case 1: //成功-解冻扣除  (把解冻金额去掉)
                $res['log'] = $temp.'成功-解冻扣除'.$change['change'];
                $change1['before_balance'] = $data['frozen_amount'];//变动前金额

                $data['frozen_amount'] = $data['frozen_amount'] - $change['change'];
                $data['total_money'] = $data['total_money'] - $change['change'];
                if($data['frozen_amount'] < 0)   $res['msg'] = '成功-解冻扣除大于冻结金额';

                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1'] + $data['df']);
                $change['balance'] = $data['frozen_amount'];//变动后的金额

                $data['total_money'] =  Db::raw('total_money-'.$change['change']);
                $data['frozen_amount'] = Db::raw('frozen_amount-'.$change['change']);

                break;

            case 3:
                $res['log'] = $temp.'添加金额'.$change['change'];

                $data['balance'] = $data['balance'] + $change['change'];
                $data['total_money'] = $data['total_money'] + $change['change'];
                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1'] + $data['df']);
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
                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1'] + $data['df']);
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
            case 5: //冻结
                $res['log'] = $temp.'申请金额冻结'.$change['change'];

                $data['balance'] = $data['balance'] - $change['change'];
                if($data['balance'] < 0)   $res['msg'] = '申请金额冻结大于可用金额';
                $data['frozen_amount'] = $data['frozen_amount'] + $change['change'];
                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1'] + $data['df']);
                $change['balance'] = $data['balance'];//变动后的金额

                $data['balance'] =  Db::raw('balance-'.$change['change']);
                $data['frozen_amount'] = Db::raw('frozen_amount+'.$change['change']);

                break;
            case 6: //解冻
                $res['log'] = $temp.'解冻退款'.$change['change'];

                $data['frozen_amount'] = $data['frozen_amount'] - $change['change'];
                $data['balance'] = $data['balance'] + $change['change'];
                if($data['frozen_amount'] < 0)   $res['msg'] = '解冻退款大于冻结金额';

                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1'] + $data['df']);
                $change['balance'] = $data['balance'];//变动后的金额

                $data['balance'] =  Db::raw('balance+'.$change['change']);
                $data['frozen_amount'] = Db::raw('frozen_amount-'.$change['change']);
                break;
            case 10:
                $res['log'] = $temp.'人工解冻金额'.$change['change'];

                $data['artificial'] = $data['artificial'] - $change['change'];
                if($data['artificial'] < 0)   $res['msg'] = '变动金额大于人工冻结金额';
                $data['balance'] = $data['balance'] + $change['change'];
                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1'] + $data['df']);
                $change['balance'] = $data['balance'];//变动后的金额

                $data['balance'] =  Db::raw('balance+'.$change['change']);
                $data['artificial'] = Db::raw('artificial-'.$change['change']);

                break;
            case 9:
                $res['log'] = $temp.'人工冻结金额'.$change['change'];

                $data['balance'] = $data['balance'] - $change['change'];
                if($data['balance'] < 0)   $res['msg'] = '变动金额大于可用余额:'.$data['balance'];
                $data['artificial'] = $data['artificial'] +  $change['change'];
                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1'] + $data['df']);
                $change['balance'] = $data['balance'];//变动后的金额

                $data['balance'] =  Db::raw('balance-'.$change['change']);
                $data['artificial'] = Db::raw('artificial+'.$change['change']);

                break;

            case 13: //余额转代付金额
                $res['log'] = $temp.'余额转代付金额'.$change['change'];

                $data['balance'] = $data['balance'] - $change['change'];
                if($data['balance'] < 0)   $res['msg'] = '变动金额大于可用金额';
                $data['df'] = $data['df'] + $change['change'];
                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1'] + $data['df']);
                $change['balance'] = $data['balance'];//变动后的金额

                $data['balance'] =  Db::raw('balance-'.$change['change']);
                $data['df'] = Db::raw('df+'.$change['change']);

                break;
            case 14: //代付金额转余额
                $res['log'] = $temp.'代付金额转余额'.$change['change'];

                $data['df'] = $data['df'] - $change['change'];
                $data['balance'] = $data['balance'] + $change['change'];
                if($data['df'] < 0)   $res['msg'] = '代付金额大于可用金额';

                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1'] + $data['df']);
                $change['balance'] = $data['balance'];//变动后的金额

                $data['balance'] =  Db::raw('balance+'.$change['change']);
                $data['df'] = Db::raw('df-'.$change['change']);
                break;

            case 15: //代付冻结
                $change['before_balance'] = $data['df'];//变动前金额
                $res['log'] = $temp.'代付冻结'.$change['change'];

                $data['df'] = $data['df'] - $change['change'];
                if($data['balance'] < 0)   $res['msg'] = '代付需冻结金额大于代付金额';
                $data['frozen_amount'] = $data['frozen_amount'] + $change['change'];
                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1'] + $data['df']);
                $change['balance'] = $data['df'];//变动后的金额

                $data['df'] =  Db::raw('df-'.$change['change']);
                $data['frozen_amount'] = Db::raw('frozen_amount+'.$change['change']);

                break;
            case 16: //代付解冻退款
                $change['before_balance'] = $data['df'];//变动前金额
                $res['log'] = $temp.'代付解冻退款'.$change['change'];

                $data['frozen_amount'] = $data['frozen_amount'] - $change['change'];
                $data['df'] = $data['df'] + $change['change'];
                if($data['frozen_amount'] < 0)   $res['msg'] = '代付解冻退款大于冻结金额';

                $total_money =  $data['total_money'] - ($data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1'] + $data['df']);
                $change['balance'] = $data['balance'];//变动后的金额

                $data['df'] =  Db::raw('df+'.$change['change']);
                $data['frozen_amount'] = Db::raw('frozen_amount-'.$change['change']);
                break;

            default:
                $total_money = false;
                $res['msg'] = '资金异常!';
                break;
        }

        if($total_money != 0)  $res['msg'] = '资金异常!';

        $res['data'][] = $data;


        $change['remark'] = $res['log'];
        $res['change'][] = $change;

        if(!empty($p)){
            $res['data'][] = $p;
            $change1['balance'] = $p['balance']; //平台变动后的金额
            $res['change'][] = $change1;
        }

        return $res;
    }


}