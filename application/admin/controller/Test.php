<?php
namespace app\admin\controller;

use think\Controller;
use redis\StringModel;

class Test  extends Controller
{
    public function index()
    {
        $model = new StringModel();
        $model->key = 'redisun4444:{id}:string:{name}';
        $model->ttl = 555555555;

        $model->database = 5;

      //$res1 =  $model->newQuery()->where('name','maria')->delete();

       $id = $model->newQuery()->where('id','1')->first();


       $res = $model->insert([
            'id' => 1,
            'name' => 'maria',
        ],4444);




    }

}
