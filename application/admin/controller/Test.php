<?php
namespace app\admin\controller;

use app\common\model\Umember;
use app\common\model\Umoney;
use think\Controller;
use redis\StringModel;
use think\Queue;
use tool\Curl;


class Test  extends Controller
{
    public function index()
    {

        array(10) {
        ["pay_amount"]=>
  string(6) "300.00"
        ["pay_applydate"]=>
  string(19) "2020-01-27 19:53:19"
        ["pay_bankcode"]=>
  string(6) "alipay"
        ["pay_callbackurl"]=>
  string(60) "http://admin_xyf987abc.xinyufu.com/Pay_Shen_callbackurl.html"
        ["pay_memberid"]=>
  string(8) "20100005"
        ["pay_notifyurl"]=>
  string(58) "http://admin_xyf987abc.xinyufu.com/Pay_Shen_notifyurl.html"
        ["pay_orderid"]=>
  string(19) "2020012719531994270"
        ["pay_md5sign"]=>
  string(32) "9B3F9658D9838EC478ABAE378322DC1E"
        ["pay_attach"]=>
  string(8) "1234|456"
        ["pay_productname"]=>
  string(12) "团购商品"
}


        return $this->fetch('', []);
    }

}
