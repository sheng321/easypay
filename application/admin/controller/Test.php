<?php
namespace app\admin\controller;

use app\common\model\Umember;
use app\common\model\Umoney;
use app\common\model\Uprofile;
use think\Controller;
use redis\StringModel;
use think\Queue;
use tool\Curl;


class Test  extends Controller
{
    public function index()
    {

       cookie('666666',1,[ 'samesite' => "None"]);

       halt( cookie('666666'));

      //  addTask(11,22,5);


/*        $data =  Uprofile::get_lower(0,0);
        halt($data);*/


/*        $uid = 20100010;
        \app\common\model\Umember::destroy(function($query) use ($uid){
            $query->where('uid','=',$uid);
        });
        \app\common\model\Uprofile::destroy(function($query)use ($uid){
            $query->where('uid','=',$uid);
        });
        \app\common\model\Umoney::destroy(function($query)use ($uid){
            $query->where('uid','=',$uid);
        });*/


    }

}
