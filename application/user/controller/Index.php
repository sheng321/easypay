<?php
namespace app\user\controller;

use app\common\controller\UserController;
use app\common\model\Accounts;

class Index extends UserController
{
    public function index()
    {
        $basic_data = [
            'title'=> '主页',
        ];

        return $this->fetch('', $basic_data);
    }

    /**
     * 商户公告
     * @return mixed
     */
    public function welcome() {


        $find =  Accounts::where(['uid'=>$this->user['uid'],'type'=>0])->order(['day'=>'desc'])->find();
        if(empty($data)){
            $data = [];
            $data['wait'] = 0;
        }else{
            $data = $find->toArray();
            $data['wait'] = $data['total_orders'] - $data['total_paid'];
        }


        //商户公告
        $Message =  model('app\common\model\Message')->where([
            ['title','<>','首页弹窗显示'],
            ['type','in',[0,1]]
        ])->order('id desc')->cache('Message_1',30)->select()->toArray();

        $basic_data = [
            'title'=> '主页',
            'message'=> $Message,
            'info'=> $data,//今天跑量详情
            'money'=> ['balance'=>$this->user['money']['balance'],'frozen_amount'=>$this->user['money']['frozen_amount']],
        ];

        return $this->fetch('',$basic_data);
    }

}
