<?php
namespace app\admin\controller;

use app\common\model\SysAdmin;
use app\common\model\Umember;
use app\common\model\Umoney;
use app\common\model\Uprofile;
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


        $RedisModel = StringModel::instance();
        $RedisModel->select(0);

        try{
            $str = '11';
        $sta =    Lock::queueLock(function ($res){
                return 777;
            },$str);
        }catch (\Exception $e){
            halt($e->getMessage());
        }

        halt($sta);







        //$res = SysAdmin::delRedis(1);

        //halt( $res);

       // $res = \think\Queue::push('app\\common\\job\\Df', 289, 'df');
       // halt( $res);


    }

}
