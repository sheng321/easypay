<?php
namespace app\withdrawal\controller;
use app\common\controller\WithdrawalController;
use app\common\model\Df;
use app\common\model\Ip;
use app\common\model\Umoney;
use app\common\model\UmoneyLog;
use app\common\model\Uprofile;
use Lock\Lock;
use think\Db;

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
        $Umoney =  Db::table('cm_money')->where(['uid'=>$param['mchid'],'channel_id'=>0,'df_id'=>0])->find();
        if(empty($Umoney) || $Umoney['df'] < $param['money']) __jerror('代付金额不足！');


        //平台代付通道属性
        $df = config('custom.df');

        $check_df =  Df::check_df($param['money']);
        if($check_df !== true) return __jerror($check_df);

        //单卡单日次数
        $card_times_money = Df::card_times_money($param['cardnumber'],$param['money']);
        if($card_times_money !== true) return __jerror($card_times_money);

        //会员单日提现额度
        $mch_id_money = Df::mch_id_money($param['mchid'],$param['money']);
        if($mch_id_money !== true) return __jerror($mch_id_money);


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
