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
        'ttl'=> 3360 ,
        'key'=> "String:table:Umoney:uid:{uid}:id:{id}",
        'keyArr'=> ['id','uid'],
    ];


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
     * 处理金额  待完善
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
            case ($data['uid'] == 0):
                $temp = $data['channel_id'];
                $change['type1'] = 1;//通道
                break;
            case ($data['channel_id'] == 0):
                $temp = $data['uid'];
                $change['type1'] = 0;//通道
                break;
            default:
                $temp = '平台';
                break;
        }

        //修改平台金额的情况
        if(in_array($change['type'],[3,4])){
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

                $p['balance'] = $p['balance'] - $change['change'];
                $p['total_money'] = $p['total_money'] - $change['change'];
                $change1['type'] = 4;

                $change['relate'] = '平台';

                break;
            case 4:
                $res['log'] = $temp.'扣除金额'.$change['change'];
                $data['balance'] = $data['balance'] - $change['change'];

                if($data['balance'] < 0)   $res['msg'] = '变动金额大于可用金额';

                $data['total_money'] = $data['total_money'] - $change['change'];


                $p['balance'] = $p['balance'] + $change['change'];
                $p['total_money'] = $p['total_money'] + $change['change'];
                $change1['type'] = 3;

                $change['relate'] = '平台';

                break;
            case 10:
                $res['log'] = $temp.'人工解冻金额'.$change['change'];
                $data['artificial'] = $data['artificial'] - $change['change'];

                if($data['artificial'] < 0)   $res['msg'] = '变动金额大于人工冻结金额';

                $data['balance'] = $data['balance'] + $change['change'];
                break;
            case 9:
                $res['log'] = $temp.'人工冻结金额'.$change['change'];
                $data['balance'] = $data['balance'] - $change['change'];

                if($data['balance'] < 0)   $res['msg'] = '变动金额大于可用余额:'.$data['balance'];

                $data['artificial'] = $data['artificial'] +  $change['change'];
                break;
            default:
                $res['msg'] = '资金异常!';
                break;
        }

        $total_money =  $data['balance'] + $data['artificial'] + $data['frozen_amount'] + $data['frozen_amount_t1'];
        if($total_money != $data['total_money'])  $res['msg'] = '资金异常!';

        $res['data'][] = $data;

        $change['balance'] = $data['balance'];
        $res['change'][] = $change;

        if(!empty($p)){
            $res['data'][] = $p;
            $change1['balance'] = $p['balance'];
            $res['change'][] = $change1;
        }

        return $res;
    }


}