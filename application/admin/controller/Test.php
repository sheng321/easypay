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


        halt('您当前的IP是 '.get_client_ip());


    }

}
