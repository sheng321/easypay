<?php
namespace app\withdrawal\controller;
use app\common\controller\WithdrawalController;
use app\common\model\Ip;
use app\common\model\Uprofile;
use think\Db;
use think\helper\Str;


/**
 * 代付订单查询接口
 * Class Query
 * @package app\pay\controller
 */
class Query extends WithdrawalController
{
    public function index(){
        $param =   $this->request->only(["out_trade_no" ,"mchid","pay_md5sign"],'post');

        //商户属性
       $Uprofile =  Uprofile::quickGet(['uid'=>$param['mchid']]);
       if(empty($Uprofile) || $Uprofile['who'] != 0 )  __jerror('商户号不存在');
        if(empty($Uprofile['df_api1']) || $Uprofile['df_api1'] != '1' )  __jerror('API代付接口未开通，请联系客服处理。');
        if( $Uprofile['df_api'] != '1' )  __jerror('商户未开启API代付接口功能。。。');

        //白名单验证
        $ips = Ip::bList($param['mchid'],2);
        if(!in_array(get_client_ip(),$ips))  __jerror('异常IP');

        if(!check_sign($param,$Uprofile['df_secret']))  __jerror('签名错误');

        $Order = Db::table('cm_withdrawal_api')->where(['out_trade_no'=>$param['out_trade_no']])->order(['id'=>'desc'])->find();
        if(empty($Order) || $Order['mch_id'] !== $param['mchid'] )   __jerror('交易不存在');

        $data = array();
        $data['mchid'] = $param['mchid'];
        $data['out_trade_no'] = $param['out_trade_no'];
        $data['amount'] = $Order['amount'];
        $data['transaction_id'] = $Order['system_no'];

        switch ($Order['status']){
            //未处理
            case 1:
                $data['refCode'] = '6';//待审核
                $data['refMsg'] = '待审核';
                break;
            //处理中
            case 2:
                $data['refCode'] = '3';//处理中
                $data['refMsg'] = '处理中';
                break;
            //已完成
            case 3:
                $data['refCode'] = '1';//成功
                $data['refMsg'] = '成功';
                $data['success_time'] = $Order['update_at'];
                break;
            //失败退款
            case 4:
                $data['refCode'] = '2';//失败
                $data['refMsg'] = '失败';
                break;
            default:
                $data['refCode'] = '8';//未知
                $data['refMsg'] = '未知状态';
                break;
        }

        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $data['sign'] = strtoupper(md5($md5str . "key=" . $Uprofile['df_secret']));

         __jsuccess('查询成功',$data);
    }


    public function balance(){
        $param =   $this->request->only(["nonce_str" ,"mchid","pay_md5sign"],'post');

        //商户属性
        $Uprofile =  Uprofile::quickGet(['uid'=>$param['mchid']]);
        if(empty($Uprofile) || $Uprofile['who'] != 0 )  __jerror('商户号不存在');
        if(empty($Uprofile['df_api1']) || $Uprofile['df_api1'] != '1' )  __jerror('API代付接口未开通，请联系客服处理。');
        if( $Uprofile['df_api'] != '1' )  __jerror('商户未开启API代付接口功能。。。');

        //白名单验证
        $ips = Ip::bList($param['mchid'],2);
        if(!in_array(get_client_ip(),$ips))  __jerror('异常IP');

        if(!check_sign($param,$Uprofile['df_secret']))  __jerror('签名错误');

        $user  = Db::table('cm_money')->where(['uid'=>$param['mchid'],'channel_id'=>0])->field('update_at')->find(); //商户金额
        if(empty($user))   __jerror('数据异常！');

        $data = array();
        $data['mchid'] = $param['mchid'];
        $data['nonce_str'] = Str::random(20);
        $data['balance'] = $user['df'];
        $data['time'] = $user['update_at'];

        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $data['sign'] = strtoupper(md5($md5str . "key=" . $Uprofile['df_secret']));

        __jsuccess('查询成功',$data);
    }

}
