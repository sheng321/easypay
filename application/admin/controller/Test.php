<?php
namespace app\admin\controller;

use think\Controller;
use redis\StringModel;
use think\Queue;
use tool\Curl;


class Test  extends Controller
{
    public function index()
    {

        $job = 'app\\common\\job\\Notify';//调用的任务名
        $data = 99999999;//传入的数据
        $queue = 'notify';//队列名，可以理解为组名
        //push()方法是立即执行
        $res =  Queue::push($job, $data, $queue);

        dump($res);
        dump(111);

        $model = (new StringModel())->instance();
        $model->select(3);

        dump(1);
       // $data =  $model->lrange("queues:notify:delayed", 0 ,100);

        $data =  $model->zRange("queues:notify:delayed",0,-1,true);
        halt($data);
        dump(222);
        foreach ($data as $k =>$v ){
            $data[$k] = json_decode($v,true);
        }

        $data[0]['attempts'] = 66;

        //通过索引修改列表中元素的值，如果没有该索引，则返回false。
        //$model->lSet('queues:notify', 0, json_encode($data[0]));


        halt($data);


/*        //加入异步队列
        $job = 'app\\common\\job\\Notify';//调用的任务名
        $data = 555555555;//传入的数据
        $queue = 'notify';//队列名，可以理解为组名
        //push()方法是立即执行
        $res =  Queue::push($job, $data, $queue);*/




    }

}
