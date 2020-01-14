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
use think\facade\Env;

/**
 * 支付下单接口
 * Class Api
 * @package app\pay\controller
 */
class Api extends PayController
{
    public function index(){
        $param =   $this->request->only(["pay_memberid" ,"pay_orderid","pay_amount","pay_applydate","pay_bankcode" ,"pay_notifyurl","pay_callbackurl","pay_md5sign"],'post');


        //商户属性
       $Uprofile =  Uprofile::quickGet(['uid'=>$param['pay_memberid']]);
       if(empty($Uprofile) || $Uprofile['who'] != 0 )  __jerror('商户号不存在');
        if(empty($Uprofile['group_id']))  __jerror('未分配用户分组');


        if(!check_sign($param,$Uprofile['secret']))  __jerror('签名错误');


       //支付产品属性
       $PayProduct = PayProduct::quickGet(['code'=>$param['pay_bankcode']]);
       if(empty($PayProduct) || $PayProduct['status'] != 1) __jerror('通道不存在，或者已维护');

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
        if(empty($Ulevel) || $Ulevel['type1'] != 0 )  __jerror('未分配用户分组或商户的用户分组不正确');

        //通道分组ID
        $channel_group_idArr = json_decode($Ulevel['channel_id'],true);
        if(empty($channel_group_idArr) ||  !isset($channel_group_idArr[$PayProduct['id']]) )  __jerror('未分配支付通道分组1');


        //所有在线的支付通道分组及其支付通道
        $ChannelProduct = [];
        foreach ($channel_group_idArr[$PayProduct['id']] as $k => $v){
            $temp1['group_id'] = $v;
            $temp1['p_id'] = $PayProduct['id'];
            $temp = ChannelProduct::where($temp1)->select()->toArray();
            if(!empty($temp)){
                $ChannelProduct =  array_merge($ChannelProduct,$temp);
            }
        }
        if(empty($ChannelProduct)) __jerror('未分配支付通道2');


        $train['channel_id'] = [];
        $train['channel_group_id'] = [];

        //排除
        foreach ($ChannelProduct as $k =>$v){
            $Channel = Channel::quickGet($v['channel_id']);

            if(empty($Channel) || $Channel['status'] != 1){
                unset($ChannelProduct[$k]);
                continue;
            }

            //验证支付通道金额
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

            //判断并发
            //todo

            //话费通道 查询库存


            //轮训
            if(!empty($v['weight']) &&  is_int($v['weight']) && $v['weight'] > 0 ){
               $temp2 = array_fill(0, $v['weight'], $Channel['id']);//填充数组   支付通道ID
                $temp3 = array_fill(0, $v['weight'], $v['group_id']);//填充数组  支付通道分组ID
               $train['channel_id'] =  array_merge($temp2,$train['channel_id']);
                $train['channel_group_id'] =  array_merge($temp3,$train['channel_group_id']);
            }else{
                array_push($train['channel_id'],$Channel['id']);
                array_push($train['channel_group_id'],$v['group_id']);
            }
        }
        unset($v);
        unset($temp2);
        unset($temp3);
        unset($Channel);


        if(empty($ChannelProduct) || empty($train)){
            //记录
            logs(json_encode($param).'-----'.json_encode($channel_group_idArr[$PayProduct['id']]).'通道分组ID-未匹配支付通道3',$type = 'api');
            __jerror('未匹配支付通道3');
        }

        //轮训通道
        $random_keys = array_rand($train['channel_id'],1);//随机抽取一个
        $channel_id =  $train['channel_id'][$random_keys];
        $channel_group_id =  $train['channel_group_id'][$random_keys];

        if(empty($channel_id)) __jerror('未匹配支付通道4');
        unset($train);
        unset($random_keys);

        //获取商户费率
        $MemRate =  RateService::getMemRate($param['pay_memberid'],$PayProduct['id'],$channel_id);

        $AgentRate1 = 0;
        $AgentRate2 = 0;
        $uid1 = 0;
        $uid2 = 0;
        //代理费率  只记二级代理
        if($Uprofile['pid'] > 0){
            $uid1 = $Uprofile['pid'];
            $AgentRate1 =  RateService::getAgentRate($Uprofile['pid'],$ChannelProduct[$channel_id]['group_id']);
             //如果商户费率大于代理费率  不给代理分配费率
            if($MemRate <= $AgentRate1) $AgentRate1 = 0;

           $Uprofile1 = Uprofile::quickGet(['uid'=>$Uprofile['pid']]);
            if($Uprofile1['pid'] > 0){
                $uid2 = $Uprofile1['pid'];
                $AgentRate2 =  RateService::getAgentRate($Uprofile1['pid'],$ChannelProduct[$channel_id]['group_id']);
                //如果下级费率大于代理费率  不给代理分配费率
                if($AgentRate1 <= $AgentRate2) $AgentRate2 = 0;
                //如果商户费率大于代理费率  不给代理分配费率
                if($MemRate <= $AgentRate2) $AgentRate2 = 0;
            }
        }


        //检测订单好是否重复
        $date = timeToDate(0,0,0,-3); //默认只搜索3天
       $id =  Order::where([
           ['out_trade_no','=',$param['pay_orderid']],
           ['create_at','>',$date],
       ])->value('id');
        if(!empty($id)) __jerror('订单号重复！');



        //已选中的通道产品
        $Channel = Channel::quickGet($channel_id);
        //已选中所属通道
        $Channel_father = Channel::quickGet($Channel['pid']);
        if(empty($Channel_father) || empty($Channel_father['code']) || empty($Channel_father['limit_time'])) __jerror('支付服务不存在0');


        $data['mch_id'] = $param['pay_memberid'];//商户号
        $data['mch_id1'] = $uid1;//上级代理
        $data['mch_id2'] = $uid2;//上上级代理
        $data['out_trade_no'] = $param['pay_orderid'];//商户订单号
        $data['systen_no'] =  getOrder('s');//平台订单号
        $data['amount'] = number_format($param['pay_amount'],2,'.','');//下单金额
        $data['cost_rate'] = $Channel['c_rate'];//成本费率
        $data['run_rate'] = $MemRate;//运营费率
        $data['total_fee'] = $data['amount']*$MemRate;//运营手续费
        $data['settle'] = $data['amount'] - $data['total_fee'] ;//商户结算
        $data['agent_rate'] =  $AgentRate1;//上级代理费率
        $data['agent_rate2'] =  $AgentRate2;//上上级代理费率
        $data['upstream_settle'] = $data['amount']*$Channel['c_rate'];//上游结算
        $data['agent_amount'] =$AgentRate1 == 0?0: $data['amount']*($MemRate -  $AgentRate1);//上级代理商结算
        $data['agent_amount2'] =$AgentRate2 ==0?0: $data['amount']*($AgentRate1 -  $AgentRate2);//上上级代理商结算
        $data['channel_id'] = $channel_id;//渠道id
        $data['channel_group_id'] = $channel_group_id;//支付通道分组ID
        $data['pay_code'] = $PayProduct['code'];
        $data['payment_id'] = $PayProduct['id'];
        $data['notify_url'] = $param['pay_notifyurl'];//异步回调
        $data['callback_url'] = $param['pay_callbackurl'];//同步跳转
        $data['ip'] = get_client_ip();//请求ip
        $data['Platform'] = $data['amount']*($MemRate -  max($data['cost_rate'],$AgentRate1,$AgentRate2));//平台收益
        $data['create_time'] =  $param['pay_applydate'];//商户提交时间

        $data['over_time'] = time() + $Channel_father['limit_time']*60;//订单过期时间

        $param1 = $this->request->only(["pay_productname","pay_attach"],'post');
        $data['productname'] = $param1['pay_productname'];//商品名称
        $data['attach'] = $param1['pay_attach'];//备注

        //插入数据库
        //文件排它锁 阻塞模式
        $fp = fopen("lock/api.txt", "w+");
        if(flock($fp,LOCK_EX))
        {
            $model = new Order();
            //使用事物保存数据
            $model->startTrans();
            $create = $model->create($data);
            if (!$create) {
                $model->rollback();
            }else{
                $model->commit();
            }
            flock($fp,LOCK_UN);
        }
        fclose($fp);

        if(empty($create) || !$create)  __jerror('系统繁忙，请重试~');
        $create['code'] = $Channel['code'];

        //提交上游
        $Payment = Payment::factory($Channel_father['code']);
        // $Payment = Payment::factory('Index');
        $html  = $Payment->pay($create);
        return $html;
    }

}
