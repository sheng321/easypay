<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\facade\Log;

use think\facade\Request;

use app\common\model\SysAdmin;

class Test  extends Controller
{
    public function index()
    {



        //  $rate1 =   \app\common\service\RateService::getMemRate('20100002','19');//平台下商户
        // $rate2=   \app\common\service\RateService::getMemRate('','');//代理下商户
        // $rate3 =   \app\common\service\RateService::getMemRate('','');//代理下商户
        // dump($rate1);
        //  dump($rate2);
        //  dump($rate3);

        $rate1 =   \app\common\service\RateService::getGroupStatus('26',20);//平台下商户
        dump($rate1);
        halt(1111);



    }

}
