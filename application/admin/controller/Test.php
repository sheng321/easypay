<?php
namespace app\admin\controller;

use think\Controller;
use redis\StringModel;
use think\Queue;

class Test  extends Controller
{
    public function index()
    {
        $job = 'app\\common\\job\\Notify';//调用的任务名
        $data = 666666;//传入的数据
        $queue = 'notify';//队列名，可以理解为组名
        //push()方法是立即执行
        $res =  Queue::push($job, $data, $queue);



        $model = (new StringModel())->instance();

        dump(  $model->select(3));
        dump(1);
        $res =   $model->keys('queues:notify' . "*");
        halt($res);


/*        //加入异步队列
        $job = 'app\\common\\job\\Notify';//调用的任务名
        $data = 555555555;//传入的数据
        $queue = 'notify';//队列名，可以理解为组名
        //push()方法是立即执行
        $res =  Queue::push($job, $data, $queue);*/




    }

}
