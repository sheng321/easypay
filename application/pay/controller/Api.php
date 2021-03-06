<?php
namespace app\pay\controller;
use app\common\controller\PayController;
use app\common\model\Channel;
use app\common\model\ChannelProduct;
use app\common\model\Order;
use app\common\model\PayProduct;
use app\common\model\Ulevel;
use app\common\model\Uprofile;
use app\common\service\RateService;
use app\pay\service\Payment;
use Lock\Lock;
use redis\StringModel;

/**
 * 支付下单接口
 * Class Api
 * @package app\pay\controller
 */
class Api extends PayController
{
    public function index(){
        $param =   $this->request->only(["pay_memberid" ,"pay_orderid","pay_amount","pay_applydate","pay_bankcode" ,"pay_notifyurl","pay_callbackurl","pay_md5sign"],'post');

        //通过cookie判断是否刷单
        $cookieName = md5($param['pay_memberid']);
        $orderId = json_decode(cookie($cookieName),true);//15分钟

        if(!empty($orderId) && is_array($orderId)){
            $num1 = count($orderId);
            if($num1 > 9){
                if($num1 < 16){
                    $orderIdType = ctype_alnum(implode('',$orderId)); //检查字符串是否是数字和字母，或者两者混合
                    if($orderIdType){
                        //检测订单好是否重复
                        $num =  Order::where([['out_trade_no','in',$orderId],['pay_status','=',2], ['create_at','>',timeToDate(0,-20)]])->order(['id'=>'desc'])->count(1);//是否有支付的情况
                        if(empty($num)) __jerror('系统检测到存在刷单的情况，请稍后在试1！！');
                        $orderId = [];//有支付的情况
                    }else{
                        //不是，表明受到了攻击
                        __jerror('系统检测到存在刷单的情况，请稍后在试2！！');
                    }
                }else{
                    //提交太多了，一定不正常
                    __jerror('系统检测到存在刷单的情况，请稍后在试3！！');
                }
            }
        }else{
            $orderId = [];
        }


        $redis1 = StringModel::instance();
        $redis1->select(2);

        //当前访问量
        $flow = 'flow_'.date('H:i');
        if($redis1->exists($flow)){
            $flow_data = json_decode($redis1->get($flow),true);
            $flow_data['num']['total'] += 1;
            $flow_data['num'][$param['pay_bankcode']] += 1;
            $redis1->set($flow,json_encode($flow_data));
            $redis1->expire($flow,60*60*6);//6小时
        }else{
            $flow_data['num']['total'] = 1;

            $PayCode =  \app\common\model\PayProduct::idCode1();

            foreach ($PayCode as $k=>$v){
                $flow_data['title'][$v['code']] =  $v['title'];
                $flow_data['num'][$v['code']] = 0;
            }
            $flow_data['num'][$param['pay_bankcode']] = 1;
            $flow_data['title']['total'] =  '总量';

            $flow_data['time'] = date('H:i');
            $flow_data['timestamp'] = time();
            $redis1->set($flow,json_encode($flow_data));
            $redis1->expire($flow,60*60*6);//6小时
        }



        //通过redis缓存IP判断是否刷单
        $ip_record = 'recordIP_'.$param['pay_memberid'].get_client_ip();
        $orderId_ip_record = json_decode($redis1->get($ip_record),true);
        if(!empty($orderId_ip_record) && is_array($orderId_ip_record)){
            $num1 = count($orderId_ip_record);
            if($num1 > 10) __jerror('系统检测到存在刷单的情况，请稍后在试5！！');
        }else{
            $orderId_ip_record = [];
        }

        //通过后台封禁IP
        $ip = 'IP_'.$param['pay_memberid'].strtr(get_client_ip(), '.', '_');
        if($redis1->exists($ip)) __jerror('系统检测到存在刷单的情况，请稍后在试4！！');


        //商户属性
        $Uprofile =  Uprofile::quickGet(['uid'=>$param['pay_memberid']]);
        if(empty($Uprofile) || $Uprofile['who'] != 0 )  __jerror('商户号不存在');
        if(empty($Uprofile['group_id']))  __jerror('未分配用户分组');


        if(!check_sign($param,$Uprofile['secret']))  __jerror('签名错误');


        //支付产品属性
        $PayProduct = PayProduct::quickGet(['code'=>$param['pay_bankcode']]);
        if(empty($PayProduct) || $PayProduct['status'] != 1) __jerror('支付产品不存在，或者已维护');


        //判断是否国内IP
        if($PayProduct['forbid'] == 0 && !is_china()) __jerror('禁止国外IP访问');

        //访问方式
        if($PayProduct['visit'] == 2 && !isMobile()) __jerror('只能移动端访问！');
        if($PayProduct['visit'] == 1 && isMobile()) __jerror('只能PC端访问！');


        //验证支付产品金额
        $amount['amount'] = $param['pay_amount'];
        $amount['min_amount'] = $PayProduct['min_amount'];
        $amount['max_amount'] = $PayProduct['max_amount'];
        $amount['f_amount'] = $PayProduct['f_amount'];
        $amount['ex_amount'] = $PayProduct['ex_amount'];
        $amount['f_multiple'] = $PayProduct['f_multiple'];
        $amount['f_num'] = $PayProduct['f_num'];
        $amount['mtype'] = $PayProduct['mtype'];
        $validate1 = $this->validate($amount, 'app\common\validate\Pay.check_amount');
        if (true !== $validate1)   __jerror($validate1);
        unset($amount);


        //下一步选择通道

        //验证用户分组
        $Ulevel = Ulevel::quickGet($Uprofile['group_id']);
        if(empty($Ulevel) || $Ulevel['type1'] != 0 )  __jerror('未分配用户分组或商户分组不正确');

        //通道分组ID
        $channel_group_idArr = json_decode($Ulevel['channel_id'],true);
        if(empty($channel_group_idArr) ||  !isset($channel_group_idArr[$PayProduct['id']]) )  __jerror('未分配支付通道分组1');


        //所有在线的支付通道分组及其支付通道
        $ChannelProduct = [];
        foreach ($channel_group_idArr[$PayProduct['id']] as $k => $v){
            $temp1['group_id'] = $v;
            $temp1['p_id'] = $PayProduct['id'];
            $temp = ChannelProduct::where($temp1)->select()->toArray();
            if(!empty($temp)) $ChannelProduct =  array_merge($ChannelProduct,$temp);
        }
        if(empty($ChannelProduct)) __jerror('未分配支付通道2');




        $train['channel_id'] = [];
        $train['channel_group_id'] = [];

        //排除
        foreach ($ChannelProduct as $k =>$v){

            //通过后台屏蔽成功率低的商户
            $merch = 'merch_'.$v['channel_id'].$param['pay_memberid'];
            if($redis1->exists($merch)){
                unset($ChannelProduct[$k]);
                continue;
            }


            $Channel = Channel::quickGet($v['channel_id']);

            //1.通道关闭
            if(empty($Channel) || $Channel['status'] != 1){
                unset($ChannelProduct[$k]);
                //话费通道 清除库存的缓存
                if(!empty($Channel) && $Channel['charge'] == 1) \think\facade\Cache::rm('charge_num_'.$Channel['id']);
                continue;
            }


            //判断是否国内IP
            if($Channel['forbid'] == 0 && !is_china()){
                unset($ChannelProduct[$k]);
                continue;
            };

            //访问方式
            if($Channel['visit'] == 2 && !isMobile()){//只能移动端访问
                unset($ChannelProduct[$k]);
                continue;
            }
            if($Channel['visit'] == 1 && isMobile()){//只能电脑端访问！
                unset($ChannelProduct[$k]);
                continue;
            }


            //2.判断并发 （每分钟多少单）
            if(!empty($v['concurrent']) &&  is_int($v['concurrent']) && $v['concurrent'] > 0){
                //存入redis，判断数量
                $key = date('YmdHi').'to'.$Channel['id'];
                $num = $redis1->get($key);
                if(empty($num)){
                    $redis1->set($key,0);
                    $redis1->expire($key,61);
                    $num = 0;
                }
                if($v['concurrent'] < $num){
                    unset($ChannelProduct[$k]);
                    continue;
                }
                $redis1->incr($key);
            }


            //3.验证支付通道金额
            $amount['amount'] = $param['pay_amount'];
            $amount['min_amount'] = $Channel['min_amount'];
            $amount['max_amount'] = $Channel['max_amount'];
            $amount['f_amount'] = $Channel['f_amount'];
            $amount['ex_amount'] = $Channel['ex_amount'];
            $amount['f_multiple'] = $Channel['f_multiple'];
            $amount['f_num'] = $Channel['f_num'];
            $amount['mtype'] = $Channel['mtype'];
            $validate1 = $this->validate($amount, 'app\common\validate\Pay.check_amount');
            if (true !== $validate1){
                unset($ChannelProduct[$k]);
                continue;
            };
            unset($amount);

            //4.商户费率 小于或者等于通道成本的情况
            $Rate =  RateService::getMemRate($param['pay_memberid'],$PayProduct['id'],$Channel['id']);//商户费率
            if(empty($Rate) || ($Rate <= $Channel['c_rate'])){
                unset($ChannelProduct[$k]);
                continue;
            }
            unset($Rate);


            //5.话费通道 查询库存
            if($Channel['charge'] == 1){
                $charge_num = $this->charge_num($Channel);
                $pay_amount =  ceil($param['pay_amount']);

                //当前金额库话费存量
                if(empty($charge_num[$pay_amount]) || $charge_num[$pay_amount] < 1){
                    unset($ChannelProduct[$k]);
                    continue;
                }
                unset($pay_amount);
                unset($charge_num);
            }


            //6.通道限额
            $check_money = $this->check_money($Channel);
            if(!$check_money){
                unset($ChannelProduct[$k]);
                continue;
            }


            //7.轮训-数据填充  （权重！！）
            if(!empty($v['weight']) &&  is_int($v['weight']) && $v['weight'] > 0 ){
                $temp2 = array_fill(0, $v['weight'], $Channel['id']);//填充数组   支付通道ID
                $temp3 = array_fill(0, $v['weight'], $v['group_id']);//填充数组  支付通道分组ID
                $train['channel_id'] =  array_merge($temp2,$train['channel_id']);
                $train['channel_group_id'] =  array_merge($temp3,$train['channel_group_id']);
                unset($temp2);
                unset($temp3);
            }else{
                array_push($train['channel_id'],$Channel['id']);
                array_push($train['channel_group_id'],$v['group_id']);
            }
            unset($Channel);
        }

        if(empty($ChannelProduct) || empty($train)) __jerror('未匹配支付通道,请重试。');

        //轮训通道 (权重)
        $random_keys = array_rand($train['channel_id'],1);//随机抽取一个
        $channel_id =  $train['channel_id'][$random_keys];//获得支付通道ID
        $channel_group_id =  $train['channel_group_id'][$random_keys];//获得通道分组ID

        if(empty($channel_id)) __jerror('未匹配支付通道4');
        unset($train);
        unset($random_keys);
        unset($ChannelProduct);

        //已选中的通道产品
        $Channel = Channel::quickGet($channel_id);

        //获取商户费率
        $MemRate =  RateService::getMemRate($param['pay_memberid'],$PayProduct['id'],$channel_id);
        if($MemRate === false)  __jerror('商户号不存在，或者未分配用户分组。');

        $AgentRate1 = 0;
        $AgentRate2 = 0;
        $uid1 = 0;
        $uid2 = 0;
        //代理费率  二级分销
        if($Uprofile['pid'] > 0){
            $uid1 = $Uprofile['pid'];
           //上级代理
            $AgentRate1 =  RateService::getAgentRate($Uprofile['pid'],$channel_group_id);
            //如果商户费率小于或者等于代理费率      代理费率小于成本 不给代理分配费率
            if(empty($AgentRate1) || ($MemRate <= $AgentRate1) || ($AgentRate1 <= $Channel['c_rate'])  ) $AgentRate1 = 0;

            //上上级代理
            $Uprofile1 = Uprofile::quickGet(['uid'=>$Uprofile['pid']]);
            if(!empty($Uprofile1) || $Uprofile1['pid'] > 0){
                $uid2 = $Uprofile1['pid'];
                $AgentRate2 =  RateService::getAgentRate( $Uprofile1['pid'],$channel_group_id);
                //二级代理费率费率查询失败 商户费率小于或者等于二级代理费率 一级代理小于或者等于二级代理费率  代理费率小于成本
                if(empty($AgentRate2) || ($MemRate <= $AgentRate2) ||  ($AgentRate1 <= $AgentRate2)  || ($AgentRate2 <= $Channel['c_rate']) ) $AgentRate2 = 0;

            }
        }

        //检测订单好是否重复
        $date = timeToDate(0,0,0,-3); //默认只搜索3天
        $id =  Order::where([
            ['out_trade_no','=',$param['pay_orderid']],
            ['create_at','>',$date],
        ])->order(['id'=>'desc'])->value('id');
        if(!empty($id)) __jerror('订单号重复！');


        //已选中所属通道
        $Channel_father = Channel::quickGet($Channel['pid']);
        if(empty($Channel_father) || empty($Channel_father['code']) || empty($Channel_father['limit_time'])) __jerror('支付服务不存在0');


        $data['mch_id'] = $param['pay_memberid'];//商户号
        $data['mch_id1'] = $uid1;//上级代理
        $data['mch_id2'] = $uid2;//上上级代理
        $data['out_trade_no'] = $param['pay_orderid'];//商户订单号
        $data['system_no'] =  getOrder('s');//平台订单号
        $data['amount'] = number_format($param['pay_amount'],2,'.','');//下单金额
        $data['cost_rate'] = $Channel['c_rate'];//成本费率
        $data['run_rate'] = $MemRate;//运营费率
        $data['total_fee'] = $data['amount']*$MemRate;//运营手续费
        $data['settle'] = $data['amount'] - $data['total_fee'] ;//商户结算
        $data['agent_rate'] =  $AgentRate1;//上级代理费率
        $data['agent_rate2'] =  $AgentRate2;//上上级代理费率
        $data['upstream_settle'] = $data['amount']*$Channel['c_rate'];//上游结算
        $data['agent_amount'] = ($AgentRate1 == 0)?0: $data['amount']*($MemRate -  $AgentRate1);//上级代理商结算
        $data['agent_amount2'] = ($AgentRate2 ==0)?0: $data['amount']*($AgentRate1 -  $AgentRate2);//上上级代理商结算
        $data['channel_id'] = $channel_id;//渠道id
        $data['channel_group_id'] = $channel_group_id;//支付通道分组ID
        $data['pay_code'] = $PayProduct['code'];
        $data['payment_id'] = $PayProduct['id'];
        $data['notify_url'] = $param['pay_notifyurl'];//异步回调
        $data['callback_url'] = $param['pay_callbackurl'];//同步跳转
        $data['ip'] = get_client_ip();//请求ip

        switch (true){
            case ($AgentRate2 > 0):
                $rate = $AgentRate2 - $data['cost_rate'];
                break;
            case ($AgentRate1 > 0):
                $rate = $AgentRate1 - $data['cost_rate'];
                break;
            default:
                $rate = $MemRate - $data['cost_rate'];
                break;
        }
        $data['Platform'] = $data['amount']*$rate;//平台收益
        $data['create_time'] =  $param['pay_applydate'];//商户提交时间
        $data['is_mobile'] =  isMobile()?1:0;//商户提交时间

        $data['over_time'] = time() + $Channel_father['limit_time']*60;//订单过期时间

        $param1 = $this->request->only(["pay_productname","pay_attach"],'post');
        $data['productname'] = $param1['pay_productname'];//商品名称
        $data['attach'] = $param1['pay_attach'];//备注

        unset($param);

        //插入数据库
        try{
            $lock_val = 'pay:api:'.$data['system_no'];
            $create = Lock::queueLock(function ($res)  use ($data){
                        $model = new Order();
                        //使用事物保存数据
                        $model->startTrans();
                        $create = $model->create($data);
                        if (!$create) {
                            $model->rollback();
                        }else{
                            $model->commit();
                        }
                        return $create;
                    },$lock_val, 100, 45);
        }catch (\Exception $e){
            //出现异常
            exceptions(['msg'=>'当前访问人数过多，请稍后再试~','url'=>'http://www.baidu.com']);
        }

        if(empty($create) || !$create)  __jerror('系统繁忙，请重试~');
        unset($data);

        $create['code'] = $Channel['code'];
        unset($Channel);
        //提交上游
        $Payment = Payment::factory($Channel_father['code']);
        $html  = $Payment->pay($create);

        //到这里表示请求下单成功，给给客户端一个标识，处理刷单的情况
        $orderId[] = $create['out_trade_no'];
        cookie($cookieName,json_encode($orderId),[ 'samesite' => "None",'expire'=>15*60]);//15分钟 添加到cookie

        $orderId_ip_record[] = $create['id'];
        //实时记录IP的下单情况
        $redis1->set($ip_record,json_encode($orderId_ip_record));
        $redis1->expire($ip_record,60*30);//三十分钟

        return $html;
    }

}
