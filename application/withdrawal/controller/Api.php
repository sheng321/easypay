<?php
namespace app\withdrawal\controller;
use app\common\controller\WithdrawalController;
use app\common\model\Df;
use app\common\model\Ip;
use app\common\model\Umoney;
use app\common\model\UmoneyLog;
use app\common\model\Uprofile;
use Lock\Lock;

/**
 * 代付下单接口
 * Class Api
 * @package app\withdrawal\controller
 */
class Api extends WithdrawalController
{
    public function index(){
        $param =   $this->request->only(["accountname" ,"bankname","cardnumber","city","extends" ,"mchid","money","out_trade_no","province","subbranch","pay_md5sign"],'post');



        //商户属性
       $Uprofile =  Uprofile::quickGet(['uid'=>$param['mchid']]);
       if(empty($Uprofile) || $Uprofile['who'] != 0 )  __jerror('商户号不存在');
        if(empty($Uprofile['df_api1']) || $Uprofile['df_api1'] != '1' )  __jerror('API代付接口未开通，请联系客服处理。');
        if( $Uprofile['df_api'] != '1' )  __jerror('商户未开启API代付接口功能。。。');

        //白名单验证
        $ips = Ip::bList($param['mchid'],2);
        if(!in_array(get_client_ip(),$ips)) return __error('异常IP');

        if(!check_sign($param,$Uprofile['df_secret']))  __jerror('签名错误');


        $param['bank_id'] = (int) $param['bankname'];
        $param['bankname'] = config('bank.'.$param['bank_id']);
        if(empty($param['bankname'])) __jerror('银行代码错误或者不支持此银行！');


        //商户金额
        $Umoney =  Umoney::quickGet(['uid'=>$param['mchid'],'channel_id'=>0,'df_id'=>0]);
        if(empty($Umoney) || $Umoney['df'] < $param['money']) __jerror('代付金额不足！');



        //平台代付通道属性
        $df = config('custom.df');
        /*
          'rate'=>'0.01',//  充值费率
        'fee'=>'5',//手续费 不填默认为0
        'min_pay'=>'1',//单笔最低 不填表示不限制
        'max_pay'=>'49900',//单笔最高 不填表示不限制
        'limit_times'=>'0',//单卡单日次数
        'limit_money'=>'0',//单卡单日限额
        'visit'=>'对私',//付款方式
        'inner'=>'外扣',//下发方式
        'total_money'=>'0',//会员单日提现额度
        'time'=>'',//格式：02:00|11:00 提现时间 不填表示任何时间都可以提现
         */

        if($param['money'] > $df['max_pay'] || $param['money'] < $df['min_pay'])   __jerror("代付金额在  {$df['min_pay']} 和 {$df['max_pay']} 范围内");
        if($param['money'] <= $df['fee'])   __jerror("代付金额小于手续费");

        //运营时间
        if(!empty($df['time'])){
            $period_time = explode("|",$df['time']);
            $time = strtotime(date('H:i',time()));//当前时间
            if($time > strtotime($period_time[0]) && $time > strtotime($period_time[1])){
                __jerror('请在 '.$period_time[0].' - '.$period_time[1].' 内进行提现申请');
            }
            unset($period_time);
        }

        $mch_id_money =  Df::mch_id_money($param['mchid']);
        if($mch_id_money > $df['total_money']) __jerror('已超过会员单日代付额度，请联系客服处理');

        $card_money =  Df::card_money($param['cardnumber']);
        if($card_money > $df['limit_money']) __jerror('已超过单卡单日限额，请联系客服处理');

        $times =  Df::times($param['cardnumber']);
        if($times > $df['limit_times']) __jerror('已超过单卡单日次数，请联系客服处理');


        //下一步选择创建订单，冻结金额，返回客户端信息

        $data['mch_id'] = $param['mchid'];
        $data['out_trade_no'] =  $param['out_trade_no'];//代付订单号
        $data['system_no'] = getOrder('d');//代付订单号
        $data['amount'] = $param['money'];
        $data['card_number'] = $param['cardnumber'];
        $data['bank'] = json_encode([
            'account_name'=>$param['accountname'],
            'bank_name'=>$param['bankname'],
            'card_number'=>$param['cardnumber'],
            'city'=>$param['city'],
            'province'=>$param['province'],
            'branch_name'=>$param['subbranch'],
            'bank_id'=>$param['bank_id'],
        ]);
        $data['fee'] = $df['fee'];
        $data['extends'] = $param['extends'];
        $data['lock_id'] = 0;

        //冻结用户金额

        $change['change'] = $data['amount'];//变动金额
        $change['relate'] = $data['system_no'];//关联订单号
        $change['type'] = 15;//代付冻结金额类型

        $res = Umoney::dispose($Umoney,$change); //处理
        if (true !== $res['msg']) __jerror($res['msg']);

        //插入数据库

        try{
            $lock_val = 'withdrawal:api:'.$data['system_no'];
            $create = Lock::queueLock(function ($res)  use ($data){
                $model = new Df();
                //使用事物保存数据
                $model->startTrans();
                $create = $model->create($data);
                $save = (new Umoney())->saveAll($res['data']);
                $add = (new UmoneyLog())->saveAll($res['change']);

                if (!$create ||!$save ||!$add) {
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

        $return = ['refCode'=>'4','transaction_id'=>$create['system_no'],'out_trade_no'=>$create['out_trade_no']]; //待处理
        $return['sign'] = create_sign($return,$Uprofile['df_secret']);

        logs('请求:'.json_encode($param).'返回报文:'.json_encode($return),'withdrawal/api/'.$param['mchid']);
        __jsuccess('代付申请成功',$return);
    }

}
