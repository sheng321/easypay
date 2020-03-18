<?php
namespace app\admin\controller;

use app\common\model\SysAdmin;
use app\common\model\Umember;
use app\common\model\Umoney;
use app\common\model\Uprofile;
use app\common\service\MoneyService;
use app\common\service\SubTable;
use think\Controller;
use redis\StringModel;
use think\Queue;
use tool\Curl;
use Lock\Lock;




class Test  extends Controller
{
    public function index()
    {


        dump('您当前的IP是 '.get_client_ip());
        $ip = get_client_ip();

        $ip_start = bindec(decbin(ip2long('175.176.40.1'))); //起始ip
        $ip_end = bindec(decbin(ip2long('175.176.41.255')));//结束ip
        //可以这样简单判断
        if($ip < $ip_start || $ip >$ip_end){
            dump(222222);
        }


    }

}
