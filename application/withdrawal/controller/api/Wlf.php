<?php
namespace app\withdrawal\controller\api;
use app\common\controller\WithdrawalController;
use tool\Curl;
use tool\rsa\WlfPaySign;

//网联付备用金 代付通道
class Wlf extends WithdrawalController
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
        //银行编码
        switch($create['bank']['bank_id']){

            //中国工商银行
            case '102':
                $bank_code = '102';
                break;
            //中国农业银行
            case '103':
                $bank_code = '103';
                break;
            //中国建设银行
            case '105':
                $bank_code = '105';
                break;
            //交通银行
            case '301':
                $bank_code = '301';
                break;
            //光大银行
            case '303':
                $bank_code = '303';
                break;
            //中国民生银行
            case '305':
                $bank_code = '305';
                break;

            //兴业银行
            case '309':
                $bank_code = '309';
                break;
            //招商银行
            case '308':
                $bank_code = '308';
                break;
            //中国银行
            case '104':
                $bank_code = '104';
                break;
            //广发银行
            case '306':
                $bank_code = '306';
                break;
            //中国邮政
            case '403':
                $bank_code = '403';
                break;
            //东亚银行
            case '502':
                $bank_code = '502';
                break;

            //中信银行
            case '302':
                $bank_code = '302';
                break;
            //华夏银行
            case '304':
                $bank_code = '304';
                break;
            //上海浦东发展银行
            case '310':
                $bank_code = '310';
                break;
            //深圳发展银行
            case '307':
                $bank_code = '307';
                break;
            //浙商银行
            case '316':
                $bank_code = '316';
                break;
            default:
                return __err("此通道暂不支持此下发银行卡");
        }

        $info['order_id'] = $create['system_no'];
        $info['amount'] = $create['channel_amount'] * 100;  // 单位分
        $info['bank_code'] = $bank_code;  // 银行代码
        $info['account_no'] = $create['bank']['card_number'];  // 银行帐号
        $info['account_name'] = iconv("UTF-8","GB2312//IGNORE",$create['bank']['account_name']);  // 报文是GBK，中文字符需要转换
        $gaohuitong_pay = new WlfPaySign();
        $res = $gaohuitong_pay->pay($info);

        logs(json_encode($info,320).'|'.json_encode($res,320),$type = 'withdrawal/'.$this->config['code'].'/pay');
        if(!$res || empty($res) || is_string($res)) return __err("代付通道异常1");
        /*
          * array(2) {
   ["INFO"]=>
   array(7) {
     ["TRX_CODE"]=>
     string(6) "100005"
     ["VERSION"]=>
     string(2) "04"
     ["DATA_TYPE"]=>
     string(1) "2"
     ["REQ_SN"]=>
     string(21) "s20200111154114892427"
     ["RET_CODE"]=>
     string(4) "0000"
     ["ERR_MSG"]=>
     string(18) "交易处理成功"
     ["SIGNED_MSG"]=>
     string(256) "3b9d3583d12f18f7bdc18ad77578ce3fb8410a27094eef1ee54c21c16d21741c6ece3c172f1685dc3af70d3c155d5366ef3a91264e552209fc4e24d4ed0a5688b63819d875e1fb9099d700a93b9f2f2fc91adcb2fd5656183f5470a65201c9888023c3d0a624c366e2bbc69cc91a9251455ac17e791b8f58d78a490a86b7a986"
   }
   ["BODY"]=>
   array(1) {
     ["RET_DETAILS"]=>
     array(1) {
       ["RET_DETAIL"]=>
       array(10) {
         ["SN"]=>
         string(4) "0001"
         ["ACCOUNT_NAME"]=>
         string(9) "廖泳堂"
         ["AMOUNT"]=>
         string(4) "2000"
         ["ACCOUNT_NO"]=>
         string(19) "6230521130013348877"
         ["CUST_USERID"]=>
         array(0) {
         }
         ["RET_CODE"]=>
         string(4) "0000"
         ["ERR_MSG"]=>
         string(15) "CI:交易成功"
         ["REMARK"]=>
         array(0) {
         }
         ["RESERVE1"]=>
         array(0) {
         }
         ["RESERVE2"]=>
         array(0) {
         }
       }
     }
   }
 }*/

        $RET_CODE1 = $res['INFO']['RET_CODE'];//交易受理
        $RET_CODE2 = empty($res['BODY']['RET_DETAILS']['RET_DETAIL']['RET_CODE'])?0:$res['BODY']['RET_DETAILS']['RET_DETAIL']['RET_CODE'];//交易结果

        switch(true){
            //成功
            case ($RET_CODE1 === "0000" && $RET_CODE2 === "0000"):
                return __suc($res["BODY"]["RET_DETAILS"]["RET_DETAIL"]['ERR_MSG']);
                break;
            case ($RET_CODE1 === "0000" && $RET_CODE2 === 0):
                return __err('代付通道异常3');
                break;
            case ($RET_CODE1 === "0001")://交易受理失败
                return __err('代付通道申请失败: '.$res["INFO"]['ERR_MSG']);
                break;
            case ($RET_CODE2 === 0):////交易结果状态不存在
            case ($RET_CODE2 === "0001")://交易失败具体原因在 ERR_MSG 中说明
            case ($RET_CODE2 === "0002")://商户审核不通过
            case ($RET_CODE2 === "0003")://不通过受理
                return __err('代付通道申请失败: '.$res["BODY"]["RET_DETAILS"]["RET_DETAIL"]['ERR_MSG']);
                break;
            //2 开头中间处理状态
            case ($RET_CODE2 === "2001")://等待商户审核
            case ($RET_CODE2 === "2002")://等待受理
            case ($RET_CODE2 === "2003")://等待复核
            case ($RET_CODE2 === "2004")://提交银行处理
                return __suc($res["BODY"]["RET_DETAILS"]["RET_DETAIL"]['ERR_MSG']);
                break;
            //1 开头系统错误
            case ($RET_CODE1 === "1000")://报文域内容检查错
            case ($RET_CODE1 === "1001")://报文解析错
            case ($RET_CODE1 === "1002")://未查询到该订单号对应的交易
            default:
                return __err('代付通道异常2');
                break;
        }
    }

    //查询订单状态
    public function query($Order){
        $info['order_id'] = $Order['system_no'];

        $gaohuitong_pay = new WlfPaySign();
        $res = $gaohuitong_pay->query($info);

        /*
         *
         * array(2) {
          ["INFO"]=>
          array(7) {
            ["TRX_CODE"]=>
            string(6) "200001"
            ["VERSION"]=>
            string(2) "03"
            ["DATA_TYPE"]=>
            string(1) "2"
            ["REQ_SN"]=>
            string(21) "s20200111145709858437"
            ["RET_CODE"]=>
            string(4) "0000"
            ["ERR_MSG"]=>
            string(18) "交易处理成功"
            ["SIGNED_MSG"]=>
            string(256) "06129d7ab37209366080bfea8cea8440d464c042137c5f3e07ad90e0bece6c865de18fe8bb73e1239f74e8b68dcdee1223c4e5a7d91a271429b68998328c58c909d01106f753929f368d60883d79d15ca151c05566bb7a2afaa21793ee067663f674e544697dfb8f14ace8ac874561b581aff7e68b6b25964ea36f270a3c7d94"
          }
          ["BODY"]=>
          array(2) {
            ["QUERY_TRANS"]=>
            array(1) {
              ["QUERY_SN"]=>
              string(21) "s20200111145709858437"
            }
            ["RET_DETAILS"]=>
            array(1) {
              ["RET_DETAIL"]=>
              array(8) {
                ["SN"]=>
                string(4) "0001"
                ["ACCOUNT"]=>
                string(19) "623052*********8877"
                ["ACCOUNT_NAME"]=>
                string(7) "廖*堂"
                ["AMOUNT"]=>
                string(4) "2000"
                ["CUST_USERID"]=>
                array(0) {
                }
                ["REMARK"]=>
                string(6) "汇款"
                ["RET_CODE"]=>
                string(4) "0000"
                ["ERR_MSG"]=>
                string(1) "2"
              }
            }
          }
        }*/

        if(!$res || empty($res) || is_string($res)) return __suc($res['message'],['status'=>2]);//处理中

        $RET_CODE1 = $res['INFO']['RET_CODE'];//交易受理
        $RET_CODE2 = empty($res['BODY']['RET_DETAILS']['RET_DETAIL']['RET_CODE'])?0:$res['BODY']['RET_DETAILS']['RET_DETAIL']['RET_CODE'];//交易结果

        switch(true){
            //成功
            case ($RET_CODE1 === "0000" && $RET_CODE2 === "0000"):
                return __suc($res['message'],['status'=>3]);//已完成
                break;
            case ($RET_CODE1 === "0000" && $RET_CODE2 === 0):
                return __suc($res['message'],['status'=>2]);//处理中
                break;
            //失败
            case ($RET_CODE2 === "0001")://交易失败
            case ($RET_CODE2 === "0002")://商户审核不通过
            case ($RET_CODE2 === "0003")://不通过受理
            case ($RET_CODE2 === 0)://交易结果状态不存在
                return __suc($res['message'],['status'=>4]);//失败退款
                break;
            //处理中
            case ($RET_CODE1 === "1002")://未查询到该订单号对应的交易
            default:
                return __suc($res['message'],['status'=>2]);//处理中
                break;
        }



    }
    //查询余额
    public function balance(){
        $info['order_id'] = date('YmdHis') . rand(10000, 99999);
        $gaohuitong_pay = new WlfPaySign();
        $res = $gaohuitong_pay->chk_account($info);

        /*
   * array(2) {
["INFO"]=>
array(7) {
["TRX_CODE"]=>
string(6) "200004"
["VERSION"]=>
string(2) "04"
["DATA_TYPE"]=>
string(1) "2"
["REQ_SN"]=>
string(19) "2020011115304520988"
["RET_CODE"]=>
string(4) "0000"
["ERR_MSG"]=>
string(18) "交易处理成功"
["SIGNED_MSG"]=>
string(256) "56ecab4f251211afa5970b4eff3fc2f23e8c9088ae138dbcec65193450644a2cda939cab116baa900f1b57c0f38456f4f4983ef3ca6f3b4d167040491bac271517bbd84937f58f493837578cb70e5e0412b9fc4867b3a7c22f659e7030691c0456b9983ec41ad59034f4433ceb508d57b1cf9440bdcf6b6be1a5523f5eef874f"
}
["BODY"]=>
array(2) {
["QUERY_TRANS"]=>
array(2) {
["MERCHANT_ID"]=>
string(15) "000000000100641"
["CURRENCY"]=>
string(3) "CNY"
}
["RET_DETAILS"]=>
array(1) {
["RET_DETAIL"]=>
array(3) {
  ["MERCHANT_NAME"]=>
  string(6) "测试"
  ["BALANCE"]=>
  string(9) "805132401" //账户余额
  ["FREEZE_AMOUNT"]=>
  string(5) "12776" //冻结金额
}
}
}
}}*/

        $data['total_balance'] = 0;
        $data['balance'] = 0;
        if( empty($res) || is_string($res) || $res['INFO']['RET_CODE'] !== "0000") return $data;

        $data['total_balance'] = floor($res['BODY']['RET_DETAILS']["RET_DETAIL"]["BALANCE"]) + floor($res['BODY']['RET_DETAILS']["RET_DETAIL"]["FREEZE_AMOUNT"]);
        $data['balance'] = floor($res['BODY']['RET_DETAILS']["RET_DETAIL"]["BALANCE"]) ;

       return $data;
    }

}
