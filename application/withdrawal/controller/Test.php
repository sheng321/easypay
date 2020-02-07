<?php
namespace app\withdrawal\controller;
use app\common\controller\WithdrawalController;

class Test extends WithdrawalController
{
    public function index(){


        $order =[
          'money'=> 100,
          'bankname'=>'103',
          'subbranch'=>'农业银行',
          'accountname'=>'廖泳堂',
          'cardnumber' => '6230521130013348877',
          'province'=>'广州',
          'city'=>'深圳',
        ];
        $basic_data = [
            'order'=>$order,
            'bank'=>config('bank.')
        ];
        return $this->fetch('', $basic_data);
    }

    public function dodf(){
        $_POST =  $this->request->post();


        $_POST['out_trade_no'] = getOrder('c');    //测试订单号
        $_POST['mchid'] = config('set.memberid');

        if(empty( $_POST['mchid'])||empty($_POST['money'])||empty($_POST['bankname'])  || empty($_POST['accountname']) || empty($_POST['cardnumber'])){
            return exceptions("信息不完整！");
        }
        if($_POST['extends']) {
            $_POST['extends'] = base64_encode(json_encode($_POST['extends']));
        }
        ksort($_POST);
        $md5str = "";
        foreach ($_POST as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $sign = strtoupper(md5($md5str . "key=" . config('set.DfMd5key')));
        $param = $_POST;
        $param["pay_md5sign"] = $sign;

        halt(msg_post(config('set.df_api'), $param));

        return  msg_post(config('set.df_api'), $param);
    }

}
