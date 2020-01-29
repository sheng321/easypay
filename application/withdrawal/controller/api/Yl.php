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

    //发起代付订单
    public function pay($create){

        $pay_memberid = $create['mch_id'];                               //商户ID
        $pay_amount = $create['amount'];                                                 //交易金额
        $bankfullname = $create['bank']['accountname'];                                         //开户名称
        $pay_bankname = $create['bank']['bankname'];                                       //银行名称
        $bankzhiname = $create['bank']['subbranch'];            //支行名称
        $pay_card_no =  $create['bank']['cardnumber'];                            //银行卡号

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

        halt($res);
    }

    //查询订单状态
    public function query($Order,$result =['code' => 0, 'msg' => '查询失败', 'data' => []]){
        $data['pay_memberid'] = $this->config['mch_id'];
        $data['tkid'] = $Order['system_no'];

        $res = Curl::post($this->config['gateway'], $data);
        $resp = json_decode($res,true);

        halt($res);


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

        switch($res['data']){
            //打款成功
            case '2':
                return $this->checkQuery(1);
            //打款失败
            case '3':
                return $this->checkQuery(2);
            //处理中或订单错误
            default:
                return $this->checkQuery(0);
        }



          //添加到订单查询日志
         logs($res,$type = 'order/query/'.$this->config['code']);
         return ['code' => 1, 'msg' => '查询成功！', 'data' => $res];
    }
    //查询余额
    public function balance(){
        $data = array();
        $data['pay_memberid'] = $this->config['mch_id'];

        $res = Curl::post($this->config['gateway'], $data);

        halt($res);

        $res = json_decode($res, true);

        return $res;
    }

}
