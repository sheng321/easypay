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

        $res =  MoneyService::api('s2003091536151522649');


       // exceptions_api('当前访问人数过多，请稍后再试~');


       //  $res = \think\Queue::push('app\\common\\job\\Df', 363, 'df');
        halt( $res);


    }

}
