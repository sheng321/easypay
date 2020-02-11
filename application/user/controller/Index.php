<?php
namespace app\user\controller;

use app\common\controller\UserController;

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

        $data =  \app\common\service\CountService::mem_today_account();
        if(!empty($data['data'][$this->user['uid']])) $data['data'] = $data['data'][$this->user['uid']];
        if(empty($data['data']['total_orders'])){
            $data['data']['wait'] = 0;
        }else{
            $data['data']['wait'] = $data['data']['total_orders'] - $data['data']['total_paid'];
        }

        $money = \app\common\model\Umoney::quickGet(['uid'=>$this->user['uid'],'channel_id'=>0]);

        //商户公告
        $Message =  model('app\common\model\Message')->where([
            ['title','<>','首页弹窗显示'],
            ['type','in',[0,1]]
        ])->order('id desc')->cache('Message_1',30)->select()->toArray();

        $basic_data = [
            'title'=> '主页',
            'message'=> $Message,
            'info'=> $data,//今天跑量详情
            'money'=> ['balance'=>$money['balance'],'frozen_amount'=>$money['frozen_amount']],
        ];

        return $this->fetch('',$basic_data);
    }

}
