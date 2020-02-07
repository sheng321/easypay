<?php
namespace app\withdrawal\controller\api;
use app\common\controller\WithdrawalController;
use think\helper\Str;
use tool\Curl;

//盈联科技 代付通道
class Yl extends WithdrawalController
{
    //通道配置信息
    protected $config;

    public function __construct()
    {
        parent::__construct();
        $classArr = explode('\\',get_class());
        $this->config = $this->set_config($classArr);
    }

/*
 * array(25) {
  ["id"] => int(22)
  ["system_no"] => string(20) "d2001301342478926444"
  ["mch_id"] => string(8) "20100005"
  ["lock_id"] => int(1)
  ["record"] => string(64) "admin选择下发代付通道:盈联|admin更新状态:处理中"
  ["remark"] => string(0) ""
  ["status"] => int(1)
  ["create_at"] => string(19) "2020-01-30 13:42:47"
  ["update_at"] => string(19) "2020-01-30 14:12:37"
  ["amount"] => string(6) "99.000"
  ["fee"] => string(5) "5.000"
  ["actual_amount"] => string(5) "0.000"
  ["create_by"] => int(52)
  ["update_by"] => int(1)
  ["bank"] => array(8) {
    ["id"] => int(12)
    ["card_number"] => string(19) "6236682080001705723"
    ["bank_name"] => string(18) "中国建设银行"
    ["account_name"] => string(9) "陈成阳"
    ["branch_name"] => string(54) "中国建设银行股份有限公司鄱阳鄱北支行"
    ["province"] => string(0) ""
    ["city"] => string(0) ""
    ["bank_id"] => int(105)
  }
  ["channel_id"] => int(62)
  ["channel_fee"] => string(5) "5.000"
  ["verson"] => int(2)
  ["transaction_no"] => string(0) ""
  ["remark1"] => string(0) ""
  ["out_trade_no"] => string(12) "后台申请"
  ["ip"] => string(9) "127.0.0.1"
  ["extends"] => NULL
  ["card_number"] => string(0) ""
  ["channel_amount"] => string(6) "99.000"
}
*/
    //发起代付订单
    public function pay($create){
        $pay_memberid = $create['mch_id'];                               //商户ID
        $pay_amount = $create['channel_amount'];                                                 //交易金额
        $bankfullname = $create['bank']['account_name'];                                         //开户名称
        $pay_bankname = $create['bank']['bank_name'];                                       //银行名称
        $bankzhiname = $create['bank']['branch_name'];            //支行名称
        $pay_card_no =  $create['bank']['card_number'];                            //银行卡号

        $requestarray = array(
            "pay_memberid" => $pay_memberid,
            "bankname" => $pay_bankname,
            "bankzhiname" => $bankzhiname,
            "bankfullname" => $bankfullname,
            "banknumber" => $pay_card_no,
            "money" => $pay_amount,
            "tongdao" => $this->config['secretkey']['tongdao'],
        );
        ksort($requestarray);
        reset($requestarray);
        $md5str = "";
        foreach ($requestarray as $key => $val) {
            $md5str = $md5str.$key."=>".$val."&";
        }
        $requestarray["pay_md5sign"] = strtoupper(md5($md5str."key=".$this->config['signkey']));
        $res = json_decode(Curl::post($this->config['gateway'], $requestarray),true);

        //添加到代付订单查询日志
        logs(json_encode($res,320).'|'.json_encode($requestarray,320),$type = 'withdrawal/'.$this->config['code'].'/pay');

        /**
         * Array
        (
        [status] => 0   int
        [message] => 签名验证失败
        [data] =>
        )
         *
        Array
        (
        [status] => 1
        [message] => 提交成功
        [data] => Array
        (
        [tkid] => 838
        [tkmoney] => 10.00
        [sxfmoney] => 2.00
        [money] => 10.00
        )
        )
         */
        if(empty($res)) return __err('代付通道异常');
        if($res['status'] === 0) return __err($res['message']);
        if($res['status'] === 1) return __suc($res['message'],[
            'actual_amount'=>$res['data']['money'],//实际到账
            'transaction_no'=>$res['data']['tkid'],//上游ID
            ]);

        return __err('未知');
    }

    //查询订单状态
    public function query($Order){
        $data['pay_memberid'] = $this->config['mch_id'];
        $data['tkid'] = $Order['transaction_no'];

        $res = json_decode(Curl::post($this->config['queryway'], $data),true);

        /*
         * array(3) {
              ["status"]=>
              int(1)
              ["message"]=>
              string(12) "查询成功"
              ["data"]=>
              string(1) "0"
            }
         */

        if(empty($res)) return __err('代付通道异常');

        if($res['status'] === 0) return __err($res['message']);
        //添加到代付订单查询日志
        logs(json_encode($res,320).'|'.json_encode($data,320),$type = 'withdrawal/'.$this->config['code'].'/query');
        if($res['status'] === 1){
            switch($res['data']){
                //打款成功
                case '2':
                    return __suc($res['message'],['status'=>3]);
                //打款失败
                case '3':
                    return __suc($res['message'],['status'=>4]);
                //处理中或订单错误
                default:
                    return __suc($res['message'],['status'=>2]);
            }
        }

    }
    //查询余额
    public function balance(){
        $data = array();
        $data['pay_memberid'] = $this->config['mch_id'];
        $res = Curl::post($this->config['balanceway'], $data);
        $resp = json_decode($res, true);

        $data['total_balance'] = 0;
        $data['balance'] = 0;
        if(!empty($resp)){
            foreach($resp as $k=>$v){
                if(!empty($v['factory_name']) && $v['factory_name'] === '汇总'){
                    continue;
                }
                if($v['zh_payname'] === '自定义接口'){
                    continue;
                }
                $data['balance'] = $v['money'];
                $data['total_balance'] = $v['money'] + $v['freezemoney'];
               // $data['title'] = $v['zh_payname'];
            }
        }
        return $data;
    }

}
